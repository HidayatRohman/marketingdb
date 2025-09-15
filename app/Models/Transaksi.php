<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mitra_id',
        'tanggal_tf',
        'tanggal_lead_masuk',
        'periode_lead',
        'no_wa',
        'usia',
        'paket_brand_id',
        'lead_awal_brand_id',
        'sumber',
        'kabupaten',
        'provinsi',
        'status_pembayaran',
        'nominal_masuk',
        'harga_paket',
        'nama_paket',
    ];

    protected $casts = [
        'tanggal_tf' => 'date:Y-m-d',
        'tanggal_lead_masuk' => 'date:Y-m-d',
        'nominal_masuk' => 'decimal:2',
        'harga_paket' => 'decimal:2',
    ];

    /**
     * Get the user (marketing) that owns the transaksi.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the mitra that owns the transaksi.
     */
    public function mitra()
    {
        return $this->belongsTo(Mitra::class);
    }

    /**
     * Get the paket brand that belongs to the transaksi.
     */
    public function paketBrand()
    {
        return $this->belongsTo(Brand::class, 'paket_brand_id');
    }

    /**
     * Get the lead awal brand that belongs to the transaksi.
     */
    public function leadAwalBrand()
    {
        return $this->belongsTo(Brand::class, 'lead_awal_brand_id');
    }
}
