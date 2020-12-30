<?php
abstract class Expression extends Node {
	public $statement;
	
	public function __construct(Statement $statement) {
		$this->statement = $statement;
	}
}

class GotoExpression extends Expression {
	public $name;
}

class CodeExpression extends Expression {
	public $code;
}

class ChoiceExpression extends Expression {
	public $number = 0;
	public $text;
	public $goto;
}

class SayExpression extends Expression {
	public $actor;
	public $text;
}

class PauseExpression extends Expression {
	public $pause_time = 0.0;
}

class SayChoiceExpression extends Expression {
	public $active = true;
}

class DialogExpression extends Expression {
	public $actor;
}

class OverrideExpression extends Expression {
	public $node;
}

class ShutupExpression extends Expression {
}

class AllowObjectsExpression extends Expression {
	public $allow = true;
}

class LimitExpression extends Expression {
	public $limit = 0;
}

class WaitWhileExpression extends Expression {
	public $condition;
}

class WaitForExpression extends Expression {
	public $actor;
}
?>