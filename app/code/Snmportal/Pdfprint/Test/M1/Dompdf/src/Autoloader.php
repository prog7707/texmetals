<?php
namespace Snmportal\External\Dompdf;

/**
 * Autoloads Dompdf classes
 *
 * @package Dompdf
 */
require_once __DIR__ . "/../lib/Cpdf.php";
class Autoloader
{
    //AUIT
    const PREFIX = 'Snmportal\External\Dompdf';
    const PREFIXSVG = 'Snmportal\External\Svg';
    /**
     * Register the autoloader
     */
    public static function register()
    {
        //AUIT
        //spl_autoload_register(array(new self, 'autoload'));
        spl_autoload_register(array(new self, 'autoload'),false,true);
    }

    /**
     * Autoloader
     *
     * @param string
     */
    public static function autoload($class)
    {
        if ($class === 'Cpdf') {
            return;
        }
        $prefixLength = strlen(self::PREFIXSVG);

        if (0 === strncmp(self::PREFIXSVG, $class, $prefixLength)) {
            $file = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, $prefixLength));
            $file = __DIR__ . '/../../php-svg-lib/src/Svg'.(empty($file) ? '' : DIRECTORY_SEPARATOR) . $file . '.php';
            $file = realpath($file);
            if (file_exists($file)) {
                require_once $file;
            }
        }

        $prefixLength = strlen(self::PREFIX);
        if (0 === strncmp(self::PREFIX, $class, $prefixLength)) {
            $file = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, $prefixLength));
            $file = realpath(__DIR__ . (empty($file) ? '' : DIRECTORY_SEPARATOR) . $file . '.php');
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }
}