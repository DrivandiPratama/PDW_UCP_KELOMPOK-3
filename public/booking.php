<?php
/**
 * Room Booking Request — Drivandi (35%)
 * Form ajukan sewa. Validasi anti-double-booking.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login('login.php');

$kamar_id = (int)($_GET['kamar'] ?? $_POST['kamar_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM kamar WHERE id = ?");
$stmt->execute([$kamar_id]);
$kamar = $stmt->fetch();
if (!$kamar) { http_response_code(404); die('Kamar tidak ditemukan'); }

$me = current_user();
$err = '';
$success_kode = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['_csrf'] ?? '')) { $err = 'Sesi tidak valid.'; }
  elseif ($kamar['status'] !== 'tersedia') { $err = 'Kamar ini sedang terisi, tidak bisa diajukan.'; }
  else try {
    $nama    = trim($_POST['nama_lengkap'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $mulai   = $_POST['tanggal_mulai'] ?? '';
    $durasi  = (int)($_POST['durasi_bulan'] ?? 0);
    $catatan = trim($_POST['catatan'] ?? '');

    if (!$nama || !$telepon || !$email || !$mulai || $durasi < 1) throw new RuntimeException('Lengkapi semua field wajib.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email tidak valid.');
    if (strtotime($mulai) < strtotime(date('Y-m-d'))) throw new RuntimeException('Tanggal mulai tidak boleh masa lalu.');
    if ($durasi < 1 || $durasi > 24) throw new RuntimeException('Durasi sewa 1–24 bulan.');

    // Anti double booking: cek apakah user ini sudah punya pending pada kamar ini
    $check = $pdo->prepare("SELECT id FROM booking WHERE user_id = ? AND kamar_id = ? AND status IN ('pending','approved','ongoing')");
    $check->execute([$me['id'], $kamar_id]);
    if ($check->fetch()) throw new RuntimeException('Anda sudah memiliki permohonan aktif untuk kamar ini.');

    $kode = gen_kode_booking($pdo);
    $stmt = $pdo->prepare("
      INSERT INTO booking (kode_booking, user_id, kamar_id, nama_lengkap, telepon, email, tanggal_mulai, durasi_bulan, catatan, status)
      VALUES (?,?,?,?,?,?,?,?,?, 'pending')
    ");
    $stmt->execute([$kode, $me['id'], $kamar_id, $nama, $telepon, $email, $mulai, $durasi, $catatan]);

    $success_kode = $kode;
  } catch (Throwable $e) { $err = $e->getMessage(); }
}

$page_title = 'Ajukan Sewa — ' . $kamar['kode'];
include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
  <a href="detail.php?id=<?= $kamar['id'] ?>" class="text-sm text-slate-500 hover:text-slate-900">← Kembali ke detail kamar</a>

  <?php if ($success_kode): ?>
    <div class="card p-8 mt-4 text-center">
      <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 grid place-items-center text-emerald-600"><?= icon('check','w-8 h-8') ?></div>
      <h1 class="text-2xl font-bold mt-4">Permohonan Berhasil Dikirim</h1>
      <p class="text-slate-500 mt-2">Permohonan sewa Anda sedang menunggu persetujuan pemilik kos.</p>
      <div class="mt-5 inline-block px-5 py-3 rounded-xl bg-brand-50 border border-brand-100">
        <div class="text-xs text-brand-700 uppercase tracking-wider font-semibold">Kode Booking</div>
        <div class="text-xl font-bold text-brand-700 mt-0.5"><?= e($success_kode) ?></div>
      </div>
      <div class="mt-6 flex gap-2 justify-center">
        <a href="tagihan.php" class="btn btn-secondary">Lihat Tagihan Saya</a>
        <a href="/pdw-ucp/public/" class="btn btn-primary">Kembali ke Katalog</a>
      </div>
    </div>
  <?php else: ?>
  <div class="grid md:grid-cols-3 gap-5 mt-4">
    <div class="md:col-span-2 card p-6">
      <h1 class="text-2xl font-bold">Formulir Permohonan Sewa</h1>
      <p class="text-sm text-slate-500 mt-1">Isi data berikut dengan benar. Pemilik kos akan menghubungi Anda.</p>

      <?php if ($err): ?>
        <div class="mt-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"><?= e($err) ?></div>
      <?php endif; ?>

      <form method="post" class="mt-5 grid grid-cols-2 gap-3">
        <?= csrf_input() ?>
        <input type="hidden" name="kamar_id" value="<?= $kamar['id'] ?>">

        <div class="col-span-2">
          <label class="text-xs text-slate-500">Nama lengkap</label>
          <input name="nama_lengkap" required class="input mt-1" value="<?= e($_POST['nama_lengkap'] ?? $me['nama']) ?>">
        </div>
        <div>
          <label class="text-xs text-slate-500">Telepon / WA</label>
          <input name="telepon" required class="input mt-1" value="<?= e($_POST['telepon'] ?? $me['telepon']) ?>" placeholder="0812-…">
        </div>
        <div>
          <label class="text-xs text-slate-500">Email</label>
          <input type="email" name="email" required class="input mt-1" value="<?= e($_POST['email'] ?? $me['email']) ?>">
        </div>
        <div>
          <label class="text-xs text-slate-500">Tanggal mulai sewa</label>
          <input type="date" name="tanggal_mulai" required min="<?= date('Y-m-d') ?>" class="input mt-1" value="<?= e($_POST['tanggal_mulai'] ?? '') ?>">
        </div>
        <div>
          <label class="text-xs text-slate-500">Durasi (bulan)</label>
          <input type="number" name="durasi_bulan" required min="1" max="24" class="input mt-1" value="<?= e($_POST['durasi_bulan'] ?? '1') ?>">
        </div>
        <div class="col-span-2">
          <label class="text-xs text-slate-500">Catatan untuk pemilik (opsional)</label>
          <textarea name="catatan" rows="3" class="input mt-1" placeholder="Mis. saya seorang mahasiswa…"><?= e($_POST['catatan'] ?? '') ?></textarea>
        </div>
        <div class="col-span-2 pt-2">
          <button class="btn btn-cta w-full"><?= icon('home') ?> Ajukan Sewa</button>
        </div>
      </form>
    </div>

    <!-- Ringkasan kamar -->
    <aside class="card p-5 h-fit sticky top-24">
      <img src="<?= e($kamar['foto_utama']) ?>" class="rounded-xl aspect-[4/3] object-cover w-full">
      <div class="mt-3">
        <div class="font-bold text-lg"><?= e($kamar['kode']) ?> · <?= e($kamar['tipe']) ?></div>
        <div class="text-xs text-slate-500"><?= e($kamar['ukuran_m2']) ?> m²</div>
      </div>
      <div class="mt-4 p-3 rounded-lg bg-brand-50 border border-brand-100">
        <div class="text-xs text-brand-700 font-semibold uppercase">Harga sewa</div>
        <div class="text-xl font-bold text-brand-700"><?= rp($kamar['harga_bulanan']) ?> /bln</div>
      </div>
      <div class="text-xs text-slate-500 mt-3 leading-relaxed">
        <strong>Fasilitas:</strong> <?= e($kamar['fasilitas']) ?>
      </div>
    </aside>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
