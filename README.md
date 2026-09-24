# Mini Project Pemrograman Web - Product Manager

Aplikasi web sederhana berbasis PHP dan MySQL untuk mengelola katalog produk inventaris. Proyek ini dibuat untuk memenuhi tugas Mini Project Praktikum Pemrograman Web (Pertemuan 3).

Aplikasi ini fokus pada pemahaman konsep dasar pengembangan web: operasi CRUD, validasi input di server, keamanan basis data dengan PDO, pengamanan tampilan dari XSS, penggunaan token CSRF, dan penerapan alur Post-Redirect-Get (PRG) agar data tidak terduplikasi saat halaman di-refresh.

---

## 1. Cara Menjalankan Aplikasi

### Menggunakan XAMPP atau Laragon

1. Salin folder proyek ini ke dalam direktori server web lokal:
   - XAMPP: letakkan di folder `htdocs/ProductManager`
   - Laragon: letakkan di folder `www/ProductManager`

2. Buat basis data dan impor tabel:
   - Buka phpMyAdmin di browser (`http://localhost/phpmyadmin`).
   - Buat database baru dengan nama `store_db`.
   - Pilih menu Import, lalu pilih berkas `database/store_db.sql`.
   - Klik Kirim / Import.
   - Alternatif lewat command line:
     ```bash
     mysql -u root -p store_db < database/store_db.sql
     ```

3. Sesuaikan konfigurasi database jika diperlukan:
   - Buka berkas `config/database.php`.
   - Periksa pengaturan host, nama database, username, dan password. Default yang digunakan adalah host `127.0.0.1`, port `3306`, user `root`, dan password kosong atau `root`.

4. Buka aplikasi di browser:
   - Akses alamat: `http://localhost/ProductManager/`

### Menggunakan PHP Built-in Server

Jika ingin menjalankan aplikasi langsung tanpa Apache:

1. Pastikan PHP dan ekstensi `pdo_mysql` sudah aktif.
2. Buka terminal di folder proyek ini, lalu jalankan:
   ```bash
   php -S 127.0.0.1:8000
   ```
3. Buka browser dan masuk ke: `http://127.0.0.1:8000`

---

## 2. Fitur Aplikasi

### Fitur Utama (CRUD)
- **Tambah Produk (Create):** Menambahkan data produk baru yang terdiri dari nama, kategori, harga, kuantitas stok, deskripsi, dan foto produk opsional.
- **Lihat Produk (Read):** Menampilkan daftar produk dalam bentuk kartu responsif yang rapi. Terdapat informasi harga, status ketersediaan stok, dan tombol aksi.
- **Ubah Produk (Update):** Memperbarui data produk berdasarkan ID. Formulir otomatis terisi dengan data produk yang sedang diedit. Pengguna juga bisa mengganti foto produk yang sudah ada.
- **Hapus Produk (Delete):** Menghapus produk dari database. Proses hapus menggunakan metode POST dan dilindungi token CSRF agar tidak bisa dijalankan sembarangan lewat URL biasa.

### Fitur Tambahan (Bonus)
- **Pencarian dan Filter:** Pencarian produk berdasarkan nama, kategori, atau deskripsi, serta filter kategori cepat. Kueri pencarian menggunakan parameter binding PDO yang aman.
- **Paginasi:** Pembagian tampilan produk per halaman dengan tetap mempertahankan parameter pencarian yang sedang aktif.
- **Upload Gambar Aman:** Berkas gambar dibatasi maksimal 2MB dengan format JPG, JPEG, PNG, atau WEBP. Tipe berkas diperiksa di sisi server menggunakan Fileinfo (bukan hanya melihat nama ekstensi). Nama berkas diacak untuk menghindari tabrakan nama file.
- **Interaktivitas:**
  - Shortcut keyboard: tekan tombol `/` untuk langsung mencari produk, tekan `n` untuk membuka form tambah produk, tekan `k` untuk kembali ke katalog, dan tekan `Esc` untuk menutup jendela modal.
  - Pencarian langsung: kartu produk akan tersaring secara otomatis saat pengguna mengetik di kolom pencarian.
  - Modal detail: tombol Detail pada setiap kartu menampilkan spesifikasi lengkap dan estimasi total nilai persediaan produk tersebut.
  - Kalkulator form: pada halaman tambah dan edit produk, total valuasi persediaan dihitung secara otomatis saat harga dan stok diisi.

---

## 3. Penjelasan Keamanan Sistem

Berikut adalah penjelasan mengenai kontrol keamanan yang diterapkan di dalam aplikasi ini:

