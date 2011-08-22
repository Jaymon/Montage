<?php
/**
 *  allows you to mimic a framework http request 
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 7-28-11
 *  @package test
 *  @subpackage PHPUnit
 ******************************************************************************/
namespace PHPUnit;

use Symfony\Component\BrowserKit\Client;
use Symfony\Component\BrowserKit\Request as BrowserKitRequest;
use Symfony\Component\BrowserKit\Response as BrowserKitResponse;

use Montage\Framework;

class FrameworkClient extends Client {
  
  protected $framework = null;
  
  protected $has_requested = false;
  
  /**
   * Constructor.
   *
   * @param HttpKernelInterface $kernel    An HttpKernel instance
   * @param array               $server    The server parameters (equivalent of $_SERVER)
   * @param History             $history   A History instance to store the browser history
   * @param CookieJar           $cookieJar A CookieJar instance to store the cookies
   */
  public function __construct(Framework $framework,array $server = array(),History $history = null,CookieJar $cookieJar = null)
  {
    $this->framework = $framework;
    parent::__construct($server,$history,$cookieJar);
    
  }//method
  
  /**
   * Filters the request.
   *
   * @param Request $request The request to filter
   *
   * @return Request
   */
  protected function filterRequest(BrowserKitRequest $request){
  
    // create a Montage compatible Request instance...
    $container = $this->framework->getContainer();
    $framework_request_class_name = $container->getClassName('montage\Request\Requestable');
    
    $framework_request = call_user_func(
      array($framework_request_class_name,'create'),
      $request->getUri(), 
      $request->getMethod(), 
      (array)$request->getParameters(), 
      (array)$request->getCookies(),
      (array)$request->getFiles(),
      (array)$request->getServer(),
      (array)$request->getContent()
    );
  
    /*
    $params = array(
      'cli' => array(),
      'query' => array(),
      'request' => $request->getParameters(),
      'attributes' => array(),
      'cookies' => $request->getCookies(),
      'files' => $request->getFiles(),
      'server' => $request->getServer(),
      'content' => $request->getContent()
    );
    
    // create a Montage compatible Request instance...
    $container = $this->framework->getContainer();
    $framework_request = $container->createInstance('montage\Request\Requestable',$params);
    */
    
    ///\out::i($framework_request); \out::x();
    
    return $framework_request;
  
  }//method
  
  /**
   * Filters the Response.
   *
   * @param Response $response The Response to filter
   *
   * @return Response
   */
  protected function filterResponse($response)
  {
    $headers = $response->headers->all();
    if ($response->headers->getCookies()) {
      $cookies = array();
      foreach ($response->headers->getCookies() as $cookie) {
          $cookies[] = new DomCookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
      }
      $headers['Set-Cookie'] = implode(', ', $cookies);
    }//if
  
    $bk_response = new BrowserKitResponse(
      $response->getContent(),
      $response->getStatusCode(),
      $headers
    );
  
    return $bk_response;
    
  }//method
  
  /**
   *  Makes a request.
   *
   *  @param Request $request A Request instance
   *  @return Response A Response instance
   */
  protected function doRequest($request){
  
    // avoid shutting down the Kernel if no request has been performed yet
    // WebTestCase::createClient() boots the Kernel but do not handle a request
    /* if($this->has_requested){
    
      $this->framework->reset();
      
    }else{
    
      $this->has_requested = true;
      
    }//if/else */
    
    $this->framework->reset();

    $this->framework->setRequest($request);
    
    ob_start();
    
      $this->framework->handle();
      $output = ob_get_contents();
    
    ob_end_clean();
    
    $response = $this->framework->getResponse();
    $response->setContent($output);
    
    return $response;
  
  }//method
  
}//class
