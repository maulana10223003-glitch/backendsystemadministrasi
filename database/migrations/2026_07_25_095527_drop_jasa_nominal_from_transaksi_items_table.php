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
    Schema::table('transaksi_items', function (Blueprint $table) {
        $table->dropColumn('jasa_nominal');
    });
}

public function down(): void
{
    Schema::table('transaksi_items', function (Blueprint $table) {
        $table->integer('jasa_nominal')->default(0);
    });
}
};
