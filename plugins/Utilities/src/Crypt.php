<?php

/**
 *  crypt php class
 *
 *  generic encrypt decrypt class
 *  
 *  9-05-07 - initial writing of the class
 *  9-09-07 - changed it so it base64 encodes all the encryption to make it easy to
 *    transport in db and file, however, this will make the encrypted text around 33%
 *    larger   
 *  12-6-09 - huge page slowdowns because I was using the secure random to create the iv, 
 *    see the comments: http://www.php.net/manual/en/function.mcrypt-create-iv.php  
 *    if mcrypt_create_iv($iv_size,MCRYPT_RAND); ends up slowing down, then use
 *    the iv() method I added to the class
 *    
 *  @link http://mcrypt.hellug.gr/lib/mcrypt.3.html
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 9-5-07
 *  @package Utilities
 ******************************************************************************/      
class Crypt {
  
  /**
   *  default encryption algorithm
   *  
   *  @var  string      
   */
  const CIPHER_ALGO = MCRYPT_RIJNDAEL_256;
  
  /**
   *  default encryption mode
   *  
   *  @var  string
   */
  const CIPHER_MODE = MCRYPT_MODE_CBC;
  
  /**
   *  passed in encryption algorithm
   *  
   *  @var  string      
   */
  protected $cipher_algo = self::CIPHER_ALGO;
  
  /**
   *  passed in encryption mode
   *  
   *  @var  string      
   */
  protected $cipher_mode = self::CIPHER_MODE;
  
  /**
   *  create instance of the class
   *     
   *  @param  string  $cipher_algo  the algorithm you want to use to encrypt/decrypt
   *  @param  string  $cipher_mode  the mode you want to use to encrypt/decrypt
   */
  public function __construct($cipher_algo = self::CIPHER_ALGO,$cipher_mode = self::CIPHER_MODE){
  
    $this->cipher_algo = $cipher_algo;
    $this->cipher_mode = $cipher_mode;
  
  }//method
  
  /**
   *  encrypt some text
   *  
   *  @param  string  $key  the password you want to use
   *  @param  string  $text the text you want to encrypt
   *  @return string  the encrypted text
   */
  public function encrypt($key,$text){
  
    return $this->_encrypt(
      $key,
      $text,
      $this->cipher_algo,
      $this->cipher_mode
    );

  }//method
  
  /**
   *  decrypt text encrypted with {@link encrypt()}
   *  
   *  @param  string  $key  the password used to encrypt the text
   *  @param  string  $cipher_text the encrypted text
   *  @return string  the decrypted text
   */
  public function decrypt($key,$cipher_text){
  
    return $this->_decrypt(
      $key,
      $cipher_text,
      $this->cipher_algo,
      $this->cipher_mode
    );
  
  }//method
  
  /**
   *  prints out lots of information about the algorithm with different modes
   *  
   *  @param  string  $text what you want the sample text to be
   *  @return string
   */
  public function info($text = ''){
  
    $ret_str = '';
    $key = 'this is the key';
    if(empty($text)){
      $text = '1234567890';
    }//if
    $iv = '';
  
    foreach(mcrypt_list_algorithms() as $algo){

      foreach(mcrypt_list_modes() as $mode){
      
        $iv_size = @mcrypt_get_iv_size($algo,$mode);
        
        $key_size = @mcrypt_get_key_size($algo,$mode);
        if(!empty($key_size)){
          $key = mb_substr($key,0,$key_size);
        }//if
        
        if($iv_size !== false){
          
          $ciphertext = $this->_encrypt($key,$text,$algo,$mode);
          $plaintext = $this->_decrypt($key,$ciphertext,$algo,$mode);
          
          $ret_str .= sprintf(
            "- %s with mode %s".PHP_EOL
            ."\tIV size: %s".PHP_EOL
            ."\tmax key size: %s".PHP_EOL
            ."\tciphertext (%s): %s".PHP_EOL
            ."\ttext (%s): %s".PHP_EOL.PHP_EOL,
            $algo,
            $mode,
            $iv_size,
            $key_size,
            strlen($ciphertext),
            $ciphertext,
            strlen($plaintext),
            $plaintext
          );
            
        }else{
        
          $ret_str .= sprintf("- %s with mode %s failed initialization%s%s",$algo,$mode,PHP_EOL,PHP_EOL);
        
        }//if/else
      
      }//foreach
    
    }//foreach
    
    return $ret_str;
  
  }//method
  
  /**
   *  true if the $text is encrypted
   *  
   *  this is actually pretty basic and will fail if you are encrypting a huge block
   *  of text that doesn't have any spaces in it         
   *
   */        
  protected function isEncrypted($text){ return ctype_graph($text); }//method
  
  /**
   *  encrypt some text
   *  
   *  @param  string  $key  the password you want to use
   *  @param  string  $text the text you want to encrypt
   *  @return string  the encrypted text
   */
  protected function _encrypt($key,$text,$algo,$mode){
  
    // canary...
    if(empty($text)){ throw new UnexpectedValueException('$text is empty'); }//if
    if(empty($key)){ throw new UnexpectedValueException('$key is empty'); }//if

    // create the IV...
    $iv = '';
    $iv_size = mcrypt_get_iv_size($algo,$mode);
    if($iv_size > 0){
      
      // srand(); // needed before 5.3
      $iv = mcrypt_create_iv($iv_size,MCRYPT_RAND);
      
    }//if
    
    $key_size = mcrypt_get_key_size($algo,$mode);
    $key = mb_substr($key,0,$key_size);

    $ciphertext = mcrypt_encrypt($algo,$key,$text,$mode,$iv);

    // attach the real IV onto the end of the endcrypted text
    $ciphertext .= $iv;
    $ciphertext = base64_encode($ciphertext); // for safe db and file storage
    
    return $ciphertext;
    
  }//method
  
  /**
   *  decrypt text encrypted with {@link encrypt()}
   *  
   *  @param  string  $key  the password used to encrypt the text
   *  @param  string  $ciphertext the encrypted text
   *  @return string  the decrypted text
   */
  protected function _decrypt($key,$ciphertext,$algo,$mode){
  
    // canary...
    if(empty($ciphertext)){ return ''; }//if
    if(empty($key)){ throw new UnexpectedValueException('$key is empty'); }//if
  
    $ciphertext = base64_decode($ciphertext); // convert the encrypted data back to binary form
  
    // iv should be appended on the end, so let's retrieve it...
    $iv = '';
    $iv_size = mcrypt_get_iv_size($algo,$mode);
    if($iv_size > 0){
    
      // NOTE 9-10-07: don't use the mb_substr functions as that messed with getting
      //  the iv from the end of the ciphertext
      $iv = substr($ciphertext,(0-$iv_size));
      
      $ciphertext = substr($ciphertext,0,(0-$iv_size));
      
    }//if
    
    // you have to trim the decrypted text because I think it null pads to work with CBC block mode...
    $text = trim(mcrypt_decrypt($algo,$key,$ciphertext,$mode,$iv));
  
    return $text;
    
  }//method
  
  /**
   *  in case you get huge slowdowns using mcrypt_create_iv() you can use this function
   *  
   *  @link http://www.php.net/manual/en/function.mcrypt-create-iv.php#54925
   *      
   *  @param  integer $size the iv size
   *  @return string  a random iv         
   */
  protected function iv($size){
    
    $iv = '';
    for($i = 0; $i < $size; $i++){
      $iv .= chr(rand(0,255));
    }//for
    
    return $iv;
    
  }//method
  
}//class
