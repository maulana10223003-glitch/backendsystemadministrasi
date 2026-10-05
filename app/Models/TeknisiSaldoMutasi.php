<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeknisiSaldoMutasi extends Model
{
    protected $table = 'teknisi_saldo_mutasi';
    protected $fillable = ['teknisi_id', 'transaksi_id', 'jenis', 'kategori', 'kelompok', 'jumlah', 'keterangan'];
}
