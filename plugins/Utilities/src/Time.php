<?php
/**
 *  adds helpful methods to the standard DateTime object
 *  
 *  @link http://us2.php.net/manual/en/class.datetime.php
 *    
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 12-24-11 
 ******************************************************************************/
class Time extends DateTime {

  // different seconds...
  const MINUTE = 60;
  const HOUR = 3600;
  const DAY = 86400;
  const WEEK = 604800;
  const MONTH = 2678400; // 31 days
  const YEAR = 31536000;
  
  /* I always forget about these
  const string ATOM = Y-m-d\TH:i:sP ;
  const string COOKIE = l, d-M-y H:i:s T ;
  const string ISO8601 = Y-m-d\TH:i:sO ;
  const string RFC822 = D, d M y H:i:s O ;
  const string RFC850 = l, d-M-y H:i:s T ;
  const string RFC1036 = D, d M y H:i:s O ;
  const string RFC1123 = D, d M Y H:i:s O ;
  const string RFC2822 = D, d M Y H:i:s O ;
  const string RFC3339 = Y-m-d\TH:i:sP ;
  const string RSS = D, d M Y H:i:s O ;
  const string W3C = Y-m-d\TH:i:sP ;
  */
  
  public function __construct($timestamp,DateTimeZone $timezone = null){
  
    // I don't know whose bright idea it was to not accept raw timestamps, but there you go
    if(is_numeric($timestamp)){ $timestamp = '@'.$timestamp; }//if
    
    // holy crap the person that designed this class is anal
    if($timezone === null){
    
      parent::__construct($timestamp);
      
    }else{
    
      parent::__construct($timestamp,$timezone);
    
    }//if/else
  
  }//method
  
  /**
	 * Returns a description of the amount of time that has passed since a particular time
	 *
	 * @param   int The particular time in question
	 * @param   int A manually set current time (optional; if not set, time() is used)
	 * @return  string Description of the amount of time that has passed
	 */
	public function getElapsed($timestamp = 0){
	
		// array of time period chunks
		$chunks = array(
  		array(self::YEAR, 'year', 'years'),
  		array(self::MONTH, 'month', 'months'),
  		array(self::WEEK, 'week', 'weeks'),
  		array(self::DAY, 'day', 'days'),
  		array(self::HOUR, 'hour', 'hours'),
  		array(self::MINUTE, 'minute', 'minutes'),
		);

		// $newer_date will equal zero if we want to know the time elapsed between a date and the current time
		// $newer_date will have a value if we want to work out time elapsed between two known dates
		$newer_date = empty($timestamp) ? time() : $timestamp;

		// difference in seconds
		$since = $newer_date - $this->getTimestamp();

		// we only want to output two chunks of time here, eg:
		// x years, xx months
		// x days, xx hours
		// so there's only two bits of calculation below:

		// step one: the first chunk
		for($i = 0, $j = count($chunks); $i < $j; $i++){
		
			$seconds = $chunks[$i][0];
			$name = $chunks[$i][1];
			$name_plural = $chunks[$i][2];

			// finding the biggest chunk (if the chunk fits, break)
			if(($count = floor($since / $seconds)) != 0){ break; }//if
			
		}//for

		// set output var
		$output = ($count == 1) ? '1 '.$name : $count.' '.$name_plural;

		// step two: the second chunk
		if($i + 1 < $j){
		
			$seconds2 = $chunks[$i + 1][0];
			$name2 = $chunks[$i + 1][1];
			$name2_plural = $chunks[$i + 1][2];

			if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0){
			
				// add to output var
				$output .= ($count2 == 1) ? ', 1 '.$name2 : ", $count2 $name2_plural";
			}//if
			
		}//if
			
		return $output;
		
	}//method

}//class
