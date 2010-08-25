<?php
/**
 *  fetch php class
 *  
 *  this class will enable remote calls so projects can access files remotely  
 *  
 *  info:
 *    - {@link http://www.php.net/manual/en/function.fopen.php#73132} has a good discussion about multithreaded fetching
 *    - {@link http://www.useragentstring.com/} is a great place for user agent strings  
 *  
 *  ideas:
 *    - putPath() when I finally get around to adding this method, it would be cool if
 *      you specify a folder it will PUT every file in the folder, if a file then it
 *      will just PUT that one file    
 *  
 *  bugs:
 *    - possible bug: when instantiating and using this class in a for loop of 10,000
 *      iterations the headers will eventually stop being retrieved, this could be a bug
 *      in this class, or my test server is just getting overwhelmed with that many request
 *      hitting it so fast, or there are too many resources getting opened. 
 *      Putting a usleep(1); in the for loop seems to fix it though  
 *  
 *  @version 2.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 5-14-10
 *  @project fetch
 ******************************************************************************/      
class fetch extends fetch_base {

  /**#@+
   *  generic http methods, though others can be passed into {@link setMethod()}
   *  @var  string
   */
  const METHOD_GET = 'GET';
  const METHOD_POST = 'POST';
  const METHOD_HEAD = 'HEAD';
  const METHOD_PUT = 'PUT';
  const METHOD_DELETE = 'DELETE';
  /**#@-*/

  /**
   *  true if {@link start()} has been called
   *  @var  boolean   
   */
  protected $is_started = false;

  /**
   *  hold the multi-curl handler
   *  @var  object
   */
  protected $mcurl_handler = null;

  /**
   *  initialize the instance
   *  
   *  @param  string  $url  the url this fetch instance will use
   */
  final public function __construct($url = ''){
  
    $this->mcurl_handler = curl_multi_init();
  
    $this->setUrl($url);
    
    // some sites only accept your request if your request looks legit, so send a generic default user agent...
    // from: http://www.php.net/manual/en/function.curl-setopt.php#11470
    $this->setUserAgent('Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0) fetch lib');
  
    $this->setMethod(self::METHOD_GET);
  
  }//method

