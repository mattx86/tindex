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
safedata.class.php -- version 1.1
	A PHP class that effectively disables both register_globals and magic_quotes.
	
	It includes simple MySQL(i) wrappers for the escape string functions, and
	wrappers for using both strings and arrays with the HTML entity functions.
	
	It can also help with user input validation by use of the included is_*
	functions.

License:
	MIT License

Requirements:
	PHP 4 >= 4.1.0, PHP 5

Changes:
	v1.1 (January xx, 2006)
		- fixed an order-of-calling issue with __init() that caused
		  __disable_register_globals() to not work on PHP 4.
		- legacy functions have been removed.  this is includes:
		   - htmlentities_array()
		   - htmlspecialchars_array()
		- license changed to the MIT License.
	v1.0 (October 17, 2005)
		- cleaned things up a bit.
		- added: safedata::__init() - as part of the clean-up,
		         safedata::mysqli_escape_string(), safedata::is_string()
		- updated: safedata::gettype(), safedata::htmlentities_array(),
		           safedata::htmlspecialchars_array()
		- fixed: safedata::is_float() - should now be compatible
		         with PHP 4 >= 4.1.0 and PHP 4 < 4.2.0
		- renamed: safedata::fix_register_globals()		=> safedata::__disable_register_globals() - internal
		           safedata::fix_magic_quotes()			=> safedata::__disable_magic_quotes() - internal
		           safedata::_current_version()			=> safedata::__version()
		           safedata::_release_date()			=> safedata::__release()
		           safedata::htmlentities_array()		=> safedata::htmlentities()
		           safedata::htmlspecialchars_array()	=> safedata::htmlspecialchars()
		- legacy: safedata::htmlentities_array(), safedata::htmlspecialchars_array()
	v0.3 (April 14, 2005)
		- added: safedata::is_int(), safedata::is_float(), safedata::gettype(),
		         safedata::_current_version(), safedata::_release_date()
	v0.2 (April 7, 2005)
		- safedata::mysql_escape_string() has a new argument, MySQL link_id.
		- added: safedata::htmlentities_array(), safedata::htmlspecialchars_array()
	v0.1 (April 3, 2005)
		- initial release

Class Functions:
	Internal Functions
		- safedata::__init()
			This is called at the end of this file, just outside of the class,
			and sets up a couple things that safedata will use.  This function
			also calls __disable_register_globals() and __disable_magic_quotes()
			for you.
		- safedata::__version()
			Returns a string of the current version of safedata.
		- safedata::__release(optional date_format)
			Returns a string of the release date of safedata.
			The default date_format is 'F d, Y' -- January 1, 2001

	SQL-Safe Functions
		- safedata::mysql_escape_string (string/array, optional link_identifier)
			When used with PHP 4 >= 4.3.0, and PHP 5, mysql_real_escape_string()
			will be used, otherwise, mysql_escape_string().
			It can take both strings and arrays containing strings.
		- safedata::mysqli_escape_string (string/array, reference mysqli)
			An object-oriented mysqli_real_escape_string() wrapper for
			both strings and arrays that contain strings.

	HTML-Safe Functions
		- safedata::htmlentities (string/array, optional quote_style, optional charset)
		- safedata::htmlspecialchars (string/array, optional quote_style, optional charset)
			A wrapper for htmlentities() and htmlspecialchars() that takes
			both strings and arrays containing strings.

	Input Validation Functions
		- safedata::is_int (var)
			Tests if the variable is a valid integer.
		- safedata::is_float (var)
			Tests if the variable is a valid float.
		- safedata::is_string (var)
			Tests if the variable is a valid string.
		- safedata::gettype (var)
			Returns the variable's type.
			('int', 'float', 'string', 'array', 'unknown')

How to use:
	To automatically disable register_globals and magic_quotes, and enable the
	use of safedata's helpful functions, simply include this class file at the
	top of your script(s).  For example:
		require_once('safedata.class.php');
	
	Then, you can use the methods of this class either statically:
		$_POST = safedata::mysqli_escape_string ($_POST, &$mysqli);
	or through an instantiated object:
		$safedata = new safedata();
		$_POST = $safedata->mysqli_escape_string ($_POST, &$mysqli);

