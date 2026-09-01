# Storage Sync API

API ini hanya digunakan oleh aplikasi file server lokal untuk menarik berkas lamaran dari hosting.

## Konfigurasi

Tambahkan pada `.env` hosting:

```ini
storageSync.clientId = 'manna-local-01'
storageSync.secret = 'SECRET_HEX_64_KARAKTER'
storageSync.clockSkewSeconds = 300
storageSync.nonceTtlSeconds = 600
storageSync.rateLimitPerMinute = 120
storageSync.pendingLimit = 100
storageSync.maxFileSize = 5242880
storageSync.requireHttps = true
```

Generate secret:

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Secret yang sama dipasang pada aplikasi lokal. Jangan simpan secret di source control atau log.

## Autentikasi HMAC

Setiap request mengirim:

```http
X-Sync-Client: manna-local-01
X-Sync-Timestamp: 1788242400
X-Sync-Nonce: 32-sampai-128-karakter-hex
X-Sync-Signature: hmac-sha256-hex
```

Canonical request:

```text
CLIENT_ID
HTTP_METHOD
URL_PATH
UNIX_TIMESTAMP
NONCE
SHA256_RAW_BODY
```

`URL_PATH` adalah path URL sebenarnya tanpa domain dan tanpa query string. Jika aplikasi dipasang pada subfolder, subfolder termasuk di dalam path. `RAW_BODY` harus berupa byte yang sama persis dengan body yang dikirim.

Signature:

```php
$signature = hash_hmac('sha256', $canonicalRequest, $secret);
```

Request ditolak apabila timestamp berbeda lebih dari lima menit, nonce sudah pernah digunakan, signature salah, koneksi bukan HTTPS di production, atau melewati rate limit.

## Endpoint

### `GET /api/storage/documents/pending`

Mengembalikan maksimal `storageSync.pendingLimit` dokumen `application_bundle` dengan status `pending`. File yang hilang, bukan PDF, berubah ukuran, atau berubah checksum tidak disertakan.

### `GET /api/storage/documents/{id}/download`

Mengembalikan PDF dengan header:

```http
Content-Type: application/pdf
Content-Length: 12345
X-Checksum-SHA256: checksum-hex
Cache-Control: private, no-store, max-age=0
```

### `POST /api/storage/documents/{id}/confirm`

Body JSON:

```json
{
  "sha256_checksum": "checksum-hex-64-karakter",
  "file_size": 12345,
  "downloaded_at": "2026-09-01T17:30:00+07:00"
}
```

Server menghitung ulang ukuran dan checksum file hosting. Status hanya berubah menjadi `confirmed` apabila keduanya sama. Endpoint bersifat idempotent selama file dan nilai konfirmasi tetap sama.

## Respons keamanan

- `401`: header, timestamp, atau signature tidak valid.
- `403`: HTTPS diwajibkan.
- `409`: nonce dipakai ulang atau integritas file hosting berubah.
- `422`: payload/checksum/ukuran konfirmasi tidak cocok.
- `429`: rate limit terlampaui.
- `503`: konfigurasi atau replay cache tidak tersedia.

Route API dikecualikan dari CSRF karena bukan dipanggil browser, tetapi tetap wajib melewati filter HMAC. Auto-routing aplikasi tetap nonaktif.
