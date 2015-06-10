<?php

class lako_objects extends lako_lib_base{
  protected $version = '0.0.1';
  protected $singleton_instances = array();
  protected $data_types = array(
    'tinyint_signed' => array(
      'Label'        => 'Tiny Integer',
      'help'        => '-128 to +128',
      'system' => array(
        'name' => 'TINYINT',
      )
    ),
    'tinyint_unsigned' => array(
      'Label'        => 'Tiny Integer Unsigned',
      'help'        => '0 to 255',
      'system' => array(
        'name' => 'TINYINT',
        'attributes' => 'UNSIGNED',
      )
    ),
    'smallint_signed' => array(
      'Label'        => 'Small Integer',
      'help'        => '-32768 to +32767',
      'system' => array(
        'name' => 'SMALLINT',
      )
    ),
    'smallint_unsigned' => array(
      'Label'        => 'Small Integer Unsigned',
      'help'        => '0 to 65535',
      'system' => array(
        'name' => 'SMALLINT',
        'attributes' => 'UNSIGNED',
      )
    )
  );
  
  function __construct($config = array()){
    parent::__construct($config);
    $this->load_base();
  }
  
  /**
   * Loads base for object class
   */
  protected function load_base(){
    require_once $this->config['base_path'].'/object_base.php';
  }
  
  /**
   * Get the objects names.
   * 
   * @param String $module|Void  Module name of which you want objects, or empty if you want all module
   * @return Array $objects name of objects
   */
  function get_all_objects($module = null){  
    if(is_null($module)){
      $objects = scandir($this->config['definitions_path']);
      array_shift($objects);
      array_shift($objects);
      
      foreach(lako::get_modules() as $module_path){
        $l_objects = scandir($module_path.'/objects/definitions');
        array_shift($l_objects);
        array_shift($l_objects);
        $objects = array_merge($objects,$l_objects);
      }
    }else{
      $modules = lako::get_modules();
      $objects = scandir($modules['tourist'].'/objects/definitions');
      array_shift($objects);
      array_shift($objects);
    }
    
    
    foreach($objects as $key => $object)
      $objects[$key] = str_replace('.json','',$object);
    return $objects;
  }
  
  /**
   *  Get instance of a an object Definition
   */
  function get($object_name){
    //if we have singleton instance of it then we return that
    if(isset($this->singleton_instances[$object_name]))
      return $this->singleton_instances[$object_name];
      
    //find objects definition
    $definition = $this->get_deifintion($object_name);
    
    //Default class name
    $object_class_name = 'lako_object';
    
    //if there a special class for it then we return that
    $object_code_file = $this->locate_object_code_file($object_name);
    
    if($object_code_file){
      require_once $object_code_file;
      $object_class_name = $this->make_object_name($object_name);
      if(!class_exists($object_class_name))
        throw new Exception("Wrong class name for object {$object_name}, trying to find {$object_class_name}. Object file {$object_code_file}");
    }
    
    // Create and make sure it extends/is the base
    $this->singleton_instances[$object_name] = new $object_class_name($definition);
    if(!($this->singleton_instances[$object_name] instanceof lako_object))
      throw new Exception("{$object_class_name} must extend 'lako_object'");
      
    return $this->singleton_instances[$object_name];
  }
  
  
  /**
   * Tries find local object and objects in modules, and gives you definition in return.
   * 
   * @param String $object_name
   * @return Mixed $object_definition
   * @throws Exception Definition not found.
   * @throws Exception Definition has syntax error.
   */
  function get_deifintion($object_name){
    //find local
    $def_file_path = $this->config['definitions_path']."/{$object_name}.json";
    if(!file_exists($def_file_path)){
      //find in modules
      $def_file_path = $this->locate_definition_in_modules($object_name);
      if(!$def_file_path)
        throw new Exception("Definition for {$object_name} is not found.");
    }
    
    $maybe_definition = json_decode(file_get_contents($def_file_path),true);
    if(is_null($maybe_definition))
      throw new Exception("Invalid definition file for object {$object_name}, found at {$def_file_path}");
    return $maybe_definition;
  }
  
  /**
   * Find definition in modules.
   * @param String $object_name
   * @return Path on succes or false when not found
   */
  public function locate_definition_in_modules($object_name){
    foreach(lako::get_modules() as $module_path){
      $def_file_path = $module_path.'/objects/definitions'."/{$object_name}.json";
      if(file_exists($def_file_path))
        return $def_file_path;
    }
    return false;
  }
  
  /**
   * Find code file for object.
   * @param String $object_name
   * @return Path on succes or false when not found
   */
  public function locate_object_code_file($object_name){
    $possible_object_dedicated_file = $this->config['code_path']."/{$object_name}.php";
    if(file_exists($possible_object_dedicated_file))
      return $possible_object_dedicated_file;
    
    foreach(lako::get_modules() as $module_path){
      $possible_object_dedicated_file = $module_path.'/objects/code'."/{$object_name}.php";
      if(file_exists($possible_object_dedicated_file))
        return $possible_object_dedicated_file;
    }
    return false;
  }
  
  function make_object_name($object_name){
    return "{$object_name}{$this->config['object_suffix']}";
  }
  
}