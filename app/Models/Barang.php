<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id', 'nama', 'kategori', 'stok', 'satuan', 'harga_beli', 'harga_jual', 'nilai_jasa'];
    public function items()
    {
        return $this->hasMany(PaketKomponen::class, 'paket_id');
    }
}
