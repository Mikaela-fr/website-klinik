<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\CommonResponse;
use App\Models\DetailPembelianObat;
use App\Models\DetailResepObat;
use App\Models\Layanan;
use App\Models\RekamMedis;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatsController extends Controller
{
    /**
     * Helper: Mendapatkan rentang tanggal dari request
     */
    private function getFilterDates(Request $request)
    {
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->toDateString());

        $parsedStartDate = Carbon::parse($startDate)->startOfMonth();
        $parsedEndDate = Carbon::parse($endDate)->endOfMonth();

        return [$parsedStartDate, $parsedEndDate];
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
                'text'       => $currentTotal > 0 ? 'Meningkat 100% (Data sebelumnya kosong)' : 'Tidak ada perubahan',
                'diff_nominal' => $currentTotal
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

    /**
     * 1. Kunjungan Pasien (Filter + Insight + Cache)
     */
    public function kunjunganPasien(Request $request)
    {
        $hasFilter = $request->has(['start_date', 'end_date']);
        
        // Inisialisasi variabel untuk scope cache
        $startDate = null; $endDate = null;
        $start = null; $end = null;
        $prevStartDate = null; $prevEndDate = null;
        $cacheKey = "";
        $label = "";

        if ($hasFilter) {
            // Bandingkan: Range Tanggal Ini vs Range Tanggal Sebelumnya (Durasi sama)            
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            
            $start = $startDate . ' 00:00:00';
            $end   = $endDate . ' 23:59:59';

            // Hitung mundur tanggal untuk perbandingan (Insight)
            $dateDiff = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
            
            $prevStartDate = Carbon::parse($startDate)->subDays($dateDiff)->toDateString();
            $prevEndDate   = Carbon::parse($endDate)->subDays($dateDiff)->toDateString();
            
            // Key Cache Unik per Tanggal
            $cacheKey = "stats_kunjungan_{$startDate}_{$endDate}";
            $label = "Data Periode $startDate s/d $endDate";

        } else {
            // Bandingkan: Hari Ini (Live) vs Kemarin            
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

            // 3. Kalkulasi Menggunakan Helper
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

        return CommonResponse::ok($data, "Statistik kunjungan pasien berhasil diambil");
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

    /**
     * 5. Margin Keuntungan
     */
    public function marginKeuntungan(Request $request)
    {
        $filter = $request->filter ?? 'bulanan';

        // =========================
        // FORMAT GROUPING PERIODE
        // =========================
        switch ($filter) {
            case 'mingguan':
                $groupBy = "YEARWEEK(%s, 1)";
                break;

            case 'tahunan':
                $groupBy = "YEAR(%s)";
                break;

            default:
                $groupBy = "DATE_FORMAT(%s, '%Y-%m')";
                $filter = 'bulanan';
        }

        // =========================
        // PENGELUARAN (MODAL OBAT)
        // =========================
        $modalData = DB::table('detail_pembelian_obats')
            ->join(
                'pembelian_obats',
                'detail_pembelian_obats.kode_pembelian',
                '=',
                'pembelian_obats.no_transaksi'
            )
            ->selectRaw(
                str_replace('%s', 'pembelian_obats.tanggal', $groupBy) . ' AS periode,
                SUM(detail_pembelian_obats.total) AS total'
            )
            ->groupBy('periode')
            ->get();

        $totalModal = $modalData->sum('total');
        $jumlahPeriodeModal = $modalData->count();

        // =========================
        // PEMASUKAN OBAT
        // =========================
        $obatData = DB::table('detail_resep_obats')
            ->selectRaw(
                str_replace('%s', 'created_at', $groupBy) . ' AS periode,
                SUM(total) AS total'
            )
            ->groupBy('periode')
            ->get();

        // =========================
        // PEMASUKAN LAYANAN
        // =========================
        $layananData = DB::table('rekam_medis')
            ->join(
                'layanans',
                'rekam_medis.kode_layanan',
                '=',
                'layanans.nama_layanan'
            )
            ->selectRaw(
                str_replace('%s', 'rekam_medis.created_at', $groupBy) . ' AS periode,
                SUM(layanans.harga) AS total'
            )
            ->groupBy('periode')
            ->get();

        // =========================
        // TOTAL PEMASUKAN
        // =========================
        $totalPendapatan = $obatData->sum('total') + $layananData->sum('total');

        $jumlahPeriodePendapatan = max(
            $obatData->count(),
            $layananData->count()
        );

        // =========================
        // RATA-RATA
        // =========================
        $rataPendapatan = $jumlahPeriodePendapatan > 0
            ? $totalPendapatan / $jumlahPeriodePendapatan
            : 0;

        $rataPengeluaran = $jumlahPeriodeModal > 0
            ? $totalModal / $jumlahPeriodeModal
            : 0;

        // =========================
        // MARGIN
        // =========================
        $marginNominal = $totalPendapatan - $totalModal;
        $marginPersen = $totalPendapatan > 0
            ? round(($marginNominal / $totalPendapatan) * 100, 2)
            : 0;

        // =========================
        // LABEL
        // =========================
        $label = $marginNominal >= 0 ? 'Positif' : 'Negatif';

        // =========================
        // RESPONSE
        // =========================
        return CommonResponse::ok([
            'filter' => $filter,
            'total_pendapatan' => (int) $totalPendapatan,
            'total_pengeluaran' => (int) $totalModal,
            'margin_nominal' => (int) $marginNominal,
            'margin_percentage' => $marginPersen,
            'label' => $label,
            'rata_rata' => [
                'pendapatan' => (int) $rataPendapatan,
                'pengeluaran' => (int) $rataPengeluaran
            ]
        ], 'Data margin keuntungan berhasil diambil');
    }

    /**
     * 6. Inventory Turnover Rate (Bar Chart per Kategori Obat)
     */
    public function inventoryTurnoverRate(Request $request)
    {
        // TODO: Implement inventory turnover logic
        return CommonResponse::ok([], "Fitur belum diimplementasikan");
    }
}