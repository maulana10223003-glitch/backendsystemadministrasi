<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\Barang;
use App\Models\PaketKomponen; // Tambahan Model PaketKomponen
use App\Models\TransaksiDokumentasi;
use App\Models\PettyCashTransaction;
use App\Models\TeknisiSaldoMutasi;
use App\Models\KantorSaldoMutasi;
use App\Models\SaldoTeknisiUmum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksiController extends Controller
{
    public function index() {
        return Transaksi::with('items', 'teknisis', 'dokumentasi')->get();
    }

    public function store(Request $request) {
        $data = $request->validate([
            'pelanggan' => 'required',
            'alamat' => 'required',
            'telepon' => 'required',
            'tgl_pengerjaan' => 'required|date',
            'tgl_bayar' => 'nullable|date',
            'tgl_invoice' => 'nullable|date',
            'metode_bayar' => 'required|in:Tunai,Transfer Bank,QRIS',
            'status_pengerjaan' => 'required|in:Menunggu,Dikerjakan,Selesai',
            'status_pembayaran' => 'required|in:Belum Bayar,DP,Lunas',
            'bop' => 'nullable|integer|min:0',
            'teknisi_ids' => 'required|array|min:1',
            'teknisi_ids.*' => 'exists:teknisis,id',
            'items' => 'required|array|min:1',
            'items.*.barang_id' => 'required',
            'items.*.nama' => 'required',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga_jual' => 'required|integer',
        ]);

        return DB::transaction(function () use ($data) {
            $data['id'] = $this->generateTransaksiId();

            $transaksi = Transaksi::create(collect($data)->except(['items', 'teknisi_ids'])->toArray());

            foreach ($data['items'] as $item) {
                $this->buatTransaksiItem($transaksi, $item);
            }

            $transaksi->teknisis()->attach($data['teknisi_ids']);

            if (!empty($data['bop']) && $data['bop'] > 0) {
                PettyCashTransaction::create([
                    'tanggal' => $data['tgl_pengerjaan'],
                    'jenis' => 'Keluar',
                    'kategori' => 'BOP',
                    'jumlah' => $data['bop'],
                    'keterangan' => "BOP untuk transaksi {$transaksi->id}",
                    'transaksi_id' => $transaksi->id,
                ]);
            }

            if ($transaksi->status_pembayaran === 'Lunas') {
                $this->prosesPelunasan($transaksi);
            }

            return $transaksi->load('items', 'teknisis');
        });
    }

    public function show(Transaksi $transaksi) {
        return $transaksi->load('items', 'teknisis', 'dokumentasi');
    }

    public function update(Request $request, Transaksi $transaksi) {
        $data = $request->validate([
            'pelanggan' => 'sometimes|required',
            'alamat' => 'sometimes|required',
            'telepon' => 'sometimes|required',
            'tgl_pengerjaan' => 'sometimes|required|date',
            'tgl_bayar' => 'nullable|date',
            'tgl_invoice' => 'nullable|date',
            'metode_bayar' => 'sometimes|required|in:Tunai,Transfer Bank,QRIS',
            'status_pengerjaan' => 'sometimes|required|in:Menunggu,Dikerjakan,Selesai',
            'status_pembayaran' => 'sometimes|required|in:Belum Bayar,DP,Lunas',
            'bop' => 'nullable|integer|min:0',
            'teknisi_ids' => 'sometimes|array|min:1',
            'teknisi_ids.*' => 'exists:teknisis,id',
            'items' => 'sometimes|array|min:1',
            'items.*.barang_id' => 'required_with:items',
            'items.*.nama' => 'required_with:items',
            'items.*.qty' => 'required_with:items|integer|min:1',
            'items.*.harga_jual' => 'required_with:items|integer',
        ]);

        return DB::transaction(function () use ($data, $transaksi) {
            $statusSebelumnya = $transaksi->status_pembayaran;

            $transaksi->update(collect($data)->except(['items', 'teknisi_ids'])->toArray());

            if (isset($data['items'])) {
                // Kembalikan stok lama sebelum item lama dihapus
                foreach ($transaksi->items as $oldItem) {
                    $oldBarang = Barang::where('id', $oldItem->barang_id)->first();
                    
                    if ($oldBarang) {
                        if ($oldBarang->kategori === 'Paket') {
                            $isiPaket = PaketKomponen::where('paket_id', $oldBarang->id)->get();
                            
                            foreach ($isiPaket as $komponen) {
                                $barangFisik = Barang::where('id', $komponen->komponen_id)->first();
                                if ($barangFisik && $barangFisik->kategori !== 'Jasa') {
                                    $stokKembali = $komponen->qty * $oldItem->qty;
                                    $barangFisik->increment('stok', $stokKembali);
                                }
                            }
                        } elseif ($oldBarang->kategori !== 'Jasa') {
                            $oldBarang->increment('stok', $oldItem->qty);
                        }
                    }
                }

                $transaksi->items()->delete();

                foreach ($data['items'] as $item) {
                    $this->buatTransaksiItem($transaksi, $item);
                }
            }

            if (isset($data['teknisi_ids'])) {
                $transaksi->teknisis()->sync($data['teknisi_ids']);
            }

            $transaksi->refresh();

            if ($transaksi->status_pembayaran === 'Lunas'
                && $statusSebelumnya !== 'Lunas'
                && !$transaksi->sudah_diproses_pelunasan) {
                $this->prosesPelunasan($transaksi);
            }

            return $transaksi->load('items', 'teknisis', 'dokumentasi');
        });
    }

    public function destroy(Transaksi $transaksi) {
        return DB::transaction(function () use ($transaksi) {
            $id = $transaksi->id;

            // Kembalikan stok barang jika transaksi dibatalkan/dihapus
            foreach ($transaksi->items as $oldItem) {
                $oldBarang = Barang::where('id', $oldItem->barang_id)->first();
                
                if ($oldBarang) {
                    if ($oldBarang->kategori === 'Paket') {
                        $isiPaket = PaketKomponen::where('paket_id', $oldBarang->id)->get();
                        
                        foreach ($isiPaket as $komponen) {
                            $barangFisik = Barang::where('id', $komponen->komponen_id)->first();
                            if ($barangFisik && $barangFisik->kategori !== 'Jasa') {
                                $stokKembali = $komponen->qty * $oldItem->qty;
                                $barangFisik->increment('stok', $stokKembali);
                            }
                        }
                    } elseif ($oldBarang->kategori !== 'Jasa') {
                        $oldBarang->increment('stok', $oldItem->qty);
                    }
                }
            }

            // Hapus semua jejak keuangan yang terhubung ke transaksi ini
            KantorSaldoMutasi::where('transaksi_id', $id)->delete();
            TeknisiSaldoMutasi::where('transaksi_id', $id)->delete();
            SaldoTeknisiUmum::where('transaksi_id', $id)->delete();
            PettyCashTransaction::where('transaksi_id', $id)->delete();

            $transaksi->delete();

            return response()->noContent();
        });
    }

    public function uploadDokumentasi(Request $request, Transaksi $transaksi) {
        $request->validate([
            'foto' => 'required|image|max:5120',
        ]);
        $path = $request->file('foto')->store('dokumentasi', 'public');
        $dok = $transaksi->dokumentasi()->create(['path' => $path]);
        return response()->json($dok, 201);
    }

    public function deleteDokumentasi(Transaksi $transaksi, TransaksiDokumentasi $dokumentasi) {
        \Storage::disk('public')->delete($dokumentasi->path);
        $dokumentasi->delete();
        return response()->noContent();
    }

    /**
     * Buat 1 baris TransaksiItem dari input item transaksi.
     */
    private function buatTransaksiItem(Transaksi $transaksi, array $item): void
    {
        $barang = Barang::where('id', $item['barang_id'])->first();
        $hargaBeliFinal = 0;
        $kategoriFinal = null;
        $nilaiJasaFinal = 0;

        if ($barang) {
            $hargaBeliFinal = $barang->harga_beli ?? 0;
            $kategoriFinal = $barang->kategori ?? null;

            if ($kategoriFinal === 'Paket') {
                $nilaiJasaFinal = $barang->nilai_jasa ?? 0;
                
                // === POTONG STOK KOMPONEN DARI TABEL PAKET_KOMPONEN ===
                $isiPaket = PaketKomponen::where('paket_id', $barang->id)->get();
                
                foreach ($isiPaket as $komponen) {
                    $barangFisik = Barang::where('id', $komponen->komponen_id)->first();
                    if ($barangFisik && $barangFisik->kategori !== 'Jasa') {
                        $stokBerkurang = $komponen->qty * $item['qty'];
                        $barangFisik->decrement('stok', $stokBerkurang);
                    }
                }
                
            } elseif ($kategoriFinal !== 'Jasa') {
                // Barang fisik biasa: potong stok
                $barang->decrement('stok', $item['qty']);
            }
        }

        $transaksi->items()->create([
            'barang_id' => $item['barang_id'],
            'nama' => $item['nama'],
            'qty' => $item['qty'],
            'harga_jual' => $item['harga_jual'],
            'harga_beli' => $hargaBeliFinal,
            'kategori' => $kategoriFinal,
            'nilai_jasa' => $nilaiJasaFinal,
        ]);
    }

    private function generateTransaksiId() {
        $last = Transaksi::orderByRaw("CAST(SUBSTRING(id, 5) AS UNSIGNED) DESC")->first();
        $nextNumber = $last ? ((int) substr($last->id, 4)) + 1 : 1;
        return 'TRX-' . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
    }

    private function prosesPelunasan(Transaksi $transaksi)
    {
        $transaksi->load('items', 'teknisis');

        $labaBarang = 0;
        $totalJasa = 0;

        foreach ($transaksi->items as $item) {
            if ($item->kategori === 'Jasa') {
                $totalJasa += $item->qty * $item->harga_jual;
            } elseif ($item->kategori === 'Paket') {
                $subtotalJasaItem = $item->qty * ($item->nilai_jasa ?? 0);
                $totalJasa += $subtotalJasaItem;

                $subtotalHargaJual = $item->qty * $item->harga_jual;
                $subtotalHargaBeli = $item->qty * $item->harga_beli;
                $labaBarang += ($subtotalHargaJual - $subtotalJasaItem - $subtotalHargaBeli);
            } else {
                $labaBarang += $item->qty * ($item->harga_jual - $item->harga_beli);
            }
        }

        if ($labaBarang != 0) {
            KantorSaldoMutasi::create([
                'transaksi_id' => $transaksi->id,
                'jenis' => 'Kredit',
                'kategori' => 'Laba Barang',
                'jumlah' => $labaBarang,
                'keterangan' => "Laba penjualan barang - transaksi {$transaksi->id}",
            ]);
        }

        $bop = $transaksi->bop ?? 0;

        if ($totalJasa > 0) {
            $sisa1 = $totalJasa - $bop;
            $potongan10 = round($sisa1 * 0.10);
            $tabunganKantor = round($potongan10 * 0.5);
            $tabunganTeknisiTotal = $potongan10 - $tabunganKantor;
            $sisa2 = $sisa1 - $potongan10;
            $bagiHasilKantor = round($sisa2 * 0.5);
            $bagiHasilTeknisiTotal = $sisa2 - $bagiHasilKantor;

            $teknisiIds = $transaksi->teknisis->pluck('id');
            $jumlahTeknisi = max($teknisiIds->count(), 1);

            $bagiHasilPerTeknisi = intdiv($bagiHasilTeknisiTotal, $jumlahTeknisi);

            KantorSaldoMutasi::create([
                'transaksi_id' => $transaksi->id,
                'jenis' => 'Kredit',
                'kategori' => 'Tabungan 5%',
                'jumlah' => $tabunganKantor,
                'keterangan' => "Tabungan 5% dari jasa - transaksi {$transaksi->id}",
            ]);
            KantorSaldoMutasi::create([
                'transaksi_id' => $transaksi->id,
                'jenis' => 'Kredit',
                'kategori' => 'Bagi Hasil Jasa',
                'jumlah' => $bagiHasilKantor,
                'keterangan' => "Bagi hasil jasa (kantor) - transaksi {$transaksi->id}",
            ]);

            if ($tabunganTeknisiTotal > 0) {
                SaldoTeknisiUmum::create([
                    'tanggal' => now()->toDateString(),
                    'jenis' => 'Masuk',
                    'kategori' => 'Tabungan 5% dari Jasa',
                    'jumlah' => $tabunganTeknisiTotal,
                    'keterangan' => "Tabungan 5% dari jasa - transaksi {$transaksi->id}",
                    'transaksi_id' => $transaksi->id,
                ]);
            }

            foreach ($teknisiIds as $teknisiId) {
                TeknisiSaldoMutasi::create([
                    'teknisi_id' => $teknisiId,
                    'transaksi_id' => $transaksi->id,
                    'jenis' => 'Kredit',
                    'kategori' => 'Bagi Hasil Jasa',
                    'kelompok' => 'Gaji',
                    'jumlah' => $bagiHasilPerTeknisi,
                    'keterangan' => "Bagi hasil jasa - transaksi {$transaksi->id}",
                ]);
            }
        }

        if ($bop > 0) {
            PettyCashTransaction::create([
                'tanggal' => now()->toDateString(),
                'jenis' => 'Masuk',
                'kategori' => 'Pengembalian BOP',
                'jumlah' => $bop,
                'keterangan' => "Pengembalian BOP - transaksi {$transaksi->id}",
                'transaksi_id' => $transaksi->id,
            ]);
        }

        $transaksi->update(['sudah_diproses_pelunasan' => true]);
    }
}