<?php
/**
 *  extends the default crawler to add some helpful methods
 *  
 *  this is a companion to the {@link FrameworkClient}  
 *  
 *  @link http://symfony.com/doc/current/book/testing.html
 *  @link http://symfony.com/doc/2.0/components/dom_crawler.html
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 8-23-11
 *  @package test
 *  @subpackage PHPUnit
 ******************************************************************************/
namespace PHPUnit;

use Symfony\Component\DomCrawler\Crawler;

class FrameworkCrawler extends Crawler {

  /**
   *  returns true if the $selector is present
   *  
   *  really, I just got sick of doing $this->filter($selector)->count() > 0 over and over      
   *  
   *  @param string $selector A CSS selector      
   *  @return boolean
   */
  public function has($selector){ return $this->filter($selector)->count() > 0; }//method

  /**
   * get the content of the crawled page
   *
   * @since 2013-3-8
   * @return  string
   */
  public function getContent(){

    $ret_html = '';
    foreach($this as $dom_element){
        $ret_html .= $dom_element->ownerDocument->saveHTML();
    }//foreach

    return $ret_html;

  }//method

  public function __toString(){ return $this->getContent(); }//method

}//class
