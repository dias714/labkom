<?php
/**
 * Konfigurasi Database PDO dengan Prepared Statement
 * Sistem Peminjaman Laptop Sekolah
 */

class Database {
    private static $host = "localhost";
    private static $db_name = "db_peminjaman_laptop";
    private static $username = "root";
    private static $password = "";
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn === null) {
            try {
                $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=utf8mb4";
                
                // Konfigurasi PDO untuk keamanan maksimal & Prepared Statement murni
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exception saat error
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch array asosiatif
                    PDO::ATTR_EMULATE_PREPARES   => false,                  // Prepared Statement ASLI dari database engine (Anti SQL Injection)
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ];

                self::$conn = new PDO($dsn, self::$username, self::$password, $options);
            } catch (PDOException $exception) {
                // Jangan ekspos password atau detail kredensial ke publik
                error_log("Database Connection Error: " . $exception->getMessage());
                die(json_encode([
                    "success" => false,
                    "message" => "Gagal terhubung ke database. Pastikan MySQL XAMPP berjalan dan database 'db_peminjaman_laptop' telah diimpor."
                ]));
            }
        }
        return self::$conn;
    }
}
