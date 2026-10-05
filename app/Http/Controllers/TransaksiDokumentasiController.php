<?php

namespace App\Http\Controllers;

use App\Models\TransaksiDokumentasi;
use Illuminate\Http\Request;

class TransaksiDokumentasiController extends Controller
{
    public function store(Request $request) {
        $data = $request->validate([
            'transaksi_id' => 'required|exists:transaksis,id',
            'keterangan' => 'nullable|string',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // max 5MB
        ]);

        $path = $request->file('file')->store('dokumentasi', 'public');

        $dok = TransaksiDokumentasi::create([
            'transaksi_id' => $data['transaksi_id'],
            'nama_file' => $request->file('file')->getClientOriginalName(),
            'path' => $path,
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        return response()->json($dok, 201);
    }

    public function destroy(TransaksiDokumentasi $dokumentasi) {
        \Storage::disk('public')->delete($dokumentasi->path);
        $dokumentasi->delete();
        return response()->noContent();
    }
}