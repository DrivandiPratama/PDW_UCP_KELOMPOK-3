<?php
/**
 * Payment Proof Upload — Drivandi
 */
require_once __DIR__ . '/../includes/auth.php';
require_penghuni('login.php');
$me = current_user();

$tagihan_id = (int)($_GET['tagihan'] ?? $_POST['tagihan_id'] ?? 0);
$stmt = $pdo->prepare("
  SELECT t.*, k.kode AS kamar_kode, k.tipe AS kamar_tipe
  FROM tagihan t JOIN kamar k ON k.id = t.kamar_id
  WHERE t.id = ? AND t.user_id = ?
");
$stmt->execute([$tagihan_id, $me['id']]);
$tagihan = $stmt->fetch();
if (!$tagihan) { http_response_code(404); die('Tagihan tidak ditemukan.'); }

$err = ''; $ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['_csrf'] ?? '')) { $err = 'Sesi tidak valid.'; }
  else try {
    $nominal = (float)($_POST['nominal'] ?? 0);
    $metode  = $_POST['metode'] ?? 'transfer';
    $catatan = trim($_POST['catatan'] ?? '');
    if ($nominal <= 0) throw new RuntimeException('Nominal harus lebih dari 0.');

    $path = handle_upload('bukti', 'bukti', 3_000_000);
    if (!$path) throw new RuntimeException('Wajib upload foto bukti transfer.');

    $stmt = $pdo->prepare("
      INSERT INTO pembayaran (user_id, tagihan_id, nominal, metode, bukti_path, status, catatan)
      VALUES (?, ?, ?, ?, ?, 'pending', ?)
    ");
    $stmt->execute([$me['id'], $tagihan_id, $nominal, $metode, $path, $catatan]);
    $ok = true;
    set_flash('success', 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi pemilik.');
  } catch (Throwable $e) { $err = $e->getMessage(); }
}

$page_title = 'Upload Bukti — ' . $tagihan['periode'];
include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
  <a href="tagihan.php" class="text-sm text-slate-500 hover:text-slate-900">← Kembali ke tagihan</a>

  <?php if ($ok): ?>
    <div class="card p-8 mt-4 text-center">
      <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 grid place-items-center text-emerald-600"><?= icon('check','w-8 h-8') ?></div>
      <h1 class="text-2xl font-bold mt-4">Bukti Terkirim</h1>
      <p class="text-slate-500 mt-2">Pemilik kos akan memverifikasi pembayaran Anda. Status akan berubah menjadi <strong>Lunas</strong> setelah disetujui.</p>
      <a href="tagihan.php" class="btn btn-primary mt-6">Lihat Tagihan Saya</a>
    </div>
  <?php else: ?>
  <div class="card p-6 mt-4">
    <h1 class="text-2xl font-bold">Upload Bukti Pembayaran</h1>
    <p class="text-sm text-slate-500 mt-1">Tagihan periode <strong><?= e($tagihan['periode']) ?></strong> — <?= e($tagihan['kamar_kode']) ?></p>

    <div class="mt-4 p-3 rounded-lg bg-brand-50 border border-brand-100 text-sm">
      Nominal tagihan: <strong class="text-brand-700"><?= rp($tagihan['nominal']) ?></strong> · Jatuh tempo: <?= fmt_tgl($tagihan['due_date']) ?>
    </div>

    <?php if ($err): ?>
      <div class="mt-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm"><?= e($err) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="mt-5 space-y-4">
      <?= csrf_input() ?>
      <input type="hidden" name="tagihan_id" value="<?= $tagihan['id'] ?>">

      <div>
        <label class="text-xs text-slate-500">Nominal yang dibayar (Rp)</label>
        <input type="number" name="nominal" required min="1" class="input mt-1" value="<?= e($tagihan['nominal']) ?>">
      </div>

      <div>
        <label class="text-xs text-slate-500">Metode pembayaran</label>
        <select name="metode" class="input mt-1">
          <option value="transfer">Transfer Bank</option>
          <option value="e-wallet">E-Wallet (OVO/DANA/GoPay)</option>
          <option value="tunai">Tunai</option>
        </select>
      </div>

      <div>
        <label class="text-xs text-slate-500">Foto bukti transfer (JPG/PNG, max 3 MB)</label>
        <input type="file" name="bukti" accept="image/*" required data-preview="#preview" class="input mt-1">
        <img id="preview" class="hidden mt-3 max-h-64 rounded-lg border border-slate-200" alt="preview">
      </div>

      <div>
        <label class="text-xs text-slate-500">Catatan (opsional)</label>
        <textarea name="catatan" rows="2" class="input mt-1" placeholder="Mis. transfer via BCA…"></textarea>
      </div>

      <button class="btn btn-cta w-full"><?= icon('upload') ?> Upload Bukti Pembayaran</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<script src="/pdw-ucp/assets/js/app.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
