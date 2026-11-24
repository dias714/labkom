# ERD Konseptual

Entitas utama yang dapat digunakan untuk dokumentasi ERD jobsheet:

```mermaid
erDiagram
    USERS ||--o| SISWA : "memiliki"
    USERS ||--o| PETUGAS : "memiliki"
    SISWA ||--o{ PEMINJAMAN : "mengajukan"
    LAPTOP ||--o{ PEMINJAMAN : "dipinjam"
    PETUGAS ||--o{ PEMINJAMAN : "memproses"

    USERS {
      int id_user PK
      string username
      string password
      string role
      string nama
    }
    SISWA {
      int id_siswa PK
      int id_user FK
      string nis
      string nama
      string kelas
      string jurusan
      string status
    }
    PETUGAS {
      int id_petugas PK
      int id_user FK
      string nip
      string nama
      string status
    }
    LAPTOP {
      int id_laptop PK
      string kode_laptop
      string nama_laptop
      string merk
      string model
      string processor
      string ram
      string storage
      string status
      string kondisi
      string lokasi
    }
    PEMINJAMAN {
      int id_peminjaman PK
      int id_siswa FK
      int id_laptop FK
      int id_petugas FK
      date tanggal_pengajuan
      date tanggal_pinjam
      date tanggal_rencana_kembali
      date tanggal_dikembalikan
      string keperluan
      string status
      string catatan
    }
```

Catatan: ERD ini adalah rancangan dokumentasi. Implementasi prototype menggunakan LocalStorage, bukan database SQL.
