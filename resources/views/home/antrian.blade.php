@extends('templates.base')
@section('title', 'Antrian Pemeriksaan')

@push('head')
    <style>
        .active-patient>div.cell {
            background: #16a34a !important;
            color: #fde047 !important;
        }
    </style>
@endpush

@section('body')
    <div class="pt-4 pr-4 m-0 bg-[url('/images/background.jpg')] bg-cover bg-center min-h-screen">
        <header class="bg-purple-400 flex rounded-br-3xl">
            <div class="flex p-2 gap-4 ml-auto items-center">
                <img src="{{ asset('images/logo.png') }}" alt="" class="w-20 h-20">
                <div class="text-white text-center">
                    <h1 class="text-6xl font-bold">RUMAH SUNAT NURYANA HUSADA</h1>
                    <p class="text-2xl font-bold">KEBUMEN - BATURRADEN</p>
                </div>
            </div>
            <div class="ml-auto flex font-bold items-center gap-4 bg-sky-400 text-white p-4 rounded-tl-3xl rounded-br-3xl">
                @php
                    $date = \Carbon\Carbon::now();

                    $d = $date->translatedFormat('d F Y');
                    $day = $date->translatedFormat('l');

                    $time = $date->format('H:i');
                @endphp
                <div>
                    <p class="text-4xl uppercase">{{ $day }}</p>
                    <p class="text-2xl text-yellow-300">{{ $d }}</p>
                </div>
                <div class="text-4xl" id="dynamicTime">
                    {{ $time }}
                </div>
            </div>
        </header>
        <main class="flex items-center justify-center min-h-[80vh] gap-4">
            <div class="min-h-[70vh] w-1/4 ml-4">
                <h2
                    class="text-5xl font-bold uppercase text-center p-2 bg-gradient-to-r from-blue-200 to-blue-500 rounded-full mb-8">
                    Antrian</h2>
                <div class="overflow-hidden text-4xl">
                    <div class="w-full gap-4" id="patientList">
                        @if ($pasienSekarang)
                            <div class="font-bold text-green-600 flex gap-4 items-center mb-4 active-patient" id="RM{{ $pasienSekarang->kode }}">
                                <div class="cell p-3 text-center rounded-full">
                                    <span>{{ $pasienSekarang->no_antrian }}</span>
                                </div>
                                <div class="cell p-3 text-center rounded-full w-full">
                                    <span>{{ $pasienSekarang->pasien->nama_pasien }}</span>
                                </div>
                            </div>
                        @endif
                        @foreach ($pasienMenunggu as $item)
                            <div class="font-bold text-green-600 flex gap-4 items-center mb-4" id="RM{{ $item->kode }}">
                                <div class="cell p-3 text-center bg-yellow-300 rounded-full">
                                    <span>{{ $item->no_antrian }}</span>
                                </div>
                                <div class="cell w-full p-3 bg-yellow-300 rounded-full text-center">
                                    <span>{{ $item->pasien->nama_pasien }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div id="videoContainer" class="relative w-9/12 min-h-[70vh]">
                @foreach ($multimedia as $item)
                    <video src="{{ asset(\Storage::url($item->isi)) }}"
                        class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-full"
                        style="z-index: {{ $loop->index == 0 ? 1 : -1 }}"></video>
                @endforeach
            </div>
        </main>
        <footer class="relative p-4 flex items-center bg-gradient-to-r from-blue-400 to-green-400 text-white">
            <div class="overflow-hidden whitespace-nowrap">
                <span class="marquee-text inline-block animate-marquee min-w-[100vw] text-4xl">
                    {{ $teksPanjangGabung }}
                </span>
            </div>
        </footer>
    </div>
@endsection

@push('scripts')
    <script>
        const dynamicTime = document.querySelector('#dynamicTime');

        function updateTime() {
            const currentDate = new Date();
            const hours = currentDate.getHours().toString();
            const minutes = currentDate.getMinutes().toString();

            dynamicTime.innerText = hours.padStart(2, "0") + ":" + minutes.padStart(2, "0");

            setTimeout(updateTime, 1000);
        }

        updateTime();
    </script>

    <script>
        let announcementAudio;

        window.addEventListener('DOMContentLoaded', function() {
            announcementAudio = new Audio('{{ asset('sound/announcement.mp3') }}');
            announcementAudio.load();

            const patientList = document.querySelector('#patientList');

            const addNewPatient = function(data, before) {
                const content = `
<div class="font-bold text-green-600 flex gap-4 items-center mb-4" id="RM${data.rekam_medis.kode}">
    <div class="cell p-3 text-center bg-yellow-300 rounded-full">
        <span>${data.rekam_medis.no_antrian}</span>
    </div>
    <div class="cell w-full p-3 bg-yellow-300 rounded-full text-center">
        <span>${data.rekam_medis.pasien.nama_pasien}</span>
    </div>
</div>
`;
                if (before) {
                    patientList.innerHTML = content + patientList.innerHTML;
                    return;
                }
                patientList.innerHTML += content;
            }

            window.Echo.channel('antrian')
                .listen('.update', (e) => {
                    const data = JSON.parse(e.message);
                    console.log(data);
                    if (data.type == 'status') {
                        playAnnouncementAndCall(data.voice);

                        const patientElement = document.querySelector('#RM' + data.rekam_medis.kode);

                        if (patientElement) {
                            patientElement.classList.add('animate-blink');
                            patientElement.classList.add('active-patient');
                        } else {
                            addNewPatient(data, true);
                            document.querySelector('#RM' + data.rekam_medis.kode).classList.add(
                                'animate-blink');
                            document.querySelector('#RM' + data.rekam_medis.kode).classList.add(
                                'active-patient');
                        }

                        setTimeout(function() {
                            document.querySelector('.active-patient').classList.remove('animate-blink');
                        }, 5000);

                        const currentActivePatient = document.querySelector('.active-patient');
                        if (currentActivePatient) {
                            if (currentActivePatient.getAttribute('id') == 'RM' + data.rekam_medis.kode) return;
                            currentActivePatient.remove();
                        }
                    } else if (data.type == 'insert') {
                        addNewPatient(data);
                    } else if (data.type == 'delete') {
                        document.querySelector('#RM' + data.rekam_medis.kode).remove();
                    }
                });
        });

        function playAnnouncementAndCall(text) {
            if (announcementAudio) {
                announcementAudio.currentTime = 0;
                announcementAudio.volume = 0.8;
                announcementAudio.play().then(() => {
                    announcementAudio.onended = () => {
                        callPatient(text);
                    };
                }).catch(error => {
                    console.error('Error playing announcement sound:', error);
                    callPatient(text);
                });
            } else {
                callPatient(text);
            }
        }

        function callPatient(text) {
            if ('speechSynthesis' in window) {
                var speech = new SpeechSynthesisUtterance(text);
                speech.lang = 'id-ID';
                window.speechSynthesis.speak(speech);
            }
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const marqueeText = document.querySelector('.marquee-text');
            const container = marqueeText.parentElement;

            const textWidth = marqueeText.offsetWidth;
            const containerWidth = container.offsetWidth;

            const baseSpeed = 15;
            const duration = (textWidth / containerWidth) * baseSpeed;

            marqueeText.style.animationDuration = `${duration}s`;

            marqueeText.classList.add('animate-marquee');
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const videoList = document.querySelector('#videoContainer');
            const VIDEO_VOLUME = 0.5;
            let currentVideo = null;

            const playNext = function() {
                let nextVideo = currentVideo.nextElementSibling;

                if (!nextVideo) {
                    nextVideo = videoList.children[0];
                }

                nextVideo.classList.add('animate-fade-in');
                currentVideo.classList.add('animate-fade-out');

                nextVideo.style.zIndex = '1';
                nextVideo.volume = VIDEO_VOLUME;
                nextVideo.play();

                setTimeout(function() {
                    nextVideo.classList.remove('animate-fade-in');
                    currentVideo.classList.remove('animate-fade-out');
                    currentVideo.style.zIndex = '-1';

                    currentVideo = nextVideo;
                }, 1000);
                nextVideo.addEventListener('ended', playNext);
            };

            document.addEventListener('click', function(event) {
                if (!document.webkitIsFullScreen) {
                    document.documentElement.requestFullscreen();
                }

                if (!currentVideo) {
                    currentVideo = videoList.children[0];
                    currentVideo.volume = VIDEO_VOLUME;
                    currentVideo.play();

                    currentVideo.addEventListener('ended', playNext);
                } else {
                    if (currentVideo.paused) {
                        currentVideo.play();
                    } else {
                        currentVideo.pause();
                    }
                }
            });
        });
    </script>
@endpush
