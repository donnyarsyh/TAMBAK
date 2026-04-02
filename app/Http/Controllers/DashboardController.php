<?php

namespace App\Http\Controllers;

use App\Models\SensorData;
use App\Models\FuzzyRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\FuzzyController;

class DashboardController extends Controller
{
    public function index()
    {
        $latest = SensorData::latest()->first() ?? new SensorData([
            'suhu' => 0, 'ph' => 0, 'salinitas' => 0, 'nilai_z' => 0, 'kondisi_air' => '-'
        ]);
        $history = SensorData::latest()->take(10)->get();
        $chartData = SensorData::latest()->take(10)->get()->reverse();

        $statusRecord = DB::table('app_settings')->where('key', 'monitoring_status')->first();
        $status = $statusRecord ? $statusRecord->value : 'stop';

        return view('dashboard', compact('latest', 'history', 'chartData', 'status'));
    }

    public function fetchData()
    {
        // Ambil 10 data terbaru dan balik urutannya (terlama ke terbaru) untuk grafik
        $chartData = SensorData::latest()->take(10)->get()->reverse();

        return response()->json([
            'latest' => SensorData::latest()->first(),
            'history' => SensorData::latest()->take(10)->get(),
            'chartLabels' => $chartData->map(fn($d) => $d->created_at->format('H:i'))->values(),
            'chartValues' => $chartData->map(fn($d) => (float)$d->nilai_z)->values(), // Pastikan Float
        ]);
    }

    public function store(Request $request) 
    {
        $fuzzy = new FuzzyController();
        $suhu = (float) $request->suhu;
        $ph = (float) $request->ph;
        $salinitas = (float) $request->salinitas / 1000;

        $muS = $fuzzy->fuzzifikasiSuhu($suhu);
        $muP = $fuzzy->fuzzifikasiph($ph);
        $muL = $fuzzy->fuzzifikasiSalinitas($salinitas);
        $nilai_z = $fuzzy->inferensiTsukamoto($muS, $muP, $muL);
        $kondisi = ($nilai_z >= 70) ? 'Baik' : (($nilai_z >= 40) ? 'Sedang' : 'Buruk');

        $data = SensorData::create([
            'suhu' => $suhu, 'ph' => $ph, 'salinitas' => $salinitas,
            'nilai_z' => $nilai_z, 'kondisi_air' => $kondisi
        ]);

        return response()->json(['status' => 'success', 'data' => $data], 201);
    }

    public function toggleStatus() {
        $setting = DB::table('app_settings')->where('key', 'monitoring_status')->first();

        if (!$setting) {
            DB::table('app_settings')->insert([
                'key' => 'monitoring_status', 
                'value' => 'start',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $newStatus = 'start';
        } else {
            $newStatus = ($setting->value == 'start') ? 'stop' : 'start';
            DB::table('app_settings')->where('key', 'monitoring_status')->update([
                'value' => $newStatus,
                'updated_at' => now()
            ]);
        }
        return response()->json(['status' => $newStatus]);
    }

    public function checkStatus() 
    {
        try {
            $setting = DB::table('app_settings')->where('key', 'monitoring_status')->first();
            return response($setting ? $setting->value : 'stop', 200)->header('Content-Type', 'text/plain');
        } catch (\Exception $e) {
            return response('error', 500);
        }
    }

    // ========== MANAJEMEN FUZZY RULES ==========
    public function fuzzyRules() {
        $rules = FuzzyRule::orderBy(DB::raw('CAST(SUBSTRING(kode_rule, 2) AS UNSIGNED)'), 'ASC')->get();
        return view('fuzzy_rules', compact('rules'));
    }

    public function storeRule(Request $request) {
        $count = FuzzyRule::count() + 1;
        $data = $request->all();
        $data['kode_rule'] = 'R' . $count;
        FuzzyRule::create($data);
        return back()->with('success', 'Aturan berhasil ditambah');
    }

    public function updateRule(Request $request, $id) {
        $data = $request->validate([
            'kode_rule' => 'required',
            'suhu' => 'required',
            'ph' => 'required',
            'salinitas' => 'required',
            'output' => 'required',
        ]);
        FuzzyRule::where('id', $id)->update($data);
        return back()->with('success', 'Aturan berhasil diperbarui!');
    }

    public function deleteRule($id) {
        FuzzyRule::destroy($id);
        return back()->with('success', 'Aturan berhasil dihapus');
    }

    public function exportExcel()
    {
        $data = SensorData::latest()->take(100)->get();
        $fileName = 'log_sensor_kepiting_' . date('Ymd_His') . '.csv';
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
        ];

        $callback = function() use($data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Waktu', 'Suhu (°C)', 'pH', 'Salinitas (ppt)', 'Nilai Z', 'Kondisi Air']);
            foreach ($data as $row) {
                fputcsv($file, [
                    $row->created_at->format('Y-m-d H:i:s'),
                    $row->suhu,
                    $row->ph,
                    $row->salinitas,
                    $row->nilai_z,
                    $row->kondisi_air
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
}