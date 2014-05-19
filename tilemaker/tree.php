<?php

define('CHILD', 0);
define ('SIB', 1);

//--------------------------------------------------------------------------------------------------
/**
 * @brief Node in a tree
 *
 * Node has pointers to child, sibling, and ancestral node, these pointers are
 * NULL if corresponding node doesn't exist. Has label as a field, all other values
 * are stored in an key-value array of attributes.
 */
class Node
{
	var $ancestor;
	var $child;
	var $sibling;
	var $label;
	var $id;
	var $attributes = array();
	var $cluster = array();
	
	//----------------------------------------------------------------------------------------------
	function __construct($label = '')
	{
		$this->ancestor = NULL;
		$this->child = NULL;
		$this->sibling = NULL;
		$this->label = $label;
		$this->cluster = array();
	}
		
	//----------------------------------------------------------------------------------------------
	function IsLeaf()
	{
		return ($this->child == NULL);
	}
	
	//----------------------------------------------------------------------------------------------
	function AddWeight($w)
	{
		$w0 = $this->GetAttribute('weight');
		$this->SetAttribute('weight', $w0 + $w);
	}
	
	//----------------------------------------------------------------------------------------------
	function Dump()
	{
		echo "---Dump Node---\n";
		echo "   Label: " . $this->label . "\n";
		echo "      Id: " . $this->id . "\n";
		echo "   Child: ";
		if ($this->child == NULL)
		{
			echo "NULL\n";
		}
		else
		{
			echo $this->child->label . "\n";
		}
		echo " Sibling: ";
		if ($this->sibling == NULL)
		{
			echo "NULL\n";
		}
		else
		{
			echo $this->sibling->label . "\n";
		}
		echo "Ancestor: ";
		if ($this->ancestor == NULL)
		{
			echo "NULL\n";
		}
		else
		{
			echo $this->ancestor->label . "\n";
		}
		echo "Attributes:\n";
		print_r($this->attributes);
		echo "Cluster:\n";
		print_r($this->cluster);
	}
	
	//----------------------------------------------------------------------------------------------
	function GetAncestor() { return $this->ancestor; }	
	
	//----------------------------------------------------------------------------------------------
	function GetAttribute($key) 
	{ 
		if (isset($this->attributes[$key]))
		{
			return $this->attributes[$key]; 
		}
		else
		{
			return NULL;
		}
	}		

	//----------------------------------------------------------------------------------------------
	function GetChild() { return $this->child; }	

	//----------------------------------------------------------------------------------------------
	function GetId() { return $this->id; }	

	//----------------------------------------------------------------------------------------------
	function GetLabel() { return $this->label; }	
	
	//----------------------------------------------------------------------------------------------
	function GetRightMostSibling()
	{
		$p = $this;
		
		while ($p->sibling)
		{
			$p = $p->sibling;
		}
		return $p;
	}


	//----------------------------------------------------------------------------------------------
	function GetSibling() { return $this->sibling; }	
	
	//----------------------------------------------------------------------------------------------
	function SetAncestor($p)
	{
		$this->ancestor = $p;
	}
	
	//----------------------------------------------------------------------------------------------
	function SetAttribute($key, $value)
	{
		$this->attributes[$key] = $value;
	}
	
	//----------------------------------------------------------------------------------------------
	function SetChild($p)
	{
		$this->child = $p;
	}
	
	//----------------------------------------------------------------------------------------------
	function SetId($id)
	{
		$this->id = $id;
	}
	
	//----------------------------------------------------------------------------------------------
	function SetLabel($label)
	{
		$this->label = $label;
	}
	

	//----------------------------------------------------------------------------------------------
	function SetSibling($p)
	{
		$this->sibling = $p;
	}
	
	//----------------------------------------------------------------------------------------------
	// Children of node (as array)
	function GetChildren()
	{
		$children = array();
		$p = $this->child;
		if ($p)
		{
			array_push($children, $p);
			$p = $p->sibling;
			while ($p)
			{
				array_push($children, $p);
				$p = $p->sibling;
			}
		}
		return $children;
	}
	
	
}


//--------------------------------------------------------------------------------------------------
class Tree
{
	var $root;
	var $num_nodes;
	var $label_to_node_map = array();
	var $nodes = array();	
	var $num_leaves;
	var $rooted = true;
	var $has_edge_lengths = false;

