<?php

// fix
require_once(dirname(dirname(__FILE__)) . '/demo/nexus.php');
require_once(dirname(__FILE__) . '/tiletree.php');


$id = '53690cd38fee9';

$data = file_get_contents('../demo/tiles/' . $id . '.tre');

$obj = parse_nexus($data);


//echo $treestring;
//exit();

make_tiles($obj->tree->newick, '../demo/tiles', $id );

?>