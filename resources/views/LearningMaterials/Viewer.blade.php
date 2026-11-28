@extends('Layouts.LayoutApp')

@section('title', $material->title)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge {{ $material->isVideo() ? 'bg-info text-dark' : 'bg-danger' }}">{{ strtoupper($material->material_type) }}</span>
            <span class="text-secondary small">{{ $material->category?->name ?? 'General' }}</span>
            @if ($material->course_subject_label)
                <span class="text-primary small">&bull; {{ $material->course_subject_label }}</span>
            @endif
        </div>
        <h3 class="mb-0">{{ $material->title }}</h3>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('learning-materials.library') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Library
        </a>
        @if ($canDownload)
            <a href="{{ route('learning-materials.download', $material) }}" class="btn btn-success">
                <i class="bi bi-download me-1"></i>Download
            </a>
        @endif
    </div>
</div>

@if ($material->isVideo())
    <div class="glass-card p-3 mb-4">
        @if ($progress && $progress->last_position > 5 && ! $progress->completed)
            <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3" id="resumePrompt">
                <span><i class="bi bi-clock-history me-1"></i>You previously watched up to <strong>{{ gmdate('H:i:s', $progress->last_position) }}</strong>.</span>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-primary" id="resumeBtn">Resume from {{ gmdate('i:s', $progress->last_position) }}</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="startOverBtn">Start Over</button>
                </div>
            </div>
        @endif

        <div id="protectedViewerContainer" class="position-relative bg-black rounded overflow-hidden @if (! $canDownload) user-select-none @endif" @if (! $canDownload) oncontextmenu="return false;" @endif>
            <div id="videoLoading" class="position-absolute top-50 start-50 translate-middle text-light d-none" style="z-index: 5;">
                <div class="spinner-border text-info" role="status"></div>
            </div>
            @if (! $canDownload)
                <div id="captureShield" class="position-absolute top-0 start-0 w-100 h-100 bg-black d-none flex-column align-items-center justify-content-center text-light text-center p-3" style="z-index: 20;">
                    <i class="bi bi-shield-lock-fill fs-1 text-warning mb-2"></i>
                    <div class="fw-semibold">Protected Video Content</div>
                    <div class="small text-secondary">Return focus to this window to continue watching.</div>
                </div>
                <div class="position-absolute top-0 end-0 m-2 px-2 py-1 rounded bg-dark bg-opacity-50 text-light small pointer-events-none" style="z-index: 10; font-size: 0.72rem; opacity: 0.65; pointer-events: none;">
                    {{ auth()->user()->name }} &bull; {{ auth()->user()->email }}
                </div>
            @endif
            <video id="lmsVideoPlayer"
                   class="w-100 d-block"
                   style="max-height: 70vh;"
                   @if ($canDownload) controls @endif
                   preload="metadata"
                   playsinline
                   @if (! $canDownload) controlsList="nodownload noplaybackrate" disablePictureInPicture oncontextmenu="return false;" @endif
                   @if ($material->thumbnail_path) poster="{{ route('learning-materials.thumbnail', $material) }}" @endif>
                <source src="{{ route('learning-materials.stream', $material) }}" type="{{ $material->mime_type ?: 'video/mp4' }}">
                Your browser does not support HTML5 video playback.
            </video>
        </div>

        @if (! $canDownload)
            {{-- Custom Video Controls (no browser download / PiP menu) --}}
            <div class="d-flex flex-wrap align-items-center gap-2 mt-2 p-2 rounded bg-body-tertiary border" id="customVideoControls">
                <button type="button" class="btn btn-sm btn-primary" id="customPlayBtn" title="Play / Pause">
                    <i class="bi bi-play-fill" id="customPlayIcon"></i>
                </button>
                <span class="small text-secondary" id="customTimeLabel" style="min-width: 92px;">00:00 / 00:00</span>
                <input type="range" class="form-range flex-grow-1" id="customSeekBar" min="0" max="100" value="0" step="0.1" style="min-width: 120px;">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="customMuteBtn" title="Mute / Unmute">
                    <i class="bi bi-volume-up-fill" id="customMuteIcon"></i>
                </button>
                <input type="range" class="form-range" id="customVolumeBar" min="0" max="1" step="0.05" value="1" style="width: 75px;">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="customFullscreenBtn" title="Fullscreen">
                    <i class="bi bi-arrows-fullscreen"></i>
                </button>
            </div>
        @endif

        <div id="videoError" class="alert alert-danger mt-3 d-none">
            Unable to load video stream. Please refresh or check your connection.
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <div class="d-flex align-items-center gap-2">
                <span class="small text-secondary">Speed:</span>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2"
                            type="button"
                            id="playbackSpeedBtn"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                        <i class="bi bi-speedometer2"></i>
                        <span id="playbackSpeedLabel">1.0x</span>
                    </button>
                    <ul class="dropdown-menu shadow" aria-labelledby="playbackSpeedBtn">
                        @foreach (['0.5' => '0.5x', '0.75' => '0.75x', '1' => '1.0x (Normal)', '1.25' => '1.25x', '1.5' => '1.5x', '2' => '2.0x'] as $rateVal => $rateText)
                            <li>
                                <button type="button"
                                        class="dropdown-item speed-option {{ $rateVal === '1' ? 'active' : '' }}"
                                        data-rate="{{ $rateVal }}">
                                    {{ $rateText }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="small text-secondary" id="progressStatus">
                @if ($progress?->completed)
                    <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Completed</span>
                @elseif ($progress)
                    Last saved: {{ gmdate('i:s', $progress->last_position) }}
                @endif
            </div>
        </div>
    </div>
@else
    {{-- Responsive PDF Viewer --}}
    <div class="glass-card p-3 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="pdfPrevBtn"><i class="bi bi-chevron-left"></i> Prev</button>
                <span class="small">Page <strong id="pdfPageNum">1</strong> / <strong id="pdfPageCount">-</strong></span>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="pdfNextBtn">Next <i class="bi bi-chevron-right"></i></button>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="pdfZoomOutBtn"><i class="bi bi-zoom-out"></i></button>
                <span class="small" id="pdfZoomLabel">120%</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="pdfZoomInBtn"><i class="bi bi-zoom-in"></i></button>
            </div>
        </div>

        <div id="pdfCanvasContainer" class="bg-dark rounded p-2 text-center overflow-auto" style="max-height: 76vh;">
            <div id="pdfLoading" class="py-5 text-secondary">Loading PDF document...</div>
            <canvas id="pdfCanvas" class="d-none mx-auto shadow"></canvas>
            <iframe id="pdfFallbackFrame"
                    src="{{ route('learning-materials.stream', $material) }}{{ $canDownload ? '' : '#toolbar=0&navpanes=0' }}"
                    class="w-100 d-none rounded border-0"
                    style="height: 72vh;"
                    title="{{ $material->title }}"></iframe>
        </div>
    </div>
@endif

@if ($material->description)
    <div class="glass-card p-3">
        <h6 class="mb-2">Description</h6>
        <p class="text-secondary mb-0">{{ $material->description }}</p>
    </div>
@endif

@if ($material->isVideo())
<script>
document.addEventListener('DOMContentLoaded', () => {
    const video = document.getElementById('lmsVideoPlayer');
    const speedLabel = document.getElementById('playbackSpeedLabel');
    const speedOptions = document.querySelectorAll('.speed-option');
    let currentRate = 1;
    const loading = document.getElementById('videoLoading');
    const errorBox = document.getElementById('videoError');
    const progressStatus = document.getElementById('progressStatus');
    const resumeBtn = document.getElementById('resumeBtn');
    const startOverBtn = document.getElementById('startOverBtn');
    const resumePrompt = document.getElementById('resumePrompt');
    const savedPos = {{ (int) ($progress?->last_position ?? 0) }};

    if (resumeBtn) {
        resumeBtn.addEventListener('click', () => {
            video.currentTime = savedPos;
            video.play();
            resumePrompt?.remove();
        });
    }
    if (startOverBtn) {
        startOverBtn.addEventListener('click', () => {
            video.currentTime = 0;
            video.play();
            resumePrompt?.remove();
        });
    }

    const applyPlaybackSpeed = () => {
        if (!Number.isNaN(currentRate) && video.playbackRate !== currentRate) {
            video.playbackRate = currentRate;
        }
    };
    speedOptions.forEach(btn => {
        btn.addEventListener('click', () => {
            currentRate = parseFloat(btn.dataset.rate || '1');
            speedLabel.textContent = currentRate + 'x';
            speedOptions.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            applyPlaybackSpeed();
        });
    });
    video.addEventListener('loadedmetadata', applyPlaybackSpeed);
    video.addEventListener('play', applyPlaybackSpeed);

    video.addEventListener('waiting', () => loading.classList.remove('d-none'));
    video.addEventListener('playing', () => loading.classList.add('d-none'));
    video.addEventListener('canplay', () => loading.classList.add('d-none'));
    video.addEventListener('error', () => {
        loading.classList.add('d-none');
        errorBox.classList.remove('d-none');
    });

    let lastSent = 0;
    const saveProgress = (completed = false) => {
        const position = Math.floor(video.currentTime || 0);
        const duration = Math.floor(video.duration || 0);
        if (!completed && Math.abs(position - lastSent) < 5) return;
        lastSent = position;

        fetch("{{ route('learning-materials.progress', $material) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ last_position: position, duration, completed })
        }).then(r => r.ok ? r.json() : null).then(data => {
            if (data && data.saved) {
                progressStatus.textContent = data.completed ? 'Completed' : 'Progress saved';
            }
        }).catch(() => {});
    };

    video.addEventListener('timeupdate', () => saveProgress(false));
    video.addEventListener('pause', () => saveProgress(false));
    video.addEventListener('ended', () => saveProgress(true));

    // Custom controls when download is restricted
    const playBtn = document.getElementById('customPlayBtn');
    const playIcon = document.getElementById('customPlayIcon');
    const seekBar = document.getElementById('customSeekBar');
    const timeLabel = document.getElementById('customTimeLabel');
    const muteBtn = document.getElementById('customMuteBtn');
    const muteIcon = document.getElementById('customMuteIcon');
    const volumeBar = document.getElementById('customVolumeBar');
    const fsBtn = document.getElementById('customFullscreenBtn');
    const viewerContainer = document.getElementById('protectedViewerContainer');

    const fmtTime = (secs) => {
        const s = Math.max(0, Math.floor(secs || 0));
        const m = Math.floor(s / 60);
        const r = s % 60;
        return String(m).padStart(2, '0') + ':' + String(r).padStart(2, '0');
    };

    if (playBtn) {
        const togglePlay = () => { video.paused ? video.play() : video.pause(); };
        playBtn.addEventListener('click', togglePlay);
        video.addEventListener('click', togglePlay);
        video.addEventListener('play', () => { playIcon.className = 'bi bi-pause-fill'; });
        video.addEventListener('pause', () => { playIcon.className = 'bi bi-play-fill'; });
        video.addEventListener('timeupdate', () => {
            if (video.duration > 0) {
                seekBar.value = (video.currentTime / video.duration) * 100;
            }
            timeLabel.textContent = fmtTime(video.currentTime) + ' / ' + fmtTime(video.duration);
        });
        video.addEventListener('loadedmetadata', () => {
            timeLabel.textContent = fmtTime(video.currentTime) + ' / ' + fmtTime(video.duration);
        });
        seekBar.addEventListener('input', () => {
            if (video.duration > 0) {
                video.currentTime = (parseFloat(seekBar.value) / 100) * video.duration;
            }
        });
        muteBtn.addEventListener('click', () => {
            video.muted = !video.muted;
            muteIcon.className = video.muted ? 'bi bi-volume-mute-fill' : 'bi bi-volume-up-fill';
        });
        volumeBar.addEventListener('input', () => {
            video.volume = parseFloat(volumeBar.value);
            video.muted = video.volume === 0;
            muteIcon.className = video.muted ? 'bi bi-volume-mute-fill' : 'bi bi-volume-up-fill';
        });
        fsBtn.addEventListener('click', () => {
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else if (viewerContainer?.requestFullscreen) {
                viewerContainer.requestFullscreen();
            }
        });
    }

    // Deterrent capture shield when download is restricted
    const shield = document.getElementById('captureShield');
    if (shield) {
        const showShield = (autoHideMs = 0) => {
            shield.classList.remove('d-none');
            shield.classList.add('d-flex');
            if (!video.paused) video.pause();
            if (autoHideMs > 0) {
                setTimeout(() => {
                    shield.classList.add('d-none');
                    shield.classList.remove('d-flex');
                }, autoHideMs);
            }
        };
        const hideShield = () => {
            if (!document.hidden && document.hasFocus()) {
                shield.classList.add('d-none');
                shield.classList.remove('d-flex');
            }
        };

        document.addEventListener('visibilitychange', () => {
            document.hidden ? showShield() : hideShield();
        });
        window.addEventListener('blur', () => showShield());
        window.addEventListener('focus', hideShield);
        document.addEventListener('keydown', (e) => {
            const key = (e.key || '').toLowerCase();
            if (
                e.key === 'PrintScreen' ||
                key === 'f12' ||
                ((e.ctrlKey || e.metaKey) && ['s', 'p', 'u'].includes(key)) ||
                ((e.ctrlKey || e.metaKey) && e.shiftKey && ['s', 'i', 'j', 'c'].includes(key))
            ) {
                e.preventDefault();
                if (navigator.clipboard?.writeText) {
                    navigator.clipboard.writeText('').catch(() => {});
                }
                showShield(2500);
            }
        });
    }
});
</script>
<style>
@media print {
    #protectedViewerContainer { display: none !important; }
}
</style>
@else
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js" crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const url = "{{ route('learning-materials.stream', $material) }}";
    const canvas = document.getElementById('pdfCanvas');
    const fallback = document.getElementById('pdfFallbackFrame');
    const loading = document.getElementById('pdfLoading');
    const pageNumEl = document.getElementById('pdfPageNum');
    const pageCountEl = document.getElementById('pdfPageCount');
    const zoomLabel = document.getElementById('pdfZoomLabel');

    if (typeof pdfjsLib === 'undefined') {
        loading.classList.add('d-none');
        fallback.classList.remove('d-none');
        return;
    }

    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    let pdfDoc = null, pageNum = 1, scale = 1.2, rendering = false;
    const renderPage = (num) => {
        rendering = true;
        pdfDoc.getPage(num).then((page) => {
            const viewport = page.getViewport({ scale });
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            return page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
        }).then(() => {
            rendering = false;
            pageNumEl.textContent = pageNum;
            zoomLabel.textContent = Math.round(scale * 100) + '%';
        });
    };

    pdfjsLib.getDocument({ url, withCredentials: true }).promise.then((doc) => {
        pdfDoc = doc;
        pageCountEl.textContent = doc.numPages;
        loading.classList.add('d-none');
        canvas.classList.remove('d-none');
        renderPage(pageNum);
    }).catch(() => {
        loading.classList.add('d-none');
        fallback.classList.remove('d-none');
    });

    document.getElementById('pdfPrevBtn').addEventListener('click', () => {
        if (!pdfDoc || rendering || pageNum <= 1) return;
        pageNum--;
        renderPage(pageNum);
    });
    document.getElementById('pdfNextBtn').addEventListener('click', () => {
        if (!pdfDoc || rendering || pageNum >= pdfDoc.numPages) return;
        pageNum++;
        renderPage(pageNum);
    });
    document.getElementById('pdfZoomInBtn').addEventListener('click', () => {
        if (!pdfDoc || rendering || scale >= 2.5) return;
        scale += 0.2;
        renderPage(pageNum);
    });
    document.getElementById('pdfZoomOutBtn').addEventListener('click', () => {
        if (!pdfDoc || rendering || scale <= 0.6) return;
        scale -= 0.2;
        renderPage(pageNum);
    });
});
</script>
@endif
@endsection
