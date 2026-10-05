<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up() {
    Schema::table('transaksi_items', function (Blueprint $table) {
        $table->integer('jasa_nominal')->default(0)->after('kategori');
    });
}

public function down() {
    Schema::table('transaksi_items', function (Blueprint $table) {
        $table->dropColumn('jasa_nominal');
    });
}
};
