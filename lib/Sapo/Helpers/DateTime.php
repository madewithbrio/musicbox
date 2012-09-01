<?php

class Sapo_Helpers_DateTime
{
	const MONTH_COMPARE_FORMAT = 'Y-m';
	protected static $DATE_FORMAT = 'Y-m-d';
	const DAY_IN_SECS = 86400;
	const INV_DAY_IN_SECS = 0.000011574;

	public static function setDateFormat($format) { self::$DATE_FORMAT = $format; }

	public static function isToday($time)
	{
		$currTime = Sapo_HTTP_Server::getCurrentTime();
		return date(self::$DATE_FORMAT, $time) == date(self::$DATE_FORMAT, $currTime);
	}

	public static function isTomorrow($time)
	{
		$tomorrowTime = Sapo_HTTP_Server::getCurrentTime() + self::DAY_IN_SECS;
		return date(self::$DATE_FORMAT, $time) == date(self::$DATE_FORMAT, $tomorrowTime);
	}

	public static function inSameMonth($time1, $time2)
	{
		return date(self::MONTH_COMPARE_FORMAT, $time1) == date(self::MONTH_COMPARE_FORMAT, $time2);
	}

	public static function today()
	{
		$time = Sapo_HTTP_Server::getCurrentTime();
		$date = date(self::$DATE_FORMAT, $time);
		return array($date, $date);
	}

	public static function thisWeekend()
	{
		$time = Sapo_HTTP_Server::getCurrentTime();
		$saturdayTime = self::getSaturdayTime($time);
		$dateFrom = date(self::$DATE_FORMAT, $saturdayTime);
		$dateTo = date(self::$DATE_FORMAT, $saturdayTime + self::DAY_IN_SECS);
		return array($dateFrom, $dateTo);
	}

	public static function thisWeek()
	{
		$time = Sapo_HTTP_Server::getCurrentTime();
		$dateFrom = date(self::$DATE_FORMAT, $time);
		$dateTo = date(self::$DATE_FORMAT, $time + 7 * self::DAY_IN_SECS);
		return array($dateFrom, $dateTo);
	}

	public static function thisMonth()
	{
		$time = Sapo_HTTP_Server::getCurrentTime();
		$dateFrom = date(self::$DATE_FORMAT, self::getMonthStartTime($time));
		$dateTo = date(self::$DATE_FORMAT, self::getMonthEndTime($time));
		return array($dateFrom, $dateTo);
	}

	public static function nextMonths()
	{
		$time = Sapo_HTTP_Server::getCurrentTime();
		$nextMonthStartTime = self::getNextMonthStartTime($time);
		$followingMonthStartTime = self::getNextMonthStartTime($nextMonthStartTime);
		$dateFrom = date(self::$DATE_FORMAT, $nextMonthStartTime);
		$dateTo = date(self::$DATE_FORMAT, self::getMonthEndTime($followingMonthStartTime));
		return array($dateFrom, $dateTo);
	}

	public static function getLocalizedDate($time, $includeYear = false)
	{
		if(self::isToday($time)) return 'Hoje';
		if(self::isTomorrow($time)) return 'Amanhã';

		$currTime = Sapo_HTTP_Server::getCurrentTime();
		$dayDelta = self::getDayDelta($time);
		if($dayDelta <= 5 && $time > $currTime)
		{
			$dateStr = /*'próx. ' . */ self::getDayName($time);
		}
		else
		{
			$day = date('j', $time);
			$dateStr = $day . ' de ' . self::getMonthFullName($time) . ($includeYear ? ' de ' . date('Y', $time) : '');
		}

		return $dateStr;
	}

	/** use with date('w', $ts) */
	public static function getTranslateWeekDay($weekDay)
	{
		switch($weekDay)
		{
			case 0: return 'Domingo';
			case 1: return 'Segunda';
			case 2: return 'Terça';
			case 3: return 'Quarta';
			case 4: return 'Quinta';
			case 5: return 'Sexta';
			case 6: return 'Sábado';
		}
	}

	public static function getShortDayNameByWeekDay($weekDay)
	{
		return mb_substr(self::getTranslateWeekDay($weekDay), 0, 3, 'UTF-8');
	}

