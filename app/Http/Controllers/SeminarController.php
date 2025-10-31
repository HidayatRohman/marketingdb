<?php

namespace App\Http\Controllers;

use App\Models\Seminar;
use App\Models\Mitra;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SeminarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $perPage = (int) $request->get('per_page', 30);
        $perPage = in_array($perPage, [10, 20, 30, 50, 100]) ? $perPage : 30;

        $seminars = Seminar::orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Ambil peserta dari Mitra yang webinar = 'Ikut'
        $participantsQuery = Mitra::query()
            ->with(['brand:id,nama', 'label:id,nama,warna', 'user:id,name'])
            ->where('webinar', 'Ikut')
            ->orderByDesc('tanggal_lead');

        // Batasi marketing melihat data sendiri jika role terbatas
        if ($user->isMarketing() && $user->hasLimitedAccess()) {
            $participantsQuery->where('user_id', $user->id);
        }

        // Filter tanggal periode jika diberikan
        if ($startDate && $endDate) {
            $participantsQuery->whereBetween('tanggal_lead', [$startDate, $endDate]);
        } elseif ($startDate) {
            $participantsQuery->where('tanggal_lead', '>=', $startDate);
        } elseif ($endDate) {
            $participantsQuery->where('tanggal_lead', '<=', $endDate);
        }

        $participants = $participantsQuery->paginate($perPage)->withQueryString();

        return Inertia::render('Seminar/Index', [
            'seminars' => $seminars,
            'participants' => $participants,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'permissions' => [
                'canCrud' => $user->canCrud(),
                'canOnlyView' => $user->canOnlyView(),
                'canOnlyViewOwn' => $user->canOnlyViewOwn(),
                'hasFullAccess' => $user->hasFullAccess(),
                'hasReadOnlyAccess' => $user->hasReadOnlyAccess(),
                'hasLimitedAccess' => $user->hasLimitedAccess(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        return Inertia::render('Seminar/Create', [
            'permissions' => [
                'canCrud' => $user->canCrud(),
                'canOnlyView' => $user->canOnlyView(),
                'canOnlyViewOwn' => $user->canOnlyViewOwn(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'tanggal' => ['nullable', 'date'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        Seminar::create($validated);

        return redirect()->route('seminars.index')->with('success', 'Seminar berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Seminar $seminar)
    {
        $user = auth()->user();
        return Inertia::render('Seminar/Show', [
            'seminar' => $seminar,
            'permissions' => [
                'canCrud' => $user->canCrud(),
                'canOnlyView' => $user->canOnlyView(),
                'canOnlyViewOwn' => $user->canOnlyViewOwn(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Seminar $seminar)
    {
        $user = auth()->user();
        return Inertia::render('Seminar/Edit', [
            'seminar' => $seminar,
            'permissions' => [
                'canCrud' => $user->canCrud(),
                'canOnlyView' => $user->canOnlyView(),
                'canOnlyViewOwn' => $user->canOnlyViewOwn(),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Seminar $seminar)
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'tanggal' => ['nullable', 'date'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $seminar->update($validated);

        return redirect()->route('seminars.index')->with('success', 'Seminar berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Seminar $seminar)
    {
        $seminar->delete();
        return redirect()->route('seminars.index')->with('success', 'Seminar berhasil dihapus.');
    }

    /**
     * Export daftar peserta webinar (Mitra dengan webinar = 'Ikut') ke XLSX
     * Mendukung filter tanggal_lead berdasarkan periode [start_date, end_date]
     */
    public function exportParticipants(Request $request)
    {
        $user = auth()->user();

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = Mitra::query()
            ->with(['brand:id,nama', 'label:id,nama,warna', 'user:id,name'])
            ->where('webinar', 'Ikut');

        if ($user->isMarketing() && $user->hasLimitedAccess()) {
            $query->where('user_id', $user->id);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('tanggal_lead', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('tanggal_lead', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('tanggal_lead', '<=', $endDate);
        }

        $items = $query->orderByDesc('tanggal_lead')->get();

        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Peserta Webinar');

            // Header
            $sheet->setCellValue('A1', 'Nama');
            $sheet->setCellValue('B1', 'Kontak');
            $sheet->setCellValue('C1', 'Tanggal Lead');
            $sheet->setCellValue('D1', 'Marketing');
            $sheet->setCellValue('E1', 'Brand');
            $sheet->setCellValue('F1', 'Chat');
            $sheet->setCellValue('G1', 'Lokasi');
            $sheet->setCellValue('H1', 'Label');
            $sheet->setCellValue('I1', 'Webinar');

            $row = 2;
            foreach ($items as $item) {
                $sheet->setCellValue('A' . $row, (string) $item->nama);
                $sheet->setCellValue('B' . $row, (string) ($item->no_telp ?? ''));
                $sheet->setCellValue('C' . $row, (string) ($item->tanggal_lead ?? ''));
                $sheet->setCellValue('D' . $row, (string) (optional($item->user)->name ?? '-'));
                $sheet->setCellValue('E' . $row, (string) (optional($item->brand)->nama ?? '-'));
                $sheet->setCellValue('F' . $row, (string) ($item->chat ?? ''));
                $lokasi = trim(($item->kota ?? '') . (isset($item->provinsi) ? ', ' . $item->provinsi : ''));
                $sheet->setCellValue('G' . $row, (string) ($lokasi ?: '-'));
                $sheet->setCellValue('H' . $row, (string) (optional($item->label)->nama ?? '-'));
                $sheet->setCellValue('I' . $row, (string) ($item->webinar ?? ''));
                $row++;
            }

            $filename = 'export-peserta-webinar-' . now()->format('Ymd_His') . '.xlsx';
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Seminar participants export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'filters' => $request->all(),
            ]);
            return back()->with('error', 'Export gagal: ' . $e->getMessage());
        }
    }
}