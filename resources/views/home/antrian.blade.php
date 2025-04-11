@extends('templates.base')

@section('body')
    <main class="grid grid-cols-12 grid-rows-5 h-screen overflow-hidden bg-[#000]">
        <div class="relative col-span-8 row-span-4 p-4 bg-[#4F959D]" id="videoContainer">
            @foreach ($multimedia as $item)
                <video src="{{ asset(\Storage::url($item->isi)) }}" class="absolute top-0 left-0 w-full"
                    style="z-index: {{ $loop->index == 0 ? 1 : -1 }}"></video>
            @endforeach
        </div>
        <div class="col-span-4 row-span-5 p-4 bg-primary-light text-white">
            <h2 class="p-4 text-2xl font-semibold text-center">Antrian</h2>
            <div id="patientList" class="overflow-hidden text-lg">
                @foreach ($pasienMenunggu as $item)
                    <div class="flex gap-2 p-2" id="RM{{ $item->kode }}">
                        <span>{{ $item->no_antrian }}</span>
                        <span>{{ $item->pasien->nama_pasien }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="relative col-span-8 p-4 flex items-center bg-[#4F959D] text-white">
            <div class="overflow-hidden whitespace-nowrap">
                <span class="marquee-text inline-block animate-marquee min-w-full text-4xl">
                    {{ $teksPanjangGabung }}
                </span>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        let announcementAudio;

        window.addEventListener('DOMContentLoaded', function() {
            announcementAudio = new Audio('{{ asset('sound/announcement.mp3') }}');
            announcementAudio.load();

            const patientList = document.querySelector('#patientList');

            const addNewPatient = function(data, before) {
                const content = `
<div class="flex gap-2 p-2" id="RM${data.rekam_medis.kode}">
    <span>${data.rekam_medis.no_antrian}</span>
    <span>${data.rekam_medis.pasien.nama_pasien}</span>
</div>`;
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
                        const existingBlinking = document.querySelector('.animate-blink');
                        if (existingBlinking) {
                            if (existingBlinking.getAttribute('id') == 'RM' + data.rekam_medis.kode) return;
                            existingBlinking.remove();
                        }
                        const patientElement = document.querySelector('#RM' + data.rekam_medis.kode);
                        if (patientElement) {
                            patientElement.classList.add('animate-blink');
                        } else {
                            addNewPatient(data, true);
                            document.querySelector('#RM' + data.rekam_medis.kode).classList.add(
                                'animate-blink');
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
