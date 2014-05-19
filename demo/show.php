<?php

$id = '';
$max_zoom = 0;

if (isset($_GET['id']))
{
	$id = $_GET['id'];
	
	// find max zoom
	
	$basedir = dirname(__FILE__) . '/tiles/' . $id;
	
	if (!file_exists($basedir))
	{
		$id = '';
	}
	else
	{
		$max_zoom = 0;
		while (file_exists($basedir . '/tile-' . $max_zoom . '-0.svg'))
		{
			$max_zoom++;
		}
		$max_zoom--;
	}
}

if ($id == '')
{
	echo '<html>Oops, no tree id</html>';
	exit();
}

?>
<!DOCTYPE html>
<html>
<!--

	
Code heavily influenced by Michal Migurski's Giant-Ass Image Viewer 
http://mike.teczno.com/giant/pan/

Ideas about world coordinates came from Google Maps API 
http://code.google.com/apis/maps/documentation/javascript/maptypes.html#WorldCoordinates
-->
<head>
	<meta charset="utf-8" />
	<title>Deep tree viewer</title>
	
<style type="text/css" title="text/css">

body {
	margin:10px;
	padding:10px;
	font-family: sans-serif;
	/* http://www.smilingsouls.net/Blog/20110804114957.html */
	overflow: hidden; 
}

.explain {
	font-size:12px;
	color:rgb(64,64,64);
}

#viewer {
	position: relative;
	top: 0;
	left: 0;
	width: 512px;
	height: 600px;
}

#well { background-color:white; }

#well, #surface {
	margin: 0;
	padding: 0;
	width: 100%;
	height: 100%;
	position: absolute;
	top: 0px;
	left: 0px;
	
}
#surface {
	z-index: 20;
	_background: url(images/blank.gif) no-repeat center center; /* NOTE: required for IE to"see" the surface */
	
	cursor: -webkit-grab;
	cursor: -moz-grab;
	
}

#well {
	overflow: hidden;
	z-index: 10;
}

/* Image tile */
.tile
{
	border: 0;
	/* border: 1px solid red; */
	margin: 0;
	padding: 0;
	position: absolute;
	top: 0px;
	left: 0px;
	display: block;
}

</style>

<!-- http://www.htmlcenter.com/blog/cross-browser-semi-transparent-backgrounds/ -->
<!--[if IE]>
<style type="text/css">
.internal
{
	background:transparent;
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=#66CCDEFD,endColorstr=#66CCDEFD);
	zoom: 1;	
}
</style>
<![endif]-->	
	
<script src="js/jquery.js"></script>
	
<script>

<!-- the viewer object -->
var Viewer = {
	count: 0,
	width: 0,
	height: 0,
	zoomLevel: 0,
	maxZoomLevel: <?php echo $max_zoom; ?>,
	tileSize: 512,
	fullSize: 0,
	tiles: [],
	mouse: {'x': 0,'y': 0},
	start: {'x': 0,'y': 0},
	x: 0,
	y: 0,
	dragging: false,
	tree_id: '<?php echo $id; ?>'
}

//--------------------------------------------------------------------------------------------------
Viewer.init=function() 
{
	this.width = $('#viewer').width();
	this.height = $('#viewer').height();
	
	// Full pixel size of the image at this zoom level
    this.fullSize = this.tileSize * Math.pow(2, this.zoomLevel); 	
		
	this.prepareTiles();
	
	// top left corner of viewer w.r.t. document
	this.offset = $('#surface').offset();
	
	// Add event handlers
	
	$('#surface').mousedown(pressViewer);
	$('#surface').mouseup(releaseViewer);	

	// Support double click zoom in/out like Google Maps
	$('#surface').dblclick(zoomInOut);	
	
	// If we drag cursor outside surface release the mouse,
	// otherwise mouse appears to be "sticky"
	$('#surface').bind("mouseout",function(e){
    	releaseViewer(e);
	});
		
	// Support scrolling using mouse wheel
	// Firefox needs DOMMouseScroll
	$('#surface').bind('mousewheel DOMMouseScroll', wheel);
	
	// Prevent display of context menu that appears by default if we right-click
	// This means we can have right-click zoom-in (like Google Maps)
	// http://stackoverflow.com/a/13260934/9684
	$('#surface').bind("contextmenu", zoomInOut);
	
	/*
	$('#surface').bind("contextmenu",function(e){
    	return false;
	});
	*/
	
}

