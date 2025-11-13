<?php
// auth.php – Include at TOP of every PHP file
session_start();
require_once 'config.php';

if (!PASSWORD_PROTECT) return;

$valid = false;

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    $valid = true;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $input = trim($_POST['password']);
    
    if (password_verify($input, SITE_PASSWORD_HASH)) {
        $_SESSION['authenticated'] = true;
        if (isset($_POST['redirect'])) {
            header('Location: ' . $_POST['redirect']);
            exit;
        }
    } else {
        $error = '❌ Incorrect password';
    }
}

if (!$valid) {
    showLoginForm($error ?? null, $_SERVER['REQUEST_URI'] ?? 'index.php');
    exit;
}

function showLoginForm($error = null, $redirect = 'index.php') {
    ?>
    <!DOCTYPE html>
    <html lang="en" data-theme="dark">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>🔒 Login Required</title>
        <style>
            :root { --bg: #121212; --card: #1e1e1e; --text: #e0e0e0; --accent: #66BB6A; }
            * { margin:0; padding:0; box-sizing:border-box; }
            body {
                background: linear-gradient(135deg, #121212 0%, #1e1e1e 100%);
                color: var(--text);
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 20px;
            }
            .login-card {
                background: var(--card);
                padding: 48px 32px;
                border-radius: 24px;
                width: 100%;
                max-width: 420px;
                box-shadow: 0 25px 50px rgba(0,0,0,0.4);
                text-align: center;
                backdrop-filter: blur(10px);
            }
            .lock-icon {
                width: 80px; height: 80px;
                margin: 0 auto 24px;
                background: var(--accent);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .lock-icon svg { width: 40px; height: 40px; stroke: white; stroke-width: 2.5; }
            h1 { margin-bottom: 8px; font-size: 2rem; font-weight: 700; }
            p { margin-bottom: 32px; opacity: 0.8; font-size: 1rem; }
            input[type="password"] {
                width: 100%; padding: 16px 20px;
                border: 2px solid #333; border-radius: 16px;
                background: #2a2a2a; color: white;
                font-size: 1rem; margin-bottom: 20px;
                transition: all 0.3s;
            }
            input:focus {
                outline: none; border-color: var(--accent);
                box-shadow: 0 0 0 4px rgba(102,187,106,0.2);
            }
            button {
                background: var(--accent); color: white;
                border: none; padding: 16px 40px;
                border-radius: 16px; font-weight: 600;
                font-size: 1.1rem; cursor: pointer;
                width: 100%; transition: all 0.3s;
            }
            button:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(102,187,106,0.4);
            }
            .error {
                color: #ff6b6b; font-size: 0.95rem;
                margin-top: 12px; min-height: 28px;
                background: rgba(255,107,107,0.1);
                padding: 12px; border-radius: 12px;
            }
        </style>
    </head>
    <body>
        <div class="login-card">
            <div class="lock-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                </svg>
            </div>
            <h1>🔒 Protected</h1>
            <p>Enter password to access gallery</p>
            <form method="post">
                <input type="password" name="password" placeholder="••••••••" required autofocus>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                <button type="submit">Unlock Gallery</button>
            </form>
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
}
?>