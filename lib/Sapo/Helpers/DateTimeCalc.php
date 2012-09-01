<?php


class Sapo_Helpers_DateTimeCalc 
{

	const WEEKDAY_PT = 0;
	const WEEKDAY_EN = 1;

	public static function getDuration($totalSeconds)
	{
		if(empty($totalSeconds)) return '0s';
		
		$elapsedHours = floor($totalSeconds / 3600);
		$totalSeconds -= $elapsedHours * 3600;
		$elapsedMinutes = floor($totalSeconds / 60);
		$totalSeconds -= $elapsedMinutes * 60;
		
		return ($elapsedHours > 0 ? $elapsedHours . 'h' : '') . 
			   ($elapsedHours > 0 || $elapsedMinutes > 0 ? ($elapsedHours > 0 && $elapsedMinutes < 10 ? '0' : '')  . $elapsedMinutes . 'm' : '') . 
			   (($elapsedHours > 0 || $elapsedMinutes > 0) && $totalSeconds < 10 ? '0' : '') . $totalSeconds . 's';
	}
	
	public static function howLongAgo($timestamp) 
	{
		if(empty($timestamp)) return null;

		$elapsedTime = time() - $timestamp;
		$elapsedDays = floor($elapsedTime / 3600 / 24);

		if($elapsedDays == 0)
		{
			$elapsedHours = floor($elapsedTime / 3600);
			$elapsedMinutes = floor($elapsedTime / 60) - $elapsedHours * 60;

			if($elapsedHours == 0) 
			{
				if($elapsedMinutes == 0) return '1m';
				else return $elapsedMinutes . 'm';
			} 
			else return $elapsedHours . 'h';
		}

//		if($elapsedDays == 1) return 'h&aacute; ' . $elapsedDays . ' d';
		if($elapsedDays > 0) return $elapsedDays . 'd';
		return null;
	}

	public static function howLongAgoFromNaturalStrPT($timeStr)
	{
		$translatedTimeStr = self::translateFromPT($timeStr);
	}


	public static function howLongAgoFromNaturalStr($timeStr)
	{
		//$translatedTimeStr = self::translateFromPT($timeStr);
		$translatedTimeStr = $timeStr;

		if(null != $translatedTimeStr)
			return self::howLongAgo(strtotime($translatedTimeStr));
		else
			return null;
	}

	//eventually refactor this method into a culture/translation class
	private static function translateFromPT($ptDateString)
	{
		$monthTranslation = array(
			'jan' => 'January',
			'fev' => 'February',
			'mar' => 'March',
			'abr' => 'April',
			'mai' => 'May',
			'jun' => 'June',
			'jul' => 'July',
			'ago' => 'August',
			'set' => 'September',
			'out' => 'October',
			'nov' => 'November',
			'dez' => 'December');

			$ptDateString = strtolower($ptDateString);
			$pattern = '/jan|fev|mar|abr|mai|jun|jul|ago|set|out|nov|dez/';
			$matches = array();

			if(preg_match($pattern, $ptDateString, $matches))
			{
				$ptMonth = $matches[0];
				return str_replace($ptMonth, $monthTranslation[$ptMonth], $ptDateString);
			}

			return null;
	}

	public static function expandWeekdayInterval($weekdayInterval)
	{
		$interval = array();

		list($startWeekday, $endWeekday) = split('-', strtolower($weekdayInterval));

		$translationTable = array(
			array('2ª', 'mon'),
			array('3ª', 'tue'),
			array('4ª', 'wed'),
			array('5ª', 'thu'),
			array('6ª', 'fri'),
			array('sab', 'sat'),
			array('dom', 'sun'));

		$cursor = 0; $nrCycles = 0;
		$nrRecords = count($translationTable);
		$foundFirst = $foundLast = false;

		while(!$foundLast)
		{
			if($nrCycles > 1) 
			{
				throw new Exception('Unable to determine first and/or last days on weekday interval', -1);
			}

			$current = $translationTable[$cursor];

			if($foundFirst)
			{
				$interval[$current[self::WEEKDAY_EN]] = array();
				if(ereg($endWeekday, $current[self::WEEKDAY_PT])) $foundLast = true;
			}
			else 
			{
				if(ereg($startWeekday, $current[self::WEEKDAY_PT]))
				{
					$interval[$current[self::WEEKDAY_EN]] = array();
					$foundFirst = true;
				}
			}

			if($cursor == $nrRecords - 1) 
			{
				$cursor = 0; //"rewind" - we really need a circular linked list here, so we can just move to the next record
				$nrCycles++; //we have gone full circle - it's normal if the last day is past sunday
			}
			else $cursor++;
		}

		return $interval;
	}

