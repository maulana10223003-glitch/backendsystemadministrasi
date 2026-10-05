<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaketKomponen extends Model
{
    protected $table = 'paket_komponen';
    protected $fillable = ['paket_id', 'komponen_id', 'qty'];

    public function komponen()
    {
        return $this->belongsTo(Barang::class, 'komponen_id');
    }
}