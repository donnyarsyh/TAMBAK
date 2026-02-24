<?php

namespace App\Http\Controllers;

use App\Models\SensorData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\FuzzyRule;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Ambil data terbaru untuk dashboard
        $latest = SensorData::latest()->first() ?? new SensorData(['suhu' => 0, 'ph' => 0, 'salinitas' => 0, 'kondisi_air' => '-']);
        $history = SensorData::latest()->take(10)->get();
        $chartData = SensorData::latest()->take(7)->get()->reverse();

        // 2. Ambil status monitoring (Start/Stop) dari tabel app_settings
        $statusRecord = DB::table('app_settings')->where('key', 'monitoring_status')->first();
        $status = $statusRecord ? $statusRecord->value : 'stop';

        // 3. Kirim semua variabel ke view (termasuk $status)
        return view('dashboard', compact('latest', 'history', 'chartData', 'status'));
    }

    // Fungsi Toggle
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
            if ($setting) {
                return response($setting->value, 200)
                    ->header('Content-Type', 'text/plain');
            }

            return response('stop', 200)->header('Content-Type', 'text/plain');

        } catch (\Exception $e) {
            return response('error', 500);
        }
    }

    public function store(Request $request) 
    {
        try {
            // Validasi data yang masuk dari ESP32
            $validated = $request->validate([
                'suhu' => 'required|numeric',
                'ph' => 'required|numeric',
                'salinitas' => 'required|numeric',
            ]);
            $sensor = new \App\Models\SensorData();
            $sensor->suhu = $request->suhu;
            $sensor->ph = $request->ph;
            $sensor->salinitas = $request->salinitas;
            $sensor->nilai_z = $request->nilai_z ?? 0; // Default 0 jika belum ada hitungan fuzzy
            $sensor->kondisi_air = $request->kondisi_air ?? 'Proses'; // Default 'Proses'
            $sensor->save();

            return response()->json(['status' => 'success', 'message' => 'Data tersimpan'], 201);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // Fungsi Fetch Data untuk AJAX
    public function fetchData()
    {
        $latest = SensorData::latest()->first();
        $history = SensorData::latest()->take(10)->get();
        $chartData = SensorData::latest()->take(7)->get()->reverse();

        return response()->json([
            'latest' => $latest,
            'history' => $history,
            'chartLabels' => $chartData->pluck('created_at')->map(fn($d) => $d->format('H:i'))->values(),
            'chartValues' => $chartData->pluck('nilai_z')->values(),
        ]);
    }

    // ========== FUZZY RULES ==========
    public function fuzzyRules() {
        $rules = FuzzyRule::all();
        return view('fuzzy_rules', compact('rules'));
    }

    public function storeRule(Request $request) {
        $count = \App\Models\FuzzyRule::count() + 1;
        
        $data = $request->all();
        $data['kode_rule'] = 'R' . $count; // Otomatis jadi R1, R2, dst
        
        \App\Models\FuzzyRule::create($data);
        return back()->with('success', 'Aturan berhasil ditambah');
    }

    public function updateRule(Request $request, $id) {
        // Validasi data
        $data = $request->validate([
            'kode_rule' => 'required',
            'suhu' => 'required',
            'ph' => 'required',
            'salinitas' => 'required',
            'output' => 'required',
        ]);

        // Update data di database
        \App\Models\FuzzyRule::where('id', $id)->update($data);

        return back()->with('success', 'Aturan berhasil diperbarui!');
    }

    public function deleteRule($id) {
        FuzzyRule::destroy($id);
        return back()->with('success', 'Aturan berhasil dihapus');
    }

    // ========== EXPORT DATA SENSOR ==========
    public function exportExcel()
    {
        $data = \App\Models\SensorData::latest()->take(100)->get();
        
        $fileName = 'log_sensor_kepiting_' . date('Ymd_His') . '.csv';
        
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('Waktu', 'Suhu (°C)', 'pH', 'Salinitas (ppt)', 'Kondisi Air');

        $callback = function() use($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($data as $row) {
                fputcsv($file, array(
                    $row->created_at->format('Y-m-d H:i:s'),
                    $row->suhu,
                    $row->ph,
                    $row->salinitas,
                    $row->kondisi_air
                ));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}