<?php

class Sapo_Helpers_Encoder
{
	const KEY = "4r1!8zfedwdfh7ss&lkl#d $8";
	const IV_LEN = 43;
	const SHA1_LEN = 40;

	public static function encode($array)
	{
		$s = serialize($array);
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$iv_base64 = rtrim(base64_encode($iv), '=');

		if (strlen($iv_base64) != self::IV_LEN) throw new Exception ("Something is wrong with server configuration on Sapo_Helpers_Encoder::encode!", 500);

		$crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, self::KEY, $s . sha1($s), MCRYPT_MODE_CBC, $iv);
		return $iv_base64 . base64_encode($crypttext);
	}

	public static function decode($base64)
	{
		$iv = base64_decode(substr($base64, 0, self::IV_LEN) . '==');
		$s =  base64_decode(substr($base64, self::IV_LEN));
		$decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, self::KEY, $s, MCRYPT_MODE_CBC, $iv));
		$hash = substr($decrypted, -self::SHA1_LEN);
		$data = substr($decrypted, 0, -self::SHA1_LEN);

		if (sha1($data) != $hash) throw new Exception ("Possible Data Tamper on Sapo_Helpers_Encoder::decode!", 500);

		return unserialize($data);
	}
}