	//----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->root = NULL;;
		$this->num_nodes = 0;
		$this->num_leaves = 0;
	}
	
	//----------------------------------------------------------------------------------------------
	function GetNumLeaves() { return $this->num_leaves; }
	
	
	//----------------------------------------------------------------------------------------------
	function GetRoot() { return $this->root; }

	//----------------------------------------------------------------------------------------------
	function HasBranchLengths() { return $this->has_edge_lengths; }

	//----------------------------------------------------------------------------------------------
	function IsRooted() { return $this->rooted; }
	
	//----------------------------------------------------------------------------------------------
	function SetRoot($root)
	{
		$this->root = $root;
	}
	
	//----------------------------------------------------------------------------------------------
	function NodeWithLabel($label)
	{
		$p = NULL;
		if (in_array($label, $this->label_to_node_map))
		{
			$p = $this->nodes[$this->label_to_node_map[$label]];
		}
		return $p;
	}
	
	//----------------------------------------------------------------------------------------------
	function NewNode($label = '')
	{
		$node = new Node($label);
		$node->id = $this->num_nodes++;
		$this->nodes[$node->id] = $node;
		if ($label != '')
		{
			$this->label_to_node_map[$label] = $node->id;
		}
		else
		{
			/*$label = "_" . $node->id;
			$node->SetLabel($label);
			$this->label_to_node_map[$label] = $node->id;*/
		}		
		return $node;
	}
	
	//----------------------------------------------------------------------------------------------
	function Parse ($str)
	{
		$str = str_replace("(", "|(|", $str);
		$str = str_replace(")", "|)|", $str);
		$str = str_replace(",", "|,|", $str);
		$str = str_replace(":", "|:|", $str);
		$str = str_replace(";", "|;|", $str);
		$str = str_replace("||", "|", $str);
		
		$token = explode("|", $str);
		
		//print_r($token);
		
		$curnode = $this->NewNode();
		$this->root = $curnode;

		$state = 0;
		$stack = array();
		$n = count($token);
		
		$i = 1;
		while ($state < 99) // 99 is error, 100 is done
		{
			switch ($state)
			{
				case 0: // getname
					if (ctype_alnum($token[$i]{0}))
					{
						$this->num_leaves++;
						$curnode->SetLabel($token[$i]);
						$i++;
						$state = 1;
					}
					else 
					{
						if ($token[$i]{0} == "'")
						{
							$label = $token[$i];
							$label = preg_replace("/^'/", "", $label);
							$label = preg_replace("/'$/", "", $label);
							$this->num_leaves++;
							$curnode->SetLabel($label);
							$i++;
							$state = 1;
							
						}
						else
						{
							switch ($token[$i])
							{
								case '(':
									$state = 2;
									break;
								default:
									$state = 99;
									break;
							}
						}
					}
					break;
					
				case 1: // getinternode
					switch ($token[$i])
					{
						case ':':
						case ',':
						case ')':
							$state = 2;
							break;
						default:
							$state = 99;
							break;
					}
					break;
					
				case 2: // nextmove
					switch ($token[$i])
					{
						case ':':
							$i++;
							if (is_numeric($token[$i]))
							{
								$curnode->SetAttribute('edge_length', $token[$i]);
								$this->has_edge_lengths = true;
								$i++;
							}
							break;
						case ',':
							$q = $this->NewNode();
							$curnode->SetSibling($q);
							$c = count($stack);
							if ($c == 0)
							{
								$state = 99;
							}
							else
							{
								$q->SetAncestor($stack[$c - 1]);
								$curnode = $q;
								$state = 0;
								$i++;
							}
							break;							
						case '(':
							$stack[] = $curnode;
							$q = $this->NewNode();
							$curnode->SetChild($q);
							$q->SetAncestor($curnode);
							$curnode = $q;
							$state = 0;
							$i++;
							break;
						case ')':
							if (empty($stack))
							{
								$state = 99;
							}
							else
							{
								$curnode = array_pop($stack);
								$state = 3;
								$i++;
							}
							/*
							$c = count($stack);
							if ($c == 0)
							{
								$state = 99;
							}
							else
							{
								$q = $stack[$c - 1];
								$curnode = $q;
								array_pop($stack);
								$state = 3;
								$i++;
							}*/
							break;
						
						case ';':
							if (empty($stack))
							{
								$state = 100;
							}
							else
							{
								$state = 99;
							}
							break;
						
						default:
							$state = 99;
							break;
					}
					break;
				
				case 3: // finishchildren
					if (ctype_alnum($token[$i]{0}))
					{
						$curnode->SetLabel($token[$i]);
						$i++;
					}
					else
					{
						switch ($token[$i])
						{
							case ':':
								$i++;
								if (is_numeric($token[$i]))
								{
									$curnode->SetAttribute('edge_length', $token[$i]);
									$this->has_edge_lengths = true;
									$i++;
								}
								break;
							case ')':
								$c = count($stack);
								if ($c == 0)
								{
									$state = 99;
								}
								else
								{
									$q = $stack[$c - 1];
									$curnode = $q;
									array_pop($stack);
									$i++;
								}
								break;
							case ',':
								$q = $this->NewNode();
								$curnode->SetSibling($q);
								$c = count($stack);
								if ($c == 0)
								{
									$state = 99;
								}
								else
								{
									$q->SetAncestor($stack[$c - 1]);
									$curnode = $q;
									$state = 0;
									$i++;
								}
								break;
							case ';':
								$state = 2;
								break;
							default:
								$state = 99;
								break;
						}
					}
					break;
			}
		}
		
		return $state;
						
	}		
	
	
	//----------------------------------------------------------------------------------------------
	function ParseHarder ($treestring)
	{
		//echo $treestring;
	
		// 1. tokenise 
		
		$n = strlen($treestring);
		
		$pos = 0;		
		$buffer = '';
		
		$state = 0;
		
		$token = array();
		
		while ($state < 99) // 99 error, 100 done
		{
			if ($pos > $n)
			{
				$state = 99;
			}
		
			switch ($state)
			{
				case 0: // get next token
					switch ($treestring{$pos})
					{
						case '(':
						case ')':
						case ',':
						case ';':
						case ':':
							$buffer = $treestring{$pos};
							$pos++;
							$state = 1;
							break;
							
						// eat new lines
						case "\r": 
						case "\n": 
							$pos++;
							break;
							
						default:
							$buffer = $treestring{$pos};
							$pos++;
							$state = 2;	
							break;
					}
					break;
					
				case 1: // emit
					
					if (preg_match('/(?<label>.*):(?<edgelength>[\-]?\d+(\.\d+)?)$/', $buffer, $m))
					{
						$t = new stdclass;
						$t->type = 'string';
						$t->value = $m['label'];
						$token[] = $t;
						
						$t = new stdclass;
						$t->type = 'token';
						$t->value = ':';
						$token[] = $t;
						
						$t = new stdclass;
						$t->type = 'number';
						$t->value = $m['edgelength'];
						$token[] = $t;
					}
					else
					{
						$t = new stdclass;
						$t->type = 'token';
						$t->value = $buffer;
						$token[] = $t;
					}
					
					$buffer = '';
					$state = 0;
					break;
					
				case 2: // extend token
					switch ($treestring{$pos})
					{
						case ')':
						case ',':
							$state = 1;
							break;
							
						case '[':
							$pos++;
							$state = 3;
							break;
							
						default:
							$buffer .= $treestring{$pos};
							$pos++;
							break;
					}
					break;	
					
				case 3: // eat comments
					switch ($treestring{$pos})
					{
						case ']':
							$pos++;
							$state = 2;
							break;
												
						default:
							$pos++;
							break;
					}
					break;	
		
				default:
					break;
			}
		}
		
		// OK, now we have some pre-processed tokens
		//print_r($token);
		
		//exit();
		
		
		$curnode = $this->NewNode();
		$this->root = $curnode;

		$state = 0;
		$stack = array();
		$n = count($token);
		
		$i = 0;
		while ($state < 99) // 99 error, 100 done
		{
		
			//echo $state . ' '  . $token[$i]->type . ' ' . $token[$i]->value . "\n";
			
			switch ($state)
			{
				case 0: // getname
					if ($token[$i]->type == 'string')
					{
						$this->num_leaves++;
						$curnode->SetLabel($token[$i]->value);
						$i++;
						$state = 1;
					}
					else 
					{
						switch ($token[$i]->value)
						{
							case '(':
								$state = 2;
								break;
							default:
								$state = 99;
								break;
						}
						
					}
					break;
					
				case 1: // getinternode
					switch ($token[$i]->value)
					{
						case ':':
						case ',':
						case ')':
							$state = 2;
							break;
						default:
							$state = 99;
							break;
					}
					break;
					
				case 2: // nextmove
					switch ($token[$i]->value)
					{
						case ':':
							$i++;
							
							if (is_numeric($token[$i]->value))
							{
								$curnode->SetAttribute('edge_length', $token[$i]->value);
								$this->has_edge_lengths = true;
								$i++;
							}
							break;
						case ',':
							$q = $this->NewNode();
							$curnode->SetSibling($q);
							$c = count($stack);
							if ($c == 0)
							{
								$state = 99;
							}
							else
							{
								$q->SetAncestor($stack[$c - 1]);
								$curnode = $q;
								$state = 0;
								$i++;
							}
							break;							
						case '(':
							$stack[] = $curnode;
							$q = $this->NewNode();
							$curnode->SetChild($q);
							$q->SetAncestor($curnode);
							$curnode = $q;
							$state = 0;
							$i++;
							break;
						case ')':
							if (empty($stack))
							{
								$state = 99;
							}
							else
							{
								$curnode = array_pop($stack);
								$state = 3;
								$i++;
							}
							break;
						
						case ';':
							if (empty($stack))
							{
								$state = 100;
							}
							else
							{
								$state = 99;
							}
							break;
						
						default:
							$state = 99;
							break;
					}
					break;
				
				case 3: // finishchildren
					if ($token[$i]->type == 'string')
					{
						$curnode->SetLabel($token[$i]->value);
						$i++;
					}
					else
					{
						switch ($token[$i]->value)
						{
							case ':':
								$i++;
								if (is_numeric($token[$i]->value))
								{
									$curnode->SetAttribute('edge_length', $token[$i]->value);
									$this->has_edge_lengths = true;
									$i++;
								}
								break;
							case ')':
								$c = count($stack);
								if ($c == 0)
								{
									$state = 99;
								}
								else
								{
									$q = $stack[$c - 1];
									$curnode = $q;
									array_pop($stack);
									$i++;
								}
								break;
							case ',':
								$q = $this->NewNode();
								$curnode->SetSibling($q);
								$c = count($stack);
								if ($c == 0)
								{
									$state = 99;
								}
								else
								{
									$q->SetAncestor($stack[$c - 1]);
									$curnode = $q;
									$state = 0;
									$i++;
								}
								break;
							case ';':
								$state = 2;
								break;
							default:
								$state = 99;
								break;
						}
					}
					break;
			}
		}
		
		return $state;
						
	}			
			
						
	//----------------------------------------------------------------------------------------------
	function Dump()
	{
		//echo "label_to_node_map\n";
		//print_r($this->label_to_node_map);
		
		//foreach ($this->nodes as $node)
		//{
		//	echo $node->GetLabel() . "\n";
		//}
		
		echo "Num leaves = " . $this->num_leaves . "\n";
		
		$n = new NodeIterator ($this->root);
		$a = $n->Begin();
		while ($a != NULL)
		{
			//echo "Node=\n:";
			$a->Dump();
			$a = $n->Next();
		}
	}
	
	//----------------------------------------------------------------------------------------------
	function WriteDot()
	{
		$dot = "digraph{\n";
		$n = new NodeIterator ($this->root);
		$a = $n->Begin();
		while ($a != NULL)
		{
			if ($a->GetAncestor())
			{
				$dot .= "\"" . $a->GetAncestor()->GetLabel() . "\" -> \"" . $a->GetLabel() . "\";\n";
			}
			$a = $n->Next();
		}
		$dot .= "}\n";
		return $dot;
	}
		
	
	
	//----------------------------------------------------------------------------------------------
	// Build weights
	function BuildWeights($p)
	{
		if ($p)
		{
			$p->SetAttribute('weight', 0);
			
			$this->BuildWeights($p->GetChild());
			$this->BuildWeights($p->GetSibling());
			
			if ($p->Isleaf())
			{
				$p->SetAttribute('weight', 1);
			}
			if ($p->GetAncestor())
			{
				$p->GetAncestor()->AddWeight($p->GetAttribute('weight'));
			}
		}
	}


}

