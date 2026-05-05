# Role Feature Matrix - Ekspedisi Online

Dokumen ini adalah baseline fitur minimal dan hak akses untuk seluruh role pada sistem.
UI tetap mengikuti template yang saat ini sudah ada, jadi fokus dokumen ini adalah alur bisnis dan kontrol akses.

## 1. Role Aktif

- `admin`
- `manager`
- `cashier`
- `courier`
- `customer`

## 2. Login dan Guard

- `web` guard: `admin`, `manager`, `cashier`, `courier` (tabel `users`)
- `customer` guard: `customer` (tabel `customers`)

## 3. Akses Data per Role (Ringkas)

- `admin`: akses penuh semua tabel operasional
- `manager`: read mayoritas data + update terbatas untuk keputusan operasional
- `cashier`: fokus pembayaran, invoice, dan validasi shipment siap bayar
- `courier`: fokus assignment pengiriman + update tracking
- `customer`: buat order, lihat tracking, kelola profil sendiri, dan pembayaran order sendiri

Detail CRUD ada di `config/role_feature_matrix.php`.

## 4. Modul Fitur Wajib (Public-Ready)

- Auth:
- login customer
- login staff internal
- logout per guard
- Master Data:
- cabang (`branches`)
- tarif (`rates`)
- kendaraan (`vehicles`)
- user staff (`users`)
- Customer:
- registrasi
- profil customer
- Shipment:
- buat shipment
- item kiriman
- assign kurir
- status shipment
- Tracking:
- update tracking internal
- tracking publik berdasarkan resi
- Payment:
- create payment
- update status payment
- bukti/invoice pembayaran
- Dashboard dan Laporan:
- KPI operasional
- KPI pembayaran
- performa kurir/cabang

## 5. Rule Implementasi Backend

- Semua route internal wajib pakai middleware `auth` + `role`
- Semua route customer wajib pakai middleware `auth:customer`
- Query data harus dibatasi sesuai role:
- courier hanya boleh lihat shipment miliknya
- customer hanya boleh lihat shipment yang berkaitan dengan dirinya (sender/receiver)
- cashier hanya boleh ubah payment dan status shipment yang terkait pembayaran

## 6. Catatan Penting Pengembangan

- Template UI tidak diubah total, hanya isi modul mengikuti matrix role.
- Untuk role customer, autentikasi dipisah dari `users` dan tetap menggunakan tabel `customers`.
- Role `customer` tetap dianggap role sistem, tetapi penyimpanan user-nya ada di guard/tabel terpisah.
