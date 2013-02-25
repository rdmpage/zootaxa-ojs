<?php

// Import Mendeley references and create various outputs
require_once(dirname(__FILE__) . '/config.inc.php');
require_once(dirname(__FILE__) . '/endnote.php');

$implementation = new DOMImplementation();

$dtd = $implementation->createDocumentType('issues', '',
	'native.dtd');

$ojs = $implementation->createDocument('', '', $dtd);
$ojs->encoding = 'UTF-8';
$issues = $ojs->appendChild($ojs->createElement('issues'));
$section = null;

$current_volume = '';

//--------------------------------------------------------------------------------------------------
function add_article(&$ojs, &$section, $reference)
{
	$article = $section->appendChild($ojs->createElement('article'));
	$article->setAttribute('language', 'en');
	
	$title = $article->appendChild($ojs->createElement('title'));
	$title->setAttribute('locale', 'en_US');
	$title->appendChild($ojs->createTextNode($reference->title));

	$abstract = $article->appendChild($ojs->createElement('abstract'));
	$abstract->setAttribute('locale', 'en_US');
	
	if (isset($reference->abstract))
	{
		$abstract->appendChild($ojs->createTextNode($reference->abstract));
	}

	$indexing = $article->appendChild($ojs->createElement('indexing'));
	
	$discipline = $indexing->appendChild($ojs->createElement('discipline'));
	$discipline->setAttribute('locale', 'en_US');
	$discipline->appendChild($ojs->createCDATASection(''));			
	
	if (isset($reference->keywords))
	{
		$subject = $indexing->appendChild($ojs->createElement('subject'));
		$subject->setAttribute('locale', 'en_US');
		$subject->appendChild($ojs->createTextNode(join("; ", $reference->keywords)));			

		$subject_class = $indexing->appendChild($ojs->createElement('subject_class'));
		$subject_class->setAttribute('locale', 'en_US');
		$subject_class->appendChild($ojs->createTextNode($reference->keywords[0]));						
	}
	else
	{
		$subject = $indexing->appendChild($ojs->createElement('subject'));
		$subject->setAttribute('locale', 'en_US');
		$subject->appendChild($ojs->createCDATASection(''));			

		$subject_class = $indexing->appendChild($ojs->createElement('subject_class'));
		$subject_class->setAttribute('locale', 'en_US');
		$subject_class->appendChild($ojs->createCDATASection(''));						
	}

	$author_count = 0;
	foreach ($reference->author as $an_author)
	{
		$author = $article->appendChild($ojs->createElement('author'));
		
		if ($author_count == 0)
		{
			$author->setAttribute('primary_contact', 'true');
		}
		else
		{
			$author->setAttribute('primary_contact', 'false');				
		}
		
		$firstname = $author->appendChild($ojs->createElement('firstname'));
		$firstname->appendChild($ojs->createTextNode($an_author->firstname));
		
		if (isset($an_author->middlename))
		{
			$middlename = $author->appendChild($ojs->createElement('middlename'));
			$middlename->appendChild($ojs->createTextNode($an_author->middlename));		
		}

		$lastname = $author->appendChild($ojs->createElement('lastname'));
		$lastname->appendChild($ojs->createTextNode($an_author->lastname));				

		$email = $author->appendChild($ojs->createElement('email'));
		$email->appendChild($ojs->createTextNode('user@example.com'));				
		
		$author_count++;
	}

	$pages = $article->appendChild($ojs->createElement('pages'));
	$pages->appendChild($ojs->createTextNode(str_replace('--', '-', $reference->journal->pages)));

	$date_published = $article->appendChild($ojs->createElement('date_published'));
	if (isset($reference->date))
	{
		$date_published->appendChild($ojs->createTextNode($reference->date));
	}
	else
	{
		$date_published->appendChild($ojs->createCDATASection(''));
	}
	
}

//--------------------------------------------------------------------------------------------------
function add_issue(&$ojs, &$issues, &$reference)
{
	$issue = $issues->appendChild($ojs->createElement('issue'));
	$issue->setAttribute('current', 'false');
	$issue->setAttribute('identification', 'title');
	$issue->setAttribute('public_id', '');
	$issue->setAttribute('published', 'true');
	
	// Issue DOI
	$doi = '10.11646/zootaxa.' . $reference->journal->volume . '.1';
	$id = $issue->appendChild($ojs->createElement('id'));
	$id->setAttribute('type', 'doi');
	$id->appendChild($ojs->createTextNode($doi));

	if (isset($reference->date))
	{
		$issue_title = date('j M. Y', strtotime($reference->date));
	}
	else
	{
		$issue_title = 'PublishDate';
	}
	$title = $issue->appendChild($ojs->createElement('title'));
	$title->setAttribute('locale', 'en_US');
	$title->appendChild($ojs->createTextNode($issue_title));
	
	// Volume
	$volume = $issue->appendChild($ojs->createElement('volume'));
	$volume->appendChild($ojs->createTextNode($reference->journal->volume));

	// Pre 2013 this is always 1
	$number = $issue->appendChild($ojs->createElement('number'));
	$number->appendChild($ojs->createTextNode('1'));

	$year = $issue->appendChild($ojs->createElement('year'));
	$year->appendChild($ojs->createTextNode($reference->year));
	
	$date_published = $issue->appendChild($ojs->createElement('date_published'));
	if (isset($reference->date))
	{
		$date_published->appendChild($ojs->createTextNode($reference->date));
	}
	else
	{
		$date_published->appendChild($ojs->createCDATASection(''));
	}				
	
	$section = $issue->appendChild($ojs->createElement('section'));
	$title = $section->appendChild($ojs->createElement('title'));
	$title->setAttribute('locale', 'en_US');
	$title->appendChild($ojs->createTextNode('Articles'));
	
	return $section;
}

//--------------------------------------------------------------------------------------------------
function convert($reference)
{
	global $ojs;
	global $issues;
	global $current_volume;
	global $section;
	
	//print_r($reference);
	
	
	if (isset($reference->journal->issue) && !isset($reference->journal->volume))
	{
		$reference->journal->volume = $reference->journal->issue;
		unset ($reference->journal->issue);
	}
	
	// OJS xml
	if ($reference->journal->volume != $current_volume)
	{
		$section = add_issue($ojs, $issues, $reference);
	
		$current_volume = $reference->journal->volume;
	}
	
	
	add_article($ojs, $section, $reference);
	
}

//--------------------------------------------------------------------------------------------------
function wos2xml($filename)
{
	global $ojs;
	
	$file = @fopen($filename, "r") or die("couldn't open $filename");
	fclose($file);

	import_endnote_file($filename, 'convert');
	
	return $ojs->saveXML();
}

?>