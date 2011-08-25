<?php
/*
Copyright (c) 2005-2006 Matt Smith

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell cop-
ies of the Software, and to permit persons to whom the Software is furnished
to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
IN THE SOFTWARE.
*/

// ----- place custom include directives here -----
$tindex['incpath'] = 'include/'; // Configuration include path.
require_once($tindex['incpath'] . 'config.inc.php');

// ----- script variables, edit as needed -----
$tindex['path'] = getcwd().'/content/'; // Content include path.
$tindex['index'] = 'customer'; // Default index page.
$tindex['ext'] = '.php'; // File Extension for pages.
$tindex['custom'] = false; // Enable custom 403 and 404 error pages.
$tindex['custompath'] = $tindex['path'].'errors/'; // Custom error path.
$tindex['https'] = true; // Enforce HTTPS.
$tindex['phppage'] = true; // Parse PHP within content file.
$tindex['phptemplate'] = "smarty"; // Template mode: "php", "smarty", "no"


// do not proceed past this line unless you know what you're doing
//----------------------------------------------------------------

$tindex['version'] = '1.1'; // Tindex version

// Get variables.
$page = $_GET['page'];
if (empty($page))
	$page = $tindex['index'];

// Check for a valid template mode.
if ($tindex['phptemplate'] != "php" && $tindex['phptemplate'] != "smarty" && $tindex['phptemplate'] != "no") {
	echo tindex_error("Invalid Template Mode", 0);
	exit;
}

// Clear file status cache.
clearstatcache();

// Check for content path tampering.
// This could probably be made to allow paths *within* the base path,
// but right now it only allows the exact content path.
$tindex['fullpath'] = $tindex['path'] . $page . $tindex['ext'];
$tindex['pathnofile'] = pathinfo($tindex['fullpath'], PATHINFO_DIRNAME);
$tindex['realpath'] = realpath($tindex['pathnofile']) . '/';
if ($tindex['realpath'] != $tindex['path']) {
	header("HTTP/1.1 403 Forbidden");
	echo tindex_error("Forbidden", 403);
	exit;
}

// HTTPS enforcement.
if ( $tindex['https'] && (empty($_SERVER['HTTPS']) && !count($_POST)) ) {
	header("Location: https://{$_SERVER['SERVER_ADDR']}{$_SERVER['REQUEST_URI']}");
	exit;
}

// Start output buffer, get page contents, and PHP-parse it if needed.
ob_start();
$tindex['phpcontent'] = tindex_get_contents($tindex['fullpath']);
if ($tindex['phppage'])
{
	eval("?>" . $tindex['phpcontent'] . "<?php ");
	$content = ob_get_contents() . "\n";
	ob_clean();
} else $content = $tindex['phpcontent'];
unset($tindex['phpcontent']);

// Display the page according to the template mode being used.
if ($tindex['phptemplate'] == "php") // Display PHP-parsed template file:
{
	$tindex['phpcontent'] = tindex_get_contents($tindex['path'] . 'template' . $tindex['ext']);
	eval("?>" . $tindex['phpcontent'] . "<?php ");
	$tindex['template'] = ob_get_contents() . "\n";
	ob_clean();
	unset($tindex['phpcontent']);
	echo $tindex['template'];
}
elseif ($tindex['phptemplate'] == "smarty") // Display Smarty Template Engine page:
{
	if (isset($root_tpl))
		$smarty->display("{$root_tpl}.tpl");
	else
		$smarty->display('template.tpl');
}
elseif ($tindex['phptemplate'] == "no") // Display raw content ("No Templating"):
{
	echo $content;
}

// Use gzip encoding if possible.
if (extension_loaded('zlib')) {
	@include_once($tindex['incpath'] . 'gzip_encode.class.php');
	new gzip_encode();
} else ob_end_clean();


// ----- tindex functions -----

function tindex_get_contents($file)
{
	$tindex =& $GLOBALS['tindex'];
	if ( file_exists($file) )
	{
		if (!is_file($file) ||
			(is_file($file) && !($phpcontent = file_get_contents($file, false))) )
		{
			header("HTTP/1.1 403 Forbidden");
			if (!$tindex['custom'] ||
				($tindex['custom'] && !($phpcontent = file_get_contents($tindex['custompath'] . '403' . $tindex['ext']))) )
			{
				$phpcontent = tindex_error("Forbidden", 403);
			}
		}
	}
	else // File does not exist:
	{
		header("HTTP/1.1 404 Not Found");
		if (!$tindex['custom'] ||
			($tindex['custom'] && !($phpcontent = file_get_contents($tindex['custompath'] . '404' . $tindex['ext']))) )
		{
			$phpcontent = tindex_error("Not Found", 404);
		}
	}
	
	return $phpcontent;
}

function tindex_error($msg, $code) {
	$page =& $GLOBALS['page'];
	$version =& $GLOBALS['tindex']['version'];
	
	return "
<html>
<head>
  <title>Tindex: Error {$code}</title>
  <style type=\"text/css\">
  <!--
    html {
      font-family: Verdana, Arial, sans-serif;
      font-size: 10pt;
    }
    body {
      background-color: rgb(255,255,255);
      color: rgb(0,0,0);
      padding: 0px;
      margin: 16px;
    }
    
    h1#heading {font-size: 14pt}
    div#msg {
      padding: 4px;
      border: 1px solid rgb(127,127,127);
      color: rgb(0,0,0);
      background-color: rgb(223,223,223);
    }
    #req {border: 1px solid rgb(255,0,0); padding: 2px;}
  -->
  </style>
</head>
<body>
<div id=\"msg\">
  An error has occurred:<br>
  <dfn>{$code} - {$msg}</dfn><br>
  <br>
  <code id=\"req\">requested page = ".htmlentities($page)."</code>
</div>
<br>
<em><a href=\"http://tindex.sourceforge.net/\">Tindex {$version}</a></em>
</body>
</html>
";
}

?>