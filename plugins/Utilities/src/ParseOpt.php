<?php
/**
 *  easily parse through a variable amount of command line options
 *  
 *  @todo support array keys, so you could pass in: --name[key]="this is the value"
 *  and you would get something like: ['name'] => array('key'] => "this is the value") 
 *    
 *  @link http://php.net/manual/en/function.getopt.php
 *    
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 10-5-11
 ******************************************************************************/
class ParseOpt {
  
  /**
   *  holds the named fields
   *  
   *  @var  array
   */
  protected $field_map = array();
  
  /**
   *  holds the unnamed fields
   * 
   *  @var  array   
   */
  protected $list = array();

  /**
   *  create instance
   *  
   *  @param  array $argv
   *  @param  array $req_argv_map arguments that have to be present, see {@link assure()}   
   */
  public function __construct(array $argv,array $req_argv_map = array()){

    $this->parse($argv);
    $this->assure($req_argv_map);

  }//method
  
  /**
   *  get the list of passed in args, the list are the args that don't have a named
   *  option
   *  
   *  @example
   *    php cli.php --named="this is a named value" unnamed            
   *
   *  @return array   
   */
  public function getList(){ return $this->list; }//method
  
  /**
   *  true if there were un-named args parsed
   *
   *  @return boolean   
   */
  public function hasList(){ return !empty($this->list); }//method
  
  /**
   *  add a value to the list
   *  
   *  @param  mixed $val  the value
   */
  protected function addList($val){ $this->list[] = $val; }//method
  
  /**
   *  set $name to $value
   *
   *  @param  string  $name
   *  @param  mixed $val   
   */
  protected function setField($name,$val){
  
    if(isset($this->field_map[$name])){
      
      $this->field_map[$name] = (array)$this->field_map[$name];
      $this->field_map[$name][] = $val;
      
    }else{
    
      $this->field_map[$name] = $val;
      
    }//if/else
  
  }//method
  
  /**
   *  check if $key exists and is non-empty
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  public function hasField($key){ return !empty($this->field_map[$key]); }//method
  
  /**
   *  check if $key exists
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  public function existsField($key){ return array_key_exists($key,$this->field_map); }//method
  
  /**
   *  return the value of $key, return $default_val if key doesn't exist
   *
   *  @param  string  $key
   *  @param  mixed $default_val
   *  @return mixed
   */
  public function getField($key,$default_val = null){
  
    $val = $default_val;
  
    if(ctype_digit($key)){
    
      $val = isset($this->list[$key]) ? $this->list[$key] : $default_val;
    
    }else{
  
      $val = $this->existsField($key) ? $this->field_map[$key] : $default_val;
      
    }//if/else
      
    return $val;
      
  }//method
  
  /**
   *  check's if a field exists and is equal to $val
   *  
   *  @param  string  $key  the name
   *  @param  string  $val  the value to compare to the $key's set value
   *  @return boolean
   */
  public function isField($key,$val){
    $ret_bool = false;
    if($this->existsField($key)){
      $ret_bool = $this->getField($key) == $val;
    }//if
    return $ret_bool;
  }//method
  
  /**
   *  return the instance's field_map
   *  
   *  @return array
   */
  public function getFields(){ return $this->field_map; }//method
  
  /**
   *  return true if there are fields
   *  
   *  @since  6-30-11   
   *  @return boolean
   */
  public function hasFields(){ return !empty($this->field_map); }//method
  
  /**
   *  function to make passing arguments passed into the CLI easier
   *  
   *  to set values: --name=val or -name val 
   *  to set a value to true: --name or -name 
   *  
   *  if you want to do an array, then specify the name multiple times: --name=val1 --name=val2 will
   *  result in ['name'] => array(val1,val2)
   *  
   *  we don't use php's http://php.net/manual/en/function.getopt.php because inputs are variable
   *  and we don't know what they will be before hand. Really, I'm shocked at how unflexible php's
   *  built in solution is                  
   *  
   *  @example
   *    // command line: php test.php --foo=bar baz che -z zed
   *    $this->getList(); // array('baz','che')
   *    $this->getFields(); // array('foo' => 'bar','z' => 'zed')
   *      
   *  @param  array $argv the values passed into php from the commmand line
   *  @param  array $required_argv_map hold required args that need to be passed in to be considered valid.
   *                                  The name is the key and the required value will be the val, if the val is null
   *                                  then the name needs to be there with a value (in $argv), if the val 
   *                                  is not null then that will be used as the default value if 
   *                                  the name isn't passed in with $argv  
   */
  protected function parse(array $argv){
  
    // canary...
    if(empty($argv)){ return array(); }//if
  
    $argv = $this->normalize($argv);
    $name = $val = null;
  
    $ret_list = array();
    $ret_map = array();
  
    for($i = 0, $max = count($argv); $i < $max ; $i++){
    
      $arg = $argv[$i];
    
      if($this->isLong($arg)){
      
        list($name,$val) = $this->parseLong($arg);
        $this->setField($name,$val);
        
        $ret_map[$name] = $val;
      
      }else if($this->isShort($arg)){
      
        list($i,$name,$val) = $this->parseShort($i,$argv);
        $this->setField($name,$val);
      
      }else{
      
        $this->addList($arg);
      
      }//if/else
    
    }//foreach
  
  }//method
  
