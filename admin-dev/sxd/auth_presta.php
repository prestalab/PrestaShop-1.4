<?php
include('../../config/config.inc.php');
$currentFileName = array_reverse(explode("/", $_SERVER['SCRIPT_NAME']));
$cookie = new Cookie('psAdmin', substr($_SERVER['SCRIPT_NAME'], strlen(__PS_BASE_URI__), -strlen($currentFileName['0'])));
/* logged or not */
if ($cookie->isLoggedBack())
	if($this->connect(_DB_SERVER_, 3306, _DB_USER_, _DB_PASSWD_)){
		$auth = 1;
		$this->CFG['my_db']   = _DB_NAME_;
	}
?>