//--------------------------------------------------------------------------------------------------
// Mouse wheel event handler
wheel=function(e) {
	
	if (e.originalEvent.wheelDelta) {
		if(e.originalEvent.wheelDelta / 120 > 0) {
			Viewer.x = 0;
			Viewer.y += e.originalEvent.wheelDelta;
		} else {
			Viewer.x = 0;
			Viewer.y += e.originalEvent.wheelDelta;
		}
	} else if (e.originalEvent.detail) {
		Viewer.x = 0;
		Viewer.y += (e.originalEvent.detail * -60);
	}
	
	
	Viewer.positionTiles(false);
}


//--------------------------------------------------------------------------------------------------
// Double click event handler
zoomInOut=function(e) {
	
	console.log('zoomInOut');
	
	var x = e.pageX - Viewer.offset.left;
	var y = e.pageY - Viewer.offset.top;
	
	var mouse = {'x': this.width / 2, 'y': y };	
	
	// left click zoom in, right click zoom out
	
	var right = false;
	
	// Mac 
	if (e.ctrlKey == true) {
		right = true;
	}
	
	if (right) {
		if (Viewer.zoomLevel > 0) {
			$('#info').html('zoom level ' + (Viewer.zoomLevel - 1));		
			Viewer.zoomImage(mouse, -1);
		}
	} else {
		if (Viewer.zoomLevel < Viewer.maxZoomLevel)
		{
			$('#info').html('zoom level ' + (Viewer.zoomLevel + 1));		
			Viewer.zoomImage(mouse, 1);
		}
	}
	
	// Vital to ensure we don't show context menu
	return false;
}


//--------------------------------------------------------------------------------------------------
// User has pressed mouse on surface of viewer
pressViewer=function(e)
{
	var x = e.pageX - Viewer.offset.left;
	var y = e.pageY - Viewer.offset.top;
	Viewer.mouse.x = x;
	Viewer.mouse.y = y;
	Viewer.start.x = x;
	Viewer.start.y = y;
		
	// Add mousemove event handler
	$('#surface').mousemove(moveViewer);
	
 	// See http://stackoverflow.com/questions/2429902/firefox-drags-div-like-it-was-an-image-javascript-event-handlers-possibly-to-bla
	return false;
}

//--------------------------------------------------------------------------------------------------
// User dragging  mouse over surface
moveViewer=function(e)
{
	// We are dragging, setting this flag enables us to decide whether to update display
	// or not while it is being dragged
	Viewer.dragging = true;

	// x and y are relative to top left of viewer
	var x = e.pageX - Viewer.offset.left;
	var y = e.pageY - Viewer.offset.top;
	Viewer.mouse.x = x;
	Viewer.mouse.y = y;

	// Move tiles to appropriate position
 	Viewer.positionTiles(false);	
 	
 	// See http://stackoverflow.com/questions/2429902/firefox-drags-div-like-it-was-an-image-javascript-event-handlers-possibly-to-bla
 	return false;
}

