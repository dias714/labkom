<?php
/**
 * Model Laptop dengan Implementasi PDO Prepared Statement
 */

class LaptopModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Ambil semua data laptop dengan filter opsional
     */
    public function getAll($status = null, $keyword = null) {
        $sql = "SELECT * FROM `laptop` WHERE 1=1";
        $params = [];

        if (!empty($status) && $status !== 'Semua') {
            $sql .= " AND `status_ketersediaan` = :status";
            $params[':status'] = $status;
        }

        if (!empty($keyword)) {
            $sql .= " AND (`nama_unit` LIKE :kw OR `kode_aset` LIKE :kw OR `merk_tipe` LIKE :kw OR `lokasi` LIKE :kw)";
            $params[':kw'] = '%' . $keyword . '%';
        }

        $sql .= " ORDER BY `id_laptop` DESC";

        // PREPARED STATEMENT
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ambil data laptop berdasarkan ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM `laptop` WHERE `id_laptop` = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Tambah laptop baru dengan Prepared Statement
     */
    public function create($data) {
        $sql = "INSERT INTO `laptop` 
                (`kode_aset`, `nama_unit`, `merk_tipe`, `nomor_seri`, `processor`, `ram`, `storage`, `kondisi`, `status_ketersediaan`, `lokasi`)
                VALUES 
                (:kode_aset, :nama_unit, :merk_tipe, :nomor_seri, :processor, :ram, :storage, :kondisi, :status_ketersediaan, :lokasi)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':kode_aset'           => $data['kode_aset'],
            ':nama_unit'           => $data['nama_unit'],
            ':merk_tipe'           => $data['merk_tipe'],
            ':nomor_seri'          => $data['nomor_seri'] ?? '-',
            ':processor'           => $data['processor'],
            ':ram'                 => $data['ram'],
            ':storage'             => $data['storage'],
            ':kondisi'             => $data['kondisi'] ?? 'Baik',
            ':status_ketersediaan' => $data['status_ketersediaan'] ?? 'Tersedia',
            ':lokasi'              => $data['lokasi'] ?? 'Lab RPL'
        ]);
    }

    /**
     * Update data laptop dengan Prepared Statement
     */
    public function update($id, $data) {
        $sql = "UPDATE `laptop` SET 
                `kode_aset` = :kode_aset,
                `nama_unit` = :nama_unit,
                `merk_tipe` = :merk_tipe,
                `nomor_seri` = :nomor_seri,
                `processor` = :processor,
                `ram` = :ram,
                `storage` = :storage,
                `kondisi` = :kondisi,
                `status_ketersediaan` = :status_ketersediaan,
                `lokasi` = :lokasi
                WHERE `id_laptop` = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':kode_aset'           => $data['kode_aset'],
            ':nama_unit'           => $data['nama_unit'],
            ':merk_tipe'           => $data['merk_tipe'],
            ':nomor_seri'          => $data['nomor_seri'] ?? '-',
            ':processor'           => $data['processor'],
            ':ram'                 => $data['ram'],
            ':storage'             => $data['storage'],
            ':kondisi'             => $data['kondisi'],
            ':status_ketersediaan' => $data['status_ketersediaan'],
            ':lokasi'              => $data['lokasi'],
            ':id'                  => $id
        ]);
    }

    /**
     * Hapus laptop dengan Prepared Statement
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM `laptop` WHERE `id_laptop` = :id");
        return $stmt->execute([':id' => $id]);
    }
}
