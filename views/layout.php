<!DOCTYPE html>
<html lang="<?= I18n::currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? t('app_name') ?> — <?= t('app_name') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<!-- Sidebar Navigation -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="logo-icon">⬡</div>
            <div class="logo-text">
                <span class="logo-name">ASTERISK</span>
                <span class="logo-sub">MANAGER</span>
            </div>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle" title="Toggle sidebar">☰</button>
    </div>

    <!-- AMI Status pill -->
    <div class="ami-status <?= ($amiConnected ?? false) ? 'connected' : 'disconnected' ?>">
        <span class="ami-dot"></span>
        <span class="ami-label"><?= ($amiConnected ?? false) ? 'AMI Connected' : 'AMI Offline' ?></span>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">OVERVIEW</div>
        <a href="?page=dashboard" class="nav-item<?= activeNav('dashboard') ?>">
            <span class="nav-icon">◈</span><span class="nav-label"><?= t('nav_dashboard') ?></span>
        </a>

        <div class="nav-section-label">TELEPHONY</div>
        <a href="?page=extensions" class="nav-item<?= activeNav('extensions') ?>">
            <span class="nav-icon">☎</span><span class="nav-label"><?= t('nav_extensions') ?></span>
        </a>
        <a href="?page=trunks" class="nav-item<?= activeNav('trunks') ?>">
            <span class="nav-icon">⟷</span><span class="nav-label"><?= t('nav_trunks') ?></span>
        </a>
        <a href="?page=queues" class="nav-item<?= activeNav('queues') ?>">
            <span class="nav-icon">⋮⋮</span><span class="nav-label"><?= t('nav_queues') ?></span>
        </a>
        <a href="?page=ring_groups" class="nav-item<?= activeNav('ring_groups') ?>">
            <span class="nav-icon">◎</span><span class="nav-label"><?= t('nav_ring_groups') ?></span>
        </a>

        <div class="nav-section-label">ROUTING</div>
        <a href="?page=inbound_routes" class="nav-item<?= activeNav('inbound_routes') ?>">
            <span class="nav-icon">↘</span><span class="nav-label"><?= t('nav_inbound') ?></span>
        </a>
        <a href="?page=outbound_routes" class="nav-item<?= activeNav('outbound_routes') ?>">
            <span class="nav-icon">↗</span><span class="nav-label"><?= t('nav_outbound') ?></span>
        </a>
        <a href="?page=dialplan" class="nav-item<?= activeNav('dialplan') ?>">
            <span class="nav-icon">⌥</span><span class="nav-label"><?= t('nav_dialplan') ?></span>
        </a>

        <div class="nav-section-label">BILLING</div>
        <a href="?page=cdr" class="nav-item<?= activeNav('cdr') ?>">
            <span class="nav-icon">▤</span><span class="nav-label"><?= t('nav_cdr') ?></span>
        </a>
        <a href="?page=customers" class="nav-item<?= activeNav('customers') ?>">
            <span class="nav-icon">⊙</span><span class="nav-label"><?= t('nav_customers') ?></span>
        </a>
        <a href="?page=invoices" class="nav-item<?= activeNav('invoices') ?>">
            <span class="nav-icon">▣</span><span class="nav-label"><?= t('nav_invoices') ?></span>
        </a>
        <a href="?page=rate_plans" class="nav-item<?= activeNav('rate_plans') ?>">
            <span class="nav-icon">€</span><span class="nav-label"><?= t('nav_rate_plans') ?></span>
        </a>

        <div class="nav-section-label">PROVISIONING</div>
        <a href="?page=provision" class="nav-item<?= activeNav('provision') ?>">
            <span class="nav-icon">📱</span><span class="nav-label">Yealink Phones</span>
        </a>
        <a href="?page=phonebook" class="nav-item<?= activeNav('phonebook') ?>">
            <span class="nav-icon">📒</span><span class="nav-label">Adresboek</span>
        </a>

        <div class="nav-section-label">SYSTEM</div>
        <a href="?page=security" class="nav-item<?= activeNav('security') ?>">
            <span class="nav-icon">🔒</span><span class="nav-label">Login Security</span>
        </a>
        <a href="?page=settings" class="nav-item<?= activeNav('settings') ?>">
            <span class="nav-icon">⚙</span><span class="nav-label"><?= t('settings') ?></span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <span class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?></span>
            <div class="user-details">
                <span class="user-name"><?= sanitize($_SESSION['username'] ?? '') ?></span>
                <span class="user-role"><?= sanitize($_SESSION['role'] ?? '') ?></span>
            </div>
        </div>
        <!-- Language Switcher -->
        <form method="POST" action="?page=settings&action=set_language" class="lang-form">
            <?= csrf() ?>
            <select name="lang" onchange="this.form.submit()" class="lang-select">
                <?php foreach (I18n::availableLanguages() as $code => $name): ?>
                <option value="<?= $code ?>" <?= I18n::currentLang() === $code ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="?page=login&action=logout" class="logout-btn"><?= t('logout') ?> ⏻</a>
    </div>
</aside>

<!-- Main Content -->
<div class="main-wrapper">
    <!-- Top Bar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="mobile-toggle" id="mobileToggle">☰</button>
            <nav class="breadcrumb">
                <a href="?page=dashboard">Home</a>
                <span class="bc-sep">/</span>
                <span><?= $title ?? '' ?></span>
            </nav>
        </div>
        <div class="topbar-right">
            <span class="topbar-clock" id="topbarClock"></span>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php foreach (getFlash() as $flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>" role="alert">
        <?= $flash['message'] ?>
        <button class="alert-close" onclick="this.parentElement.remove()">×</button>
    </div>
    <?php endforeach; ?>

    <!-- Page Content -->
    <main class="page-content">
        <?= $content ?>
    </main>

    <footer class="page-footer">
        <?= t('app_name') ?> v<?= APP_VERSION ?> — Asterisk 22 on Debian / Apache2
    </footer>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
