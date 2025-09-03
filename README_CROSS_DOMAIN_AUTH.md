# Cross-Domain Authentication System

Sistem ini memungkinkan transfer autentikasi dari domain parent (aninkafashion.com) ke domain child (aninkafashion.evennode.com) menggunakan cookies Sanctum.

## Cara Kerja

1. **Parent Domain** (aninkafashion.com):
   - User login dan mendapatkan cookies `laravel_session` dan `XSRF-TOKEN`
   - Cookies disimpan di browser

2. **Transfer Process**:
   - Parent domain mengambil cookies dari request
   - Redirect ke child domain dengan cookies yang sama
   - Child domain memvalidasi cookies dan mengautentikasi user

3. **Child Domain** (aninkafashion.evennode.com):
   - Menerima cookies dari parent
   - Memvalidasi session dan mengautentikasi user
   - User dapat mengakses API protected

## Endpoints

### Parent Domain (aninkafashion.com)
- `GET /api/auth/transfer?target_url=<child_domain>`
  - Mengambil cookies dari parent domain
  - Redirect ke child domain dengan cookies

### Child Domain (aninkafashion.evennode.com)
- `GET /api/auth/validate-transfer`
  - Memvalidasi cookies yang diterima
  - Mengautentikasi user
- `GET /api/auth/check`
  - Mengecek status autentikasi

## Penggunaan

### Dari Parent Domain
```javascript
// Transfer auth ke child domain
await this.transferAuth('https://aninkafashion.evennode.com');
```

### Dari Child Domain
```javascript
// Validasi transfer
const result = await this.validateTransfer();

// Cek status auth
const authStatus = await this.checkAuth();
```

## Konfigurasi

### .env (Parent Domain)
```env
APP_URL=https://aninkafashion.com
SESSION_DOMAIN=.aninkafashion.com
SANCTUM_STATEFUL_DOMAINS=aninkafashion.com,aninkafashion.evennode.com
```

### .env (Child Domain)
```env
APP_URL=https://aninkafashion.evennode.com
SESSION_DOMAIN=.aninkafashion.com
SANCTUM_STATEFUL_DOMAINS=aninkafashion.com,aninkafashion.evennode.com
```

### config/sanctum.php
```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    Sanctum::currentApplicationUrlWithPort(),
))),
```

## Alur Lengkap

1. User login di aninkafashion.com
2. Cookies `laravel_session` dan `XSRF-TOKEN` tersimpan
3. User klik link ke aninkafashion.evennode.com
4. Parent domain transfer cookies via `/api/auth/transfer`
5. Browser redirect ke child domain dengan cookies
6. Child domain validasi cookies via `/api/auth/validate-transfer`
7. User terautentikasi di child domain
8. User dapat akses API protected

## Keamanan

- Hanya domain yang diizinkan yang dapat menerima transfer
- Session di-decode dan divalidasi
- CSRF token divalidasi
- User ID divalidasi di database

## Troubleshooting

### Cookies tidak ter-transfer
- Pastikan `SESSION_DOMAIN` sama di kedua domain
- Pastikan `SANCTUM_STATEFUL_DOMAINS` mencakup kedua domain
- Cek browser console untuk error CORS

### Session tidak valid
- Pastikan user masih login di parent domain
- Cek apakah session belum expired
- Pastikan database user masih ada

### CORS Error
- Pastikan CORS dikonfigurasi untuk kedua domain
- Pastikan `supports_credentials` = true
- Cek browser console untuk detail error
