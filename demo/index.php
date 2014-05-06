<?php

// http://stackoverflow.com/questions/353803/redirect-to-specified-url-on-php-script-completion
ob_start();

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/nexus.php');
require_once(dirname(dirname(__FILE__)) . '/tilemaker/tiletree.php');


define ('TREEDIR', dirname(__FILE__) . '/tiles');

function main()
{
	
	$display_form = true;	
	
	$tree_id = '';
	$treefilename = '';
	
	//----------------------------------------------------------------------------------------------
	// User has pasted in a tree
	if (isset($_POST['tree']))
	{
		$display_form = false;
		
		$tree = $_POST['tree'];
		$tree = stripcslashes($tree);
		
		// save tree
		$tree_id = uniqid();
		$treefilename = TREEDIR . '/' . $tree_id . '.tre';
		
		file_put_contents ($treefilename, $tree);
		
		echo "pasted a tree";
		

	}	
	
	//----------------------------------------------------------------------------------------------
	// User has uploaded a file
	if (isset($_FILES['uploadedfile']))
	{
		$display_form = false;
		
		if ($_FILES["uploadedfile"]["error"] > 0)
		{
			echo "Return Code: " . $_FILES["uploadedfile"]["error"];
		}
		else
		{		
			$tree_id = uniqid();
			$treefilename = TREEDIR . '/' . $tree_id . '.tre';
			
			move_uploaded_file($_FILES["uploadedfile"]["tmp_name"], $treefilename);
		}
		
		echo "uploaded tree";
	}
	


	//----------------------------------------------------------------------------------------------
	if (isset($_GET['url']))
	{
		

		$tree = get($_GET['url']);
		
		if ($tree != '')
		{
			$display_form = false;
		
			$tree_id = uniqid();
			$treefilename = TREEDIR . '/' . $tree_id . '.tre';
		
			file_put_contents ($treefilename, $tree);
		}
		
		echo "fetched tree";

	}
	
	
	
	//----------------------------------------------------------------------------------------------
	if ($treefilename != '')
	{
		// read...
		$data = file_get_contents($treefilename);
		
		// is it NEXUS?
		
		
		$obj = parse_nexus($data);
	
		//print_r($obj);
		
		// make tiles and tell where to get result....
		
		echo $tree_id;
		
		make_tiles($obj->tree->newick, TREEDIR, $tree_id);
		
		// clear out the output buffer
		while (ob_get_status()) 
		{
			ob_end_clean();
		}
		
		// no redirect
		header( "Location: show.php?id=$tree_id" );		
		
	
	
	}


	if ($display_form)
	{
	
		$treelist = array();
		$files = scandir(TREEDIR);
		foreach ($files as $file)
		{
			if (preg_match('/\.tre$/', $file))
			{	
				$treelist[] = str_replace('.tre', '', $file);
			}
		}
		
	
echo '<!DOCTYPE html>
	<html>
        <head>
            <meta charset="utf-8"/>
			<style type="text/css">
			  body {
				margin: 20px;
				font-family:sans-serif;
			  }
			</style>
            <title>Deep tree viewer</title>
        </head>
		<body>
			<h1>Deep tree viewer</h1>
				<div>
					<p>A simple Google Maps-inspired large tree viewer.</p>
					<p>Examples</p>';
					
					echo '<div style="height:100px;overflow:auto;">';
					foreach ($treelist as $t)
					{
						echo '<a href="show.php?id=' . $t . '">' . $t . '</a><br />';
					}
					echo '</div>';
					
					
echo '
				</div>
				
				<div>
					<h2>Upload a tree to display</h2>
					
					<h3>Fetch tree from the web</h3>
					
					<form method="get" action=".">
						<input id="url" name="url" size="60"></input>
						<input type="submit" value="Display tree"></input>
					</form>

					
					<h3>Paste in a tree description</h3>
						<form method="post" action=".">
							<textarea id="tree" name="tree" rows="10" cols="60"></textarea>
							<br />
							<input type="submit" value="Display tree"></input>
						</form>					
					
					
					<h3>Upload a tree file</h3>
						<form enctype="multipart/form-data" action="index.php" method="POST">
							Choose a file to upload: <input name="uploadedfile" type="file" />
							<br />
							<input type="submit" value="Display tree" /><br />
						</form>
					
				</div>
		</body>
	</html>
';

	}
}

main();

?>