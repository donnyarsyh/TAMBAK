<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SensorData; 
use Exception;

class FuzzyController extends Controller
{
    // Fungsi ini dipanggil oleh route API untuk ESP32/Postman
    public function hitungFuzzy(Request $request)
    {
        try {
            $suhu = (float) $request->input('suhu');
            $ph = (float) $request->input('ph');
            $tds_ppm = (float) $request->input('salinitas'); 
            
            // Konversi PPM ke PPT
            $salinitas_ppt = $tds_ppm / 1000; 

            // Hitung Fuzzy
            $muS = $this->fuzzifikasiSuhu($suhu);
            $muP = $this->fuzzifikasiph($ph);
            $muL = $this->fuzzifikasiSalinitas($salinitas_ppt);
            $hasil_z = $this->inferensiTsukamoto($muS, $muP, $muL);

            $kondisi = ($hasil_z >= 70) ? 'Baik' : (($hasil_z >= 40) ? 'Sedang' : 'Buruk');

            // SIMPAN KE DATABASE
            $data = SensorData::create([
                'suhu' => $suhu,
                'ph' => $ph,
                'salinitas' => $salinitas_ppt,
                'nilai_z' => $hasil_z,
                'kondisi_air' => $kondisi
            ]);

            return response()->json([
                'status' => 'success',
                'data_terkirim' => ['suhu' => $suhu, 'ph' => $ph, 'salinitas_ppt' => $salinitas_ppt],
                'hasil_z' => round($hasil_z, 2),
                'kondisi' => $kondisi,
                'db_id' => $data->id
            ], 200);

        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // Pastikan Public agar bisa dipanggil DashboardController
    public function fuzzifikasiSuhu($x) {
        $mu = ['buruk' => 0, 'sedang' => 0, 'baik' => 0];
        if ($x <= 20 || $x >= 40) $mu['buruk'] = 1;
        else if ($x > 20 && $x < 22) $mu['buruk'] = (22 - $x) / (22 - 20);
        else if ($x > 38 && $x < 40) $mu['buruk'] = ($x - 38) / (40 - 38);

        if (($x >= 22 && $x <= 25) || ($x >= 35 && $x <= 38)) $mu['sedang'] = 1;
        else if ($x > 20 && $x < 22) $mu['sedang'] = ($x - 20) / (22 - 20);
        else if ($x > 25 && $x < 27) $mu['sedang'] = (27 - $x) / (27 - 25);
        else if ($x > 33 && $x < 35) $mu['sedang'] = ($x - 33) / (35 - 33);
        else if ($x > 38 && $x < 40) $mu['sedang'] = (40 - $x) / (40 - 38);

        if ($x >= 27 && $x <= 33) $mu['baik'] = 1;
        else if ($x > 25 && $x < 27) $mu['baik'] = ($x - 25) / (27 - 25);
        else if ($x > 33 && $x < 35) $mu['baik'] = (35 - $x) / (35 - 33);
        return $mu;
    }

    public function fuzzifikasiph($x) {
        $mu = ['buruk' => 0, 'sedang' => 0, 'baik' => 0];
        if ($x <= 6.8 || $x >= 9.0) $mu['buruk'] = 1;
        else if ($x > 6.8 && $x < 7.0) $mu['buruk'] = (7.0 - $x) / (7.0 - 6.8);
        else if ($x > 8.8 && $x < 9.0) $mu['buruk'] = ($x - 8.8) / (9.0 - 8.8);

        if (($x >= 7.0 && $x <= 7.5) || ($x >= 8.5 && $x <= 8.8)) $mu['sedang'] = 1;
        else if ($x > 6.8 && $x < 7.0) $mu['sedang'] = ($x - 6.8) / (7.0 - 6.8);
        else if ($x > 7.5 && $x < 7.7) $mu['sedang'] = (7.7 - $x) / (7.7 - 7.5);
        else if ($x > 8.3 && $x < 8.5) $mu['sedang'] = ($x - 8.3) / (8.5 - 8.3);
        else if ($x > 8.8 && $x < 9.0) $mu['sedang'] = (9.0 - $x) / (9.0 - 8.8);

        if ($x >= 7.7 && $x <= 8.3) $mu['baik'] = 1;
        else if ($x > 7.5 && $x < 7.7) $mu['baik'] = ($x - 7.5) / (7.7 - 7.5);
        else if ($x > 8.3 && $x < 8.5) $mu['baik'] = (8.5 - $x) / (8.5 - 8.3);
        return $mu;
    }

    public function fuzzifikasiSalinitas($x) {
        $mu = ['buruk' => 0, 'sedang' => 0, 'baik' => 0];
        if ($x <= 8 || $x >= 35) $mu['buruk'] = 1;
        else if ($x > 8 && $x < 10) $mu['buruk'] = (10 - $x) / (10 - 8);
        else if ($x > 33 && $x < 35) $mu['buruk'] = ($x - 33) / (35 - 33);

        if (($x >= 10 && $x <= 15) || ($x >= 30 && $x <= 33)) $mu['sedang'] = 1;
        else if ($x > 8 && $x < 10) $mu['sedang'] = ($x - 8) / (10 - 8);
        else if ($x > 15 && $x < 17) $mu['sedang'] = (17 - $x) / (17 - 15);
        else if ($x > 28 && $x < 30) $mu['sedang'] = ($x - 28) / (30 - 28);
        else if ($x > 33 && $x < 35) $mu['sedang'] = (35 - $x) / (35 - 33);

        if ($x >= 17 && $x <= 28) $mu['baik'] = 1;
        else if ($x > 15 && $x < 17) $mu['baik'] = ($x - 15) / (17 - 15);
        else if ($x > 28 && $x < 30) $mu['baik'] = (30 - $x) / (30 - 28);
        return $mu;
    }

    public function inferensiTsukamoto($muS, $muP, $muL) {
        $zBaik = 100; $zSedang = 50; $zBuruk = 0;
        $rules = [];
        $rules[] = ['a' => min($muS['baik'], $muP['baik'], $muL['baik']), 'z' => $zBaik];
        $rules[] = ['a' => min($muS['baik'], $muP['baik'], $muL['sedang']), 'z' => $zSedang];
        $rules[] = ['a' => min($muS['baik'], $muP['baik'], $muL['buruk']), 'z' => $zBuruk];
        $rules[] = ['a' => min($muS['baik'], $muP['sedang'], $muL['baik']), 'z' => $zSedang];
        $rules[] = ['a' => min($muS['baik'], $muP['sedang'], $muL['sedang']), 'z' => $zSedang];
        $rules[] = ['a' => min($muS['baik'], $muP['sedang'], $muL['buruk']), 'z' => $zBuruk];
        $rules[] = ['a' => min($muS['baik'], $muP['buruk'], $muL['baik']), 'z' => $zBuruk];
        $rules[] = ['a' => min($muS['baik'], $muP['buruk'], $muL['sedang']), 'z' => $zBuruk];
        $rules[] = ['a' => min($muS['baik'], $muP['buruk'], $muL['buruk']), 'z' => $zBuruk];
        $rules[] = ['a' => min($muS['sedang'], $muP['baik'], $muL['baik']), 'z' => $zSedang];
        $rules[] = ['a' => min($muS['sedang'], $muP['baik'], $muL['sedang']), 'z' => $zSedang];
        $rules[] = ['a' => min($muS['sedang'], $muP['baik'], $muL['buruk']), 'z' => $zBuruk];
        $rules[] = ['a' => min($muS['sedang'], $muP['sedang'], $muL['baik']), 'z' => $zSedang];
        $rules[] = ['a' => min($muS['sedang'], $muP['sedang'], $muL['sedang']), 'z' => $zSedang];
        $rules[] = ['a' => min($muS['sedang'], $muP['sedang'], $muL['buruk']), 'z' => $zBuruk];
        $rules[] = ['a' => min($muS['sedang'], $muP['buruk'], $muL['baik']), 'z' => $zBuruk];
        $rules[] = ['a' => min($muS['sedang'], $muP['buruk'], $muL['sedang']), 'z' => $zBuruk];
        $rules[] = ['a' => min($muS['sedang'], $muP['buruk'], $muL['buruk']), 'z' => $zBuruk];
        $rules[] = ['a' => min($muS['buruk'], $muP['baik'], $muL['baik']), 'z' => $zBuruk];
        $rules[] = ['a' => min($muS['buruk'], $muP['baik'], $muL['sedang']), 'z' => $zBuruk];
        $rules[] = ['a' => min($muS['buruk'], $muP['baik'], $muL['buruk']), 'z' => $zBuruk];
        $rules[] = ['a' => min($muS['buruk'], $muP['sedang'], $muL['baik']), 'z' => $zBuruk];
        $rules[] = ['a' => min($muS['buruk'], $muP['sedang'], $muL['sedang']), 'z' => $zBuruk];
        $rules[] = ['a' => min($muS['buruk'], $muP['sedang'], $muL['buruk']), 'z' => $zBuruk];
        $rules[] = ['a' => min($muS['buruk'], $muP['buruk'], $muL['baik']), 'z' => $zBuruk];
        $rules[] = ['a' => min($muS['buruk'], $muP['buruk'], $muL['sedang']), 'z' => $zBuruk];
        $rules[] = ['a' => min($muS['buruk'], $muP['buruk'], $muL['buruk']), 'z' => $zBuruk];

        $total_az = 0; $total_a = 0;
        foreach ($rules as $rule) {
            $total_az += ($rule['a'] * $rule['z']);
            $total_a += $rule['a'];
        }
        return ($total_a > 0) ? ($total_az / $total_a) : 0;
    }
}