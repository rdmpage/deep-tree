<?php

// fix
require_once(dirname(dirname(__FILE__)) . '/demo/nexus.php');
require_once(dirname(__FILE__) . '/tiletree.php');


$id = '53690cd38fee9';

$data = file_get_contents('../demo/tiles/' . $id . '.tre');

$treestring = '';

// is it NEXUS?

if (preg_match('/^#nexus/i', $data))
{
	$obj = parse_nexus($data);
	
	$treestring = $obj->tree->newick;
}
else
{
	$treestring = $data;
}



make_tiles($treestring, '../demo/tiles', $id );

?>