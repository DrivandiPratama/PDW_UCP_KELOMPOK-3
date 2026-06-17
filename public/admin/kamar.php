<?php
/**
 * CRUD Kamar — Dzaki
 */
require_once __DIR__ . '/../../includes/auth.php';
require_owner('/pdw-ucp/public/login.php');
$me = current_user();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  if (!csrf_check($_POST['_csrf'] ?? '')) { set_flash('error','CSRF gagal.'); }
  else {
    $stmt = $pdo->prepare("DELETE FROM kamar WHERE id = ?");
    $stmt->execute([(int)$_POST['id']]);
    set_flash('success','Kamar dihapus.');
  }
  redirect('kamar.php');
}

$rows = $pdo->query("SELECT * FROM kamar ORDER BY kode")->fetchAll();

$page_title = 'Kelola Kamar';
include __DIR__ . '/../../includes/header.php';
?>

<div class="flex">
<?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

<div class="flex-1 p-4 md:p-8 max-w-7xl">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold">Kelola Kamar</h1>
      <p class="text-sm text-slate-500 mt-1">CRUD data kamar: tambah, edit, hapus.</p>
    </div>
    <a href="kamar-form.php" class="btn btn-primary"><?= icon('plus') ?> Tambah Kamar</a>
  </div>

  <div class="card mt-5 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="tbl">
        <thead><tr><th>Foto</th><th>Kode</th><th>Tipe</th><th>Ukuran</th><th class="text-right">Harga</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><img src="<?= e($r['foto_utama'] ?: 'https://placehold.co/80x60') ?>" class="w-16 h-12 object-cover rounded"></td>
            <td class="font-semibold"><?= e($r['kode']) ?></td>
            <td><?= e($r['tipe']) ?></td>
            <td><?= e($r['ukuran_m2']) ?> m²</td>
            <td class="text-right font-semibold"><?= rp($r['harga_bulanan']) ?></td>
            <td><span class="badge badge-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
            <td class="text-right whitespace-nowrap">
              <a href="kamar-form.php?id=<?= $r['id'] ?>" class="btn btn-secondary text-xs px-3 py-1.5">Edit</a>
              <form method="post" data-confirm="Yakin hapus kamar <?= e($r['kode']) ?>?" class="inline">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button class="btn btn-danger text-xs px-3 py-1.5">Hapus</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>

<script src="/pdw-ucp/assets/js/app.js"></script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