1. **Pencegahan SQL Injection (PDO Prepared Statements):**
   Semua perintah SQL yang berhubungan dengan data pengguna tidak pernah dirangkai langsung menggunakan penggabungan string. Kueri selalu menggunakan prepared statement (`$pdo->prepare()`) dengan tanda binding seperti `:name`, `:q`, atau `:id`. Dengan cara ini, input pengguna hanya dianggap sebagai data murni dan tidak bisa mengubah struktur perintah SQL.

2. **Pencegahan XSS (Cross-Site Scripting):**
   Semua data yang diambil dari database dan ditampilkan ke browser selalu dibungkus dengan fungsi pembantu `htmlspecialchars($string, ENT_QUOTES, 'UTF-8')`. Jika pengguna memasukkan nama produk yang berisi tag HTML seperti `<b>Promo</b>` atau tag skrip, browser hanya akan menampilkannya sebagai teks biasa tanpa menjalankannya sebagai kode HTML.

3. **Pencegahan CSRF (Cross-Site Request Forgery):**
   Operasi perubahan data seperti hapus produk tidak boleh dijalankan melalui tautan GET biasa. Form penghapusan produk wajib menggunakan metode POST dan membawa token acak yang tersimpan di session pengguna. Server akan memvalidasi kecocokan token menggunakan fungsi `hash_equals()` sebelum menghapus data.

4. **Pencegahan Duplikasi Form (Pola Post-Redirect-Get / PRG):**
   Saat pengguna mengirim formulir (POST), server akan memproses data lalu langsung mengalihkan halaman (redirect dengan kode HTTP 303) kembali ke halaman katalog atau formulir. Pesan status disimpan sementara di session (flash message). Dengan alur ini, jika pengguna me-refresh browser, tidak akan muncul dialog konfirmasi pengiriman ulang formulir dan data tidak akan tersimpan ganda.

5. **Pengamanan Direktori Upload:**
   Berkas gambar disimpan di folder `uploads/` yang dilengkapi berkas `.htaccess` untuk menonaktifkan eksekusi skrip PHP. Ini mencegah risiko seseorang mengunggah file skrip berbahaya yang disamarkan sebagai gambar lalu menjalankannya di server.

---

## 4. Jawaban Pertanyaan Refleksi (Slide 20)

> Pertanyaan: "Di bagian mana aplikasi paling rentan: input, query, output, atau alur request? Jelaskan kontrol keamanan yang telah Anda implementasikan."

**Jawaban:**

Jika dilihat dari tingkat kerusakan, bagian yang paling berbahaya apabila diserang adalah **Query (basis data)**. Celah SQL Injection pada query dapat membuat penyerang membaca seluruh isi tabel, mengubah data, atau bahkan menghapus database secara permanen.

Namun, jika dilihat dari jalur masuknya serangan, titik yang paling sering menjadi sasaran utama adalah **Input**. Hampir semua bentuk serangan, baik SQL Injection, XSS, maupun upload file berbahaya, bermula dari input pengguna yang tidak divalidasi dengan benar.

Oleh karena itu, kontrol keamanan di aplikasi ini dipasang berlapis di setiap titik:
- **Di sisi Input:** Diterapkan validasi ketat di server untuk memastikan nama minimal 3 karakter, harga berupa angka positif, stok tidak negatif, nama produk tidak duplikat, dan berkas upload benar-benar bertipe gambar yang sah.
- **Di sisi Query:** Menggunakan prepared statements PDO secara menyeluruh sehingga input pengguna terpisah dari perintah SQL.
- **Di sisi Output:** Menerapkan fungsi `htmlspecialchars()` dengan flag `ENT_QUOTES` pada setiap variabel yang dicetak ke halaman web untuk mencegah eksekusi skrip (XSS).
- **Di sisi Alur Request:** Menerapkan verifikasi token CSRF pada aksi penghapusan produk dan menerapkan pola PRG pada seluruh formulir agar tidak terjadi data ganda saat refresh.

---

## 5. Checklist Pengujian Dosen (Slide 20)

Berikut adalah panduan untuk menguji perilaku aplikasi sesuai kriteria checklist pada Slide 20:

