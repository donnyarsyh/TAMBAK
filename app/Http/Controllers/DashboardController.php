<?php

namespace App\Http\Controllers;

use App\Models\SensorData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Tambahkan ini untuk akses DB langsung

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Ambil data terbaru untuk dashboard
        $latest = SensorData::latest()->first() ?? new SensorData(['suhu' => 0, 'ph' => 0, 'salinitas' => 0, 'kondisi_air' => '-']);
        $history = SensorData::latest()->take(10)->get();
        $chartData = SensorData::latest()->take(7)->get()->reverse();

        // 2. Ambil status monitoring (Start/Stop) dari tabel app_settings
        // Jika data belum ada, kita beri default 'stop'
        $statusRecord = DB::table('app_settings')->where('key', 'monitoring_status')->first();
        $status = $statusRecord ? $statusRecord->value : 'stop';

        // 3. Kirim semua variabel ke view (termasuk $status)
        return view('dashboard', compact('latest', 'history', 'chartData', 'status'));
    }

    // Fungsi Toggle (Sudah kita bahas sebelumnya, pastikan ada)
    public function toggleStatus()
    {
        $current = DB::table('app_settings')->where('key', 'monitoring_status')->first();
        
        if (!$current) {
            DB::table('app_settings')->insert(['key' => 'monitoring_status', 'value' => 'start']);
            $newStatus = 'start';
        } else {
            $newStatus = ($current->value == 'start') ? 'stop' : 'start';
            DB::table('app_settings')->where('key', 'monitoring_status')->update(['value' => $newStatus]);
        }
        
        return response()->json(['status' => $newStatus]);
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
}