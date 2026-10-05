<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
    {
        Schema::dropIfExists('paket_barang_items');
        Schema::dropIfExists('paket_barangs');
    }

    public function down(): void
    {
        // Sengaja dikosongkan — tabel ini sudah tidak dipakai di sistem,
        // jadi tidak perlu di-recreate kalau migration di-rollback.
    }
};
