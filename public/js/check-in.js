// CineBook – trang Soát vé (admin): quét QR bằng camera hoặc nhập mã vé, tra cứu và cho khách vào rạp
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('codeForm');
    const input = document.getElementById('codeInput');
    const result = document.getElementById('checkinResult');
    const list = document.getElementById('checkinList');
    const count = document.getElementById('checkinCount');
    const toggle = document.getElementById('cameraToggle');
    const cameraError = document.getElementById('cameraError');
    const reader = document.getElementById('qrReader');
    const placeholder = document.getElementById('qrPlaceholder');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    // Tiếng "bíp" báo kết quả: cao = hợp lệ, thấp = có vấn đề
    let audio;
    const beep = (ok) => {
        try {
            audio ??= new AudioContext();
            const osc = audio.createOscillator();
            const gain = audio.createGain();
            osc.frequency.value = ok ? 1200 : 300;
            gain.gain.value = 0.15;
            osc.connect(gain).connect(audio.destination);
            osc.start();
            osc.stop(audio.currentTime + (ok ? 0.12 : 0.35));
        } catch { /* trình duyệt không hỗ trợ âm thanh: bỏ qua */ }
    };

    const show = (html, ok) => {
        result.innerHTML = html;
        beep(ok);
    };

    const lookup = async (code) => {
        code = code.trim().toUpperCase();
        if (!code) return;
        input.value = code;
        try {
            const res = await fetch(`${form.dataset.lookupUrl}?code=${encodeURIComponent(code)}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            show(data.html, data.ok);
        } catch {
            show('<div class="alert alert-danger mb-0">Không kết nối được máy chủ. Vui lòng thử lại.</div>', false);
        }
        input.select();
    };

    // Bảng "Suất đang soát vé": tự cập nhật mỗi 30 giây, báo khi có suất vừa tới giờ mở soát
    const board = document.getElementById('showtimeBoard');
    let openIds = new Set(JSON.parse(board.dataset.open));

    const notify = (text) => {
        const wrap = document.querySelector('.cb-toast-wrap');
        wrap.insertAdjacentHTML('beforeend', `
            <div class="toast align-items-center text-white bg-primary border-0 mb-2" role="alert" data-bs-delay="10000">
                <div class="d-flex">
                    <div class="toast-body d-flex gap-2 align-items-start"><i class="bi bi-bell-fill mt-1"></i><span></span></div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Đóng"></button>
                </div>
            </div>`);
        const toast = wrap.lastElementChild;
        toast.querySelector('span').textContent = text;
        bootstrap.Toast.getOrCreateInstance(toast).show();
    };

    const refreshBoard = async () => {
        try {
            const res = await fetch(board.dataset.url, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            board.innerHTML = data.html;
            const opened = data.open.filter((st) => !openIds.has(st.id));
            opened.forEach((st) => notify(`Đã mở soát vé: ${st.label}`));
            if (opened.length) beep(true);
            openIds = new Set(data.open.map((st) => st.id));
        } catch { /* mất mạng: lần sau thử lại */ }
    };
    setInterval(refreshBoard, 30000);

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        lookup(input.value);
    });

    // Bấm "Cho vào" / "Đã thu tiền & cho vào"
    result.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-checkin-url]');
        if (!btn) return;
        btn.disabled = true;
        try {
            const res = await fetch(btn.dataset.checkinUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ collect: btn.dataset.collect === '1' }),
            });
            const data = await res.json();
            show(data.html, data.ok);
            if (data.ok) {
                list.querySelector('[data-empty]')?.remove();
                list.insertAdjacentHTML('afterbegin', data.row);
                count.textContent = `${data.count} vé`;
                refreshBoard(); // cập nhật số vé đã vào của suất
            }
        } catch {
            btn.disabled = false;
        }
        input.value = '';
        input.focus();
    });

    // Quét QR bằng camera (thư viện html5-qrcode). Camera chỉ dùng được trên localhost hoặc HTTPS.
    let scanner = null;
    let lastCode = '';
    let lastAt = 0;

    const onScan = (text) => {
        // Bỏ qua khi camera đọc lại cùng một mã liên tục trong 3 giây
        if (text === lastCode && Date.now() - lastAt < 3000) return;
        lastCode = text;
        lastAt = Date.now();
        lookup(text);
    };

    const setCamera = (on) => {
        reader.classList.toggle('d-none', !on);
        placeholder.classList.toggle('d-none', on);
        toggle.innerHTML = on ? '<i class="bi bi-camera-video-off me-1"></i>Tắt camera' : '<i class="bi bi-camera-video me-1"></i>Bật camera';
    };

    toggle.addEventListener('click', async () => {
        cameraError.classList.add('d-none');
        if (scanner) {
            await scanner.stop().catch(() => {});
            scanner = null;
            setCamera(false);
            return;
        }
        if (typeof Html5Qrcode === 'undefined') {
            cameraError.textContent = 'Không tải được thư viện quét QR (cần Internet). Hãy nhập mã vé bằng tay.';
            cameraError.classList.remove('d-none');
            return;
        }
        setCamera(true);
        scanner = new Html5Qrcode('qrReader');
        try {
            await scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: 220 }, onScan, () => {});
        } catch (err) {
            scanner = null;
            setCamera(false);
            cameraError.textContent = 'Không mở được camera: hãy cho phép trình duyệt dùng camera (chỉ hoạt động trên localhost hoặc HTTPS).';
            cameraError.classList.remove('d-none');
        }
    });
});
