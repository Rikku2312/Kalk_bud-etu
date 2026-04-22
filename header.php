<?php
ob_start();
// header.php — Wspólny nagłówek wszystkich stron
require_once 'db.php';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$pages = [
    'index'        => ['icon'=>'📊','label'=>'Dashboard'],
    'transactions' => ['icon'=>'💳','label'=>'Transakcje'],
    'budget'       => ['icon'=>'🎯','label'=>'Budżety'],
    'categories'   => ['icon'=>'🏷️','label'=>'Kategorie'],
    'savings'      => ['icon'=>'💰','label'=>'Cele oszczędnościowe'],
    'reports'      => ['icon'=>'📈','label'=>'Raporty'],
];
$pageTitles = [
    'index'        => ['Kalk Budget — Dashboard', 'Przegląd finansów'],
    'transactions' => ['Kalk Budget — Transakcje', 'Zarządzaj transakcjami'],
    'budget'       => ['Kalk Budget — Budżety',    'Limity miesięczne'],
    'categories'   => ['Kalk Budget — Kategorie',  'Zarządzaj kategoriami'],
    'savings'      => ['Kalk Budget — Oszczędności','Cele oszczędnościowe'],
    'reports'      => ['Kalk Budget — Raporty',    'Analizy i wykresy'],
];
[$pageTitle, $pageSubtitle] = $pageTitles[$currentPage] ?? ['Kalk Budget',''];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Kalk Budget — profesjonalna aplikacja do zarządzania budżetem domowym">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app-wrapper">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="logo-icon">💰</div>
      <div>
        <span>Kalk Budget</span>
        <small>Budżet domowy</small>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section">
        <div class="nav-section-title">Główne</div>
        <?php foreach ($pages as $slug => $info): ?>
        <a href="<?= $slug === 'index' ? 'index.php' : "$slug.php" ?>"
           class="nav-link <?= $currentPage === $slug ? 'active' : '' ?>">
          <span class="nav-icon"><?= $info['icon'] ?></span>
          <?= $info['label'] ?>
        </a>
        <?php endforeach; ?>
      </div>
    </nav>

    <div class="sidebar-footer">
      © <?= date('Y') ?> Kalk Budget
    </div>
  </aside>

  <!-- Main -->
  <div class="main-content">
    <header class="topbar">
      <div>
        <button class="hamburger" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
        <div class="topbar-title"><?= $pageTitles[$currentPage][0] ?? 'Kalk Budget' ?></div>
        <div class="topbar-subtitle"><?= $pageSubtitle ?></div>
      </div>
      <div class="topbar-actions">
        <span id="topbar-date" style="font-size:12px;color:var(--text-muted)"></span>
        <a href="transactions.php" class="btn btn-primary btn-sm">+ Dodaj transakcję</a>
      </div>
    </header>

    <div class="page-content fade-in">
