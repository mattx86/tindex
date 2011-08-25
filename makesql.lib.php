<?php
/*
Copyright (c) 2006 Matt Smith

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
makesql.lib.php -- version 1.0
*/


require_once('safedata.class.php');

function makesql()
{
	// not enough parameters
	$num_args = func_num_args();
	if ($num_args <= 1)
		return -1;
	
	$mode = func_get_arg(0);
	// what's allowed for the mode parameter.. :
	if ($mode != "insert" && $mode != "update" && $mode != "delete")
		return -1;
	
	$success = 0;
	for ($i = 1; $i < $num_args; $i++)
	{
		$arg = func_get_arg($i);
		foreach (array_keys($arg) as $table)
		{
			if (is_array($arg[$table]))
			{
				$fields = "";
				$values = "";
				
				$pairs = "";
				$where = "";
				
				if ($mode == "insert")
				{
					foreach ($arg[$table] as $field => $value)
					{
						$fields .= ", `{$field}`";
						if (safedata::is_int($value) || safedata::is_float($value) || $value == "NULL")
						{
							$values .= ", {$value}";
						}
						elseif (safedata::is_string($value))
						{
							$value = safedata::mysql_escape_string($value);
							$values .= ", '{$value}'";
						}
					}
					
					$fields = "(" . substr($fields, 2) . ")";
					$values = "(" . substr($values, 2) . ")";
					
					$cmd = "INSERT INTO `{$table}` {$fields} VALUES {$values}";
				}
				elseif ($mode == "update")
				{
					$where = "";
					foreach ($arg[$table] as $field => $value)
					{
						if ($field != "where")
						{
							$pairs .= ", `{$field}` = ";
							if (safedata::is_int($value) || safedata::is_float($value) || $value == "NULL")
							{
								$pairs .= "{$value}";
							}
							elseif (safedata::is_string($value))
							{
								$value = safedata::mysql_escape_string($value);
								$pairs .= "'{$value}'";
							}
						}
						else $where = " WHERE {$value}";
					}
					
					$pairs = substr($pairs, 2);
					$cmd = "UPDATE `{$table}` SET {$pairs}{$where}";
				}
				elseif ($mode == "delete")
				{
					// so you don't shoot yourself in the foot..
					// this attempts to stop you from removing a record
					// you may have just updated (if you happen to pass
					// the same array, this time in delete mode).
					$arrcnt = count($arg[$table]);
					if ($arrcnt != 1 || ($arrcnt == 1 && !isset($arg[$table]['where'])))
						return -1;
					
					$cmd = "DELETE FROM `{$table}` WHERE {$arg[$table]['where']}";
				}
				$success += mysql_query($cmd);
			}
		}
	}
	
	return $success; // number of successful table inserts
}

