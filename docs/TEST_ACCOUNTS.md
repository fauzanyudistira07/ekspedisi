# Akun Demo per Role

Gunakan akun berikut untuk pengecekan login.

## Login Staff Internal (`/login-admin`)

- Role `admin`
- Email: `admin@ekspedisi.test`
- Password: `password123`

- Role `manager`
- Email: `manager@ekspedisi.test`
- Password: `password123`

- Role `cashier`
- Email: `cashier@ekspedisi.test`
- Password: `password123`

- Role `courier`
- Email: `courier@ekspedisi.test`
- Password: `password123`

- Role `courier`
- Email: `courier.bdg@ekspedisi.test`
- Password: `password123`

## Login Customer (`/login-customer`)

- Role `customer`
- Email: `customer@ekspedisi.test`
- Password: `password123`

## Cara Generate Ulang Akun

```bash
php artisan db:seed
```

## Data Master Awal

- Branch: `Pusat Jakarta`
- Branch: `Cabang Bandung`
- Rate: `Jakarta -> Bandung`
- Rate: `Bandung -> Jakarta`
