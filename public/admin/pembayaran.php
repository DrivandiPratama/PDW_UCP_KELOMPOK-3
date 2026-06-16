<?php
/**
 * Verifikasi pembayaran — Dzaki / Drivandi
 */
require_once __DIR__ . '/../../includes/auth.php';
require_owner('/pdw-ucp/public/login.php');
$me = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (csrf_check($_POST['_csrf'] ?? '')) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    $stmt = $pdo->prepare("SELECT * FROM pembayaran WHERE id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if ($p) {
      if ($action === 'verify') {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE pembayaran SET status='verified', verified_at=NOW(), verified_by=? WHERE id=?")->execute([$me['id'], $id]);
        if ($p['tagihan_id']) $pdo->prepare("UPDATE tagihan SET status='lunas' WHERE id=?")->execute([$p['tagihan_id']]);
        $pdo->commit();
        set_flash('success','Pembayaran diverifikasi. Tagihan terkait ditandai lunas.');
      } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE pembayaran SET status='rejected' WHERE id=?")->execute([$id]);
        set_flash('info','Pembayaran ditolak.');
      }
    }
  }
  redirect('pembayaran.php');
}

$status = $_GET['status'] ?? '';
$sql = "SELECT p.*, u.nama AS user_nama, u.email AS user_email,
               t.periode, t.nominal AS tagihan_nominal, k.kode AS kamar_kode
        FROM pembayaran p
        JOIN users u ON u.id = p.user_id
        LEFT JOIN tagihan t ON t.id = p.tagihan_id
        LEFT JOIN kamar k ON k.id = t.kamar_id";
$bind=[]; if($status){ $sql.=" WHERE p.status=?"; $bind[]=$status; }
$sql .= " ORDER BY (p.status='pending') DESC, p.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($bind);
$rows = $stmt->fetchAll();

$page_title = 'Verifikasi Pembayaran';
include __DIR__ . '/../../includes/header.php';
?>

<div class="flex">
<?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

<div class="flex-1 p-4 md:p-8 max-w-7xl">
  <h1 class="text-2xl font-bold">Verifikasi Pembayaran</h1>
  <p class="text-sm text-slate-500 mt-1">Periksa bukti transfer dan setujui pembayaran penghuni.</p>

  <div class="mt-4 flex gap-2 text-sm">
    <?php foreach (['' => 'Semua','pending'=>'Pending','verified'=>'Verified','rejected'=>'Rejected'] as $k=>$lbl): ?>
      <a href="?status=<?= $k ?>" class="px-3 py-1.5 rounded-lg <?= $status===$k?'bg-brand-500 text-white':'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>"><?= $lbl ?></a>
    <?php endforeach; ?>
  </div>

  <div class="grid md:grid-cols-2 gap-4 mt-5">
    <?php if (!$rows): ?>
      <div class="md:col-span-2 card p-8 text-center text-slate-500">Tidak ada data.</div>
    <?php endif; ?>
    <?php foreach ($rows as $p): ?>
      <div class="card p-5">
        <div class="flex justify-between items-start gap-2">
          <div>
            <div class="font-semibold"><?= e($p['user_nama']) ?></div>
            <div class="text-xs text-slate-500"><?= e($p['user_email']) ?> · <?= fmt_tgl_full($p['created_at']) ?></div>
          </div>
          <span class="badge badge-<?= e($p['status']) ?>"><?= e($p['status']) ?></span>
        </div>
        <div class="grid grid-cols-2 gap-3 mt-3 text-sm">
          <div><span class="text-slate-500">Periode:</span> <?= e($p['periode'] ?? '-') ?></div>
          <div><span class="text-slate-500">Kamar:</span> <?= e($p['kamar_kode'] ?? '-') ?></div>
          <div><span class="text-slate-500">Nominal:</span> <strong><?= rp($p['nominal']) ?></strong></div>
          <div><span class="text-slate-500">Metode:</span> <?= e($p['metode']) ?></div>
        </div>
        <?php if ($p['catatan']): ?>
          <div class="mt-2 text-sm text-slate-600 italic">"<?= e($p['catatan']) ?>"</div>
        <?php endif; ?>
        <?php if ($p['bukti_path']): ?>
          <a href="<?= e($p['bukti_path']) ?>" target="_blank" class="block mt-3">
            <img src="<?= e($p['bukti_path']) ?>" class="rounded-lg max-h-56 object-cover w-full border border-slate-200 hover:opacity-80 transition">
          </a>
        <?php else: ?>
          <div class="mt-3 p-3 bg-slate-50 rounded text-xs text-slate-500">Tidak ada bukti gambar.</div>
        <?php endif; ?>

        <?php if ($p['status'] === 'pending'): ?>
        <div class="flex gap-2 mt-4">
          <form method="post" class="flex-1" data-confirm="Verifikasi pembayaran ini?">
            <?= csrf_input() ?>
            <input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="action" value="verify">
            <button class="btn btn-success w-full"><?= icon('check') ?> Verifikasi</button>
          </form>
          <form method="post" class="flex-1" data-confirm="Tolak pembayaran ini?">
            <?= csrf_input() ?>
            <input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="action" value="reject">
            <button class="btn btn-danger w-full"><?= icon('x') ?> Tolak</button>
          </form>
        </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</div>

<script src="/pdw-ucp/assets/js/app.js"></script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
