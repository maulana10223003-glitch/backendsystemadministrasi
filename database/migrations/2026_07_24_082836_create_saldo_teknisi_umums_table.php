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
    Schema::create('saldo_teknisi_umum', function (Blueprint $table) {
        $table->id();
        $table->date('tanggal');
        $table->enum('jenis', ['Masuk', 'Keluar']);
        $table->string('kategori');
        $table->bigInteger('jumlah');
        $table->string('keterangan')->nullable();
        $table->string('transaksi_id')->nullable();
        $table->timestamps();

        $table->foreign('transaksi_id')->references('id')->on('transaksis')->onDelete('set null');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldo_teknisi_umums');
    }
};
