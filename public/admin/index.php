<?php
/**
 * Owner Dashboard — Dzaki
 */
require_once __DIR__ . '/../../includes/auth.php';
require_owner('/pdw-ucp/public/login.php');
$me = current_user();

$stats = [
  'kamar_total'    => (int)$pdo->query("SELECT COUNT(*) FROM kamar")->fetchColumn(),
  'kamar_tersedia' => (int)$pdo->query("SELECT COUNT(*) FROM kamar WHERE status='tersedia'")->fetchColumn(),
  'kamar_terisi'   => (int)$pdo->query("SELECT COUNT(*) FROM kamar WHERE status='terisi'")->fetchColumn(),
  'penghuni'       => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='penghuni'")->fetchColumn(),
  'booking_pending'=> (int)$pdo->query("SELECT COUNT(*) FROM booking WHERE status='pending'")->fetchColumn(),
  'pembayaran_pending'=>(int)$pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status='pending'")->fetchColumn(),
  'pendapatan'     => (float)$pdo->query("SELECT COALESCE(SUM(nominal),0) FROM pembayaran WHERE status='verified' AND MONTH(verified_at)=MONTH(CURRENT_DATE) AND YEAR(verified_at)=YEAR(CURRENT_DATE)")->fetchColumn(),
  'tagihan_belum'  => (float)$pdo->query("SELECT COALESCE(SUM(nominal),0) FROM tagihan WHERE status='belum'")->fetchColumn(),
];

$pending_bookings = $pdo->query("
  SELECT b.*, k.kode AS kamar_kode, k.tipe AS kamar_tipe
  FROM booking b JOIN kamar k ON k.id = b.kamar_id
  WHERE b.status = 'pending'
  ORDER BY b.created_at DESC LIMIT 5
")->fetchAll();

// 6-month revenue for chart
$chartData = $pdo->query("
  SELECT DATE_FORMAT(verified_at,'%Y-%m') AS m, SUM(nominal) AS total
  FROM pembayaran
  WHERE status='verified' AND verified_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
  GROUP BY m ORDER BY m
")->fetchAll();

$page_title = 'Dashboard Pemilik';
include __DIR__ . '/../../includes/header.php';
?>

<div class="flex">
<?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

<div class="flex-1 p-4 md:p-8 max-w-7xl">
  <h1 class="text-2xl font-bold">Dashboard</h1>
  <p class="text-sm text-slate-500 mt-1">Ringkasan operasional kos Anda hari ini.</p>

  <!-- Stat cards -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-5">
    <?php
    $cards = [
      ['Total Kamar',       $stats['kamar_total'],    'bg-blue-50 text-blue-700'],
      ['Kamar Tersedia',    $stats['kamar_tersedia'], 'bg-emerald-50 text-emerald-700'],
      ['Kamar Terisi',      $stats['kamar_terisi'],   'bg-slate-100 text-slate-700'],
      ['Total Penghuni',    $stats['penghuni'],       'bg-violet-50 text-violet-700'],
    ];
    foreach ($cards as [$l,$v,$cls]): ?>
      <div class="card p-5">
        <div class="text-xs text-slate-500 uppercase tracking-wider"><?= $l ?></div>
        <div class="text-2xl font-extrabold mt-1"><?= $v ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="grid lg:grid-cols-3 gap-5 mt-5">
    <div class="card p-5 lg:col-span-2">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-xs text-slate-500 uppercase tracking-wider">Pendapatan Bulan Ini</div>
          <div class="text-3xl font-extrabold text-emerald-600 mt-1"><?= rp($stats['pendapatan']) ?></div>
        </div>
        <div class="text-right">
          <div class="text-xs text-slate-500 uppercase tracking-wider">Tagihan Belum Lunas</div>
          <div class="text-xl font-bold text-amber-600 mt-1"><?= rp($stats['tagihan_belum']) ?></div>
        </div>
      </div>
      <canvas id="chart-rev" height="120" class="mt-4"></canvas>
    </div>

    <div class="card p-5">
      <div class="font-semibold">Butuh Perhatian</div>
      <div class="space-y-3 mt-3">
        <a href="booking.php" class="flex items-center justify-between p-3 rounded-lg bg-amber-50 hover:bg-amber-100 transition">
          <div>
            <div class="text-sm font-semibold text-amber-800">Permohonan Sewa Baru</div>
            <div class="text-xs text-amber-700"><?= $stats['booking_pending'] ?> menunggu persetujuan</div>
          </div>
          <span class="text-amber-700"><?= icon('arrow','w-5 h-5') ?></span>
        </a>
        <a href="pembayaran.php" class="flex items-center justify-between p-3 rounded-lg bg-blue-50 hover:bg-blue-100 transition">
          <div>
            <div class="text-sm font-semibold text-blue-800">Verifikasi Pembayaran</div>
            <div class="text-xs text-blue-700"><?= $stats['pembayaran_pending'] ?> bukti pending</div>
          </div>
          <span class="text-blue-700"><?= icon('arrow','w-5 h-5') ?></span>
        </a>
      </div>
    </div>
  </div>

  <!-- Booking pending list -->
  <div class="card mt-5 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
      <h2 class="font-semibold">Permohonan Sewa Terbaru</h2>
      <a href="booking.php" class="text-sm text-brand-600 hover:underline">Lihat semua →</a>
    </div>
    <?php if (!$pending_bookings): ?>
      <div class="p-8 text-center text-slate-500">Tidak ada permohonan pending 🎉</div>
    <?php else: ?>
    <div class="overflow-x-auto">
      <table class="tbl">
        <thead><tr><th>Kode</th><th>Calon Penghuni</th><th>Kamar</th><th>Tanggal Mulai</th><th>Durasi</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pending_bookings as $b): ?>
          <tr>
            <td class="font-mono text-xs"><?= e($b['kode_booking']) ?></td>
            <td><?= e($b['nama_lengkap']) ?><div class="text-xs text-slate-400"><?= e($b['telepon']) ?></div></td>
            <td><?= e($b['kamar_kode']) ?> · <?= e($b['kamar_tipe']) ?></td>
            <td><?= fmt_tgl($b['tanggal_mulai']) ?></td>
            <td><?= e($b['durasi_bulan']) ?> bulan</td>
            <td class="text-right"><a href="booking.php#b<?= $b['id'] ?>" class="btn btn-primary text-xs px-3 py-1.5">Tinjau</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const labels = <?= json_encode(array_column($chartData, 'm')) ?>;
const data   = <?= json_encode(array_map(fn($r)=>(float)$r['total'], $chartData)) ?>;
new Chart(document.getElementById('chart-rev'), {
  type: 'bar',
  data: { labels, datasets: [{ label:'Pendapatan', data, backgroundColor:'#0ea5e9', borderRadius:6 }] },
  options: { responsive:true, plugins:{ legend:{display:false} }, scales:{ y:{ ticks:{ callback:v=>'Rp '+(v/1000).toFixed(0)+'k' }}}}
});
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
