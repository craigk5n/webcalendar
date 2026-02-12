<?php
/**
 * MCP Loader - Custom autoloader for MCP SDK
 * 
 * This file provides a simple autoloader for the Mcp namespace to avoid
 * loading the full vendor/autoload.php when not needed.
 */

spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'Mcp\\';

    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/../vendor/mcp/sdk/src/';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Also need PSR interfaces if they are used and not already loaded
if (!interface_exists('Psr\Container\ContainerInterface')) {
    spl_autoload_register(function ($class) {
        $map = [
            'Psr\\Container\\' => __DIR__ . '/../vendor/psr/container/src/',
            'Psr\\Log\\' => __DIR__ . '/../vendor/psr/log/src/',
        ];
        foreach ($map as $prefix => $base_dir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) === 0) {
                $relative_class = substr($class, $len);
                $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
                if (file_exists($file)) {
                    require $file;
                    return;
                }
            }
        }
    });
}
