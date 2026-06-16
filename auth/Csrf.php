<?php

namespace App\Auth;

/**
 * Proteção contra CSRF (Cross-Site Request Forgery).
 *
 * Gera um token por sessão e valida-o em todas as requisições que alteram
 * estado (POST). Usa hash_equals para comparação resistente a timing attacks.
 */
class Csrf
{
    const SESSION_KEY = '_csrf_token';

    /** Retorna o token atual, criando-o se necessário. */
    public static function token()
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /** Campo <input hidden> pronto para inserir em formulários. */
    public static function field()
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /** Valida o token recebido (POST/cabeçalho). */
    public static function validate($token)
    {
        return !empty($_SESSION[self::SESSION_KEY])
            && is_string($token)
            && hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    /**
     * Garante CSRF válido em requisições POST. Encerra a execução (403) caso
     * inválido. Aceita o token via campo "_csrf" ou cabeçalho X-CSRF-Token.
     */
    public static function requireValidToken()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $token = $_POST['_csrf']
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

        if (!self::validate($token)) {
            http_response_code(403);
            exit('Token de seguranca (CSRF) invalido ou expirado. Recarregue a pagina e tente novamente.');
        }
    }
}
