# Aplikasi Peminjaman Laptop Sekolah

Sistem Informasi Pengelolaan dan Peminjaman Inventaris Laptop Sekolah berbasis Multi-Role dengan fitur **Validasi Form Terintegrasi** dan arsitektur **Prepared Statement (Anti SQL Injection & XSS)**.

---

## 🛠️ Ringkasan Pembaruan & Perbaikan Sistem

### 1. Perbaikan Kesalahan (Bug Fixes)
- **Syntax Error Kritis pada `siswa/status.html`**: Diperbaiki penutupan sintaks ekstra `)` pada `.join('')||'...</div>');` yang sebelumnya menyebabkan script berhenti (`Uncaught SyntaxError: Unexpected token ')'`) sehingga halaman status peminjaman siswa menjadi blank.
- **Pembersihan Marker Stray `$`**: Menghilangkan karakter liar `$` pada template tabel dan modal di `admin/siswa.html`, `admin/petugas.html`, dan `admin/users.html`.
- **Perbaikan Logika Penyimpanan Pengembalian**: Pada `petugas/pengembalian.html`, sebelumnya `DB.get('pengembalian');` tidak menyimpan data apapun. Kini seluruh data pengembalian (kondisi fisik akhir, catatan kerusakan, denda, dan petugas penerima) tersimpan permanen ke tabel `pengembalian`.
- **Sinkronisasi Akun Pengguna**: Penambahan data siswa atau petugas baru secara otomatis tersinkronisasi dengan tabel `users` sehingga akun langsung dapat login.
- **Perbaikan Input Form**: Mengganti input teks bebas untuk `status`, `kondisi`, `role`, dan `jurusan` menjadi komponen `<select>` terstandar.

---

### 2. Fitur Baru: Validasi Form (`assets/js/validator.js` & `backend/helpers/Validator.php`)
Sistem validasi form bekerja secara interaktif pada sisi client (real-time) dan server-side:
- **Validasi Real-time**: Indikator border merah untuk error, border hijau untuk isian valid, serta pesan error informatif di bawah field terkait.
- **Aturan Validasi Lengkap**:
  - `required`: Field wajib diisi dan tidak boleh hanya berisi spasi.
  - `numeric`: Field NIS, NIP, dan Denda wajib berupa angka valid.
  - `minLength` & `maxLength`: Batas panjang karakter (Nama minimal 3 karakter, keperluan minimal 5 karakter, password minimal 6 karakter).
  - `unique`: Memastikan Kode Laptop, NIS, NIP, dan Username tidak duplikat di database.
  - `dateNotBefore`: Memastikan tanggal rencana pengembalian tidak dapat dipilih di masa lampau.
  - `inList`: Memvalidasi pilihan opsi status dan kondisi fisik.
  - `safeText`: Sanitasi otomatis untuk mencegah injeksi script (XSS).

---

### 3. Fitur Baru: Arsitektur Prepared Statement (`Prepared`)
Sistem menyediakan dua lapis implementasi Prepared Statement:

#### A. Client-Side Prepared Statement (`DB.prepare` di `assets/js/data.js`)
- Menggunakan sintaks parameter binding SQL (`?` positional dan `:param` named bindings).
- Melakukan sanitasi otomatis terhadap nilai input sebelum dimasukkan ke database LocalStorage.
- Mendukung transaksi atomik (`DB.beginTransaction()`, `DB.commit()`, `DB.rollback()`).
- Dilengkapi **Query Log Inspector** di sidebar untuk memantau query prepared statement yang sedang dieksekusi.

Contoh Penggunaan:
```javascript
// INSERT dengan positional parameters
const stmt = DB.prepare("INSERT INTO peminjaman (code, studentId, laptopId, requestDate, returnPlan, purpose, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
stmt.execute([code, studentId, laptopId, today, returnPlan, purpose, 'Menunggu']);

// UPDATE dengan named parameters
const stmt = DB.prepare("UPDATE laptops SET status = :status, condition = :condition WHERE id = :id");
stmt.execute({ status: 'Dipinjam', condition: 'Baik', id: 2 });
```

#### B. Fullstack Backend PHP + MySQL PDO Prepared Statement
Tersedia skema database dan controller backend production-ready di:
- `database/peminjaman_laptop.sql`: Skema relasional (Users, Siswa, Petugas, Laptop, Peminjaman, Pengembalian) lengkap dengan foreign key constraints dan data seed.
- `backend/config/Database.php`: Koneksi PDO dengan `PDO::ATTR_EMULATE_PREPARES => false` (Prepared Statement murni hardware/DB level).
- `backend/models/`: Model CRUD (Laptop, Peminjaman, Pengembalian, User) dengan parameter binding PDO (`$stmt->execute([...])`).
- `backend/api.php`: RESTful endpoint JSON yang siap dihubungkan.

---

## 👥 Akun Demo Login

| Role | Username | Password | Hak Akses |
|---|---|---|---|
| **Admin** | `admin` | `admin123` | Kelola Master Laptop, Siswa, Petugas, User, Peminjaman, Pengembalian & Laporan |
| **Petugas** | `petugas` | `petugas123` | Persetujuan Pengajuan, Pengecekan Fisik & Pengembalian, Update Laptop |
| **Siswa** | `siswa` | `siswa123` | Katalog Laptop, Pengajuan Peminjaman, Status Pengajuan & Riwayat |

---

## 🚀 Cara Menjalankan

### Cara 1: Langsung Buka di Browser (Frontend Mode)
1. Buka folder proyek.
2. Dobel klik file `index.html` atau buka menggunakan ekstensi VS Code **Live Server**.
3. Gunakan salah satu akun demo untuk masuk.

### Cara 2: Menjalankan Backend PHP / MySQL (Opsional)
1. Nyalakan Apache dan MySQL di XAMPP.
2. Buat database baru di phpMyAdmin: `db_peminjaman_laptop`.
3. Impor file [database/peminjaman_laptop.sql](file:///c:/Users/AIO%20RPL/Downloads/Aplikasi-Peminjaman-Laptop-main/database/peminjaman_laptop.sql).
4. Jalankan backend:
   ```bash
   php -S localhost:8000 -t .
   ```
5. Endpoint API dapat diakses di `http://localhost:8000/backend/api.php`.

---

## 🧪 Pengujian Otomatis (Unit Tests)
Jalankan pengujian unit test Prepared Statement dan Validator melalui:
```bash
node tests/test_runner.js
```
Hasil: `ALL UNIT TESTS PASSED 100%!`