//--------------------------------------------------------------------------------------------------
/**
 * @brief
 *
 * Iterator that visits nodes in a tree in post order. Uses a stack to keep
 * track of place in tree. 
 *
 */
class NodeIterator
{
	var $root;
	var $cur;
	var $stack = array();
	
	//----------------------------------------------------------------------------------------------
	/**
	 * @brief Takes the root of the tree as a parameter.
	 *
     * @param r the root of the tree
	 */
	function __construct($r)
	{
		$this->root = $r;
	}
	
	//----------------------------------------------------------------------------------------------
	/**
	 * @brief Initialise iterator and returns the first node.
	 *
	 * Initialises the 
	 * @return The first node of the tree
	 */
	function Begin()
	{
		$this->cur = $this->root;
		while ($this->cur->GetChild())
		{
			array_push($this->stack, $this->cur);			
			$this->cur = $this->cur->GetChild();
		}
		return $this->cur;	
	}
	
	//----------------------------------------------------------------------------------------------
 	/**
	 * @brief Move to the next node in the tree.
	 *
	 * @return The next node in the tree, or NULL if all nodes have been visited.
	 */
	function Next()
	{
		if (count($this->stack) == 0)
		{
			$this->cur = NULL;
		}
		else
		{
			if ($this->cur->GetSibling())
			{
				$p = $this->cur->GetSibling();
				while ($p->GetChild())
				{
					array_push($this->stack, $p);
					$p = $p->GetChild();
				}
				$this->cur = $p;
			}
			else
			{
				$this->cur = array_pop($this->stack);
			}
		}
		return $this->cur;
	}
}


