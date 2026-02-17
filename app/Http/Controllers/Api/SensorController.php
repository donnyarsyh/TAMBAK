<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorData;
use Illuminate\Http\Request;

class SensorController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi data yang masuk
        $request->validate([
            'suhu' => 'required',
            'ph' => 'required',
            'salinitas' => 'required',
        ]);

        try {
            // 2. Logika Sederhana (Sebelum kamu memasukkan rumus Fuzzy Tsukamoto yang asli)
            $kondisi = 'Normal';
            if ($request->ph < 6 || $request->ph > 9) {
                $kondisi = 'Buruk';
            } else {
                $kondisi = 'Baik';
            }

            // 3. Simpan ke Database
            $data = SensorData::create([
                'suhu' => $request->suhu,
                'ph' => $request->ph,
                'salinitas' => $request->salinitas,
                'nilai_z' => rand(70, 90), // Sementara pakai angka acak untuk grafik
                'kondisi_air' => $kondisi
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil disimpan',
                'data' => $data
            ], 201);

        } catch (\Exception $e) {
            // Jika ada error, kirim pesan errornya agar terlihat di Serial Monitor
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}