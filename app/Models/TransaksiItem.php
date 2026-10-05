<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiItem extends Model
{
    protected $fillable = ['transaksi_id', 'barang_id', 'nama', 'qty', 'harga_jual', 'harga_beli', 'kategori', 'nilai_jasa'];
}