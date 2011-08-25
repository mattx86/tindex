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

/*
tindex -- version 1.2
*/

// ----- place custom include directives here -----
$tindex['incpath'] = 'include/'; // Configuration include path.
require_once($tindex['incpath'] . 'config.inc.php');

// ----- script variables, edit as needed -----
$tindex['path_pages'] = getcwd().'/pages/'; // Path to page files.
$tindex['path_templates'] = getcwd().'/templates/'; // Path to template files.
$tindex['index'] = 'customer.php'; // Default index page.
$tindex['https'] = true; // Force HTTPS.


// do not proceed past this line unless you know what you're doing
//----------------------------------------------------------------

// Get variables.
$page = ((empty($_GET['page'])) ? $tindex['index'] : $_GET['page'] . ".php");

// Check for content path tampering.
// This could probably be made to allow paths *within* the base path,
// but right now it only allows the exact content path.
$tindex['fullpath'] = $tindex['path_pages'] . $page;
$tindex['pathnofile'] = pathinfo($tindex['fullpath'], PATHINFO_DIRNAME);
$tindex['realpath'] = realpath($tindex['pathnofile']) . '/';
if ($tindex['realpath'] != $tindex['path_pages']) {
	header("HTTP/1.1 403 Forbidden");
	die("<br />\n<strong>Error</strong>: 403 Forbidden<br />\n");
}

// HTTPS forcing
if ( $tindex['https'] && (empty($_SERVER['HTTPS']) && !count($_POST)) ) {
	header("Location: https://{$_SERVER['SERVER_ADDR']}{$_SERVER['REQUEST_URI']}");
	exit;
}

// Start output buffer, get page contents, and PHP-parse it.
ob_start();
if ( ! ($tindex['content'] = @file_get_contents($tindex['fullpath'], false)) )
{
	die("<br />\n<strong>Error</strong>: Failed to load page<br />\n");
}
eval("?>" . $tindex['content'] . "<?php ");
$tindex['content'] = ob_get_contents() . "\n";
ob_clean();

// Check for a valid template mode.
if ($tindex['template_mode'] != "php" && $tindex['template_mode'] != "smarty" && $tindex['template_mode'] != "no") {
	die("<br />\n<strong>Error</strong>: Invalid template mode<br />\n");
}

// Display the page according to the template mode being used.
if ($tindex['template_mode'] == "php") // Display PHP-parsed template file:
{
	//@include($tindex['path_templates'] . $tindex['template']);
	if ( ! ($tindex['tmp'] = @file_get_contents($tindex['path_templates'] . $tindex['template'], false)) )
	{
		die("<br />\n<strong>Error</strong>: Failed to load template<br />\n");
	}
	eval("?>" . $tindex['tmp'] . "<?php ");
	$tindex['tmp'] = ob_get_contents() . "\n";
	echo $tindex['tmp'];
	ob_clean();
}
elseif ($tindex['template_mode'] == "smarty") // Display Smarty Template Engine page:
{
	$smarty->display($tindex['template']);
}
elseif ($tindex['template_mode'] == "no") // Display raw content ("No Templating"):
{
	echo $content;
}

// Use gzip encoding if possible.
if (extension_loaded('zlib')) {
	@include_once($tindex['incpath'] . 'gzip_encode.class.php');
	new gzip_encode();
} else ob_end_clean();

?>