	public static function filterTimesWithinThreshold($timeList, $minThreshold, $maxThreshold = null, $now = null, $format = null)
	{
		$filteredTimes = array();

		if(empty($now)) $now = time();

		foreach($timeList as $i => $timeString)
		{
			$time = self::convertTimeStringToTimeStamp($timeString);
			$diff = $time - $now;

			if(!empty($maxThreshold))
			{
				if($diff >= $minThreshold && $diff <= $maxThreshold) 
					$filteredTimes[] = $format == null ? $time : date($format, $time);
			}
			else
			{
				if($diff >= $minThreshold) 
					$filteredTimes[] = $format == null ? $time : date($format, $time);
			}
		}

		return $filteredTimes;
	}

	private static function convertTimeStringToTimeStamp($timeString, $timeSeparator = 'h')
	{
		list($hour, $minute) = split($timeSeparator, $timeString);
		return @mktime($hour, $minute);
	}

	public static function getSecondsToTomorrow()
	{
		$now = Zend_Date::now();
		$timestampNow = $now->getTimestamp();

		$tomorrow = $now->getDate();
		$tomorrow = $tomorrow->addDay(1);
		$timestampTomorrow = $tomorrow->getTimestamp();

		return $timestampTomorrow - $timestampNow;
	}

	public static function getSecondsToNextHour()
	{
		$now = Zend_Date::now();
		$timestampNow = $now->getTimestamp();

		$nextHour = clone($now);
		$nextHour  = $nextHour->addHour(1)->setMinute(0)->setSecond(0);

		$timestampNextHour = $nextHour->getTimestamp();
		return $timestampNextHour - $timestampNow;
	}

	public static function getTodaysDayAndMonth()
	{
		$locale = new Zend_Locale('pt_PT');
		$date = Zend_Locale_Format::getDate(date('dm'), array('date_format' => 'dM'));

		$translation = $locale->getTranslationList('month');
		$dateStr = (int)$date['day'] . ' ' . ucwords($translation[(int)$date['month']]);

		return $dateStr;
	}

	public static function getLocalizedDayAndMonthFromDate($date, $dateInputFormat = 'Y-M-d', $locale = 'pt_PT')
	{
		$locale = new Zend_Locale('pt_PT');
		$normalizedDate = Zend_Locale_Format::getDate($date, array('date_format' => $dateInputFormat));

		$translation = $locale->getTranslationList('month');
		$dateStr = (int)$normalizedDate['day'] . ' ' . ucwords($translation[(int)$normalizedDate['month']]);

		return $dateStr;
	}

	public static function changeTimeZone(DateTime $dt, $timeZoneId)
	{
		$dtz_original = $dt->getTimezone();
		$timestamp = $dt->format('U');

		$dtz_new = new DateTimeZone($timeZoneId);
		$dt->setTimezone($timestamp);

		$year = gmdate("Y", $timestamp);
		$month = gmdate("n", $timestamp);
		$day = gmdate("j", $timestamp);
		$hour = gmdate("G", $timestamp);
		$minute = gmdate("i", $timestamp);
		$second = gmdate("s", $timestamp);

		$dt->setDate($year, $month, $day);
		$dt->setTime($hour, $minute, $second);
		$dt->setTimezone($dtz_original);
		return $dt;
	}
}


