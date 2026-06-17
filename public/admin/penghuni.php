<?php
require_once __DIR__ . '/../../includes/auth.php';
require_owner('/pdw-ucp/public/login.php');
$me = current_user();

$rows = $pdo->query("
  SELECT u.*,
    (SELECT COUNT(*) FROM booking b WHERE b.user_id = u.id AND b.status='ongoing') AS booking_aktif,
    (SELECT COALESCE(SUM(nominal),0) FROM tagihan t WHERE t.user_id = u.id AND t.status='belum') AS tagihan_belum
  FROM users u WHERE role='penghuni' ORDER BY u.nama
")->fetchAll();

$page_title = 'Penghuni';
include __DIR__ . '/../../includes/header.php';
?>

<div class="flex">
<?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

<div class="flex-1 p-4 md:p-8 max-w-7xl">
  <h1 class="text-2xl font-bold">Penghuni</h1>
  <p class="text-sm text-slate-500 mt-1">Daftar seluruh penghuni terdaftar.</p>

  <div class="card mt-5 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="tbl">
        <thead><tr><th>Nama</th><th>Email</th><th>Telepon</th><th class="text-center">Sewa Aktif</th><th class="text-right">Tunggakan</th><th>Bergabung</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $u): ?>
          <tr>
            <td>
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-brand-600 text-white grid place-items-center text-xs font-bold"><?= e(strtoupper(substr($u['nama'],0,1))) ?></div>
                <div><div class="font-semibold"><?= e($u['nama']) ?></div></div>
              </div>
            </td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['telepon'] ?? '-') ?></td>
            <td class="text-center"><?= $u['booking_aktif'] ?></td>
            <td class="text-right <?= $u['tagihan_belum']>0?'text-amber-600 font-semibold':'' ?>"><?= rp($u['tagihan_belum']) ?></td>
            <td><?= fmt_tgl($u['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
