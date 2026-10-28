// CineBook – script dùng chung
document.addEventListener('DOMContentLoaded', () => {
    // Hiện toast thông báo (flash message)
    document.querySelectorAll('.toast').forEach((el) => bootstrap.Toast.getOrCreateInstance(el).show());

    // Thanh lọc cuộn ngang <div data-scroll-active>: cuộn mục đang chọn (.active) ra giữa để người dùng biết mình đang ở mục nào
    document.querySelectorAll('[data-scroll-active]').forEach((bar) => {
        const active = bar.querySelector('.active');
        if (!active) return;
        const barRect = bar.getBoundingClientRect();
        const activeRect = active.getBoundingClientRect();
        bar.scrollLeft += activeRect.left - barRect.left - (barRect.width - activeRect.width) / 2;
    });

    // Nút sao chép mã giảm giá: <button data-copy="MA">
    document.querySelectorAll('[data-copy]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const label = btn.querySelector('span');
            try {
                await navigator.clipboard.writeText(btn.dataset.copy);
                label.textContent = 'Đã sao chép';
            } catch {
                label.textContent = btn.dataset.copy; // trình duyệt chặn clipboard: hiện mã để khách tự chép
            }
            setTimeout(() => (label.textContent = 'Sao chép'), 2000);
        });
    });

    // Mở hộp thoại theo địa chỉ, VD: /ve-cua-toi/CB123456#qr mở ngay mã QR phóng to
    const hashModal = location.hash.length > 1 ? document.getElementById(decodeURIComponent(location.hash.slice(1))) : null;
    if (hashModal?.classList.contains('modal')) bootstrap.Modal.getOrCreateInstance(hashModal).show();

    // Hộp xác nhận trước khi xóa/hủy: <form data-confirm="Nội dung câu hỏi">
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!confirm(form.dataset.confirm)) e.preventDefault();
        });
    });

    // Bấm vào cả dòng trong bảng để mở chi tiết: <tr data-href="...">
    document.querySelectorAll('tr[data-href]').forEach((row) => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('a, button, input, select, label, form') || getSelection().toString()) return;
            if (e.ctrlKey || e.metaKey) window.open(row.dataset.href, '_blank');
            else location.href = row.dataset.href;
        });
    });

    // Xem trước ảnh trước khi upload: <input type="file" data-preview="#idAnh">
    document.querySelectorAll('input[type=file][data-preview]').forEach((input) => {
        input.addEventListener('change', () => {
            const target = document.querySelector(input.dataset.preview);
            const file = input.files[0];
            if (target && file) {
                target.src = URL.createObjectURL(file);
                target.classList.remove('d-none');
                document.querySelectorAll(input.dataset.hide || '').forEach((el) => el.classList.add('d-none'));
            }
        });
    });
});
