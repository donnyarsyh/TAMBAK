<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\SensorData; 
use Exception;

class FuzzyController extends Controller
{
    public function hitungFuzzy(Request $request)
    {
        try {
            $suhu = (float) $request->suhu;
            $ph = (float) $request->ph;
            $salinitas_ppt = (float) $request->salinitas;
            $muS = $this->fuzzifikasiSuhu($suhu);
            $muP = $this->fuzzifikasiph($ph);
            $muL = $this->fuzzifikasiSalinitas($salinitas_ppt);
            
            // Proses Inferensi Tsukamoto
            $hasil_z = $this->inferensiTsukamoto($muS, $muP, $muL);

            // Klasifikasi berdasarkan Nilai Z (Hasil Defuzzifikasi)
            $kondisi = ($hasil_z >= 70) ? 'Baik' : (($hasil_z >= 40) ? 'Sedang' : 'Buruk');

            $data = SensorData::create([
                'suhu' => $suhu,
                'ph' => $ph,
                // 'v_ph' => $request->v_ph ?? 0, // Pastikan menangkap voltase dari request
                'salinitas' => $salinitas_ppt,
                'nilai_z' => $hasil_z,
                'kondisi_air' => $kondisi
            ]);

            return response()->json(['status' => 'success', 'hasil_z' => $hasil_z, 'kondisi' => $kondisi], 200);

        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // --- METODE FUZZIFIKASI ---
    public function fuzzifikasiSuhu($x) {
        $mu = ['baik' => 0, 'sedang' => 0, 'buruk' => 0];
        if ($x >= 27 && $x <= 33) $mu['baik'] = 1;
        else if ($x >= 25 && $x < 27) $mu['baik'] = ($x - 25) / (27 - 25);
        else if ($x > 33 && $x <= 35) $mu['baik'] = (35 - $x) / (35 - 33);
        if (($x >= 22 && $x <= 25) || ($x >= 35 && $x <= 38)) $mu['sedang'] = 1;
        else if ($x >= 20 && $x < 22) $mu['sedang'] = ($x - 20) / (22 - 20);
        else if ($x > 25 && $x <= 27) $mu['sedang'] = (27 - $x) / (27 - 25);
        else if ($x >= 33 && $x < 35) $mu['sedang'] = ($x - 33) / (35 - 33);
        else if ($x > 38 && $x < 40) $mu['sedang'] = (40 - $x) / (40 - 38);
        if ($x <= 20 || $x >= 40) $mu['buruk'] = 1;
        else if ($x > 20 && $x < 22) $mu['buruk'] = (22 - $x) / (22 - 20);
        else if ($x > 38 && $x < 40) $mu['buruk'] = (40 - $x) / (40 - 38);
        return $mu;
    }

    public function fuzzifikasiph($x) {
        $mu = ['baik' => 0, 'sedang' => 0, 'buruk' => 0];
        if ($x >= 7.5 && $x <= 8.0) $mu['baik'] = 1;
        else if ($x >= 7.0 && $x < 7.5) $mu['baik'] = ($x - 7.0) / (7.5 - 7.0);
        else if ($x > 8.0 && $x <= 8.5) $mu['baik'] = (8.5 - $x) / (8.5 - 8.0);
        if (($x >= 6.8 && $x <= 7.0) || ($x >= 8.5 && $x <= 8.8)) $mu['sedang'] = 1;
        else if ($x >= 6.5 && $x < 6.8) $mu['sedang'] = ($x - 6.5) / (6.8 - 6.5);
        else if ($x >= 7.0 && $x <= 7.5) $mu['sedang'] = (7.5 - $x) / (7.5 - 7.0); 
        else if ($x >= 8.0 && $x <= 8.5) $mu['sedang'] = ($x - 8.0) / (8.5 - 8.0);
        else if ($x > 8.8 && $x < 9.0) $mu['sedang'] = (9.0 - $x) / (9.0 - 8.8);
        if ($x <= 6.5 || $x >= 9.0) $mu['buruk'] = 1;
        else if ($x > 6.5 && $x < 6.8) $mu['buruk'] = (6.8 - $x) / (6.8 - 6.5);
        else if ($x > 8.8 && $x < 9.0) $mu['buruk'] = (9.0 - $x) / (9.0 - 8.8);
        return $mu;
    }

    public function fuzzifikasiSalinitas($x) {
        $mu = ['baik' => 0, 'sedang' => 0, 'buruk' => 0];
        if ($x >= 17 && $x <= 28) $mu['baik'] = 1;
        else if ($x >= 15 && $x < 17) $mu['baik'] = ($x - 15) / (17 - 15);
        else if ($x > 28 && $x <= 30) $mu['baik'] = (30 - $x) / (30 - 28);
        if (($x >= 10 && $x <= 15) || ($x >= 30 && $x <= 33)) $mu['sedang'] = 1;
        else if ($x >= 8 && $x < 10) $mu['sedang'] = ($x - 8) / (10 - 8);
        else if ($x >= 15 && $x <= 17) $mu['sedang'] = (17 - $x) / (17 - 15);
        else if ($x >= 28 && $x <= 30) $mu['sedang'] = ($x - 28) / (30 - 28);
        else if ($x > 33 && $x <= 35) $mu['sedang'] = (35 - $x) / (35 - 33);
        if ($x <= 8 || $x >= 35) $mu['buruk'] = 1;
        else if ($x > 8 && $x < 10) $mu['buruk'] = (10 - $x) / (10 - 8);
        else if ($x > 33 && $x < 35) $mu['buruk'] = (35 - $x) / (35 - 33);
        return $mu;
    }

    // --- INFERENSI TSUKAMOTO ---
    public function inferensiTsukamoto($muS, $muP, $muL) {
        $zBaik = 100; $zSedang = 50; $zBuruk = 0;
        $rules = [];
        // KELOMPOK BAIK (Hanya jika mayoritas Baik/Sedang Ringan)
        $rules[] = ['a' => min($muS['baik'],   $muP['baik'],   $muL['baik']),   'z' => $zBaik];   // R1
        $rules[] = ['a' => min($muS['baik'],   $muP['sedang'], $muL['baik']),   'z' => $zBaik];   // R2
        $rules[] = ['a' => min($muS['baik'],   $muP['baik'],   $muL['sedang']), 'z' => $zBaik];   // R3
        $rules[] = ['a' => min($muS['sedang'], $muP['baik'],   $muL['baik']),   'z' => $zBaik];   // R4
        $rules[] = ['a' => min($muS['baik'],   $muP['sedang'], $muL['sedang']), 'z' => $zBaik];   // R5
        $rules[] = ['a' => min($muS['sedang'], $muP['sedang'], $muL['baik']),   'z' => $zBaik];   // R6
        $rules[] = ['a' => min($muS['sedang'], $muP['baik'],   $muL['sedang']), 'z' => $zBaik];   // R7

        // KELOMPOK SEDANG (Kondisi mulai tidak stabil)
        $rules[] = ['a' => min($muS['sedang'], $muP['sedang'], $muL['sedang']), 'z' => $zSedang]; // R8
        $rules[] = ['a' => min($muS['baik'],   $muP['baik'],   $muL['buruk']),  'z' => $zSedang]; // R9
        $rules[] = ['a' => min($muS['buruk'],  $muP['baik'],   $muL['baik']),   'z' => $zSedang]; // R10
        $rules[] = ['a' => min($muS['baik'],   $muP['buruk'],  $muL['baik']),   'z' => $zSedang]; // R11

        // KELOMPOK BURUK (Jika ada kombinasi Buruk + Sedang atau Buruk + Buruk)
        $rules[] = ['a' => min($muS['baik'],   $muP['sedang'], $muL['buruk']),  'z' => $zBuruk];  // R12
        $rules[] = ['a' => min($muS['baik'],   $muP['buruk'],  $muL['sedang']), 'z' => $zBuruk];  // R13
        $rules[] = ['a' => min($muS['sedang'], $muP['baik'],   $muL['buruk']),  'z' => $zBuruk];  // R14
        $rules[] = ['a' => min($muS['sedang'], $muP['buruk'],  $muL['baik']),   'z' => $zBuruk];  // R15
        $rules[] = ['a' => min($muS['buruk'],  $muP['sedang'], $muL['baik']),   'z' => $zBuruk];  // R16
        $rules[] = ['a' => min($muS['buruk'],  $muP['baik'],   $muL['sedang']), 'z' => $zBuruk];  // R17
        $rules[] = ['a' => min($muS['buruk'],  $muP['sedang'], $muL['sedang']), 'z' => $zBuruk];  // R18
        $rules[] = ['a' => min($muS['sedang'], $muP['sedang'], $muL['buruk']),  'z' => $zBuruk];  // R19
        $rules[] = ['a' => min($muS['sedang'], $muP['buruk'],  $muL['sedang']), 'z' => $zBuruk];  // R20
        $rules[] = ['a' => min($muS['buruk'],  $muP['buruk'],  $muL['baik']),   'z' => $zBuruk];  // R21
        $rules[] = ['a' => min($muS['buruk'],  $muP['baik'],   $muL['buruk']),  'z' => $zBuruk];  // R22
        $rules[] = ['a' => min($muS['baik'],   $muP['buruk'],  $muL['buruk']),  'z' => $zBuruk];  // R23
        $rules[] = ['a' => min($muS['buruk'],  $muP['buruk'],  $muL['buruk']),  'z' => $zBuruk];  // R24
        $rules[] = ['a' => min($muS['buruk'],  $muP['sedang'], $muL['buruk']),  'z' => $zBuruk];  // R25
        $rules[] = ['a' => min($muS['sedang'], $muP['buruk'],  $muL['buruk']),  'z' => $zBuruk];  // R26
        $rules[] = ['a' => min($muS['buruk'],  $muP['buruk'],  $muL['sedang']), 'z' => $zBuruk];  // R27

        $total_az = 0; $total_a = 0;
        foreach ($rules as $rule) {
            $total_az += ($rule['a'] * $rule['z']);
            $total_a += $rule['a'];
        }
        return ($total_a > 0) ? ($total_az / $total_a) : 0;
    }
}