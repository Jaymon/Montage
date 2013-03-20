<?php
/**
 * handles a char
 *
 * I originally added this to create utf-8 chars from unicode codepoints, but I'm sure I'll
 * find other uses for this class at some point and expand it
 *
 * @link  http://www.utf8-chartable.de/unicode-utf8-table.pl?start=9600&number=128
 *
 * @since 2013-3-16
 ******************************************************************************/
class Char {

  /**
   * the raw char that was passed into the constructor
   *
   * @var string
   */
  protected $char = '';

  public function __construct($char){
    $this->char = $char;
  }//method

  /**
   * allow fluid interface
   *
   * @param string  $char
   * @return  Char
   */
  public static function create($char){ return new static($char); }//method

    /**
     * if you passed in a Unicode code point like U+2580, this will render it as the utf-8 char
     *
     * @return  string  a utf-8 encoded char
     */
  public function unicodeCodepointToUtf8(){
    return html_entity_decode(preg_replace("/U\+([0-9A-F]{4,5})/", "&#x\\1;", $this->char), ENT_NOQUOTES, 'UTF-8');
  }//method

  /**
   * print out a utf8 string of this char
   *
   * TODO - in the future, this should check what kind of char we have (eg, if it is in the form
   * U+cccc it is a unicode codepoint) and print out the actual utf8 representation of the char, that
   * will allow us to not just pass unicode codepoints, but hex, or whatever and get the correct char
   * printed out
   *
   * @return  string
   */
  public function __toString(){
    return $this->unicodeCodepointToUtf8();
  }//method

}//class

