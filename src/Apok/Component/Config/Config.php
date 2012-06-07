<?php
namespace Apok\Component\Config;

/**
 * Configuration handling
 *
 * Usage:
 * Config::extend('Development', array('Staging')); // Extend Development from Staging values
 * Config::set('Development.Key', 'value');
 * Config::load('Development');
 * Config::read('Key'); //outcomes 'value'
 *
 * KNOWN ISSUES:
 * - Config::set('key', boolean); fails when trying to overwrite the boolean
 *
 * @todo Fix overwriting boolean values
 *
 * @package Config
 */
class Config
{
    /**
     * Config array with all the configurations for each environment
     *
     * @var array $config Full configuration array
     */
    private static $config = array();

    /**
     * Environment which to use
     *
     * @var string $environment Environment name to use
     */
    private static $environment = null;

    /**
     * Determine basename for setting variables (e.g. environment name)
     *
     * @var string $keyBase Key to be added in front of value when set
     */
    private static $keyBase = null;

    /**
     * Get environment which we're in
     */
    public static function getEnvironment()
    {
        return self::$environment;
    }

    /**
     * Get config array
     */
    public static function getConfig()
    {
        return self::$config;
    }

    /**
     * Set key base for configurations
     */
    public static function setBase($key)
    {
        if ($key != '') {
            self::$keyBase = $key.'.';
        } else {
            self::$keyBase = '';
        }
    }

    /**
     * Set recursive key
     *
     * @param   array $keys Recursive keys to array
     * @param   mixed $value Value to insert in the final array
     * @return  array Return multi-dimensional/recursive array
     */
    private static function setRecursiveKey($keys, $value)
    {
        $array    = array();
        $keyCount = count($keys);

        if ($keyCount>0) {
            $key = array_shift($keys);
            $array[$key] = self::setRecursiveKey($keys, $value);
        } else {
            $array = $value;
        }

        return $array;
    }


    /**
     * Merge recursive arrays with overwrite
     *
     * @param   array $array1 Array to merge with another
     * @param   array $array2 Array to merge with
     * @return  array Merged array with array2 overriding values
     */
    private static function mergeArrays($array1, $array2)
    {
        foreach($array2 as $key => $value) {
            if (array_key_exists($key, $array1) && is_array($value)) {
                $array1[$key] = self::mergeArrays($array1[$key], $array2[$key]);
            }
            else {
                $array1[$key] = $value;
            }
        }

        return $array1;
    }

    /**
     * Load configurations for specific environment/base
     */
    public static function load($environment)
    {
        if (array_key_exists($environment, self::$config)) {
            self::$environment = $environment;
            self::setBase($environment);
        }
    }

    /**
     * Extend configurations between environments
     */
    public static function extend($environment, array $extends)
    {
        $extendCount = count($extends);
        $array = array();

        //$array = self::$config[$environment];

        for ($i=0; $i < $extendCount; $i++) {
            $extendName = $extends[$i];

            if ($i==0 && isset(self::$config[$extendName])) {
                $array = self::$config[$extendName];
            }
            else if (isset(self::$config[$extendName])){
                $array = self::mergeArrays($array, self::$config[$extendName]);
            }
        }

        // If environment already exist merge with it
        if (isset(self::$config[$environment])) {
            $array = self::mergeArrays($array, self::$config[$environment]);
        }
        // If not create it
        self::$config[$environment] = $array;

    }

    /**
     * Set config value
     *
     * @param   string $key Key for the config value
     * @param   mixed $value Value for the key
     */
    public static function set($key, $value)
    {
        $key = self::$keyBase.$key;

        $keys  = explode('.', $key); // [0] => development [1] => database [2] => dsn
        $array = self::setRecursiveKey($keys, $value);
        self::$config = self::mergeArrays(self::$config, $array);
    }

    /**
     * Read config value
     *
     * @param   string $key Key to read from config file
     * @return  mixed Value correspoding the key
     */
    public static function read($key)
    {
        $value  = null;
        $config = null;
        $keys = explode('.', $key); // [0] => database [1] => dsn

        // Environmet variable in key also
        if (isset($keys[0]) && array_key_exists($keys[0], self::$config)) {
            $config = self::$config;
        }
        else if (isset(self::$config[self::$environment])){
            $config = self::$config[self::$environment];
        }

        if (is_array($config)) {
            foreach ($keys as $keyName) {
                if (isset($config[$keyName])) {
                    $config = $config[$keyName];
                    $value  = $config;
                }
            }
        }

        return $value;
    }
}