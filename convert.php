<?php

// convert Zoo Rec Endnote dump to RIS

require_once(dirname(__FILE__) . '/endnote.php');

function convert($reference)
{
	print_r($reference);
}


$filename = '';
if ($argc < 2)
{
	echo "Usage: convert.php <RIS file> <mode>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
}


$file = @fopen($filename, "r") or die("couldn't open $filename");
fclose($file);

import_endnote_file($filename, 'convert');


?>