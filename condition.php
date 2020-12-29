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
}

class ShowOnceCondition extends Condition {
}

class OnceEverCondition extends Condition {
}

class TempOnceCondition extends Condition {
}

class CodeCondition extends Condition {
	public $code;
}
?>