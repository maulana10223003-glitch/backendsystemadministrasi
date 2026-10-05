<?php

namespace App\Http\Controllers;

use App\Models\PettyCashTransaction;
use Illuminate\Http\Request;

class PettyCashController extends Controller
{
    // List transaksi kas kecil, bisa difilter by rentang tanggal
    public function index(Request $request) {
        $query = PettyCashTransaction::query()->orderByDesc('tanggal')->orderByDesc('id');

        if ($request->filled('dari')) {
            $query->where('tanggal', '>=', $request->dari);
        }
        if ($request->filled('sampai')) {
            $query->where('tanggal', '<=', $request->sampai);
        }

        $data = $query->get();

        $saldo = PettyCashTransaction::selectRaw(
            "SUM(CASE WHEN jenis = 'Masuk' THEN jumlah ELSE -jumlah END) as saldo"
        )->value('saldo') ?? 0;

        return response()->json([
            'saldo_saat_ini' => $saldo,
            'data' => $data,
        ]);
    }

    // Tambah entri manual (misal beli alat kecil, biaya operasional lain)
    public function store(Request $request) {
        $data = $request->validate([
            'tanggal' => 'required|date',
            'jenis' => 'required|in:Masuk,Keluar',
            'kategori' => 'required|string',
            'jumlah' => 'required|integer|min:1',
            'keterangan' => 'nullable|string',
        ]);

        return PettyCashTransaction::create($data);
    }

    public function destroy(PettyCashTransaction $pettyCash) {
        // Cegah hapus entri otomatis (BOP/Pengembalian BOP) yang terikat transaksi,
        // biar data keuangan gak jadi ganjil/gak sinkron.
        if ($pettyCash->transaksi_id) {
            return response()->json([
                'message' => 'Entri otomatis dari transaksi tidak bisa dihapus manual.'
            ], 422);
        }
        $pettyCash->delete();
        return response()->noContent();
    }
    public function kasKecilIndex(Request $request)
{
    $query = PettyCashTransaction::query()->orderByDesc('created_at');

    if ($request->filled('dari')) {
        $query->where('tanggal', '>=', $request->dari);
    }
    if ($request->filled('sampai')) {
        $query->where('tanggal', '<=', $request->sampai);
    }

    $items = $query->get();

    $saldo = PettyCashTransaction::selectRaw(
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

public function kasKecilStore(Request $request)
{
    $data = $request->validate([
        'jenis' => 'required|in:Kredit,Debit',
        'jumlah' => 'required|integer|min:1',
        'keterangan' => 'required|string',
    ]);

    $petty = PettyCashTransaction::create([
        'tanggal' => now()->toDateString(),
        'jenis' => $data['jenis'] === 'Kredit' ? 'Masuk' : 'Keluar',
        'kategori' => $data['jenis'] === 'Kredit' ? 'Pemasukan Manual' : 'Pengeluaran Manual',
        'jumlah' => $data['jumlah'],
        'keterangan' => $data['keterangan'],
        'transaksi_id' => null,
    ]);

    return response()->json([
        'id' => $petty->id,
        'transaksi_id' => null,
        'jenis' => $data['jenis'],
        'kategori' => $petty->kategori,
        'jumlah' => $petty->jumlah,
        'keterangan' => $petty->keterangan,
        'created_at' => $petty->created_at,
        'otomatis' => false,
    ], 201);
}
}