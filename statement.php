<?php
class Statement extends Node {
	public $section;
	public $expression;
	public $conditions = array();
	
	public function __construct(Section $section) {
		$this->section = $section;
	}
	
	public function setExpression(Expression $expression) {
		$this->expression = $expression;
	}
	
	public function addCondition(Condition $condition) {
		$this->conditions[] = $condition;
	}
}
?>