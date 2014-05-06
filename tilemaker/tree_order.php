<?php

require_once (dirname(__FILE__) . '/tree.php');

//-------------------------------------------------------------------------------------------------
class TreeOrder
{
	var $t;
	
	function __construct($tree)
	{
		$this->t = $tree;
	}
	
	function Order()
	{
		$n = new NodeIterator ($this->t->getRoot());
		
		$q = $n->Begin();
		while ($q != NULL)
		{			
			if (!$q->IsLeaf ())
			{
				$this->SortDescendants ($q);
			}	
			$q = $n->Next();
		}
	
	}
	
	function MustSwap($p, $q) { return false; }
	
	function SortDescendants($node)
	{
		$head = $node->GetChild();
		$tail = $head;
		while ($tail->GetSibling())
		{
			$p = $tail->GetSibling();
			if ($this->MustSwap($head, $p))
			{
				$tail->SetSibling($p->GetSibling());
				$p->SetSibling($head);
				$head = $p;
				$p->GetAncestor()->SetChild($p);
			}
			else
			{
				$q = $head;
				$r = $q->GetSibling();
				while ($this->MustSwap($p, $r))
				{
					$q = $r;
					$r = $q->GetSibling();
				}
				if ($p === $r)
				{
					$tail = $p;
				}
				else
				{
					$tail->SetSibling($p->GetSibling());
					$p->SetSibling($r);
					$p->SetSibling($p);
				}
			}
		}
	}
}

//-------------------------------------------------------------------------------------------------
class RightOrder extends TreeOrder
{

	function MustSwap($p, $q)
	{
		return ($p->GetAttribute('weight') > $q->GetAttribute('weight'));
	}
}

?>