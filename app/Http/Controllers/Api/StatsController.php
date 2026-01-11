<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\CommonResponse;
use App\Models\RekamMedis;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\DetailPembelianObat;
use App\Models\DetailResepObat;
use App\Models\Layanan;

use Illuminate\Support\Facades\Log;

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
        $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->toDateString());

        $parsedStartDate = Carbon::parse($startDate)->startOfMonth();
        $parsedEndDate = Carbon::parse($endDate)->endOfMonth();

        return [$parsedStartDate, $parsedEndDate];
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

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Durasi periode (dalam detik)
        $durationInSeconds = $start->diffInSeconds($end);

        // Periode sebelumnya
        $prevStart = $start->copy()->subSeconds($durationInSeconds);
        $prevEnd = $start;

        // =====================
        // DATA PER LAYANAN
        // =====================
        $perLayanan = RekamMedis::query()
            ->select(['jenis_tindakan as layanan', DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, waktu_dilayani)) as avg_minutes')])
            ->whereNotNull('jenis_tindakan')
            ->whereNotNull('waktu_dilayani')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('jenis_tindakan')
            ->get()
            ->map(function ($item) {
                return [
                    'layanan' => $item->layanan,
                    'avg_minutes' => (int) round($item->avg_minutes),
                    'target_threshold' => 15,
                ];
            })
            ->values();

        // =====================
        // INSIGHT: LAYANAN TERLAMA
        // =====================
        $layananTerlama = $perLayanan->sortByDesc('avg_minutes')->first();

        $insightTerlama = $layananTerlama
            ? [
                'layanan' => $layananTerlama['layanan'],
                'avg_minutes' => $layananTerlama['avg_minutes'],
                'pesan' => "Waktu tunggu terlama terjadi pada {$layananTerlama['layanan']} dengan rata-rata {$layananTerlama['avg_minutes']} menit",
            ]
            : null;

        // =====================
        // RATA-RATA PERIODE AKTIF
        // =====================
        $currentAvg = RekamMedis::query()
            ->whereNotNull('waktu_dilayani')
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, waktu_dilayani)) as avg'))
            ->value('avg');

        // =====================
        // RATA-RATA PERIODE SEBELUMNYA
        // =====================
        $previousAvg = RekamMedis::query()
            ->whereNotNull('waktu_dilayani')
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, waktu_dilayani)) as avg'))
            ->value('avg');

        $currentAvg = $currentAvg ? round($currentAvg) : 0;
        $previousAvg = $previousAvg ? round($previousAvg) : 0;

        // =====================
        // RINGKASAN
        // =====================
        $diffMinutes = $currentAvg - $previousAvg;

        if ($diffMinutes > 0) {
            $keterangan = "Bertambah {$diffMinutes} menit dari periode terakhir";
        } elseif ($diffMinutes < 0) {
            $keterangan = 'Berkurang ' . abs($diffMinutes) . ' menit dari periode terakhir';
        } else {
            $keterangan = 'Tidak ada perubahan dibanding periode terakhir';
        }

        $data = [
            'per_layanan' => $perLayanan,
            'ringkasan' => [
                'rata_rata' => $currentAvg,
                'perbedaan' => $diffMinutes,
                'keterangan' => $keterangan,
                'layanan_terlama' => $insightTerlama,
            ],
        ];

        return CommonResponse::ok($data);
    }

    /**
     * 3. Jenis dan Tren Penyakit (Top 10 & Grouped Bar Chart)
     */
    public function jenisTrenPenyakit(Request $request)
    {
        [$startDate, $endDate] = $this->getFilterDates($request);

        $data = [
            'top_10_penyakit' => [], // Eloquent: count penyakit limit 10
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



public function marginKeuntungan(Request $request)
{
    // =========================
    // 1. Ambil range tanggal
    // =========================
    $start = $request->start_date 
        ? $request->start_date . ' 00:00:00' 
        : now()->startOfMonth();

    $end = $request->end_date 
        ? $request->end_date . ' 23:59:59' 
        : now()->endOfMonth();

    // =========================
    // 2. TOTAL MODAL OBAT
    // dari detail_pembelian_obats.total
    // =========================
    $totalModal = DB::table('detail_pembelian_obats')
        ->join(
            'pembelian_obats',
            'detail_pembelian_obats.kode_pembelian',
            '=',
            'pembelian_obats.no_transaksi'
        )
        ->whereBetween('pembelian_obats.tanggal', [$start, $end])
        ->sum('detail_pembelian_obats.total');

    // =========================
    // 3. TOTAL PENJUALAN OBAT
    // dari detail_resep_obats.total
    // =========================
    $pendapatanObat = DB::table('detail_resep_obats')
        ->whereBetween('created_at', [$start, $end])
        ->sum('total');

    // =========================
    // 4. TOTAL PENDAPATAN LAYANAN
    // join rekam_medis.kode_layanan -> layanans.nama_layanan
    // =========================
    $pendapatanLayanan = DB::table('rekam_medis')
        ->join(
            'layanans',
            'rekam_medis.kode_layanan',
            '=',
            'layanans.nama_layanan'
        )
        ->whereBetween('rekam_medis.created_at', [$start, $end])
        ->sum('layanans.harga');

    // =========================
    // 5. HITUNG TOTAL & MARGIN
    // =========================
    $totalPendapatan = $pendapatanObat + $pendapatanLayanan;
    $marginKeuntungan = $totalPendapatan - $totalModal;

    // =========================
    // 6. RESPONSE API
    // =========================
    return CommonResponse::ok([
        'periode' => [
            'start' => $start,
            'end' => $end,
        ],
        'modal_obat' => (int) $totalModal,
        'pendapatan_obat' => (int) $pendapatanObat,
        'pendapatan_layanan' => (int) $pendapatanLayanan,
        'total_pendapatan' => (int) $totalPendapatan,
        'margin_keuntungan' => (int) $marginKeuntungan,
    ], 'Berhasil mengambil data margin keuntungan');
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
