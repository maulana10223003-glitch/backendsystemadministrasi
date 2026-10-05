<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('teknisi_saldo_mutasi', function (Blueprint $table) {
        $table->id();
        $table->string('teknisi_id');
        $table->string('transaksi_id')->nullable();
        $table->enum('jenis', ['Kredit', 'Debit']);
        $table->string('kategori'); // Bagi Hasil Jasa, Tabungan 5%, Potongan Kesalahan, Pembayaran Gaji
        $table->bigInteger('jumlah');
        $table->string('keterangan')->nullable();
        $table->timestamps();

        $table->foreign('teknisi_id')->references('id')->on('teknisis')->onDelete('cascade');
        $table->foreign('transaksi_id')->references('id')->on('transaksis')->onDelete('set null');
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teknisi_saldo_mutasis');
    }
};
