<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Strict',
]);
session_start();

function respond(array $body, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function database(): PDO
{
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $name = getenv('DB_NAME') ?: 'peminjaman_laptop';
    $user = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';

    return new PDO(
        "mysql:host={$host};dbname={$name};charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function createSchema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS app_data (
            collection VARCHAR(32) NOT NULL,
            record_id VARCHAR(64) NOT NULL,
            payload JSON NOT NULL,
            PRIMARY KEY (collection, record_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS app_meta (
            meta_key VARCHAR(32) NOT NULL PRIMARY KEY,
            meta_value VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $seeded = $pdo->query("SELECT meta_value FROM app_meta WHERE meta_key = 'seeded'")->fetchColumn();
    if ($seeded !== false) {
        return;
    }

    $users = [
        ['id' => 1, 'username' => 'admin', 'password' => 'admin123', 'role' => 'admin', 'name' => 'Administrator'],
        ['id' => 2, 'username' => 'petugas', 'password' => 'petugas123', 'role' => 'petugas', 'name' => 'Budi Santoso'],
        ['id' => 3, 'username' => 'siswa', 'password' => 'siswa123', 'role' => 'siswa', 'name' => 'Andi Pratama'],
    ];
    $students = [
        ['id' => 1, 'nis' => '2026001', 'name' => 'Andi Pratama', 'kelas' => 'XII RPL 1', 'jurusan' => 'RPL', 'username' => 'siswa', 'status' => 'Aktif'],
        ['id' => 2, 'nis' => '2026002', 'name' => 'Siti Aulia', 'kelas' => 'XII RPL 1', 'jurusan' => 'RPL', 'username' => 'sitiaulia', 'status' => 'Aktif'],
        ['id' => 3, 'nis' => '2026003', 'name' => 'Rizky Maulana', 'kelas' => 'XII RPL 2', 'jurusan' => 'RPL', 'username' => 'rizky', 'status' => 'Aktif'],
        ['id' => 4, 'nis' => '2026004', 'name' => 'Nadia Putri', 'kelas' => 'XII RPL 2', 'jurusan' => 'RPL', 'username' => 'nadia', 'status' => 'Aktif'],
        ['id' => 5, 'nis' => '2026005', 'name' => 'Fajar Hidayat', 'kelas' => 'XII TKJ 1', 'jurusan' => 'TKJ', 'username' => 'fajar', 'status' => 'Aktif'],
    ];
    $staff = [
        ['id' => 1, 'nip' => '1989001', 'name' => 'Budi Santoso', 'username' => 'petugas', 'status' => 'Aktif'],
        ['id' => 2, 'nip' => '1989002', 'name' => 'Dewi Lestari', 'username' => 'dewi', 'status' => 'Aktif'],
    ];
    $laptops = [
        ['id' => 1, 'code' => 'LT001', 'name' => 'Laptop 01', 'brand' => 'Lenovo', 'model' => 'ThinkPad E14', 'processor' => 'Core i5', 'ram' => '8 GB', 'storage' => '256 GB SSD', 'status' => 'Tersedia', 'condition' => 'Baik', 'location' => 'Lab RPL'],
        ['id' => 2, 'code' => 'LT002', 'name' => 'Laptop 02', 'brand' => 'Acer', 'model' => 'Aspire 5', 'processor' => 'Core i5', 'ram' => '8 GB', 'storage' => '512 GB SSD', 'status' => 'Dipinjam', 'condition' => 'Baik', 'location' => 'Lab RPL'],
        ['id' => 3, 'code' => 'LT003', 'name' => 'Laptop 03', 'brand' => 'ASUS', 'model' => 'VivoBook 14', 'processor' => 'Core i3', 'ram' => '8 GB', 'storage' => '256 GB SSD', 'status' => 'Tersedia', 'condition' => 'Baik', 'location' => 'Lab RPL'],
        ['id' => 4, 'code' => 'LT004', 'name' => 'Laptop 04', 'brand' => 'HP', 'model' => '14s', 'processor' => 'Core i5', 'ram' => '8 GB', 'storage' => '512 GB SSD', 'status' => 'Maintenance', 'condition' => 'Perlu diperiksa', 'location' => 'Ruang Teknisi'],
        ['id' => 5, 'code' => 'LT005', 'name' => 'Laptop 05', 'brand' => 'Lenovo', 'model' => 'IdeaPad Slim 3', 'processor' => 'Ryzen 5', 'ram' => '8 GB', 'storage' => '512 GB SSD', 'status' => 'Tersedia', 'condition' => 'Baik', 'location' => 'Lab RPL'],
        ['id' => 6, 'code' => 'LT006', 'name' => 'Laptop 06', 'brand' => 'Acer', 'model' => 'TravelMate', 'processor' => 'Core i5', 'ram' => '8 GB', 'storage' => '256 GB SSD', 'status' => 'Rusak', 'condition' => 'Rusak', 'location' => 'Ruang Teknisi'],
        ['id' => 7, 'code' => 'LT007', 'name' => 'Laptop 07', 'brand' => 'ASUS', 'model' => 'ExpertBook', 'processor' => 'Core i5', 'ram' => '16 GB', 'storage' => '512 GB SSD', 'status' => 'Tersedia', 'condition' => 'Baik', 'location' => 'Lab RPL'],
        ['id' => 8, 'code' => 'LT008', 'name' => 'Laptop 08', 'brand' => 'HP', 'model' => 'ProBook', 'processor' => 'Core i5', 'ram' => '8 GB', 'storage' => '512 GB SSD', 'status' => 'Tersedia', 'condition' => 'Baik', 'location' => 'Lab RPL'],
        ['id' => 9, 'code' => 'LT009', 'name' => 'Laptop 09', 'brand' => 'Lenovo', 'model' => 'V14', 'processor' => 'Core i3', 'ram' => '8 GB', 'storage' => '256 GB SSD', 'status' => 'Dipinjam', 'condition' => 'Baik', 'location' => 'Lab RPL'],
        ['id' => 10, 'code' => 'LT010', 'name' => 'Laptop 10', 'brand' => 'Acer', 'model' => 'Aspire 3', 'processor' => 'Core i3', 'ram' => '8 GB', 'storage' => '256 GB SSD', 'status' => 'Tersedia', 'condition' => 'Baik', 'location' => 'Lab RPL'],
    ];
    $loans = [
        ['id' => 1, 'code' => 'PJM001', 'studentId' => 1, 'laptopId' => 2, 'requestDate' => '2026-09-10', 'borrowDate' => '2026-09-11', 'returnPlan' => '2026-09-18', 'returnedDate' => '', 'purpose' => 'Praktik pemrograman', 'status' => 'Dipinjam', 'note' => ''],
        ['id' => 2, 'code' => 'PJM002', 'studentId' => 2, 'laptopId' => 3, 'requestDate' => '2026-09-14', 'borrowDate' => '', 'returnPlan' => '2026-09-20', 'returnedDate' => '', 'purpose' => 'Proyek RPL', 'status' => 'Menunggu', 'note' => ''],
        ['id' => 3, 'code' => 'PJM003', 'studentId' => 3, 'laptopId' => 1, 'requestDate' => '2026-08-20', 'borrowDate' => '2026-08-21', 'returnPlan' => '2026-08-25', 'returnedDate' => '2026-08-25', 'purpose' => 'Uji aplikasi', 'status' => 'Dikembalikan', 'note' => 'Kondisi baik'],
        ['id' => 4, 'code' => 'PJM004', 'studentId' => 4, 'laptopId' => 6, 'requestDate' => '2026-08-28', 'borrowDate' => '', 'returnPlan' => '2026-09-02', 'returnedDate' => '', 'purpose' => 'Praktik jaringan', 'status' => 'Ditolak', 'note' => 'Laptop sedang rusak'],
    ];

    $insert = $pdo->prepare('INSERT INTO app_data (collection, record_id, payload) VALUES (?, ?, ?)');
    foreach ([
        'users' => $users,
        'siswa' => $students,
        'petugas' => $staff,
        'laptops' => $laptops,
        'peminjaman' => $loans,
        'pengembalian' => [],
    ] as $collection => $records) {
        foreach ($records as $record) {
            if ($collection === 'users') {
                $record['password'] = password_hash($record['password'], PASSWORD_DEFAULT);
            }
            $insert->execute([$collection, (string) $record['id'], json_encode($record, JSON_UNESCAPED_UNICODE)]);
        }
    }
    $pdo->exec("INSERT INTO app_meta (meta_key, meta_value) VALUES ('seeded', '1')");
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireUser(): array
{
    $user = currentUser();
    if (!$user) {
        respond(['error' => 'Silakan login kembali.'], 401);
    }
    return $user;
}

function verifyCsrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        respond(['error' => 'Sesi keamanan tidak valid. Muat ulang halaman dan coba lagi.'], 403);
    }
}

function loadCollection(PDO $pdo, string $collection): array
{
    $stmt = $pdo->prepare('SELECT payload FROM app_data WHERE collection = ? ORDER BY record_id');
    $stmt->execute([$collection]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return array_map(static fn(string $row): array => json_decode($row, true, 512, JSON_THROW_ON_ERROR), $rows);
}

function publicRecords(string $collection, array $records): array
{
    if ($collection === 'users') {
        foreach ($records as &$record) {
            $record['password'] = '********';
        }
        unset($record);
    }
    return $records;
}

function visibleCollections(PDO $pdo, array $user): array
{
    $role = $user['role'];
    $collections = $role === 'admin'
        ? ['users', 'siswa', 'petugas', 'laptops', 'peminjaman', 'pengembalian']
        : ($role === 'petugas'
            ? ['siswa', 'laptops', 'peminjaman', 'pengembalian']
            : ['siswa', 'laptops', 'peminjaman', 'pengembalian']);

    $result = [];
    foreach ($collections as $collection) {
        $records = loadCollection($pdo, $collection);
        if ($role === 'siswa') {
            if ($collection === 'siswa') {
                $records = array_values(array_filter($records, static fn(array $student): bool => $student['username'] === $user['username']));
            } elseif ($collection === 'peminjaman') {
                $students = loadCollection($pdo, 'siswa');
                $matchingStudents = array_values(array_filter($students, static fn(array $item): bool => $item['username'] === $user['username']));
                $student = $matchingStudents[0] ?? null;
                $records = array_values(array_filter($records, static fn(array $loan): bool => $student && (string) $loan['studentId'] === (string) $student['id']));
            } elseif ($collection === 'pengembalian') {
                $records = [];
            }
        }
        $result[$collection] = publicRecords($collection, $records);
    }
    return $result;
}

try {
    $pdo = database();
    createSchema($pdo);
} catch (Throwable $error) {
    error_log($error->getMessage());
    respond(['error' => 'Database tidak dapat diakses. Periksa konfigurasi MySQL dan pastikan database sudah dibuat.'], 500);
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$action = $_GET['action'] ?? '';

if ($action === 'bootstrap' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = currentUser();
    respond([
        'user' => $user,
        'csrf' => $_SESSION['csrf'],
        'data' => $user ? visibleCollections($pdo, $user) : new stdClass(),
    ]);
}

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $username = trim((string) ($input['username'] ?? ''));
    $password = (string) ($input['password'] ?? '');
    $users = loadCollection($pdo, 'users');
    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'name' => $user['name'],
                'role' => $user['role'],
            ];
            respond(['user' => $_SESSION['user'], 'csrf' => $_SESSION['csrf']]);
        }
    }
    respond(['error' => 'Username atau password tidak benar.'], 401);
}

if ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireUser();
    verifyCsrf();
    $_SESSION = [];
    session_destroy();
    respond(['ok' => true]);
}

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = requireUser();
    verifyCsrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $collection = (string) ($input['collection'] ?? '');
    $records = $input['records'] ?? null;
    $allowed = [
        'admin' => ['users', 'siswa', 'petugas', 'laptops', 'peminjaman', 'pengembalian'],
        'petugas' => ['laptops', 'peminjaman', 'pengembalian'],
        'siswa' => ['peminjaman'],
    ];
    if (!in_array($collection, $allowed[$user['role']] ?? [], true) || !is_array($records) || count($records) > 10000) {
        respond(['error' => 'Aksi atau data tidak diizinkan.'], 403);
    }
    foreach ($records as $record) {
        if (!is_array($record) || !isset($record['id']) || !is_scalar($record['id'])) {
            respond(['error' => 'Format data tidak valid.'], 422);
        }
    }

    try {
        $pdo->beginTransaction();
        $existing = loadCollection($pdo, $collection);
        if ($user['role'] === 'siswa') {
            $students = loadCollection($pdo, 'siswa');
            $matchingStudents = array_values(array_filter($students, static fn(array $item): bool => $item['username'] === $user['username']));
            $student = $matchingStudents[0] ?? null;
            if (!$student) {
                respond(['error' => 'Data siswa tidak ditemukan.'], 403);
            }
            $owned = [];
            foreach ($existing as $record) {
                if ((string) ($record['studentId'] ?? '') === (string) $student['id']) {
                    $owned[(string) $record['id']] = $record;
                }
            }
            foreach ($records as $record) {
                if (!isset($owned[(string) $record['id']])) {
                    if ((string) ($record['studentId'] ?? '') !== (string) $student['id'] || ($record['status'] ?? '') !== 'Menunggu') {
                        respond(['error' => 'Pengajuan peminjaman tidak valid.'], 403);
                    }
                    $laptops = loadCollection($pdo, 'laptops');
                    $laptopAvailable = false;
                    foreach ($laptops as $laptop) {
                        if ((string) $laptop['id'] === (string) ($record['laptopId'] ?? '') && $laptop['status'] === 'Tersedia') {
                            $laptopAvailable = true;
                            break;
                        }
                    }
                    if (!$laptopAvailable || empty($record['returnPlan']) || empty($record['purpose'])) {
                        respond(['error' => 'Laptop tidak tersedia atau data pengajuan belum lengkap.'], 422);
                    }
                    foreach ($owned as $loan) {
                        if (in_array($loan['status'], ['Menunggu', 'Disetujui', 'Dipinjam'], true)) {
                            respond(['error' => 'Masih ada peminjaman aktif.'], 409);
                        }
                    }
                } elseif ($record !== $owned[(string) $record['id']]) {
                    respond(['error' => 'Perubahan status peminjaman hanya dapat dilakukan petugas.'], 403);
                }
            }
            if (count($records) < count($owned)) {
                respond(['error' => 'Data peminjaman tidak dapat dihapus.'], 403);
            }
            $existingIds = array_fill_keys(array_map(static fn(array $r): string => (string) $r['id'], $existing), true);
            foreach ($records as $record) {
                $recordId = (string) $record['id'];
                if (isset($existingIds[$recordId])) {
                    continue;
                }
                $stmt = $pdo->prepare('INSERT INTO app_data (collection, record_id, payload) VALUES (?, ?, ?)');
                $stmt->execute([$collection, $recordId, json_encode($record, JSON_UNESCAPED_UNICODE)]);
            }
        } else {
            $stmt = $pdo->prepare('DELETE FROM app_data WHERE collection = ?');
            $stmt->execute([$collection]);
            $insert = $pdo->prepare('INSERT INTO app_data (collection, record_id, payload) VALUES (?, ?, ?)');
            foreach ($records as $record) {
                $recordId = (string) $record['id'];
                if ($collection === 'users') {
                    $previous = null;
                    foreach ($existing as $oldUser) {
                        if ((string) $oldUser['id'] === $recordId) {
                            $previous = $oldUser;
                            break;
                        }
                    }
                    if (in_array(($record['password'] ?? ''), ['', '********'], true) && $previous) {
                        $record['password'] = $previous['password'];
                    } elseif (isset($record['password']) && $record['password'] !== '') {
                        $record['password'] = password_hash((string) $record['password'], PASSWORD_DEFAULT);
                    } else {
                        respond(['error' => 'Password wajib diisi untuk user baru.'], 422);
                    }
                }
                $insert->execute([$collection, $recordId, json_encode($record, JSON_UNESCAPED_UNICODE)]);
            }
        }
        $pdo->commit();
        $saved = loadCollection($pdo, $collection);
        if ($user['role'] === 'siswa') {
            $saved = array_values(array_filter($saved, static fn(array $record): bool => (string) ($record['studentId'] ?? '') === (string) ($student['id'] ?? '')));
        }
        respond(['records' => publicRecords($collection, $saved)]);
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log($error->getMessage());
        respond(['error' => 'Data gagal disimpan ke database. Periksa data dan coba lagi.'], 500);
    }
}

respond(['error' => 'Endpoint tidak ditemukan.'], 404);


