// Chọn ghế: tính tiền tức thì + cập nhật ghế đã đặt bằng AJAX mỗi 10 giây
(() => {
    const root = document.getElementById('seatPicker');
    if (!root) return;

    const maxSeats = Number(root.dataset.max);
    const statusUrl = root.dataset.statusUrl;
    const form = document.getElementById('bookingForm');
    const hiddenBox = document.getElementById('seatInputs');
    const tagBox = document.getElementById('selectedSeats');
    const totalEl = document.getElementById('totalPrice');
    const countEl = document.getElementById('seatCount');
    const submitBtn = document.getElementById('submitBooking');
    const liveEl = document.getElementById('liveStatus');
    const money = (n) => n.toLocaleString('vi-VN') + ' ₫';

    const selected = new Map(); // mã ghế => giá

    const render = () => {
        hiddenBox.innerHTML = '';
        tagBox.innerHTML = '';
        let total = 0;

        [...selected.keys()].sort((a, b) => a.localeCompare(b, 'en', { numeric: true })).forEach((code) => {
            total += selected.get(code);
            hiddenBox.insertAdjacentHTML('beforeend', `<input type="hidden" name="seats[]" value="${code}">`);
            tagBox.insertAdjacentHTML('beforeend', `<span class="badge rounded-pill text-bg-danger me-1 mb-1">${code}</span>`);
        });

        if (!selected.size) tagBox.innerHTML = '<span class="text-muted small">Chưa chọn ghế nào</span>';
        totalEl.textContent = money(total);
        countEl.textContent = selected.size;
        submitBtn.disabled = selected.size === 0;
    };

    root.addEventListener('click', (e) => {
        const seat = e.target.closest('.cb-seat[data-code]');
        if (!seat || seat.disabled) return;

        const code = seat.dataset.code;
        if (selected.has(code)) {
            selected.delete(code);
            seat.classList.remove('selected');
            seat.setAttribute('aria-pressed', 'false');
        } else {
            if (selected.size >= maxSeats) {
                alert(`Mỗi lần chỉ được đặt tối đa ${maxSeats} ghế.`);
                return;
            }
            selected.set(code, Number(seat.dataset.price));
            seat.classList.add('selected');
            seat.setAttribute('aria-pressed', 'true');
        }
        render();
    });

    // Khôi phục ghế đã chọn khi form bị trả về do lỗi validate
    (root.dataset.old ? JSON.parse(root.dataset.old) : []).forEach((code) => {
        const seat = root.querySelector(`.cb-seat[data-code="${code}"]`);
        if (seat && !seat.disabled) seat.click();
    });

    const refresh = async () => {
        try {
            const res = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            const booked = new Set(data.booked);
            let lost = [];

            root.querySelectorAll('.cb-seat[data-code]').forEach((seat) => {
                const code = seat.dataset.code;
                const isBooked = booked.has(code);
                if (isBooked && !seat.disabled) {
                    if (selected.has(code)) {
                        selected.delete(code);
                        lost.push(code);
                    }
                    seat.classList.remove('selected');
                    seat.classList.add('booked', 'just-taken');
                    seat.disabled = true;
                } else if (!isBooked && seat.disabled) {
                    seat.classList.remove('booked', 'just-taken');
                    seat.disabled = false;
                }
            });

            if (lost.length) {
                render();
                alert(`Rất tiếc, ghế ${lost.join(', ')} vừa được người khác đặt. Vui lòng chọn ghế khác.`);
            }
            liveEl.textContent = `Cập nhật lúc ${data.updated_at}`;
        } catch (err) {
            liveEl.textContent = 'Mất kết nối, đang thử lại...';
        }
    };

    setInterval(refresh, 10000);

    form.addEventListener('submit', () => {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';
    });

    render();
})();
