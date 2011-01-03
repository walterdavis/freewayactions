<?php
//functions to simplify your life
if(!function_exists('alternate')){
	function alternate($strClass1="even",$strClass2="odd"){
		static $out;
		$out = ($out == $strClass1) ? $strClass2 : $strClass1;
		return $out;
	}
}
if(!function_exists('link_for')){
	function link_for($file, $base = '', $class = ''){
		$class = (empty($class)) ? '' : ' class="' . $class . '"';
		return '<a href="' . $base . '/' . $file . '"' . $class . '>' . $file . '</a>';
	}
}
if(!function_exists('isAjax')){
	function isAjax() {
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
			($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
	}
}

/**
 * Compatibility shim for PHP4, provides same function as PHP5 native function
 *
 * @param string $directory_path 
 * @param int $intSortOrder
 * @param int $intStreamContext -- ignored
 * @return array of strings for filenames
 * @author Walter Lee Davis
 */

if(!function_exists('scandir')){
	function scandir($directory_path,$intSortOrder = 0,$intStreamContext = null){
		$dh  = opendir($directory_path);
		$files = array();
		while (false !== ($filename = readdir($dh))) {
			$files[] = $filename;
		}
		closedir($dh);
		if($intSortOrder == 1){
			rsort($files);
		}else{
			sort($files);
		}
		return $files;
	}
}
$index = $_REQUEST['index'];
$up = false;
$ajax = '<script type="text/javascript" charset="utf-8">
	$$("a.dir").invoke("observe","click",function(evt){
		var link = this;
		var box = link.up("div._afl");
		if(box){
			Event.stop(evt);
			new Ajax.Updater(box,"' . $index . '/index.php",{evalScripts:true,parameters:{parent:this.href,index:"' . $index . '"}});
		}
	});
</script>';
//base for all links
$port = ($_SERVER['SERVER_PORT'] != '80') ? ':' . $_SERVER['SERVER_PORT'] : '';
$base = 'http://' . $_SERVER['SERVER_NAME'] . $port . preg_replace('~\/index\.php.*$~','',$_SERVER['REQUEST_URI']);
//scan the directory this file is in
$files_base = dirname(__FILE__);
if(isset($_POST['parent'])){
	$p = preg_replace('~/\index\.php.*$~','',$_POST['parent']);
	$r = preg_replace('~\/index\.php.*$~','','http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
	if(strlen($p) > strlen($r)){
		$next = str_replace($r,'',$p);
	}else{
		$next = '';
	}
	$files_base .= $next;
	$base .= $next;
	$up = ($p != $r);
}
$files = scandir($files_base);
//build an unordered list of files
$out = '<ul class="dirlist">';
$out .= ($up) ? '<li class="upnav"><a href="' . dirname($p) . '" class="dir">Up a level</a></li>' : '';
$script = '';
			$script = $ajax;
foreach($files as $f){
	//skip this file, any folders, and any hidden files
	if($f != basename(__FILE__) && substr($f,0,1) != '.'){
		if(!is_dir($files_base . '/' . $f)){
			$out .= '<li class="' . alternate() . '">' . link_for($f,$base) . '</li>';
		}else{
			$out .= '<li class="' . alternate() . '">' . link_for($f,$base,'dir') . '</li>';
		}
	}
}
$out .= '</ul>';
//support for Ajax.Updater using Prototype
if(isAjax()) {
	header('Content-type: text/html; charset="utf-8"');
	print $out . $script;
	exit;
}else{
	$title = htmlentities(basename($files_base));
	$template = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

	<title>%s</title>
	
</head>

<body>
<h1>%s</h1>
<div>%s</div>
%s
</body>
</html>
';
	printf($template,$title,$title,$out,$script);
}
?>
