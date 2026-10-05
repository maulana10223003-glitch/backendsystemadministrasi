<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaldoTeknisiUmum extends Model
{
    protected $table = 'saldo_teknisi_umum';
    protected $fillable = ['tanggal', 'jenis', 'kategori', 'jumlah', 'keterangan', 'transaksi_id'];
}