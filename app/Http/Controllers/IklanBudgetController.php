<?php

namespace App\Http\Controllers;

use App\Models\IklanBudget;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Carbon\Carbon;

class IklanBudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $query = IklanBudget::with('brand');
        
        // Set default periode
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        // Filter berdasarkan periode jika ada
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->inPeriod($request->start_date, $request->end_date);
        } else {
            // Default: tampilkan data bulan ini
            $query->inPeriod($startOfMonth, $endOfMonth);
        }
        
        // Filter berdasarkan brand jika ada
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        
        $iklanBudgets = $query->orderBy('tanggal', 'desc')->paginate(31);
        
        // Hitung total untuk periode yang dipilih
        $totalQuery = IklanBudget::query();
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $totalQuery->inPeriod($request->start_date, $request->end_date);
        } else {
            $totalQuery->inPeriod($startOfMonth, $endOfMonth);
        }
        
        // Filter berdasarkan brand untuk total juga
        if ($request->filled('brand_id')) {
            $totalQuery->where('brand_id', $request->brand_id);
        }
        
        $totals = $totalQuery->getTotals(
            $request->start_date ?? $startOfMonth,
            $request->end_date ?? $endOfMonth,
            $request->brand_id
        )->first();
        
        // Calculate correct AVG CPL: Total Spent / Total Leads
        if ($totals && $totals->total_leads > 0) {
            $totals->avg_cost_per_lead = $totals->total_spent / $totals->total_leads;
        } else {
            $totals->avg_cost_per_lead = 0;
        }
        
        // Get brands for select options
        $brands = Brand::select('id', 'nama')->orderBy('nama')->get();
        
        return Inertia::render('IklanBudget/Index', [
            'iklanBudgets' => $iklanBudgets,
            'totals' => $totals,
            'brands' => $brands,
            'filters' => $request->only(['start_date', 'end_date', 'brand_id']),
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

        // Add custom validation for unique combination of tanggal and brand_id (excluding current record)
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
}