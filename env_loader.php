<?php
/**
 * env_loader.php
 *
 * Loads environment variables from the .env file at the project root.
 * Include this file once at the top of db.php so all pages inherit it.
 *
 * Usage: require_once __DIR__ . '/env_loader.php';   (from root files)
 *        require_once __DIR__ . '/../env_loader.php'; (from sub-folders)
 */

function loadEnv(string $envPath = ''): void
{
    if ($envPath === '') {
        $envPath = __DIR__ . '/.env';
    }

    if (!file_exists($envPath)) {
        return; // silently skip – production servers may inject vars another way
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip blank lines and comments
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        // Must contain '='
        if (strpos($line, '=') === false) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);

        // Strip surrounding single or double quotes
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last  = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        // Only set if not already defined (lets server-level vars take priority)
        if (!array_key_exists($name, $_ENV) && getenv($name) === false) {
            putenv("$name=$value");
            $_ENV[$name]    = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Auto-run on include
loadEnv();
