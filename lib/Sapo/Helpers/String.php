<?php

class Sapo_Helpers_String
{
	public static function toIntegerHash($string) { return base_convert(sha1($string), 16, 10); }
	public static function toFloatString($float) { return str_replace(',', '.', $float); }
	public static function alphabeticSort($a, $b) { return strcmp(self::replaceInternationalCharacters($a), self::replaceInternationalCharacters($b)); }

	public static function requestPathEncode($a) { return urlencode(str_replace('/', '%2f', $a)); }
	public static function requestPathDecode($a) { return str_replace('%2f', '/', self::utf8Urldecode($a)); }


	public static function utf8Urldecode($value)
	{
		return preg_replace('/%([0-9a-f]{2})/ie', 'chr(hexdec("$1"))', (string) $value);
	}


	public static function htmlEntityDecode($string, $quote_style = ENT_COMPAT)
	{
		return stripslashes(html_entity_decode($string, $quote_style, 'UTF-8'));
	}

	public static function htmlEntityEncode($string, $quote_style = ENT_COMPAT)
	{
		return htmlentities(stripslashes($string), $quote_style, 'UTF-8');
	}

	public static function isUTF8($string)
	{
		return (utf8_encode(utf8_decode($string)) == $string);

		// From http://w3.org/International/questions/qa-forms-utf-8.html
		//ALERT: code below crashes on prd servers (works on dev though)
/*
		return preg_match('%^(?:
			  [\x09\x0A\x0D\x20-\x7E]            # ASCII
			| [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
			|  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
			| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
			|  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
			|  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
			| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
			|  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
		)*$%xs', $string);
*/
	}

	public static function truncateString($string, $length = 80, $etc = '...', $breakWords = false, $middle = false)
	{
		if($length == 0) return '';

		if(strlen($string) > $length) 
		{
			$length -= min($length, strlen($etc));
			if(!$breakWords && !$middle) 
			{
				$string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length + 1));
			}

