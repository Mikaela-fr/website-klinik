@extends('templates.pengaturan')

@section('title', 'Tambah Konten Multimedia')
@section('page_title', 'Tambah Konten Multimedia')

@push('head')
    <style>
        #progressContainer {
            width: 100%;
            background: #ccc;
            margin-top: 10px;
        }

        #progressBar {
            width: 0%;
            text-align: center;
            color: white;
            font-size: 0.8rem;
        }
    </style>
@endpush

@section('pengaturan_content')
    <form action="{{ route('pengaturan.simpan') }}" id="formTambah" method="POST" class="max-w-lg mx-auto"
        enctype="multipart/form-data">
        @csrf
        <div class="mb-3 hidden">
            <label for="jenis" class="block mb-2">Jenis</label>
            <select name="jenis" id="jenis" class="px-4 py-2 border rounded-md block w-full">
                <option value="video-mp4">Video mp4</option>
                <option value="video-youtube">Video Youtube</option>
            </select>
        </div>
        <div class="mb-3" id="content">
            <label for="isi" class="block mb-2">Konten</label>
        </div>

        <div id="progressContainer" class="hidden mb-4 rounded-full">
            <div id="progressBar" class="rounded-full bg-primary">0%</div>
        </div>

        <x-button attrs='id="btnSimpan"'>Simpan</x-button>
    </form>
@endsection

@push('scripts')
    <script>
        const videoInputElement = document.createElement('input');
        const videoInputElementPreview = document.createElement('video');
        videoInputElementPreview.classList.add('hidden');

        videoInputElementPreview.setAttribute('controls', 'true');
        videoInputElement.setAttribute('name', 'isi');
        videoInputElement.setAttribute('id', 'isi');
        videoInputElement.setAttribute('type', 'file');
        videoInputElement.setAttribute('accept', 'video/*');
        videoInputElement.setAttribute('class', 'px-4 py-2 border rounded-md block w-full')
        videoInputElement.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const videoUrl = URL.createObjectURL(file);
                videoInputElementPreview.src = videoUrl;
                videoInputElementPreview.classList.remove('hidden');
            } else {
                videoInputElementPreview.classList.add('hidden');
            }
        });

        window.addEventListener('DOMContentLoaded', function() {
            const contentContainer = document.querySelector('#content');
            const uploadProgress = document.querySelector('#progressContainer');
            const btnSimpan = document.querySelector('#btnSimpan');
            const formTambah = document.querySelector('#formTambah');

            contentContainer.appendChild(videoInputElement);
            contentContainer.appendChild(videoInputElementPreview);

            document.querySelector('#jenis').addEventListener('change', changeInput);
            formTambah.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);

                btnSimpan.setAttribute('disabled', true);
                uploadFile(formData, this.getAttribute('action'), uploadProgress, function() {
                    btnSimpan.removeAttribute('disabled');
                });
            });
        });

        function uploadFile(data, path, progressElement, onFinish) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', path, true);

            xhr.upload.onprogress = function(event) {
                if (event.lengthComputable) {
                    const percentCompleted = (event.loaded / event.total) * 100;
                    progressElement.classList.remove('hidden');
                    progressElement.querySelector('div').style.width = percentCompleted + "%";
                    progressElement.querySelector('div').textContent = percentCompleted.toFixed(2) + "%";
                }
            };

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    Swal.fire({
                        title: "Upload berhasil!",
                        icon: 'success'
                    }).then(() => {
                        window.location.href = "{{ route('pengaturan.index') }}"
                    });
                } else {
                    Swal.fire({
                        title: "Upload gagal",
                        icon: 'error'
                    });
                }
                progressElement.classList.add('hidden');
                if (onFinish) onFinish();
            }

            xhr.onerror = function() {
                Swal.fire({
                    title: "Upload gagal",
                    icon: 'error'
                });
            };

            xhr.send(data);
        }

        function changeInput(e) {
            const contentContainer = document.querySelector('#content');

            if (e.target.value == 'video-mp4') {
                contentContainer.innerHTML = `
<label for="isi" class="block mb-2">Konten</label>
                `;
                contentContainer.appendChild(videoInputElement);
                contentContainer.appendChild(videoInputElementPreview);
            } else {
                contentContainer.innerHTML = `
<label for="isi" class="block mb-2">Konten</label>
<input type="text" name="isi" id="isi" class="px-4 py-2 border rounded-md block w-full" placeholder="Tempel tautan video youtube disini">
                `;
            }
        }
    </script>
@endpush
