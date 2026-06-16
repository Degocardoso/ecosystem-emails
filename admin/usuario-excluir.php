<?php

/**
 * Administração » Remover usuário (POST + CSRF; requer usuarios.gerenciar).
 */

require_once __DIR__ . '/../auth/guard.php';
require_permission('usuarios.gerenciar');

use App\Auth\Auth;
use App\Auth\Csrf;
use App\Auth\User;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(base_url('admin/usuarios.php'));
}

Csrf::requireValidToken();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    flash_set('erro', 'Usuário inválido.');
    redirect(base_url('admin/usuarios.php'));
}

// Não permitir remover a si mesmo
if ($id === Auth::id()) {
    flash_set('erro', 'Você não pode remover o seu próprio usuário.');
    redirect(base_url('admin/usuarios.php'));
}

$alvo = User::findById($id);
if (!$alvo) {
    flash_set('erro', 'Usuário não encontrado.');
    redirect(base_url('admin/usuarios.php'));
}

// Não permitir remover o último administrador ativo
if ($alvo['role_slug'] === 'admin' && (int) $alvo['ativo'] === 1 && User::countActiveAdmins() <= 1) {
    flash_set('erro', 'Não é possível remover o último administrador ativo.');
    redirect(base_url('admin/usuarios.php'));
}

User::delete($id);
flash_set('ok', 'Usuário removido com sucesso.');
redirect(base_url('admin/usuarios.php'));
