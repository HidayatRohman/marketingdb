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
        $query = IklanBudget::query();
        
        // Filter berdasarkan periode jika ada
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->periode($request->start_date, $request->end_date);
        } else {
            // Default: tampilkan data bulan ini
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();
            $query->periode($startOfMonth, $endOfMonth);
        }
        
        $iklanBudgets = $query->orderBy('tanggal', 'desc')->paginate(31);
        
        // Hitung total untuk periode yang dipilih
        $totalQuery = IklanBudget::query();
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $totalQuery->periode($request->start_date, $request->end_date);
        } else {
            $totalQuery->periode($startOfMonth, $endOfMonth);
        }
        
        $totals = $totalQuery->totalPeriode(
            $request->start_date ?? $startOfMonth,
            $request->end_date ?? $endOfMonth
        )->first();
        
        // Get brands for select options
        $brands = Brand::select('id', 'nama')->orderBy('nama')->get();
        
        return Inertia::render('IklanBudget/Index', [
            'iklanBudgets' => $iklanBudgets,
            'totals' => $totals,
            'brands' => $brands,
            'filters' => $request->only(['start_date', 'end_date']),
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
            'tanggal' => 'required|date|unique:iklan_budgets,tanggal',
            'brand_id' => 'nullable|exists:brands,id',
            'spent_amount' => 'required|numeric|min:0',
            'real_lead' => 'required|integer|min:0',
            'spent_plus_tax' => 'nullable|numeric|min:0',
            'cost_per_lead' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Calculate spent_plus_tax and cost_per_lead if not provided
        $data = $request->all();
        if (!isset($data['spent_plus_tax']) || $data['spent_plus_tax'] == 0) {
            $data['spent_plus_tax'] = $data['spent_amount'] * 1.11; // 11% tax
        }
        if (!isset($data['cost_per_lead']) || $data['cost_per_lead'] == 0) {
            $data['cost_per_lead'] = $data['real_lead'] > 0 ? $data['spent_amount'] / $data['real_lead'] : 0;
        }

        $iklanBudget = IklanBudget::create($data);

        return redirect()->route('iklan-budgets.index')
            ->with('success', 'Data budget iklan berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, IklanBudget $iklanBudget)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date|unique:iklan_budgets,tanggal,' . $iklanBudget->id,
            'brand_id' => 'nullable|exists:brands,id',
            'spent_amount' => 'required|numeric|min:0',
            'real_lead' => 'required|integer|min:0',
            'spent_plus_tax' => 'nullable|numeric|min:0',
            'cost_per_lead' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Calculate spent_plus_tax and cost_per_lead if not provided
        $data = $request->all();
        if (!isset($data['spent_plus_tax']) || $data['spent_plus_tax'] == 0) {
            $data['spent_plus_tax'] = $data['spent_amount'] * 1.11; // 11% tax
        }
        if (!isset($data['cost_per_lead']) || $data['cost_per_lead'] == 0) {
            $data['cost_per_lead'] = $data['real_lead'] > 0 ? $data['spent_amount'] / $data['real_lead'] : 0;
        }

        $iklanBudget->update($data);

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
                    'spent_plus_tax' => 0,
                    'real_lead' => 0,
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