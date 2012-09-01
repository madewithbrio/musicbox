<?php

if (!defined('_BOOTSTRAP') || !_BOOTSTRAP) {
	require_once(dirname(__FILE__).'/../bootstrap.php');
}

if (!file_exists(_TMP_LOCATION)) {
	if(!mkdir(_TMP_LOCATION,0777,true)) {
		throw new Exception("fail make temp location");
	}
}
