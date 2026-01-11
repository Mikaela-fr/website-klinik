<?php

namespace App\Http\Controllers\Api;

use App\Models\RekamMedis;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Responses\CommonResponse;
use Illuminate\Support\Facades\Cache;

class StatsController extends Controller
{
    /**
     * Helper: Mendapatkan rentang tanggal dari request
     */
    private function getFilterDates(Request $request)
    {
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->query('end_date', Carbon::now()->endOfMonth()->toDateString());

        return [$startDate, $endDate];
    }

    /**
     * Helper: Menghitung Insight (Persentase & Tren)
     */
    private function calculateInsight(int $currentTotal, int $pastTotal): array
    {
        // 1. Handle jika data masa lalu kosong (division by zero prevention)
        if ($pastTotal == 0) {
            return [
                'percentage' => $currentTotal > 0 ? 100 : 0,
                'trend'      => 'up',
                'text'       => $currentTotal > 0 ? 'Meningkat 100% (Data sebelumnya kosong)' : 'Tidak ada perubahan'
            ];
        }

        // 2. Hitung selisih dan persentase
        $diff = $currentTotal - $pastTotal;
        $percentage = round(($diff / $pastTotal) * 100, 1);
        
        // 3. Tentukan arah tren dan teks
        if ($diff > 0) {
            $trend = 'up';
            $text = "Naik " . abs($percentage) . "% dari periode sebelumnya";
        } elseif ($diff < 0) {
            $trend = 'down';
            $text = "Turun " . abs($percentage) . "% dari periode sebelumnya";
        } else {
            $trend = 'neutral';
            $text = "Stabil (Sama dengan periode sebelumnya)";
        }

        return [
            'percentage' => abs($percentage),
            'trend'      => $trend,
            'text'       => $text,
            'diff_nominal' => $diff
        ];
    }

    // 1. Kunjungan Pasien (Filter + Insight + Cache)
    public function kunjunganPasien(Request $request)
    {
        $hasFilter = $request->has(['start_date', 'end_date']);

        if ($hasFilter) {

            // Bandingkan: Range Tanggal Ini vs Range Tanggal Sebelumnya (Durasi sama)            
            [$startDate, $endDate] = $this->getFilterDates($request);
            $start = $startDate . ' 00:00:00';
            $end   = $endDate . ' 23:59:59';

            // Hitung mundur tanggal untuk perbandingan (Insight)
            // Misal filter 3 hari, maka pembandingnya adalah 3 hari sebelum start date
            $dateDiff = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
            
            $prevStartDate = Carbon::parse($startDate)->subDays($dateDiff)->toDateString();
            $prevEndDate   = Carbon::parse($endDate)->subDays($dateDiff)->toDateString();
            
            // Key Cache Unik per Tanggal
            $cacheKey = "stats_kunjungan_{$startDate}_{$endDate}";
            $label = "Data Periode $startDate s/d $endDate";

        } else {
            // Bandingkan: Hari Ini (Live) vs Kemarin            
            $startDate = $endDate = $start = $end = null;
            
            // Untuk insight default, kita pakai Today vs Yesterday
            $today = Carbon::today()->toDateString();
            $yesterday = Carbon::yesterday()->toDateString();

            $start = $today . ' 00:00:00';
            $end   = $today . ' 23:59:59';
            
            $prevStartDate = $yesterday;
            $prevEndDate   = $yesterday;

            // Key Cache Statis
            $cacheKey = "stats_kunjungan_all_time";
            $label = "Data Seluruh Waktu";
        }

        // Simpan di Cache selama 1 jam (3600 detik)
        $data = Cache::remember($cacheKey, 3600, function () use ($hasFilter, $start, $end, $label, $prevStartDate, $prevEndDate) {
            
            // A. Query Chart Time Series
            $timeSeriesQuery = RekamMedis::selectRaw('DATE(created_at) as date, count(*) as total');
            if ($hasFilter) {
                $timeSeriesQuery->whereBetween('created_at', [$start, $end]);
            }
            $timeSeries = $timeSeriesQuery->groupBy('date')->orderBy('date', 'asc')->get()->toArray();

            // B. Query Chart Wilayah
            $perWilayahQuery = RekamMedis::query()
                ->join('pasiens', 'rekam_medis.no_pasien', '=', 'pasiens.no_pasien')
                ->join('tbl_regions', 'pasiens.kode_kecamatan', '=', 'tbl_regions.region_code')
                ->selectRaw('tbl_regions.region_name as wilayah, count(*) as total');
            if ($hasFilter) {
                $perWilayahQuery->whereBetween('rekam_medis.created_at', [$start, $end]);
            }
            $perWilayah = $perWilayahQuery
                ->groupBy('tbl_regions.region_code', 'tbl_regions.region_name')
                ->orderByDesc('total')
                ->get()
                ->toArray();

            // C. Hitung Insight (Total & Perbandingan)
            
            // 1. Total Periode Ini
            if ($hasFilter) {
                // Jika filter aktif, total diambil dari penjumlahan data chart
                $currentTotal = array_sum(array_column($timeSeries, 'total'));
            } else {
                // Jika all time, currentTotal untuk insight adalah "Hari Ini"
                $currentTotal = RekamMedis::whereDate('created_at', Carbon::today())->count();
            }

            // 2. Total Periode Lalu (Query Ringan)
            $pastTotal = RekamMedis::whereBetween('created_at', [
                    $prevStartDate . ' 00:00:00', 
                    $prevEndDate . ' 23:59:59'
                ])->count();

            // 3. Kalkulasi
            $insightData = $this->calculateInsight($currentTotal, $pastTotal);

            // Total Keseluruhan (Untuk ditampilkan di Card Utama jika mode All Time)
            $grandTotal = $hasFilter ? $currentTotal : RekamMedis::count();

            return [
                'label'       => $label,
                'summary'     => [
                    'total_kunjungan' => $grandTotal,
                    'insight'         => $insightData
                ],
                'time_series' => $timeSeries,
                'per_wilayah' => $perWilayah
            ];
        });

        return CommonResponse::ok($data);
    }
}