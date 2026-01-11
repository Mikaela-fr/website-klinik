<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('layanans', function (Blueprint $table) {
        $table->id(); // Ini otomatis jadi kolom 'id'
        $table->string('nama_layanan');
        $table->decimal('harga', 12, 2); // Pakai decimal biar aman untuk uang
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layanans');
    }
};
