<?php

$base = realpath(__DIR__.'/../src');
require_once($base.'/Str.php');

class StrTest extends \PHPUnit_Framework_TestCase {

  /**
   *  @since  11-7-11
   */
  public function testGetWords(){
  
    $test_list = array();
    $test_list['email address'] = array(
      'in' => 'happy@example.com',
      'out' => array('happy@example.com')
    );
    $test_list['punct character before word'] = array(
      'in' => 'happy @example',
      'out' => array('happy','example')
    );
    $test_list['punct character after word'] = array(
      'in' => 'happy example.',
      'out' => array('happy','example')
    );
    $test_list['multiple punct characters'] = array(
      'in' => '(happy example.)',
      'out' => array('happy','example')
    );
    $test_list['multiple punct characters in middle of string'] = array(
      'in' => 'happy (this is the example.) and the end',
      'out' => array('happy','this','is','the','example','and','the','end')
    );
  
    foreach($test_list as $msg => $test_map){
    
      $s = new Str($test_map['in']);
      $this->assertEquals($test_map['out'],$s->getWords(),(string)$msg);
    
    }//foreach
  
  }//method

  public function testPathify(){
  
    $s = new Str('foo bar blah test string');
    $sp = $s->pathify();
    $this->assertEquals('foo_bar_blah_test_string',(string)$sp);
    
    $sp = $s->pathify(5);
    $this->assertEquals('foo',(string)$sp);
  
  }//method
  
  public function testLinks(){
  
    $s = new Str('http://foo.com/blah_blah http://foo.com/blah_blah/');
    
    $this->assertEquals(2,count($s->getLinks()));
  
  }//method
  
  public function testHighlight(){
  
    ///\out::e(ctype_punct(ord('%u0104')));
    ///\out::e(ctype_punct(chr(132)));
    ///\out::e(chr(132));
    
    $s = new Str('foo bar blah test string');
    $sn = $s->highlight('bar');
    
    $regex = '#class=\"highlight\"#i';
    $matched = array();
    preg_match_all($regex,$sn,$matched);
    
    $this->assertEquals(1,count($matched[0]));
    
    $sn = $s->highlight('bar. test');
    preg_match_all($regex,$sn,$matched);
    
    $this->assertEquals(2,count($matched[0]));
  
  }//method
  
  public function testAccess(){
  
    $s = new Str('foo bar baz che');
    
    $sn = $s(0,3);
    $this->assertEquals('foo',(string)$sn);
    
    $sn = $s(-3);
    $this->assertEquals('c',(string)$sn);
  
    $sn = $s(-3,null);
    $this->assertEquals('che',(string)$sn);
  
  }//method
  
  public function testLinkify(){
  
    $s = new Str(
      'Test data for the URL-matching regex pattern presented here:

      http://daringfireball.net/2010/07/improved_regex_for_matching_urls
      
      
      Matches the right thing in the following lines:
      
      	http://foo.com/blah_blah
      	http://foo.com/blah_blah/
      	(Something like http://foo.com/blah_blah)
      	http://foo.com/blah_blah_(wikipedia)
      	http://foo.com/more_(than)_one_(parens)
      	(Something like http://foo.com/blah_blah_(wikipedia))
      	http://foo.com/blah_(wikipedia)#cite-1
      	http://foo.com/blah_(wikipedia)_blah#cite-1
      	http://foo.com/unicode_(?)_in_parens
      	http://foo.com/(something)?after=parens
      	http://foo.com/blah_blah.
      	http://foo.com/blah_blah/.
      	<http://foo.com/blah_blah>
      	<http://foo.com/blah_blah/>
      	http://foo.com/blah_blah,
      	http://www.extinguishedscholar.com/wpglob/?p=364.
      	http://?df.ws/1234
      	rdar://1234
      	rdar:/1234
      	x-yojimbo-item://6303E4C1-6A6E-45A6-AB9D-3A908F59AE0E
      	message://%3c330e7f840905021726r6a4ba78dkf1fd71420c1bf6ff@mail.gmail.com%3e
      	http://?.ws/?
      	www.c.ws/?
      	<tag>http://example.com</tag>
      	Just a www.example.com link.
      	http://example.com/something?with,commas,in,url, but not at end
      	What about <mailto:gruber@daringfireball.net?subject=TEST> (including brokets).
      	mailto:name@example.com
      	bit.ly/foo
      	“is.gd/foo/”
      	WWW.EXAMPLE.COM
      	http://www.asianewsphoto.com/(S(neugxif4twuizg551ywh3f55))/Web_ENG/View_DetailPhoto.aspx?PicId=752
      	http://www.asianewsphoto.com/(S(neugxif4twuizg551ywh3f55))
      	http://lcweb2.loc.gov/cgi-bin/query/h?pp/horyd:@field(NUMBER+@band(thc+5a46634))
      '   
    );
  
    $ns = $s->linkify();
  
  }//method
  
}//class
