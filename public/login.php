<?php
require_once __DIR__ . '/../includes/auth.php';

// Pastikan akun demo tersedia begitu halaman login dibuka (jalan sekali saat DB kosong).
ensure_demo_users();

if (is_logged_in()) redirect(is_owner() ? 'admin/' : 'index.php');

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['_csrf'] ?? '')) { $err = 'Sesi tidak valid. Coba lagi.'; }
  else {
    $u = attempt_login($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($u) {
      set_flash('success', 'Selamat datang, ' . $u['nama'] . '!');
      redirect($u['role'] === 'owner' ? 'admin/' : 'index.php');
    }
    $err = 'Email atau password salah.';
  }
}

$page_title = 'Masuk';
include __DIR__ . '/../includes/header.php';
?>

<div class="min-h-[70vh] grid place-items-center px-4 py-8">
  <div class="w-full max-w-md card p-8">
    <h1 class="text-2xl font-bold">Masuk</h1>
    <p class="text-sm text-slate-500 mt-1">Akses akun penghuni atau pemilik kos Anda.</p>

    <?php if ($err): ?>
      <div class="mt-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"><?= e($err) ?></div>
    <?php endif; ?>

    <form method="post" class="mt-6 space-y-3">
      <?= csrf_input() ?>
      <div>
        <label class="text-xs text-slate-500">Email</label>
        <input type="email" name="email" required autofocus class="input mt-1" placeholder="anda@email.com" value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div>
        <label class="text-xs text-slate-500">Password</label>
        <input type="password" name="password" required class="input mt-1" placeholder="••••••••">
      </div>
      <button class="btn btn-primary w-full">Masuk</button>
    </form>

    <p class="text-sm text-slate-500 text-center mt-5">
      Belum punya akun? <a href="register.php" class="text-brand-600 font-semibold hover:underline">Daftar</a>
    </p>

    <div class="mt-6 p-3 rounded-lg bg-slate-50 text-xs text-slate-500 border border-slate-200">
      <div class="font-semibold text-slate-700 mb-1">Akun demo:</div>
      Owner: <code>owner@kos.id</code> / <code>owner123</code><br>
      Penghuni: <code>penghuni@kos.id</code> / <code>pengguna123</code>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
