<?php
namespace Montage\Test\PHPUnit;

use PHPUnit\TestCase;

use Montage\Form\Form;
use Montage\Form\Field\Input;
use Montage\Form\Field\Textarea;

class FormTest extends TestCase {

  public function testDocBlockPopulate(){
  
    $f = new TestForm();
    
    ///\out::i($f);
  
  
  }//method

  /**
   *  tests some form stuff
   */
  public function testAddSimpleFieldToForm(){
  
    $form = new TestForm();
    
    $field = new Input('test','blah');
    
    $form->setField($field);
    
    $this->assertSame('blah',$form->getField('test')->getVal());
    
    ///out::e($form);
    
  
  }//method
  
  /**
   *  tests some form stuff
   */
  public function testAddArrayFieldToForm(){
  
    $form = new TestForm();
    
    $field = new Input('test[]','foo');
    $form->setField($field);
    
    $field = new Input('test[]','bar');
    $form->setField($field);
    
    $this->assertSame(2,count($form->getField('test')));
    
    $field = new Input('test[che]','che');
    $form->setField($field);
    
    $fields = $form->getField('test');
    foreach(array(0,1,'che') as $key){
      $this->assertArrayHasKey($key,$fields);
    }//foreach
  
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