//--------------------------------------------------------------------------------------------------
class PreorderIterator extends NodeIterator
{
	//----------------------------------------------------------------------------------------------
	function Begin()
	{
		$this->cur = $this->root;
		return $this->cur;	
	}
	
	//----------------------------------------------------------------------------------------------
	function Next()
	{
		if ($this->cur->GetChild() && !$this->cur->GetAttribute('stop'))
		{
			array_push($this->stack, $this->cur);
			$this->cur = $this->cur->GetChild();
		}
		else
		{
			while (!empty($this->stack)
				&& ($this->cur->GetSibling() == NULL))
			{
				$this->cur = array_pop($this->stack);
			}
			if (empty($this->stack))
			{
				$this->cur = NULL;
			}
			else
			{
				$this->cur = $this->cur->GetSibling();
			}
		}
		return $this->cur;
	}
	
}

//-------------------------------------------------------------------------------------------------
class Port
{
	var $output = '';
	var $width = 0;
	var $height = 0;
	var $element_id = 0;
	
	//----------------------------------------------------------------------------------------------
	function __construct($element_id, $width, $height)
	{
		$this->element_id 	= $element_id;
		$this->width 		= $width;
		$this->height 		= $height;
		$this->StartPicture();
	}
	
	//----------------------------------------------------------------------------------------------
	function DrawLine($p0, $p1)
	{
	}
	
	//----------------------------------------------------------------------------------------------
	function DrawRect($p0, $p1)
	{
	}
	
	
	function DrawPolygon($pts, $color = array())
	{
	}
	
	//----------------------------------------------------------------------------------------------
	function DrawText ($pt, $text)
	{
	}
	
	//----------------------------------------------------------------------------------------------
	function GetOutput()
	{
		$this->EndPicture();
		return $this->output;
	}
	
	//----------------------------------------------------------------------------------------------
	function StartPicture ()
	{
	}

	//----------------------------------------------------------------------------------------------
	function EndPicture ()
	{
	}
	
	
}

