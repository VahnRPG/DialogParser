<?php
class Statement extends Node {
	public $label;
	public $expression;
	public $conditions = array();
	
	public function __construct(Label $label) {
		$this->label = $label;
	}
	
	public function setExpression(Expression $expression) {
		$this->expression = $expression;
	}
	
	public function addCondition(Condition $condition) {
		$this->conditions[] = $condition;
	}
}
?>