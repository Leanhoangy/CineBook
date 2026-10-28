@extends('layouts.admin')

@section('title', 'Soát vé')
@section('subtitle', 'Quét mã QR trên vé của khách hoặc nhập mã vé')

@section('content')
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="ad-card p-3 p-lg-4 mb-4">
                <div id="qrReader" class="ad-qr-reader d-none"></div>
                <div id="qrPlaceholder" class="ad-qr-placeholder">
                    <i class="bi bi-qr-code-scan"></i>
                    <p class="mb-0 small text-muted">Bật camera rồi đưa mã QR trên vé (điện thoại hoặc vé in) vào khung hình.</p>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="button" class="btn btn-primary flex-grow-1" id="cameraToggle"><i class="bi bi-camera-video me-1"></i>Bật camera</button>
                </div>
                <div id="cameraError" class="small text-danger mt-2 d-none"></div>

                <hr class="my-4">

                <form id="codeForm" data-lookup-url="{{ route('admin.check-in.lookup') }}" autocomplete="off">
                    <label for="codeInput" class="form-label small text-muted">Hoặc nhập mã vé / dùng máy quét mã vạch</label>
                    <div class="input-group">
                        <input type="text" id="codeInput" class="form-control text-uppercase font-monospace" placeholder="VD: CBX7K2QP" maxlength="30" autofocus>
                        <button class="btn btn-dark"><i class="bi bi-search me-1"></i>Tra cứu</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div id="checkinResult" class="mb-4">
                <div class="ad-card ad-checkin-empty">
                    <i class="bi bi-ticket-perforated"></i>
                    <p class="mb-0">Thông tin vé sẽ hiện ở đây sau khi quét.</p>
                </div>
            </div>

            <div class="ad-card">
                <div class="ad-card-header">
                    <h2>Đã soát hôm nay</h2>
                    <span class="badge rounded-pill text-bg-success" id="checkinCount">{{ $todayCount }} vé</span>
                </div>
                <ul class="list-group list-group-flush" id="checkinList">
                    @forelse ($recent as $booking)
                        @include('admin.check-in._row')
                    @empty
                        <li class="list-group-item text-muted small" data-empty>Chưa có vé nào được soát hôm nay.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="{{ asset('js/check-in.js') }}?v={{ filemtime(public_path('js/check-in.js')) }}"></script>
@endpush
