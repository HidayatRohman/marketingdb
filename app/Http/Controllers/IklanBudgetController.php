<?php

namespace App\Http\Controllers;

use App\Models\IklanBudget;
use App\Models\Brand;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IklanBudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $query = IklanBudget::with('brand');
        
        // Set default periode ke bulan berjalan
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        // Tentukan periode aktif: gunakan filter jika ada, jika tidak pakai default bulan berjalan
        $activeStart = $request->filled('start_date') ? Carbon::parse($request->start_date) : $startOfMonth;
        $activeEnd = $request->filled('end_date') ? Carbon::parse($request->end_date) : $endOfMonth;
        
        // Terapkan filter periode aktif untuk data tabel
        $query->inPeriod($activeStart, $activeEnd);
        
        // Filter berdasarkan brand jika ada
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        
        $iklanBudgets = $query->orderBy('tanggal', 'desc')->paginate(31);
        
        // Update closing and omset values for the current page data
        foreach ($iklanBudgets->items() as $budget) {
            $closing = IklanBudget::calculateClosingForDate($budget->tanggal, $budget->brand_id);
            $omset = IklanBudget::calculateOmsetForDate($budget->tanggal, $budget->brand_id);
            
            // Update the model instance for display (without saving to database yet)
            $budget->closing = $closing;
            $budget->omset = $omset;
            
            // Optionally save to database to persist the calculated values
            $budget->save();
        }
        
        // Hitung total untuk periode aktif (global, terpisah dari data tabel)
        $totalQuery = IklanBudget::query()->inPeriod($activeStart, $activeEnd);
        
        // Filter berdasarkan brand untuk total juga
        if ($request->filled('brand_id')) {
            $totalQuery->where('brand_id', $request->brand_id);
        }
        
        $totals = $totalQuery->getTotals(
            $activeStart->format('Y-m-d'),
            $activeEnd->format('Y-m-d'),
            $request->brand_id
        )->first();
        
        // Calculate correct AVG CPL: Total Spent / Total Leads
        if ($totals && $totals->total_leads > 0) {
            $totals->avg_cost_per_lead = $totals->total_spent / $totals->total_leads;
        } else {
            $totals->avg_cost_per_lead = 0;
        }
        
        // Summary report by brand (global, bukan dari halaman tabel)
        $selectedBrand = $request->brand_id;
        $ppnRate = (float) SiteSetting::get('ppn_rate', 11);
        $spentMultiplier = 1 + ($ppnRate / 100.0);

        // Gunakan periode aktif untuk summary report
        $startDate = $activeStart->format('Y-m-d');
        $endDate = $activeEnd->format('Y-m-d');

        // Build expressions dengan periode aktif
        $spentExpr = 'COALESCE(SUM(CASE WHEN iklan_budgets.tanggal BETWEEN "' . $startDate . '" AND "' . $endDate . '" THEN iklan_budgets.spent_amount END), 0)';
        $leadSub = 'SELECT COUNT(*) FROM mitras WHERE mitras.brand_id = brands.id AND mitras.tanggal_lead BETWEEN "' . $startDate . '" AND "' . $endDate . '"';
        $closingSub = 'SELECT COUNT(*) FROM transaksis WHERE transaksis.lead_awal_brand_id = brands.id AND transaksis.tanggal_tf BETWEEN "' . $startDate . '" AND "' . $endDate . '"';
        $omsetSub = 'SELECT COALESCE(SUM(transaksis.nominal_masuk), 0) FROM transaksis WHERE transaksis.lead_awal_brand_id = brands.id AND transaksis.tanggal_tf BETWEEN "' . $startDate . '" AND "' . $endDate . '"';

        $reportQuery = Brand::select(
            'brands.id',
            'brands.nama',
            DB::raw($spentExpr . ' as spent'),
            DB::raw('(' . $leadSub . ') as real_lead'),
            DB::raw('(' . $closingSub . ') as closing'),
            DB::raw('(' . $omsetSub . ') as omset')
        )
        ->leftJoin('iklan_budgets', 'brands.id', '=', 'iklan_budgets.brand_id')
        ->groupBy('brands.id', 'brands.nama');

        if (!empty($selectedBrand)) {
            $reportQuery->where('brands.id', $selectedBrand);
        }

        $reportRows = $reportQuery->get();

        $reportSummary = $reportRows->map(function ($row) use ($spentMultiplier) {
            $spent = (float) ($row->spent ?? 0);
            $spentWithTax = $spent * $spentMultiplier;
            $realLead = (int) ($row->real_lead ?? 0);
            $closing = (int) ($row->closing ?? 0);
            $omset = (float) ($row->omset ?? 0);

            return [
                'brand' => $row->nama,
                'spent' => $spent,
                'spent_with_tax' => $spentWithTax,
                'real_lead' => $realLead,
                'closing' => $closing,
                'omset' => $omset,
                'cost_per_lead' => $realLead > 0 ? ($spent / $realLead) : 0,
                'roas' => $spentWithTax > 0 ? ($omset / $spentWithTax) : 0,
            ];
        })->values();
        
        // Get brands for select options
        $brands = Brand::select('id', 'nama')->orderBy('nama')->get();
        
        return Inertia::render('IklanBudget/Index', [
            'iklanBudgets' => $iklanBudgets,
            'totals' => $totals,
            'reportSummary' => $reportSummary,
            'brands' => $brands,
            // Kirimkan filters dengan default bulan berjalan jika tidak disediakan
            'filters' => [
                'start_date' => $activeStart->format('Y-m-d'),
                'end_date' => $activeEnd->format('Y-m-d'),
                'brand_id' => $request->brand_id,
            ],
            'permissions' => [
                'canCrud' => $currentUser->canCrud(),
                'canOnlyView' => $currentUser->canOnlyView(),
                'canOnlyViewOwn' => $currentUser->canOnlyViewOwn(),
                'role' => $currentUser->role,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'brand_id' => 'required|exists:brands,id',
            'spent_amount' => 'required|numeric|min:0',
        ]);

        // Add custom validation for unique combination of tanggal and brand_id
        $validator->after(function ($validator) use ($request) {
            $exists = IklanBudget::where('tanggal', $request->tanggal)
                ->where('brand_id', $request->brand_id)
                ->exists();
            
            if ($exists) {
                $validator->errors()->add('tanggal', 'Kombinasi tanggal dan brand sudah ada.');
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $iklanBudget = IklanBudget::create($validator->validated());

        return redirect()->route('iklan-budgets.index')
            ->with('success', 'Data budget iklan berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, IklanBudget $iklanBudget)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'brand_id' => 'required|exists:brands,id',
            'spent_amount' => 'required|numeric|min:0',
        ]);

        // Add custom validation for unique combination of tanggal dan brand_id (excluding current record)
        $validator->after(function ($validator) use ($request, $iklanBudget) {
            $exists = IklanBudget::where('tanggal', $request->tanggal)
                ->where('brand_id', $request->brand_id)
                ->where('id', '!=', $iklanBudget->id)
                ->exists();
            
            if ($exists) {
                $validator->errors()->add('tanggal', 'Kombinasi tanggal dan brand sudah ada.');
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $iklanBudget->update($validator->validated());

        return redirect()->route('iklan-budgets.index')
            ->with('success', 'Data budget iklan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IklanBudget $iklanBudget)
    {
        $iklanBudget->delete();

        return redirect()->route('iklan-budgets.index')
            ->with('success', 'Data budget iklan berhasil dihapus.');
    }

    /**
     * Generate budget data untuk satu bulan penuh
     */
    public function generateMonthlyBudget(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
            'default_budget' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $year = $request->year;
        $month = $request->month;
        $defaultBudget = $request->default_budget;
        
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        $created = 0;
        $current = $startDate->copy();
        
        while ($current <= $endDate) {
            $exists = IklanBudget::where('tanggal', $current->format('Y-m-d'))->exists();
            
            if (!$exists) {
                IklanBudget::create([
                    'tanggal' => $current->format('Y-m-d'),
                    'budget_amount' => $defaultBudget,
                    'spent_amount' => 0,
                    'closing' => 0,
                    'omset' => 0
                ]);
                $created++;
            }
            
            $current->addDay();
        }
        
        return redirect()->route('iklan-budgets.index')
            ->with('success', "Berhasil membuat {$created} data budget untuk bulan {$month}/{$year}.");
    }

    /**
     * Analytics: Get monthly spent totals for a given year and optional brand
     */
    public function monthlySpent(Request $request)
    {
        $year = (int) ($request->get('year', now()->year));
        $brandId = $request->get('brand_id');

        $query = IklanBudget::query()->whereYear('tanggal', $year);

        if (!empty($brandId)) {
            $query->where('brand_id', $brandId);
        }

        // Aggregate spent per month
        $rows = $query->selectRaw('MONTH(tanggal) as month, COALESCE(SUM(spent_amount), 0) as total_spent')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Prepare a full 12-month series with zero filling
        $monthLabels = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $found = $rows->firstWhere('month', $m);
            $data[] = [
                'month' => $m,
                'label' => $monthLabels[$m],
                'spent' => (float) ($found->total_spent ?? 0),
            ];
        }

        return response()->json([
            'year' => $year,
            'brand_id' => $brandId,
            'data' => $data,
        ]);
    }
}