			if(!$middle) return substr($string, 0, $length) . $etc;
			else return substr($string, 0, $length/2) . $etc . substr($string, -$length/2);
		} 

		return $string;
	}

	public static function buildSlug($string)
	{
		if (self::isUTF8($string)) $string =  utf8_decode($string) ;
		$string = self::htmlEntityDecode($string);
		return preg_replace('/[^a-zA-Z0-9]/', "_", self::replaceInternationalCharacters(substr(strtolower($string), 0, 100)));
	}

	public static function replaceInternationalCharacters($string)
	{
		$table = array(
			'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
			'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
			'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
			'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
			'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
			'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
			'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
			'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r', 'ª'=>'a', 'º'=>'o',
			/*'«'=>'<', '»'=>'>'*/ '«'=>'', '»'=>'',
			'–'=>'-', '"'=>'', '”'=>'', '“'=>'', 
		);

		$string = (self::isUTF8($string)) ? $string : utf8_encode($string);
		$string = strtr($string, $table);

		return utf8_decode($string);
	}

	public static function replaceSpecialCharacters($string)
	{
		$specialCharList = array('«', '»', '–', '’', '”', '“', '', '', '', '', '', '', '&', '', '', 'ǂ');

		$replaceList = array('', '', '-', "'", '"', '"', '€', '"', '"', '', '', '', '&amp;', '-', '', "\n");

		return str_replace($specialCharList, $replaceList, $string);
	}

	public static function removeSpecialChars($string)
	{
		return stripslashes(ereg_replace('^[^a-zA-Z|\s]', ' ', strip_tags(html_entity_decode($string))));
	}

	public static function hasPossibleXSS($string)
	{
		$res = preg_match('/(amp;|#38;)(#(34|38|60|62)|&(amp|gt|lt|quot));/', $string, $matches);
		if ($res) Sapo_Log::getLogger()->info('possible XSS on: ' . $text);
		return $res;
	}

	public static function getFormattedText($text)//, $objectTypeRegExps = null, $defaultObjectType = null, $displayKnownContentLinks = null)
	{
		if (self::hasPossibleXSS($text)) return null;

		$text = preg_replace('/<object(.*?)<\/object>|<embed(.*?)<\/embed>/is', '', $text);
//		$text = strip_tags($text, '<object><embed><p><strong><i><b><img><em><br>');
//		$text = Sapo_Helpers_String::replaceKnownContentTags($text, $objectTypeRegExps, $defaultObjectType, $displayKnownContentLinks);

		$text = strip_tags($text, '<p><strong><i><b><img><em><br><a>');
		$text = preg_replace('/<br[^>]*>(\s*<br[^>]*>)+/i', '<br /><br />', $text); // 2 line consecutive breaks
		$text = preg_replace('/<p[^>]*>/i', '<p>', $text); // get rid of paragraph formatting
		$text = preg_replace('/<p[^>]*>[^\w\s]*(\s*<br[^>]*>\s*)*\s*<\/p[^>]*>/i', '', $text); // empty or <br> laden paragraphs
		$text = preg_replace('/<p>[^\w\s]*(\s*<br[^>]*>\s*)*[^\w\s]*<\/p>/i', '', $text); // get rid of leading breaks
		$text = preg_replace('/<\/p>\s*(<br[^>]*>\s*)+/i', '</p>', $text); // clear line breaks between paragraphs

		$text = trim($text);

		return $text;
	}

	public static function filterBasicText($text)
	{
		if (self::hasPossibleXSS($text)) return null;

		$description = $text;
		$description = strip_tags($description, '<p><strong><i><b><em><br>');
		$description = str_replace("\n", "<br />", $description);
		$description = preg_replace('/\n/', '', $description);
		$description = preg_replace('/<br[^>]*>(\s*<br[^>]*>)+/i', '<br /><br />', $description); // 2 line consecutive breaks
		$description = preg_replace('/<p[^>]*>/i', '<p>', $description); // get rid of paragraph formatting
		$description = preg_replace('/<p[^>]*>[^\w\s]*(\s*<br[^>]*>\s*)*\s*<\/p[^>]*>/i', '', $description); // empty or <br> laden paragraphs
		$description = preg_replace('/<p>[ \s]+<\/p>/i', '', $description);	
		$description = preg_replace('/<p>[^\w\s]*(\s*<br[^>]*>\s*)*[^\w\s]*<\/p>/i', '', $description); // get rid of leading breaks
		$description = preg_replace('/<\/p>\s*(<br[^>]*>\s*)+/i', '</p>', $description); // clear line breaks between paragraphs

		$description = str_replace('</P>', '</p>', $description); // change paragraph closing tag to lower case

		$description = Sapo_Helpers_String::closeTags($description);
		$description = trim($description);

		return $description;
	}

	public static function encodeString($string)
	{
	}

	public static function decodeString($string)
	{
	}

	public static function fillLimitedTemplate($template, $fill, $charLimit, $termination = '...')
	{
		$templateLen = strlen($template);
		$terminationLen = strlen($termination);
		$fillLen = strlen($fill);

		if ($templateLen + $terminationLen >= $charLimit) return sprintf($template, '');

		if ($templateLen + $fillLen >= $charLimit)
			$fill = trim(substr($fill, 0, $charLimit - $templateLen - $terminationLen)) . $termination;

		return sprintf($template, $fill);
	}

	public static function textToHtml($text) {
		return str_replace("\n", '<br /><br />', trim($text));
	}

	public static function integerToVerbose($int)
	{
		$million = 1000000;
		$thousand = 1000;

		if($int >= $million)
		{
			$newInt = $int / $million * 10; // leave a decimal
			$newInt = floor($newInt);
			$newInt = $newInt / 10;
			if (1 == $newInt) return '1 milhão';
			return $newInt . ' milhões';
		}

		$leftover = $int % $thousand;
		if ($leftover == 0)
		{
			$newInt = $int / $thousand;
			if (1 == $newInt) return 'mil';
			return $newInt . ' mil';
		}
	}

	function randomString($length = 16, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890')
	{
		$charsCount = (strlen($chars) - 1);
		$string = '';

		for ($i = 0; $i < $length; $i++)
			$string.= $chars{rand(0, $charsCount)};

		return $string;
	}

	public static function base64_url_encode($input)
	{
		return strtr(base64_encode($input), '+/=', '-_,');
	}

	public static function base64_url_decode($input)
	{
		return base64_decode(strtr($input, '-_,', '+/='));
	}

	public static function cleanBreakTags($text) { return preg_replace('/<br[^>]*>/i', '<br />', $text); }

	public static function closeTags($text)
	{
		$text = self::cleanBreakTags($text);
		$finalText = '';
		$autocloseTags = array('b', 'em', 'strong', 'i');
		// TODO close p before another p
		$pos = 0;
		$searchPos = 0;
		$openTag = null;
		$pTagIsOpen = false;
		while (false !== $openPos = strpos($text, '<', $searchPos))
		{
			$openPos++;
			$tagType = ($text[$openPos] == '/')? 'closingTag' : 'openingTag';
			$tagMatched = preg_match('/^\/?([A-Za-z]+)[^>]*>/', substr($text, $openPos), $matches);
			if ($tagMatched)
			{
				$tag = strtolower($matches[1]);

				if ($tag == 'a') // if caught an a - advance
				{
					$searchPos = $openPos + strlen($matches[0]);
					continue;
				}
				else if ($tag == 'p')
				{
					if ($tagType == 'openingTag')
					{
						if ($pTagIsOpen) // must closeTag
						{
							$finalText .= substr($text, $pos, $openPos - $pos - 1) . "</p><p>";
							$pos = $openPos + strlen($matches[0]);
						} else $pTagIsOpen = true;
					}
					else // closing Tag
					{
						if ($pTagIsOpen) $pTagIsOpen = false;
					}
				}

				if ($tagType == 'openingTag')
				{
//echo "\n\n" . $tagType. ": $tag (openTag: $openTag)\n";
					if ($openTag)
					{
						$finalText .= substr($text, $pos, $openPos - $pos - 1) . "</$openTag><$tag>";
//echo "$finalText  ($pos, $openPos, ";
						$pos = $openPos + strlen($matches[0]);
//echo "$pos)\n";

					}

					if(in_array($tag, $autocloseTags)) $openTag = $tag;
					else $openTag = null;

				}
				else // closingTag
				{
//echo "\n\n" . $tagType. ": $tag (openTag: $openTag)\n";
					if ($openTag && $openTag != $tag)
					{
						$finalText .= substr($text, $pos, $openPos - $pos - 1) . "</$openTag><$tag>";
						$pos = $openPos + strlen($matches[0]);
//echo "$finalText  ($pos, $openPos)\n";
					}
					$openTag = null;
				}
			}
			$searchPos = $openPos + 1;
		}

		$finalText.= substr($text, $pos);

		if ($openTag) $finalText.= "</$openTag>";
//echo "Final: $finalText\n";
		return $finalText;
	}

	public static function naturalLanguageList($list)
	{
		$return = '';
		for($i = 0, $count = count($list)-1; $i <= $count; $i++)
		{
			$connector = ($i == $count ? '' : ($i == $count-1 ? ' e ' : ', '));
			$return .= $list[$i] . $connector;
		}
		return $return;
	}

	public static function utf8_strlen($str) {
	  $count = 0;
	  for ($i = 0; $i < strlen($str); ++$i) {
	    if ((ord($str[$i]) & 0xC0) != 0x80) {
	      ++$count;
	    }
	  }
	  return $count;
	}
}
