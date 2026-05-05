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
            <p class="cp-info-box mb-0">Gunakan Address Book untuk mempercepat input penerima. Kamu juga bisa simpan penerima baru ke address book saat submit.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('customer.shipments.store') }}" enctype="multipart/form-data" class="cp-form">
        @csrf

        <div class="cp-card mb-4">
            <div class="cp-card-header"><h3 class="cp-section-title">A. Data Penerima</h3></div>
            <div class="cp-card-body">
                <div class="form-group">
                    <label>Mode Penerima</label>
                    <select name="receiver_mode" id="receiver_mode" class="custom-select" required>
                        <option value="address" {{ old('receiver_mode', 'address') === 'address' ? 'selected' : '' }}>Pilih dari Address Book</option>
                        <option value="new" {{ old('receiver_mode') === 'new' ? 'selected' : '' }}>Tambah Penerima Baru</option>
                    </select>
                </div>

                <div id="receiver_address_wrap" class="form-group">
                    <label>Address Book</label>
                    <select name="address_id" class="custom-select">
                        <option value="">- Pilih alamat tersimpan -</option>
                        @foreach ($addressBook as $addr)
                            <option value="{{ $addr->id }}" {{ (string) old('address_id') === (string) $addr->id ? 'selected' : '' }}>
                                {{ $addr->label }} - {{ $addr->receiver_name }} ({{ $addr->city }}) {{ $addr->is_default ? '[DEFAULT]' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="cp-muted-small mt-1">Belum punya alamat? <a href="{{ route('customer.addresses.create') }}">Tambah Address Book</a></div>
                </div>

                <div id="receiver_new_wrap">
                    <div class="row">
                        <div class="col-md-4 form-group"><label>Label Address Book (opsional)</label><input type="text" name="receiver_label" value="{{ old('receiver_label') }}" class="form-control" placeholder="Contoh: Rumah Orang Tua"></div>
                        <div class="col-md-4 form-group"><label>Nama Penerima</label><input type="text" name="receiver_name" value="{{ old('receiver_name') }}" class="form-control"></div>
                        <div class="col-md-4 form-group"><label>Email Penerima (opsional)</label><input type="email" name="receiver_email" value="{{ old('receiver_email') }}" class="form-control"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-7 form-group"><label>Alamat Penerima</label><input type="text" name="receiver_address" value="{{ old('receiver_address') }}" class="form-control"></div>
                        <div class="col-md-3 form-group"><label>Kota</label><input type="text" name="receiver_city" value="{{ old('receiver_city') }}" class="form-control"></div>
                        <div class="col-md-2 form-group"><label>Telepon</label><input type="text" name="receiver_phone" value="{{ old('receiver_phone') }}" class="form-control"></div>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="save_to_address_book" name="save_to_address_book" value="1" {{ old('save_to_address_book') ? 'checked' : '' }}>
                        <label class="form-check-label" for="save_to_address_book">Simpan penerima ini ke Address Book</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="cp-card mb-4">
            <div class="cp-card-header"><h3 class="cp-section-title">B. Data Pengiriman</h3></div>
            <div class="cp-card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Cabang Asal</label>
                        <select name="origin_branch_id" class="custom-select" required>
                            <option value="">- Pilih cabang asal -</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) old('origin_branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->city }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Cabang Tujuan</label>
                        <select name="destination_branch_id" class="custom-select" required>
                            <option value="">- Pilih cabang tujuan -</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) old('destination_branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->city }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Rate Ongkir</label>
                        <select name="rate_id" id="rate_id" class="custom-select" required>
                            <option value="">- Pilih rate -</option>
                            @foreach ($rates as $rate)
                                <option value="{{ $rate->id }}" data-price="{{ $rate->price_per_kg }}" data-days="{{ $rate->estimated_days }}" {{ (string) old('rate_id') === (string) $rate->id ? 'selected' : '' }}>
                                    {{ $rate->origin_city }} -> {{ $rate->destination_city }} (Rp {{ number_format($rate->price_per_kg, 0, ',', '.') }}/kg, {{ $rate->estimated_days }} hari)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Kurir</label>
                        <select name="courier_id" class="custom-select" required>
                            <option value="">- Pilih kurir -</option>
                            @foreach ($couriers as $courier)
                                <option value="{{ $courier->id }}" {{ (string) old('courier_id') === (string) $courier->id ? 'selected' : '' }}>{{ $courier->name }}</option>
                            @endforeach
                        </select>
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
    const modeSelect = document.getElementById('receiver_mode');
    const addressWrap = document.getElementById('receiver_address_wrap');
    const newWrap = document.getElementById('receiver_new_wrap');
    const rateInput = document.getElementById('rate_id');
    const addItemBtn = document.getElementById('add_item_row');
    const itemsWrapper = document.getElementById('items_wrapper');
    const costPreview = document.getElementById('cost_preview');

    function toggleReceiverFields() {
        const mode = modeSelect.value;
        addressWrap.style.display = mode === 'address' ? '' : 'none';
        newWrap.style.display = mode === 'new' ? '' : 'none';
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
        const selected = rateInput.options[rateInput.selectedIndex];
        const pricePerKg = selected ? parseFloat(selected.dataset.price || 0) : 0;
        const days = selected ? selected.dataset.days : '-';
        const items = collectItems();

        if (!items.length || !pricePerKg) {
            costPreview.textContent = 'Estimasi berat total dan ongkir akan tampil otomatis.';
            return;
        }

        const totalWeight = items.reduce((sum, item) => sum + (item.qty * item.weight), 0);
        const estimate = totalWeight * pricePerKg;

        costPreview.innerHTML = 'Total item: <strong>' + items.length + '</strong> | Total berat: <strong>' + totalWeight.toFixed(2) + ' kg</strong> | Estimasi ongkir: <strong>' + formatRupiah(estimate) + '</strong> | Estimasi tiba: <strong>' + days + ' hari</strong>';
    }

    function bindRowEvents(row) {
        row.querySelector('.remove-item').addEventListener('click', function () {
            if (itemsWrapper.querySelectorAll('.item-row').length === 1) {
                return;
            }
            row.remove();
            updatePreview();
        });

        row.querySelectorAll('.item-qty, .item-weight').forEach((input) => {
            input.addEventListener('input', updatePreview);
        });
    }

    let itemIndex = 0;
    function addRow(item = {}) {
        itemsWrapper.insertAdjacentHTML('beforeend', itemRowTemplate(itemIndex++, item));
        bindRowEvents(itemsWrapper.lastElementChild);
        updatePreview();
    }

    addItemBtn.addEventListener('click', function () {
        addRow({ qty: 1, weight: 1 });
    });

    modeSelect.addEventListener('change', toggleReceiverFields);
    rateInput.addEventListener('change', updatePreview);

    const oldNames = @json(old('item_name', []));
    const oldQty = @json(old('quantity', []));
    const oldWeight = @json(old('weight', []));
    const hasOldRows = Array.isArray(oldNames) && oldNames.length > 0;

    if (hasOldRows) {
        oldNames.forEach((name, idx) => {
            addRow({
                name: name || '',
                qty: oldQty[idx] || 1,
                weight: oldWeight[idx] || 1
            });
        });
    } else {
        addRow({ qty: 1, weight: 1 });
    }

    toggleReceiverFields();
})();
</script>
@endpush
