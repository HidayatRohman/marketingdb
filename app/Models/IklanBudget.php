<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class IklanBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal',
        'brand_id',
        'spent_amount',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'spent_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Get real lead from Mitra table
            $realLead = $model->getRealLeadFromMitra();
            
            // Calculate cost per lead if spent_amount and real_lead exist
            if ($model->spent_amount && $realLead > 0) {
                $model->cost_per_lead = $model->spent_amount / $realLead;
            } else {
                $model->cost_per_lead = 0;
            }

            // Calculate ROAS if omset and spent_amount exist
            if ($model->omset && $model->spent_amount > 0) {
                $model->roas = $model->omset / $model->spent_amount;
            } else {
                $model->roas = 0;
            }
        });
    }

    /**
     * Get real lead count from Mitra table based on date and brand
     */
    public function getRealLeadFromMitra()
    {
        if (!$this->tanggal || !$this->brand_id) {
            return 0;
        }

        return \App\Models\Mitra::where('tanggal_lead', $this->tanggal)
            ->whereHas('brand', function ($query) {
                $query->where('id', $this->brand_id);
            })
            ->count();
    }

    /**
     * Accessor for real_lead attribute
     */
    public function getRealLeadAttribute()
    {
        return $this->getRealLeadFromMitra();
    }

    /**
     * Get formatted budget amount
     */
    public function getFormattedBudgetAmountAttribute()
    {
        return 'Rp ' . number_format($this->budget_amount, 0, ',', '.');
    }

    public function getFormattedSpentAmountAttribute()
    {
        return 'Rp ' . number_format($this->spent_amount, 0, ',', '.');
    }

    public function getFormattedOmsetAttribute()
    {
        return 'Rp ' . number_format($this->omset, 0, ',', '.');
    }

    public function getFormattedCostPerLeadAttribute()
    {
        return 'Rp ' . number_format($this->cost_per_lead, 0, ',', '.');
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal', [$startDate, $endDate]);
    }

    public function scopeGetTotals($query, $startDate = null, $endDate = null, $brandId = null)
    {
        // Calculate total leads from Mitra table based on date range and brand
        $totalLeadsQuery = \App\Models\Mitra::query();
        if ($startDate && $endDate) {
            $totalLeadsQuery->whereBetween('tanggal_lead', [$startDate, $endDate]);
        }
        if ($brandId) {
            $totalLeadsQuery->where('brand_id', $brandId);
        }
        $totalLeads = $totalLeadsQuery->count();

        // Calculate total omset from Transaksi table based on nominal_masuk
        $totalOmsetQuery = \App\Models\Transaksi::query();
        if ($startDate && $endDate) {
            $totalOmsetQuery->whereBetween('tanggal_tf', [$startDate, $endDate]);
        }
        if ($brandId) {
            $totalOmsetQuery->where(function($q) use ($brandId) {
                $q->where('paket_brand_id', $brandId)
                  ->orWhere('lead_awal_brand_id', $brandId);
            });
        }
        $totalOmset = $totalOmsetQuery->sum('nominal_masuk');

        // Calculate total closing from Transaksi table based on lead_awal_brand_id
        $totalClosingQuery = \App\Models\Transaksi::query();
        if ($startDate && $endDate) {
            $totalClosingQuery->whereBetween('tanggal_tf', [$startDate, $endDate]);
        }
        if ($brandId) {
            $totalClosingQuery->where('lead_awal_brand_id', $brandId);
        }
        $totalClosing = $totalClosingQuery->count();

        $avgCostPerLead = $totalLeads > 0 ? 0 : 0; // Will be calculated in controller
        
        $query = $query->selectRaw('
            SUM(budget_amount) as total_budget,
            SUM(spent_amount) as total_spent,
            SUM(spent_amount * 1.11) as total_spent_plus_tax,
            ? as total_closing,
            ? as total_omset,
            CASE 
                WHEN SUM(spent_amount) > 0 THEN ? / SUM(spent_amount)
                ELSE 0 
            END as avg_roas,
            0 as avg_cost_per_lead,
            ? as total_leads
        ', [$totalClosing, $totalOmset, $totalOmset, $totalLeads]);

        if ($startDate && $endDate) {
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }
        
        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        return $query;
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}