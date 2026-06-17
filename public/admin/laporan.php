<?php
/**
 * Laporan keuangan — Nur Sidik (10% fitur tambahan)
 */
require_once __DIR__ . '/../../includes/auth.php';
require_owner('/pdw-ucp/public/login.php');

$year = (int)($_GET['year'] ?? date('Y'));

// Pendapatan per bulan (verified)
$rev = $pdo->prepare("
  SELECT DATE_FORMAT(verified_at,'%Y-%m') AS m, SUM(nominal) AS total
  FROM pembayaran WHERE status='verified' AND YEAR(verified_at)=?
  GROUP BY m ORDER BY m
");
$rev->execute([$year]);
$rev = $rev->fetchAll();

// Per kamar
$byKamar = $pdo->prepare("
  SELECT k.kode, k.tipe, COALESCE(SUM(p.nominal),0) AS pendapatan
  FROM kamar k
  LEFT JOIN tagihan t ON t.kamar_id = k.id
  LEFT JOIN pembayaran p ON p.tagihan_id = t.id AND p.status='verified' AND YEAR(p.verified_at)=?
  GROUP BY k.id ORDER BY pendapatan DESC
");
$byKamar->execute([$year]);
$byKamar = $byKamar->fetchAll();

$total = array_sum(array_column($rev, 'total'));

$page_title = 'Laporan Keuangan';
include __DIR__ . '/../../includes/header.php';
?>

<div class="flex">
<?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

<div class="flex-1 p-4 md:p-8 max-w-7xl">
  <div class="flex flex-wrap items-center justify-between gap-2">
    <div>
      <h1 class="text-2xl font-bold">Laporan Keuangan</h1>
      <p class="text-sm text-slate-500 mt-1">Ringkasan pendapatan verified per bulan & per kamar.</p>
    </div>
    <form class="flex gap-2">
      <select name="year" class="input">
        <?php for ($y=(int)date('Y'); $y>=date('Y')-3; $y--): ?>
          <option value="<?= $y ?>" <?= $year===$y?'selected':'' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
      <button class="btn btn-primary">Filter</button>
      <a href="javascript:print()" class="btn btn-secondary no-print"><?= icon('printer') ?> Print</a>
    </form>
  </div>

  <div class="card p-5 mt-5">
    <div class="text-xs text-slate-500 uppercase tracking-wider">Total Pendapatan <?= $year ?></div>
    <div class="text-3xl font-extrabold text-emerald-600 mt-1"><?= rp($total) ?></div>
    <canvas id="chart-rev" height="100" class="mt-4"></canvas>
  </div>

  <div class="card mt-5 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 font-semibold">Pendapatan per Kamar</div>
    <div class="overflow-x-auto">
      <table class="tbl">
        <thead><tr><th>Kode</th><th>Tipe</th><th class="text-right">Pendapatan</th></tr></thead>
        <tbody>
        <?php foreach ($byKamar as $k): ?>
          <tr>
            <td class="font-semibold"><?= e($k['kode']) ?></td>
            <td><?= e($k['tipe']) ?></td>
            <td class="text-right font-semibold"><?= rp($k['pendapatan']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chart-rev'), {
  type:'line',
  data:{
    labels: <?= json_encode(array_column($rev,'m')) ?>,
    datasets:[{
      label:'Pendapatan',
      data: <?= json_encode(array_map(fn($r)=>(float)$r['total'],$rev)) ?>,
      borderColor:'#0ea5e9', backgroundColor:'rgba(14,165,233,.12)', fill:true, tension:.35, pointRadius:4
    }]
  },
  options:{ responsive:true, plugins:{ legend:{display:false} }, scales:{ y:{ ticks:{ callback:v=>'Rp '+(v/1000).toFixed(0)+'k' }}}}
});
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
