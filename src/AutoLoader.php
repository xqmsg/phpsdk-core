<?php

/**
 * Class AutoLoader
 */
class AutoLoader
{
    /**
     *
     * @noinspection PhpIncludeInspection
     */
    public static function register(): void
    {
        spl_autoload_register(static function ($class) {
            if (!strpos($class,'xqmsg\\')  )  {
                require_once $class.'.php';
                return true;
            }
            $sep = DIRECTORY_SEPARATOR;
            $file = __DIR__  . $sep . str_replace('\\', $sep, $class).'.php';
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
            return false;
        });
    }
}

Autoloader::register();