  /**
   *  this will start fetching a url without blocking and then return focus back to 
   *  the application, in order to get the response you'll want to call {@link stop()}
   *  as this function will just return true to let you know it started ok
   *  
   *  it's handy to use this to begin getting a url and then you can do other things
   *  while you are waiting for the websites to finish. Use {@link get()} if you 
   *  don't mind waiting as that will block until the response is received   
   *  
   *  @return boolean
   */
  public function start(){
  
    // canary...
    if(!empty($this->is_started)){
      throw new BadMethodCallException('start() has already been called, no sense in doing it again');
    }//if
    if(empty($this->field_map['url'])){
      throw new InvalidArgumentException('no url has been set, use setUrl() to set one.');
    }//if
    if(empty($this->field_map['method'])){
      throw new InvalidArgumentException('no method has been set, use setMethod() to set one.');
    }//if
    
    // start your engines...
    $curl_handler = curl_init();
    
    // casting to a string turns the resource into a string like: "Resource id #12" that is unique... 
    $curl_key = $this->getCurlKey($curl_handler);
    $this->field_map[$curl_key] = array();
    
    // ignore certificates on an https request...
    // http://www.php.net/manual/en/function.curl-setopt.php#44349
    curl_setopt($curl_handler, CURLOPT_SSL_VERIFYPEER, false);
    
    // follow any redirects encountered...
    curl_setopt($curl_handler, CURLOPT_FOLLOWLOCATION, 1);
    
    $url = $this->field_map['url'];
    $method = $this->field_map['method'];
    
    $encoded_fields = empty($this->field_map['fields'])
      ? array()
      : $this->encodeFields($this->field_map['fields']);
    
    if($this->isMethod(self::METHOD_POST)){
    
      $url = $this->resolveUrl($url);
      curl_setopt($curl_handler, CURLOPT_POST, 1);
      
      if(!empty($encoded_fields)){
        curl_setopt($curl_handler, CURLOPT_POSTFIELDS, $encoded_fields);
      }//if
    
    }else if($this->isMethod(self::METHOD_PUT)){
    
      // http://www.php.net/manual/en/function.curl-setopt.php#96056
    
      $url = $this->resolveUrl($url);
      
      if(!empty($encoded_fields)){
        curl_setopt($curl_handler, CURLOPT_POSTFIELDS, $encoded_fields);
      }//if
    
      // if there is a file then use the normal put, else user custom request set to 'PUT'
      // since the curl put requires a file
      
      ///curl_setopt($curl_handler, CURLOPT_PUT, 1);
      ///curl_setopt($curl_handler, CURLOPT_INFILE, ...);
      ///curl_setopt($curl_handler, CURLOPT_INFILESIZE, ...);
    
    }else if($this->isMethod(self::METHOD_HEAD)){
    
      $url = $this->resolveUrl($url,$encoded_fields);
      curl_setopt($curl_handler, CURLOPT_NOBODY, true);
    
    }else{
    
      if(!$this->isMethod(self::METHOD_GET)){
        // WARNING: there is no error checking on this call, so if you do 'HAPPY' that is what will be set...
        curl_setopt($curl_handler, CURLOPT_CUSTOMREQUEST,$method);
      }//if
    
      $url = $this->resolveUrl($url,$encoded_fields);
    
    }//if/else
    
    // set the url...
    curl_setopt($curl_handler, CURLOPT_URL, $url);
    
    if(!empty($this->field_map['cookie_fields'])){
      $encoded_cookie_fields = $this->encodeFields($this->field_map['cookie_fields'],';');
      curl_setopt($curl_handler, CURLOPT_COOKIE, $encoded_cookie_fields);
    }//if

    if(!empty($this->field_map['headers'])){
      
      // http://www.php.net/manual/en/function.curl-setopt.php#26797
      // http://www.php.net/manual/en/function.curl-setopt.php#20410
      
      // custom headers have to be a list of full headers (ie, Array("Content-Type: text/xml"))
      // to be valid...
      $header_list = array();
      foreach($this->field_map['headers'] as $name => $val){
        $header_list[] = sprintf('%s: %s',$name,$val);
      }//foreach
    
      curl_setopt($curl_handler, CURLOPT_HTTPHEADER, $header_list);
      
    }//if
    
    // return request headers...
    // http://stackoverflow.com/questions/866946/
    curl_setopt($curl_handler, CURLINFO_HEADER_OUT, true);
    
    curl_setopt($curl_handler, CURLOPT_HEADERFUNCTION,array($this,'appendHeader'));
    $this->field_map[$curl_key]['headers'] = array();
    
    if(empty($this->field_map['response_path'])){
      
      // return the result...
      curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, true);
      
    }else{

      // we're writing to a file...

      $this->field_map[$curl_key]['response_handler'] = fopen($this->field_map['response_path'], 'wb');
  
      if($this->field_map[$curl_key]['response_handler'] === false){
  
        throw new UnexpectedValueException(
          sprintf('Could not open a file to write to at path: %s',$this->field_map['response_path'])
        );
  
      }else{
  
        curl_setopt($curl_handler, CURLOPT_FILE, $this->field_map[$curl_key]['response_handler']);
        
      }//if/else
      
    }//if/else
    
    // sets the referrer field...
    if(!empty($this->field_map['referer'])){
      curl_setopt($curl_handler, CURLOPT_REFERER, $this->field_map['referer']);
    }//if
    
    // set the timeout in seconds...
    if(!empty($this->field_map['timeout_global'])){
      curl_setopt($curl_handler, CURLOPT_TIMEOUT, $this->field_map['timeout_global']);
    }//if
    
