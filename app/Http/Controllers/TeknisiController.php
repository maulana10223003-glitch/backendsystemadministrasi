<?php

namespace App\Http\Controllers;

use App\Models\Teknisi;
use Illuminate\Http\Request;

class TeknisiController extends Controller
{
    public function index() {
        return Teknisi::all();
    }

    public function store(Request $request) {
        $data = $request->validate([
            'nama' => 'required',
            'telepon' => 'required',
            'status' => 'required|in:Aktif,Nonaktif',
        ]);

        $data['id'] = $this->generateTeknisiId();

        return Teknisi::create($data);
    }

    public function show(Teknisi $teknisi) {
        return $teknisi;
    }

    public function update(Request $request, Teknisi $teknisi) {
        $data = $request->validate([
            'nama' => 'sometimes|required',
            'telepon' => 'sometimes|required',
            'status' => 'sometimes|required|in:Aktif,Nonaktif',
        ]);

        $teknisi->update($data);
        return $teknisi;
    }

    public function destroy(Teknisi $teknisi) {
        $teknisi->delete();
        return response()->noContent();
    }

    /**
     * Generate ID sequential format TKN-001, TKN-002, dst.
     * Cuma ngambil ID lama yang sudah sesuai format TKN-xxx (numerik di belakang),
     * biar aman kalau ada ID lama dengan format berbeda tercampur di tabel.
     */
    private function generateTeknisiId(): string
    {
        $last = Teknisi::where('id', 'like', 'TKN-%')
            ->whereRaw("id REGEXP '^TKN-[0-9]+$'")
            ->orderByRaw("CAST(SUBSTRING(id, 5) AS UNSIGNED) DESC")
            ->first();

        $nextNumber = $last ? ((int) substr($last->id, 4)) + 1 : 1;

        return 'TKN-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}