//--------------------------------------------------------------------------------------------------
// User has released mouse
releaseViewer=function(e)
{
	// Unbind mousemove handler
	$('#surface').unbind('mousemove', moveViewer);
	
	if (Viewer.dragging)
	{
		// We've stopped dragging, update display
		Viewer.dragging = false;
		
		Viewer.x = 0; //(Viewer.mouse.x - Viewer.start.x);
		Viewer.y += (Viewer.mouse.y - Viewer.start.y);
	}
	else
	{	
		// User has clicked, not dragged, so locate the element (if any) the user clicked on.
		// To do this we hide the "surface", locate the element, then show the surface again
		// (so that it receives mouse events again).
		// For background see:		
		// http://stackoverflow.com/a/10623558/9684 for hide and show surface
		
		$('#surface').hide();
		
		// Locate element (if any) that user clicked on
		var hit = $(document.elementFromPoint(e.clientX,e.clientY));
		if (hit) {
			// do stuff
			
			// Find out what sort of nde we hit
			// For example, 'text' is an SVG text element
			// need to check that nodeName is defined, otherwise 
			// if we swithc to another window then come back the viewer is not responsive
			if ((typeof $(hit)[0].nodeName != undefined) && ($(hit)[0].nodeName == 'text')) {
			
				// Which tile has the hit?
        		var tile = null;
        		
        		// Position of click relative to viewer
        		var clicked_y = e.clientY - Viewer.offset.top;
        
        		// which tile contains event?
        		for (var i in Viewer.tiles) {
        			if ((clicked_y > Viewer.tiles[i].y) && (clicked_y < (Viewer.tiles[i].y + Viewer.tileSize))) {
	        			tile = Viewer.tiles[i].row;
        			}
	    		}
	    					
				// Do something with text itself
				$('#info').html( $(hit).text() );
			}
			
		}
		
		$('#surface').show();
	}
	
 	// See http://stackoverflow.com/questions/2429902/firefox-drags-div-like-it-was-an-image-javascript-event-handlers-possibly-to-bla
	return false;
}


//--------------------------------------------------------------------------------------------------
// Position the image tiles
//
// There's a problem here if the tile size is not a divisor of the viewer size, the non-visible 
// tile bounces from top to bottom of list
// 
Viewer.positionTiles=function(force_drawing)
{
	var n = this.tiles.length;
	for (var r = 0; r < n; r++)
	{
 		var tile = this.tiles[r];
 		 				
        tile.y = (tile.row * this.tileSize) + this.y + (this.mouse.y - this.start.y);
        
        // Is tile visible?
        var visible = false;
        if (tile.y >= 0 && tile.y <= this.height) {
        	visible = true;
        }
        if ((tile.y + this.tileSize) >= 0 && (tile.y + this.tileSize) <= this.height) {
        	visible = true;
        }
        
        
        if (!visible) {
        	if (tile.y > this.height) {
				// shift it to very top
				do {
					tile.row -= n;
					tile.y =(tile.row * this.tileSize) + this.y + (this.mouse.y - this.start.y);
				} while(tile.y > this.height);
			}
			else
			{
				// tile may be too far up
				// if it is, shift it to the very bottom until it's within the viewer window
				while (tile.y < (-1 * this.tileSize))
				{
					tile.row += n;
					tile.y = (tile.row * this.tileSize) + this.y + (this.mouse.y - this.start.y);
				}
			}
		}
		
		if (force_drawing) {
			tile.draw = true;
		} else {
			tile.draw = visible && !tile.visible;
		}
		tile.visible = visible;
		
		
		this.setTileImage(tile);
		                
        // Set top of tile, this moves it to the correct position
        tile.img.style.top = tile.y + 'px';
 	}   
}

//--------------------------------------------------------------------------------------------------
// Create tile elements in DOM
// Call this when viewer is first created, and when zoom level changes
Viewer.prepareTiles=function()
{
    var rows = Math.ceil(this.height / this.tileSize) + 1;
    var cols = 1;
    
	for (var r = 0; r < rows; r++)
	{
		var tile = {'y': 0, 'id': r, 'visible' : false, 'draw' : true, 'row': r, 'img': document.createElement('div')};

		tile.img.className = 'tile';
		tile.img.style.width = this.tileSize+'px';
		tile.img.style.height = this.tileSize+'px';
		
		this.setTileImage(tile, false);
		
		$('#well').append(tile.img);
		this.tiles.push(tile);
	}    
    this.positionTiles(true);
}

