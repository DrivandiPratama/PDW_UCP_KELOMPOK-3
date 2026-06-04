<?php
/**
 * One-time setup: generate akun demo dengan bcrypt hash valid.
 * URL: http://localhost/PDW-UCP/public/setup.php
 *
 * ⚠️ HAPUS FILE INI SETELAH DIJALANKAN!
 */
require __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><meta charset=utf-8><title>Setup</title>
<style>body{font-family:sans-serif;max-width:600px;margin:3rem auto;padding:1rem;background:#f8fafc;color:#0f172a}
.box{background:white;border:1px solid #e2e8f0;border-radius:12px;padding:2rem;box-shadow:0 4px 16px rgba(0,0,0,.04)}
.ok{color:#059669}.warn{color:#dc2626;background:#fef2f2;padding:.75rem;border-radius:8px;margin-top:1rem}
code{background:#f1f5f9;padding:2px 6px;border-radius:4px}</style>
<div class=box><h2>🔧 Setup Akun Demo</h2>';

$users = [
  ['Pemilik Kos',  'owner@kos.id',    'owner123',    '0812-1000-0001', 'owner',    'Jl. Mawar No. 1'],
  ['Andi Saputra', 'penghuni@kos.id', 'pengguna123', '0812-2000-0002', 'penghuni', 'Bantul'],
  ['Budi Hartono', 'budi@kos.id',     'pengguna123', '0812-2000-0003', 'penghuni', 'Sleman'],
  ['Citra Dewi',   'citra@kos.id',    'pengguna123', '0812-2000-0004', 'penghuni', 'Yogyakarta'],
];

try {
  $stmt = $pdo->prepare("
    INSERT INTO users (nama, email, password_hash, telepon, role, alamat)
    VALUES (?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), nama = VALUES(nama)
  ");
  foreach ($users as $u) {
    $hash = password_hash($u[2], PASSWORD_BCRYPT);
    $stmt->execute([$u[0], $u[1], $hash, $u[3], $u[4], $u[5]]);
    echo "<p class=ok>✓ <code>{$u[1]}</code> ({$u[4]}) — password: <code>{$u[2]}</code></p>";
  }
  echo '<div class=warn>⚠️ <strong>HAPUS file <code>public/setup.php</code> sekarang juga</strong> agar tidak disalahgunakan!</div>';
  echo '<p style="margin-top:1.5rem"><a href="login.php">→ Lanjut ke halaman Login</a></p>';
} catch (Throwable $e) {
  echo "<p style='color:#dc2626'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
  echo "<p>Pastikan <code>schema.sql</code> sudah diimport ke database <code>kos_indekos</code>.</p>";
}
echo '</div>';
