<?php
/**
 * Model Peminjaman dengan Implementasi Transaksi & Prepared Statement
 */

class PeminjamanModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Ambil seluruh transaksi peminjaman lengkap dengan relasi siswa & laptop
     */
    public function getAll($status = null) {
        $sql = "SELECT p.*, s.nama_siswa, s.nis, s.kelas, l.nama_unit, l.kode_aset, l.merk_tipe
                FROM `peminjaman` p
                JOIN `siswa` s ON p.id_siswa = s.id_siswa
                JOIN `laptop` l ON p.id_laptop = l.id_laptop";
        
        $params = [];
        if (!empty($status) && $status !== 'Semua') {
            $sql .= " WHERE p.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY p.id_peminjaman DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ajukan Peminjaman Baru oleh Siswa
     */
    public function createLoan($data) {
        // Cek apakah laptop masih berstatus Tersedia
        $stmtCheck = $this->db->prepare("SELECT `status_ketersediaan` FROM `laptop` WHERE `id_laptop` = :id");
        $stmtCheck->execute([':id' => $data['id_laptop']]);
        $lap = $stmtCheck->fetch();

        if (!$lap || $lap['status_ketersediaan'] !== 'Tersedia') {
            throw new Exception("Laptop sedang tidak tersedia untuk dipinjam.");
        }

        $sql = "INSERT INTO `peminjaman` 
                (`kode_peminjaman`, `id_siswa`, `id_laptop`, `tanggal_pengajuan`, `tanggal_rencana_kembali`, `keperluan`, `status`)
                VALUES 
                (:kode, :id_siswa, :id_laptop, :tgl_aju, :tgl_kembali, :keperluan, 'Menunggu')";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':kode'        => 'PJM' . strtoupper(substr(uniqid(), -6)),
            ':id_siswa'    => $data['id_siswa'],
            ':id_laptop'   => $data['id_laptop'],
            ':tgl_aju'     => date('Y-m-d'),
            ':tgl_kembali' => $data['tanggal_rencana_kembali'],
            ':keperluan'   => $data['keperluan']
        ]);
    }

    /**
     * Persetujuan Peminjaman oleh Petugas (Atomic Transaction)
     */
    public function approveLoan($id_peminjaman, $id_petugas) {
        $this->db->beginTransaction();
        try {
            // Ambil id_laptop dari peminjaman
            $stmt = $this->db->prepare("SELECT `id_laptop`, `status` FROM `peminjaman` WHERE `id_peminjaman` = :id FOR UPDATE");
            $stmt->execute([':id' => $id_peminjaman]);
            $loan = $stmt->fetch();

            if (!$loan || $loan['status'] !== 'Menunggu') {
                throw new Exception("Transaksi tidak valid untuk disetujui.");
            }

            // Update status laptop menjadi Dipinjam
            $stmtLap = $this->db->prepare("UPDATE `laptop` SET `status_ketersediaan` = 'Dipinjam' WHERE `id_laptop` = :id_laptop");
            $stmtLap->execute([':id_laptop' => $loan['id_laptop']]);

            // Update status peminjaman menjadi Dipinjam
            $stmtLoan = $this->db->prepare("UPDATE `peminjaman` SET 
                                            `status` = 'Dipinjam', 
                                            `id_petugas` = :id_petugas, 
                                            `tanggal_pinjam` = :tgl_pinjam 
                                            WHERE `id_peminjaman` = :id");
            $stmtLoan->execute([
                ':id_petugas'   => $id_petugas,
                ':tgl_pinjam'   => date('Y-m-d'),
                ':id'           => $id_peminjaman
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Penolakan Peminjaman oleh Petugas
     */
    public function rejectLoan($id_peminjaman, $catatan, $id_petugas) {
        $stmt = $this->db->prepare("UPDATE `peminjaman` SET 
                                    `status` = 'Ditolak', 
                                    `catatan` = :catatan, 
                                    `id_petugas` = :id_petugas 
                                    WHERE `id_peminjaman` = :id");
        return $stmt->execute([
            ':catatan'    => $catatan,
            ':id_petugas' => $id_petugas,
            ':id'         => $id_peminjaman
        ]);
    }
}
