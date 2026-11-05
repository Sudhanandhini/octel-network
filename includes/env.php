<?php
/**
 * Minimal .env loader. Reads KEY=VALUE lines from the project root .env
 * (re-read on every request, so editing .env takes effect immediately).
 */
function env(string $key, string $default = ''): string {
    static $vars = null;
    if ($vars === null) {
        $vars = [];
        $file = __DIR__ . '/../.env';
        if (is_readable($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
                [$k, $v] = array_map('trim', explode('=', $line, 2));
                // Strip matching surrounding quotes.
                if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) {
                    $v = substr($v, 1, -1);
                }
                $vars[$k] = $v;
            }
        }
    }
    return $vars[$key] ?? $default;
}
