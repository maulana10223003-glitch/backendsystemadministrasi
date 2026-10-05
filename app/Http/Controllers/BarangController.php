<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\PaketKomponen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarangController extends Controller
{
    public function index() {
        return Barang::with('items.komponen')->get();
    }

    public function store(Request $request) {
        $data = $request->validate([
            'nama' => 'required',
            'kategori' => 'required',
            'stok' => 'nullable|integer',
            'satuan' => 'required',
            'harga_beli' => 'nullable|integer',
            'harga_jual' => 'required|integer',
            'items' => 'nullable|array',
            'items.*.barang_id' => 'required_with:items|exists:barangs,id',
            'items.*.qty' => 'required_with:items|integer|min:1',
        ]);

        $data['id'] = $this->generateBarangId();

        $data['nilai_jasa'] = $this->hitungNilaiJasa($data);
        if ($data['kategori'] === 'Paket') {
            $data['harga_beli'] = $this->hitungModalPaket($data);
        }

        $items = $data['items'] ?? [];
        unset($data['items']);

        return DB::transaction(function () use ($data, $items) {
            $barang = Barang::create($data);

            if ($data['kategori'] === 'Paket' && !empty($items)) {
                foreach ($items as $it) {
                    PaketKomponen::create([
                        'paket_id' => $barang->id,
                        'komponen_id' => $it['barang_id'],
                        'qty' => $it['qty'],
                    ]);
                }
            }

            return $barang->load('items.komponen');
        });
    }

    public function show(Barang $barang) {
        return $barang->load('items.komponen');
    }

    public function update(Request $request, Barang $barang) {
        $data = $request->validate([
            'nama' => 'required',
            'kategori' => 'required',
            'stok' => 'nullable|integer',
            'satuan' => 'required',
            'harga_beli' => 'nullable|integer',
            'harga_jual' => 'required|integer',
            'items' => 'nullable|array',
            'items.*.barang_id' => 'required_with:items|exists:barangs,id',
            'items.*.qty' => 'required_with:items|integer|min:1',
        ]);

        $data['nilai_jasa'] = $this->hitungNilaiJasa($data);
        if ($data['kategori'] === 'Paket') {
            $data['harga_beli'] = $this->hitungModalPaket($data);
        }

        $items = $data['items'] ?? [];
        unset($data['items']);

        return DB::transaction(function () use ($data, $items, $barang) {
            $barang->update($data);

            // Sinkronisasi komponen: hapus yang lama, ganti dengan yang baru
            $barang->items()->delete();
            if ($data['kategori'] === 'Paket' && !empty($items)) {
                foreach ($items as $it) {
                    PaketKomponen::create([
                        'paket_id' => $barang->id,
                        'komponen_id' => $it['barang_id'],
                        'qty' => $it['qty'],
                    ]);
                }
            }

            return $barang->load('items.komponen');
        });
    }

    public function destroy(Barang $barang) {
        $barang->delete();
        return response()->noContent();
    }

    private function generateBarangId(): string
    {
        $last = Barang::where('id', 'like', 'BRG-%')
            ->whereRaw("id REGEXP '^BRG-[0-9]+$'")
            ->orderByRaw("CAST(SUBSTRING(id, 5) AS UNSIGNED) DESC")
            ->first();

        $nextNumber = $last ? ((int) substr($last->id, 4)) + 1 : 1;

        return 'BRG-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    private function hitungNilaiJasa(array $data): int
    {
        $nilaiJasa = 0;

        if (($data['kategori'] ?? null) === 'Paket' && !empty($data['items'])) {
            foreach ($data['items'] as $it) {
                $komponen = Barang::find($it['barang_id']);
                if ($komponen && $komponen->kategori === 'Jasa') {
                    $nilaiJasa += $komponen->harga_jual * $it['qty'];
                }
            }
        }

        return $nilaiJasa;
    }

    private function hitungModalPaket(array $data): int
    {
        $modal = 0;

        if (($data['kategori'] ?? null) === 'Paket' && !empty($data['items'])) {
            foreach ($data['items'] as $it) {
                $komponen = Barang::find($it['barang_id']);
                if ($komponen && $komponen->kategori !== 'Jasa') {
                    $modal += $komponen->harga_beli * $it['qty'];
                }
            }
        }

        return $modal;
    }

    public function adjustStok(Request $request, Barang $barang)
    {
        $data = $request->validate([
            'delta' => 'required|integer',
            'harga_beli_baru' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data, $barang) {
            $locked = Barang::where('id', $barang->id)->lockForUpdate()->first();

            $stokLama = $locked->stok ?? 0;
            $delta = $data['delta'];
            $stokBaru = max(0, $stokLama + $delta);
            $hargaBeliBaru = $data['harga_beli_baru'] ?? null;

            if ($delta > 0 && !is_null($hargaBeliBaru)) {
                if ($stokLama > 0) {
                    $totalNilaiLama = $stokLama * $locked->harga_beli;
                    $totalNilaiTambahan = $delta * $hargaBeliBaru;
                    $locked->harga_beli = round(($totalNilaiLama + $totalNilaiTambahan) / ($stokLama + $delta));
                } else {
                    $locked->harga_beli = $hargaBeliBaru;
                }
            }

            $locked->stok = $stokBaru;
            $locked->save();

            return response()->json($locked);
        });
    }
}