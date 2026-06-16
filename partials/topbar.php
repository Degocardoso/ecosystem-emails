<?php

use App\Auth\Auth;

$u = Auth::user();
$roleClass = in_array($u['role'] ?? '', ['admin', 'criador', 'leitor'], true) ? $u['role'] : 'leitor';
?>
<header class="fecap-topbar">
    <a class="brand" href="<?= e(base_url('dashboard.php')) ?>" style="text-decoration:none;color:#fff">
        <img src="<?= e(base_url('assets/img/fecap-branco.svg')) ?>" alt="FECAP">
        <span>Ecossistema de E-mails</span>
    </a>
    <div class="userbox">
        <span><?= e($u['nome'] ?? '') ?></span>
        <span class="badge-role"><?= e($u['role_nome'] ?? '') ?></span>
        <a class="logout" href="<?= e(base_url('logout.php')) ?>">Sair</a>
    </div>
</header>
