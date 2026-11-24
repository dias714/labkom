<?php
/**
 * RESTful API Controller
 * Mendukung pengujian endpoint backend dengan Form Validation & Prepared Statement
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/helpers/Validator.php';
require_once __DIR__ . '/models/LaptopModel.php';
require_once __DIR__ . '/models/PeminjamanModel.php';
require_once __DIR__ . '/models/PengembalianModel.php';
require_once __DIR__ . '/models/UserModel.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true) ?? $_POST;

try {
    $db = Database::getConnection();

    switch ($action) {
        // ==========================
        // 1. AUTENTIKASI / LOGIN
        // ==========================
        case 'login':
            if ($method !== 'POST') throw new Exception("Metode request tidak diizinkan.");
            
            $validator = new Validator($inputData, $db);
            if (!$validator->validate([
                'username' => ['required', 'min:3'],
                'password' => ['required', 'min:4']
            ])) {
                http_response_code(422);
                echo json_encode(["success" => false, "errors" => $validator->getErrors()]);
                exit;
            }

            $userModel = new UserModel($db);
            $user = $userModel->login($inputData['username'], $inputData['password']);
            if (!$user) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Username atau password salah."]);
                exit;
            }

            echo json_encode(["success" => true, "message" => "Login berhasil.", "user" => $user]);
            break;

        // ==========================
        // 2. LAPTOP CRUD
        // ==========================
        case 'laptops':
            $laptopModel = new LaptopModel($db);

            if ($method === 'GET') {
                $status = $_GET['status'] ?? null;
                $search = $_GET['search'] ?? null;
                $rows = $laptopModel->getAll($status, $search);
                echo json_encode(["success" => true, "data" => $rows]);
            } elseif ($method === 'POST') {
                $validator = new Validator($inputData, $db);
                if (!$validator->validate([
                    'kode_aset' => ['required', 'unique:laptop,kode_aset'],
                    'nama_unit' => ['required', 'min:3'],
                    'merk_tipe' => ['required'],
                    'processor' => ['required'],
                    'ram'       => ['required'],
                    'storage'   => ['required']
                ])) {
                    http_response_code(422);
                    echo json_encode(["success" => false, "errors" => $validator->getErrors()]);
                    exit;
                }

                $laptopModel->create($validator->getData());
                echo json_encode(["success" => true, "message" => "Laptop berhasil ditambahkan dengan Prepared Statement."]);
            }
            break;

        // ==========================
        // 3. PEMINJAMAN
        // ==========================
        case 'loans':
            $loanModel = new PeminjamanModel($db);

            if ($method === 'GET') {
                $status = $_GET['status'] ?? null;
                $rows = $loanModel->getAll($status);
                echo json_encode(["success" => true, "data" => $rows]);
            } elseif ($method === 'POST') {
                $validator = new Validator($inputData, $db);
                if (!$validator->validate([
                    'id_siswa'                => ['required', 'numeric'],
                    'id_laptop'               => ['required', 'numeric'],
                    'tanggal_rencana_kembali' => ['required', 'date_not_before:' . date('Y-m-d')],
                    'keperluan'               => ['required', 'min:5']
                ])) {
                    http_response_code(422);
                    echo json_encode(["success" => false, "errors" => $validator->getErrors()]);
                    exit;
                }

                $loanModel->createLoan($validator->getData());
                echo json_encode(["success" => true, "message" => "Pengajuan peminjaman berhasil disimpan."]);
            }
            break;

        case 'loan-approve':
            if ($method !== 'POST') throw new Exception("Metode POST diperlukan.");
            $loanModel = new PeminjamanModel($db);
            $loanModel->approveLoan($inputData['id_peminjaman'], $inputData['id_petugas'] ?? 1);
            echo json_encode(["success" => true, "message" => "Peminjaman disetujui (Prepared Statement)."]);
            break;

        case 'loan-reject':
            if ($method !== 'POST') throw new Exception("Metode POST diperlukan.");
            $loanModel = new PeminjamanModel($db);
            $loanModel->rejectLoan($inputData['id_peminjaman'], $inputData['catatan'] ?? 'Ditolak petugas', $inputData['id_petugas'] ?? 1);
            echo json_encode(["success" => true, "message" => "Peminjaman berhasil ditolak."]);
            break;

        // ==========================
        // 4. PENGEMBALIAN
        // ==========================
        case 'returns':
            $returnModel = new PengembalianModel($db);

            if ($method === 'GET') {
                $rows = $returnModel->getAll();
                echo json_encode(["success" => true, "data" => $rows]);
            } elseif ($method === 'POST') {
                $validator = new Validator($inputData, $db);
                if (!$validator->validate([
                    'id_peminjaman'   => ['required', 'numeric'],
                    'kondisi_akhir'   => ['required', 'in:Baik,Rusak Ringan,Rusak Berat,Perlu diperiksa']
                ])) {
                    http_response_code(422);
                    echo json_encode(["success" => false, "errors" => $validator->getErrors()]);
                    exit;
                }

                $returnModel->processReturn($validator->getData());
                echo json_encode(["success" => true, "message" => "Pengembalian berhasil diproses (Prepared Statement)."]);
            }
            break;

        default:
            echo json_encode([
                "status" => "online",
                "message" => "API Sistem Peminjaman Laptop Sekolah siap digunakan.",
                "endpoints" => [
                    "POST ?action=login",
                    "GET/POST ?action=laptops",
                    "GET/POST ?action=loans",
                    "POST ?action=loan-approve",
                    "POST ?action=loan-reject",
                    "GET/POST ?action=returns"
                ]
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
