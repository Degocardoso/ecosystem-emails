<?php

/**
 * Utilitário de linha de comando para gerar um hash bcrypt de senha.
 *
 * Uso:
 *     php scripts/gerar-hash.php "MinhaSenhaForte123"
 *
 * Útil para criar/repor o hash do administrador diretamente no phpMyAdmin
 * sem nunca digitar a senha em texto puro no banco.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script só pode ser executado via linha de comando.');
}

$senha = $argv[1] ?? null;

if ($senha === null || $senha === '') {
    fwrite(STDERR, "Uso: php scripts/gerar-hash.php \"SuaSenhaForte\"\n");
    exit(1);
}

echo password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]), PHP_EOL;