    // set how long to try and connect...
    if(!empty($this->field_map['timeout_connection'])){
      curl_setopt($curl_handler, CURLOPT_CONNECTTIMEOUT, $this->field_map['timeout_connection']);
    }//if
    
    // set the User-Agent string that will identify the call...
    if(!empty($this->field_map['user_agent'])){
      curl_setopt($curl_handler, CURLOPT_USERAGENT, $this->field_map['user_agent']);
    }//if
    
    // set a username and password if there is one...
    if(!empty($this->field_map['username']) && isset($this->field_map['password'])){
      ///curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY); // this caused problems with twitter
      curl_setopt($curl_handler, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt(
        $curl_handler, 
        CURLOPT_USERPWD, 
        sprintf('%s:%s',$this->field_map['username'],$this->field_map['password'])
      );
    }//if
    
    // start the request...
    curl_multi_add_handle($this->mcurl_handler, $curl_handler);
    
    // execute the handler...
    $active_curl_count = 0;
    do{
      $result_int = curl_multi_exec($this->mcurl_handler, $active_curl_count);
    }while($result_int === CURLM_CALL_MULTI_PERFORM);
  
    $this->is_started = true;
  
    return true;
  
  }//method
  
  /**
   *  called after {@link start()} to get the response that was started
   *  
   *  @note this is actually set up to handle multiple urls but I can't decide if
   *  it is worth it, since you could do multiple urls by just instantiating more
   *  than one instance of this class      
   *      
   *  @return fetch_response  the response object containing what was downloaded
   *                          from the url
   */
  public function stop(){
  
    // canary...
    if(empty($this->is_started)){
      throw new BadMethodCallException('you shouldn\'t call this method without first calling start()');
    }//if
  
    $ret_instance = null;
  
    $current_curl_count = 0;
    do{
    
      // we do this again to get how many active curl transfers there are...
      $response = curl_multi_exec($this->mcurl_handler, $active_curl_count);
    
      if($active_curl_count !== $current_curl_count){
      
        // go through and process each curl as it finishes...
        while($done_map = curl_multi_info_read($this->mcurl_handler)){
        
          // canary, make sure it wasn't a failed fetch...
          if(curl_errno($done_map['handle'])){
            throw new RuntimeException(
              sprintf('fetch failed with %s',curl_error($done_map['handle']))
            );
          }//if
        
          // get the response to pass to the response object...
          $response = curl_multi_getcontent($done_map['handle']);
          $response_info = curl_getinfo($done_map['handle']);
          $curl_key = $this->getCurlKey($done_map['handle']);
          
          $ret_instance = new fetch_response(
            $response,
            $response_info,
            $this->field_map[$curl_key]['headers']
          );
          
          // make the response headers available...
          if(isset($response_info['request_header'])){
          
            $this->field_map['headers'] = $this->parseHeaders($response_info['request_header']);
          
            // the first header is the request...
            $this->field_map['request'] = $this->field_map['headers']['http'];
          
          }//if
          
          // if we were reading into a file, close the file pointer for this particular handle...
          if(isset($this->field_map[$curl_key]['response_handler'])){
            
            fclose($this->field_map[$curl_key]['response_handler']);
            unset($this->field_map[$curl_key]['response_handler']);
            
            $path = $this->getPath();
            
            if($ret_instance->failed()){
            
              // delete the file if the request failed...
              if(file_exists($path)){ unlink($path); }//if
              clearstatcache();
            
            }else{
            
              // set the path so the return instance has it...
              $ret_instance->setPath($path);
            
            }//if/else
            
          }//if
          
          // close the connection and get rid of the individual curl handler...
          curl_multi_remove_handle($this->mcurl_handler, $done_map['handle']);
          curl_close($done_map['handle']);
          
        }//while
        
        $current_curl_count = $active_curl_count;
        
      }//if
      
    }while($active_curl_count > 0);
  
    return $ret_instance;
  
  }//method
  
