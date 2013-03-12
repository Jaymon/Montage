<?php
/**
 *  allows you to mimic a framework http request 
 *  
 *  @link http://symfony.com/doc/current/book/testing.html
 *  
 *  @version 0.2
 *  @author Jay Marcyes
 *  @since 7-28-11
 *  @package test
 *  @subpackage PHPUnit
 ******************************************************************************/
namespace PHPUnit;

use Symfony\Component\BrowserKit\Client;
use Symfony\Component\BrowserKit\Request as BrowserKitRequest;
use Symfony\Component\BrowserKit\Response as BrowserKitResponse;
use Symfony\Component\HttpFoundation\Cookie;

use PHPUnit\FrameworkCrawler;
use Montage\Framework;

class FrameworkClient extends Client {
  
  /**
   *  holds the Montage framework instance this client will use
   *
   *  @var  \Montage\Framework   
   */
  protected $framework = null;
  
  /**
   *  hold any followed redirects
   *  
   *  @var  array
   */
  protected $redirect_list = array();
  
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
   *  print out the returned response
   *
   *  @since  11-1-11
   *  @return string      
   */
  public function __toString(){
  
    return $this->getResponse()->getContent();
  
  }//method
  
  /**
   *  get the redirect history of this client
   *  
   *  @since  8-23-11      
   *  @return array
   */
  public function getRedirectHistory(){
  
    return $this->redirect_list;
  
  }//method
  
  /**
   *  override to allow redirect history recording
   *     
   *  @see  parent::followRedirect
   */
  public function followRedirect(){
  
    if(!empty($this->redirect)){
    
      $this->redirect_list[] = $this->redirect;
    
    }//if
  
    return parent::followRedirect();
  
  }//method
  
  /**
   * Creates a crawler.
   *
   * @param string $uri A uri
   * @param string $content Content for the crawler to use
   * @param string $type Content type
   *
   * @return Crawler
   */
  protected function createCrawlerFromContent($uri,$content,$type){

    $crawler = new FrameworkCrawler(null, $uri);
    $crawler->addContent($content, $type);

    return $crawler;
      
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
    $framework_request_class_name = $container->getClassName('\\montage\\Request\\Requestable');
    
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
    
    if($cookies = $response->headers->getCookies()){
      
      ///\out::e($cookies);
      
      /*
      $cookies = array();
      foreach($response->headers->getCookies() as $cookie){
          
          $cookies[] = new Cookie(
            $cookie->getName(),
            $cookie->getValue(),
            $cookie->getExpiresTime(),
            $cookie->getPath(),
            $cookie->getDomain(),
            $cookie->isSecure(),
            $cookie->isHttpOnly()
          );
          
      }//foreach */
      
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

    // build a session before clearning the framework
    $container = $this->framework->getContainer();
    // create a session that doesn't actually do anything
    // http://symfony.com/doc/master/components/http_foundation/session_testing.html
    $session_storage = $container->createInstance(
      '\\Symfony\\Component\\HttpFoundation\\Session\\Storage\\MockArraySessionStorage'
    );
    $session = $container->createInstance('\\Montage\Session', array($session_storage));
    
    $this->framework->reset();
    $container = $this->framework->getContainer();
    $container->setInstance('request',$request);
    $container->setInstance(get_class($session), $session);

    // actually handle the request, capture the output since handle usually echoes to the screen...
    ob_start();
    
      $this->framework->handle();
      $output = ob_get_contents();
    
    ob_end_clean();
    
    // set the captured content into the response object...
    $response = $container->getResponse();
    $response->setContent($output);
    
    return $response;
  
  }//method
  
}//class
