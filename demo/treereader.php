<?php

// Parse data, extract a tree decsription, and check it is valid


require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/nexus.php');
require_once(dirname(dirname(__FILE__)) . '/tilemaker/tree.php');


define ('TREEDIR', dirname(__FILE__) . '/tiles');

define ('STATUS_OK', 		200);
define ('STATUS_NO_TREE', 	404);
define ('STATUS_BAD_TREE', 	403);

function main()
{
	$display_form = true;
	
	$result = new stdclass;
	$result->status = STATUS_NO_TREE;
	$have_data = false;
	
	$result->tree_id = uniqid();
	$result->treefilename = TREEDIR . '/' . $result->tree_id. '.tre';
	
	//----------------------------------------------------------------------------------------------
	// User has pasted in a tree
	if (isset($_POST['tree']))
	{
		$display_form = false;
		
		$tree = $_POST['tree'];
		$tree = stripcslashes($tree);
		
		// save tree
		file_put_contents ($result->treefilename, $tree);
		
		$have_data = true;
	}	
	
	//----------------------------------------------------------------------------------------------
	// User has uploaded a file
	if (isset($_FILES['uploadedfile']))
	{		
		if ($_FILES["uploadedfile"]["error"] > 0)
		{
			echo "Return Code: " . $_FILES["uploadedfile"]["error"];
		}
		else
		{		
			move_uploaded_file($_FILES["uploadedfile"]["tmp_name"], $result->treefilename);
			$have_data = true;
		}
		
		//echo "uploaded tree";
	}
	
	//----------------------------------------------------------------------------------------------
	if (isset($_GET['url']))
	{
		$tree = get($_GET['url']);
		
		if ($tree != '')
		{
			$display_form = false;
			file_put_contents ($result->treefilename, $tree);
			$have_data = true;
		}
		
		//echo "fetched tree";

	}
	
	
	
	//----------------------------------------------------------------------------------------------
	if ($have_data)
	{
		$result->status = STATUS_BAD_TREE;
	
		// read...
		$data = file_get_contents($result->treefilename);
		
		$treestring = '';
		
		$translate = array();
		
		// is it NEXUS?		
		$format = '';
		
		if (preg_match('/^#nexus/i', $data))
		{
			$format = 'nexus';
			$obj = parse_nexus($data);
			
			if (isset($obj->tree))
			{
				// do we have a tree?
				if (isset($obj->tree->newick)) 
				{
					$treestring = $obj->tree->newick;
				}
			}
		}
		if (preg_match('/^\(/', $data))
		{
			$format = 'newick';
			$treestring = $data;
		}
		
		//------------------------------------------------------------------------------------------
		// Make sure tree can be read
		// Parse tree and make it pretty by ordering it
		
		$parser_state = 0;
		
		$t = new Tree();
		if ($format == 'newick')
		{
			$parser_state = $t->ParseHarder($treestring);
		}
		else
		{
			// nexus
			$parser_state = $t->Parse($treestring);	
		}
		
		if ($parser_state == 100)
		{
			$result->status = STATUS_OK;
		}
		
		// dump tree
		
		//echo "<b>Parser $parser_state</b><br />";
		//echo "<b>Status $status</b><br />";
		header("Content-type: text/plain");
		echo json_format(json_encode($result));
		
	}


	// debugging
	if ($display_form)
	{
			
	
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
            <title>Tree Reader</title>
        </head>
		<body>
			<h1>Tree Reader</h1>
				<div>
					<h2>Upload a tree to display</h2>
					
					<p>For now tree must be NEXUS or Newick format and have branch lengths</p>
					
					<h3>Fetch tree from the web</h3>
					
					<form method="get" action="treereader.php">
						<input id="url" name="url" size="60"></input>
						<input type="submit" value="Display tree"></input>
					</form>

					
					<h3>Paste in a tree description</h3>
						<form method="post" action="treereader.php">
							<textarea id="tree" name="tree" rows="10" cols="60"></textarea>
							<br />
							<input type="submit" value="Display tree"></input>
						</form>					
					
					
					<h3>Upload a tree file</h3>
						<form enctype="multipart/form-data" action="treereader.php" method="POST">
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