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
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Nama marketing (otomatis dari user login)
            $table->foreignId('mitra_id')->constrained()->onDelete('cascade'); // Nama mitra (diambil dari pencarian database Mitra)
            $table->date('tanggal_tf'); // Tanggal TF (date picker, default now)
            $table->date('tanggal_lead_masuk'); // Tanggal Lead Masuk (date picker)
            $table->enum('periode_lead', ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']); // Periode Lead
            $table->string('no_wa'); // No WA (otomatis dari data mitra)
            $table->integer('usia'); // Usia (17-80)
            $table->foreignId('paket_brand_id')->constrained('brands')->onDelete('cascade'); // Paket (dari data brand)
            $table->foreignId('lead_awal_brand_id')->constrained('brands')->onDelete('cascade'); // Lead Awal (dari data brand)
            $table->enum('sumber', ['Unknown', 'IG', 'FB', 'WA', 'Tiktok', 'Web', 'Google', 'Organik', 'Teman']); // Sumber
            $table->string('kabupaten'); // Kabupaten
            $table->string('provinsi'); // Provinsi
            $table->enum('status_pembayaran', ['Dp / TJ', 'Tambahan Dp', 'Pelunasan']); // Status Pembayaran
            $table->decimal('nominal_masuk', 15, 2); // Nominal Masuk (format Rupiah)
            $table->decimal('harga_paket', 15, 2); // Harga Paket (format Rupiah)
            $table->string('nama_paket'); // Nama paket
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
