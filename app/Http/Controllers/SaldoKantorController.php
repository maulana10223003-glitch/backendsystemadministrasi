<?php

namespace App\Http\Controllers;

use App\Models\KantorSaldoMutasi;
use Illuminate\Http\Request;

class SaldoKantorController extends Controller
{
    // Ringkasan saldo + riwayat mutasi kantor, bisa difilter tanggal
    public function index(Request $request) {
        $query = KantorSaldoMutasi::query()->orderByDesc('created_at');

        if ($request->filled('dari')) {
            $query->whereDate('created_at', '>=', $request->dari);
        }
        if ($request->filled('sampai')) {
            $query->whereDate('created_at', '<=', $request->sampai);
        }

        $mutasi = $query->get();

        $saldo = KantorSaldoMutasi::selectRaw(
            "SUM(CASE WHEN jenis = 'Kredit' THEN jumlah ELSE -jumlah END) as saldo"
        )->value('saldo') ?? 0;

        return response()->json([
            'saldo' => $saldo,
            'mutasi' => $mutasi,
        ]);
    }

    // Entri manual, misal pengeluaran operasional kantor (bayar listrik, sewa, dll)
    public function store(Request $request) {
        $data = $request->validate([
            'jenis' => 'required|in:Kredit,Debit',
            'kategori' => 'required|string',
            'jumlah' => 'required|integer|min:1',
            'keterangan' => 'required|string',
        ]);

        $mutasi = KantorSaldoMutasi::create([
            'transaksi_id' => null,
            'jenis' => $data['jenis'],
            'kategori' => $data['kategori'],
            'jumlah' => $data['jumlah'],
            'keterangan' => $data['keterangan'],
        ]);

        return response()->json($mutasi, 201);
    }

    // Cegah hapus entri otomatis (dari transaksi), sama seperti Kas Kecil
    public function destroy(KantorSaldoMutasi $saldoKantor) {
        if ($saldoKantor->transaksi_id) {
            return response()->json([
                'message' => 'Entri otomatis dari transaksi tidak bisa dihapus manual.'
            ], 422);
        }
        $saldoKantor->delete();
        return response()->noContent();
    }
}