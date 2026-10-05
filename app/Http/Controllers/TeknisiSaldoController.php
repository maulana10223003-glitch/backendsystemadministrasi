<?php

namespace App\Http\Controllers;

use App\Models\Teknisi;
use App\Models\TeknisiSaldoMutasi;
use Illuminate\Http\Request;

class TeknisiSaldoController extends Controller
{
    // List saldo TABUNGAN semua teknisi (gaji tidak lagi ditampilkan di sini)
    public function index() {
        $teknisis = Teknisi::all();

        $result = $teknisis->map(function ($t) {
            $saldoTabungan = TeknisiSaldoMutasi::where('teknisi_id', $t->id)
                ->where('kelompok', 'Tabungan')
                ->selectRaw("SUM(CASE WHEN jenis = 'Kredit' THEN jumlah ELSE -jumlah END) as saldo")
                ->value('saldo') ?? 0;

            return [
                'teknisi_id' => $t->id,
                'nama' => $t->nama,
                'saldo' => $saldoTabungan,
            ];
        });

        return response()->json($result);
    }

    // Detail riwayat TABUNGAN 1 teknisi
    public function show(Teknisi $teknisi) {
        $mutasi = TeknisiSaldoMutasi::where('teknisi_id', $teknisi->id)
            ->where('kelompok', 'Tabungan')
            ->orderByDesc('created_at')
            ->get();

        $saldo = $mutasi->reduce(
            fn($c, $m) => $c + ($m->jenis === 'Kredit' ? $m->jumlah : -$m->jumlah), 0
        );

        return response()->json([
            'teknisi' => $teknisi,
            'saldo' => $saldo,
            'mutasi' => $mutasi,
        ]);
    }

    // Tambah entri manual (Kredit/Debit) - persis pola Kas Kecil
    public function store(Request $request, Teknisi $teknisi) {
        $data = $request->validate([
            'jenis' => 'required|in:Kredit,Debit',
            'jumlah' => 'required|integer|min:1',
            'keterangan' => 'required|string',
        ]);

        $mutasi = TeknisiSaldoMutasi::create([
            'teknisi_id' => $teknisi->id,
            'transaksi_id' => null,
            'jenis' => $data['jenis'],
            'kategori' => $data['jenis'] === 'Kredit' ? 'Tambahan Manual' : 'Potongan Kesalahan',
            'kelompok' => 'Tabungan',
            'jumlah' => $data['jumlah'],
            'keterangan' => $data['keterangan'],
        ]);

        return response()->json($mutasi, 201);
    }

    // Cegah hapus entri otomatis dari transaksi (sama seperti Kas Kecil)
    public function destroy(TeknisiSaldoMutasi $mutasi) {
        if ($mutasi->transaksi_id) {
            return response()->json([
                'message' => 'Entri otomatis dari transaksi tidak bisa dihapus manual.'
            ], 422);
        }
        $mutasi->delete();
        return response()->noContent();
    }
}