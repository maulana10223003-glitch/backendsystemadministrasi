<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','pelanggan','alamat','telepon','tgl_pengerjaan','tgl_bayar','tgl_invoice','metode_bayar','status_pengerjaan','status_pembayaran', 'bop', 'sudah_diproses_pelunasan'];

    public function items() {
        return $this->hasMany(TransaksiItem::class, 'transaksi_id');
    }
    public function teknisis() {
        return $this->belongsToMany(Teknisi::class, 'transaksi_teknisi', 'transaksi_id', 'teknisi_id');
    }
    public function dokumentasi() {
        return $this->hasMany(TransaksiDokumentasi::class, 'transaksi_id');
    }
    protected $casts = [
    'sudah_diproses_pelunasan' => 'boolean',
];
}