	public static function getDayName($time)
	{
		return self::getTranslateWeekDay(date('w', $time));
	}

	public static function getShortDayName($time)
	{
		return mb_substr(self::getTranslateWeekDay(date('w', $time)), 0, 3, 'UTF-8');
	}

	public static function getMonthFromCanonicalName($monthCanonicalName)
	{
		switch($monthCanonicalName)
		{
			case 'janeiro':   return 1;
			case 'fevereiro': return 2;
			case 'marco': 	  return 3;
			case 'abril': 	  return 4;
			case 'maio': 	  return 5;
			case 'junho': 	  return 6;
			case 'julho': 	  return 7;
			case 'agosto': 	  return 8;
			case 'setembro':  return 9;
			case 'outubro':   return 10;
			case 'novembro':  return 11;
			case 'dezembro':  return 12;
		}
	}
	
	public static function getMonthCanonicalName($time)
	{
		switch(date('n', $time))
		{
			case 1:  return 'janeiro';
			case 2:  return 'fevereiro';
			case 3:  return 'marco';
			case 4:  return 'abril';
			case 5:  return 'maio';
			case 6:  return 'junho';
			case 7:  return 'julho';
			case 8:  return 'agosto';
			case 9:  return 'setembro';
			case 10: return 'outubro';
			case 11: return 'novembro';
			case 12: return 'dezembro';
		}
	}
	
	public static function getMonthShortName($time)
	{
		switch(date('n', $time))
		{
			case 1: return 'Jan';
			case 2: return 'Fev';
			case 3: return 'Mar';
			case 4: return 'Abr';
			case 5: return 'Mai';
			case 6: return 'Jun';
			case 7: return 'Jul';
			case 8: return 'Ago';
			case 9: return 'Set';
			case 10: return 'Out';
			case 11: return 'Nov';
			case 12: return 'Dez';
		}
	}

	public static function getMonthFullNameFromCanonicalName($monthCanonicalName)
	{
		switch($monthCanonicalName)
		{
			case 'janeiro':   return 'Janeiro';
			case 'fevereiro': return 'Fevereiro';
			case 'marco': 	  return 'Março';
			case 'abril': 	  return 'Abril';
			case 'maio': 	  return 'Maio';
			case 'junho': 	  return 'Junho';
			case 'julho': 	  return 'Julho';
			case 'agosto': 	  return 'Agosto';
			case 'setembro':  return 'Setembro';
			case 'outubro':   return 'Outubro';
			case 'novembro':  return 'Novembro';
			case 'dezembro':  return 'Dezembro';
		}
	}

	public static function getMonthFullName($time)
	{
		switch(date('n', $time))
		{
			case 1: return 'Janeiro';
			case 2: return 'Fevereiro';
			case 3: return 'Março';
			case 4: return 'Abril';
			case 5: return 'Maio';
			case 6: return 'Junho';
			case 7: return 'Julho';
			case 8: return 'Agosto';
			case 9: return 'Setembro';
			case 10: return 'Outubro';
			case 11: return 'Novembro';
			case 12: return 'Dezembro';
		}
	}

	protected static function getSaturdayTime($time)
	{
		$dayOfWeek = date('w', $time);
		if (0 == $dayOfWeek) return $time - self::DAY_IN_SECS;
		else return $time + (6 - $dayOfWeek) * self::DAY_IN_SECS;
	}

	protected static function getMonthStartTime($time)
	{
		$dayOfMonth = date('j', $time);
		return $time - ($dayOfMonth - 1) * self::DAY_IN_SECS;
	}

	protected static function getMonthEndTime($time)
	{
		$dayOfMonth = date('j', $time);
		$daysInMonth = date('t', $time);
		return $time + ($daysInMonth - $dayOfMonth) * self::DAY_IN_SECS;
	}

	protected static function getNextMonthStartTime($time)
	{
		return self::getMonthEndTime($time) + self::DAY_IN_SECS;
	}

	protected static function getDayDelta($time)
	{
		$currTime = Sapo_HTTP_Server::getCurrentTime();
		return ceil(($time - $currTime) * self::INV_DAY_IN_SECS); // was round
	}
}