//-------------------------------------------------------------------------------------------------
class CanvasPort extends Port
{
	
	
	function DrawLine($p0, $p1)
	{
		$this->output .= 'context.moveTo(' . $p0['x'] . ',' . $p0['y'] . ');' . "\n";
		$this->output .= 'context.lineTo(' . $p1['x'] . ',' . $p1['y'] . ');' . "\n";
		$this->output .= 'context.stroke();' . "\n";
	}
	
	function DrawText ($pt, $text)
	{
		$this->output .= 'context.fillText("' . $text . '", ' . $pt['x'] . ', ' . $pt['y'] . ');' . "\n";
	}
	
	function StartPicture ()
	{
		$this->output = '<script type="application/javascript">' . "\n";
		$this->output .= 'var paper = Raphael("' . $this->element_id . '", ' . $this->width . ', ' . $this->height . ');' . "\n";
	}
		
	
	function EndPicture ()
	{
		$this->output .= '</script>';
	}
	
	
}

//-------------------------------------------------------------------------------------------------
class SVGPort extends Port
{
		
	function DrawLine($p0, $p1)
	{
/*		$this->output .= '<path d="M ' 
				. $p0['x'] . ' ' . $p0['y'] . ' ' . $p1['x'] . ' ' . $p1['y'] . '" />';
*/
		$this->output .= '<line  x1="' 
				. $p0['x'] . '" y1="' . $p0['y'] . '" x2="' . $p1['x'] . '" y2="' . $p1['y'] . '" />';
				
		/*		
  <line
     style="stroke:#000000;stroke-width:1;stroke-linecap:square"
     id="line9"
     y2="10"
     x2="19.743589"
     y1="10"
     x1="390"
     vector-effect="non-scaling-stroke" />
	*/			
				
	}
	
	//----------------------------------------------------------------------------------------------
	function DrawRect($p0, $p1, $color = array())
	{
		$this->output .= '<rect';
		if (count($color) > 0)
		{
			$this->output .= ' fill="rgb(' . join(",", $color) . ')"';
		}
		else
		{
			$this->output .= ' fill="#dddddd"';
		}
		$this->output .= ' stroke="#FFFFFF"'; 
		$this->output .= ' x="' . $p0['x'] . '"';	
		$this->output .= ' y="' . $p0['y'] . '"';	
		$this->output .= ' width="' . ($p1['x'] - $p0['x']) . '"';	
		$this->output .= ' height="' . ($p1['y'] - $p0['y']) . '"';	
		$this->output .= ' />';
		
	}
	
	
	function DrawPolygon($pts, $color = array())
	{
		$this->output .= '<polygon';
		
		if (count($color) > 0)
		{
			$this->output .= ' fill="rgb(' . join(",", $color) . ')"';
		}
		else
		{
			$this->output .= ' fill="#dddddd"';
		}
		
		$this->output .= ' stroke="#999999" points="';
		
		foreach ($pts as $pt)
		{
			$this->output .=  $pt['x'] . ',' . $pt['y'] . ' ';
		}
		$this->output .= '" />';
	}
	
	function DrawText ($pt, $text)
	{
		$this->output .= '<text x="' . $pt['x'] . '" y="' . $pt['y'] . '">' . $text . '</text>' .  "\n";
	}
	
	function StartPicture()
	{
		$this->output = '<?xml version="1.0" ?>
<svg xmlns:xlink="http://www.w3.org/1999/xlink" 
	xmlns="http://www.w3.org/2000/svg"
	width="' . $this->width . 'px" 
    height="'. $this->height . 'px" 
    >';	
    
    	$this->output .= '<style type="text/css">
<![CDATA[

text {
	font-size: 10px;
	color: black;
}

path {
	stroke:#000000;
	stroke-width:1;
	/*stroke-linecap:square;*/
}

line {
	stroke: #aaaaaa;
	stroke-width:1;
	stroke-linecap:square;
}

]]>
</style>';

	$this->output .= '<g id="' . $this->element_id . '">';
    
    }
	
	function EndPicture ()
	{
		$this->output .= '</g>';
		$this->output .= '</svg>';
	}
	
}


//-------------------------------------------------------------------------------------------------
class RaphaelPort extends Port
{

	//----------------------------------------------------------------------------------------------
	function DrawLine($p0, $p1)
	{
		$this->output .= 'paper.path("M ' 
				. $p0['x'] . ' ' . $p0['y'] . ' ' . $p1['x'] . ' ' . $p1['y'] . '");' . "\n";
	}
	
	//----------------------------------------------------------------------------------------------
	function DrawText ($pt, $text)
	{
		$this->output .= 'var t = paper.text(' . $pt['x'] . ', ' . $pt['y'] . ', "' . $text . '");' . "\n";
		$this->output .= 't.attr("text-anchor", "start");' . "\n";
		//$this->output .= 't.attr("fill", "red");' . "\n";
		$this->output .= 't.mouseover(function (event) {
    this.attr({fill: "red"});
	});' . "\n";
		$this->output .= 't.mouseout(function (event) {
    this.attr({fill: "black"});
	});' . "\n";
	}
	
	//----------------------------------------------------------------------------------------------
	function Circle($pt, $r)
	{
		$this->output .= 'paper.circle(' 
				. $pt['x'] . ', ' . $pt['y'] . ', ' . $r . ');' . "\n";
	
	}
	
	//----------------------------------------------------------------------------------------------
	function StartPicture ()
	{
		$this->output = '<script type="application/javascript">' . "\n";
		$this->output .= 'var paper = Raphael("' . $this->element_id . '", ' . $this->width . ', ' . $this->height . ');' . "\n";
	}
		
	
	//----------------------------------------------------------------------------------------------
	function EndPicture ()
	{
		$this->output .= '</script>';
	}
	
	
	
}



