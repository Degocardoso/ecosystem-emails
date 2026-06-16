<?php

/**
 * Carregador minimalista de variáveis de ambiente (.env) — sem dependências.
 *
 * Lê o arquivo .env localizado na RAIZ do projeto e popula
 * $_ENV / $_SERVER / getenv(). Não sobrescreve variáveis já definidas
 * (comportamento "imutável"), de modo a conviver com o phpdotenv usado
 * pelo módulo de relatórios.
 *
 * Este carregador é propositalmente independente do Composer para que a
 * camada de autenticação e o gerador de e-mails funcionem mesmo sem o
 * "vendor/" instalado.
 */

if (!function_exists('ecossistema_load_env')) {
    function ecossistema_load_env($path)
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignora linhas vazias e comentários
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name  = trim($name);
            $value = trim($value);

            // Remove um comentário inline simples (apenas quando o valor não é citado)
            if ($value !== '' && $value[0] !== '"' && $value[0] !== "'") {
                $hashPos = strpos($value, ' #');
                if ($hashPos !== false) {
                    $value = rtrim(substr($value, 0, $hashPos));
                }
            }

            // Remove aspas envolventes
            $len = strlen($value);
            if ($len >= 2) {
                $first = $value[0];
                $last  = $value[$len - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            // Não sobrescreve o que já existe (imutável)
            if ($name === '' || array_key_exists($name, $_ENV) || getenv($name) !== false) {
                continue;
            }

            $_ENV[$name]    = $value;
            $_SERVER[$name] = $value;
            putenv($name . '=' . $value);
        }
    }
}

if (!function_exists('env')) {
    /**
     * Lê uma variável de ambiente com valor padrão e normalização de booleanos.
     */
    function env($key, $default = null)
    {
        $value = array_key_exists($key, $_ENV) ? $_ENV[$key] : getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        switch (strtolower((string) $value)) {
            case 'true':
                return true;
            case 'false':
                return false;
            case 'null':
                return null;
            case '':
                return $default;
        }

        return $value;
    }
}

// Carrega automaticamente o .env da raiz do projeto (um nível acima de /auth).
ecossistema_load_env(dirname(__DIR__) . '/.env');
