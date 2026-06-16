<?php

/**
 * Guarda de acesso. Inclua no TOPO de qualquer página protegida:
 *
 *     require_once __DIR__ . '/../auth/guard.php';
 *     require_login();                       // exige apenas estar autenticado
 *     require_permission('gerador.acesso');  // exige uma permissão específica
 *     require_role(['admin']);               // exige um perfil específico
 */

require_once __DIR__ . '/bootstrap.php';

use App\Auth\Auth;

if (!function_exists('require_login')) {
    function require_login()
    {
        if (!Auth::check()) {
            $next = urlencode($_SERVER['REQUEST_URI'] ?? '');
            redirect(base_url('login.php') . ($next ? '?next=' . $next : ''));
        }
    }
}

if (!function_exists('deny_access')) {
    function deny_access($mensagem = 'Voce nao tem permissao para acessar esta area.')
    {
        http_response_code(403);
        $painel = base_url('dashboard.php');
        $msg = e($mensagem);
        echo <<<HTML
<!DOCTYPE html>
<html lang="pt-br"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acesso negado</title>
<style>
  body{font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#f4f7f6;
       display:flex;align-items:center;justify-content:center;height:100vh;margin:0;color:#023c2c}
  .box{background:#fff;padding:2.5rem 3rem;border-radius:.75rem;box-shadow:0 8px 24px rgba(0,0,0,.08);
       text-align:center;max-width:460px}
  h1{margin:.2rem 0;font-size:3rem}
  p{color:#555}
  a{display:inline-block;margin-top:1rem;background:#023c2c;color:#fff;text-decoration:none;
    padding:.7rem 1.4rem;border-radius:.5rem;font-weight:600}
</style></head>
<body><div class="box">
  <h1>403</h1>
  <p>{$msg}</p>
  <a href="{$painel}">Voltar ao painel</a>
</div></body></html>
HTML;
        exit;
    }
}

if (!function_exists('require_permission')) {
    function require_permission($permission)
    {
        require_login();
        if (!Auth::can($permission)) {
            deny_access();
        }
    }
}

if (!function_exists('require_role')) {
    function require_role($roles)
    {
        require_login();
        if (!Auth::hasRole($roles)) {
            deny_access();
        }
    }
}
