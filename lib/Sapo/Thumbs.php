<?php
class Sapo_Thumbs {
	public static function generateThumbUrl($image, $w = null, $h = null, $crop = 'center') {
			$image_thumb = sprintf('http://thumbs.sapo.pt?pic=%s&Q=80&crop=%s&errorpic=transparent', urlencode($image), $crop);
			if (is_numeric($h)) $image_thumb .= "&H=" . $h;
			if (is_numeric($w)) $image_thumb .= "&W=" . $w;
			return $image_thumb;
	}
}
