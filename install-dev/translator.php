<?php
$pattern='/lang\(\'(.*[^\\\\])\'(, ?\'(.+)\')?(, ?(.+))?\)/U';
$lang_file='langs/ru.php';

$_LANG = array();

include($lang_file);

$content=file_get_contents('index.php');

preg_match_all($pattern, $content, $matches);

$_LANG_NEW = array();
foreach ($matches[1] AS $key)
{
	$_LANG_NEW[$key]=($_LANG[$key]?$_LANG[$key]:$key);
}

$data="<?php\n";
foreach ($_LANG_NEW AS $key=>$value)
{
	$data.="\$_LANG['".str_replace(array(".'","'."), array(".\'","\'."),$key)."'] = '".($_LANG_NEW[$key]?$_LANG_NEW[$key]:$key)."';\n";
}
file_put_contents($lang_file, $data);
echo 'ok';