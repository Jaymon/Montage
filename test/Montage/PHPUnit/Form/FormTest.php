<?php
namespace Montage\Test\PHPUnit;

use PHPUnit\TestCase;

use Montage\Form\Form;
use Montage\Form\Field\Input;
use Montage\Form\Field\Textarea;

class FormTest extends TestCase {

  public function testDocBlockPopulate(){
  
    $f = new TestForm();
    
    $field = $f->getField('foo');
    $this->assertInstanceOf('Montage\Form\Field\Input',$field);
    $this->assertTrue($field->hasDesc());
    
    $field = $f->getField('bar');
    $this->assertInstanceOf('Montage\Form\Field\Textarea',$field);
    $this->assertTrue($field->hasDesc());
  
  }//method

}//class

class TestForm extends Form {

  /**
   *  Foo - this will be the label
   *   
   *  @var  Input
   */
  protected $foo = null;
  
  /**
   *  Bar - this will be the label
   *   
   *  @var  Textarea
   */
  protected $bar = null;

}//class
