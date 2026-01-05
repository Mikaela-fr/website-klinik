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
    private function getFilterDates(Request $request)
    {
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->query('end_date', Carbon::now()->endOfMonth()->toDateString());

        return [$startDate, $endDate];
    }

    // 1. Kunjungan Pasien (SEMUA DATA / All Time)
    public function kunjunganPasien(Request $request)
    {
        $data = Cache::remember('stats_kunjungan_all_time', 3600, function () {
            
            // 1. Query Time Series
            $timeSeries = RekamMedis::selectRaw('DATE(created_at) as date, count(*) as total')
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get()
                ->toArray();

            // 2. Query Per Wilayah
            $perWilayah = RekamMedis::query()
                ->join('pasiens', 'rekam_medis.no_pasien', '=', 'pasiens.no_pasien')
                ->join('tbl_regions', 'pasiens.kode_kecamatan', '=', 'tbl_regions.region_code')
                ->selectRaw('tbl_regions.region_name as wilayah, count(*) as total')
                ->groupBy('tbl_regions.region_code', 'tbl_regions.region_name') 
                ->orderByDesc('total')
                ->get()
                ->toArray();
                
            return [
                'label' => 'Data Seluruh Waktu',
                'time_series' => $timeSeries,
                'per_wilayah' => $perWilayah
            ];
        });

        return CommonResponse::ok($data, "Seluruh data kunjungan pasien berhasil dimuat");
    }

    // // 1. Kunjungan Pasien (Line Chart & Bar Chart per Wilayah)
    // public function kunjunganPasien(Request $request)
    // {
    //     [$startDate, $endDate] = $this->getFilterDates($request);

    //     // // TODO: Gunakan ->whereBetween('tanggal_kolom', [$startDate, $endDate])
    //     // $data = [
    //     //     'time_series' => [], // Eloquent: count kunjungan group by date
    //     //     'per_wilayah' => [], // Eloquent: count kunjungan group by wilayah
    //     // ];
    //     $timeSeries = RekamMedis::selectRaw('DATE(created_at) as date, count(*) as total')
    //         ->groupBy('date')
    //         ->orderBy('date', 'asc')
    //         ->get();

    //     $perWilayah = RekamMedis::query()
    //         ->join('pasiens', 'rekam_medis.no_pasien', '=', 'pasiens.no_pasien')
    //         ->selectRaw('pasiens.alamat as wilayah, count(*) as total')
    //         ->groupBy('wilayah')
    //         ->orderByDesc('total')
    //         ->get();

    //     $data = [
    //         'time_series' => $timeSeries,
    //         'per_wilayah' => $perWilayah
    //     ];

    //     return CommonResponse::ok($data, "Statistik kunjungan pasien periode $startDate s/d $endDate berhasil diambil");
    // }

    // 2. Waktu Tunggu Rata-rata (Bar Chart per Layanan)
    public function waktuTunggu(Request $request)
    {
        [$startDate, $endDate] = $this->getFilterDates($request);

        $data = [
            'per_layanan' => [], // Eloquent: avg(waktu_selesai - waktu_daftar) per layanan
        ];

        return CommonResponse::ok($data, "Statistik waktu tunggu periode $startDate s/d $endDate berhasil diambil");
    }

    // 3. Jenis dan Tren Penyakit (Top 10 & Grouped Bar Chart)
    public function jenisTrenPenyakit(Request $request)
    {
        [$startDate, $endDate] = $this->getFilterDates($request);

        $data = [
            'top_10_penyakit'      => [], // Eloquent: count penyakit limit 10
            'top_wilayah_penyakit' => [], // Eloquent: group by wilayah & penyakit
        ];

        return CommonResponse::ok($data, "Statistik tren penyakit periode $startDate s/d $endDate berhasil diambil");
    }


    // 4. Pendapatan dan Pengeluaran (Line Charts)
    public function pendapatanPengeluaran(Request $request)
    {
        [$startDate, $endDate] = $this->getFilterDates($request);

        $data = [
            'tren_keuangan' => [], // Eloquent: sum(nominal) group by bulan/hari
        ];

        return CommonResponse::ok($data, "Statistik keuangan periode $startDate s/d $endDate berhasil diambil");
    }

    // 5. Margin Keuntungan
    public function marginKeuntungan(Request $request)
    {
        [$startDate, $endDate] = $this->getFilterDates($request);

        $data = [
            'total_modal'       => 0, // Total pengeluaran operasional
            'margin_nominal'    => 0, // Pendapatan - Pengeluaran
            'margin_percentage' => 0, // (Margin Nominal / Pendapatan) * 100
            'label'             => '', // "Positif" atau "Negatif"
        ];

        return CommonResponse::ok($data, "Data margin periode $startDate s/d $endDate berhasil diambil");
    }

    // 6. Inventory Turnover Rate (Bar Chart per Kategori Obat)
    public function inventoryTurnoverRate(Request $request)
    {
        [$startDate, $endDate] = $this->getFilterDates($request);

        $data = [
            'categories' => [], // Eloquent: Rasio per kategori (COGS / Avg Inventory)
        ];

        return CommonResponse::ok($data, "Statistik inventory turnover periode $startDate s/d $endDate berhasil diambil");
    }
}
