<?php
require_once("label.php");
require_once("statement.php");
require_once("expression.php");
require_once("condition.php");

abstract class Node {
	public $start;
	public $end;
	
	public function accept($visitor) {
	}
}
?>