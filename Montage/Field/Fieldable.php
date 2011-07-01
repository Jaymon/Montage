<?php
/**
 *  The field is just an easy way to store key/value pairs
 *  
 *  the reason this interface exists is to make sure any class can mimick the field
 *  behavior even if it can't extend the Field class 
 *  
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-19-10
 *  @package montage 
 ******************************************************************************/
namespace Montage\Field;

use Montage\Field\SetFieldable;
use Montage\Field\GetFieldable;

interface Fieldable extends SetFieldable,GetFieldable {}//class     
