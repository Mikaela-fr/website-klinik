@extends('templates.pengaturan')

@section('title', 'Kelola Konten Antrian')
@section('page_title', 'Pengaturan')

@section('pengaturan_content')
    <section class="mb-8">
        <h2 class="text-primary text-2xl font-semibold mb-2">Kelola Konten Multimedia</h2>
        <p class="text-slate-400 mb-3">Edit, tambah, hapus, atau urutkan video-video atau gambar-gambar yang ditampilkan
            disamping layar antrian.</p>

        <x-button type="a" href="{{ route('pengaturan.tambah') }}" class="mb-5">Tambah Konten</x-button>

        <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($multimedias as $item)
                <div class="border rounded-md p-4 bg-white">
                    <div class="mb-4">
                        @if ($item->jenis == 'video-mp4')
                            <video src="{{ asset(\Storage::url($item->isi)) }}" controls></video>
                        @endif
                    </div>
                    <x-button tint="bg-success text-white hover:bg-success-dark" type="a"
                        href="{{ route('pengaturan.edit', $item->id) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-pencil-fill" viewBox="0 0 16 16">
                            <path
                                d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.5.5 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11z" />
                        </svg>
                    </x-button>
                    <form action="{{ route('pengaturan.hapus', $item->id) }}" method="POST" class="inline"
                        onsubmit="deleteMultimedia(event)">
                        @csrf
                        @method('DELETE')
                        <x-button tint="bg-danger text-white hover:bg-danger-dark">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-trash-fill" viewBox="0 0 16 16">
                                <path
                                    d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0" />
                            </svg>
                        </x-button>
                    </form>
                    <x-button type="a"
                        href="{{ route('pengaturan.ubah-urutan', ['id' => $item->id, 'dir' => 'up', 'jenis' => $item->jenis]) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-caret-up-fill" viewBox="0 0 16 16">
                            <path
                                d="m7.247 4.86-4.796 5.481c-.566.647-.106 1.659.753 1.659h9.592a1 1 0 0 0 .753-1.659l-4.796-5.48a1 1 0 0 0-1.506 0z" />
                        </svg>
                    </x-button>
                    <x-button type="a"
                        href="{{ route('pengaturan.ubah-urutan', ['id' => $item->id, 'dir' => 'down', 'jenis' => $item->jenis]) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-caret-down-fill" viewBox="0 0 16 16">
                            <path
                                d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z" />
                        </svg>
                    </x-button>
                </div>
            @endforeach
        </div>
    </section>
    <section class="mb-8">
        <h2 class="text-primary text-2xl font-semibold mb-2">Kelola Konten Teks</h2>
        <p class="text-slate-400 mb-3">Edit, tambah, hapus, atau urutkan <em>running text</em> yang akan tampil di bawah
            layar antrian.</p>
        <div class="grid grid-cols-1">
            @foreach ($texts as $item)
                <form class="flex gap-2 mb-2" action="{{ route('pengaturan.ubah-teks', $item->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="text" name="isi" id="isi"
                        required
                        value="{{ $item->isi }}"
                        class="px-4 py-2 border flex-1 rounded-md block w-full">
                    <x-button tint="bg-success text-white hover:bg-success-dark">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-floppy-fill" viewBox="0 0 16 16">
                            <path
                                d="M0 1.5A1.5 1.5 0 0 1 1.5 0H3v5.5A1.5 1.5 0 0 0 4.5 7h7A1.5 1.5 0 0 0 13 5.5V0h.086a1.5 1.5 0 0 1 1.06.44l1.415 1.414A1.5 1.5 0 0 1 16 2.914V14.5a1.5 1.5 0 0 1-1.5 1.5H14v-5.5A1.5 1.5 0 0 0 12.5 9h-9A1.5 1.5 0 0 0 2 10.5V16h-.5A1.5 1.5 0 0 1 0 14.5z" />
                            <path
                                d="M3 16h10v-5.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5zm9-16H4v5.5a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 .5-.5zM9 1h2v4H9z" />
                        </svg>
                    </x-button>
                    <x-button type="a"
                        href="{{ route('pengaturan.ubah-urutan', ['id' => $item->id, 'dir' => 'up', 'jenis' => $item->jenis]) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-caret-up-fill" viewBox="0 0 16 16">
                            <path
                                d="m7.247 4.86-4.796 5.481c-.566.647-.106 1.659.753 1.659h9.592a1 1 0 0 0 .753-1.659l-4.796-5.48a1 1 0 0 0-1.506 0z" />
                        </svg>
                    </x-button>
                    <x-button type="a"
                        href="{{ route('pengaturan.ubah-urutan', ['id' => $item->id, 'dir' => 'down', 'jenis' => $item->jenis]) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-caret-down-fill" viewBox="0 0 16 16">
                            <path
                                d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z" />
                        </svg>
                    </x-button>
                    <x-button type="a" href="{{ route('pengaturan.hapus-teks', $item->id) }}"
                        attrs='onclick="deleteText(event)"' tint="bg-danger text-white hover:bg-danger-dark">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-trash-fill" viewBox="0 0 16 16">
                            <path
                                d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0" />
                        </svg>
                    </x-button>
                </form>
            @endforeach
            <form action="{{ route('pengaturan.tambah-teks') }}" method="post" class="flex gap-2">
                @csrf
                <input required type="text" name="isi" id="isi"
                    class="px-4 py-2 border flex-1 rounded-md block w-full" placeholder="Tambah teks baru disini">
                <x-button>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-floppy-fill" viewBox="0 0 16 16">
                        <path
                            d="M0 1.5A1.5 1.5 0 0 1 1.5 0H3v5.5A1.5 1.5 0 0 0 4.5 7h7A1.5 1.5 0 0 0 13 5.5V0h.086a1.5 1.5 0 0 1 1.06.44l1.415 1.414A1.5 1.5 0 0 1 16 2.914V14.5a1.5 1.5 0 0 1-1.5 1.5H14v-5.5A1.5 1.5 0 0 0 12.5 9h-9A1.5 1.5 0 0 0 2 10.5V16h-.5A1.5 1.5 0 0 1 0 14.5z" />
                        <path
                            d="M3 16h10v-5.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5zm9-16H4v5.5a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 .5-.5zM9 1h2v4H9z" />
                    </svg>
                </x-button>
            </form>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        function deleteMultimedia(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Yakin untuk menghapus?',
                text: 'Tindakan ini tidak akan dapat dibatalkan',
                showCancelButton: true,
                confirmButtonColor: '#BE3144'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        }

        function deleteText(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Yakin untuk menghapus?',
                text: 'Tindakan ini tidak akan dapat dibatalkan',
                showCancelButton: true,
                confirmButtonColor: '#BE3144'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = event.target.href;
                }
            });
        }
    </script>
@endpush
