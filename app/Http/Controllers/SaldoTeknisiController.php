<?php

namespace App\Http\Controllers;

use App\Models\SaldoTeknisiUmum;
use Illuminate\Http\Request;

class SaldoTeknisiController extends Controller
{
    public function index(Request $request)
    {
        $query = SaldoTeknisiUmum::query()->orderByDesc('created_at');

        if ($request->filled('dari')) {
            $query->where('tanggal', '>=', $request->dari);
        }
        if ($request->filled('sampai')) {
            $query->where('tanggal', '<=', $request->sampai);
        }

        $items = $query->get();

        $saldo = SaldoTeknisiUmum::selectRaw(
            "SUM(CASE WHEN jenis = 'Masuk' THEN jumlah ELSE -jumlah END) as saldo"
        )->value('saldo') ?? 0;

        $mutasi = $items->map(function ($m) {
            return [
                'id' => $m->id,
                'transaksi_id' => $m->transaksi_id,
                'jenis' => $m->jenis === 'Masuk' ? 'Kredit' : 'Debit',
                'kategori' => $m->kategori,
                'jumlah' => $m->jumlah,
                'keterangan' => $m->keterangan,
                'created_at' => $m->created_at,
                'otomatis' => !is_null($m->transaksi_id),
            ];
        });

        return response()->json([
            'saldo' => $saldo,
            'mutasi' => $mutasi,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'jenis' => 'required|in:Kredit,Debit',
            'jumlah' => 'required|integer|min:1',
            'keterangan' => 'required|string',
        ]);

        $item = SaldoTeknisiUmum::create([
            'tanggal' => now()->toDateString(),
            'jenis' => $data['jenis'] === 'Kredit' ? 'Masuk' : 'Keluar',
            'kategori' => $data['jenis'] === 'Kredit' ? 'Tambahan Manual' : 'Potongan Kesalahan',
            'jumlah' => $data['jumlah'],
            'keterangan' => $data['keterangan'],
            'transaksi_id' => null,
        ]);

        return response()->json([
            'id' => $item->id,
            'transaksi_id' => null,
            'jenis' => $data['jenis'],
            'kategori' => $item->kategori,
            'jumlah' => $item->jumlah,
            'keterangan' => $item->keterangan,
            'created_at' => $item->created_at,
            'otomatis' => false,
        ], 201);
    }

    public function destroy(SaldoTeknisiUmum $saldoTeknisi)
    {
        if ($saldoTeknisi->transaksi_id) {
            return response()->json([
                'message' => 'Entri otomatis dari transaksi tidak bisa dihapus manual.'
            ], 422);
        }
        $saldoTeknisi->delete();
        return response()->noContent();
    }
}