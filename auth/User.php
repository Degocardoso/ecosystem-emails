<?php

namespace App\Auth;

use PDO;

/**
 * Modelo de usuário + operações de persistência.
 *
 * Senhas são sempre armazenadas como hash (bcrypt, cost 12) — nunca em texto
 * puro. Todas as consultas usam prepared statements.
 */
class User
{
    /** Custo do bcrypt. 12 é um bom equilíbrio segurança/desempenho. */
    const BCRYPT_COST = 12;

    /** Gera o hash seguro de uma senha. */
    public static function hashPassword($plain)
    {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);
    }

    /** Verifica a senha contra o hash armazenado. */
    public static function verifyPassword($plain, $hash)
    {
        return password_verify($plain, $hash);
    }

    /** Busca usuário ativo por e-mail (com role e permissões). */
    public static function findByEmail($email)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT u.*, r.slug AS role_slug, r.nome AS role_nome
               FROM users u
               JOIN roles r ON r.id = u.role_id
              WHERE u.email = :email
              LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /** Busca usuário por id. */
    public static function findById($id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT u.*, r.slug AS role_slug, r.nome AS role_nome
               FROM users u
               JOIN roles r ON r.id = u.role_id
              WHERE u.id = :id
              LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /** Lista todos os usuários (para a tela de administração). */
    public static function all()
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            'SELECT u.id, u.nome, u.email, u.ativo, u.ultimo_login, u.created_at,
                    r.slug AS role_slug, r.nome AS role_nome
               FROM users u
               JOIN roles r ON r.id = u.role_id
              ORDER BY u.nome'
        );
        return $stmt->fetchAll();
    }

    /** Permissões (slugs) associadas a um perfil. */
    public static function permissionsForRole($roleId)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT p.slug
               FROM role_permissions rp
               JOIN permissions p ON p.id = rp.permission_id
              WHERE rp.role_id = :rid'
        );
        $stmt->execute([':rid' => $roleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Lista de perfis (id, slug, nome) para selects de formulário. */
    public static function roles()
    {
        $pdo = Database::getConnection();
        return $pdo->query('SELECT id, slug, nome FROM roles ORDER BY id')->fetchAll();
    }

    /** Cria um novo usuário. Retorna o id criado. */
    public static function create($nome, $email, $senhaPlano, $roleId, $ativo = 1)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO users (nome, email, senha_hash, role_id, ativo)
             VALUES (:nome, :email, :hash, :role, :ativo)'
        );
        $stmt->execute([
            ':nome'  => $nome,
            ':email' => $email,
            ':hash'  => self::hashPassword($senhaPlano),
            ':role'  => $roleId,
            ':ativo' => $ativo ? 1 : 0,
        ]);
        return (int) $pdo->lastInsertId();
    }

    /** Atualiza dados básicos do usuário (sem senha). */
    public static function update($id, $nome, $email, $roleId, $ativo = 1)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE users
                SET nome = :nome, email = :email, role_id = :role, ativo = :ativo
              WHERE id = :id'
        );
        return $stmt->execute([
            ':nome'  => $nome,
            ':email' => $email,
            ':role'  => $roleId,
            ':ativo' => $ativo ? 1 : 0,
            ':id'    => $id,
        ]);
    }

    /** Atualiza a senha (recebe texto puro, grava hash). */
    public static function updatePassword($id, $senhaPlano)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET senha_hash = :hash WHERE id = :id');
        return $stmt->execute([
            ':hash' => self::hashPassword($senhaPlano),
            ':id'   => $id,
        ]);
    }

    /** Remove um usuário. */
    public static function delete($id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /** Marca o momento do último login. */
    public static function touchLastLogin($id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET ultimo_login = NOW() WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /** Conta quantos administradores ativos existem (proteção contra remover o último). */
    public static function countActiveAdmins()
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            "SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id
              WHERE r.slug = 'admin' AND u.ativo = 1"
        );
        return (int) $stmt->fetchColumn();
    }

    /** Verifica se um e-mail já está em uso por outro usuário. */
    public static function emailExists($email, $ignoreId = null)
    {
        $pdo = Database::getConnection();
        if ($ignoreId) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id <> :id');
            $stmt->execute([':email' => $email, ':id' => $ignoreId]);
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
            $stmt->execute([':email' => $email]);
        }
        return ((int) $stmt->fetchColumn()) > 0;
    }
}
