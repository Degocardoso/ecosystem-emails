<?php

/**
 * Encerra a sessão do usuário.
 */

require_once __DIR__ . '/auth/bootstrap.php';

use App\Auth\Auth;

Auth::logout();
redirect(base_url('login.php'));
