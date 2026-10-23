@if ($errors->any())
    <div class="alert alert-danger d-flex gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div>
            <strong>Dữ liệu chưa hợp lệ, vui lòng kiểm tra lại:</strong>
            <ul class="mb-0 mt-1 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
