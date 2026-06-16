<?php
/**
 * Auth: login, register, session, role guard.
 */
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/database.php';

function current_user() {
  global $pdo;
  if (empty($_SESSION['uid'])) return null;
  static $cache = null;
  if ($cache && $cache['id'] == $_SESSION['uid']) return $cache;
  $stmt = $pdo->prepare("SELECT id, nama, email, telepon, role, alamat, foto FROM users WHERE id = ?");
  $stmt->execute([$_SESSION['uid']]);
  $cache = $stmt->fetch();
  return $cache ?: null;
}

function is_logged_in() { return !empty($_SESSION['uid']); }
function is_owner()     { $u = current_user(); return $u && $u['role'] === 'owner'; }
function is_penghuni()  { $u = current_user(); return $u && $u['role'] === 'penghuni'; }

function require_login($redir = '/pdw-ucp/public/login.php') {
  if (!is_logged_in()) { set_flash('error', 'Silakan login terlebih dahulu.'); redirect($redir); }
}
function require_owner($redir = '/pdw-ucp/public/login.php') {
  require_login($redir);
  if (!is_owner()) { set_flash('error', 'Akses ditolak — khusus pemilik kos.'); redirect($redir); }
}
function require_penghuni($redir = '/pdw-ucp/public/login.php') {
  require_login($redir);
  if (!is_penghuni()) { set_flash('error', 'Akses ditolak.'); redirect($redir); }
}

/**
 * Auto-seed akun demo bila tabel users masih kosong.
 * Dipanggil sebelum login — jadi tidak perlu file setup.php manual.
 * Aman dijalankan berkali-kali (hanya seed saat benar-benar kosong).
 */
function ensure_demo_users() {
  global $pdo;
  try {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count > 0) return;

    $demo = [
      ['Pemilik Kos',  'owner@kos.id',    'owner123',    '0812-1000-0001', 'owner',    'Jl. Mawar No. 1'],
      ['Andi Saputra', 'penghuni@kos.id', 'pengguna123', '0812-2000-0002', 'penghuni', 'Bantul'],
      ['Budi Hartono', 'budi@kos.id',     'pengguna123', '0812-2000-0003', 'penghuni', 'Sleman'],
      ['Citra Dewi',   'citra@kos.id',    'pengguna123', '0812-2000-0004', 'penghuni', 'Yogyakarta'],
    ];
    $stmt = $pdo->prepare("INSERT INTO users (nama, email, password_hash, telepon, role, alamat) VALUES (?,?,?,?,?,?)");
    foreach ($demo as $u) {
      $stmt->execute([$u[0], $u[1], password_hash($u[2], PASSWORD_BCRYPT), $u[3], $u[4], $u[5]]);
    }
  } catch (Throwable $e) {
    // Diamkan — kalau tabel belum ada, biar error koneksi/schema yang muncul di tempat lain.
  }
}

function attempt_login($email, $password) {
  global $pdo;
  ensure_demo_users();
  $stmt = $pdo->prepare("SELECT id, password_hash, role, nama FROM users WHERE email = ? LIMIT 1");
  $stmt->execute([$email]);
  $u = $stmt->fetch();
  if (!$u || !password_verify($password, $u['password_hash'])) return false;
  session_regenerate_id(true);
  $_SESSION['uid']  = $u['id'];
  $_SESSION['role'] = $u['role'];
  $_SESSION['nama'] = $u['nama'];
  return $u;
}

function register_user($nama, $email, $password, $telepon = null, $alamat = null) {
  global $pdo;
  if (strlen($password) < 6) throw new RuntimeException('Password minimal 6 karakter.');
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email tidak valid.');
  $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
  $stmt->execute([$email]);
  if ($stmt->fetch()) throw new RuntimeException('Email sudah terdaftar.');
  $hash = password_hash($password, PASSWORD_BCRYPT);
  $stmt = $pdo->prepare("INSERT INTO users (nama, email, password_hash, telepon, alamat, role) VALUES (?,?,?,?,?,'penghuni')");
  $stmt->execute([$nama, $email, $hash, $telepon, $alamat]);
  return (int)$pdo->lastInsertId();
}

function do_logout() {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
  }
  session_destroy();
}