Credits:
	Created by Matt Smith and released under the MIT license -- portions of
	this PHP class are based on:
	
	- The articles at the PHP Security Consortium
		http://phpsec.org
	- This ONLamp.com article, entitled "PHP Form Handling"
		http://www.onlamp.com/pub/a/php/2004/08/26/PHPformhandling.html
	- This NYPHP article
		http://education.nyphp.org/phundamentals/PH_storingretrieving.php
	- and PHP function comments at
		http://php.net

*/

class safedata
{
// Private/Internal Functions

	function __init ()
	{
		$phpversion = phpversion ();
		$GLOBALS['__safedata__']['php4'] = version_compare ($phpversion, "5.0.0", "lt");
		$GLOBALS['__safedata__']['php420'] = version_compare ($phpversion, "4.2.0", "ge");
		$GLOBALS['__safedata__']['php430'] = version_compare ($phpversion, "4.3.0", "lt");
		$GLOBALS['__safedata__']['version'] = '1.1';
		$GLOBALS['__safedata__']['release'] = array ('month' => 1, 'day' => 1, 'year' => 2006);

		safedata::__disable_register_globals ();
		safedata::__disable_magic_quotes ();
	}

	// This gives the current version of the safedata class.
	function __version ()
	{
		return $GLOBALS['__safedata__']['version'];
	}
	
	// This gives the release date of this version.
	function __release ($datefmt = 'F d, Y')
	{
		extract ($GLOBALS['__safedata__']['release']);
		return date ($datefmt, mktime (0, 0, 0, $month, $day, $year) );
	}

	// A function to fix register_globals
	function __disable_register_globals ()
	{
		if ( ini_get ('register_globals') )
		{
			foreach ( array ('_ENV', '_REQUEST', '_GET', '_POST', '_COOKIE', '_SERVER') as $globalkey )
				foreach ( $GLOBALS[$globalkey] as $sub_globalkey => $sub_globalval )
					if ( isset ($GLOBALS[$sub_globalkey]) )
					{
						if ( $GLOBALS['__safedata__']['php4'] ) // PHP 4
							$unset_line = "if ( !is_a (\$GLOBALS[\$sub_globalkey], 'safedata') ) { unset (\$GLOBALS[\$sub_globalkey]); }";
						else // PHP 5
							$unset_line = "if ( !(\$GLOBALS[\$sub_globalkey] instanceof safedata) ) { unset (\$GLOBALS[\$sub_globalkey]); }";
						eval ($unset_line);
					}
			
			ini_set ('register_globals', 0);
		}
	}
	
	// NYPHP's fix_magic_quotes function
	// http://education.nyphp.org/phundamentals/PH_storingretrieving.php
	function __disable_magic_quotes ($var = NULL, $sybase = NULL)
	{
		// if sybase style quoting isn't specified, use ini setting
		if ( !isset ($sybase) )
		{
			$sybase = ini_get ('magic_quotes_sybase');
		}
	
		// if no var is specified, fix all affected superglobals
		if ( !isset ($var) )
		{
			// if magic quotes is enabled
			if ( get_magic_quotes_gpc () )
			{
				// workaround because magic_quotes does not change $_SERVER['argv']
				$argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : NULL; 
	
				// fix all affected arrays
				foreach ( array ('_ENV', '_REQUEST', '_GET', '_POST', '_COOKIE', '_SERVER') as $var )
				{
					$GLOBALS[$var] = safedata::__disable_magic_quotes ($GLOBALS[$var], $sybase);
				}
	
				$_SERVER['argv'] = $argv;
	
				// turn off magic quotes, this is so scripts which
				// are sensitive to the setting will work correctly
				ini_set ('magic_quotes_gpc', 0);
			}
	
			// disable magic_quotes_sybase
			if ( $sybase )
			{
				ini_set ('magic_quotes_sybase', 0);
			}
	
			// disable magic_quotes_runtime
			set_magic_quotes_runtime (0);
			return TRUE;
		}
	
		// if var is an array, fix each element
		if ( is_array ($var) )
		{
			foreach ( $var as $key => $val )
			{
				$var[$key] = safedata::__disable_magic_quotes ($val, $sybase);
			}
	
			return $var;
		}
	
		// if var is a string, strip slashes
		if ( is_string ($var) )
		{
			return $sybase ? str_replace ('\'\'', '\'', $var) : stripslashes ($var);
		}
	
		// otherwise ignore
		return $var;
	}

// SQL-Safe Functions

