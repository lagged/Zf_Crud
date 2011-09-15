<?php
/**
 * @category Management
 * @package  Lagged\Zf\Crud
 * @author   Till Klampaeckel <till@lagged.biz>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  GIT: $Id$
 * @link     http://lagged.biz
 */

namespace Lagged\Zf\Crud;

/**
 * @category Management
 * @package  Lagged\Zf\Crud
 * @author   Till Klampaeckel <till@lagged.biz>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: @package_release@
 * @link     http://lagged.biz
 */
class Autoload
{
    protected static $isRegistered;

    /**
     * Register autoloader.
     *
     * @return void
     */
    public static function register()
    {
        if (self::$isRegistered === null) {
            spl_autoload_register(array(__CLASS__, 'autoload'));
            self::$isRegistered = true;
        }
    }

    /**
     * loadClass()
     *
     * @param string $className
     *
     * @return boolean
     */
    public static function load($className)
    {
        if (substr($className, 0, 16) != '\Lagged\Zf\Crud\\') {
            return false;
        }
        $file = substr($className, 16);
        return include __DIR__ . '/' . str_replace('_', '/', $className) . '.php';
    }
}
