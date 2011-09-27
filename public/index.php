<?php

include_once('out_class.php');

$bread = 'O God, the Eternal Father, we ask thee in the name of thy Son, Jesus Christ, 
to bless and sanctify this bread to the souls of all those who partake of it, 
that they may eat in remembrance of the body of thy Son, and witness unto thee, 
O God, the Eternal Father, that they are willing to take upon them the name of thy Son, 
and always remember him and keep his commandments which he has given them; 
that they may always have his Spirit to be with them. Amen.';

$water = 'O God, the Eternal Father, we ask thee in the name of thy Son, Jesus Christ, 
to bless and sanctify this water to the souls of all those who drink of it, that 
they may do it in remembrance of the blood of thy Son, which was shed for them; 
that they may witness unto thee, O God, the Eternal Father, that they do always 
remember him, that they may have his Spirit to be with them. Amen.';

$b = preg_split('#[,.]?\s+#',$bread);
$w = preg_split('#[,.]?\s+#',$water);

$same = array();
$in_b = array();

foreach($b as $i => $word){

  if($keys = array_keys($w,$word,true)){
  
    if(!isset($same[$word])){
    
      $same[$word] = count($keys);
      
      foreach($keys as $key){

        unset($w[$key]);
        
      }//foreach
    
    }//if
  
  }else{
    
    if(!isset($same[$word])){
    
      $in_b[] = $word;
    
    }//if
  
  }//if/else

}//foreach

echo "Words in both: ",join(', ',array_keys($same));
echo PHP_EOL,PHP_EOL;
echo "Words in bread: ",join(', ',$in_b);
echo PHP_EOL,PHP_EOL;
echo "Words in water: ",join(', ',$w);
echo PHP_EOL,PHP_EOL;

out::e($same);
out::e($in_b,$w);