//-------------------------------------------------------------------------------------------------
class TreeDrawer
{
	var $t;
	var $width 		= 0;
	var $height 	= 0;
	var $left 		= 0;
	var $top 		= 0;
	var $leaf_count = 0;
	var $leaf_gap	= 0;
	var $node_gap	= 0;
	var $last_y		= 0;
	var $max_depth 	= 0;
	var $last_label = 0;
	var $max_height = 0;
	
	var $map = '';
	
	var $settings = array();
	
	var $port;
	
	
	//----------------------------------------------------------------------------------------------
	function __construct($tree, $attr)
	{
		$this->t = $tree;
		
		// Settings
		$this->settings = $attr;		
		
		// Ensure sensible defaults
		$this->SetDefaults();
			
		$this->left = $this->settings['inset'];
		$this->top = $this->settings['inset'];
		$this->width = $this->settings['width'] - 2 * $this->settings['inset'];
		$this->height = $this->settings['height'] - 2 * $this->settings['inset'];
				
		$this->last_label = -($this->settings['font_height']/2.0);
	}
	
	//----------------------------------------------------------------------------------------------
	function SetDefaults()
	{
		if (!isset($this->settings['inset']))
		{
			$this->settings['inset'] = 10;
		}
		if (!isset($this->settings['width']))
		{
			$this->settings['width'] = 200;
		}
		if (!isset($this->settings['height']))
		{
			$this->settings['height'] = 400;
		}
		if (!isset($this->settings['font_height']))
		{
			$this->settings['font_height'] = 10;
		}	
	}
	
	
	//----------------------------------------------------------------------------------------------
	function CalcInternal($p)
	{
		// Cladogram
		$pt = array();		
		$pt['x'] = $this->left + $this->node_gap * ($this->t->GetNumLeaves() - $p->GetAttribute('weight'));
		$pt['y'] = $this->last_y - (($p->GetAttribute('weight') - 1) * $this->leaf_gap)/2.0;

		$p->SetAttribute('xy', $pt);
	}

	//----------------------------------------------------------------------------------------------
	function CalcLeaf($p)
	{
		$pt = array();
		$pt['y'] = $this->top + $this->leaf_count * $this->leaf_gap;
		$this->last_y = $pt['y'];
		$this->leaf_count++;
		
		// cladogram
		$pt['x'] = $this->left + $this->width;
		
		$p->SetAttribute('xy', $pt);
		
		// image map
		/*$this->map .= '<area shape="rect" coords="' 
		. ($pt['x'] + 10) . ',' . ($pt['y'] - 5) . ',' . ($pt['x'] + 10 + strlen($p->GetLabel()) * 10) . ',' . ($pt['y'] + 5) 
		. '" href="http://www.ncbi.nlm.nih.gov/nuccore/' . $p->GetLabel() . '" />' . "\n";*/
		
		$this->max_height = max($this->max_height, $pt['y']);
	}
	
	//----------------------------------------------------------------------------------------------
	function CalcCoordinates()
	{
		$leaves = $this->t->GetNumLeaves();
		$this->leaf_count = 0;
   		$this->leaf_gap = $this->height / ($leaves - 1.0);
   		
   		if ($this->t->IsRooted())		
		{
			$this->node_gap = $this->width / ($leaves);
			
			$this->left += $this->node_gap; 
			$this->width -= $this->node_gap; 
		}
		else
		{
			$this->node_gap = $this->width / ($leaves - 1.0);	
		}
		
		$n = new NodeIterator ($this->t->getRoot());
		
		$q = $n->Begin();
		while ($q != NULL)
		{
			
			if ($q->IsLeaf ())
			{
				$this->CalcLeaf ($q);
			}
			else
			{
				$this->CalcInternal ($q);
			}
	
			$q = $n->Next();
		}
	}
	
	//----------------------------------------------------------------------------------------------
	function Draw($port)
	{
		$this->port = $port;
		
		$n = new NodeIterator ($this->t->getRoot());
		
		$q = $n->Begin();
		while ($q != NULL)
		{			
			if ($q->IsLeaf ())
			{
				$this->DrawLeaf ($q);
			}
			else
			{
				$this->DrawInternal ($q);
			}
	
			$q = $n->Next();
		}
		if ($this->t->IsRooted())
		{
			$this->DrawRoot ();
		}

	}
	
	//----------------------------------------------------------------------------------------------
	function DrawLeaf($p)
	{
		$anc = $p->GetAncestor();
		if ($anc)
		{
			// Slant
			$p0 = $p->GetAttribute('xy');
			$p1 = $anc->GetAttribute('xy');

			$this->port->DrawLine($p0, $p1);
 		}
 		
 		$this->DrawLeafLabel ($p);		
	}
	
