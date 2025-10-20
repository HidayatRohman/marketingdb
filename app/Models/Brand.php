<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'logo',
    ];

    protected $appends = ['logo_url'];

    /**
     * Get the full URL for the logo
     */
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

    /**
     * Get the mitras for the brand.
     */
    public function mitras()
    {
        return $this->hasMany(Mitra::class);
    }
}
