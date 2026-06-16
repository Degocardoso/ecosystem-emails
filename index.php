<?php

/**
 * Ponto de entrada do ecossistema.
 * Encaminha para o painel (se autenticado) ou para o login.
 */

require_once __DIR__ . '/auth/bootstrap.php';

use App\Auth\Auth;

if (Auth::check()) {
    redirect(base_url('dashboard.php'));
}
redirect(base_url('login.php'));