  /**
   *  basically combines {@link start()} and {@link stop()} into one call
   *  
   *  this is handy if you don't want the code to do something else but wait for
   *  the response         
   *
   *  @return fetch_response
   */
  public function get(){
  
    $this->start();
    return $this->stop();
  
  }//method

  /**
   *  set the url that will be fetched
   *  
   *  @param  string  $val  the url      
   */        
  public function setUrl($val){
    $this->field_map['url'] = (string)$val;
  }//method
  
  /**
   *  set the method that will be used to fetch the url, defauls to self::METHOD_GET
   *  
   *  @param  string  $val  usually one of the METHOD_* constants
   */        
  public function setMethod($val){
    $this->field_map['method'] = mb_strtoupper((string)$val);
  }//method
  
  /**
   *  set the timesouts, basically how long fetch will attempt to get the url
   *  
   *  pass in zero on either to use built in defaults (no idea what those are but most
   *  likely they are unlimited)
   *      
   *  @param  integer $val  how many total seconds the fetch will last
   *  @param  integer $val  how long you would like to spend just on connecting
   *                        eg, if you pass in 4 then if it doesn't connect in 4
   *                        seconds it will give up         
   */
  public function setTimeout($timeout_global,$timeout_connection = 0){
    $this->field_map['timeout_global'] = (int)$timeout_global;
    $this->field_map['timeout_connection'] = (int)$timeout_connection;
  }//method
  
  /**
   *  GET/POST vars to send with the url
   *
   *  @param  array|string  $val  key/value mapping of vars, pass in just a string 
   *                              if you want to post raw stuff
   */
  public function setFields($val){
    $this->field_map['fields'] = $val;
  }//method
  
  /**
   *  GET/POST vars to send with the url
   *
   *  @param  array|string  $val  key/value mapping of vars, pass in just a string 
   *                              if you want to post raw stuff
   */
  public function setCookieFields($val){
    $this->field_map['cookie_fields'] = $val;
  }//method
  
  /**
   *  set a specific referrer
   *  
   *  @param  string  $val  the url you want the request to look like it came from
   */
  public function setReferer($val){
    $this->field_map['referer'] = $val;
  }//method
  
  /**
   *  set a specific browser user agent string, this is handy when a site is blocking
   *  certain generic user agents   
   *  
   *  @param  string  $val  the user agent you want to send
   */
  public function setUserAgent($val){
    $this->field_map['user_agent'] = $val;
  }//method
  
  /**
   *  if you want the url to be authenticated with a username and password
   *
   *  @param  string  $username
   *  @param  string  $password      
   */
  public function setAuth($username,$password){
    $this->field_map['username'] = (string)$username;
    $this->field_map['password'] = (string)$password;
  }//method
  
  /**
   *  quick way to set a header
   *  
   *  @since  6-2-10
   *  @see  setHeaders()
   *  @param  string  $name the header name (eg, Content-Type)
   *  @param  string  $val  the value of the header
   */
  public function setHeader($name,$val){
  
    // canary...
    if(empty($name)){ throw new UnexpectedValueException('$name cannot be empty'); }//if
  
    $this->setHeaders(array($name => $val));
  
  }//method
  
  /**
   *  set any custom headers
   *  
   *  @see  http://www.php.net/manual/en/function.curl-setopt.php#26797
   *  @see  http://www.php.net/manual/en/function.curl-setopt.php#20410  
   *      
   *  @param  string|array  $val  either an array of headers or one header
   */        
  public function setHeaders($val){
  
    // canary...
    if(!is_array($val)){ $val = array($val); }//if
    if(!isset($this->field_map['headers'])){
      $this->field_map['headers'] = array();
    }//if
  
    $header_list = array();
    foreach($val as $header_name => $header_val){
    
      $header_name = (string)$header_name;
      $header_key = (string)$header_key;
    
      // header is complete, eg: "header_name: header_val" so break it up...
      if(ctype_digit($header_key)){
      
        $header_bits = explode(':',$header_key,2);
        $header_name = trim($header_bits[0]);
        $header_val = empty($header_bits[1]) ? '' : trim($header_bits[1]);
        
      }//if
      
      $this->field_map['headers'][$header_name] = $header_val;
      
    }//for
  
  }//method
  
