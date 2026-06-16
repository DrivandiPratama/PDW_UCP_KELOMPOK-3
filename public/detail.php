<?php
/**
 * Detail kamar.
 * Owner: Fadil
 */
require_once __DIR__ . '/../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM kamar WHERE id = ?");
$stmt->execute([$id]);
$kamar = $stmt->fetch();
if (!$kamar) { http_response_code(404); die('Kamar tidak ditemukan'); }

$stmt = $pdo->prepare("SELECT path FROM foto_kamar WHERE kamar_id = ?");
$stmt->execute([$id]);
$fotos = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (!$fotos) $fotos = [$kamar['foto_utama']];

$fasilitas = array_map('trim', explode(',', $kamar['fasilitas']));

$page_title = $kamar['kode'] . ' — ' . $kamar['tipe'];
include __DIR__ . '/../includes/header.php';
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
  <a href="/pdw-ucp/public/" class="text-sm text-slate-500 hover:text-slate-900">← Kembali ke katalog</a>

  <div class="mt-4 grid lg:grid-cols-5 gap-6">
    <!-- Gallery -->
    <div class="lg:col-span-3">
      <div class="rounded-2xl overflow-hidden bg-slate-100 aspect-[4/3]">
        <img id="main-photo" src="<?= e($fotos[0] ?: $kamar['foto_utama']) ?>" class="w-full h-full object-cover">
      </div>
      <?php if (count($fotos) > 1): ?>
      <div class="grid grid-cols-4 gap-2 mt-2">
        <?php foreach ($fotos as $i => $f): ?>
          <button onclick="document.getElementById('main-photo').src='<?= e($f) ?>'" class="rounded-lg overflow-hidden border border-slate-200">
            <img src="<?= e($f) ?>" class="w-full h-20 object-cover">
          </button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Info -->
    <div class="lg:col-span-2">
      <span class="badge badge-<?= e($kamar['status']) ?>"><?= $kamar['status']==='tersedia'?'Tersedia':'Terisi' ?></span>
      <h1 class="text-3xl font-extrabold mt-3"><?= e($kamar['kode']) ?> · <?= e($kamar['tipe']) ?></h1>
      <p class="text-slate-500 mt-1"><?= e($kamar['ukuran_m2']) ?> m²</p>

      <div class="mt-5 p-4 rounded-2xl bg-brand-50 border border-brand-100">
        <div class="text-xs text-brand-700 font-semibold uppercase tracking-wider">Harga sewa</div>
        <div class="text-3xl font-extrabold text-brand-700 mt-1"><?= rp($kamar['harga_bulanan']) ?> <span class="text-base font-medium text-slate-500">/bulan</span></div>
      </div>

      <?php if ($kamar['status'] === 'tersedia'): ?>
        <a href="booking.php?kamar=<?= $kamar['id'] ?>" class="btn btn-cta w-full mt-5">
          <?= icon('home') ?> Ajukan Sewa Sekarang
        </a>
      <?php else: ?>
        <button disabled class="btn w-full mt-5 bg-slate-200 text-slate-500 cursor-not-allowed">Kamar Sedang Terisi</button>
      <?php endif; ?>

      <div class="mt-6">
        <h3 class="font-semibold">Fasilitas</h3>
        <ul class="mt-2 grid grid-cols-1 gap-1.5">
          <?php foreach ($fasilitas as $f): if(!$f) continue; ?>
            <li class="flex items-center gap-2 text-sm">
              <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m5 12 5 5L20 7"/></svg>
              <?= e($f) ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <?php if ($kamar['deskripsi']): ?>
      <div class="mt-6">
        <h3 class="font-semibold">Deskripsi</h3>
        <p class="text-sm text-slate-600 mt-2 leading-relaxed"><?= nl2br(e($kamar['deskripsi'])) ?></p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
