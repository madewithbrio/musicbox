<?php
# Get a cookie value
# CookieHelper::getCookie(CookieHelper::TRACKING_COOKIE)

class Sapo_Helpers_JSON
{
	public static function clean(&$json)
	{
		while(false !== strpos($json, ',,')) $json = str_replace(',,', ',', $json);
		while(false !== strpos($json, '[,')) $json = str_replace('[,', '[', $json);
		while(false !== strpos($json, ',]')) $json = str_replace(',]', ']', $json);
	}
}
