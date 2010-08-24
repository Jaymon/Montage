<?php

/**
 *  handy functions for dealing with geo (latitude, longitude) stuff
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 8-19-10
 *  @package montage
 *  @subpackage help 
 ******************************************************************************/
class montage_geo {

  /**
   *  get a bounding box for a given $point using $miles
   *  
   *  the bounding box will basically be $miles from $point in any direction
   *  
   *  links that helped me calculate miles to a point:
   *  http://mathforum.org/library/drmath/view/55461.html
   *  http://wiki.answers.com/Q/How_many_miles_are_in_a_degree_of_longitude_or_latitude
   *  http://answers.yahoo.com/question/index?qid=20070911165150AAQGeJc              
   *  
   *  @since  8-19-10   
   *  @param  integer $miles  how many miles we want to go in any direction from $point
   *  @param  array $point  see {@link assurePoint()} for description
   *  @return array basically 4 points: array($point_a,$point_b,$point_c,$point_d)
   */              
  public static function getBoundingBox($miles,$point){
  
    // canary...
    if(empty($miles)){ throw UnexpectedValueException('$miles should not be empty'); }//if
  
    list($latitude,$longitude) = self::assurePoint($point);
  
    $latitude_miles = 69; // 1 degree of latitude, this is approximate but it's close enough
    $latitude_bounding = ($miles / $latitude_miles);
    
    // get the longitude bounding using cosine...
    $longitude_percentage = abs(cos($latitude * (pi()/180)));
    $longitude_miles = $latitude_miles * $longitude_percentage;
    $longitude_bounding = ($miles / $longitude_miles);
    
    // create a bounding rectangle...
    $point_a = array($latitude - $latitude_bounding,$longitude - $longitude_bounding); 
    $point_b = array($latitude - $latitude_bounding,$longitude + $longitude_bounding); 
    $point_c = array($latitude + $latitude_bounding,$longitude + $longitude_bounding); 
    $point_d = array($latitude + $latitude_bounding,$longitude - $longitude_bounding);
    
    return array($point_a,$point_b,$point_c,$point_d);
    
  }//method
  
  /**
   *  make sure $point is basically array($latitude,$longitude)
   *  
   *  valid lat, long http://answers.yahoo.com/question/index?qid=20090928084146AAVfOZz
   *  
   *  understanding what lat/long is:
   *  http://www.nationalatlas.gov/articles/mapping/a_latlong.html         
   *      
   *  @since  8-19-10   
   *  @param  array $point  an array that contains a latitude and longitude value
   *  @return array array($latitude,$longitude)
   */
  public static function assurePoint($point){
    
    // canary...
    if(!is_array($point)){ throw new InvalidArgumentException('$point was not an array'); }//if
    
    // canary, make sure latitude and longitude exist...
    $latitude = isset($point['latitude']) 
      ? $point['latitude'] 
      : (isset($point[0]) ? $point[0] : null);
    
    // assure valid val...
    if($latitude === null){
    
      throw new UnexpectedValueException('$point had no valid latitude');
      
    }else{
    
      $latitude = self::assureCoordinate($latitude);
    
      if(($latitude >= 90.0) || ($latitude <= -90.0)){
        throw new UnexpectedValueException('latitude of $point was not between 90 and -90 degrees');
      }//if
    
    }//if/else
  
    $longitude = isset($point['longitude']) 
      ? $point['longitude'] 
      : (isset($point[1]) ? $point[1] : null);
    
    // assure valid val...
    if($longitude === null){
      
      throw new UnexpectedValueException('$point had no valid longitude');
    
    }else{
    
      $longitude = self::assureCoordinate($longitude);
    
      if(($longitude >= 180.0) || ($longitude <= -180.0)){
        throw new UnexpectedValueException('longitude of $point was not between 180 and -180 degrees');
      }//if
    
    }//if/else
        
    return array($latitude,$longitude);
    
  }//method
  
  /**
   *  convert a coordinate in any format to a decimal coordinate
   *  
   *  link I used to figure out the math:
   *  http://zonalandeducation.com/mmts/trigonometryRealms/degMinSec/degMinSec.htm
   *  
   *  @since  8-19-10   
   *  @param  string|array|float  $coordinate either a string "DEGREE MINUTE SECOND" or a tri-array with
   *                                          0 => degree, 1 => minute, 2 => second or a float that is
   *                                          already converted so it will be returned
   *  @return float the coordinate as a decimal
   */
  public static function assureCoordinate($coordinate){
  
    // canary, if already numeric our work here is done...
    if(is_numeric($coordinate)){ return (float)$coordinate; }//if
    // canary, if we have a string then divide up into degrees minutes seconds array
    if(is_string($coordinate)){
      $coordinate = explode(' ',str_replace(array('°','"',"'"),'',$coordinate));
    }//if
    // canary, $coordinate needs to be an array by the time it gets here...
    if(!is_array($coordinate)){
      throw new UnexpectedValueException(
        sprintf('$coordinate was not a string or array, it was a %s',gettype($coordinate))
      );
    }//if
      
    // break the array into degrees minutes and seconds...
    $degrees = (float)$coordinate[0];
    $minutes = isset($coordinate[1]) ? (float)$coordinate[1] : 0.0; // 1/60th of a degree
    $seconds = isset($coordinate[2]) ? (float)$coordinate[2] : 0.0; // 1/60th of a minute
    
    return $degrees + ($minutes * (1/60.0)) + ($seconds * (1/3600));
      
  }//method

}//class     
