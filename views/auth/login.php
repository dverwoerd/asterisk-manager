<!DOCTYPE html>
<html lang="<?= I18n::currentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('login') ?> — <?= t('app_name') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            height: 100%;
            background: #0a0e1a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'IBM Plex Sans', sans-serif;
        }
        .login-wrap {
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(0,212,170,0.06) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(99,102,241,0.06) 0%, transparent 60%),
                #0a0e1a;
        }
        .login-card {
            background: #111827;
            border: 1px solid #1e2a3a;
            border-radius: 16px;
            padding: 48px 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }
        .login-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
        }
        .login-logo-icon {
            font-size: 32px;
            color: #00d4aa;
            line-height: 1;
        }
        .login-logo-name {
            font-size: 18px;
            font-weight: 600;
            color: #f1f5f9;
            letter-spacing: 2px;
        }
        .login-logo-sub {
            font-size: 10px;
            color: #64748b;
            letter-spacing: 3px;
            font-family: 'IBM Plex Mono', monospace;
        }
        .login-title {
            font-size: 22px;
            font-weight: 500;
            color: #f1f5f9;
            margin-bottom: 28px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .form-control {
            width: 100%;
            background: #0d1117;
            border: 1px solid #1e2a3a;
            border-radius: 8px;
            padding: 12px 14px;
            color: #f1f5f9;
            font-size: 14px;
            font-family: 'IBM Plex Sans', sans-serif;
            transition: border-color 0.2s;
            outline: none;
        }
        .form-control:focus { border-color: #00d4aa; }
        .btn-login {
            width: 100%;
            background: #00d4aa;
            color: #0a0e1a;
            border: none;
            border-radius: 8px;
            padding: 13px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 0.5px;
            margin-top: 8px;
            transition: background 0.2s;
        }
        .btn-login:hover { background: #00b894; }
        .btn-login:disabled {
            background: #334155;
            color: #64748b;
            cursor: not-allowed;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5;
        }
        .alert-blocked {
            background: rgba(239,68,68,0.15);
            border: 1px solid rgba(239,68,68,0.5);
            color: #f87171;
            text-align: center;
            padding: 20px;
        }
        .blocked-icon { font-size: 36px; margin-bottom: 8px; }
        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 11px;
            color: #334155;
            font-family: 'IBM Plex Mono', monospace;
        }
        .ip-badge {
            display: inline-block;
            background: #1e2a3a;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-logo">
            <div class="login-logo-icon">⬡</div>
            <div>
                <div class="login-logo-name">ASTERISK</div>
                <div class="login-logo-sub">MANAGER</div>
            </div>
        </div>

        <?php foreach (getFlash() as $flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
        <?php endforeach; ?>

        <?php if ($blocked): ?>
        <div class="alert alert-blocked">
            <div class="blocked-icon">🔒</div>
            <strong>Toegang tijdelijk geblokkeerd</strong><br>
            <small>Te veel mislukte pogingen vanaf <span class="ip-badge"><?= sanitize($ip) ?></span></small><br>
            <small style="color:#94a3b8;margin-top:8px;display:block">Probeer het over 15 minuten opnieuw.<br>Neem contact op met de beheerder als dit een vergissing is.</small>
        </div>
        <?php else: ?>
        <h1 class="login-title">Inloggen</h1>
        <form method="POST" action="?page=login&action=post_login">
            <?= csrf() ?>
            <div class="form-group">
                <label for="username">Gebruikersnaam</label>
                <input type="text" id="username" name="username" class="form-control"
                       autofocus autocomplete="username" required>
            </div>
            <div class="form-group">
                <label for="password">Wachtwoord</label>
                <input type="password" id="password" name="password" class="form-control"
                       autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn-login">Inloggen →</button>
        </form>
        <?php endif; ?>

        <p class="login-footer">
            Asterisk Manager v<?= APP_VERSION ?> &nbsp;·&nbsp;
            <span class="ip-badge"><?= sanitize($ip) ?></span>
        </p>
    </div>
</div>
</body>
</html>
