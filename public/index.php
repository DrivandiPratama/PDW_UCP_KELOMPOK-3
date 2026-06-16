<?php
/**
 * Room Catalog (publik) — daftar kamar + filter & search.
 * Owner: Fadil
 */
require_once __DIR__ . '/../includes/auth.php';

// Filter parameter
$q          = trim($_GET['q'] ?? '');
$tipe       = $_GET['tipe'] ?? '';
$harga_max  = (int)($_GET['harga_max'] ?? 0);
$status     = $_GET['status'] ?? '';

// Build dynamic WHERE
$where = []; $bind = [];
if ($q !== '')        { $where[] = '(nama LIKE ? OR fasilitas LIKE ? OR kode LIKE ? OR deskripsi LIKE ?)'; $like='%'.$q.'%'; $bind=array_merge($bind,[$like,$like,$like,$like]); }
if ($tipe !== '')     { $where[] = 'tipe = ?'; $bind[] = $tipe; }
if ($harga_max > 0)   { $where[] = 'harga_bulanan <= ?'; $bind[] = $harga_max; }
if ($status !== '')   { $where[] = 'status = ?'; $bind[] = $status; }

// Note: kolom 'nama' tidak ada di kamar, ganti search ke kode/fasilitas/tipe/deskripsi
$where = str_replace('nama LIKE ?', 'tipe LIKE ?', $where);

$sql = "SELECT * FROM kamar"
     . ($where ? " WHERE " . implode(' AND ', $where) : "")
     . " ORDER BY (status='tersedia') DESC, harga_bulanan ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($bind);
$rooms = $stmt->fetchAll();

// Daftar tipe unik untuk dropdown
$tipes = $pdo->query("SELECT DISTINCT tipe FROM kamar ORDER BY tipe")->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Katalog Kamar';
include __DIR__ . '/../includes/header.php';
?>

<!-- Hero -->
<section class="bg-brand-600 text-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 md:py-20">
    <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight max-w-2xl">Temukan kos nyaman, langsung pesan online.</h1>
    <p class="mt-3 text-white/85 max-w-xl">Lihat detail kamar, fasilitas, dan harga. Ajukan sewa & upload bukti bayar tanpa perlu datang langsung.</p>
    <a href="#daftar" class="inline-flex mt-6 btn btn-secondary text-brand-700">Lihat Kamar Tersedia ↓</a>
  </div>
</section>

<!-- Filter -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 -mt-8 relative z-10" id="daftar">
  <form method="get" class="card p-4 grid grid-cols-1 md:grid-cols-5 gap-3">
    <div class="md:col-span-2">
      <label class="text-xs text-slate-500">Cari (kode/tipe/fasilitas)</label>
      <input name="q" value="<?= e($q) ?>" class="input mt-1" placeholder="mis. AC, K-101, Deluxe…">
    </div>
    <div>
      <label class="text-xs text-slate-500">Tipe</label>
      <select name="tipe" class="input mt-1">
        <option value="">Semua tipe</option>
        <?php foreach ($tipes as $t): ?>
          <option value="<?= e($t) ?>" <?= $tipe===$t?'selected':'' ?>><?= e($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-xs text-slate-500">Harga maks (Rp)</label>
      <input type="number" name="harga_max" value="<?= $harga_max ?: '' ?>" class="input mt-1" placeholder="2000000">
    </div>
    <div>
      <label class="text-xs text-slate-500">Status</label>
      <select name="status" class="input mt-1">
        <option value="">Semua</option>
        <option value="tersedia" <?= $status==='tersedia'?'selected':'' ?>>Tersedia</option>
        <option value="terisi"   <?= $status==='terisi'?'selected':''   ?>>Terisi</option>
      </select>
    </div>
    <div class="md:col-span-5 flex gap-2 justify-end">
      <a href="/pdw-ucp/public/" class="btn btn-secondary">Reset</a>
      <button class="btn btn-primary">Terapkan Filter</button>
    </div>
  </form>
</section>

<!-- Grid -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
  <div class="flex items-baseline justify-between mb-4">
    <h2 class="text-xl font-bold">Daftar Kamar</h2>
    <div class="text-sm text-slate-500"><?= count($rooms) ?> kamar ditemukan</div>
  </div>

  <?php if (!$rooms): ?>
    <div class="text-center py-16 text-slate-500">
      <div class="mx-auto w-12 h-12 mb-3 text-slate-300"><?= icon('search','w-12 h-12') ?></div>
      <p>Tidak ada kamar yang cocok dengan filter Anda.</p>
    </div>
  <?php else: ?>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php foreach ($rooms as $r): ?>
      <a href="detail.php?id=<?= $r['id'] ?>" class="card group block">
        <div class="aspect-[4/3] bg-slate-100 overflow-hidden">
          <img loading="lazy" src="<?= e($r['foto_utama'] ?: 'https://placehold.co/600x450?text=No+Image') ?>" class="w-full h-full object-cover img-hover">
        </div>
        <div class="p-4">
          <div class="flex justify-between items-start gap-2">
            <div>
              <div class="font-bold text-lg leading-tight"><?= e($r['kode']) ?> · <?= e($r['tipe']) ?></div>
              <div class="text-xs text-slate-500 mt-0.5"><?= e($r['ukuran_m2']) ?> m² · <?= e(explode(',', $r['fasilitas'])[0]) ?></div>
            </div>
            <span class="badge badge-<?= e($r['status']) ?>"><?= $r['status']==='tersedia'?'Tersedia':'Terisi' ?></span>
          </div>
          <div class="mt-3 flex items-baseline justify-between">
            <div>
              <div class="text-xl font-bold text-brand-700"><?= rp($r['harga_bulanan']) ?></div>
              <div class="text-xs text-slate-400">/bulan</div>
            </div>
            <span class="text-sm text-brand-600 font-semibold group-hover:underline">Detail →</span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
