<?php

// Clipping

// Much of this code is adapted from Haowei Hsieh's pages at
// http://www.cc.gatech.edu/grads/h/Hao-wei.Hsieh/Haowei.Hsieh/mm.html#sec2_3
// (see also http://en.wikipedia.org/wiki/Line_clipping)
// The goal is to compute whether we need to draw a line in a clip rectangle

define ('regionTop',		1);
define ('regionLeft',		8);
define ('regionRight',	4);
define ('regionBottom',	2);


//--------------------------------------------------------------------------------------------------
/**
 * @brief Encapsulate a rectangle
 *
 */class Rectangle
{
	var $x;
	var $y;
	var $w;
	var $h;
	

	function Rectangle($x=0, $y=0, $w=0, $h=0)
	{
		$this->x = $x;
		$this->y = $y;
		$this->w = $w;
		$this->h = $h;
	}
	
	function Dump()
	{
		echo $this->x . ' ' . $this->y . ' ' . $this->w . ' ' . $this->h . "\n";
	}
	
	function GetLeft() { return $this->x; }
	function GetRight() { return ($this->x + $this->w); }
	function GetTop() { return $this->y; }
	function GetBottom() { return ($this->y + $this->h); }
	
	function Inflate($dx=0, $dy=0)
	{
		$this->x -= $dx;
		$this->w += (2 * $dx);

		$this->y -= $dy;
		$this->h += (2 * $dy);

	}
	
	
}




function OutCode($pt, $r)
{
	$code = 0;
	
	if ($pt['x'] > $r->GetRight())
	{
		$code = regionRight;
	}
	else if ($pt['x'] < $r->GetLeft())
	{
		$code = regionLeft;
	}
	if ($pt['y'] < $r->GetTop())
	{
		$code |= regionTop;
	}
	else if ($pt['y'] > $r->GetBottom())
	{
		$code |= regionBottom;
	}
	
	return $code;
}

function CohenSutherlandLineClipAndDraw (&$pt0, &$pt1, $r)
{
	$accept = false;
	$done = false;
	
	$outcode0 = OutCode($pt0, $r);
	$outcode1 = OutCode($pt1, $r);
	
	//echo "outcode0=$outcode0\n";
	//echo "outcode1=$outcode1\n";
	
	do {
		if (($outcode0 == 0) && ($outcode1 == 0))
		{
			// Trival accept (line is contained entirely within clip rectangle
			$accept = true;
			$done = true;
		}
		else if (($outcode0 & $outcode1) != 0)
		{
			// Trivial reject (line is entirely outside clip rectangle)
			$done = true;
		}
		else
		{
			// Failed both tests, so calculate the line segment to clip
			// from an outside point to an intersection with clip edge.
			
			$outcodeOut = 0;

			// At least one endpoint is outside clip rectangle, pick it.
			if ($outcode0 != 0)
			{
				$outcodeOut = $outcode0;
			}
			else
			{
				$outcodeOut = $outcode1;
			}			
			
			if ((regionTop & $outcodeOut) != 0)
			{
			  $x = $pt0['x'] + ($pt1['x'] - $pt0['x']) * ($r->GetTop() - $pt0['y']) / ($pt1['y'] - $pt0['y']);
			  $y = $r->GetTop();
			}
			else if ((regionBottom & $outcodeOut) != 0)
			{
			  $x = $pt0['x'] + ($pt1['x'] - $pt0['x']) * ($r->GetBottom() - $pt0['y']) / ($pt1['y'] - $pt0['y']);
			  $y = $r->GetBottom();
			}
			else if ((regionRight & $outcodeOut) != 0)
			{
			  $y = $pt0['y'] + ($pt1['y'] - $pt0['y']) * ($r->GetRight() - $pt0['x']) / ($pt1['x'] - $pt0['x']);
			  $x = $r->GetRight();
			}
			else if ((regionLeft & $outcodeOut) != 0)
			{
			  $y = $pt0['y'] + ($pt1['y'] - $pt0['y']) * ($r->GetLeft() - $pt0['x']) / ($pt1['x'] - $pt0['x']);
			  $x = $r->GetLeft();
			}
			if ($outcodeOut == $outcode0)
			{
				$pt0['x'] = $x;
				$pt0['y'] = $y;
				$outcode0 = OutCode ($pt0, $r);
			}
			else
			{
				$pt1['x'] = $x;
				$pt1['y'] = $y;
				$outcode1 = OutCode ($pt1, $r);
			}
		}	
	} while (!$done);
	
	return $accept;
}

// test
if (0)
{
	$pt0['x'] = 0;
	$pt0['y'] = 0;
	
	$pt1['x'] = 100;
	$pt1['y'] = 100;
	
	print_r($pt0);
	print_r($pt1);
	
	
	$r = new Rectangle(10,10,50,50);
	
	
	CohenSutherlandLineClipAndDraw($pt0, $pt1, $r);
	
	print_r($pt0);
	print_r($pt1);
	print_r($r);
}

?>