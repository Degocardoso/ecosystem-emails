<?php

namespace App\Auth;

/**
 * Autenticação, sessão e controle de acesso (RBAC).
 *
 * - Login com verificação bcrypt e proteção contra força bruta.
 * - Sessão endurecida (regeneração de id, dados mínimos).
 * - Verificação por perfil (role) e por permissão (permission).
 */
class Auth
{
    /** Tenta autenticar. Retorna ['success'=>bool, 'message'=>string]. */
    public static function attempt($email, $password)
    {
        $email = trim(strtolower($email));
        $ip    = self::clientIp();

        // 1) Proteção contra força bruta
        if (self::isLockedOut($email, $ip)) {
            $minutes = (int) env('LOGIN_LOCKOUT_MINUTES', 15);
            return [
                'success' => false,
                'message' => "Muitas tentativas de login. Tente novamente em {$minutes} minutos.",
            ];
        }

        // 2) Busca o usuário
        $user = User::findByEmail($email);

        // password_verify sempre é executado (mesmo sem usuário) para evitar
        // ataques de enumeração por tempo de resposta.
        $hash = $user['senha_hash'] ?? '$2y$12$invalidinvalidinvalidinvalidinvalidinvalidinvalidinv';
        $ok   = User::verifyPassword($password, $hash);

        if (!$user || !$ok) {
            self::recordAttempt($email, false);
            return ['success' => false, 'message' => 'E-mail ou senha incorretos.'];
        }

        if ((int) $user['ativo'] !== 1) {
            self::recordAttempt($email, false);
            return ['success' => false, 'message' => 'Usuario inativo. Contate o administrador.'];
        }

        // 3) Rehash automático se o custo do bcrypt mudou
        if (password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => User::BCRYPT_COST])) {
            User::updatePassword($user['id'], $password);
        }

        // 4) Sucesso
        self::recordAttempt($email, true);
        User::touchLastLogin($user['id']);
        self::login($user);

        return ['success' => true, 'message' => 'Login realizado com sucesso.'];
    }

    /** Materializa a sessão autenticada a partir do registro do usuário. */
    public static function login(array $user)
    {
        // Evita session fixation
        session_regenerate_id(true);

        $permissions = User::permissionsForRole($user['role_id']);

        $_SESSION['user'] = [
            'id'          => (int) $user['id'],
            'nome'        => $user['nome'],
            'email'       => $user['email'],
            'role'        => $user['role_slug'],
            'role_nome'   => $user['role_nome'],
            'permissions' => $permissions,
        ];
        $_SESSION['_last_activity'] = time();
        $_SESSION['_login_time']    = time();
    }

    public static function logout()
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    public static function check()
    {
        return isset($_SESSION['user']['id']);
    }

    public static function user()
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id()
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function role()
    {
        return $_SESSION['user']['role'] ?? null;
    }

    /** Verifica se o usuário possui um dos perfis informados. */
    public static function hasRole($roles)
    {
        $roles = (array) $roles;
        return in_array(self::role(), $roles, true);
    }

    /** Verifica uma permissão específica (admin tem acesso total). */
    public static function can($permission)
    {
        if (!self::check()) {
            return false;
        }
        if (self::role() === 'admin') {
            return true;
        }
        $perms = $_SESSION['user']['permissions'] ?? [];
        return in_array($permission, $perms, true);
    }

    /** Expira a sessão por inatividade (chamado no bootstrap). */
    public static function enforceIdleTimeout()
    {
        if (!self::check()) {
            return;
        }
        $lifetime = ((int) env('SESSION_LIFETIME', 120)) * 60;
        $last = $_SESSION['_last_activity'] ?? time();

        if ((time() - $last) > $lifetime) {
            self::logout();
            return;
        }
        $_SESSION['_last_activity'] = time();
    }

    // ------------------------------------------------------------------
    // Proteção contra força bruta
    // ------------------------------------------------------------------

    private static function isLockedOut($email, $ip)
    {
        $max     = (int) env('LOGIN_MAX_ATTEMPTS', 5);
        $minutes = (int) env('LOGIN_LOCKOUT_MINUTES', 15);

        // $minutes é forçado a inteiro acima, podendo ser interpolado com seguranca
        // (o INTERVAL nem sempre aceita placeholder em prepared statements nativos).
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
              WHERE sucesso = 0
                AND (email = :email OR ip = :ip)
                AND created_at > (NOW() - INTERVAL ' . $minutes . ' MINUTE)'
        );
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':ip', $ip);
        $stmt->execute();

        return ((int) $stmt->fetchColumn()) >= $max;
    }

    private static function recordAttempt($email, $success)
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                'INSERT INTO login_attempts (email, ip, sucesso, user_agent)
                 VALUES (:email, :ip, :sucesso, :ua)'
            );
            $stmt->execute([
                ':email'   => $email,
                ':ip'      => self::clientIp(),
                ':sucesso' => $success ? 1 : 0,
                ':ua'      => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (\Throwable $e) {
            error_log('[Auth] Falha ao registrar tentativa de login: ' . $e->getMessage());
        }
    }

    private static function clientIp()
    {
        // Em produção atrás de proxy, configure o servidor para enviar o IP real.
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
