<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiDokumentasi extends Model
{
    protected $fillable = ['transaksi_id', 'path'];

    protected $appends = ['url'];

    public function getUrlAttribute() {
        return asset('storage/' . $this->path);
    }
}