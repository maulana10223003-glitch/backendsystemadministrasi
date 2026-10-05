<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KantorSaldoMutasi extends Model
{
    protected $table = 'kantor_saldo_mutasi';
    protected $fillable = ['transaksi_id', 'jenis', 'kategori', 'jumlah', 'keterangan'];
}
