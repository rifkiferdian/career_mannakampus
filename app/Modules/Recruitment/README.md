# Recruitment Module

Modul ini memisahkan fitur katalog rekrutmen dari controller halaman umum.

## Alur data

`Route -> VacancyController -> VacancyCatalogService -> Models -> VacancyPresenter -> View`

## Tanggung jawab

- `Controllers/`: menerima request, memilih response/view, dan mencatat kegagalan.
- `Services/`: menjalankan use-case dan menggabungkan data lintas model.
- `Models/`: seluruh query database dan konfigurasi tabel.
- `Presenters/`: mengubah data domain menjadi format yang dibutuhkan view.

View publik tetap berada di `app/Views` karena dipakai bersama halaman utama dan
modul rekrutmen.

## Debug

- Error katalog dicatat dengan prefix `[Recruitment]` di `writable/logs`.
- Pada environment `development`, exception dilempar kembali agar toolbar dan
  stack trace CodeIgniter tetap terlihat.
- Pada environment `production`, halaman tetap ditampilkan dengan katalog kosong.
- Jalankan test presenter dengan:

  `vendor/bin/phpunit tests/unit/Recruitment`
