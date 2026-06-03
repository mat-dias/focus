<?php
if (!function_exists('loadEnv')) {
    function loadEnv($path)
    {
        if (!file_exists($path)) {
            throw new Exception("Nenhum arquivo .env Detectado em: " . $path);
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value);
                putenv(trim($name) . "=" . trim($value));
            }
        }
    }
}

try {
    loadEnv(__DIR__ . '/../../.env');;
} catch (Exception $e) {
}

// Define as constantes
define('USER', getenv('USER') ?: ($_ENV['USER'] ?? ''));
define('PWD', getenv('PWD') ?: ($_ENV['PWD'] ?? ''));
?>