  /**
   *  true if the $arg is a long opt (eg, --name=val)
   *
   *  @param  string  $arg
   *  @return boolean      
   */
  protected function isLong($arg){
  
    $ret_bool = false;
  
    if(preg_match('#^--[^-]#',$arg)){ $ret_bool = true; }//if
  
    return $ret_bool;
  
  }//method
  
  /**
   *  true if the $arg is a short opt (eg, -n val)
   *
   *  @param  string  $arg
   *  @return boolean
   */
  protected function isShort($arg){
  
    $ret_bool = false;
  
    if(preg_match('#^-[^-]#',$arg)){ $ret_bool = true; }//if
  
    return $ret_bool;
  
  }//method
  
  /**
   *  parse a short argument (eg, -name val)
   *  
   *  @param  integer $i  current position of $argv
   *  @param  array $argv
   *  @return array array($i,$name,$val)
   */
  protected function parseShort($i,array $argv){
  
    // strip off the dashes...
    $name = mb_substr($argv[$i],1);
    $val = true;
    
    if(isset($argv[$i + 1])){
    
      if(!$this->isShort($argv[$i + 1]) && !$this->isLong($argv[$i + 1])){
      
        $val = $this->normalizeVal($argv[$i + 1]);
        $i += 1;
      
      }//if
    
    }//if
  
    return array($i,$name,$val);
  
  }//method
  
  /**
   *  parse a long argument (eg, --name=val)
   *  
   *  @param  string  $arg
   *  @return array array($name,$val)
   */
  protected function parseLong($arg){
  
    $arg_bits = explode('=',$arg,2);
    
    // strip off the dashes...
    $name = mb_substr($arg_bits[0],2);
    
    $val = true;
    if(isset($arg_bits[1])){
      
      $val = $this->normalizeVal($arg_bits[1]);
      
    }//if
  
    return array($name,$val);
  
  }//method
  
  /**
   *  converts a val into the correct type
   *  
   *  if the val is an integer, cast it as an int
   *  if the val is something like TRUE then make it a boolean         
   *
   *  @param  mixed $val
   *  @return mixed the $val, converted
   */
  protected function normalizeVal($val){
  
    if(is_numeric($val)){
    
      if(ctype_digit((string)$val)){
      
        $val = (int)$val;
      
      }else{
      
        $val = (float)$val;
      
      }//if/else
    
    }else{
      
      // convert literal true or false into actual booleans...
      switch(mb_strtoupper($val)){
      
        case 'TRUE':
          $val = true;
          break;
          
        case 'FALSE':
          $val = false;
          break;
      
      }//switch
    
    }//if
  
    return $val;
  
  }//method
  
  /**
   *  make sure any required fields were parsed from the command line
   *  
   *  @param  array $req_argv_map key/val pairs of fields that have to be there, if the val
   *                              is null then the field is required, if something else, then
   *                              that value will be set if the field wasn't found
   */
  protected function assure(array $req_argv_map){
  
    // canary...
    if(empty($req_argv_map)){ return; }//if
    
    foreach($req_argv_map as $name => $default_val){
      
      if(!$this->existsField($name)){
      
        if($default_val === null){
        
          throw new \InvalidArgumentException(
            sprintf(
              '%s was not passed in and is required, you need to pass it in: --%s=<VALUE>',
              $name,
              $name
            )
          );
          
        }else{
        
          $this->setField($name,$default_val);
        
        }///if/else
      }//if
    
    }//foreach
  
  }//method
  
  /**
   *  normalize the $argv before parsing it
   *  
   *  currently, this just makes sure the first argument is stripped if it is just
   *  the file name         
   *
   *  @param  array $argv the arguments to be parsed
   *  @return array $argv without the called filename   
   */
  protected function normalize(array $argv){
  
    // canary...
    if(empty($argv)){ return $argv; }//if
  
    $path = $argv[0];
  
    // do some hackish stuff to decide if the first argv needs to be stripped...
    $bt = debug_backtrace();
    
    // we want to look at the file that started the request...
    end($bt);
    $key = key($bt);
    
    if(!empty($bt[$key]['file'])){
    
      // make sure we're comparing full file paths...
      if(preg_match('#(?<=^|[\\\\/])\.+[\\\\/]#',$path)){
      
        if($realpath = realpath($path)){
          $path = $realpath;
        }//if
        
      }//if
    
      $file_path = $bt[$key]['file'];
      $file_name = basename($bt[$key]['file']);
    
      if(($path == $file_path) || ($path == $file_name)){
        $argv = array_values(array_slice($argv,1));
      }//if

    }//if
  
    return $argv;
  
  }//method

}//class
