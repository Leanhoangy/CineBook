@include('partials.form-errors')

<div class="ad-card p-4" style="max-width: 720px">
    <div class="row g-3">
        <div class="col-md-6">
            <label for="name" class="form-label">Họ tên <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label for="phone" class="form-label">Số điện thoại</label>
            <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control @error('phone') is-invalid @enderror">
            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label for="role" class="form-label">Vai trò <span class="text-danger">*</span></label>
            <select id="role" name="role" class="form-select @error('role') is-invalid @enderror">
                @foreach (\App\Models\User::ROLES as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label for="password" class="form-label">Mật khẩu @unless ($user->exists)<span class="text-danger">*</span>@endunless</label>
            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
            @if ($user->exists)
                <div class="form-text">Để trống nếu không muốn đổi mật khẩu.</div>
            @endif
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label for="password_confirmation" class="form-label">Nhập lại mật khẩu</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password">
        </div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Lưu</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-light">Hủy</a>
    </div>
</div>
