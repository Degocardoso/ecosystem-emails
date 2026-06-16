<?php

/**
 * Administração » Lista de usuários (requer permissão usuarios.gerenciar).
 */

require_once __DIR__ . '/../auth/guard.php';
require_permission('usuarios.gerenciar');

use App\Auth\Auth;
use App\Auth\Csrf;
use App\Auth\User;

$usuarios = User::all();
$me = Auth::id();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários — Ecossistema de E-mails FECAP</title>
    <link rel="stylesheet" href="<?= e(base_url('assets/css/fecap.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../partials/topbar.php'; ?>

    <main class="fecap-container">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
            <div>
                <h1 class="fecap-page-title">Gerenciar Usuários</h1>
                <p class="fecap-page-sub">Cadastro de usuários e definição de perfis de acesso.</p>
            </div>
            <a class="fecap-btn" href="<?= e(base_url('admin/usuario-form.php')) ?>">+ Novo usuário</a>
        </div>

        <?php if ($msg = flash_get('ok')): ?>
            <div class="fecap-alert success"><?= e($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = flash_get('erro')): ?>
            <div class="fecap-alert error"><?= e($msg) ?></div>
        <?php endif; ?>

        <table class="fecap-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th>Último login</th>
                    <th style="text-align:right">Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $usr): ?>
                <tr>
                    <td><?= e($usr['nome']) ?><?= $usr['id'] == $me ? ' <small>(você)</small>' : '' ?></td>
                    <td><?= e($usr['email']) ?></td>
                    <td><span class="tag <?= e($usr['role_slug']) ?>"><?= e($usr['role_nome']) ?></span></td>
                    <td>
                        <?php if ((int)$usr['ativo'] === 1): ?>
                            <span class="tag on">Ativo</span>
                        <?php else: ?>
                            <span class="tag off">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $usr['ultimo_login'] ? e(date('d/m/Y H:i', strtotime($usr['ultimo_login']))) : '—' ?></td>
                    <td style="text-align:right;white-space:nowrap">
                        <a class="fecap-btn secondary" style="padding:.35rem .8rem"
                           href="<?= e(base_url('admin/usuario-form.php?id=' . (int)$usr['id'])) ?>">Editar</a>
                        <?php if ($usr['id'] != $me): ?>
                        <form action="<?= e(base_url('admin/usuario-excluir.php')) ?>" method="post"
                              style="display:inline" onsubmit="return confirm('Remover o usuário <?= e($usr['nome']) ?>?');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$usr['id'] ?>">
                            <button class="fecap-btn danger" style="padding:.35rem .8rem" type="submit">Remover</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
