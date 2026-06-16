<?php

/**
 * Funções utilitárias compartilhadas. Todas protegidas por function_exists
 * para conviver com helpers já definidos em outros módulos.
 */

if (!function_exists('e')) {
    /** Escapa saída para HTML (proteção contra XSS). */
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    /** Redireciona e encerra a execução. */
    function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('base_url')) {
    /** Monta uma URL absoluta a partir de APP_URL. */
    function base_url($path = '')
    {
        $base = rtrim((string) env('APP_URL', ''), '/');
        $path = ltrim((string) $path, '/');
        return $path === '' ? $base . '/' : $base . '/' . $path;
    }
}

if (!function_exists('flash_set')) {
    /** Guarda uma mensagem temporária na sessão. */
    function flash_set($key, $message)
    {
        $_SESSION['_flash'][$key] = $message;
    }
}

if (!function_exists('flash_get')) {
    /** Recupera e remove uma mensagem temporária da sessão. */
    function flash_get($key)
    {
        if (!isset($_SESSION['_flash'][$key])) {
            return null;
        }
        $msg = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $msg;
    }
}
