document.addEventListener('DOMContentLoaded', function () {
    const hasSpecs = window.HAS_SPECS;
    const basePrice = window.BASE_PRICE || 0;
    const productId = window.PRODUCT_ID || (document.body && document.body.dataset ? (document.body.dataset.productId || 0) : 0);
    const csrfToken = window.CSRF_TOKEN;

    const priceDisplay = document.getElementById('priceDisplay');
    const stockDisplay = document.getElementById('stockDisplay');
    const quantityInput = document.getElementById('quantity');
    const totalPrice = document.getElementById('totalPrice');
    const specBtns = document.querySelectorAll('.spec-btn');
    const qtyMinus = document.getElementById('qtyMinus');
    const qtyPlus = document.getElementById('qtyPlus');
    const submitBtn = document.getElementById('submitOrder');

    // 收货信息本地缓存：下次打开自动填充
    const buyerNameEl = document.getElementById('buyerName');
    const buyerPhoneEl = document.getElementById('buyerPhone');
    const buyerAddressEl = document.getElementById('buyerAddress');
    const buyerRemarkEl = document.getElementById('buyerRemark');
    const BUYER_CACHE_KEY = 'imall_buyer_info';
    try {
        const cached = JSON.parse(localStorage.getItem(BUYER_CACHE_KEY) || '{}');
        if (cached.name) buyerNameEl.value = cached.name;
        if (cached.phone) buyerPhoneEl.value = cached.phone;
        if (cached.address) buyerAddressEl.value = cached.address;
        if (cached.remark) buyerRemarkEl.value = cached.remark;
    } catch (e) {}
    function saveBuyerCache() {
        try {
            localStorage.setItem(BUYER_CACHE_KEY, JSON.stringify({
                name: buyerNameEl.value.trim(),
                phone: buyerPhoneEl.value.trim(),
                address: buyerAddressEl.value.trim(),
                remark: buyerRemarkEl.value.trim(),
            }));
        } catch (e) {}
    }
    [buyerNameEl, buyerPhoneEl, buyerAddressEl, buyerRemarkEl].forEach(el => {
        if (el) el.addEventListener('input', saveBuyerCache);
    });

    let currentPrice = basePrice;
    let currentStock = parseInt(quantityInput ? quantityInput.getAttribute('max') : 0) || 0;
    let currentSpecKey = '';

    function updateTotal() {
        const qty = parseInt(quantityInput.value) || 1;
        const total = (currentPrice * qty).toFixed(2);
        totalPrice.textContent = '¥' + total;
    }

    function selectSpec(btn) {
        specBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentPrice = parseFloat(btn.dataset.price) || 0;
        currentStock = parseInt(btn.dataset.stock) || 0;
        currentSpecKey = btn.dataset.key || '';
        if (priceDisplay) priceDisplay.textContent = '¥' + currentPrice.toFixed(2);
        if (stockDisplay) stockDisplay.textContent = currentStock;
        quantityInput.setAttribute('max', currentStock);
        let qty = parseInt(quantityInput.value) || 1;
        if (qty > currentStock) qty = Math.max(1, currentStock);
        quantityInput.value = qty;
        updateTotal();
    }

    if (specBtns.length) {
        const first = document.querySelector('.spec-btn.active') || specBtns[0];
        selectSpec(first);
        specBtns.forEach(btn => {
            btn.addEventListener('click', function () { selectSpec(this); });
        });
    }

    if (qtyMinus) {
        qtyMinus.addEventListener('click', function () {
            let v = parseInt(quantityInput.value) || 1;
            if (v > 1) quantityInput.value = v - 1;
            updateTotal();
        });
    }
    if (qtyPlus) {
        qtyPlus.addEventListener('click', function () {
            let v = parseInt(quantityInput.value) || 1;
            const max = parseInt(quantityInput.getAttribute('max')) || 9999;
            if (v < max) quantityInput.value = v + 1;
            updateTotal();
        });
    }
    if (quantityInput) {
        quantityInput.addEventListener('change', function () {
            const max = parseInt(this.getAttribute('max')) || 9999;
            let v = parseInt(this.value) || 1;
            if (v < 1) v = 1;
            if (v > max) v = max;
            this.value = v;
            updateTotal();
        });
    }

    updateTotal();

    // 顶部图集滑动（箭头 + 拖拽 + 原生触摸滑动）
    initGallery();
    function initGallery() {
        const track = document.getElementById('galleryTrack');
        const dots = document.querySelectorAll('#galleryDots .dot');
        if (!track) return;
        const slides = track.children.length;
        let current = 0;

        function goTo(i) {
            i = Math.max(0, Math.min(slides - 1, i));
            current = i;
            track.scrollTo({ left: i * track.clientWidth, behavior: 'smooth' });
            dots.forEach((d, idx) => d.classList.toggle('active', idx === i));
        }

        track.addEventListener('scroll', () => {
            const i = Math.round(track.scrollLeft / track.clientWidth);
            current = i;
            dots.forEach((d, idx) => d.classList.toggle('active', idx === i));
        }, { passive: true });

        dots.forEach(d => d.addEventListener('click', () => goTo(parseInt(d.dataset.i) || 0)));

        // 鼠标 / 触摸拖拽滑动
        let isDown = false, startX = 0, startScroll = 0, moved = 0;
        track.addEventListener('pointerdown', (e) => {
            isDown = true; moved = 0;
            startX = e.clientX;
            startScroll = track.scrollLeft;
            try { track.setPointerCapture(e.pointerId); } catch (err) {}
        });
        track.addEventListener('pointermove', (e) => {
            if (!isDown) return;
            const dx = e.clientX - startX;
            moved = Math.abs(dx);
            track.scrollLeft = startScroll - dx;
        });
        const endDrag = () => { isDown = false; };
        track.addEventListener('pointerup', endDrag);
        track.addEventListener('pointercancel', endDrag);

        // 拖拽后不触发灯箱放大
        track.addEventListener('click', (e) => {
            if (moved > 8) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);
    }

    // 图片点击放大（图集 + 详情图）
    const lightbox = document.getElementById('imgLightbox');
    if (lightbox) {
        const lbImg = lightbox.querySelector('img');
        document.querySelectorAll('.gallery-img, .detail-img').forEach(el => {
            el.style.cursor = 'zoom-in';
            el.addEventListener('click', function () {
                lbImg.src = el.getAttribute('data-full') || el.src;
                lightbox.classList.add('active');
            });
        });
        lightbox.addEventListener('click', function () {
            lightbox.classList.remove('active');
        });
    }

    if (submitBtn) {
        // 记录按钮初始状态，供浏览器「返回」从 bfcache 还原时复位
        const defaultLabel = submitBtn.textContent;
        const defaultDisabled = submitBtn.disabled;

        // 浏览器「返回」时页面常从 bfcache 原样恢复，按钮可能卡在「提交中...」
        // 此时复位为初始状态，避免一直不可点（缺货商品也正确还原为「暂时缺货」）
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                submitBtn.disabled = defaultDisabled;
                submitBtn.textContent = defaultLabel;
            }
        });

        submitBtn.addEventListener('click', function () {
            if (submitBtn.disabled) return;

            if (!productId) {
                alert('商品信息加载异常，请刷新页面后重试');
                return;
            }

            // 规格兜底：有规格但当前未选中时，默认取第一个
            if (specBtns.length && !currentSpecKey) {
                currentSpecKey = specBtns[0].dataset.key || '';
            }

            const buyerName = document.getElementById('buyerName').value.trim();
            const buyerPhone = document.getElementById('buyerPhone').value.trim();
            const buyerAddress = document.getElementById('buyerAddress').value.trim();
            const buyerRemark = document.getElementById('buyerRemark').value.trim();
            const quantity = parseInt(quantityInput.value) || 1;

            if (!buyerName) { alert('请填写收货人姓名'); return; }
            if (!/^1[3-9]\d{9}$/.test(buyerPhone)) { alert('请填写正确的手机号码'); return; }
            if (!buyerAddress) { alert('请填写详细收货地址'); return; }

            submitBtn.disabled = true;
            submitBtn.textContent = '提交中...';

            const payload = {
                product_id: productId,
                spec_key: currentSpecKey,
                quantity: quantity,
                buyer_name: buyerName,
                buyer_phone: buyerPhone,
                buyer_address: buyerAddress,
                buyer_remark: buyerRemark,
            };

            fetch('/api/order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify(payload),
            })
            .then(r => r.json())
            .then(res => {
                if (res.code === 0 && res.data && res.data.pay_url) {
                    // 仍处于提交态才跳转；若页面已被「返回」复位（bfcache），不再劫持跳转
                    if (submitBtn && submitBtn.disabled) {
                        window.location.href = res.data.pay_url;
                    }
                } else {
                    alert(res.msg || '下单失败，请重试');
                    submitBtn.disabled = false;
                    submitBtn.textContent = '立即下单';
                }
            })
            .catch(err => {
                alert('网络错误，请重试');
                submitBtn.disabled = false;
                submitBtn.textContent = '立即下单';
            });
        });
    }
});
