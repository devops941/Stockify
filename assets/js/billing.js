(function () {
    'use strict';

    const itemsBody     = document.getElementById('itemsBody');
    const addRowBtn      = document.getElementById('addRowBtn');
    const customerSelect = document.getElementById('customerSelect');
    const billForm       = document.getElementById('billForm');

    let rowSeq = 0;

    function productOptions(selectedId) {
        let html = '<option value="">-- Select Product --</option>';
        window.PRODUCTS.forEach(function (p) {
            const selected = String(p.product_id) === String(selectedId) ? 'selected' : '';
            html += '<option value="' + p.product_id + '" ' + selected + '>' +
                p.name + ' (Stock: ' + p.stock_qty + ' ' + p.unit + ')</option>';
        });
        return html;
    }

    function addRow() {
        rowSeq++;
        const tr = document.createElement('tr');
        tr.dataset.row = rowSeq;
        tr.innerHTML =
            '<td><select class="form-select form-select-sm product-select" name="product_id[]" required>' + productOptions('') + '</select></td>' +
            '<td class="hsn-cell text-muted">-</td>' +
            '<td><input type="number" class="form-control form-control-sm qty-input" name="qty[]" value="1" min="0.01" step="0.01" required></td>' +
            '<td class="text-end rate-cell">0.00</td>' +
            '<td class="text-end taxable-cell">0.00</td>' +
            '<td class="text-end gstrate-cell">0</td>' +
            '<td class="text-end gstamt-cell">0.00</td>' +
            '<td class="text-end linetotal-cell fw-semibold">0.00</td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x-lg"></i></button></td>';
        itemsBody.appendChild(tr);
        bindRow(tr);
    }

    function bindRow(tr) {
        const select = tr.querySelector('.product-select');
        const qty    = tr.querySelector('.qty-input');
        select.addEventListener('change', function () { recalcRow(tr); });
        qty.addEventListener('input', function () { recalcRow(tr); });
        tr.querySelector('.remove-row').addEventListener('click', function () {
            tr.remove();
            recalcTotals();
        });
    }

    function findProduct(id) {
        return window.PRODUCTS.find(function (p) { return String(p.product_id) === String(id); });
    }

    function recalcRow(tr) {
        const productId = tr.querySelector('.product-select').value;
        const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
        const product = findProduct(productId);

        if (!product) {
            tr.querySelector('.hsn-cell').textContent = '-';
            tr.querySelector('.rate-cell').textContent = '0.00';
            tr.querySelector('.taxable-cell').textContent = '0.00';
            tr.querySelector('.gstrate-cell').textContent = '0';
            tr.querySelector('.gstamt-cell').textContent = '0.00';
            tr.querySelector('.linetotal-cell').textContent = '0.00';
            recalcTotals();
            return;
        }

        const rate = parseFloat(product.selling_price);
        const gstRate = parseFloat(product.gst_rate);
        const taxable = qty * rate;
        const gstAmt = taxable * gstRate / 100;
        const lineTotal = taxable + gstAmt;

        tr.querySelector('.hsn-cell').textContent = product.hsn_code;
        tr.querySelector('.rate-cell').textContent = rate.toFixed(2);
        tr.querySelector('.taxable-cell').textContent = taxable.toFixed(2);
        tr.querySelector('.gstrate-cell').textContent = gstRate;
        tr.querySelector('.gstamt-cell').textContent = gstAmt.toFixed(2);
        tr.querySelector('.linetotal-cell').textContent = lineTotal.toFixed(2);

        recalcTotals();
    }

    function isIntraState() {
        const opt = customerSelect.options[customerSelect.selectedIndex];
        const custState = opt ? (opt.getAttribute('data-state') || '') : '';
        return custState.trim().toLowerCase() === window.SHOP_STATE.trim().toLowerCase();
    }

    function recalcTotals() {
        let taxable = 0, gst = 0;
        itemsBody.querySelectorAll('tr').forEach(function (tr) {
            taxable += parseFloat(tr.querySelector('.taxable-cell').textContent) || 0;
            gst     += parseFloat(tr.querySelector('.gstamt-cell').textContent) || 0;
        });

        const intra = isIntraState();
        const cgst = intra ? gst / 2 : 0;
        const sgst = intra ? gst / 2 : 0;
        const igst = intra ? 0 : gst;

        const rawTotal = taxable + gst;
        const grandTotal = Math.round(rawTotal);
        const roundOff = grandTotal - rawTotal;

        document.getElementById('sumTaxable').textContent = taxable.toFixed(2);
        document.getElementById('sumCgst').textContent = cgst.toFixed(2);
        document.getElementById('sumSgst').textContent = sgst.toFixed(2);
        document.getElementById('sumIgst').textContent = igst.toFixed(2);
        document.getElementById('sumRound').textContent = roundOff.toFixed(2);
        document.getElementById('sumGrand').textContent = '₹ ' + grandTotal.toFixed(2);

        document.getElementById('rowCgst').style.display = intra ? '' : 'none';
        document.getElementById('rowSgst').style.display = intra ? '' : 'none';
        document.getElementById('rowIgst').style.display = intra ? 'none' : '';

        const hint = document.getElementById('taxTypeHint');
        if (customerSelect.value) {
            hint.textContent = intra
                ? 'Same state as shop → CGST + SGST applies.'
                : 'Different state from shop → IGST applies.';
        } else {
            hint.textContent = 'Select a customer to see tax type.';
        }
    }

    addRowBtn.addEventListener('click', addRow);
    customerSelect.addEventListener('change', recalcTotals);

    billForm.addEventListener('submit', function (e) {
        const rows = itemsBody.querySelectorAll('tr');
        if (rows.length === 0) {
            e.preventDefault();
            alert('Please add at least one item to the bill.');
            return;
        }
        let valid = true;
        rows.forEach(function (tr) {
            if (!tr.querySelector('.product-select').value || !(parseFloat(tr.querySelector('.qty-input').value) > 0)) {
                valid = false;
            }
        });
        if (!valid) {
            e.preventDefault();
            alert('Please select a product and a valid quantity for every row.');
        }
    });

    // Start with one empty row
    addRow();
    recalcTotals();
})();
