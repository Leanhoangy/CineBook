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

    // Bắp nước: nút +/- đổi số lượng, cộng vào tổng tiền (mã giảm giá chỉ trừ vào tiền vé)
    const combos = [...document.querySelectorAll('.cb-combo')].map((el) => ({
        el, name: el.dataset.name, price: Number(el.dataset.price), input: el.querySelector('input'),
    }));
    combos.forEach((c) => c.el.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-step]');
        if (!btn) return;
        const qty = Math.min(Number(c.input.max), Math.max(0, Number(c.input.value) + Number(btn.dataset.step)));
        c.input.value = qty;
        render();
    }));

    // Mã giảm giá: server trả về điều kiện của mã, trình duyệt tự tính lại mỗi khi đổi ghế
    const voucherBox = document.getElementById('voucherBox');
    const voucherInput = document.getElementById('voucherInput');
    const voucherBtn = document.getElementById('voucherApply');
    const voucherMsg = document.getElementById('voucherMessage');
    let voucher = null;

    // Cùng công thức với Voucher::discountFor() bên PHP
    const discountFor = (v, subtotal) => {
        if (!v || subtotal < v.min_order) return 0;
        let discount = v.type === 'percent' ? Math.floor(subtotal * v.value / 100) : v.value;
        if (v.max_discount) discount = Math.min(discount, v.max_discount);
        return Math.min(discount, subtotal);
    };

    // Danh sách "Mã dành cho bạn": hiện mức giảm theo ghế đang chọn, đánh dấu mã tiết kiệm nhất
    const options = [...document.querySelectorAll('.cb-voucher-option')].map((el) => ({ el, data: JSON.parse(el.dataset.voucher) }));

    const renderOptions = (subtotal) => {
        const best = Math.max(0, ...options.map((o) => discountFor(o.data, subtotal)));
        options.forEach(({ el, data }) => {
            const hint = el.querySelector('.cb-voucher-hint');
            const discount = discountFor(data, subtotal);
            const short = subtotal < data.min_order;

            el.classList.toggle('active', voucher?.code === data.code);
            el.classList.toggle('is-locked', selected.size > 0 && short);
            if (!selected.size) {
                hint.innerHTML = data.min_order ? `Đơn từ ${money(data.min_order)}` : data.label;
            } else if (short) {
                hint.innerHTML = `Cần thêm ${money(data.min_order - subtotal)}`;
            } else {
                hint.innerHTML = `<strong class="text-success">-${money(discount)}</strong>`
                    + (discount === best && best > 0 ? ' <span class="badge text-bg-success">Tiết kiệm nhất</span>' : '');
            }
        });
    };

    const showVoucherMessage = (text, type) => {
        voucherMsg.textContent = text;
        voucherMsg.className = `small mt-1 text-${type}`;
    };

    const render = () => {
        hiddenBox.innerHTML = '';
        tagBox.innerHTML = '';
        let subtotal = 0;

        [...selected.keys()].sort((a, b) => a.localeCompare(b, 'en', { numeric: true })).forEach((code) => {
            subtotal += selected.get(code);
            hiddenBox.insertAdjacentHTML('beforeend', `<input type="hidden" name="seats[]" value="${code}">`);
            tagBox.insertAdjacentHTML('beforeend', `<span class="badge rounded-pill text-bg-danger me-1 mb-1">${code}</span>`);
        });

        const discount = discountFor(voucher, subtotal);
        renderOptions(subtotal);

        let comboTotal = 0;
        const comboLines = [];
        combos.forEach((c) => {
            const qty = Number(c.input.value);
            c.el.classList.toggle('active', qty > 0);
            if (qty > 0) {
                comboTotal += qty * c.price;
                comboLines.push(`${qty}× ${c.name}`);
            }
        });
        document.getElementById('comboRow')?.classList.toggle('d-none', !comboTotal);
        if (comboTotal) {
            document.getElementById('comboPrice').textContent = money(comboTotal);
            document.getElementById('comboList').textContent = comboLines.join(', ');
        }

        document.getElementById('subtotalRow').classList.toggle('d-none', !voucher && !comboTotal);
        document.getElementById('discountRow').classList.toggle('d-none', !voucher);
        document.getElementById('subtotalPrice').textContent = money(subtotal);
        document.getElementById('discountPrice').textContent = '-' + money(discount);

        if (voucher) {
            document.getElementById('discountCode').textContent = `(${voucher.code})`;
            if (selected.size && subtotal < voucher.min_order) {
                showVoucherMessage(`Mã ${voucher.code} cần đơn từ ${money(voucher.min_order)}. Hãy chọn thêm ghế.`, 'warning');
            } else {
                showVoucherMessage(`Đã áp dụng: ${voucher.label}`, 'success');
            }
        }

        if (!selected.size) tagBox.innerHTML = '<span class="text-muted small">Chưa chọn ghế nào</span>';
        totalEl.textContent = money(subtotal - discount + comboTotal);
        countEl.textContent = selected.size;
        submitBtn.disabled = selected.size === 0;
    };

    const applyVoucher = async () => {
        const code = voucherInput.value.trim();
        voucher = null;
        if (!code) {
            showVoucherMessage('', 'muted');
            render();
            return;
        }

        const params = new URLSearchParams({ code });
        selected.forEach((_, seat) => params.append('seats[]', seat));
        voucherBtn.disabled = true;

        try {
            const res = await fetch(`${voucherBox.dataset.checkUrl}?${params}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (res.ok && data.valid) {
                voucher = data;
                voucherInput.value = data.code;
            } else {
                showVoucherMessage(data.message || 'Mã giảm giá không hợp lệ.', 'danger');
            }
        } catch (err) {
            showVoucherMessage('Không kiểm tra được mã, vui lòng thử lại.', 'danger');
        } finally {
            voucherBtn.disabled = false;
            render();
        }
    };

    voucherBtn.addEventListener('click', applyVoucher);
    options.forEach(({ el, data }) => el.addEventListener('click', () => {
        // Bấm lại mã đang dùng để bỏ mã
        voucherInput.value = voucher?.code === data.code ? '' : data.code;
        applyVoucher();
    }));
    voucherInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault(); // Enter trong ô mã không gửi form đặt vé
            applyVoucher();
        }
    });
    voucherInput.addEventListener('input', () => {
        if (voucher) {
            voucher = null;
            showVoucherMessage('Bấm "Áp dụng" để kiểm tra mã mới.', 'muted');
            render();
        }
    });

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
    if (voucherInput.value.trim() && !voucherInput.classList.contains('is-invalid')) applyVoucher();

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
