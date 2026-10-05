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
    Schema::create('paket_barang_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('paket_id')->constrained('paket_barangs')->onDelete('cascade');
        $table->string('barang_id');
        $table->integer('qty')->default(1);
        $table->timestamps();

        $table->foreign('barang_id')->references('id')->on('barangs');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paket_barang_items');
    }
};
