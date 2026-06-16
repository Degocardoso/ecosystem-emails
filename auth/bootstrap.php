<?php

/**
 * Bootstrap da camada compartilhada (autenticação + sessão).
 *
 * Inclua este arquivo no topo de qualquer página que precise de sessão ou
 * autenticação. É independente do Composer.
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Auth.php';

use App\Auth\Auth;

// Em produção os erros nunca devem ser exibidos ao usuário; apenas registrados.
$appDebug = (bool) env('APP_DEBUG', false);
ini_set('display_errors', $appDebug ? '1' : '0');
ini_set('display_startup_errors', $appDebug ? '1' : '0');
error_reporting($appDebug ? E_ALL : (E_ALL & ~E_DEPRECATED & ~E_NOTICE));

// ----- Sessão endurecida -----
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    session_name((string) env('SESSION_NAME', 'ECOSSISTEMA_SESSID'));

    // PHP 7.3+ aceita o array com 'samesite'
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_start();
}

// Expira sessões ociosas
Auth::enforceIdleTimeout();
