@extends('fe.master')

@section('content')
<div class="container">
    <div class="cp-card mb-4">
        <div class="cp-card-header d-flex flex-wrap justify-content-between align-items-center" style="gap:10px;">
            <h2 class="cp-section-title">Address Book</h2>
            <a href="{{ route('customer.addresses.create') }}" class="btn btn-primary">+ Tambah Alamat</a>
        </div>
        <div class="cp-card-body p-0">
            <div class="table-responsive">
                <table class="table cp-table mb-0">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Penerima</th>
                            <th>Alamat</th>
                            <th>Kontak</th>
                            <th>Default</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($addresses as $address)
                            <tr>
                                <td>{{ $address->label }}</td>
                                <td>
                                    <div>{{ $address->receiver_name }}</div>
                                    <div class="cp-muted-small">{{ $address->receiver_email ?? 'Tanpa email' }}</div>
                                </td>
                                <td>{{ $address->address }}, {{ $address->city }}</td>
                                <td>{{ $address->receiver_phone }}</td>
                                <td>{!! $address->is_default ? '<span class="cp-badge delivered">DEFAULT</span>' : '-' !!}</td>
                                <td class="text-right">
                                    <a href="{{ route('customer.addresses.edit', $address) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="POST" action="{{ route('customer.addresses.destroy', $address) }}" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus alamat ini?')">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4">Belum ada alamat penerima tersimpan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $addresses->links() }}</div>
</div>
@endsection