// all modes:
//   mode, type, ...
// general mode:
//   mode, type, name, value
// insert mode:
//   mode, type, field
// update mode:
//   mode, type, table, field, where
function makehtml()
{
	// not enough parameters
	$num_args = func_num_args();
	if ($num_args < 2)
		return -1;
	
	$mode = func_get_arg(0);
	// what's allowed for the mode parameter.. :
	if ($mode != "insert" && $mode != "update" && $mode != "general")
		return -1;
	
	$type = func_get_arg(1);
	// what's allowed for the type parameter.. :
	// Input Tag:
	//  * button
	//  * checkbox
	//  * file (to-do; is this feasible?)
	//  * hidden
	//  * image (to-do; is this feasible?)
	//  * password
	//  * radio
	//  * reset
	//  * submit
	//  * text
	// Other Tags:
	//  * select (no extra_parm; is this feasible?)
	//  * textarea
	if ($type != "button" && $type != "checkbox" && $type != "file" && $type != "hidden" &&
		$type != "image" && $type != "password" && $type != "radio" && $type != "reset" &&
		$type != "submit" && $type != "text" && $type != "select" && $type != "textarea")
		return -1;
	
	/**********
	** modes **
	**********/
	if ($mode == "general")
	{
		// not enough parameters for general
		if ($num_args < 4)
			return -1;
		
		$name = func_get_arg(2);
		$value = func_get_arg(3);
		
		$extra = "";
		if ($num_args >= 5)
		{
			$extra_parm = func_get_arg(4);
			$extra = " " . $extra_parm;
		}
		
		if ($type == "button" || $type == "hidden" || $type == "password" || $type == "reset" || $type == "submit")
		{
			$html = "<input type=\"{$type}\" name=\"{$name}\" value=\"{$value}\"{$extra} />";
			return $html;
		}
	}
	elseif ($mode == "insert")
	{
		// not enough parameters for insert
		if ($num_args < 3)
			return -1;
		
		$field = func_get_arg(2);
	}
	elseif ($mode == "update")
	{
		// not enough parameters for update
		if ($num_args < 5)
			return -1;
		
		$table = func_get_arg(2);
		$field = func_get_arg(3);
		$where = func_get_arg(4);
	}
	
	/**********
	** types **
	**********/
	// checkbox, radio, text, textarea, select
	if ($type == "checkbox" || $type == "radio")
	{
		// not enough parameters for checkbox/radio
		if ($num_args < 4)
			return -1;
		
		$extra = "";
		if ($mode == "insert")
		{
			$value = func_get_arg(3);
			if ($num_args >= 5)
			{
				$extra_parm = func_get_arg(4);
				$extra = " " . $extra_parm;
			}
		}
		elseif ($mode == "update")
		{
			// not enough parameters for update
			if ($num_args < 6)
				return -1;
			
			$value = func_get_arg(5);
			if ($num_args >= 7)
			{
				$extra_parm = func_get_arg(6);
			} else $extra_parm = "";
			
			$val = getfield($table, $field, $where);
			$val = safedata::htmlentities($val);
			if ($val == $value)
				$extra = " checked=\"checked\"";
			if ($extra_parm != "")
				$extra .= " " . $extra_parm;
		}
		
		$html = "<input type=\"{$type}\" name=\"{$field}\" value=\"{$value}\"{$extra} />";
	}
	elseif ($type == "text" || $type == "textarea")
	{
		// not enough parameters for insert
		if ($num_args < 4)
			return -1;
		
		$extra = "";
		$textarea = "";
		if ($mode == "insert")
		{
			// differs a little from any other insert type
			$table = func_get_arg(2);
			$field = func_get_arg(3);
			
			if ($num_args >= 5)
			{
				$extra_parm = func_get_arg(4);
			} else $extra_parm = "";
		}
		elseif ($mode == "update")
		{
			if ($num_args >= 6)
			{
				$extra_parm = func_get_arg(5);
			} else $extra_parm = "";
			$val = getfield($table, $field, $where);
			$val = safedata::htmlentities($val);
			if ($type == "textarea")
				$textarea = $val;
			elseif ($type == "text")
				$extra = " value=\"{$val}\"";
		}
		
		// get field length
		$val = getfieldlen($table, $field);
		$extra .= " maxlength=\"{$val}\"";
		if ($extra_parm != "")
			$extra .= " " . $extra_parm;
		
		if ($type == "text")
			$html = "<input type=\"text\" name=\"{$field}\"{$extra} />";
		elseif ($type == "textarea")
			$html = "<textarea name=\"{$field}\"{$extra}>{$textarea}</textarea>";
	}
	elseif ($type == "select")
	{
		// not enough parameters
		if ($num_args < 7)
			return -1;
		
		// parameters for select differ drastically
		unset($field);
		unset($where);
		$table = func_get_arg(2);
		$field = func_get_arg(3);
		$id = func_get_arg(4);
		$text = func_get_arg(5);
		$order = func_get_arg(6);
		
		if ($mode == "insert")
		{
			$extra = "";
			$cmd = "SELECT `{$id}`, `{$text}` FROM `{$table}` ORDER BY {$order}";
			$result = mysql_query($cmd);
			$select[] = "<select name=\"{$field}\">\n";
			while ($data = mysql_fetch_assoc($result))
			{
				if (count($select) == 1)
					$extra = " selected=\"selected\"";
				else $extra = "";
				$select[] = "<option value=\"{$data[$id]}\"{$extra}>{$data[$text]}</option>\n";
			}
			$select[] = "</select>\n";
		}
		elseif ($mode == "update")
		{
			// not enough parameters for update
			if ($num_args < 8)
				return -1;
			
			$where = func_get_arg(7);
			
			$extra = "";
			$cmd = "SELECT `{$id}`, `{$text}` FROM `{$table}` ORDER BY {$order}";
			$result = mysql_query($cmd);
			$select[] = "<select name=\"{$field}\">\n";
			while ($data = mysql_fetch_assoc($result))
			{
				if ($data[$id] == $selected)
					$extra = " selected=\"selected\"";
				else $extra = "";
				$select[] = "<option value=\"{$data[$id]}\"{$extra}>{$data[$text]}</option>\n";
			}
			$select[] = "</select>\n";
		}
		
		$html = "";
		$select_lines = count($select);
		for ($i = 0; $i < $select_lines; $i++)
		{
			$html .= $select[$i];
		}
	}
	
	return $html;
}

function makeurl()
{
	// not enough parameters
	$num_args = func_num_args();
	if ($num_args < 1)
		return -1;
		
	$url = func_get_arg(0);
	
	if ($num_args > 1)
	{
		for ($i = 1; $i < $num_args; $i++)
		{
			$arg = func_get_arg($i);
			$sep = (($i == 1)? '?':'&amp;');
			$url .= $sep . $arg;
		}
	}
	
	return $url;
}

function getfield($table, $field, $where)
{
	$cmd = "SELECT `{$field}` FROM `{$table}` WHERE {$where}";
	$result = mysql_query($cmd);
	$val = mysql_result($result, 0);
	
	return $val;
}

function getfieldlen($table, $field)
{
	$cmd = "SELECT `{$field}` FROM `{$table}` LIMIT 1";
	$result = mysql_query($cmd);
	$val = mysql_field_len($result, 0);
	
	return $val;
}

?>