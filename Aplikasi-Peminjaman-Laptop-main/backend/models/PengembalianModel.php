<?php
/**
 * Model Pengembalian dengan Implementasi Transaksi & Prepared Statement
 */

class PengembalianModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Ambil riwayat pengembalian lengkap
     */
    public function getAll() {
        $sql = "SELECT pg.*, p.kode_peminjaman, s.nama_siswa, s.nis, l.nama_unit, l.kode_aset, pt.nama_petugas
                FROM `pengembalian` pg
                JOIN `peminjaman` p ON pg.id_peminjaman = p.id_peminjaman
                JOIN `siswa` s ON p.id_siswa = s.id_siswa
                JOIN `laptop` l ON p.id_laptop = l.id_laptop
                LEFT JOIN `petugas` pt ON pg.id_petugas = pt.id_petugas
                ORDER BY pg.id_pengembalian DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Proses Pengembalian Laptop
     */
    public function processReturn($data) {
        $this->db->beginTransaction();
        try {
            // Ambil data peminjaman
            $stmt = $this->db->prepare("SELECT `id_laptop`, `status`, `tanggal_rencana_kembali` 
                                        FROM `peminjaman` 
                                        WHERE `id_peminjaman` = :id FOR UPDATE");
            $stmt->execute([':id' => $data['id_peminjaman']]);
            $loan = $stmt->fetch();

            if (!$loan || $loan['status'] !== 'Dipinjam') {
                throw new Exception("Peminjaman tidak aktif atau sudah dikembalikan.");
            }

            // Tentukan status laptop berdasarkan kondisi akhir
            $kondisi = $data['kondisi_akhir'];
            $statusLaptop = 'Tersedia';
            if ($kondisi === 'Rusak Berat') {
                $statusLaptop = 'Rusak';
            } elseif ($kondisi === 'Rusak Ringan' || $kondisi === 'Perlu diperiksa') {
                $statusLaptop = 'Maintenance';
            }

            // 1. Simpan ke tabel pengembalian (PREPARED STATEMENT)
            $stmtRet = $this->db->prepare("INSERT INTO `pengembalian` 
                (`id_peminjaman`, `id_petugas`, `tanggal_kembali`, `kondisi_akhir`, `catatan_kerusakan`, `denda`)
                VALUES 
                (:id_peminjaman, :id_petugas, :tanggal_kembali, :kondisi_akhir, :catatan, :denda)");

            $stmtRet->execute([
                ':id_peminjaman'   => $data['id_peminjaman'],
                ':id_petugas'      => $data['id_petugas'] ?? null,
                ':tanggal_kembali' => $data['tanggal_kembali'] ?? date('Y-m-d'),
                ':kondisi_akhir'   => $kondisi,
                ':catatan'         => $data['catatan_kerusakan'] ?? '',
                ':denda'           => $data['denda'] ?? 0.00
            ]);

            // 2. Update status peminjaman menjadi Dikembalikan
            $stmtLoan = $this->db->prepare("UPDATE `peminjaman` SET 
                `status` = 'Dikembalikan', 
                `tanggal_dikembalikan` = :tgl,
                `catatan` = :catatan 
                WHERE `id_peminjaman` = :id");

            $stmtLoan->execute([
                ':tgl'     => $data['tanggal_kembali'] ?? date('Y-m-d'),
                ':catatan' => $data['catatan_kerusakan'] ?? 'Dikembalikan',
                ':id'      => $data['id_peminjaman']
            ]);

            // 3. Update kondisi dan status ketersediaan laptop
            $stmtLap = $this->db->prepare("UPDATE `laptop` SET 
                `status_ketersediaan` = :status,
                `kondisi` = :kondisi 
                WHERE `id_laptop` = :id_laptop");

            $stmtLap->execute([
                ':status'    => $statusLaptop,
                ':kondisi'   => $kondisi,
                ':id_laptop' => $loan['id_laptop']
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
