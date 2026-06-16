<?php

/**
 * Tela de Login — autenticação via MySQL (bcrypt) + proteção CSRF.
 */

require_once __DIR__ . '/auth/bootstrap.php';

use App\Auth\Auth;
use App\Auth\Csrf;

// Já autenticado? Vai direto para o painel.
if (Auth::check()) {
    redirect(base_url('dashboard.php'));
}

/**
 * Garante que o destino pós-login seja um caminho LOCAL (evita open redirect).
 */
function safe_next($next)
{
    if (!is_string($next) || $next === '') {
        return null;
    }
    // Apenas caminhos relativos iniciados por "/" e não "//" (que seria outro host)
    if ($next[0] !== '/' || (isset($next[1]) && $next[1] === '/')) {
        return null;
    }
    return $next;
}

$erro = null;
$next = safe_next($_GET['next'] ?? ($_POST['next'] ?? null));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValidToken();

    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Informe e-mail e senha.';
    } else {
        $res = Auth::attempt($email, $senha);
        if ($res['success']) {
            // $next já é um caminho LOCAL absoluto validado (safe_next).
            redirect($next ?: base_url('dashboard.php'));
        }
        $erro = $res['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — Ecossistema de E-mails FECAP</title>
    <link rel="stylesheet" href="<?= e(base_url('assets/css/fecap.css')) ?>">
</head>
<body>
    <div class="fecap-login-wrap">
        <div class="fecap-login-card">
            <div class="logo">
                <img src="<?= e(base_url('assets/img/fecap-verde-escuro.svg')) ?>" alt="FECAP">
            </div>
            <h1>Ecossistema de E-mails</h1>

            <?php if ($erro): ?>
                <div class="fecap-alert error"><?= e($erro) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(base_url('login.php')) ?>" autocomplete="off" novalidate>
                <?= Csrf::field() ?>
                <?php if ($next): ?>
                    <input type="hidden" name="next" value="<?= e($next) ?>">
                <?php endif; ?>

                <div class="fecap-field">
                    <label for="email">E-mail</label>
                    <input class="fecap-input" type="email" id="email" name="email"
                           value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
                </div>

                <div class="fecap-field">
                    <label for="senha">Senha</label>
                    <input class="fecap-input" type="password" id="senha" name="senha" required>
                </div>

                <button class="fecap-btn block" type="submit">Entrar</button>
            </form>
        </div>
    </div>
</body>
</html>
