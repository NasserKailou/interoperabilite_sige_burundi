<?php
/**
 * SIGE Burundi — Administration — Page de connexion
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

// Rediriger si déjà connecté
if (Auth::isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Token de sécurité invalide. Veuillez rafraîchir la page.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Veuillez renseigner votre email et votre mot de passe.';
        } elseif (Auth::login($email, $password)) {
            redirect('dashboard.php');
        } else {
            // Authentification de démo (sans BDD)
            if ($email === 'admin@sige.bi' && $password === 'Admin2024!') {
                Auth::startSession();
                session_regenerate_id(true);
                $_SESSION['admin_user_id']    = 1;
                $_SESSION['admin_user_nom']   = 'Administrateur SIGE';
                $_SESSION['admin_user_email'] = 'admin@sige.bi';
                $_SESSION['admin_user_role']  = 'superadmin';
                redirect('dashboard.php');
            }
            $error = 'Email ou mot de passe incorrect.';
            log_event('warning', 'AUTH', 'Tentative de connexion échouée', ['email' => $email]);
        }
    }
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Administration SIGE Burundi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap">
    <style>
        :root {
            --blue: #1e88e5; --blue-dark: #1565c0; --green: #43a047;
            --red: #e53935; --white: #fff;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Nunito', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1565c0 0%, #1e88e5 50%, #29b6f6 100%);
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }
        .login-header {
            background: linear-gradient(135deg, #1565c0, #1e88e5);
            color: white;
            text-align: center;
            padding: 2.5rem 2rem 2rem;
            position: relative;
        }
        .login-logo {
            width: 72px; height: 72px;
            background: rgba(255,255,255,.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.8rem;
            border: 3px solid rgba(255,255,255,.3);
        }
        .login-header h1 { font-size: 1.15rem; font-weight: 800; margin-bottom: .25rem; }
        .login-header p  { font-size: .82rem; opacity: .8; }
        .login-body { padding: 2rem; }
        .login-body h2 {
            font-size: 1rem; font-weight: 700;
            color: #3c4043; margin-bottom: 1.5rem;
            display: flex; align-items: center; gap: .5rem;
        }
        .login-body h2::after {
            content: ''; flex: 1; height: 1px; background: #e8eaed;
        }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block; font-size: .8rem; font-weight: 700;
            color: #5f6368; text-transform: uppercase;
            letter-spacing: .4px; margin-bottom: .4rem;
        }
        .input-wrap { position: relative; }
        .input-wrap i {
            position: absolute; left: 12px; top: 50%;
            transform: translateY(-50%);
            color: #9aa0a6; font-size: .9rem;
        }
        .form-group input {
            width: 100%;
            padding: .7rem .75rem .7rem 2.5rem;
            border: 1.5px solid #e8eaed;
            border-radius: 8px;
            font-size: .92rem;
            font-family: 'Nunito', sans-serif;
            color: #3c4043;
            transition: all .2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(30,136,229,.12);
        }
        .btn-login {
            width: 100%;
            padding: .85rem;
            background: linear-gradient(135deg, var(--blue-dark), var(--blue));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: .95rem;
            font-weight: 700;
            font-family: 'Nunito', sans-serif;
            cursor: pointer;
            transition: all .2s;
            display: flex; align-items: center; justify-content: center; gap: .5rem;
        }
        .btn-login:hover { opacity: .92; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(30,136,229,.4); }
        .alert-error {
            background: #ffebee; border: 1px solid #ffcdd2; border-radius: 8px;
            padding: .75rem 1rem; margin-bottom: 1.25rem;
            color: #b71c1c; font-size: .87rem;
            display: flex; align-items: center; gap: .5rem;
        }
        .demo-hint {
            background: #e8f5e9; border-radius: 8px; padding: .75rem 1rem;
            margin-top: 1rem; font-size: .8rem; color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .demo-hint strong { display: block; margin-bottom: .25rem; }
        .demo-hint code {
            background: rgba(0,0,0,.06); padding: 1px 6px; border-radius: 4px;
            font-family: monospace; font-size: .82rem;
        }
        .login-footer {
            text-align: center; padding: 1rem 2rem 1.5rem;
            border-top: 1px solid #f1f3f4;
        }
        .login-footer a { font-size: .82rem; color: var(--blue); font-weight: 600; }
        .login-footer p { font-size: .75rem; color: #9aa0a6; margin-top: .5rem; }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <div class="login-logo"><i class="fas fa-graduation-cap"></i></div>
        <h1>Administration SIGE</h1>
        <p>République du Burundi — Ministère de l'Éducation</p>
    </div>

    <div class="login-body">
        <h2><i class="fas fa-lock" style="color:var(--blue)"></i> Connexion</h2>

        <?php if ($error): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrf) ?>">

            <div class="form-group">
                <label for="email">Adresse email</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email"
                           value="<?= e($_POST['email'] ?? '') ?>"
                           placeholder="admin@sige.bi"
                           autocomplete="email" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password"
                           placeholder="••••••••"
                           autocomplete="current-password" required>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Se connecter
            </button>
        </form>

        <div class="demo-hint">
            <strong><i class="fas fa-info-circle"></i> Accès de démonstration</strong>
            Email : <code>admin@sige.bi</code><br>
            Mot de passe : <code>Admin2024!</code>
        </div>
    </div>

    <div class="login-footer">
        <a href="<?= PUBLIC_BASE_URL ?: '../public/' ?>"><i class="fas fa-arrow-left"></i> Retour au portail public</a>
        <p>&copy; <?= date('Y') ?> SIGE Burundi — Système sécurisé</p>
    </div>
</div>
</body>
</html>