	//----------------------------------------------------------------------------------------------
	function DrawLeaflabel($p)
	{
		$p0 = $p->GetAttribute('xy');
		
		if ($p0['y'] - $this->last_label > $this->settings['font_height'])
		{
			//echo 'context.fillText("' . $p->Getlabel() . '", ' . ($p0['x'] + $this->settings['font_height']/2.0) . ', ' . $p0['y'] . ');' , "\n";
			$this->port->DrawText($p0, $p->Getlabel()); 
			$this->last_label  = $p0['y'];
		}
		
		
	}

	//----------------------------------------------------------------------------------------------
	function DrawLine($p0, $p1)
	{
		echo 'context.moveTo(' . $p0['x'] . ',' . $p0['y'] . ');' . "\n";
		echo 'context.lineTo(' . $p1['x'] . ',' . $p1['y'] . ');' . "\n";
		echo 'context.stroke();' . "\n";
	}
	
	//----------------------------------------------------------------------------------------------
	function DrawInternal($p)
	{
		$p0 = $p->GetAttribute('xy');
		$anc = $p->GetAncestor();
		if ($anc)
		{
			// Slant
			$p1 = $anc->GetAttribute('xy');
			

			// Rectangle
			
//			$p1 = $anc->GetAttribute('xy');
//			$p1['y'] = $p0['y'];
				
			//$this->DrawLine($p0, $p1);
			$this->port->DrawLine($p0, $p1);
		}
/*		
		// rectangle
		$pl = $p->GetChild()->GetAttribute('xy');
		$pr = $p->GetChild()->GetRightMostSibling()->GetAttribute('xy');
		
		$p0['x'] = $p0['x'];
		$p0['y'] = $pl['y'];
		$p1['x'] = $p0['x'];
		$p1['y'] = $pr['y'];

		//$this->DrawLine($p0, $p1);
		$this->port->DrawLine($p0, $p1);*/
		
			
 		$this->DrawInternalLabel ($p);		
	}
	
	//----------------------------------------------------------------------------------------------
	function DrawInternalLabel($p)
	{
		//$p->Dump();
		
		/*if ($p->GetLabel())
		{
			// to do test
			$p0 = $p->GetAttribute('xy');
			$this->port->Circle($p0, 5);
		}
		*/

	}
	
	//----------------------------------------------------------------------------------------------
	function DrawRoot()
	{
		$p0 = $this->t->GetRoot()->GetAttribute('xy');
		$p1 = $p0;
		$p1['x'] -= $this->node_gap;
		//$this->DrawLine($p0, $p1);	
		$this->port->DrawLine($p0, $p1);
	}
	
	//----------------------------------------------------------------------------------------------
	function GetMap()
	{
		return $this->map;
	}
	
	
}

//-------------------------------------------------------------------------------------------------
class RectangleTreeDrawer extends TreeDrawer
{
	
	
	//----------------------------------------------------------------------------------------------
	function CalcInternal($p)
	{
		$pt['x'] = (float)$this->left + (float)$this->node_gap * (float)($this->max_depth - $p->GetAttribute('depth'));
    	
		$pl = $p->GetChild()->GetAttribute('xy');
		$pr = $p->GetChild()->GetRightMostSibling()->GetAttribute('xy');
		
		$pt['y'] = (float)$pl['y'] + (float)($pr['y'] - $pl['y'])/2.0;
   	
		$p->SetAttribute('xy', $pt);
	}

	
	//----------------------------------------------------------------------------------------------
	function CalcCoordinates()
	{
		// rectangle		
		foreach ($this->t->nodes as $n)
		{
			$n->SetAttribute('depth', 0);
		}
		$this->max_depth = 0;
		foreach ($this->t->nodes as $n)
		{
			if ($n->IsLeaf())
			{
				$p = $n->GetAncestor();
				$count = 1;
				while ($p)
				{
					if ($count > $p->GetAttribute('depth'))
					{
						$p->SetAttribute('depth', $count);
						$this->max_depth = max($this->max_depth, $count);
					}
					$count++;
					$p = $p->GetAncestor();
				}
			}
		}						
		$leaves = $this->t->GetNumLeaves();
		$this->leaf_count = 0;
   		$this->leaf_gap = (float)$this->height / ($leaves - 1.0);
   		
   		if ($this->t->IsRooted())		
		{
			$this->node_gap = $this->width / ($this->max_depth + 1);
			
			$this->left += $this->node_gap; 
			$this->width -= $this->node_gap; 
		}
		else
		{
//			$this->node_gap = $this->width / ($leaves - 1.0);	
			$this->node_gap = ((float)$this->width / (float)$this->max_depth);	
		}
		
		$n = new NodeIterator ($this->t->getRoot());
		
		$q = $n->Begin();
		while ($q != NULL)
		{
			
			if ($q->IsLeaf ())
			{
				$this->CalcLeaf ($q);
			}
			else
			{
				$this->CalcInternal ($q);
			}
	
			$q = $n->Next();
		}
	}
	
	
	//----------------------------------------------------------------------------------------------
	function DrawLeaf($p)
	{
		$anc = $p->GetAncestor();
		if ($anc)
		{

			// Rectangle
			$p0 = $p->GetAttribute('xy');
			$p1 = $anc->GetAttribute('xy');
			$p1['y'] = $p0['y'];
			
			$this->port->DrawLine($p0, $p1);
 		}
 		
 		$this->DrawLeafLabel ($p);		
	}
	
	
	//----------------------------------------------------------------------------------------------
	function DrawInternal($p)
	{
		$p0 = $p->GetAttribute('xy');
		$anc = $p->GetAncestor();
		if ($anc)
		{
			$p1 = $anc->GetAttribute('xy');
			$p1['y'] = $p0['y'];
				
			$this->port->DrawLine($p0, $p1);
			
		}
		
		// rectangle
		$pl = $p->GetChild()->GetAttribute('xy');
		$pr = $p->GetChild()->GetRightMostSibling()->GetAttribute('xy');
		
		$p0['x'] = $p0['x'];
		$p0['y'] = $pl['y'];
		$p1['x'] = $p0['x'];
		$p1['y'] = $pr['y'];

		$this->port->DrawLine($p0, $p1);
		
		

			
 		$this->DrawInternalLabel ($p);		
	}
	

	
}


