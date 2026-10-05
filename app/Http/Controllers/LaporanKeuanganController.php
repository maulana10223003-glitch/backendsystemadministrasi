<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\KantorSaldoMutasi;
use App\Models\PettyCashTransaction;
use Illuminate\Http\Request;

class LaporanKeuanganController extends Controller
{
    public function index(Request $request) {
        $dari = $request->input('dari');
        $sampai = $request->input('sampai');

        $transaksiQuery = Transaksi::with('items')
            ->when($dari, fn($q) => $q->where('tgl_pengerjaan', '>=', $dari))
            ->when($sampai, fn($q) => $q->where('tgl_pengerjaan', '<=', $sampai));

        $semuaTransaksi = $transaksiQuery->get();

        $omzetTercatat = $semuaTransaksi->reduce(function ($sum, $t) {
            return $sum + $t->items->reduce(fn($s, $i) => $s + ($i->qty * $i->harga_jual), 0);
        }, 0);

        $piutang = $semuaTransaksi->where('status_pembayaran', '!=', 'Lunas')
            ->reduce(function ($sum, $t) {
                return $sum + $t->items->reduce(fn($s, $i) => $s + ($i->qty * $i->harga_jual), 0);
            }, 0);

        $kantorQuery = KantorSaldoMutasi::query()
            ->when($dari, fn($q) => $q->whereDate('created_at', '>=', $dari))
            ->when($sampai, fn($q) => $q->whereDate('created_at', '<=', $sampai));

        $labaBarangRealized = (clone $kantorQuery)->where('kategori', 'Laba Barang')->sum('jumlah');
        $bagiHasilJasaRealized = (clone $kantorQuery)->where('kategori', 'Bagi Hasil Jasa')->sum('jumlah');
        $tabunganKantorRealized = (clone $kantorQuery)->where('kategori', 'Tabungan 5%')->sum('jumlah');

        $totalSaldoKantor = KantorSaldoMutasi::selectRaw(
            "SUM(CASE WHEN jenis = 'Kredit' THEN jumlah ELSE -jumlah END) as saldo"
        )->value('saldo') ?? 0;

        $saldoPettyCash = PettyCashTransaction::selectRaw(
            "SUM(CASE WHEN jenis = 'Masuk' THEN jumlah ELSE -jumlah END) as saldo"
        )->value('saldo') ?? 0;

        return response()->json([
            'periode' => ['dari' => $dari, 'sampai' => $sampai],
            'accrual' => [
                'omzet_tercatat' => $omzetTercatat,
                'piutang_belum_lunas' => $piutang,
                'jumlah_transaksi' => $semuaTransaksi->count(),
            ],
            'realized' => [
                'laba_barang' => $labaBarangRealized,
                'bagi_hasil_jasa_kantor' => $bagiHasilJasaRealized,
                'tabungan_kantor' => $tabunganKantorRealized,
                'total_realized_periode' => $labaBarangRealized + $bagiHasilJasaRealized + $tabunganKantorRealized,
            ],
            'saldo_saat_ini' => [
                'saldo_kantor_total' => $totalSaldoKantor,
                'saldo_petty_cash' => $saldoPettyCash,
            ],
        ]);
    }

    public function rincian(Request $request)
    {
        $dari = $request->input('dari');
        $sampai = $request->input('sampai');

        $transaksi = Transaksi::with('items', 'teknisis')
            ->when($dari, fn($q) => $q->where('tgl_pengerjaan', '>=', $dari))
            ->when($sampai, fn($q) => $q->where('tgl_pengerjaan', '<=', $sampai))
            ->orderBy('tgl_pengerjaan')
            ->get();

        $hasil = $transaksi->map(function ($t) {
            $labaBarang = 0;
            $modal = 0;
            $omzetJasa = 0;
            $invoice = 0;

            foreach ($t->items as $item) {
                $subtotal = $item->qty * $item->harga_jual;
                $invoice += $subtotal;

                if ($item->kategori === 'Jasa') {
                    $omzetJasa += $subtotal;
                } elseif ($item->kategori === 'Paket') {
                    // Nilai jasa sudah tersnapshot per-unit saat transaksi dibuat
                    $jasaDariPaket = $item->nilai_jasa ?? 0;
                    $subtotalJasaItem = $item->qty * $jasaDariPaket;
                    $omzetJasa += $subtotalJasaItem;

                    $modal += $item->qty * $item->harga_beli;
                    $labaBarang += ($subtotal - $subtotalJasaItem - ($item->qty * $item->harga_beli));
                } else {
                    $modal += $item->qty * $item->harga_beli;
                    $labaBarang += $subtotal - ($item->qty * $item->harga_beli);
                }
            }

            $bop = $t->bop ?? 0;

            if ($omzetJasa > 0) {
                $sisa1 = $omzetJasa - $bop;
                $potongan10 = round($sisa1 * 0.10);
                $tabunganKantor = round($potongan10 * 0.5);
                $tabunganTeknisiTotal = $potongan10 - $tabunganKantor;
                $sisaJasa = $sisa1 - $potongan10;
                $bagiHasilKantor = round($sisaJasa * 0.5);
                $bagiHasilTeknisiTotal = $sisaJasa - $bagiHasilKantor;
            } else {
                $potongan10 = 0;
                $tabunganKantor = 0;
                $tabunganTeknisiTotal = 0;
                $sisaJasa = 0;
                $bagiHasilKantor = 0;
                $bagiHasilTeknisiTotal = 0;
            }

            $totalKantor = $bagiHasilKantor;
            $totalTeknisi = $bagiHasilTeknisiTotal;

            return [
                'id' => $t->id,
                'transaksi_id' => $t->kode_transaksi ?? $t->id_transaksi ?? $t->id,
                'pelanggan' => $t->pelanggan ?? $t->nama_pelanggan ?? $t->nama ?? '-',
                'tanggal' => $t->tgl_pengerjaan,
                'lokasi' => $t->alamat,
                'status' => $t->status_pembayaran,
                'invoice' => $invoice,
                'modal' => $modal,
                'omzet_jasa' => $omzetJasa,
                'bop' => $bop,
                'potongan_10' => $potongan10,
                'sisa_jasa' => $omzetJasa > 0 ? $sisaJasa : 0,
                'kantor' => $totalKantor,
                'teknisi' => $totalTeknisi,
                'ket_teknisi' => $t->teknisis->pluck('nama')->join(', '),
            ];
        });

        return response()->json($hasil);
    }
}