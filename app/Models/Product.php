<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'logo',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            $path = storage_path('app/public/' . $this->logo);
            if (file_exists($path)) {
                return asset('storage/' . $this->logo);
            }
        }
        return null;
    }

    public function csRepeats()
    {
        return $this->hasMany(CsRepeat::class);
    }

    public function csMaintenances()
    {
        return $this->hasMany(CsMaintenance::class);
    }
}