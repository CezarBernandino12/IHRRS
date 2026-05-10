<?php
/**
 * Environment Configuration Loader
 * Loads environment variables from .env file
 */

// Load environment variables
function loadEnv($path = __DIR__) {
    $envFile = $path . DIRECTORY_SEPARATOR . '.env';
    
    if (!file_exists($envFile)) {
        throw new Exception("Environment file (.env) not found at: $envFile");
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse key=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                $value = substr($value, 1, -1);
            }
            
            // Set as environment variable
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

/**
 * Get environment variable with optional default value
 * 
 * @param string $key The environment variable name
 * @param string|null $default Default value if not set
 * @return string|null
 */
function env($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// Load .env file on include
try {
    loadEnv(dirname(__DIR__));
} catch (Exception $e) {
    error_log("Warning: " . $e->getMessage());
}

?>
