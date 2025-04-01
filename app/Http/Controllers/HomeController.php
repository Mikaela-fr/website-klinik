<?php

namespace App\Http\Controllers;

use App\Models\Carousel;
use App\Models\RekamMedis;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('home.index');
    }

    public function antrian()
    {
        $pasienMenunggu = RekamMedis::whereDate('created_at', Carbon::today())->where('status', 'Menunggu')->orderBy('no_antrian', 'asc')->get();

        $multimedia = Carousel::where('jenis', 'like', 'video%')->orderBy('urutan', 'asc')->get();
        $teks = Carousel::where('jenis', 'text')->orderBy('urutan', 'asc')->get();

        foreach ($teks as $item) {
            $teksPanjang[] = $item->isi;
        }

        $teksPanjangGabung = implode(" | ", $teksPanjang);

        return view('home.antrian', compact('multimedia', 'teksPanjangGabung', 'pasienMenunggu'));
    }
}
