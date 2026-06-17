<?php
/**
 * Helper functions — sanitize, format, redirect, CSRF.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// Escape output (XSS protection)
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/**
 * Inline SVG icon (Heroicons-style, stroke based). Pengganti emoji.
 * Pemakaian: <?= icon('home') ?> atau <?= icon('check', 'w-5 h-5') ?>
 */
function icon($name, $class = 'w-4 h-4') {
  $paths = [
    'home'    => '<path d="M3 12l9-9 9 9M5 10v10h14V10"/>',
    'upload'  => '<path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M7 9l5-5 5 5M12 4v12"/>',
    'check'   => '<path d="m5 12 5 5L20 7"/>',
    'x'       => '<path d="M6 6l12 12M18 6 6 18"/>',
    'printer' => '<path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M6 14h12v7H6z"/>',
    'plus'    => '<path d="M12 5v14m-7-7h14"/>',
    'search'  => '<path d="m21 21-5-5m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>',
    'inbox'   => '<path d="M22 12h-6l-2 3h-4l-2-3H2M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2Z"/>',
    'edit'    => '<path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>',
    'trash'   => '<path d="M4 7h16M10 11v6m4-6v6M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/>',
    'arrow'   => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
  ];
  $p = $paths[$name] ?? '';
  return '<svg class="' . $class . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">' . $p . '</svg>';
}

// Format rupiah
function rp($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

// Format tanggal Indonesia
function fmt_tgl($d) {
  if (!$d) return '-';
  $bln = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
  $t = strtotime($d);
  return date('j', $t) . ' ' . $bln[(int)date('n', $t)] . ' ' . date('Y', $t);
}

function fmt_tgl_full($d) {
  if (!$d) return '-';
  return date('d/m/Y H:i', strtotime($d));
}

// Relative time (e.g. "3 jam lalu")
function rel_time($d) {
  if (!$d) return '-';
  $diff = time() - strtotime($d);
  if ($diff < 60) return 'baru saja';
  if ($diff < 3600) return floor($diff/60) . ' menit lalu';
  if ($diff < 86400) return floor($diff/3600) . ' jam lalu';
  if ($diff < 2592000) return floor($diff/86400) . ' hari lalu';
  return fmt_tgl($d);
}

// Redirect helper
function redirect($path) {
  header('Location: ' . $path);
  exit;
}

// Flash messages (one-time toast)
function set_flash($type, $msg) {
  $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}
function get_flashes() {
  $f = $_SESSION['flash'] ?? [];
  unset($_SESSION['flash']);
  return $f;
}

// CSRF
function csrf_token() {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
  return $_SESSION['csrf'];
}
function csrf_check($token) {
  return is_string($token) && hash_equals($_SESSION['csrf'] ?? '', $token);
}
function csrf_input() {
  return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

// Generate kode booking unik (BK-YYYY-NNN)
function gen_kode_booking(PDO $pdo) {
  $year = date('Y');
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking WHERE YEAR(created_at) = ?");
  $stmt->execute([$year]);
  $n = (int)$stmt->fetchColumn() + 1;
  return sprintf('BK-%s-%03d', $year, $n);
}

// Upload helper — validasi MIME + ukuran
function handle_upload($field, $subfolder, $maxBytes = 2_000_000) {
  if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
  $file = $_FILES[$field];
  if ($file['size'] > $maxBytes) throw new RuntimeException('Ukuran file maksimal ' . ($maxBytes/1_000_000) . ' MB');
  $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($file['tmp_name']);
  if (!isset($allowed[$mime])) throw new RuntimeException('Format file tidak didukung. Gunakan JPG/PNG/WEBP.');
  $dir = UPLOAD_DIR . '/' . $subfolder;
  if (!is_dir($dir)) mkdir($dir, 0775, true);
  $name = uniqid($subfolder . '_', true) . '.' . $allowed[$mime];
  $dest = $dir . '/' . $name;
  if (!move_uploaded_file($file['tmp_name'], $dest)) throw new RuntimeException('Gagal menyimpan file.');
  return UPLOAD_URL . '/' . $subfolder . '/' . $name;
}

// Pagination helper
function paginate(array $params, int $total, int $perPage = 12) {
  $page = max(1, (int)($params['page'] ?? 1));
  $pages = max(1, (int)ceil($total / $perPage));
  $offset = ($page - 1) * $perPage;
  return compact('page','pages','perPage','offset','total');
}

// Get current URL path (for active nav)
function active($path) {
  $cur = strtok($_SERVER['REQUEST_URI'], '?');
  return str_ends_with($cur, $path) ? 'active' : '';
}