| No | Kriteria Pengujian | Cara Menguji | Hasil yang Diharapkan |
| :--- | :--- | :--- | :--- |
| 1 | Tambah produk valid | Buka menu Tambah Produk. Masukkan nama minimal 3 karakter, pilih kategori, isi harga lebih dari 0, dan stok 0 atau lebih. Klik Simpan Produk. | Produk berhasil tersimpan di database, muncul notifikasi sukses, dan produk tampil pada kartu teratas di halaman katalog. |
| 2 | Nama kurang dari 3 karakter | Buka form tambah produk. Isi nama dengan 2 karakter (misalnya `AB`), lalu coba simpan. | Form ditolak oleh server. Muncul pesan error bahwa nama minimal 3 karakter, data tidak tersimpan ke database, dan isian form yang lain tetap tersimpan. |
| 3 | Harga atau stok negatif | Coba masukkan harga negatif (misal `-10000`) atau stok negatif (misal `-5`), lalu klik simpan. | Server menolak input tersebut dan menampilkan pesan kesalahan bahwa harga harus lebih dari 0 dan stok tidak boleh negatif. |
| 4 | Refresh setelah create | Setelah berhasil menambah produk dan diarahkan ke halaman katalog, tekan tombol F5 / refresh browser beberapa kali. | Tidak muncul peringatan konfirmasi form resubmission dan data produk tidak bertambah dobel karena sudah menggunakan pola PRG. |
| 5 | Nama berisi tag HTML `<b>Promo</b>` | Tambah produk baru dengan nama `Paket Sensor <b>Promo</b>`. | Nama tersimpan di database secara utuh. Ketika ditampilkan di halaman katalog, teks `<b>Promo</b>` tampil biasa sebagai tulisan teks biasa (bukan tulisan tebal), membuktikan proteksi XSS berfungsi. |
| 6 | Tampilan layar sempit (Mobile) | Perkecil ukuran jendela browser ke ukuran ponsel (misalnya lebar 375px) atau gunakan mode Device Toolbar di browser inspect element. | Kartu produk dan tombol navigasi membungkus ke bawah dengan rapi menjadi 1 kolom tanpa terpotong dan tidak ada scrollbar horizontal yang rusak. |

### Ringkasan Pemenuhan Kriteria Slide 20

Semua 6 poin evaluasi di atas telah terpenuhi secara langsung pada kode aplikasi:
1. Penambahan produk valid langsung masuk ke database melalui prepared statement PDO dan tampil di posisi teratas katalog.
2. Validasi panjang karakter pada server (`mb_strlen($name) < 3`) langsung mencegat nama yang kurang dari 3 karakter sebelum kueri SQL dijalankan.
3. Nilai harga dan stok diproteksi dua lapis: validasi angka positif di server (`functions.php`) dan check constraint pada tabel database (`chk_products_price` dan `chk_products_stock`).
4. Alur Post-Redirect-Get (PRG) mengalihkan formulir dengan status HTTP 303 ke halaman GET sehingga me-refresh browser tidak akan memicu pengiriman ulang formulir.
5. Fungsi pembungkus `htmlspecialchars()` dengan flag `ENT_QUOTES` meng-escape seluruh karakter HTML sehingga tag seperti `<b>Promo</b>` tampil murni sebagai teks visual.
6. Desain antarmuka responsif menggunakan CSS Grid `1fr` pada mobile, navbar baris tunggal, dan grid metrik 2x2 yang ringkas tanpa ada horizontal scrollbar yang berlebih.
---

## 6. Struktur Direktori Proyek

```text
ProductManager/
├── README.md               Dokumentasi proyek, panduan instalasi, dan checklist pengujian
├── index.php               Halaman utama (katalog, metrik ringkasan, pencarian, paginasi)
├── create.php              Halaman formulir pendaftaran produk baru
├── edit.php                Halaman formulir pembaruan data produk
├── delete.php              Proses penghapusan produk (khusus POST dengan CSRF)
├── demo_seed.php           Skrip untuk mengisi ulang data contoh ke database
├── config/
│   ├── database.php        Pengaturan koneksi PDO ke MySQL
│   └── csrf.php            Fungsi pembuatan dan pemeriksaan token CSRF
├── includes/
│   ├── functions.php       Fungsi bantuan sanitasi, validasi, flash message, dan upload
│   ├── header.php          Bagian atas halaman web dan navigasi
│   └── footer.php          Bagian bawah halaman web dan modal konfirmasi
├── database/
│   └── store_db.sql        Skema tabel basis data dan 10 data awal produk
├── assets/
│   ├── css/
│   │   └── style.css       Berkas stylesheet desain antarmuka
│   └── js/
│       └── app.js          Skrip interaktivitas klien (shortcut, live search, kalkulator)
└── uploads/
    ├── .htaccess           Pengamanan agar file PHP tidak bisa dieksekusi di folder upload
    └── .gitkeep            Penjaga folder di git
```
