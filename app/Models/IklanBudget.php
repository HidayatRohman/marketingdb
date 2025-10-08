<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class IklanBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal',
        'brand_id',
        'budget_amount',
        'spent_amount',
        'spent_plus_tax',
        'real_lead',
        'cost_per_lead',
        'closing',
        'omset',
        'roas',
        'keterangan'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'budget_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'spent_plus_tax' => 'decimal:2',
        'cost_per_lead' => 'decimal:2',
        'omset' => 'decimal:2',
        'roas' => 'decimal:4'
    ];

    /**
     * Automatically calculate cost per lead when spent_amount or real_lead changes
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($model) {
            // Calculate cost per lead
            if ($model->real_lead > 0) {
                $model->cost_per_lead = $model->spent_amount / $model->real_lead;
            } else {
                $model->cost_per_lead = 0;
            }
            
            // Calculate ROAS
            if ($model->spent_amount > 0) {
                $model->roas = $model->omset / $model->spent_amount;
            } else {
                $model->roas = 0;
            }
        });
    }

    /**
     * Get formatted budget amount
     */
    protected function formattedBudgetAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp' . number_format($this->budget_amount, 0, ',', '.')
        );
    }

    /**
     * Get formatted spent amount
     */
    protected function formattedSpentAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp' . number_format($this->spent_amount, 0, ',', '.')
        );
    }

    /**
     * Get formatted omset
     */
    protected function formattedOmset(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp' . number_format($this->omset, 0, ',', '.')
        );
    }

    /**
     * Get formatted cost per lead
     */
    protected function formattedCostPerLead(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->real_lead > 0 ? 'Rp' . number_format($this->cost_per_lead, 0, ',', '.') : '#DIV/0!'
        );
    }

    /**
     * Scope untuk mendapatkan data dalam periode tertentu
     */
    public function scopePeriode($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal', [$startDate, $endDate]);
    }

    /**
     * Scope untuk mendapatkan total dalam periode
     */
    public function scopeTotalPeriode($query, $startDate, $endDate)
    {
        return $query->periode($startDate, $endDate)
            ->selectRaw('SUM(budget_amount) as total_budget')
            ->selectRaw('SUM(spent_amount) as total_spent')
            ->selectRaw('SUM(spent_plus_tax) as total_spent_tax')
            ->selectRaw('SUM(real_lead) as total_leads')
            ->selectRaw('SUM(closing) as total_closing')
            ->selectRaw('SUM(omset) as total_omset')
            ->selectRaw('CASE WHEN SUM(spent_amount) > 0 THEN SUM(omset) / SUM(spent_amount) ELSE 0 END as avg_roas')
            ->selectRaw('CASE WHEN SUM(real_lead) > 0 THEN SUM(spent_amount) / SUM(real_lead) ELSE 0 END as avg_cost_per_lead');
    }

    /**
     * Get the brand that owns the iklan budget.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}