<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PettyCashTransaction extends Model
{
    protected $table = 'petty_cash_transactions';
    protected $fillable = ['tanggal', 'jenis', 'kategori', 'jumlah', 'keterangan', 'transaksi_id'];
}
