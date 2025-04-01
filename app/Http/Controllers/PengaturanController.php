<?php

namespace App\Http\Controllers;

use App\Http\Responses\CommonResponse;
use App\Models\Carousel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PengaturanController extends Controller
{
    public function index()
    {
        $multimedias = Carousel::where('jenis', 'like', 'video%')->orderBy('urutan', 'asc')->get();
        $texts = Carousel::where('jenis', 'text')->orderBy('urutan', 'asc')->get();

        return view('pengaturan.index', compact('multimedias', 'texts'));
    }

    public function tambah()
    {
        return view('pengaturan.tambah');
    }

    public function simpan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jenis' => 'required',
            'isi' => 'required'
        ], [
            'jenis.required' => 'Harus memilih jenis kontennya',
            'isi' => 'Isi konten harus diisi'
        ]);
        if ($validator->fails()) {
            return CommonResponse::badRequest($validator->errors()->toArray());
        }

        $data = $validator->validated();

        $path = null;

        if ($data['jenis'] == 'video-mp4') {
            $path = Storage::putFile('public', $data['isi']);
        } else if ($data['jenis'] == 'video-youtube') {
            $path = $data['isi'];
        }

        $latest = Carousel::where('jenis', $data['jenis'])->orderBy('urutan', 'desc')->first();
        $urutan = $latest ? $latest->urutan : 1;

        $hasil = Carousel::create([
            'jenis' => $data['jenis'],
            'isi' => $path,
            'urutan' => $urutan + 1
        ]);

        return CommonResponse::created($hasil->toArray());
    }

    public function hapus($id)
    {
        $target = Carousel::find($id);
        if (!$target) return back();
        $target->delete();
        if ($target->jenis == 'video-mp4') {
            Storage::delete($target->isi);
        }

        return back();
    }

    public function ubahUrutan(Request $request, $id)
    {
        $target = Carousel::find($id);
        if (!$target) return back();
        $direction = request('dir');
        $jenis = request('jenis');

        if ($direction == 'down') {
            $after = Carousel::where('urutan', '>', $target->urutan)->where('jenis', $jenis)->orderBy('urutan', 'asc')->first();
            if ($after) {
                $tmpUrutan = $after->urutan;
                $after->urutan = $target->urutan;
                $target->urutan = $tmpUrutan;

                $after->save();
                $target->save();
            }
        } else {
            $before = Carousel::where('urutan', '<', $target->urutan)->where('jenis', $jenis)->orderBy('urutan', 'desc')->first();
            if ($before) {
                $tmpUrutan = $before->urutan;
                $before->urutan = $target->urutan;
                $target->urutan = $tmpUrutan;

                $before->save();
                $target->save();
            }
        }

        return back();
    }

    public function edit($id)
    {
        $carousel = Carousel::findOrFail($id);
        return view('pengaturan.edit', compact('carousel'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'jenis' => 'required',
            'isi' => 'nullable'
        ], [
            'jenis.required' => 'Harus memilih jenis kontennya',
        ]);

        if ($validator->fails()) {
            return CommonResponse::badRequest($validator->errors()->toArray());
        }

        $carousel = Carousel::findOrFail($id);
        $data = $validator->validated();

        if ($carousel->jenis == 'video-mp4' && $data['jenis'] == 'video-youtube') {
            if ($carousel->isi) {
                Storage::delete($carousel->isi);
            }
        }

        if ($data['jenis'] == 'video-mp4' && $request->hasFile('isi')) {
            if ($carousel->isi) {
                Storage::delete($carousel->isi);
            }
            $path = Storage::putFile('public', $request->file('isi'));
        } else if ($data['jenis'] == 'video-youtube') {
            $path = $data['isi'];
        } else {
            $path = $carousel->isi;
        }

        $carousel->update([
            'jenis' => $data['jenis'],
            'isi' => $path
        ]);

        return CommonResponse::ok($carousel->toArray());
    }

    public function tambahTeks(Request $request) {
        $validator = Validator::make($request->all(), [
            'isi' => 'required'
        ], [
            'isi.required' => 'Isi harus diisi'
        ]);

        if ($validator->fails()) {
            return CommonResponse::badRequest($validator->errors()->toArray());
        }

        $data = $validator->validated();

        $latest = Carousel::where('jenis', 'text')->orderBy('urutan', 'desc')->first();
        $urutan = $latest ? $latest->urutan : 1;

        Carousel::create([
            'jenis' => 'text',
            'isi' => $data['isi'],
            'urutan' => $urutan + 1
        ]);

        return back()->with('message', 'Data berhasil ditambahkan!');
    }

    public function ubahTeks(Request $request, $id) {
        $target = Carousel::find($id);
        if (!$target) return back();

        $validator = Validator::make($request->all(), [
            'isi' => 'required'
        ], [
            'isi.required' => 'Isi harus diisi'
        ]);

        if ($validator->fails()) {
            return CommonResponse::badRequest($validator->errors()->toArray());
        }

        $data = $validator->validated();

        $target->update([
            'jenis' => 'text',
            'isi' => $data['isi']
        ]);

        return back()->with('success', 'Data berhasil diubah!');
    }

    public function hapusTeks($id) {
        $target = Carousel::find($id);
        if (!$target) return back();
        $target->delete();

        return back()->with('success', 'Data berhasil dihapus!');
    }
}
