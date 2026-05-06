@extends('fe.master')

@section('content')
<div class="container">
    <div class="cp-card mb-4">
        <div class="cp-card-header d-flex justify-content-between align-items-center">
            <h2 class="cp-section-title">Buat Shipment Baru</h2>
            <a href="{{ route('customer.shipments.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
        <div class="cp-card-body">
            <div class="cp-stepper">
                <div class="cp-step">1. Pilih penerima</div>
                <div class="cp-step">2. Tentukan rute</div>
                <div class="cp-step">3. Isi multi-item</div>
                <div class="cp-step">4. Submit order</div>
            </div>
            <p class="cp-info-box mb-0">Gunakan Address Book untuk mempercepat input penerima, lalu sistem akan memilih tarif dan kurir otomatis berdasarkan rute cabang.</p>
            <div id="shipment_draft_notice" class="cp-muted-small mt-2"></div>
        </div>
    </div>

    <form method="POST" action="{{ route('customer.shipments.store') }}" enctype="multipart/form-data" class="cp-form">
        @csrf

        <div class="cp-card mb-4">
            <div class="cp-card-header"><h3 class="cp-section-title">A. Data Penerima</h3></div>
            <div class="cp-card-body">
                <input type="hidden" name="receiver_mode" value="address">

                <div id="receiver_address_wrap" class="form-group">
                    <label>Address Book</label>
                    <select name="address_id" class="custom-select" required>
                        <option value="">- Pilih alamat tersimpan -</option>
                        @foreach ($addressBook as $addr)
                            <option value="{{ $addr->id }}" {{ (string) old('address_id') === (string) $addr->id ? 'selected' : '' }}>
                                {{ $addr->label }} - {{ $addr->receiver_name }} ({{ $addr->city }}) {{ $addr->is_default ? '[DEFAULT]' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="cp-muted-small mt-1">Belum punya alamat? <a href="{{ route('customer.addresses.create') }}">Tambah Address Book</a></div>
                </div>
            </div>
        </div>

        <div class="cp-card mb-4">
            <div class="cp-card-header"><h3 class="cp-section-title">B. Data Pengiriman</h3></div>
            <div class="cp-card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Cabang Asal</label>
                        <select name="origin_branch_id" id="origin_branch_id" class="custom-select" required>
                            <option value="">- Pilih cabang asal -</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) old('origin_branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->city }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Cabang Tujuan</label>
                        <select name="destination_branch_id" id="destination_branch_id" class="custom-select" required>
                            <option value="">- Pilih cabang tujuan -</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) old('destination_branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->city }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group mb-2">
                    <button type="button" id="unlock_route_btn" class="btn btn-sm btn-outline-secondary" style="display:none;">Ubah Cabang</button>
                </div>
                <input type="hidden" id="origin_branch_hidden" value="">
                <input type="hidden" id="destination_branch_hidden" value="">

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Rate Ongkir</label>
                        <input type="hidden" name="rate_id" id="rate_id" value="{{ old('rate_id') }}">
                        <input type="text" id="rate_preview" class="form-control" value="" placeholder="Otomatis dari rute cabang" readonly>
                        <div class="cp-muted-small mt-1" id="rate_helper">Pilih cabang asal dan tujuan untuk menghitung ongkir otomatis.</div>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Kurir</label>
                        <input type="hidden" name="courier_id" id="courier_id" value="{{ old('courier_id') }}">
                        <input type="text" id="courier_preview" class="form-control" value="" placeholder="Otomatis dari cabang asal" readonly>
                        <div class="cp-muted-small mt-1">Courier pickup dipilih otomatis sesuai cabang asal.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Total Berat</label>
                        <input type="text" id="total_weight_preview" class="form-control" value="" placeholder="Otomatis dari item" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Total Ongkir</label>
                        <input type="text" id="total_price_preview" class="form-control" value="" placeholder="Otomatis dari berat dan rute" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Estimasi Tiba</label>
                        <input type="text" id="estimated_days_preview" class="form-control" value="" placeholder="Otomatis dari rate" readonly>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 form-group"><label>Tanggal Kirim</label><input type="date" name="shipment_date" value="{{ old('shipment_date', now()->format('Y-m-d')) }}" class="form-control" required></div>
                    <div class="col-md-4 form-group"><label>Foto Paket (opsional)</label><input type="file" name="shipment_photo" class="form-control"></div>
                </div>
            </div>
        </div>

        <div class="cp-card mb-4">
            <div class="cp-card-header d-flex justify-content-between align-items-center">
                <h3 class="cp-section-title">C. Multi Item Kiriman</h3>
                <button type="button" id="add_item_row" class="btn btn-sm btn-outline-primary">+ Tambah Item</button>
            </div>
            <div class="cp-card-body">
                <div id="items_wrapper"></div>
                <div class="cp-info-box" id="cost_preview">Estimasi berat total dan ongkir akan tampil otomatis.</div>
            </div>
        </div>

        <div class="d-flex flex-wrap" style="gap:10px;">
            <button type="submit" class="btn btn-primary btn-lg">Simpan Shipment</button>
            <a href="{{ route('customer.shipments.index') }}" class="btn btn-outline-secondary btn-lg">Batal</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const routeOptions = @json($routeOptions);
    const couriersByBranch = @json($couriersByBranch);
    const hasServerOldInput = @json(!empty(session()->getOldInput()));
    const draftStorageKey = 'customer-shipment-draft:{{ Auth::guard('customer')->id() }}';

    const shipmentForm = document.querySelector('form.cp-form');
    const addressInput = shipmentForm.querySelector('select[name="address_id"]');
    const shipmentDateInput = shipmentForm.querySelector('input[name="shipment_date"]');
    const draftNotice = document.getElementById('shipment_draft_notice');
    const originInput = document.getElementById('origin_branch_id');
    const destinationInput = document.getElementById('destination_branch_id');
    const originHidden = document.getElementById('origin_branch_hidden');
    const destinationHidden = document.getElementById('destination_branch_hidden');
    const unlockRouteBtn = document.getElementById('unlock_route_btn');

    const rateInput = document.getElementById('rate_id');
    const ratePreview = document.getElementById('rate_preview');
    const rateHelper = document.getElementById('rate_helper');
    const courierInput = document.getElementById('courier_id');
    const courierPreview = document.getElementById('courier_preview');
    const totalWeightPreview = document.getElementById('total_weight_preview');
    const totalPricePreview = document.getElementById('total_price_preview');
    const estimatedDaysPreview = document.getElementById('estimated_days_preview');
    const addItemBtn = document.getElementById('add_item_row');
    const itemsWrapper = document.getElementById('items_wrapper');
    const costPreview = document.getElementById('cost_preview');
    let activeRoute = null;
    let isSubmitting = false;

    function setDraftNotice(message) {
        draftNotice.textContent = message || '';
    }

    function loadDraft() {
        if (hasServerOldInput) {
            return null;
        }

        try {
            const stored = window.localStorage.getItem(draftStorageKey);
            return stored ? JSON.parse(stored) : null;
        } catch (error) {
            return null;
        }
    }

    function saveDraft() {
        const draft = {
            address_id: addressInput.value || '',
            origin_branch_id: originInput.value || '',
            destination_branch_id: destinationInput.value || '',
            shipment_date: shipmentDateInput.value || '',
            items: Array.from(itemsWrapper.querySelectorAll('.item-row')).map((row) => ({
                name: row.querySelector('input[name="item_name[]"]').value || '',
                qty: row.querySelector('input[name="quantity[]"]').value || 1,
                weight: row.querySelector('input[name="weight[]"]').value || 1,
            })),
        };

        window.localStorage.setItem(draftStorageKey, JSON.stringify(draft));
        setDraftNotice('Draft shipment tersimpan sementara di browser ini.');
    }

    function clearDraft() {
        window.localStorage.removeItem(draftStorageKey);
        setDraftNotice('');
    }

    function restoreDraftValue(input, value) {
        if (!input || value === undefined || value === null || value === '') {
            return;
        }

        const optionExists = Array.from(input.options || []).some((option) => option.value === String(value));
        if (!optionExists && input.tagName === 'SELECT') {
            return;
        }

        input.value = String(value);
    }

    function getRouteKey() {
        if (!originInput.value || !destinationInput.value) {
            return null;
        }
        return originInput.value + '-' + destinationInput.value;
    }

    function syncBranchOptions() {
        if (originInput.value && destinationInput.value && originInput.value === destinationInput.value) {
            destinationInput.value = '';
        }

        const activeOrigin = originInput.value;
        const activeDestination = destinationInput.value;

        Array.from(originInput.options).forEach((option) => {
            option.disabled = option.value !== '' && option.value === activeDestination;
        });

        Array.from(destinationInput.options).forEach((option) => {
            option.disabled = option.value !== '' && option.value === activeOrigin;
        });
    }

    function clearAutoFields(message) {
        activeRoute = null;
        rateInput.value = '';
        courierInput.value = '';
        ratePreview.value = '';
        courierPreview.value = '';
        totalWeightPreview.value = '';
        totalPricePreview.value = '';
        estimatedDaysPreview.value = '';
        rateHelper.textContent = message || 'Pilih cabang asal dan tujuan untuk menghitung ongkir otomatis.';
        updatePreview();
    }

    function lockRouteSelection() {
        originInput.disabled = true;
        destinationInput.disabled = true;
        originHidden.name = 'origin_branch_id';
        destinationHidden.name = 'destination_branch_id';
        originHidden.value = originInput.value;
        destinationHidden.value = destinationInput.value;
        unlockRouteBtn.style.display = '';
    }

    function unlockRouteSelection() {
        originInput.disabled = false;
        destinationInput.disabled = false;
        originHidden.name = '';
        destinationHidden.name = '';
        originHidden.value = '';
        destinationHidden.value = '';
        unlockRouteBtn.style.display = 'none';
    }

    function syncAutoRouteAndCourier(lockWhenValid) {
        const originValue = originInput.value;
        const destinationValue = destinationInput.value;

        if (!originValue || !destinationValue) {
            clearAutoFields('Pilih cabang asal dan tujuan untuk menghitung ongkir otomatis.');
            return;
        }

        if (originValue === destinationValue) {
            clearAutoFields('Cabang asal dan tujuan tidak boleh sama.');
            return;
        }

        const route = routeOptions[getRouteKey()];
        if (!route) {
            clearAutoFields('Rute cabang ini belum punya tarif ongkir.');
            return;
        }

        const branchCouriers = couriersByBranch[originValue] || [];
        if (!branchCouriers.length) {
            clearAutoFields('Belum ada kurir yang terdaftar di cabang asal.');
            return;
        }

        const courier = branchCouriers[0];
        activeRoute = route;
        rateInput.value = String(route.rate_id);
        courierInput.value = String(courier.id);
        ratePreview.value = 'Rp ' + Number(route.price_per_kg).toLocaleString('id-ID') + '/kg | Estimasi ' + route.estimated_days + ' hari';
        courierPreview.value = courier.name;
        rateHelper.textContent = route.origin_city + ' -> ' + route.destination_city;

        if (lockWhenValid) {
            lockRouteSelection();
        }

        updatePreview();
    }

    function formatRupiah(value) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
    }

    function itemRowTemplate(index, item = {}) {
        return `
            <div class="border rounded p-3 mb-3 item-row" data-index="${index}">
                <div class="row">
                    <div class="col-md-4 form-group mb-2">
                        <label>Nama Item</label>
                        <input type="text" name="item_name[]" value="${item.name || ''}" class="form-control" required>
                    </div>
                    <div class="col-md-2 form-group mb-2">
                        <label>Qty</label>
                        <input type="number" min="1" name="quantity[]" value="${item.qty || 1}" class="form-control item-qty" required>
                    </div>
                    <div class="col-md-2 form-group mb-2">
                        <label>Berat / Item (kg)</label>
                        <input type="number" min="0.01" step="0.01" name="weight[]" value="${item.weight || 1}" class="form-control item-weight" required>
                    </div>
                    <div class="col-md-3 form-group mb-2">
                        <label>Foto Item (opsional)</label>
                        <input type="file" name="item_photo[]" class="form-control">
                    </div>
                    <div class="col-md-1 d-flex align-items-end mb-2">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-item">X</button>
                    </div>
                </div>
            </div>
        `;
    }

    function collectItems() {
        const rows = itemsWrapper.querySelectorAll('.item-row');
        return Array.from(rows).map((row) => {
            return {
                qty: parseFloat(row.querySelector('.item-qty').value || 0),
                weight: parseFloat(row.querySelector('.item-weight').value || 0)
            };
        });
    }

    function updatePreview() {
        const pricePerKg = activeRoute ? parseFloat(activeRoute.price_per_kg || 0) : 0;
        const days = activeRoute ? activeRoute.estimated_days : '-';
        const items = collectItems();

        if (!items.length || !pricePerKg) {
            totalWeightPreview.value = items.length ? items.reduce((sum, item) => sum + (item.qty * item.weight), 0).toFixed(2) + ' kg' : '';
            totalPricePreview.value = '';
            estimatedDaysPreview.value = activeRoute ? activeRoute.estimated_days + ' hari' : '';
            costPreview.textContent = 'Estimasi berat total dan ongkir akan tampil otomatis.';
            return;
        }

        const totalWeight = items.reduce((sum, item) => sum + (item.qty * item.weight), 0);
        const estimate = totalWeight * pricePerKg;

        totalWeightPreview.value = totalWeight.toFixed(2) + ' kg';
        totalPricePreview.value = formatRupiah(estimate);
        estimatedDaysPreview.value = days + ' hari';
        costPreview.innerHTML = 'Total item: <strong>' + items.length + '</strong> | Total berat: <strong>' + totalWeight.toFixed(2) + ' kg</strong> | Estimasi ongkir: <strong>' + formatRupiah(estimate) + '</strong> | Estimasi tiba: <strong>' + days + ' hari</strong>';
    }

    function bindRowEvents(row) {
        row.querySelector('.remove-item').addEventListener('click', function () {
            if (itemsWrapper.querySelectorAll('.item-row').length === 1) {
                return;
            }
            row.remove();
            updatePreview();
            saveDraft();
        });

        row.querySelectorAll('.item-qty, .item-weight').forEach((input) => {
            input.addEventListener('input', function () {
                updatePreview();
                saveDraft();
            });
        });

        row.querySelector('input[name="item_name[]"]').addEventListener('input', saveDraft);
    }

    let itemIndex = 0;
    function addRow(item = {}) {
        itemsWrapper.insertAdjacentHTML('beforeend', itemRowTemplate(itemIndex++, item));
        bindRowEvents(itemsWrapper.lastElementChild);
        updatePreview();
    }

    addItemBtn.addEventListener('click', function () {
        addRow({ qty: 1, weight: 1 });
        saveDraft();
    });

    originInput.addEventListener('change', function () {
        unlockRouteSelection();
        syncBranchOptions();
        syncAutoRouteAndCourier(true);
    });
    destinationInput.addEventListener('change', function () {
        unlockRouteSelection();
        syncBranchOptions();
        syncAutoRouteAndCourier(true);
    });
    unlockRouteBtn.addEventListener('click', function () {
        unlockRouteSelection();
        syncBranchOptions();
        clearAutoFields('Silakan ubah cabang asal/tujuan, lalu tarif dan kurir akan dipilih otomatis lagi.');
        saveDraft();
    });

    shipmentForm.addEventListener('input', function (event) {
        if (event.target.type === 'file') {
            return;
        }

        saveDraft();
    });

    shipmentForm.addEventListener('change', function (event) {
        if (event.target.type === 'file') {
            return;
        }

        saveDraft();
    });

    shipmentForm.addEventListener('submit', function () {
        isSubmitting = true;
        clearDraft();
    });

    window.addEventListener('beforeunload', function () {
        if (isSubmitting) {
            return;
        }

        saveDraft();
    });

    const oldNames = @json(old('item_name', []));
    const oldQty = @json(old('quantity', []));
    const oldWeight = @json(old('weight', []));
    const hasOldRows = Array.isArray(oldNames) && oldNames.length > 0;
    const savedDraft = loadDraft();

    if (savedDraft) {
        restoreDraftValue(addressInput, savedDraft.address_id);
        restoreDraftValue(originInput, savedDraft.origin_branch_id);
        restoreDraftValue(destinationInput, savedDraft.destination_branch_id);
        if (savedDraft.shipment_date) {
            shipmentDateInput.value = savedDraft.shipment_date;
        }
        setDraftNotice('Draft shipment sebelumnya dipulihkan otomatis.');
    }

    if (hasOldRows) {
        oldNames.forEach((name, idx) => {
            addRow({
                name: name || '',
                qty: oldQty[idx] || 1,
                weight: oldWeight[idx] || 1
            });
        });
    } else if (savedDraft && Array.isArray(savedDraft.items) && savedDraft.items.length > 0) {
        savedDraft.items.forEach((item) => {
            addRow({
                name: item.name || '',
                qty: item.qty || 1,
                weight: item.weight || 1
            });
        });
    } else {
        addRow({ qty: 1, weight: 1 });
    }

    if (originInput.value && destinationInput.value) {
        syncBranchOptions();
        syncAutoRouteAndCourier(true);
    } else {
        syncBranchOptions();
        clearAutoFields();
    }

    if (!hasServerOldInput && !savedDraft) {
        setDraftNotice('Draft shipment akan tersimpan sementara jika Anda pindah halaman.');
    }

})();
</script>
@endpush
