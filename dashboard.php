<?php

/**
 * Painel inicial. As funcionalidades exibidas respeitam as permissões do
 * perfil do usuário autenticado.
 */

require_once __DIR__ . '/auth/guard.php';
require_login();

use App\Auth\Auth;

$u = Auth::user();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel — Ecossistema de E-mails FECAP</title>
    <link rel="stylesheet" href="<?= e(base_url('assets/css/fecap.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/partials/topbar.php'; ?>

    <main class="fecap-container">
        <h1 class="fecap-page-title">Olá, <?= e($u['nome']) ?>!</h1>
        <p class="fecap-page-sub">
            Você está conectado como <strong><?= e($u['role_nome']) ?></strong>.
            Escolha uma das funcionalidades disponíveis para o seu perfil.
        </p>

        <div class="fecap-grid">

            <?php if (Auth::can('gerador.acesso')): ?>
            <a class="fecap-card" href="<?= e(base_url('gerador-de-emails-master/')) ?>">
                <div class="icon">✉</div>
                <h3>Gerador de E-mails</h3>
                <p>Crie e configure e-mails institucionais e envie para o Dynamics 365.</p>
            </a>
            <?php endif; ?>

            <?php if (Auth::can('relatorios.acesso')): ?>
            <a class="fecap-card" href="<?= e(base_url('dynamics-email-report/public/')) ?>">
                <div class="icon">📊</div>
                <h3>Relatórios de E-mails</h3>
                <p>Consulte o engajamento e exporte relatórios (CSV, Excel, PDF, XML).</p>
            </a>
            <?php endif; ?>

            <?php if (Auth::can('usuarios.gerenciar')): ?>
            <a class="fecap-card" href="<?= e(base_url('admin/usuarios.php')) ?>">
                <div class="icon">👥</div>
                <h3>Gerenciar Usuários</h3>
                <p>Cadastre, edite e defina os perfis de acesso dos usuários do sistema.</p>
            </a>
            <?php endif; ?>

        </div>
    </main>

    <?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
