<?php
/**
 * Validator Server-Side untuk Form Validation & Input Sanitization
 */

class Validator {
    private $data = [];
    private $errors = [];
    private $db = null;

    public function __construct($data, $db = null) {
        $this->data = $this->sanitize($data);
        $this->db = $db;
    }

    /**
     * Sanitasi data input mencegah XSS & Bad Characters
     */
    private function sanitize($input) {
        if (is_array($input)) {
            $cleaned = [];
            foreach ($input as $k => $v) {
                $cleaned[$k] = $this->sanitize($v);
            }
            return $cleaned;
        }
        return is_string($input) ? htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8') : $input;
    }

    /**
     * Jalankan aturan validasi
     * Contoh:
     * [
     *   'kode_aset' => ['required', 'min:3', 'unique:laptop,kode_aset'],
     *   'ram' => ['required']
     * ]
     */
    public function validate($rules = []) {
        foreach ($rules as $field => $fieldRules) {
            $val = $this->data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                if ($rule === 'required') {
                    if ($val === null || $val === '' || (is_string($val) && strlen(trim($val)) === 0)) {
                        $this->addError($field, "Field {$field} wajib diisi.");
                        break;
                    }
                } elseif ($rule === 'numeric') {
                    if ($val !== null && $val !== '' && !is_numeric($val)) {
                        $this->addError($field, "Field {$field} harus berupa angka.");
                    }
                } elseif (strpos($rule, 'min:') === 0) {
                    $min = (int) substr($rule, 4);
                    if ($val !== null && strlen(trim($val)) < $min) {
                        $this->addError($field, "Field {$field} minimal {$min} karakter.");
                    }
                } elseif (strpos($rule, 'max:') === 0) {
                    $max = (int) substr($rule, 4);
                    if ($val !== null && strlen(trim($val)) > $max) {
                        $this->addError($field, "Field {$field} maksimal {$max} karakter.");
                    }
                } elseif (strpos($rule, 'in:') === 0) {
                    $allowed = explode(',', substr($rule, 3));
                    if ($val !== null && !in_array($val, $allowed)) {
                        $this->addError($field, "Nilai {$field} tidak valid.");
                    }
                } elseif (strpos($rule, 'date_not_before:') === 0) {
                    $ref = substr($rule, 16);
                    if ($val !== null && strtotime($val) < strtotime($ref)) {
                        $this->addError($field, "Tanggal {$field} tidak boleh sebelum {$ref}.");
                    }
                } elseif (strpos($rule, 'unique:') === 0 && $this->db) {
                    // format: unique:table,column[,excludeId]
                    $parts = explode(',', substr($rule, 7));
                    $table = $parts[0];
                    $col = $parts[1];
                    $excludeId = $parts[2] ?? null;

                    $query = "SELECT COUNT(*) as cnt FROM `{$table}` WHERE `{$col}` = :val";
                    $params = [':val' => $val];
                    if ($excludeId !== null) {
                        $query .= " AND id_{$table} != :exId";
                        $params[':exId'] = $excludeId;
                    }

                    // PREPARED STATEMENT UNTUK VALIDASI UNIQUE
                    $stmt = $this->db->prepare($query);
                    $stmt->execute($params);
                    $row = $stmt->fetch();
                    if ($row && $row['cnt'] > 0) {
                        $this->addError($field, "{$field} sudah digunakan.");
                    }
                }
            }
        }

        return count($this->errors) === 0;
    }

    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getData() {
        return $this->data;
    }
}
