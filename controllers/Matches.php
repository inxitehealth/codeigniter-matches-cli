<?php if (!defined('BASEPATH')) exit('File not found.');

#!/usr/bin/php

/*
 * Copyright (C) 2014 @avenirer [avenir.ro@gmail.com]
 * Everyone is permitted to copy and distribute verbatim or modified copies of this license document,
 * and changing it is allowed as long as the name is changed.
 * DON'T BE A DICK PUBLIC LICENSE TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION
 *
 ***** Do whatever you like with the original work, just don't be a dick.
 ***** Being a dick includes - but is not limited to - the following instances:
 ********* 1a. Outright copyright infringement - Don't just copy this and change the name.
 ********* 1b. Selling the unmodified original with no work done what-so-ever, that's REALLY being a dick.
 ********* 1c. Modifying the original work to contain hidden harmful content. That would make you a PROPER dick.
 ***** If you become rich through modifications, related works/services, or supporting the original work, share the love. Only a dick would make loads off this work and not buy the original works creator(s) a pint.
 ***** Code is provided with no warranty.
 *********** Using somebody else's code and bitching when it goes wrong makes you a DONKEY dick.
 *********** Fix the problem yourself. A non-dick would submit the fix back.
 *
 *
 * filename: Matches.php
 * This project started from a great idea posted by @veedeoo [veedeoo@gmail.com] on http://www.daniweb.com/web-development/php/code/477847/codeigniter-cli-trainer-script-creates-simple-application
 * License info: http://www.dbad-license.org/
 */

/* first we make sure this isn't called from a web browser */
if (PHP_SAPI !== 'cli') exit('File not found.');
/* raise or eliminate limits we would otherwise put on http requests */
set_time_limit(0);
ini_set('memory_limit', '256M');

class Matches extends CI_Controller
{
    private $_c_extends;
    private $_mo_extends;
    private $_mi_extends;
    private $_templates_loc;

    private $_tab = "\t";
    private $_tab2 = "\t\t";
    private $_tab3 = "\t\t\t";

    private $_ret = "\n";
    private $_ret2 = "\n\n";
    private $_rettab = "\n\t";
    private $_tabret = "\t\n";

    private $_find_replace = [];

    public function __construct()
    {
        parent::__construct();

        $this->config->load('matches', true);
        $this->config->load('migration', true);
        $this->_templates_loc = APPPATH . $this->config->item('templates', 'matches');
        $this->_c_extends = $this->config->item('c_extends', 'matches');
        $this->_mo_extends = $this->config->item('mo_extends', 'matches');
        $this->_mi_extends = $this->config->item('mi_extends', 'matches');

        if (ENVIRONMENT === 'production') {
            echo $this->_ret;
            echo "======== WARNING ========" . $this->_ret;
            echo "===== IN PRODUCTION =====" . $this->_ret;
            echo "=========================" . $this->_ret;
            echo "Are you sure you want to work with CLI on a production app? (y/n)";
            $line = fgets(STDIN);
            if (trim($line) != 'y') {
                echo "Aborting!" . $this->_ret;
                exit;
            }
            echo $this->_ret;
            echo "Thank you, continuing..." . $this->_ret2;
        }
        $this->load->helper('file');
    }

