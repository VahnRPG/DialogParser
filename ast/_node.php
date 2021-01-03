<?php
require_once("section.php");
require_once("statement.php");
require_once("ast/visitor.php");
require_once("ast/expression.php");
require_once("ast/condition.php");

abstract class Node {
	public $start;
	public $end;
	
	abstract public function accept(Visitor $visitor);
}
?>