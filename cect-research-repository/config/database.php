<?php
/**
 * PDO connection singleton for the CECT Research Repository.
 */

define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'cect_repository');
define('DB_USER', 'root');
define('DB_PASS', '');           // your MySQL root password

define('APP_NAME', 'CECT Research Repository');
define('UPLOAD_DIR', __DIR__ . '/../uploads/researches/');
define('MAX_UPLOAD_SIZE', 20 * 1024 * 1024); // 20 MB

// Cache-busting version for static assets, based on the newest mtime of the CSS/JS files.
$cssPath = __DIR__ . '/../assets/css/style.css';
$jsPath  = __DIR__ . '/../assets/js/main.js';
define('ASSET_VERSION', max(
    file_exists($cssPath) ? filemtime($cssPath) : 0,
    file_exists($jsPath) ? filemtime($jsPath) : 0
) ?: time());
unset($cssPath, $jsPath);

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                http_response_code(500);
                die('Database connection failed. Please contact the system administrator.');
            }
        }

        return self::$instance;
    }
}
