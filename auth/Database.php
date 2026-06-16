<?php

namespace App\Auth;

use PDO;
use PDOException;

/**
 * Conexão única (singleton) com o MySQL via PDO.
 *
 * As credenciais vêm exclusivamente do arquivo .env (nunca do código-fonte).
 * Usa prepared statements reais (EMULATE_PREPARES = false) para mitigar
 * injeção de SQL.
 */
class Database
{
    /** @var PDO|null */
    private static $pdo = null;

    public static function getConnection()
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host    = env('DB_HOST', '127.0.0.1');
        $port    = env('DB_PORT', '3306');
        $name    = env('DB_NAME', 'ecosystem_emails');
        $user    = env('DB_USER', 'root');
        $pass    = env('DB_PASS', '');
        $charset = env('DB_CHARSET', 'utf8mb4');

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $name, $charset);

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Nunca expor credenciais ou detalhes internos ao usuário final.
            error_log('[Database] Falha de conexao: ' . $e->getMessage());
            http_response_code(500);
            exit('Erro de conexao com o banco de dados. Contate o administrador do sistema.');
        }

        return self::$pdo;
    }
}
