<?php
namespace Montage\Test\PHPUnit;

require_once('out_class.php');
require_once(__DIR__.'/../Test.php');

require_once(__DIR__.'/../../../Field/Fieldable.php');
require_once(__DIR__.'/../../../Form/Common.php');
require_once(__DIR__.'/../../../Form/Form.php');
require_once(__DIR__.'/../../../Form/Field/Field.php');
require_once(__DIR__.'/../../../Form/Field/Input.php');
require_once(__DIR__.'/../../../Form/Field/Textarea.php');

use Montage\Form\Form;
use Montage\Form\Field\Input;
use Montage\Form\Field\Textarea;
use out;

class FieldTest extends Test {

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

  protected function populate(){}//method

}//class
