<?php
abstract class Expression extends Node {
	public $statement;
	
	public function __construct(Statement $statement) {
		$this->statement = $statement;
	}
}

class GotoExpression extends Expression {
	public $name;
	
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}

class CodeExpression extends Expression {
	public $code;
	
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}

class ChoiceExpression extends Expression {
	public $number = 0;
	public $text;
	public $goto;
	
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}

class SayExpression extends Expression {
	public $actor;
	public $text;
	
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}

class PauseExpression extends Expression {
	public $pause_time = 0.0;
	
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}

class SayChoiceExpression extends Expression {
	public $active = true;
	
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}

class DialogExpression extends Expression {
	public $actor;
	
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}

class OverrideExpression extends Expression {
	public $node;
	
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}

class StopTalkingExpression extends Expression {
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}

class AllowObjectsExpression extends Expression {
	public $allow = true;
	
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}

class LimitExpression extends Expression {
	public $limit = 0;
	
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}

class WaitWhileExpression extends Expression {
	public $condition;
	
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}

class WaitForExpression extends Expression {
	public $actor;
	
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}
?>