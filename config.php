<?php
/**
 * @author  Peter Hinchley
 * @license http://sam.zoy.org/wtfpl
 */

/**
 * The 'Config' class is a container that can be used to store and
 * retrieve configuration settings.
 */
class Config {
  /**
   * The loaded items in the configuration container.
   *
   * @var array
   */
  protected static $items = array();

  /**
   * The paths of registered configuration folders.
   *
   * @var array
   */
  protected static $paths = array('$' => './config');

  /**
   * The configuration mode.
   *
   * @var string
   */
  protected static $mode = 'development';

  /**
   * Adds a path to the set of registered configuration folders.
   *
   * <code>
   *   // Adds a configuration folder named 'auth'.
   *   Config::register('auth', '../modules/auth/config');
   * </code>
   *
   * @param  string $name The unique identifier for the path.
   * @param  string $path The path of the configuration folder.
   * @return void
   */
  public static function register($name, $path) {
    static::$paths[$name] = rtrim($path, '/');
  }

  /**
   * Check if a configuration setting has been defined.
   *
   * The method will return true if the setting has been defined,
   * and false if it hasn't.
   *
   * <code>
   *   // Is 'user' defined in the 'database' config file under the
   *   // 'auth' config path.
   *   $exists = Config::has('auth.database.user');
   * </code>
   *
   * @param  string  $key The unique identifier for the setting.
   * @return boolean
   */
  public static function has($key) {
    return static::get($key) !== null;
  }

  /**
   * Register one or more configuration settings.
   *
   * Settings are typically defined by returning an array from a
   * file stored under a registered config folder. However, it is
   * also possible to explicitly set configuration values using
   * the Config class.
   *
   * <code>
   *   // Override 'mongo.user' in the 'database' config file
   *   // under the 'auth' config folder.
   *   Config::set('auth.database.mongo.user', 'root');
   *
   *   // Set 'https' to 'enabled' in the global config namespace:
   *   Config::set('https', 'enabled');
   * </code>
   *
   * @param  string $key The unique identifier for the setting.
   * @param  mixed  $val The configuration value.
   * @return void
   */
  public static function set($key, $val) {
    $items = &static::$items;

    foreach(explode('.', $key) as $step) {
      $items = &$items[$step];
    }

    $items = $val;
  }

  /**
   * Set a configuration mode (i.e. environment).
   *
   * The mode determines the configuration subfolder from which
   * settings are retrieved. For example, if the mode is set to
   * 'production', the 'get' method will look for settings in
   * a sub-folder named 'production', falling back to the parent
   * config folder if the requested settings cannot be found.
   *
   * The default mode is 'development'.
   *
   * <code>
   *   // Set the current mode to 'test'.
   *   Config::mode('test);
   * </code>
   *
   * @param  string $mode Name of the configuration sub-folder.
   * @return void
   */
  public static function mode($mode) {
    static::$mode = $mode;
  }

  /**
   * Retrieve a configuration setting.
   *
   * Typically called using a multi-part key of the form:
   *   folder.file.setting
   *
   * Where 'folder' is the name of a registered config folder,
   * 'file' is the name (without the extension) of a file within
   * the folder, and 'setting' is the index of an element in the
   * array returned from the config file.
   *
   * Settings retrieved using the form above will be 'lazy loaded'.
   * i.e. the config file will only be parsed when first referenced.
   *
   * The method will return null if the requested setting has not
   * been defined, or a default value if supplied.
   * 
   * <code>
   *   // Retrieve 'mongo.user' from the 'database' config file
   *   // under the 'auth' config folder.
   *   $user = Config::get('auth.database.mongo.user');
   *
   *   // Get 'https' from the global config namespace, returning
   *   // a default value of 'disabled' if the setting has not
   *   // been defined.
   *   $https = Config::get('https', 'disabled');
   * </code>
   *
   * @param  string $key The unique identifier for the setting.
   * @param  mixed  $default The default value.
   * @return mixed
   */
  public static function get($key, $default = null) {
    $segments = explode('.', $key);

    // Check to see if the value is already loaded.
    $value = static::lookup($segments);
    if ($value !== null) return $value;

    if (count($segments) < 3) return $default;

    // Attempt a 'lazy load' if the key has at least 3 segments.
    if (!static::load($segments)) return $default;

    // Recheck for the setting.
    $value = static::lookup($segments);
    return $value !== null ? $value : $default;
  }

  /**
   * Lookup a loaded configuration item given a 'dot notation' key
   * that has been exploded into an array.
   *
   * @param  array $segments An exploded 'dot notation' key.
   * @return mixed The configuration value, or null.
   */
  protected static function lookup($segments) {
    $items = &static::$items;

    foreach($segments as $step) {
      if (is_array($items) && array_key_exists($step, $items)) {
        $items = &$items[$step];  
      } else {
        return null;
      }
    }

    return $items;
  }

  /**
   * Load a configuration file given a 'dot notation' key that has
   * been exploded into an array.
   *
   * @param  array $segments An exploded 'dot notation' key.
   * @return bool  True if a non-empty configuration file was loaded.
   */
  protected static function load($segments) {
    $config = array();
    list($folder, $file, $setting) = $segments;

    // Check if 'folder' is registered.
    if (!isset(static::$paths[$folder])) return false;

    $paths = array(
      static::$paths[$folder],
      static::$paths[$folder].'/'.static::$mode
    );

    // Configuration files cascade. This permits 'mode' specific
    // settings to merge with generic settings.
    foreach ($paths as $path) {
      if (file_exists($path = "$path/$file.php")) {
        $config = array_merge($config, require $path);
      }
    }

    return empty($config) ? false :
      (bool) static::$items[$folder][$file] = $config;
  }
}