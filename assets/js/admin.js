document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = window.CSRF_TOKEN || '';

    // 通用图片上传函数
    function bindUpload(inputId, hiddenInputSelector, previewSelector, multiple) {
        const input = document.getElementById(inputId);
        if (!input) return;

        input.addEventListener('change', function () {
            const files = multiple ? input.files : [input.files[0]];
            if (!files.length) return;

            Array.from(files).forEach(file => {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('csrf_token', csrfToken);

                fetch('/admin/api/upload.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-CSRF-Token': csrfToken },
                })
                .then(r => r.json())
                .then(res => {
                    if (res.code !== 0) {
                        alert(res.msg || '上传失败');
                        return;
                    }
                    const path = res.data.path;
                    if (multiple) {
                        const hidden = document.querySelector(hiddenInputSelector);
                        let list = [];
                        try { list = JSON.parse(hidden.value || '[]'); } catch (e) {}
                        list.push(path);
                        hidden.value = JSON.stringify(list);
                        renderImageList(previewSelector, hiddenInputSelector);
                    } else {
                        document.querySelector(hiddenInputSelector).value = path;
                        const preview = document.querySelector(previewSelector);
                        preview.innerHTML = '<img src="' + res.data.url + '">';
                    }
                })
                .catch(() => alert('上传失败，请检查网络'));
            });

            input.value = '';
        });
    }

    bindUpload('mainImageInput', 'input[name="main_image"]', '#mainImagePreview', false);
    bindSingleUploadWithClear('paymentQrInput', 'input[name="payment_qr"]', '#paymentQrPreview');
    bindSingleUploadWithClear('contactWechatQrInput', 'input[name="contact_wechat_qr"]', '#contactWechatQrPreview');
    // 单个收款码上传（支持清除，清除后回退全局通用码）
    function bindSingleUploadWithClear(inputId, hiddenSel, previewSel) {
        const input = document.getElementById(inputId);
        const hidden = document.querySelector(hiddenSel);
        const preview = document.querySelector(previewSel);
        if (!input || !hidden || !preview) return;
        input.addEventListener('change', function () {
            const file = input.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('file', file);
            formData.append('csrf_token', csrfToken);
            fetch('/admin/api/upload.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-Token': csrfToken },
            })
            .then(r => r.json())
            .then(res => {
                if (res.code !== 0) { alert(res.msg || '上传失败'); return; }
                hidden.value = res.data.path;
                preview.innerHTML = '<div class="preview-item"><img src="' + res.data.url + '"><button type="button" class="remove-btn" data-clear-single="1">×</button></div>';
            })
            .catch(() => alert('上传失败，请检查网络'));
            input.value = '';
        });
        preview.addEventListener('click', function (e) {
            if (e.target && e.target.matches('[data-clear-single]')) {
                hidden.value = '';
                preview.innerHTML = '';
            }
        });
    }

    bindUpload('detailImagesInput', '#detailImagesInputHidden', '#detailImagesPreview', true);
    bindUpload('galleryImagesInput', '#galleryImagesInputHidden', '#galleryImagesPreview', true);

    // 单张预览项 HTML（可拖动，data-path 用于排序回写）
    function previewItemHtml(path) {
        return '<div class="preview-item" draggable="true" data-path="' + path + '">'
            + '<span class="drag-grip" title="拖动排序">⋮⋮</span>'
            + '<img src="/uploads/' + path.replace('/uploads/', '') + '">'
            + '<button type="button" class="remove-btn" data-path="' + path + '">×</button>'
            + '</div>';
    }

    // 通用多图列表渲染（删除 + 拖动排序）
    function renderImageList(containerSel, hiddenSel) {
        const hidden = document.querySelector(hiddenSel);
        const container = document.querySelector(containerSel);
        if (!hidden || !container) return;
        let list = [];
        try { list = JSON.parse(hidden.value || '[]'); } catch (e) {}
        container.innerHTML = list.map(previewItemHtml).join('');
        bindRemove(containerSel, hiddenSel, function () { renderImageList(containerSel, hiddenSel); });
        enableSort(containerSel, hiddenSel);
    }

    function bindRemove(previewSel, hiddenSel, rerender) {
        document.querySelectorAll(previewSel + ' .remove-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const path = this.dataset.path;
                const hidden = document.querySelector(hiddenSel);
                let list = [];
                try { list = JSON.parse(hidden.value || '[]'); } catch (e) {}
                list = list.filter(p => p !== path);
                hidden.value = JSON.stringify(list);
                rerender();
            });
        });
    }

    // 原生拖动排序：拖完按 DOM 顺序回写隐藏字段 JSON
    function enableSort(containerSel, hiddenSel) {
        const container = document.querySelector(containerSel);
        if (!container) return;
        let dragEl = null;
        container.querySelectorAll('.preview-item').forEach(item => {
            item.addEventListener('dragstart', function (e) {
                dragEl = this;
                this.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                try { e.dataTransfer.setData('text/plain', this.dataset.path); } catch (err) {}
            });
            item.addEventListener('dragend', function () {
                this.classList.remove('is-dragging');
                container.querySelectorAll('.preview-item').forEach(i => i.classList.remove('drag-over'));
                const hidden = document.querySelector(hiddenSel);
                if (!hidden) return;
                const ordered = Array.from(container.querySelectorAll('.preview-item')).map(i => i.dataset.path);
                hidden.value = JSON.stringify(ordered);
            });
            item.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                if (this !== dragEl) this.classList.add('drag-over');
            });
            item.addEventListener('dragleave', function () {
                this.classList.remove('drag-over');
            });
            item.addEventListener('drop', function (e) {
                e.preventDefault();
                if (!dragEl || this === dragEl) return;
                const rect = this.getBoundingClientRect();
                const after = (e.clientX - rect.left) > rect.width / 2;
                container.insertBefore(dragEl, after ? this.nextSibling : this);
            });
        });
    }

    // 上传成功后由 bindUpload 的 multiple 分支调用 renderImageList 刷新
    renderImageList('#detailImagesPreview', '#detailImagesInputHidden');
    renderImageList('#galleryImagesPreview', '#galleryImagesInputHidden');

    // slug 实时预览
    const slugInput = document.querySelector('input[name="slug"]');
    const slugPreview = document.getElementById('slugPreview');
    if (slugInput && slugPreview) {
        slugInput.addEventListener('input', function () {
            slugPreview.textContent = this.value;
        });
        slugPreview.textContent = slugInput.value;
    }

    // 规格管理
    const specsArea = document.getElementById('specsArea');
    const addSpecBtn = document.getElementById('addSpec');
    const specsHidden = document.getElementById('specsHidden');

    function buildSpecRow(label, key, price, stock) {
        const div = document.createElement('div');
        div.className = 'spec-row';
        div.innerHTML = `
            <input type="text" placeholder="规格名称" class="spec-label" value="${label || ''}">
            <input type="text" placeholder="标识(key)" class="spec-key" value="${key || ''}">
            <input type="number" placeholder="价格" class="spec-price" value="${price || ''}" step="0.01">
            <input type="number" placeholder="库存" class="spec-stock" value="${stock || ''}">
            <button type="button" class="btn-sm btn-danger remove-spec">删除</button>
        `;
        div.querySelector('.remove-spec').addEventListener('click', function () {
            div.remove();
        });
        return div;
    }

    if (addSpecBtn) {
        addSpecBtn.addEventListener('click', function () {
            specsArea.appendChild(buildSpecRow());
        });
    }

    document.querySelectorAll('.remove-spec').forEach(btn => {
        btn.addEventListener('click', function () {
            this.closest('.spec-row').remove();
        });
    });

    function collectSpecs() {
        const rows = specsArea ? specsArea.querySelectorAll('.spec-row') : [];
        const specs = [];
        rows.forEach(row => {
            const label = row.querySelector('.spec-label').value.trim();
            const key = row.querySelector('.spec-key').value.trim();
            const price = parseFloat(row.querySelector('.spec-price').value) || 0;
            const stock = parseInt(row.querySelector('.spec-stock').value) || 0;
            if (label && key) specs.push({ label, key, price, stock });
        });
        return specs;
    }

    // 商品保存
    const productForm = document.getElementById('productForm');
    if (productForm) {
        productForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('saveBtn');
            btn.disabled = true;
            btn.textContent = '保存中...';

            const detailImages = [];
            try {
                detailImages.push(...JSON.parse(document.getElementById('detailImagesInputHidden').value || '[]'));
            } catch (e) {}

            const galleryImages = [];
            try {
                galleryImages.push(...JSON.parse(document.getElementById('galleryImagesInputHidden').value || '[]'));
            } catch (e) {}

            const payload = {
                id: parseInt(document.querySelector('input[name="id"]').value) || 0,
                title: document.querySelector('input[name="title"]').value.trim(),
                slug: document.querySelector('input[name="slug"]').value.trim(),
                subtitle: document.querySelector('input[name="subtitle"]').value.trim(),
                main_image: document.querySelector('input[name="main_image"]').value.trim(),
                gallery_images: galleryImages,
                detail_images: detailImages,
                specs: collectSpecs(),
                price: parseFloat(document.querySelector('input[name="price"]').value) || 0,
                original_price: parseFloat(document.querySelector('input[name="original_price"]').value) || 0,
                stock: parseInt(document.querySelector('input[name="stock"]').value) || 0,
                status: parseInt(document.querySelector('select[name="status"]').value),
                sort: parseInt(document.querySelector('input[name="sort"]').value) || 0,
                seo_title: document.querySelector('input[name="seo_title"]').value.trim(),
                seo_desc: document.querySelector('textarea[name="seo_desc"]').value.trim(),
            };

            fetch('/admin/api/product_save.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify(payload),
            })
            .then(r => r.json())
            .then(res => {
                if (res.code === 0) {
                    alert('保存成功');
                    window.location.href = '/admin/products.php';
                } else {
                    alert(res.msg || '保存失败');
                    btn.disabled = false;
                    btn.textContent = '保存商品';
                }
            })
            .catch(() => {
                alert('网络错误');
                btn.disabled = false;
                btn.textContent = '保存商品';
            });
        });
    }

    // 设置保存
    const settingsForm = document.getElementById('settingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('saveSettingsBtn');
            btn.disabled = true;
            btn.textContent = '保存中...';

            const payload = {
                site_name: document.querySelector('input[name="site_name"]').value.trim(),
                site_notice: document.querySelector('textarea[name="site_notice"]').value.trim(),
                payment_qr: document.querySelector('input[name="payment_qr"]').value.trim(),
                payment_tip: document.querySelector('textarea[name="payment_tip"]').value.trim(),
                payment_card_extra: document.querySelector('textarea[name="payment_card_extra"]').value.trim(),
                contact_phone: document.querySelector('input[name="contact_phone"]').value.trim(),
                contact_wechat: document.querySelector('input[name="contact_wechat"]').value.trim(),
                contact_wechat_qr: document.querySelector('input[name="contact_wechat_qr"]').value.trim(),
                sms_enabled: document.querySelector('input[name="sms_enabled"]').checked ? 1 : 0,
                sms_api_key: document.querySelector('input[name="sms_api_key"]').value.trim(),
                sms_signature: document.querySelector('input[name="sms_signature"]').value.trim(),
                admin_notify_mobiles: document.querySelector('input[name="admin_notify_mobiles"]').value.trim(),
                sms_tpl_order: document.querySelector('textarea[name="sms_tpl_order"]').value.trim(),
                sms_tpl_ship: document.querySelector('textarea[name="sms_tpl_ship"]').value.trim(),
                admin_password: document.querySelector('input[name="admin_password"]').value,
                admin_password_confirm: document.querySelector('input[name="admin_password_confirm"]').value,
            };

            fetch('/admin/api/settings_save.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify(payload),
            })
            .then(r => r.json())
            .then(res => {
                if (res.code === 0) {
                    alert('保存成功');
                    window.location.reload();
                } else {
                    alert(res.msg || '保存失败');
                    btn.disabled = false;
                    btn.textContent = '保存设置';
                }
            })
            .catch(() => {
                alert('网络错误');
                btn.disabled = false;
                btn.textContent = '保存设置';
            });
        });
    }

    // 短信测试发送
    const smsTestBtn = document.getElementById('smsTestBtn');
    if (smsTestBtn) {
        smsTestBtn.addEventListener('click', function () {
            const mobile = document.getElementById('smsTestMobile').value.trim();
            const resultEl = document.getElementById('smsTestResult');
            if (!mobile) {
                resultEl.textContent = '请填写测试手机号';
                return;
            }
            smsTestBtn.disabled = true;
            resultEl.textContent = '发送中...';
            fetch('/admin/api/sms_test.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ mobile: mobile }),
            })
            .then(r => r.json())
            .then(res => {
                resultEl.textContent = res.msg || (res.code === 0 ? '发送成功' : '发送失败');
                smsTestBtn.disabled = false;
            })
            .catch(() => {
                resultEl.textContent = '请求失败，请检查网络';
                smsTestBtn.disabled = false;
            });
        });
    }
});
