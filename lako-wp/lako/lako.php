<?php
/**
 *  Lako Web Framework.
 *
 *
 *  Lako is a bundle of code modules to make Web Development faster, easier, secure and more stable. These modules are designed to seamlessly work with other modules in lako. 
 *  
 *  When used together these modules act intelligently and make the most out of other modules to provides out of the box solutions to many common Web Dev tasks. 
 *  
 *  Think of it as small intelligent robots helping each other doing work for you.
 */
 
/**
 *  Main lako class.
 *
 *
 *  lako is main class and entry point for all the other lako libraries. It manages and keep track of things.
 *  
 *  Typically used like lako::do_awesome();
 *  
 */
class lako{
  /**
   * Configuration
   */
  private static $config = array();
  
  /**
   * Keeps all the singleton lib's instances
   */
  private static $singleton_instances = array();
  
  /**
   * Stores modules paths
   */
  private static $modules_paths = array();
  
  private function __construct(){}
  
  /**
   * Initialize lako.  
   *
   * This must called before everything. 
   * @example lako-examples/lako-init.php Codeigniter Example.
   * 
   * 
   * @param $config  The configuration Array
   * @return void
   */
  public static function init(Array $config){
    self::set_config($config);
    self::load_base();
    self::set_paths();
  }
  
  /**
   *  Sets the paths, to find stuff when importing
   */
  public static function set_paths(){
    if(!isset(self::$config['modules_path']))
      return;
    self::add_modules_path(self::$config['modules_path']);
  }
  
  /**
   * Tracks all the modules on given path.
   * @param String $path Full path to modules DIR
   * @return null
   */
  public static function add_modules_path($path){
    $modules_paths = self::get_files($path);
    foreach($modules_paths as $module_path)
      self::$modules_paths[basename($module_path)] = $module_path;
  }
  
  /**
   *  Get modules and their paths.
   *  
   *  @param String $module_name Optional, If not provided all the modules are returned in array.
   *  @return Mixed  Returns path to single module or, Array of modules
   *  @throws Exception If module is not found.
   */
  public static function get_modules($module_name = null){
    if(is_null($module_name))
      return self::$modules_paths;
      
    if(!isset(self::$modules_paths[$module_name]))
      throw new Exception("Module with name {$module_name} is not found");
    
    return self::$modules_paths[$module_name];
  }
  
  /**
   * Loads most required files for lako.
   */
  private static function load_base(){  
    $base_files = self::get_files(self::$config['base_path']);
    foreach($base_files as $file);
      require_once $file;
  }
  
  
  
  /**
   * Set configuration data, this will replace previous configuration.
   * 
   * @param $config Configuration Array
   * @return void
   */
  public static function set_config(Array $config){
    self::$config = $config;
  }
  
  /**
   * Returns current configuration data.
   * 
   * @param String $index Optional, if not provided whole config array will be returned
   * @return Mixed Configuration data
   */
  public static function get_config($index = null){
    if(is_null($index))
      return self::$config;
    
    if(!isset(self::$config[$index]))
      return null;
      
    return self::$config[$index];
  }
  
  /**
   * Imports a library to global scope.
   * 
   * Similar to `require_once` but only for lako libs. Used for non singleton libs e.g. lako_templates.
   *
   * @example lako-examples/lako-import.php Importing lako_templates.
   *
   * @throws Exception  If lib file is not found.
   *
   * @todo Allow option to add more paths where libs could be found.
   *
   * @param String $lib Name of library without prefix e.g. lako
   * @return void
   */
  public static function import($lib){
    //try local
    $file_path = self::$config['libs_path'].'/'.$lib.'.php';
    if(file_exists($file_path)){
      require_once $file_path;
      return;
    }
    
    //try modules
    foreach(self::$modules_paths as $p){
      $file_path = $p.'/libs/'.$lib.'.php';
      if(file_exists($file_path)){
        require_once $file_path;
        return;
      }
    }
    
    //throw error for, no existent Lib
    throw new Exception("Lako Library does not exists at '{$file_path}'.");
  }
  
  /**
   * Get a singleton instance of a library, remember only certain libraries can be invoked this way.
   * 
   * @example lako-examples/lako-get.php Getting lako_objects.
   *
   * @throws Exception  If lib file is not found.
   * @throws Exception  If class names doesn't follows pattern.
   * @throws Exception  If class does not extends the lako_lib_base class.
   *
   * @param String $lib Name of library without prefix e.g. lako
   * @return Object Instance of a singleton laco lib.
   */
  public static function get($lib){
    //if we have it create then send
    if(isset(self::$singleton_instances[$lib]))
      return self::$singleton_instances[$lib];
      
    //make sure its imported
    self::import($lib);
    $class_name = self::make_lib_name($lib);
    if(!class_exists($class_name))
      throw new Exception("Could not find the Lako Library class '{$class_name}'");
      
    //check if the class allow singleton
   /* if(!$class_name::IS_SINGLETON)
      throw new Exception("{$class_name} cannot be a singleton. Please instantiate with 'new'.");*/
      
    // Initiate class and pass its config if we have any
    if(isset(self::$config[$lib]))
      $config = self::$config[$lib];
    else
      $config = array();
      
    //save in the single instance for the next time
    self::$singleton_instances[$lib] = new $class_name($config);
    
    if(!(self::$singleton_instances[$lib] instanceof lako_lib_base)){
      unset(self::$singleton_instances[$lib]);
      throw new Exception('Library class must extend "lako_lib_base"');
    }
    return self::$singleton_instances[$lib];
  }
  
  /**
   * Adds prefix to a class name.
   * @param $lib_name A lib name without prefix
   * @return string Returns prefixed lib
   */
  public static function make_lib_name($lib_name){ 
    return self::$config['class_prefix'].$lib_name;
  }
  
  /**
   * Utility function to get list of all the files in a Dir
   * 
   * @todo Move all the file related operations to a lib, or use a third party Lib.
   * 
   */
  public static function get_files($dir){ 
    if(!file_exists($dir))
      throw new Exception("Lako Base directory '{$dir}' does not exists");
    $files = scandir($dir);
    array_shift($files);
    array_shift($files);
    foreach($files as $key => $file)
      $files[$key] = rtrim($dir,'/\\') . '/' .$file;
    return $files;
  }
  
}