  /**
   *  test if a given method is what you need it to be.
   *     
   *  @param  string  $method
   *  @return boolean true if $method is equal to the method set in {@link setMethod()}
   */
  protected function isMethod($method){
    return $this->field_map['method'] === mb_strtoupper($method);
  }//method
  
  /**
   *  resolve and build a url
   *
   *  since the url isn't empty, we want to make sure the url is valid and that the
   *  encoded fields are added to the end of the url   
   *  
   *  @param  string  $url  the url to be checked, and built
   *  @param  string  $encoded_fields the variables in the ?...&... form from {@link encodeFields()}
   *  
   *  @return string  the full url
   */
  protected function resolveUrl($url,$encoded_fields = ''){
    
    $url_map = parse_url($url);
    
    // canary, couldn't even parse the url so something is screwy...
    if(!is_array($url_map)){
      throw new UnexpectedValueException(
        sprintf('Could not parse the $url (%s)',$url)
      );
    }//if
    
    // make sure the scheme does exist (http, ftp, etc.), default to http if it doesn't...
    if(empty($url_map['scheme'])){ $url = sprintf('http://%s',$url); }//method
    
    // add the encoded vars if this is a get query...
    if(!empty($encoded_fields)){
    
      $begin_char = empty($url_bits['query']) ? '?' : '&';
    
      $url = sprintf(
        '%s%s%s',
        $url,
        empty($url_bits['query']) ? '?' : '&',
        $encoded_fields
      );
      
    }//if
    
    return $url;
  
  }//method
  
  /**
   *  encode and return all the GET/POST vars to be included with the url
   *     
   *  raw fields (a string of text) can be read on the other side using: 'php://input'
   *      
   *  @param  array|string $fields
   *  @return string  a string usually in the form of key=val&key2=val2...
   */
  protected function encodeFields($fields,$separator = '&'){
  
    // canary...
    if(empty($fields)){ return ''; }//if
  
    return is_array($fields) ? http_build_query($fields,'',$separator) : urlencode($fields);
    
  }//method
  
  /**
   *  casting to a string turns the resource into a string like: "Resource id #12" that is unique.
   *  
   *  having a unique string to identify a certain resource is handy for when your juggling
   *  multiple curl handlers         
   *  
   *  @param  resource  $curl_handler
   *  @return string
   */
  protected function getCurlKey($curl_handler){
    return (string)$curl_handler;
  }//method
  
  /**
   *  append the response header to the $curl_handler's unique key
   *  
   *  @param  resource  $curl_handler the curl instance handling the request
   *  @param  string  $header the response header         
   *  @return integer the total number of bytes seen
   */
  protected function appendHeader($curl_handler,$header){
    
    $curl_key = $this->getCurlKey($curl_handler);
    
    list($header_name,$header_val) = $this->parseHeader($header);
    
    if(empty($header_name)){
    
      // this is the method, code, and message...
      if(empty($this->field_map[$curl_key]['headers'])){
        $this->field_map[$curl_key]['headers']['http'] = trim($header);
      }//if
    
    }else{
      
      if(isset($this->field_map[$curl_key]['headers'][$header_name])){
            
        $this->field_map[$curl_key]['headers'][$header_name] = (array)$this->field_map[$curl_key]['headers'][$header_name];
        $this->field_map[$curl_key]['headers'][$header_name][] = $header_val;
        
      }else{
      
        $this->field_map[$curl_key]['headers'][$header_name] = $header_val;
      
      }//if/else
      
    }//if/else

    return mb_strlen($header);
    
  }//method
  
  public function __destruct(){
  
    // close the multi curl connection...
    curl_multi_close($this->mcurl_handler);
  
  }//method
  
}//class
