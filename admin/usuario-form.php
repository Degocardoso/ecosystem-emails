<?php

/**
 * Administração » Criar / editar usuário (requer permissão usuarios.gerenciar).
 */

require_once __DIR__ . '/../auth/guard.php';
require_permission('usuarios.gerenciar');

use App\Auth\Auth;
use App\Auth\Csrf;
use App\Auth\User;

/** Política mínima de senha: 8+ caracteres, com letra e número. */
function senha_valida($s)
{
    return is_string($s) && strlen($s) >= 8 && preg_match('/[A-Za-z]/', $s) && preg_match('/\d/', $s);
}

$roles    = User::roles();
$roleIds  = array_map(function ($r) { return (int) $r['id']; }, $roles);
$errors   = [];
$editId   = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Valores do formulário (default)
$form = ['id' => null, 'nome' => '', 'email' => '', 'role_id' => 2, 'ativo' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValidToken();

    $form['id']      = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;
    $form['nome']    = trim($_POST['nome'] ?? '');
    $form['email']   = trim(strtolower($_POST['email'] ?? ''));
    $form['role_id'] = (int) ($_POST['role_id'] ?? 0);
    $form['ativo']   = isset($_POST['ativo']) ? 1 : 0;
    $senha           = $_POST['senha'] ?? '';
    $senha2          = $_POST['senha2'] ?? '';
    $isEdit          = $form['id'] !== null;

    // Validações
    if ($form['nome'] === '') {
        $errors[] = 'O nome é obrigatório.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Informe um e-mail válido.';
    } elseif (User::emailExists($form['email'], $form['id'])) {
        $errors[] = 'Este e-mail já está em uso por outro usuário.';
    }
    if (!in_array($form['role_id'], $roleIds, true)) {
        $errors[] = 'Selecione um perfil válido.';
    }
    if (!$isEdit || $senha !== '') {
        if (!senha_valida($senha)) {
            $errors[] = 'A senha deve ter no mínimo 8 caracteres, incluindo letras e números.';
        } elseif ($senha !== $senha2) {
            $errors[] = 'A confirmação de senha não confere.';
        }
    }

    // Proteção: não permitir que o último administrador ativo seja rebaixado/desativado.
    if ($isEdit) {
        $atual = User::findById($form['id']);
        if ($atual && $atual['role_slug'] === 'admin') {
            $deixaDeSerAdmin = ($form['role_id'] !== (int) $atual['role_id']) || ($form['ativo'] === 0);
            if ($deixaDeSerAdmin && User::countActiveAdmins() <= 1) {
                $errors[] = 'Este é o último administrador ativo. Promova outro usuário antes de alterar este.';
            }
        }
    }

    if (empty($errors)) {
        if ($isEdit) {
            User::update($form['id'], $form['nome'], $form['email'], $form['role_id'], $form['ativo']);
            if ($senha !== '') {
                User::updatePassword($form['id'], $senha);
            }
            flash_set('ok', 'Usuário atualizado com sucesso.');
        } else {
            User::create($form['nome'], $form['email'], $senha, $form['role_id'], $form['ativo']);
            flash_set('ok', 'Usuário criado com sucesso.');
        }
        redirect(base_url('admin/usuarios.php'));
    }
} elseif ($editId) {
    // GET com id → carrega usuário para edição
    $usr = User::findById($editId);
    if (!$usr) {
        flash_set('erro', 'Usuário não encontrado.');
        redirect(base_url('admin/usuarios.php'));
    }
    $form = [
        'id'      => (int) $usr['id'],
        'nome'    => $usr['nome'],
        'email'   => $usr['email'],
        'role_id' => (int) $usr['role_id'],
        'ativo'   => (int) $usr['ativo'],
    ];
}

$isEdit = $form['id'] !== null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Editar' : 'Novo' ?> usuário — FECAP</title>
    <link rel="stylesheet" href="<?= e(base_url('assets/css/fecap.css')) ?>">
</head>
<body>
    <?php require __DIR__ . '/../partials/topbar.php'; ?>

    <main class="fecap-container" style="max-width:640px">
        <h1 class="fecap-page-title"><?= $isEdit ? 'Editar usuário' : 'Novo usuário' ?></h1>
        <p class="fecap-page-sub">
            <a href="<?= e(base_url('admin/usuarios.php')) ?>">&larr; Voltar à lista</a>
        </p>

        <?php if ($errors): ?>
            <div class="fecap-alert error">
                <ul style="margin:0;padding-left:1.2rem">
                    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(base_url('admin/usuario-form.php')) ?>" class="fecap-card" autocomplete="off">
            <?= Csrf::field() ?>
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$form['id'] ?>"><?php endif; ?>

            <div class="fecap-field">
                <label for="nome">Nome completo</label>
                <input class="fecap-input" id="nome" name="nome" value="<?= e($form['nome']) ?>" required>
            </div>

            <div class="fecap-field">
                <label for="email">E-mail</label>
                <input class="fecap-input" type="email" id="email" name="email" value="<?= e($form['email']) ?>" required>
            </div>

            <div class="fecap-field">
                <label for="role_id">Perfil de acesso</label>
                <select class="fecap-select" id="role_id" name="role_id">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= (int)$r['id'] ?>" <?= $form['role_id'] === (int)$r['id'] ? 'selected' : '' ?>>
                            <?= e($r['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="fecap-field">
                <label for="senha">Senha <?= $isEdit ? '(deixe em branco para manter a atual)' : '' ?></label>
                <input class="fecap-input" type="password" id="senha" name="senha" <?= $isEdit ? '' : 'required' ?>>
            </div>

            <div class="fecap-field">
                <label for="senha2">Confirmar senha</label>
                <input class="fecap-input" type="password" id="senha2" name="senha2" <?= $isEdit ? '' : 'required' ?>>
            </div>

            <div class="fecap-field">
                <label style="font-weight:normal">
                    <input type="checkbox" name="ativo" value="1" <?= $form['ativo'] ? 'checked' : '' ?>>
                    Usuário ativo (pode fazer login)
                </label>
            </div>

            <button class="fecap-btn" type="submit"><?= $isEdit ? 'Salvar alterações' : 'Criar usuário' ?></button>
        </form>
    </main>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
