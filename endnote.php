<?php

/**
 * @file endnote.php
 *
 */

// Parse EndNote format from Zoological Records and try and find first page of article in BHL

require_once (dirname(__FILE__) . '/nameparse.php');

$debug = true;

$key_map = array(
	'UT' => 'publisher_id',
	'TI' => 'title',
	'SN' => 'issn',
	'SO' => 'name',
	'VL' => 'volume',
	'IS' => 'issue',
	'BP' => 'spage',
	'EP' => 'epage',
	'AB' => 'abstract',
	'PY' => 'year'
	);
	
//--------------------------------------------------------------------------------------------------
function process_endnote_key($key, $value, &$obj)
{
	global $key_map;
	global $debug;
	
	//echo $key . "\n";
	
	switch ($key)
	{
		case 'AU':
			// Ignore as we handle this in main loop
			break;
	
		case 'SO':
			$obj->journal->$key_map[$key] = $value;
			break;
			
		case 'TI':
			$value = str_replace("“", "\"", $value);
			$value = str_replace("”", "\"", $value);
			$value = rtrim($value, '.');
			$obj->$key_map[$key] = $value;
			break;
			
		case 'PS':
			$value = preg_replace('/^pp. /', '', $value);
			$parts = explode('-', $value);			
			$obj->journal->pages = $parts[0] . '--' .  $parts[1];
			break;	
			
		case 'BP':
			$obj->journal->pages = $value;
			break;

		case 'EP':
			$obj->journal->pages .= '--' . $value;
			break;
			
		case 'VL':
		case 'IS':
			if (array_key_exists($key, $key_map))
			{
				// Only set value if it is not empty
				if ($value != '')
				{
					$obj->journal->$key_map[$key] = $value;
				}
			}
			break;
			
		case 'SN':
			//$obj->journal->identifier 
			break;
			
		case 'PD':
			$date = $value;
			if (strtotime($date) === false)
			{
			}
			else
			{
				$obj->date = date('Y-m-d', strtotime($date));
			}
			
			break;
			
		default:
			if (array_key_exists($key, $key_map))
			{
				// Only set value if it is not empty
				if ($value != '')
				{
					$obj->$key_map[$key] = $value;
				}
			}
			break;
	}
}

//--------------------------------------------------------------------------------------------------
function add_author(&$obj, $authorstring)
{	
	$matched = false;
	
	$a = null;
	
	// Get parts of name
	$parts = parse_name($authorstring);
	
	$a = new stdClass();
	
	if (isset($parts['last']))
	{
		$a->lastname = $parts['last'];
	}
	if (isset($parts['suffix']))
	{
		$a->suffix = $parts['suffix'];
	}
	if (isset($parts['first']))
	{
		$a->firstname = $parts['first'];
		
		if (array_key_exists('middle', $parts))
		{
			$a->middlename .= ' ' . $parts['middle'];
		}
	}
	
	// Handle initials with no separator
	if (preg_match('/^([A-Z]+)$/Uu', $a->firstname))
	{
		$str = $a->firstname;			
		
		if (function_exists('mb_strlen'))
		{
			$len = mb_strlen($str);		
			$parts = array();
			for($i=0;$i<$len;$i++)
			{
				$parts[] = mb_substr($str,$i,1);
			}
			$a->firstname = $parts[0] . '.';
		}
		else
		{
			$len = strlen($str);		
			$parts = array();
			for($i=0;$i<$len;$i++)
			{
				$parts[] = substr($str,$i,1);
			}
			$a->firstname = $parts[0] . '.';		
		}
		
		if ($len > 1)
		{
			array_shift($parts);
			$a->middlename = trim(join(". ", $parts) . '.');
		}
	}		
	
	
	// Add periods if missing
	if (isset($a->firstname))
	{
		if (preg_match('/^\w$/Uu', $a->firstname))
		{
			$a->firstname .= '.';
		}
	}
	
	/*
	if (isset($a->firstname))
	{
		if (preg_match('/^\w$/Uu', $a->firstname))
		{
			$a->firstname .= '.';
		}
	}
	*/
	
	
	if ($a)
	{
		$obj->author[] = $a;
	}
}


//--------------------------------------------------------------------------------------------------
// Use this function to handle very large RIS files
function import_endnote_file($filename, $callback_func = '')
{
	global $debug;
	
	$file_handle = fopen($filename, "r");
			
	$state = 1;	
	
	$current_key = '';
	$current_value = '';
	
	while (!feof($file_handle)) 
	{
		$line = fgets($file_handle);
		
		//echo $line . "\n";
		
		if (preg_match('/^(?<key>[A-Z]{2})\s(?<value>.*)$/Uu', $line, $m))
		{
			//print_r($m);
			$current_key 	= $m['key'];
			$current_value 	= $m['value'];
			
			if ($current_key == 'AU')
			{
				add_author($obj, $m['value']);
			}
			
		}
		if (preg_match('/^ER$/Uu', $line))
		{
			$current_key = 'ER';
		}
		if (preg_match('/^   (?<value>["|\(]?\w+(.*))$/Uu', $line, $m))
		{
			if ($current_key == 'AU')
			{
				add_author($obj, $m['value']);
			}
			else
			{
				$current_value .= ' ' . $m['value'];
			}
		}
				
		if ($current_key == 'PT')
		{
			$state = 1;
			$obj = new stdClass();
			$obj->author = array();
			
			if ('J' ==  $current_value)
			{
				$obj->type = 'article';
				$obj->journal = new stdclass;
			}
		}
		if ($current_key == 'ER')
		{
			$state = 0;
			
			if (isset($obj->journal->issue))
			{
				$obj->journal->issue = preg_replace('/pt\.\s+/', '', $obj->journal->issue);
				$obj->journal->issue = preg_replace('/^\(/', '', $obj->journal->issue);
				$obj->journal->issue = preg_replace('/\)$/', '', $obj->journal->issue);
			}
						
			// Cleaning...						
			if (0)
			{
				print_r($obj);
			}	
			
			if ($callback_func != '')
			{
				$callback_func($obj);
			}
			
			$current_key = '';
			$current_value = '';
			
		}
		
		if ($state == 1)
		{
			if ($current_value != '')
			{
				process_endnote_key($current_key, $current_value, $obj);
			}
		}
	}
	
	
}

// test

//import_endnote_file('ZooRecord/savedrecs.txt');


?>