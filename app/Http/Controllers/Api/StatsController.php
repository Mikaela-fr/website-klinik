<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\CommonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StatsController extends Controller
{
    /**
     * Helper untuk mendapatkan rentang tanggal filter.
     * Default: Awal bulan ini sampai akhir bulan ini.
     */
    private function getFilterDates(Request $request)
    {
        // Mengambil dari query param, jika tidak ada gunakan default awal & akhir bulan berjalan
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->query('end_date', Carbon::now()->endOfMonth()->toDateString());

        return [$startDate, $endDate];
    }

    /**
     * 1. Kunjungan Pasien (Line Chart & Bar Chart per Wilayah)
     */
    public function kunjunganPasien(Request $request)
    {
        [$startDate, $endDate] = $this->getFilterDates($request);

        // TODO: Gunakan ->whereBetween('tanggal_kolom', [$startDate, $endDate])
        $data = [
            'time_series' => [], // Eloquent: count kunjungan group by date
            'per_wilayah' => [], // Eloquent: count kunjungan group by wilayah
        ];

        return CommonResponse::ok($data, "Statistik kunjungan pasien periode $startDate s/d $endDate berhasil diambil");
    }

    /**
     * 2. Waktu Tunggu Rata-rata (Bar Chart per Layanan)
     */
    public function waktuTunggu(Request $request)
    {
        [$startDate, $endDate] = $this->getFilterDates($request);

        $data = [
            'per_layanan' => [], // Eloquent: avg(waktu_selesai - waktu_daftar) per layanan
        ];

        return CommonResponse::ok($data, "Statistik waktu tunggu periode $startDate s/d $endDate berhasil diambil");
    }

    /**
     * 3. Jenis dan Tren Penyakit (Top 10 & Grouped Bar Chart)
     */
    public function jenisTrenPenyakit(Request $request)
    {
        [$startDate, $endDate] = $this->getFilterDates($request);

        $data = [
            'top_10_penyakit'      => [], // Eloquent: count penyakit limit 10
            'top_wilayah_penyakit' => [], // Eloquent: group by wilayah & penyakit
        ];

        return CommonResponse::ok($data, "Statistik tren penyakit periode $startDate s/d $endDate berhasil diambil");
    }

    /**
     * 4. Pendapatan dan Pengeluaran (Line Charts)
     */
    public function pendapatanPengeluaran(Request $request)
    {
        [$startDate, $endDate] = $this->getFilterDates($request);

        $data = [
            'tren_keuangan' => [], // Eloquent: sum(nominal) group by bulan/hari
        ];

        return CommonResponse::ok($data, "Statistik keuangan periode $startDate s/d $endDate berhasil diambil");
    }

    /**
     * 5. Margin Keuntungan
     */
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

    /**
     * 6. Inventory Turnover Rate (Bar Chart per Kategori Obat)
     */
    public function inventoryTurnoverRate(Request $request)
    {
        [$startDate, $endDate] = $this->getFilterDates($request);

        $data = [
            'categories' => [], // Eloquent: Rasio per kategori (COGS / Avg Inventory)
        ];

        return CommonResponse::ok($data, "Statistik inventory turnover periode $startDate s/d $endDate berhasil diambil");
    }
}