//-------------------------------------------------------------------------------------------------
class PhylogramTreeDrawer extends RectangleTreeDrawer
{
	var $max_path_length = 0.0;
	
	
	//----------------------------------------------------------------------------------------------
	function CalcInternal($p)
	{
		$pt = array();
		$pt['x'] = (float)$this->left + ((float)$p->GetAttribute('path_length') / (float)$this->max_path_length) * (float)$this->width;
    	
		$pl = $p->GetChild()->GetAttribute('xy');
		$pr = $p->GetChild()->GetRightMostSibling()->GetAttribute('xy');
		
		$pt['y'] = $pl['y'] + ($pr['y'] - $pl['y'])/2.0;
   	
		$p->SetAttribute('xy', $pt);
	}
	
	//----------------------------------------------------------------------------------------------
	function CalcLeaf($p)
	{
		$pt = array();
		$pt['y'] = (float)$this->top + $this->leaf_count * (float)$this->leaf_gap;
		$this->last_y = $pt['y'];
		$this->leaf_count++;
		
		// cladogram
		$pt['x'] = $this->left + ($p->GetAttribute('path_length') / $this->max_path_length) * $this->width;
		
		$p->SetAttribute('xy', $pt);
		
		
		$this->max_height = max($this->max_height, $pt['y']);
	}		

	
	//----------------------------------------------------------------------------------------------
	function CalcCoordinates()
	{
		$this->max_path_length = 0.0;		
		$this->t->GetRoot()->SetAttribute('path_length', $this->t->GetRoot()->GetAttribute('edge_length'));

		// Get path lengths
		$n = new PreorderIterator ($this->t->getRoot());
		$q = $n->Begin();
		while ($q != NULL)
		{			
			$d = $q->GetAttribute('edge_length');
			if ($d < 0.00001)
			{
				$d = 0.0;
			}
        	if ($q != $this->t->GetRoot())
	    		$q->SetAttribute('path_length', $q->GetAncestor()->GetAttribute('path_length') + $d);

			$this->max_path_length = max($this->max_path_length, $q->GetAttribute('path_length'));
			$q = $n->Next();
		}

		//$this->height -= $this->settings['font_height'];

		$leaves = $this->t->GetNumLeaves();
		$this->leaf_count = 0;
   		$this->leaf_gap = $this->height / ($leaves - 1.0);

		$n = new NodeIterator ($this->t->getRoot());
		
		$q = $n->Begin();
		while ($q != NULL)
		{
			
			if ($q->IsLeaf ())
			{
				$this->CalcLeaf ($q);
			}
			else
			{
				$this->CalcInternal ($q);
			}
	
			$q = $n->Next();
		}
		
		// Space for scale bar
		//$this->max_height += $this->settings['font_height'];		
	}
	
	//----------------------------------------------------------------------------------------------
	function Draw($port)
	{
		parent::Draw($port);
		//$this->DrawScaleBar($port);
	}

	//----------------------------------------------------------------------------------------------
	function DrawScaleBar($port)
	{
		$pt1 = array();
		$pt2 = array();
		
		$m = log10($this->max_path_length);
		$i = floor($m);
		//     if (!mUltrametric)

		//$i -= 1;
		$bar = pow(10.0, $i);
		
		//echo $bar;
		
		$scalebar = ($bar/$this->max_path_length) * $this->width;
		
		if (0)
		{
		}
		else
		{
			// Scale bar
			$pt1['x'] = $this->left;
			$pt1['y'] = $this->top + $this->height + $this->settings['font_height'];

			$pt2['x'] = $pt1['x'] + $scalebar;
			$pt2['y'] = $pt1['y'];
			
			$this->port->DrawLine($pt1, $pt2);
			
			// Label
			$buf = '';
			if ($i >= 0)
			{
				$buf = sprintf ("%d", floor($bar));
			}
			else
			{
				$j = abs ($i);
				$buf = sprintf ("%." . $j . "f", $bar);
			}
			$this->port->DrawText($pt2, $buf);
			
  		}
	}
	
	
}	

?>