    public function _remap(string $method, array $params = [])
    {
        if (strpos($method, ':')) {
            $method = str_replace(':', '_', $method);
        }
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $params);
        }
    }

    /**
     * Used to test the ability to call this controller
     *
     * @return string Message confirming successful call
     */
    public function index()
    {
        echo 'Hello. Need help to ignite somethin\'?' . $this->_ret2;
        return;
    }

    /**
     * Used to test the ability to call this controller's methods
     *
     * @param string $name Name to echo
     *
     * @return string Message confirming successful call
     */
    public function hello(string $name)
    {
        echo 'Hello ' . $name . $this->_ret2;
        return;
    }

    /**
     * Lists the available commands
     *
     * @return string List of all available commands
     */
    public function help()
    {
        echo $this->_ret . 'Available commands:';
        echo $this->_ret2 . ' create';
        echo $this->_ret . '  app name_of_app';
        echo $this->_ret . '  controller name_of_controller';
        echo $this->_ret . '  migration name_of_migration name_of_table-(OPTIONAL)';
        echo $this->_ret . '  model name_of_model';
        echo $this->_ret . '  view name_of_view';
        echo $this->_ret2 . ' encryption_key string_to_hash-(OPTIONAL)';
        echo $this->_ret2 . $this->_ret2;
        return;
    }

    /**
     * Used to create a file
     *
     * @param string|null $what One of app, controller, model, view, or migration
     * @param string|null $name The name of the file to be created
     *
     * @return bool True if successful, else false
     */
    public function create(string $what = null, string $name = null) : bool
    {
        $what = filter_var($what, FILTER_SANITIZE_STRING);
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        $can_create = ['app', 'controller', 'model', 'view', 'migration'];
        if (!in_array($what, $can_create)) {
            echo $this->_ret . 'I can only create the following: app, controller, model, migration' . $this->_ret2;
            return false;
        }
        if (empty($name)) {
            echo $this->_ret . 'You didn\'t provide a name for the ' . $what . $this->_ret2;
            return false;
        }
        switch ($what) {
            case 'app':
                $this->create_app($name);
                break;
            case 'controller':
                $this->create_controller($name);
                break;
            case 'model':
                $this->create_model($name);
                break;
            case 'view':
                $this->create_view($name);
                break;
            case 'migration':
                $this->create_migration($name);
                break;
        }
        return true;
    }

    /**
     * Used to create an application which includes a controller,
     * a model, and a view from the each respective template
     *
     * @param string|null $app Name of application to create
     *
     * @return bool True if successful, else false
     */
    public function create_app(string $app = null)
    {
        if (!isset($app)) {
            echo $this->_ret . 'You need to provide a name for the app' . $this->_ret2;
            return false;
        }
        if (file_exists('application' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $this->_filename($app) . '.php') or (class_exists('' . $app . '')) or (class_exists('' . $app . '_model'))) {
            echo $app . ' Controller or Model already exists in the application/controllers directory.' . $this->_ret2;
            return false;
        }
        $this->create_controller($app);
        $this->create_model($app);
        $this->create_view($app);
    }

    /**
     * Used to create a controller from the controller template
     *
     * @return bool True if successful, else false
     */
    public function create_controller() : bool
    {
        $available = ['extend' => 'extend', 'e' => 'extend'];
        $params = func_get_args();
        $arguments = [];
        foreach ($params as $parameter) {
            $argument = explode(':', $parameter);
            if (count($argument) == 1 && !isset($controller)) {
                $controller = $argument[0];
            } elseif (array_key_exists($argument[0], $available)) {
                $arguments[$available[$argument[0]]] = $argument[1];
            }
        }
        if (!isset($controller)) {
            echo $this->_ret . 'You need to provide a name for the controller.' . $this->_ret2;
            return false;
        }
        $names = $this->_names($controller);
        $class_name = $names['class'];
        $file_name = $names['file'];
        $directories = $names['directories'];
        if (file_exists(APPPATH . 'controllers' . DIRECTORY_SEPARATOR . $file_name . '.php')) {
            echo $this->_ret . $class_name . ' Controller already exists in the application/controllers' . $directories . ' directory.' . $this->_ret2;
            return false;
        }
        $f = $this->_get_template('controller');
        if ($f === false) return false;
        $this->_find_replace['{{CONTROLLER}}'] = $class_name;
        $this->_find_replace['{{CONTROLLER_FILE}}'] = $file_name . '.php';
        $this->_find_replace['{{MV}}'] = strtolower($class_name);
        $extends = array_key_exists('extend', $arguments) ? $arguments['extend'] : $this->_c_extends;
        $extends = in_array(strtolower($extends), ['my', 'ci']) ? strtoupper($extends) : ucfirst($extends);
        $this->_find_replace['{{C_EXTENDS}}'] = $extends;
        $f = strtr($f, $this->_find_replace);
        if (strlen($directories) > 0 && !file_exists(APPPATH . 'controllers' . DIRECTORY_SEPARATOR . $directories)) {
            mkdir(APPPATH . 'controllers' . DIRECTORY_SEPARATOR . $directories, 0777, true);
        }
        if (!write_file(APPPATH . 'controllers' . DIRECTORY_SEPARATOR . $file_name . '.php', $f)) {
            echo $this->_ret . 'Couldn\'t write Controller.' . $this->_ret2;
            return false;
        }
        echo $this->_ret . 'Controller ' . $class_name . ' has been created inside ' . APPPATH . 'controllers' . DIRECTORY_SEPARATOR . $directories . '.' . $this->_ret2;
        return true;
    }

    /**
     * Used to create a model from the model template
     *
     * @return bool True if successful, else false
     */
    public function create_model() : bool
    {
        $available = ['extend' => 'extend', 'e' => 'extend'];
        $params = func_get_args();
        $arguments = [];
        foreach ($params as $parameter) {
            $argument = explode(':', $parameter);
            if (count($argument) == 1 && !isset($model)) {
                $model = $argument[0];
            } elseif (array_key_exists($argument[0], $available)) {
                $arguments[$available[$argument[0]]] = $argument[1];
            }
        }
        if (!isset($model)) {
            echo $this->_ret . 'You need to provide a name for the model.' . $this->_ret2;
            return false;
        }
        $names = $this->_names($model);
        $class_name = $names['class'];
        $file_name = $names['file'];
        $directories = $names['directories'];
        if (file_exists(APPPATH . 'models' . DIRECTORY_SEPARATOR . $file_name . '.php')) {
            echo $this->_ret . $class_name . ' Model already exists in the application/models' . $directories . ' directory.' . $this->_ret2;
            return false;
        }
        $f = $this->_get_template('model');
        if ($f === false) return false;
        $this->_find_replace['{{MODEL}}'] = $class_name;
        $this->_find_replace['{{MODEL_FILE}}'] = $file_name . '.php';

        $extends = array_key_exists('extend', $arguments) ? $arguments['extend'] : $this->_mo_extends;
        $extends = in_array(strtolower($extends), ['my', 'ci']) ? strtoupper($extends) : ucfirst($extends);

        $this->_find_replace['{{MO_EXTENDS}}'] = $extends;
        $f = strtr($f, $this->_find_replace);
        if (strlen($directories) > 0 && !file_exists(APPPATH . 'models' . DIRECTORY_SEPARATOR . $directories)) {
            mkdir(APPPATH . 'models' . DIRECTORY_SEPARATOR . $directories, 0777, true);
        }
        if (!write_file(APPPATH . 'models' . DIRECTORY_SEPARATOR . $file_name . '.php', $f)) {
            echo $this->_ret . 'Couldn\'t write Model.' . $this->_ret2;
            return false;
        }
        echo $this->_ret . 'Model ' . $class_name . ' has been created inside ' . APPPATH . 'models' . DIRECTORY_SEPARATOR . $directories . '.' . $this->_ret2;
        return true;
    }

    /**
     * Used to create a view from the view template
     *
     * @param  string|null $view Name of view
     *
     * @return bool True if successful, else false
     */
    public function create_view(string $view = null) : bool
    {
        $available = [];
        $params = func_get_args();
        $arguments = [];
        foreach ($params as $parameter) {
            $argument = explode(':', $parameter);
            if (count($argument) == 1 && !isset($view)) {
                $view = $argument[0];
            } elseif (array_key_exists($argument[0], $available)) {
                $arguments[$available[$argument[0]]] = $argument[1];
            }
        }
        if (!isset($view)) {
            echo $this->_ret . 'You need to provide a name for the view file.' . $this->_ret2;
            return false;
        }
        $names = $this->_names($view);
        $file_name = strtolower($names['file']);
        $directories = $names['directories'];
        if (file_exists(APPPATH . 'views' . DIRECTORY_SEPARATOR . $file_name . '.php')) {
            echo $this->_ret . $file_name . ' View already exists in the application/views/' . $directories . ' directory.' . $this->_ret2;
            return false;
        }
        $f = $this->_get_template('view');
        if ($f === false) return false;
        $this->_find_replace['{{VIEW}}'] = $file_name . '.php';
        $f = strtr($f, $this->_find_replace);
        if (strlen($directories) > 0 && !file_exists(APPPATH . 'views' . DIRECTORY_SEPARATOR . $directories)) {
            mkdir(APPPATH . 'views' . DIRECTORY_SEPARATOR . $directories, 0777, true);
        }
        if (!write_file(APPPATH . 'views' . DIRECTORY_SEPARATOR . $file_name . '.php', $f)) {
            echo $this->_ret . 'Couldn\'t write View.' . $this->_ret2;
            return false;
        }
        echo $this->_ret . 'View ' . $file_name . ' has been created inside ' . APPPATH . 'views' . DIRECTORY_SEPARATOR . $directories . '.' . $this->_ret2;
        return true;
    }

    /**
     * Used to execute all new migrations if no version is specified
     *
     * @param int|null $version The migration version number to run
     *
     * @return bool True if successful, else false
     */
    public function do_migration(int $version = null) : bool
    {
        $this->load->library('migration');
        if (isset($version) && ($this->migration->version($version) === false)) {
            show_error($this->migration->error_string());
            error_log("Migration failed.");
            return false;
        } elseif (is_null($version) && $this->migration->latest() === false) {
            show_error($this->migration->error_string());
            error_log("Migration failed 2.");
            return false;
        } else {
            echo $this->_ret . 'The migration has concluded successfully.' . $this->_ret2;
            return true;
        }
    }

    /**
     * Used to roll back to the specified migration version
     *
     * @param int|null $version The migration version number to rollback to or a negative int indicating the number of migrations to roll back, starting from the current
     *
     * @return bool True if successful, else false
     */
    public function undo_migration(int $version = null) : bool
    {
        $this->load->library('migration');
        $migrations = $this->migration->find_migrations();
        $migration_keys = [];
        foreach ($migrations as $key => $migration) {
            $migration_keys[] = $key;
        }
        $current_version = $this->_get_version();
        if (isset($version) && $version == $current_version) {
            echo $this->_ret . 'Undo migration failed. The entered version is the same as the current version. Please use "redo:migration" or enter a different version number.' . $this->_ret2;
            return false;
        } elseif (isset($version) && array_key_exists($version, $migrations) && $this->migration->version($version)) {
            echo $this->_ret . 'The migration was reset to the version: ' . $version . $this->_ret2;
            return true;
        } elseif (isset($version) && $version < 0) {
            $keys = array_keys($migrations);
            $desired_version = abs($version) <= count($migration_keys) ? $keys[array_search($current_version, $keys) - abs($version)] : 0;
            if ($desired_version == 0) {
                echo "Are you sure you want to rollback all migrations? (y/n)";
            } else {
                echo "Are you sure you want to roll back to migration version " . $desired_version . "? (y/n)";
            }
            $line = fgets(STDIN);
            if (trim($line) != 'y') {
                echo "Aborting!" . $this->_ret;
                exit;
            }
        } elseif (isset($version) && !array_key_exists($version, $migrations)) {
            echo $this->_ret . 'The entered migration version number ' . $version . ' doesn\'t exist.' . $this->_ret2;
            return false;
        } else {
            $desired_version = (count($migration_keys) == 1) ? 0 : $migration_keys[count($migration_keys) - 2];
        }
        if (!$this->migration->version($desired_version)) {
            echo $this->_ret . 'Couldn\'t roll back the migration.' . $this->_ret2;
            return false;
        }
        echo $this->_ret . 'The migration has been rolled back successfully.' . $this->_ret2;
        return true;
    }

    /**
     * Used to re-run the current migration
     *
     * @return bool True if successful, else false
     */
    public function redo_migration() : bool
    {
        $this->load->library('migration');
        $migrations = $this->migration->find_migrations();
        $migration_keys = [];
        foreach ($migrations as $key => $migration) {
            $migration_keys[] = $key;
        }
        $current_version = $this->_get_version();
        $keys = array_keys($migrations);
        $previous_version = $keys[array_search($current_version, $keys) - 1];

        if (!$this->migration->version($previous_version)) {
            show_error($this->migration->error_string());
            error_log("Failed rolling back migration.");
            return false;
        }

        if (!$this->migration->version($current_version)) {
            show_error($this->migration->error_string());
            error_log("Failed running migration.");
            return false;
        }

        echo $this->_ret . 'The migration version ' . $current_version . ' has been re-run successfully.' . $this->_ret2;
        return true;
    }

    /**
     * Reset the migrations to the version specified in the migration config file
     *
     * @return bool True if successful, else false
     */
    public function reset_migration() : bool
    {
        $this->load->library('migration');
        if ($this->migration->current() === false) {
            echo $this->_ret . 'Couldn\'t reset migration.' . $this->_ret2;
            show_error($this->migration->error_string());
            return false;
        }
        echo $this->_ret . 'The migration was reset to the version set in the config file.' . $this->_ret2;
        return true;
    }

    /**
     * Verify that the application has migrations enabled
     *
     * @return bool True if migrations are enabled, else false
     */
    public function verify_migration_enabled() : bool
    {
        $migration_enabled = $this->config->item('migration_enabled');
        if ($migration_enabled === false) {
            echo $this->_ret . 'Your app is not migration enabled. Enable it inside application/config/migration.php' . $this->_ret2;
            return false;
        }
        return true;
    }

    /**
     * Used to create a migration from the migration template
     *
     * @return bool True if successful, else false
     */
    public function create_migration() : bool
    {
        $available = ['extend' => 'extend', 'e' => 'extend', 'table' => 'table', 't' => 'table'];
        $params = func_get_args();
        $arguments = [];
        foreach ($params as $parameter) {
            $argument = explode(':', $parameter);
            if (count($argument) == 1 && !isset($action)) {
                $action = $argument[0];
            } elseif (array_key_exists($argument[0], $available)) {
                $arguments[$available[$argument[0]]] = $argument[1];
            }
        }
        if (!isset($action)) {
            echo $this->_ret . 'You need to provide a name for the migration.' . $this->_ret2;
            return false;
        }
        $class_name = 'Migration_' . ucfirst($action);
        $this->config->load('migration', true);
        $migration_path = $this->config->item('migration_path', 'migration');
        if (!file_exists($migration_path)) {
            if (mkdir($migration_path, 0755)) {
                echo $this->_ret . 'Folder migrations created.' . $this->_ret2;
            } else {
                echo $this->_ret . 'Couldn\'t create folder migrations.' . $this->_ret2;
                return false;
            }
        }
        $this->verify_migration_enabled();
        $migration_type = $this->config->item('migration_type', 'migration');
        if (empty($migration_type)) {
            $migration_type = 'sequential';
        }
        if ($migration_type == 'timestamp') {
            $file_name = date('YmdHis') . '_' . strtolower($action);
        } else {
            $latest_migration = 0;
            foreach (glob($migration_path . '*.php') as $migration) {
                $pattern = '/[0-9]{3}/';
                if (preg_match($pattern, $migration, $matches)) {
                    $migration_version = intval($matches[0]);
                    $latest_migration = ($migration_version > $latest_migration) ? $migration_version : $latest_migration;
                }
            }
            $latest_migration = (string)++$latest_migration;
            $file_name = str_pad($latest_migration, 3, '0', STR_PAD_LEFT) . '_' . strtolower($action);
        }
        if (file_exists($migration_path . $file_name) or (class_exists($class_name))) {
            echo $this->_ret . $class_name . ' Migration already exists.' . $this->_ret2;
            return false;
        }
        $f = $this->_get_template('migration');
        if ($f === false) return false;
        $this->_find_replace['{{MIGRATION}}'] = $class_name;
        $this->_find_replace['{{MIGRATION_FILE}}'] = $file_name;
        $this->_find_replace['{{MIGRATION_PATH}}'] = $migration_path;

        $extends = array_key_exists('extend', $arguments) ? $arguments['extend'] : $this->_mi_extends;
        $extends = in_array(strtolower($extends), ['my', 'ci']) ? strtoupper($extends) : ucfirst($extends);

        $this->_find_replace['{{MI_EXTENDS}}'] = $extends;
        $table = 'SET_YOUR_TABLE_HERE';

        if (array_key_exists('table', $arguments)) {
            if ($arguments['table'] == '%inherit%' || $arguments['table'] == '%i%') {
                $table = preg_replace('/rename_|remove_|modify_|delete_|add_|create_|_table|_tbl/i', '', $action);
            } else {
                $table = $arguments['table'];
            }
        }

        $this->_find_replace['{{TABLE}}'] = $table;
        $f = strtr($f, $this->_find_replace);
        if (!write_file($migration_path . $file_name . '.php', $f)) {
            echo $this->_ret . 'Couldn\'t write Migration.' . $this->_ret2;
            return false;
        }
        echo $this->_ret . 'Migration ' . $class_name . ' has been created.' . $this->_ret2;
        return true;
    }

    /**
     * Used to set the encryption key in your config file
     *
     * @param string|null $string Desired Unix timestamp with microseconds
     *
     * @return bool True if successful, else false
     */
    public function encryption_key(string $string = null) : bool
    {
        if (is_null($string)) {
            $string = microtime();
        }
        $key = hash('ripemd128', $string);
        $files = $this->_search_files(APPPATH . 'config' . DIRECTORY_SEPARATOR, 'config.php');
        if (empty($files)) {
            echo $this->_ret . 'Couldn\'t find config.php' . $this->_ret2;
            return false;
        }
        $search = '$config[\'encryption_key\'] = \'\';';
        $replace = '$config[\'encryption_key\'] = \'' . $key . '\';';
        foreach ($files as $file) {
            $file = trim($file);
            $f = file_get_contents($file);
            if (strpos($f, $search) === false) {
                echo $this->_ret . 'Couldn\t find encryption_key or encryption_key already exists in ' . $file . '.' . $this->_ret2;
                return false;
            }
            $f = str_replace($search, $replace, $f);
            if (write_file($file, $f)) {
                echo $this->_ret . 'Encryption key ' . $key . ' added to ' . $file . '.' . $this->_ret2;
                return true;
            } else {
                echo $this->_ret . 'Couldn\'t write encryption key ' . $key . ' to ' . $file . '.' . $this->_ret2;
                return false;
            }
        }
    }

    /**
     * Searches a directory for files
     *
     * @param string $path Directory to search
     * @param string $file File to find
     *
     * @return array Contains the names of files found during the search
     */
    private function _search_files(string $path, string $file) : array
    {
        $directory = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($directory);
        $files = [];
        foreach ($iterator as $file) {
            if ($file->getFilename() == 'config.php') {
                $found = str_replace('\\', DIRECTORY_SEPARATOR, $this->_ret . $file->getPath() . DIRECTORY_SEPARATOR . $file);
                $files[] = $found;
            }
        }
        return $files;
    }

    /**
     * Finds file name, class name, and directory of the given string
     *
     * @param string $str Name of file to find
     *
     * @return array Associative array containing file name, class name, and directory
     */
    private function _names(string $str) : array
    {
        $str = strtolower($str);
        if (strpos($str, '.')) {
            $structure = explode('.', $str);
            $class_name = array_pop($structure);
        } else {
            $structure = [];
            $class_name = $str;
        }
        $class_name = ucfirst($class_name);
        $file_name = $class_name;
        if (substr(CI_VERSION, 0, 1) != '2') {
            $file_name = ucfirst($file_name);
        }
        $directories = implode(DIRECTORY_SEPARATOR, $structure);
        $file = $directories . DIRECTORY_SEPARATOR . $file_name;
        return ['file' => $file, 'class' => $class_name, 'directories' => $directories];
    }

    /**
     * Formats given string to appropriate case for a CI file name
     *
     * @param string $str String to be re-formatted
     *
     * @return string Given string in proper format
     */
    private function _filename(string $str) : string
    {
        $file_name = strtolower($str);
        if (substr(CI_VERSION, 0, 1) != '2') {
            $file_name = ucfirst($file_name);
        }
        return $file_name;
    }

    /**
     * Get the template file for the given file type
     *
     * @param string $type One of controller, migration, model, or view
     *
     * @return string|bool Contents of the template file, if found, else false
     */
    private function _get_template(string $type)
    {
        $template_loc = $this->_templates_loc . $type . '_template.txt';
        if (!file_exists($template_loc)) {
            echo $this->_ret . 'Couldn\'t find ' . $type . ' template.' . $this->_ret2;
            return false;
        }
        $f = file_get_contents($template_loc);
        return $f;
    }

    /**
     * Retrieves current schema version
     *
     * @return string Current migration version
     */
    private function _get_version() : string
    {
        $row = $this->db->select('version')->get($this->config->item('migration_table', 'migration'))->row();
        return $row ? $row->version : '0';
    }
}
