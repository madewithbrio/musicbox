<?php
if (!defined('_BOOTSTRAP') || !_BOOTSTRAP) {
	require_once(dirname(__FILE__).'/../bootstrap.php');
}

$_RESOURCE_LANGUAGE = array();
$_LANGUAGES = array('pt', 'en', 'es');
define('BUILD_FILE', _PROJECT_TEMPLATES_MULTILANGUAGE."/build");

function loadAllResources() {
	global $_LANGUAGES;
	foreach($_LANGUAGES as $language) {
		loadResourceLanguage($language);
	}
}

function loadResourceLanguage($language) {
	global $_RESOURCE_LANGUAGE;
	$_RESOURCE_LANGUAGE[$language] = array();
	$fp = @fopen(_PROJECT_CONFIG."/resource.".$language.".txt", "r");
	if ($fp) {
		while (($buffer = fgets($fp, 4096)) !== false) {
			if (strpos($buffer, "=>"))
			{
				list($key, $value) = explode("=>", $buffer);
				$_RESOURCE_LANGUAGE[$language][$key] = trim($value);
			}
		}
		if (!feof($fp)) {
			throw new Exception("fail: unexpected fgets");
		}
		fclose($fp);
	}
}

function getResource($language, $value) {
	global $_RESOURCE_LANGUAGE;
	$key = Sapo_Helpers_String::buildSlug(strtolower($value));
	if (isset($_RESOURCE_LANGUAGE[$language][$key])) {
		return $_RESOURCE_LANGUAGE[$language][$key];
	} else {
		$fp = @fopen(_PROJECT_CONFIG."/resource.".$language.".txt",'a+');
		fwrite($fp, sprintf("\n%s=>%s", $key, $value));
		fclose($fp);
		$_RESOURCE_LANGUAGE[$language][$key] = $value;
		return $value;
	}
}

function listDir($dir) {
	$files = glob($dir."/*");
	return $files;
}

function transformTemplate($originalFile, $destinationFile) {
	global $_LANGUAGES;
	$content = file_get_contents($originalFile);

	foreach($_LANGUAGES as $language) {
		$tranformContent = $content;
		preg_match_all('/##([^#]*)##/', $tranformContent, $matches);
		for($i = 0, $j = sizeof($matches[1]); $i < $j; $i++) {
			$str_to_replace = getResource($language, $matches[1][$i]);
			$tranformContent = str_replace("##".$matches[1][$i]."##", $str_to_replace, $tranformContent);
		}

		$destinationLanguageFile = str_replace('{language}', $language, $destinationFile);
		file_put_contents($destinationLanguageFile, $tranformContent);
	}
}

function createDir($dir) {
	global $_LANGUAGES;
	foreach($_LANGUAGES as $language) {
		$destinationDir = str_replace('{language}', $language, $dir);
		@mkdir($destinationDir, 0775, true);
	}
}

function processTemplates($dir = _PROJECT_TEMPLATES) {
	$files = listDir($dir);
	createDir(_PROJECT_TEMPLATES_MULTILANGUAGE."/{language}");
	foreach ($files as $file) {
		$destinationFile = str_replace(_PROJECT_TEMPLATES, _PROJECT_TEMPLATES_MULTILANGUAGE."/{language}", $file);
		if (is_file($file)) {
			transformTemplate($file, $destinationFile);
		} else if (is_dir($file)) {
			createDir($destinationFile);
			processTemplates($file);
		}
	}
}

if (!file_exists(BUILD_FILE))
{
	loadAllResources();
	processTemplates();
	// build mtemplate smarty
	foreach ($_LANGUAGES as $language) {
		@mkdir(_TMP_LOCATION."/smarty_mtemplates/".$language, 0775, true);
	}
	touch(BUILD_FILE);
}
unset($_RESOURCE_LANGUAGE);
unset($_LANGUAGES);