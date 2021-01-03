<?php
class Section extends Node {
	public $name;
	public $statements = array();
	
	public function __construct($name = "") {
		$this->name = $name;
	}
	
	public function addStatement(Statement $statement) {
		$this->statements[] = $statement;
	}
	
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}
?>