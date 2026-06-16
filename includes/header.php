<?php
require_once __DIR__ . '/auth.php';
$me = current_user();
$page_title = $page_title ?? APP_NAME;
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?> — <?= e(APP_NAME) ?></title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%232563EB'/%3E%3Ctext x='50%25' y='56%25' font-size='34' font-family='sans-serif' font-weight='700' fill='white' text-anchor='middle' dominant-baseline='middle'%3EK%3C/text%3E%3C/svg%3E">

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: { sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'] },
        colors: {
          // Primary: Blue 600 · Secondary: Blue 100 · Accent: Amber 500
          brand:  { 50:'#eff6ff', 100:'#dbeafe', 200:'#bfdbfe', 500:'#3b82f6', 600:'#2563eb', 700:'#1d4ed8', 800:'#1e40af' },
          accent: { 100:'#fef3c7', 500:'#f59e0b', 600:'#d97706' }
        }
      }
    }
  };
</script>
<link rel="stylesheet" href="/pdw-ucp/assets/css/style.css">
</head>
<body class="font-sans bg-slate-50 text-slate-900 antialiased min-h-screen flex flex-col">

<!-- Navbar -->
<nav class="bg-white/80 backdrop-blur border-b border-slate-200 sticky top-0 z-40">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
    <a href="/pdw-ucp/public/" class="flex items-center gap-2">
      <span class="w-9 h-9 rounded-xl bg-brand-600 text-white grid place-items-center font-bold">K</span>
      <span class="font-bold tracking-tight"><?= e(APP_NAME) ?></span>
    </a>

    <button id="nav-toggle" class="md:hidden p-2 rounded-lg hover:bg-slate-100" aria-label="Menu">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    <div id="nav-menu" class="hidden md:flex items-center gap-1 text-sm">
      <a href="/pdw-ucp/public/" class="px-3 py-2 rounded-lg hover:bg-slate-100">Katalog</a>
      <a href="/pdw-ucp/public/pengumuman.php" class="px-3 py-2 rounded-lg hover:bg-slate-100">Pengumuman</a>
      <?php if ($me): ?>
        <?php if ($me['role'] === 'owner'): ?>
          <a href="/pdw-ucp/public/admin/" class="px-3 py-2 rounded-lg hover:bg-slate-100 font-medium text-brand-700">Dashboard</a>
        <?php else: ?>
          <a href="/pdw-ucp/public/tagihan.php" class="px-3 py-2 rounded-lg hover:bg-slate-100">Tagihan Saya</a>
        <?php endif; ?>
        <div class="ml-2 flex items-center gap-2 pl-3 border-l border-slate-200">
          <span class="w-8 h-8 rounded-full bg-brand-600 text-white grid place-items-center text-xs font-bold">
            <?= e(strtoupper(substr($me['nama'],0,1))) ?>
          </span>
          <span class="hidden sm:inline text-sm font-medium"><?= e($me['nama']) ?></span>
          <a href="/pdw-ucp/public/logout.php" class="text-sm text-slate-500 hover:text-red-600 ml-2">Keluar</a>
        </div>
      <?php else: ?>
        <a href="/pdw-ucp/public/login.php"    class="px-3 py-2 rounded-lg hover:bg-slate-100">Masuk</a>
        <a href="/pdw-ucp/public/register.php" class="ml-1 px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-700 text-white font-medium">Daftar</a>
      <?php endif; ?>
    </div>
  </div>

  <div id="nav-mobile" class="hidden md:hidden border-t border-slate-200 px-4 py-3 space-y-1 bg-white">
    <a href="/pdw-ucp/public/" class="block px-3 py-2 rounded-lg hover:bg-slate-100">Katalog</a>
    <a href="/pdw-ucp/public/pengumuman.php" class="block px-3 py-2 rounded-lg hover:bg-slate-100">Pengumuman</a>
    <?php if ($me): ?>
      <?php if ($me['role'] === 'owner'): ?>
        <a href="/pdw-ucp/public/admin/" class="block px-3 py-2 rounded-lg hover:bg-slate-100 font-medium text-brand-700">Dashboard</a>
      <?php else: ?>
        <a href="/pdw-ucp/public/tagihan.php" class="block px-3 py-2 rounded-lg hover:bg-slate-100">Tagihan Saya</a>
      <?php endif; ?>
      <a href="/pdw-ucp/public/logout.php" class="block px-3 py-2 rounded-lg hover:bg-slate-100 text-red-600">Keluar</a>
    <?php else: ?>
      <a href="/pdw-ucp/public/login.php"    class="block px-3 py-2 rounded-lg hover:bg-slate-100">Masuk</a>
      <a href="/pdw-ucp/public/register.php" class="block px-3 py-2 rounded-lg bg-brand-600 text-white font-medium">Daftar</a>
    <?php endif; ?>
  </div>
</nav>

<!-- Flash toasts -->
<?php $flashes = get_flashes(); if ($flashes): ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 mt-4 space-y-2">
  <?php foreach ($flashes as $f):
    $cls = $f['type']==='success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
         : ($f['type']==='error' ? 'bg-red-50 text-red-800 border-red-200'
         : 'bg-blue-50 text-blue-800 border-blue-200');
  ?>
    <div class="border rounded-xl px-4 py-3 text-sm <?= $cls ?>"><?= e($f['msg']) ?></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<main class="flex-1">
<script>
  document.getElementById('nav-toggle')?.addEventListener('click', () => {
    document.getElementById('nav-mobile').classList.toggle('hidden');
  });
</script>
