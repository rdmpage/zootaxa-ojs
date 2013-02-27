<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/wostoxml.php');

function main()
{
	$display_form = true;	

	// Handle file upload
	if (isset($_FILES['uploadedfile']))
	{
		$display_form = false;

		//print_r($_FILES);
		
		if ($_FILES["uploadedfile"]["error"] > 0)
		{
			echo "Return Code: " . $_FILES["uploadedfile"]["error"];
		}
		else
		{
			$download = false;
			if (isset($_POST['download']) && ($_POST['download'] == 'download'))
			{
				$download = true;
			}
		
			$filename = "tmp/" . $_FILES["uploadedfile"]["name"];
			move_uploaded_file($_FILES["uploadedfile"]["tmp_name"], $filename);
			
			$info = pathinfo($filename);
			
			$xml_filename = $info['filename'] . '.xml';
			
			$xml = wos2xml($filename);
			
			header("Content-type: application/xml");
			
			if ($download)
			{
				header('Content-Disposition: attachment; filename="' . $xml_filename . '"');
			}

			echo $xml;

		}
	}
	
	if ($display_form)
	{
$html = <<<EOT
<!DOCTYPE html>
	<html>
        <head>
            <meta charset="utf-8"/>
			<style type="text/css">
			  body {
				margin: 20px;
				font-family:sans-serif;
			  }
			</style>
            <title>Zootaxa OJS Conversion</title>
        </head>
		<body>
			<h1>Zootaxa OJS Conversion</h1>
			
			<h2>Web of Science (Endnote)</h2>
			<p>Upload a Web of Science file in Endnote format (with abstracts if possible)</p>
			<form enctype="multipart/form-data" action="index.php" method="POST">
				Choose a file to upload: <input name="uploadedfile" type="file" /><br />
				<input type="checkbox" name="download" value="download"  /> Download XML<br />
				<br />
				
				<input type="submit" value="Upload File" /><br />
			</form>
		</body>
	</html>
EOT;

echo $html;

	}
}

main();

?>