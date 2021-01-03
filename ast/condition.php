<?php
abstract class Condition extends Node {
	public $statement;
	
	private $line;
	
	public function __construct(Statement $statement, int $line) {
		$this->statement = $statement;
		$this->line = $line;
	}
	
	public function getLine() {
		return $this->line;
	}
}

class OnceCondition extends Condition {
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}

class ShowOnceCondition extends Condition {
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}

class OnceEverCondition extends Condition {
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}

class TempOnceCondition extends Condition {
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}

class CodeCondition extends Condition {
	public $code;
	
	public function accept(Visitor $visitor) {
		$visitor->visit($this);
	}
}
?>