<?php
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start();
date_default_timezone_set("Asia/Calcutta");
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

// Redirect if already logged in
if (isset($_SESSION['vrx_logged_in']) && $_SESSION['vrx_logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$error = '';

if (isset($_POST['login'])) {
    $uname = trim($_POST['username'] ?? '');
    $pw    = trim($_POST['password'] ?? '');

    // Hardcoded credentials — change before go-live
    $vrx_users = [
        'vrx' => 'FSz&slJQe3Y',
    ];

    if (!empty($uname) && isset($vrx_users[$uname]) && hash_equals($vrx_users[$uname], $pw)) {
        session_regenerate_id(true);
        $_SESSION['vrx_logged_in'] = true;
        $_SESSION['vrx_user']      = $uname;
        header('Location: index.php');
        exit();
    } else {
        $error = 'Invalid username or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VRX – Ads &amp; Leads Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body {
            background: linear-gradient(135deg, #0f2044 0%, #1a3a6e 50%, #0d1f3c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-wrap { width: 100%; max-width: 400px; padding: 20px; }
        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.4);
            background: #fff;
        }
        .brand {
            font-size: 3rem;
            font-weight: 900;
            color: #0f2044;
            letter-spacing: 6px;
            line-height: 1;
        }
        .brand-sub { color: #6b7280; font-size: 0.85rem; letter-spacing: 1px; margin-top: 4px; }
        .form-control:focus { border-color: #1a3a6e; box-shadow: 0 0 0 3px rgba(26,58,110,0.15); }
        .btn-login {
            background: linear-gradient(135deg, #0f2044, #1a3a6e);
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            padding: 12px;
            letter-spacing: 0.5px;
            transition: all 0.2s;
        }
        .btn-login:hover { opacity: 0.9; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(15,32,68,0.4); }
        .divider { border-top: 1px solid #e5e7eb; margin: 20px 0; }
        .alert { border-radius: 10px; font-size: 0.875rem; }
        .input-group-text { background: #f8fafc; border-color: #dee2e6; color: #6b7280; }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="card login-card">
        <div class="card-body p-5">
            <!-- Brand -->
            <div class="text-center mb-4">
                <div class="brand"><img src="vrx-logo.png" style="height: 35px;" /></div>
                <div class="brand-sub text-uppercase">Ads &amp; Leads Dashboard</div>
            </div>

            <div class="divider"></div>

            <?php if ($error): ?>
            <div class="alert alert-danger py-2 mb-3">
                <i class="me-1">⚠️</i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-uppercase text-muted">Username</label>
                    <div class="input-group">
                        <span class="input-group-text">👤</span>
                        <input type="text" name="username" class="form-control form-control-lg"
                               placeholder="Enter username" required autofocus
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold small text-uppercase text-muted">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">🔒</span>
                        <input type="password" name="password" class="form-control form-control-lg"
                               placeholder="Enter password" required>
                    </div>
                </div>
                <button type="submit" name="login" class="btn btn-login btn-primary w-100 text-white">
                    Login →
                </button>
            </form>

            <div class="text-center mt-4">
                <small class="text-muted">Powered by <strong>BYT</strong></small>
            </div>
        </div>
    </div>
</div>
</body>
</html>
