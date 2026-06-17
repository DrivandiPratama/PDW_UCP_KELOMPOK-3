<?php
/**
 * Billing Summary (penghuni) — Dzaki / Drivandi sync.
 */
require_once __DIR__ . '/../includes/auth.php';
require_penghuni('login.php');
$me = current_user();

$stmt = $pdo->prepare("
  SELECT t.*, k.kode AS kamar_kode, k.tipe AS kamar_tipe
  FROM tagihan t
  JOIN kamar k ON k.id = t.kamar_id
  WHERE t.user_id = ?
  ORDER BY t.periode DESC
");
$stmt->execute([$me['id']]);
$tagihan = $stmt->fetchAll();

// Booking pending status (info atas)
$stmt = $pdo->prepare("
  SELECT b.*, k.kode AS kamar_kode, k.tipe AS kamar_tipe, k.foto_utama
  FROM booking b
  JOIN kamar k ON k.id = b.kamar_id
  WHERE b.user_id = ?
  ORDER BY b.created_at DESC LIMIT 5
");
$stmt->execute([$me['id']]);
$bookings = $stmt->fetchAll();

$total_belum = array_sum(array_map(fn($t) => $t['status']==='belum' ? $t['nominal'] : 0, $tagihan));
$total_lunas = array_sum(array_map(fn($t) => $t['status']==='lunas' ? $t['nominal'] : 0, $tagihan));

$page_title = 'Tagihan Saya';
include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
  <h1 class="text-2xl font-bold">Tagihan Saya</h1>
  <p class="text-sm text-slate-500 mt-1">Riwayat tagihan bulanan & status pembayaran.</p>

  <!-- Ringkasan -->
  <div class="grid sm:grid-cols-3 gap-3 mt-5">
    <div class="card p-5">
      <div class="text-xs text-slate-500 uppercase tracking-wider">Total Belum Lunas</div>
      <div class="text-2xl font-bold text-amber-600 mt-1"><?= rp($total_belum) ?></div>
    </div>
    <div class="card p-5">
      <div class="text-xs text-slate-500 uppercase tracking-wider">Total Sudah Lunas</div>
      <div class="text-2xl font-bold text-emerald-600 mt-1"><?= rp($total_lunas) ?></div>
    </div>
    <div class="card p-5">
      <div class="text-xs text-slate-500 uppercase tracking-wider">Booking Aktif</div>
      <div class="text-2xl font-bold mt-1"><?= count(array_filter($bookings, fn($b)=>in_array($b['status'],['ongoing','approved']))) ?></div>
    </div>
  </div>

  <!-- Bookings -->
  <h2 class="text-lg font-semibold mt-8">Permohonan & Sewa Aktif</h2>
  <?php if (!$bookings): ?>
    <div class="card p-8 mt-3 text-center text-slate-500">
      Belum ada permohonan sewa. <a href="/pdw-ucp/public/" class="text-brand-600 hover:underline">Cari kamar →</a>
    </div>
  <?php else: ?>
  <div class="grid md:grid-cols-2 gap-4 mt-3">
    <?php foreach ($bookings as $b): ?>
    <div class="card p-4 flex gap-4">
      <img src="<?= e($b['foto_utama']) ?>" class="w-24 h-24 rounded-lg object-cover shrink-0">
      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between gap-2">
          <div>
            <div class="font-semibold"><?= e($b['kamar_kode']) ?> · <?= e($b['kamar_tipe']) ?></div>
            <div class="text-xs text-slate-500"><?= e($b['kode_booking']) ?></div>
          </div>
          <span class="badge badge-<?= e($b['status']) ?>"><?= e($b['status']) ?></span>
        </div>
        <div class="text-xs text-slate-500 mt-2">
          Mulai: <?= fmt_tgl($b['tanggal_mulai']) ?> · <?= e($b['durasi_bulan']) ?> bulan
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Tagihan list -->
  <h2 class="text-lg font-semibold mt-8">Daftar Tagihan</h2>
  <?php if (!$tagihan): ?>
    <div class="card p-8 mt-3 text-center text-slate-500">Belum ada tagihan.</div>
  <?php else: ?>
  <div class="card mt-3 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="tbl">
        <thead><tr>
          <th>Periode</th><th>Kamar</th><th>Jatuh Tempo</th><th class="text-right">Nominal</th><th>Status</th><th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($tagihan as $t): ?>
          <tr>
            <td class="font-medium"><?= e($t['periode']) ?></td>
            <td><?= e($t['kamar_kode']) ?> · <?= e($t['kamar_tipe']) ?></td>
            <td><?= fmt_tgl($t['due_date']) ?></td>
            <td class="text-right font-semibold"><?= rp($t['nominal']) ?></td>
            <td><span class="badge badge-<?= e($t['status']) ?>"><?= e($t['status']) ?></span></td>
            <td class="text-right">
              <?php if ($t['status'] === 'belum'): ?>
                <a href="upload-bukti.php?tagihan=<?= $t['id'] ?>" class="btn btn-cta px-4 py-2 text-sm"><?= icon('upload') ?> Upload Bukti</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
