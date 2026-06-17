<?php
/**
 * Approve / Reject booking requests — Dzaki
 */
require_once __DIR__ . '/../../includes/auth.php';
require_owner('/pdw-ucp/public/login.php');
$me = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (csrf_check($_POST['_csrf'] ?? '')) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'] ?? '';
    $stmt = $pdo->prepare("SELECT b.*, k.harga_bulanan FROM booking b JOIN kamar k ON k.id = b.kamar_id WHERE b.id = ?");
    $stmt->execute([$id]);
    $b = $stmt->fetch();
    if ($b) {
      if ($action === 'approve') {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE booking SET status='ongoing', approved_at=NOW() WHERE id=?")->execute([$id]);
        $pdo->prepare("UPDATE kamar SET status='terisi' WHERE id=?")->execute([$b['kamar_id']]);
        // Generate tagihan bulanan
        $ins = $pdo->prepare("INSERT IGNORE INTO tagihan (booking_id, user_id, kamar_id, periode, nominal, due_date) VALUES (?,?,?,?,?,?)");
        $start = new DateTime($b['tanggal_mulai']);
        for ($i=0; $i < (int)$b['durasi_bulan']; $i++) {
          $p = $start->format('Y-m');
          $due = $start->format('Y-m-05');
          $ins->execute([$b['id'], $b['user_id'], $b['kamar_id'], $p, $b['harga_bulanan'], $due]);
          $start->modify('+1 month');
        }
        $pdo->commit();
        set_flash('success', 'Booking disetujui & tagihan dibuat.');
      } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE booking SET status='rejected' WHERE id=?")->execute([$id]);
        set_flash('info','Booking ditolak.');
      } elseif ($action === 'complete') {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE booking SET status='completed' WHERE id=?")->execute([$id]);
        $pdo->prepare("UPDATE kamar SET status='tersedia' WHERE id=?")->execute([$b['kamar_id']]);
        $pdo->commit();
        set_flash('success','Booking ditandai selesai. Kamar kembali tersedia.');
      }
    }
  }
  redirect('booking.php');
}

$status_filter = $_GET['status'] ?? '';
$sql = "SELECT b.*, k.kode AS kamar_kode, k.tipe AS kamar_tipe, u.email AS user_email
        FROM booking b
        JOIN kamar k ON k.id = b.kamar_id
        JOIN users u ON u.id = b.user_id";
$bind = [];
if ($status_filter) { $sql .= " WHERE b.status = ?"; $bind[] = $status_filter; }
$sql .= " ORDER BY (b.status='pending') DESC, b.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($bind);
$rows = $stmt->fetchAll();

$page_title = 'Permohonan Sewa';
include __DIR__ . '/../../includes/header.php';
?>

<div class="flex">
<?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

<div class="flex-1 p-4 md:p-8 max-w-7xl">
  <h1 class="text-2xl font-bold">Permohonan Sewa</h1>
  <p class="text-sm text-slate-500 mt-1">Setujui atau tolak permohonan calon penghuni.</p>

  <div class="mt-4 flex gap-2 text-sm">
    <?php foreach (['' => 'Semua','pending'=>'Pending','ongoing'=>'Aktif','rejected'=>'Ditolak','completed'=>'Selesai'] as $k=>$lbl): ?>
      <a href="?status=<?= $k ?>" class="px-3 py-1.5 rounded-lg <?= $status_filter===$k?'bg-brand-500 text-white':'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>"><?= $lbl ?></a>
    <?php endforeach; ?>
  </div>

  <div class="space-y-3 mt-5">
    <?php if (!$rows): ?>
      <div class="card p-8 text-center text-slate-500">Tidak ada data.</div>
    <?php endif; ?>
    <?php foreach ($rows as $b): ?>
      <div id="b<?= $b['id'] ?>" class="card p-5 flex flex-col md:flex-row gap-4">
        <div class="flex-1">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="font-mono text-xs text-slate-500"><?= e($b['kode_booking']) ?></span>
            <span class="badge badge-<?= e($b['status']) ?>"><?= e($b['status']) ?></span>
          </div>
          <div class="mt-2 grid sm:grid-cols-2 gap-3 text-sm">
            <div><span class="text-slate-500">Nama:</span> <strong><?= e($b['nama_lengkap']) ?></strong></div>
            <div><span class="text-slate-500">Telepon:</span> <?= e($b['telepon']) ?></div>
            <div><span class="text-slate-500">Email:</span> <?= e($b['email']) ?></div>
            <div><span class="text-slate-500">Kamar:</span> <strong><?= e($b['kamar_kode']) ?></strong> · <?= e($b['kamar_tipe']) ?></div>
            <div><span class="text-slate-500">Mulai:</span> <?= fmt_tgl($b['tanggal_mulai']) ?></div>
            <div><span class="text-slate-500">Durasi:</span> <?= e($b['durasi_bulan']) ?> bulan</div>
          </div>
          <?php if ($b['catatan']): ?>
            <div class="mt-3 text-sm text-slate-600 italic">"<?= e($b['catatan']) ?>"</div>
          <?php endif; ?>
        </div>
        <div class="flex md:flex-col gap-2 md:items-end">
          <?php if ($b['status'] === 'pending'): ?>
            <form method="post" class="inline" data-confirm="Setujui permohonan ini?">
              <?= csrf_input() ?>
              <input type="hidden" name="id" value="<?= $b['id'] ?>"><input type="hidden" name="action" value="approve">
              <button class="btn btn-success"><?= icon('check') ?> Setujui</button>
            </form>
            <form method="post" class="inline" data-confirm="Tolak permohonan ini?">
              <?= csrf_input() ?>
              <input type="hidden" name="id" value="<?= $b['id'] ?>"><input type="hidden" name="action" value="reject">
              <button class="btn btn-danger"><?= icon('x') ?> Tolak</button>
            </form>
          <?php elseif ($b['status'] === 'ongoing'): ?>
            <form method="post" class="inline" data-confirm="Tandai sewa selesai?">
              <?= csrf_input() ?>
              <input type="hidden" name="id" value="<?= $b['id'] ?>"><input type="hidden" name="action" value="complete">
              <button class="btn btn-secondary">Tandai Selesai</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</div>

<script src="/pdw-ucp/assets/js/app.js"></script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