	// A mysql_[real_]escape_string() wrapper for both strings and arrays.
	function mysql_escape_string ($var, $link_id = NULL)
	{
		if ( is_array ($var) )
		{
			foreach ($var as $key => $val)
				$var[$key] = safedata::mysql_escape_string ($val, $link_id);
		}
		else
		{
			if ( !is_numeric ($var) )
			{
				if ( $GLOBALS['__safedata__']['php430'] )
					return mysql_escape_string ($var);
				else
					return isset ($link_id) ? mysql_real_escape_string ($var, $link_id) : mysql_real_escape_string ($var);
			}
		}
		
		return $var;
	}

	// An object-oriented mysqli_real_escape_string() wrapper for
	// both strings and arrays.
	function mysqli_escape_string ($var, $mysqli)
	{
		if ( is_array ($var) )
		{
			foreach ($var as $key => $val)
				$var[$key] = safedata::mysqli_escape_string ($val, $mysqli);
		}
		else
		{
			if ( !is_numeric ($var) )
				return $mysqli->real_escape_string ($var);
		}
		
		return $var;
	}

// HTML-Safe Functions
	
	// An htmlentities() wrapper for both strings and arrays.
	function htmlentities ($var, $quote_style = ENT_COMPAT, $charset = 'ISO-8859-1')
	{
		if ( is_array ($var) )
		{
			foreach ($var as $key => $val)
				$var[$key] = safedata::htmlentities ($val, $quote_style, $charset);
		}
		else
		{
			if ( !is_numeric ($var) )
				return htmlentities ($var, $quote_style, $charset);
		}
		
		return $var;
	}
	
	// An htmlspecialchars() wrapper for both strings and arrays.
	function htmlspecialchars ($var, $quote_style = ENT_COMPAT, $charset = 'ISO-8859-1')
	{
		if ( is_array ($var) )
		{
			foreach ($var as $key => $val)
				$var[$key] = safedata::htmlspecialchars ($val, $quote_style, $charset);
		}
		else
		{
			if ( !is_numeric ($var) )
				return htmlspecialchars ($var, $quote_style, $charset);
		}
		
		return $var;
	}

// Input Validation Functions

	// Tests for integer.
	function is_int ($var)
	{
		if ( is_array ($var) || is_object ($var) )
			return false;
		
		return ( $var == strval (intval ($var) ) ) ? true : false;
	}

	// Tests for float.
	function is_float ($var)
	{
		if ( is_array ($var) || is_object ($var) )
			return false;
		
		if ( $GLOBALS['__safedata__']['php420'] )
			return ( $var == strval (floatval ($var) ) ) ? true : false;
		else
			return ( $var == strval (doubleval ($var) ) ) ? true : false;
	}
	
	// Tests for string.
	function is_string ($var)
	{
		if ( is_array ($var) || is_object ($var) )
			return false;
		
		return ( $var == strval ($var) ) ? true : false;
	}

	// Returns the variable's type:
	// 'int', 'float, 'string', 'array', or 'unknown'
	function gettype ($var)
	{
		if ( safedata::is_int ($var) )
			return 'int';
		elseif ( safedata::is_float ($var) )
			return 'float';
		elseif ( safedata::is_string ($var) )
			return 'string';
		elseif ( is_array ($var) )
			return 'array';
		else
			return 'unknown';
	}

}

// Initialize safedata
safedata::__init ();

?>