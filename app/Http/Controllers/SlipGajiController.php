<?php

namespace App\Http\Controllers;

use App\Models\Teknisi;
use App\Models\TeknisiSaldoMutasi;
use Illuminate\Http\Request;

class SlipGajiController extends Controller
{
public function generate(Request $request, Teknisi $teknisi) {
    $data = $request->validate([
        'dari' => 'required|date',
        'sampai' => 'required|date|after_or_equal:dari',
    ]);

    $mutasi = TeknisiSaldoMutasi::where('teknisi_id', $teknisi->id)
        ->where('kelompok', 'Gaji') 
        ->whereBetween('created_at', [$data['dari'] . ' 00:00:00', $data['sampai'] . ' 23:59:59'])
        ->orderBy('created_at')
        ->get();

    $totalKredit = $mutasi->where('jenis', 'Kredit')->sum('jumlah');
    $totalDebit = $mutasi->where('jenis', 'Debit')->sum('jumlah');

    $saldoBerjalan = TeknisiSaldoMutasi::where('teknisi_id', $teknisi->id)
        ->where('kelompok', 'Gaji')
        ->where('created_at', '<', $data['dari'] . ' 00:00:00')
        ->selectRaw("SUM(CASE WHEN jenis = 'Kredit' THEN jumlah ELSE -jumlah END) as saldo")
        ->value('saldo') ?? 0;

    return response()->json([
        'teknisi' => $teknisi,
        'periode' => ['dari' => $data['dari'], 'sampai' => $data['sampai']],
        'saldo_awal_periode' => $saldoBerjalan,
        'total_pendapatan_periode' => $totalKredit,
        'total_potongan_periode' => $totalDebit,
        'saldo_akhir_periode' => $saldoBerjalan + $totalKredit - $totalDebit,
        'rincian' => $mutasi,
    ]);
}

public function bayar(Request $request, Teknisi $teknisi) {
    $data = $request->validate([
        'jumlah' => 'required|integer|min:1',
        'keterangan' => 'nullable|string',
    ]);

    $mutasi = TeknisiSaldoMutasi::create([
        'teknisi_id' => $teknisi->id,
        'transaksi_id' => null,
        'jenis' => 'Debit',
        'kategori' => 'Pembayaran Gaji',
        'kelompok' => 'Gaji', // pembayaran gaji ambil dari kelompok Gaji, bukan Tabungan
        'jumlah' => $data['jumlah'],
        'keterangan' => $data['keterangan'] ?? 'Pencairan gaji/bagi hasil',
    ]);

    return response()->json($mutasi, 201);
}
}