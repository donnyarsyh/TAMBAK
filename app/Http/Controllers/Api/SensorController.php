<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorData;
use Illuminate\Http\Request;
use App\Http\Controllers\FuzzyController; // Import FuzzyController

class SensorController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi data yang masuk dari ESP32
        $request->validate([
            'suhu'      => 'required',
            'ph'        => 'required',
            'v_ph'      => 'required', // Pastikan ESP32 mengirim voltase pH
            'salinitas' => 'required', // Data ini diterima dalam satuan PPT (sudah dibagi 1000 di ESP32)
        ]);

        try {
            // 2. Panggil Logika Fuzzy Tsukamoto (Prioritas Survival)
            $fuzzy = new FuzzyController();
            
            $suhu      = (float) $request->suhu;
            $ph        = (float) $request->ph;
            $v_ph      = (float) $request->v_ph;
            $salinitas = (float) $request->salinitas; // Satuan PPT

            // Jalankan tahapan fuzzy
            $muS = $fuzzy->fuzzifikasiSuhu($suhu);
            $muP = $fuzzy->fuzzifikasiph($ph);
            $muL = $fuzzy->fuzzifikasiSalinitas($salinitas);
            
            // Dapatkan Nilai Z menggunakan Rule Base Survival yang baru
            $nilai_z = $fuzzy->inferensiTsukamoto($muS, $muP, $muL);

            // 3. Tentukan Label Kondisi Air berdasarkan Nilai Z
            // Sesuai threshold: Baik (>=70), Sedang (40-69), Buruk (<40)
            $kondisi = ($nilai_z >= 70) ? 'Baik' : (($nilai_z >= 40) ? 'Sedang' : 'Buruk');

            // 4. Simpan ke Database dengan data yang sudah tervalidasi
            $data = SensorData::create([
                'suhu'         => $suhu,
                'ph'           => $ph,
                'v_ph'         => $v_ph,
                'salinitas'    => $salinitas,
                'nilai_z'      => $nilai_z,
                'kondisi_air'  => $kondisi
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data berhasil diproses dengan Fuzzy Tsukamoto',
                'data'    => [
                    'ph'      => $ph,
                    'v_ph'    => $v_ph,
                    'z_value' => round($nilai_z, 2),
                    'kondisi' => $kondisi
                ]
            ], 201);

        } catch (\Exception $e) {
            // Memudahkan troubleshooting melalui Serial Monitor ESP32
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}