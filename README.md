Config
======
Config is a container that can be used to store and retrieve configuration settings.

Usage
-----
### Register
Use the ``register`` method to add a path to the set of registered configuration folders.

    // Adds a configuration folder named 'auth'.
    Config::register('auth', '../modules/auth/config');

### Has
Use the ``has`` method to check if a configuration setting has been defined.

The method will return true if the setting has been defined, and false if it hasn't.

    // Is 'user' defined in the 'database' config file under the
    // 'auth' config path.
    $exists = Config::has('auth.database.user');

### Set
Use the ``set`` method to register one or more configuration settings.

Settings are typically defined by returning an array from a file stored under a registered config folder. However, it is also possible to explicitly set configuration values using the Config class.

    // Override 'mongo.user' in the 'database' config file
    // under the 'auth' config folder.
    Config::set('auth.database.mongo.user', 'root');

    // Set 'https' to 'enabled' in the global config namespace:
    Config::set('https', 'enabled');

### Mode
Use the ``mode`` method to set a configuration mode (i.e. environment).

The mode determines the configuration subfolder from which settings are retrieved. For example, if the mode is set to 'production', the 'get' method will look for settings in a sub-folder named 'production', falling back to the parent config folder if the requested settings cannot be found.

The default mode is 'development'.

    // Set the current mode to 'test'.
    Config::mode('test);

### Get
Use the ``get`` method to retrieve a configuration setting.

Typically called using a multi-part key of the form: *folder.file.setting*

Where *folder* is the name of a registered config folder, *file* is the name (without the extension) of a file within the folder, and *setting* is the index of an element in the array returned from the config file.

Settings retrieved using the form above will be 'lazy loaded'. i.e. the config file will only be parsed when first referenced.

The method will return null if the requested setting has not been defined, or a default value if supplied.

    // Retrieve 'mongo.user' from the 'database' config file
    // under the 'auth' config folder.
    $user = Config::get('auth.database.mongo.user');

    // Get 'https' from the global config namespace, returning
    // a default value of 'disabled' if the setting has not
    // been defined.
    $https = Config::get('https', 'disabled');