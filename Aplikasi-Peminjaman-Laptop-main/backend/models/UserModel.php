<?php
/**
 * Model User untuk Autentikasi dengan Password Hashing & Prepared Statement
 */

class UserModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Verifikasi Login Pengguna dengan Prepared Statement
     */
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM `users` WHERE `username` = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user) {
            // Cek password hash atau plain fallback untuk akun demo
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                unset($user['password']); // Hapus hash dari memori sebelum return
                return $user;
            }
        }
        return false;
    }

    /**
     * Ambil data profil berdasarkan ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT `id_user`, `username`, `nama`, `role`, `created_at` FROM `users` WHERE `id_user` = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
}