//--------------------------------------------------------------------------------------------------
// Set the image to be shown by a tile
Viewer.setTileImage=function(tile)
{
    // request a particular image slice
    
    if (tile.draw) 
    {    
		var src = 'tiles/' + this.tree_id + '/tile-' + this.zoomLevel + '-' + tile.row + '.svg';
		
		var high = tile.row < 0;
		var low = tile.row >= Math.pow(2, this.zoomLevel);
		var outside = high || low;
		if(outside)          { src = 'images/null.svg';          }		
				
		$.ajax({
				 url:    src,
				 async:   false
		}).done(function(data) {
			var svgNode = $("svg", data);
				var docNode = document.adoptNode(svgNode[0]);
				$(tile.img).html(docNode);
			});  					
	}

}

//--------------------------------------------------------------------------------------------------
// Handle change in zoom level
Viewer.zoomImage = function(mouse, direction)
{
	if(mouse == undefined) 
	{
		var mouse = {'x': this.width / 2, 'y': this.height / 2};
	}
	
	var pos = {'before': {'x': 0, 'y': 0}};

    // pixel position within the image is a function of the
    // upper-left-hand corner of the viewer in the page (pos.before),
    // the click position (event), and the image position within
    // the viewer (dim).
    pos.before.x = (mouse.x - pos.before.x) - this.x;
    pos.before.y = (mouse.y - pos.before.y) - this.y;
    pos.before.height = Math.pow(2, this.zoomLevel) * this.tileSize;
	
   if(this.zoomLevel + direction >= 0) 
   {
     	pos.after = {'height': (pos.before.height * Math.pow(2, direction))};        
        pos.after.x = pos.before.x ;
        pos.after.y = pos.before.y * Math.pow(2, direction);
 
        pos.after.left = mouse.x - pos.after.x;
        pos.after.top = mouse.y - pos.after.y;		
		
  		this.x = pos.after.left;
        this.y = pos.after.top;
        this.zoomLevel += direction;
        
        this.start.x = this.mouse.x = 0;
        this.start.y = this.mouse.y = 0;
        this.positionTiles(true);
    }
}


//--------------------------------------------------------------------------------------------------
Viewer.zoomImageUp = function(mouse)
{
	if (this.zoomLevel < this.maxZoomLevel)
	{
		$('#info').html('zoom level ' + (this.zoomLevel + 1));
		this.zoomImage(mouse, 1);
	}
}

//--------------------------------------------------------------------------------------------------
Viewer.zoomImageDown = function(mouse)
{
	if (this.zoomLevel > 0)
	{
		$('#info').html('zoom level ' + (this.zoomLevel - 1));
		this.zoomImage(mouse, -1);
	}
}

</script>
	
	
</head>
<body>
	<a href=".">Home</a>
	<h1>Display tree</h1>
	<div>
		<p class="explain">Double click to zoom in, right click (ctrl-click on Mac) to zoom out, 
		click and drag, or use mouse wheel to scroll. Click on a node for information.</p>
	</div>
	
	<div style="position:absolute;left:650px;" id="info"></div>
	

	<div style="position:relative;width:512px;height:600px;left:100px;top:0px;border:1px solid rgb(192,192,192);">
		
			<!-- zoom in/out controls -->
			<div style="position:absolute;top:0px;left:0px;width:auto;z-index:100">
				<button onclick="Viewer.zoomImageUp(null);"  "tabindex="-1">+</button>
				<button onclick="Viewer.zoomImageDown(null);" "tabindex="-1">-</button>
			</div>

			<!-- viewer -->
			<div id="viewer">
				
				<!-- surface is what user interacts with -->
				<div id="surface"></div>
				
				<!-- well contains the tile images -->
				<div id="well"></div>
					
			</div>
			<!-- end of viewer -->
	</div>


<script>
	Viewer.init();
</script>

</body>
</html>
