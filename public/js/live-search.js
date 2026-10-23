// Tìm kiếm phim trực tiếp bằng AJAX (gợi ý khi gõ)
(() => {
    const box = document.querySelector('[data-live-search]');
    if (!box) return;

    const input = box.querySelector('input');
    const list = box.querySelector('.cb-suggest');
    const url = box.dataset.liveSearch;
    let timer = null;
    let controller = null;
    let activeIndex = -1;

    const escape = (s) => s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const render = (items, keyword) => {
        activeIndex = -1;
        if (!items.length) {
            list.innerHTML = `<div class="empty">Không tìm thấy phim nào cho "${escape(keyword)}"</div>`;
        } else {
            list.innerHTML = items.map((m) => `
                <a href="${m.url}">
                    ${m.poster
                        ? `<img class="thumb" src="${m.poster}" alt="">`
                        : `<span class="thumb" style="background:linear-gradient(160deg,hsl(${m.hue},55%,35%),hsl(${m.hue + 40},60%,12%))"></span>`}
                    <span><strong class="d-block">${escape(m.title)}</strong><small class="text-muted">${escape(m.meta)}</small></span>
                </a>`).join('');
        }
        list.classList.remove('d-none');
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        const keyword = input.value.trim();
        if (keyword.length < 2) {
            list.classList.add('d-none');
            return;
        }

        // Chờ người dùng ngừng gõ 250ms rồi mới gọi API (debounce)
        timer = setTimeout(async () => {
            controller?.abort();
            controller = new AbortController();
            try {
                const res = await fetch(`${url}?q=${encodeURIComponent(keyword)}`, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                render(await res.json(), keyword);
            } catch (e) {
                if (e.name !== 'AbortError') console.error(e);
            }
        }, 250);
    });

    // Điều hướng bằng phím mũi tên
    input.addEventListener('keydown', (e) => {
        const links = [...list.querySelectorAll('a')];
        if (!links.length || list.classList.contains('d-none')) return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = (activeIndex + (e.key === 'ArrowDown' ? 1 : -1) + links.length) % links.length;
            links.forEach((a, i) => a.classList.toggle('active', i === activeIndex));
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            window.location = links[activeIndex].href;
        } else if (e.key === 'Escape') {
            list.classList.add('d-none');
        }
    });

    document.addEventListener('click', (e) => {
        if (!box.contains(e.target)) list.classList.add('d-none');
    });
})();
