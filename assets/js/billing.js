(function () {
    'use strict';

    const itemsBody           = document.getElementById('itemsBody');
    const addRowBtn           = document.getElementById('addRowBtn');
    const customerSelect      = document.getElementById('customerSelect');
    const billForm            = document.getElementById('billForm');
    const duplicateAlert      = document.getElementById('duplicateAlert');
    const duplicateAlertMsg   = document.getElementById('duplicateAlertMsg');
    const duplicateAlertClose = document.getElementById('duplicateAlertClose');

    let rowSeq = 0;

    if (duplicateAlertClose && duplicateAlert) {
        duplicateAlertClose.addEventListener('click', function () {
            duplicateAlert.classList.add('d-none');
            duplicateAlert.classList.remove('d-flex');
        });
    }

    function showDuplicateAlert(message) {
        if (!duplicateAlert) return;
        if (duplicateAlertMsg) {
            duplicateAlertMsg.textContent = message;
        }
        duplicateAlert.classList.remove('d-none');
        duplicateAlert.classList.add('d-flex');
        duplicateAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideDuplicateAlert() {
        if (!duplicateAlert) return;
        duplicateAlert.classList.add('d-none');
        duplicateAlert.classList.remove('d-flex');
    }

    function findProduct(id) {
        if (!window.PRODUCTS || !Array.isArray(window.PRODUCTS)) return null;
        return window.PRODUCTS.find(function (p) {
            return String(p.product_id) === String(id);
        });
    }

    function formatCurrency(val) {
        return (Number(val) || 0).toFixed(2);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getSelectedProductIds(excludeTr) {
        const selected = {};
        if (!itemsBody) return selected;
        itemsBody.querySelectorAll('tr').forEach(function (tr) {
            if (excludeTr && tr === excludeTr) return;
            const select = tr.querySelector('.product-select');
            if (select && select.value) {
                selected[String(select.value)] = tr;
            }
        });
        return selected;
    }

    function syncProductDropdowns() {
        if (!itemsBody) return;
        const allSelected = getSelectedProductIds();

        itemsBody.querySelectorAll('tr').forEach(function (tr) {
            const select = tr.querySelector('.product-select');
            if (!select) return;
            const currentVal = String(select.value || '');

            Array.from(select.options).forEach(function (opt) {
                if (!opt.value) return; // Skip placeholder
                const optVal = String(opt.value);
                const isSelectedElsewhere = allSelected[optVal] && allSelected[optVal] !== tr;

                if (isSelectedElsewhere) {
                    opt.disabled = true;
                    if (!opt.dataset.origText) opt.dataset.origText = opt.textContent;
                    opt.textContent = opt.dataset.origText + ' — [Already in Bill]';
                } else {
                    opt.disabled = false;
                    if (opt.dataset.origText) {
                        opt.textContent = opt.dataset.origText;
                    }
                }
            });
        });

        // Update addRow button state if all products are selected
        if (addRowBtn && window.PRODUCTS) {
            const countSelected = Object.keys(allSelected).length;
            if (countSelected >= window.PRODUCTS.length && window.PRODUCTS.length > 0) {
                addRowBtn.disabled = true;
                addRowBtn.title = 'All catalog products are already added to this bill.';
            } else {
                addRowBtn.disabled = false;
                addRowBtn.title = 'Add another product row';
            }
        }
    }

    function buildProductOptions(selectedId) {
        let html = '<option value="">-- Select Product --</option>';
        if (!window.PRODUCTS || !Array.isArray(window.PRODUCTS)) return html;

        const allSelected = getSelectedProductIds();

        window.PRODUCTS.forEach(function (p) {
            const pid = String(p.product_id);
            const isSelected = pid === String(selectedId);
            const isTaken = allSelected[pid] && !isSelected;
            const disabledAttr = isTaken ? 'disabled' : '';
            const suffix = isTaken ? ' — [Already in Bill]' : '';
            const selectedAttr = isSelected ? 'selected' : '';

            html += '<option value="' + p.product_id + '" ' + selectedAttr + ' ' + disabledAttr +
                ' data-orig-text="' + escapeHtml(p.name + ' (Stock: ' + p.stock_qty + ' ' + p.unit + ')') + '">' +
                escapeHtml(p.name + ' (Stock: ' + p.stock_qty + ' ' + p.unit + ')') + suffix + '</option>';
        });
        return html;
    }

    function addRow() {
        rowSeq++;
        const tr = document.createElement('tr');
        tr.dataset.row = String(rowSeq);
        tr.innerHTML =
            '<td class="col-product"><select class="form-select form-select-sm product-select" name="product_id[]" required>' +
                buildProductOptions('') +
            '</select></td>' +
            '<td class="hsn-cell text-muted col-hsn">-</td>' +
            '<td class="col-qty">' +
                '<div class="qty-stepper-container">' +
                    '<div class="input-group input-group-sm">' +
                        '<button type="button" class="btn btn-outline-secondary btn-qty-step btn-qty-minus" title="Decrease quantity"><i class="bi bi-dash"></i></button>' +
                        '<input type="number" class="form-control text-center qty-input" name="qty[]" value="1" min="0.01" step="any" required>' +
                        '<button type="button" class="btn btn-outline-secondary btn-qty-step btn-qty-plus" title="Increase quantity"><i class="bi bi-plus"></i></button>' +
                    '</div>' +
                    '<div class="stock-info-text text-muted text-center mt-1"><span class="stock-badge">Stock: <span class="stock-val">-</span></span></div>' +
                '</div>' +
            '</td>' +
            '<td class="text-end rate-cell col-rate">0.00</td>' +
            '<td class="text-end taxable-cell col-taxable">0.00</td>' +
            '<td class="text-end gstrate-cell col-gstrate">0%</td>' +
            '<td class="text-end gstamt-cell col-gstamt">0.00</td>' +
            '<td class="text-end linetotal-cell fw-semibold col-linetotal">0.00</td>' +
            '<td class="col-action text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove Item"><i class="bi bi-x-lg"></i></button></td>';

        itemsBody.appendChild(tr);
        bindRow(tr);
        syncProductDropdowns();
    }

    function bindRow(tr) {
        const select     = tr.querySelector('.product-select');
        const qtyInput   = tr.querySelector('.qty-input');
        const minusBtn   = tr.querySelector('.btn-qty-minus');
        const plusBtn    = tr.querySelector('.btn-qty-plus');
        const removeBtn  = tr.querySelector('.remove-row');

        // Track previous valid value to allow clean revert on duplicate pick
        let prevProductId = select.value;

        select.addEventListener('change', function () {
            const chosenId = select.value;

            if (chosenId) {
                // Check if this product is already selected in another row
                const otherRows = getSelectedProductIds(tr);
                if (otherRows[chosenId]) {
                    const existingTr = otherRows[chosenId];
                    const product = findProduct(chosenId);
                    const prodName = product ? product.name : 'Selected product';

                    // Revert select back
                    select.value = prevProductId;

                    // Show warning banner
                    showDuplicateAlert('"' + prodName + '" is already added in the bill. Please increase its quantity instead of adding another row.');

                    // Highlight and scroll the existing row
                    existingTr.classList.remove('row-highlight-pulse');
                    void existingTr.offsetWidth; // Force CSS reflow to re-trigger animation
                    existingTr.classList.add('row-highlight-pulse');
                    existingTr.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    const existingQtyInput = existingTr.querySelector('.qty-input');
                    if (existingQtyInput) {
                        existingQtyInput.focus();
                        existingQtyInput.select();
                    }
                    return;
                }
            }

            prevProductId = chosenId;
            hideDuplicateAlert();
            syncProductDropdowns();
            recalcRow(tr);
        });

        qtyInput.addEventListener('input', function () {
            recalcRow(tr);
        });

        minusBtn.addEventListener('click', function () {
            let val = parseFloat(qtyInput.value) || 0;
            if (val > 1) {
                qtyInput.value = Math.max(1, Math.round(val - 1));
            } else if (val > 0.1) {
                qtyInput.value = (val - 0.1 > 0 ? (val - 0.1).toFixed(2) : 0.01);
            }
            qtyInput.dispatchEvent(new Event('input', { bubbles: true }));
        });

        plusBtn.addEventListener('click', function () {
            let val = parseFloat(qtyInput.value) || 0;
            qtyInput.value = Math.round(val + 1);
            qtyInput.dispatchEvent(new Event('input', { bubbles: true }));
        });

        removeBtn.addEventListener('click', function () {
            tr.remove();
            hideDuplicateAlert();
            syncProductDropdowns();
            recalcTotals();
        });
    }

    function recalcRow(tr) {
        const select    = tr.querySelector('.product-select');
        const qtyInput  = tr.querySelector('.qty-input');
        const stockInfo = tr.querySelector('.stock-info-text');

        const productId = select ? select.value : '';
        const qty       = parseFloat(qtyInput.value) || 0;
        const product   = findProduct(productId);

        if (!product) {
            tr.querySelector('.hsn-cell').textContent       = '-';
            tr.querySelector('.rate-cell').textContent      = '0.00';
            tr.querySelector('.taxable-cell').textContent   = '0.00';
            tr.querySelector('.gstrate-cell').textContent   = '0%';
            tr.querySelector('.gstamt-cell').textContent    = '0.00';
            tr.querySelector('.linetotal-cell').textContent = '0.00';
            if (stockInfo) {
                stockInfo.innerHTML = '<span class="stock-badge">Stock: <span class="stock-val">-</span></span>';
            }
            qtyInput.classList.remove('is-invalid');
            recalcTotals();
            return;
        }

        const rate     = parseFloat(product.selling_price) || 0;
        const gstRate  = parseFloat(product.gst_rate) || 0;
        const stockQty = parseFloat(product.stock_qty) || 0;
        const unit     = product.unit || '';

        const taxable   = qty * rate;
        const gstAmt    = (taxable * gstRate) / 100;
        const lineTotal = taxable + gstAmt;

        tr.querySelector('.hsn-cell').textContent       = product.hsn_code || '-';
        tr.querySelector('.rate-cell').textContent      = formatCurrency(rate);
        tr.querySelector('.taxable-cell').textContent   = formatCurrency(taxable);
        tr.querySelector('.gstrate-cell').textContent   = gstRate + '%';
        tr.querySelector('.gstamt-cell').textContent    = formatCurrency(gstAmt);
        tr.querySelector('.linetotal-cell').textContent = formatCurrency(lineTotal);

        // Stock quantity live validation & indicator
        if (stockInfo) {
            if (qty > stockQty) {
                qtyInput.classList.add('is-invalid');
                stockInfo.innerHTML = '<span class="text-danger fw-semibold"><i class="bi bi-exclamation-triangle-fill me-1"></i>Max: ' + stockQty + ' ' + escapeHtml(unit) + '</span>';
            } else {
                qtyInput.classList.remove('is-invalid');
                stockInfo.innerHTML = '<span class="text-muted">Stock: <strong class="text-dark">' + stockQty + '</strong> ' + escapeHtml(unit) + '</span>';
            }
        }

        recalcTotals();
    }

    function isIntraState() {
        if (!customerSelect || customerSelect.selectedIndex < 0) return true;
        const opt = customerSelect.options[customerSelect.selectedIndex];
        if (!opt || !opt.value) {
            // No customer chosen yet; default to intra-state (CGST+SGST)
            return true;
        }
        const custState = (opt.getAttribute('data-state') || '').trim();
        const shopState = (window.SHOP_STATE || '').trim();

        if (!custState || !shopState) return true;
        return custState.toLowerCase() === shopState.toLowerCase();
    }

    function recalcTotals() {
        let taxable = 0;
        let gst = 0;

        if (itemsBody) {
            itemsBody.querySelectorAll('tr').forEach(function (tr) {
                taxable += parseFloat(tr.querySelector('.taxable-cell').textContent) || 0;
                gst     += parseFloat(tr.querySelector('.gstamt-cell').textContent) || 0;
            });
        }

        const intra = isIntraState();
        const tax  = intra ? gst : 0;
        const igst = intra ? 0 : gst;

        const rawTotal   = taxable + gst;
        const grandTotal = Math.round(rawTotal);
        const roundOff   = grandTotal - rawTotal;

        const sumTaxable = document.getElementById('sumTaxable');
        const sumTax     = document.getElementById('sumTax');
        const sumIgst    = document.getElementById('sumIgst');
        const sumRound   = document.getElementById('sumRound');
        const sumGrand   = document.getElementById('sumGrand');
        const rowTax     = document.getElementById('rowTax');
        const rowIgst    = document.getElementById('rowIgst');
        const hint       = document.getElementById('taxTypeHint');

        if (sumTaxable) sumTaxable.textContent = formatCurrency(taxable);
        if (sumTax)     sumTax.textContent     = formatCurrency(tax);
        if (sumIgst)    sumIgst.textContent    = formatCurrency(igst);
        if (sumRound)   sumRound.textContent   = (roundOff >= 0 ? '+' : '') + roundOff.toFixed(2);
        if (sumGrand) {
            sumGrand.innerHTML = '&#8377; ' + grandTotal.toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        if (rowTax)  rowTax.style.display  = intra ? '' : 'none';
        if (rowIgst) rowIgst.style.display = intra ? 'none' : '';

        if (hint) {
            const opt = customerSelect && customerSelect.selectedIndex >= 0 ? customerSelect.options[customerSelect.selectedIndex] : null;
            if (customerSelect && customerSelect.value && opt) {
                const custState = opt.getAttribute('data-state') || 'Customer State';
                if (intra) {
                    hint.innerHTML = '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-check-circle me-1"></i>Same state as shop (' + escapeHtml(window.SHOP_STATE || '') + ') &rarr; CGST + SGST applies.</span>';
                } else {
                    hint.innerHTML = '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><i class="bi bi-globe me-1"></i>Inter-state (' + escapeHtml(custState) + ' &ne; ' + escapeHtml(window.SHOP_STATE || '') + ') &rarr; IGST applies.</span>';
                }
            } else {
                hint.innerHTML = '<i class="bi bi-info-circle me-1"></i>Select a customer to confirm tax type (default: CGST + SGST).';
            }
        }
    }

    if (addRowBtn) {
        addRowBtn.addEventListener('click', addRow);
    }

    if (customerSelect) {
        customerSelect.addEventListener('change', recalcTotals);
    }

    if (billForm) {
        billForm.addEventListener('submit', function (e) {
            const rows = itemsBody ? itemsBody.querySelectorAll('tr') : [];
            if (rows.length === 0) {
                e.preventDefault();
                alert('Please add at least one item to the bill.');
                return;
            }

            const seenProducts = {};
            let hasEmptyProduct = false;
            let hasInvalidQty = false;
            let stockExceededProduct = null;

            rows.forEach(function (tr) {
                const select = tr.querySelector('.product-select');
                const qtyInput = tr.querySelector('.qty-input');
                const pid = select ? select.value : '';
                const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;

                if (!pid) {
                    hasEmptyProduct = true;
                } else if (seenProducts[pid]) {
                    e.preventDefault();
                    const product = findProduct(pid);
                    const name = product ? product.name : 'A product';
                    alert('Cannot save bill: "' + name + '" is added multiple times. Please combine into a single row.');
                    return;
                } else {
                    seenProducts[pid] = true;
                }

                if (qty <= 0) {
                    hasInvalidQty = true;
                }

                if (pid) {
                    const product = findProduct(pid);
                    if (product && qty > product.stock_qty) {
                        stockExceededProduct = {
                            name: product.name,
                            stock: product.stock_qty,
                            unit: product.unit,
                            qty: qty,
                            input: qtyInput
                        };
                    }
                }
            });

            if (hasEmptyProduct || hasInvalidQty) {
                e.preventDefault();
                alert('Please select a product and enter a valid quantity for every row.');
                return;
            }

            if (stockExceededProduct) {
                e.preventDefault();
                alert('Cannot generate invoice:\n\nQuantity for "' + stockExceededProduct.name +
                      '" (' + stockExceededProduct.qty + ' ' + stockExceededProduct.unit +
                      ') exceeds available stock (' + stockExceededProduct.stock + ' ' + stockExceededProduct.unit + ').\n\nPlease adjust the quantity.');
                if (stockExceededProduct.input) {
                    stockExceededProduct.input.focus();
                    stockExceededProduct.input.select();
                }
            }
        });
    }

    // Initialize with one empty row
    addRow();
    recalcTotals();
})();
