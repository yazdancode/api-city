<?php
namespace App\Utilities;

use DateTime;
use DateTimeZone;
use Exception;

class CacheUtility
{
    protected static string $cacheFile;
    protected static bool $cacheEnabled = false;
    protected static string $cacheDir;

    const EXPIRE_TIME = 3600; // 1 ساعت

    public static function init(bool $enabled = false, string $cacheDir = null): void
    {
        self::$cacheEnabled = $enabled;

        self::$cacheDir = $cacheDir ?? (__DIR__ . '/../../cache/');

        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }

        self::$cacheFile = self::$cacheDir . md5($_SERVER['REQUEST_URI']) . ".json";

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            self::$cacheEnabled = false;
        }
    }

    public static function cacheExists(): bool
    {
        if (!isset(self::$cacheFile)) {
            throw new Exception("CacheUtility::init() must be called before cacheExists().");
        }

        return (
            file_exists(self::$cacheFile) &&
            (time() - filemtime(self::$cacheFile)) < self::EXPIRE_TIME
        );
    }

    public static function start(): void
    {
        if (!self::$cacheEnabled) {
            return;
        }

        if (self::cacheExists()) {
            if (class_exists('\App\Utilities\Response')) {
                \App\Utilities\Response::setHeaders(200);
            }
            readfile(self::$cacheFile);
            exit;
        }

        ob_start();
    }

    /**
     * @throws Exception
     */
    public static function end(): void
    {
        if (!self::$cacheEnabled) {
            return;
        }

        date_default_timezone_set('Asia/Tehran');

        $output = ob_get_clean();

        $decoded = json_decode($output, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $now = new DateTime('now', new DateTimeZone('Asia/Tehran'));

            // فقط تاریخ میلادی را اضافه می‌کنیم
            $decoded['cached_at'] = [
                'gregorian' => $now->format('Y-m-d H:i:s'),
            ];
            $output = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            $now = new DateTime('now', new DateTimeZone('Asia/Tehran'));
            $output .= "\n<!-- Cached at " . $now->format('Y-m-d H:i:s') . " -->";
        }

        $cachedFile = fopen(self::$cacheFile, 'w');
        if ($cachedFile && flock($cachedFile, LOCK_EX)) {
            fwrite($cachedFile, $output);
            flock($cachedFile, LOCK_UN);
        }
        if ($cachedFile) {
            fclose($cachedFile);
        }

        echo $output;
    }

    public static function flush(): void
    {
        if (!isset(self::$cacheDir)) {
            throw new Exception("CacheUtility::init() must be called before flush().");
        }

        $files = glob(self::$cacheDir . "*");
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
