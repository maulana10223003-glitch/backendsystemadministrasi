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
    Schema::create('petty_cash_transactions', function (Blueprint $table) {
        $table->id();
        $table->date('tanggal');
        $table->enum('jenis', ['Masuk', 'Keluar']);
        $table->string('kategori'); // BOP, Pengembalian BOP, Operasional Lain, dll
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
        Schema::dropIfExists('petty_cash_transactions');
    }
};
