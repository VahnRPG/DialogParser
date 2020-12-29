<?php
class TokenIds {
	const NONE = "NONE";
	const NEW_LINE = "NEW_LINE";
	const IDENTIFIER = "IDENTIFIER";
	const WAIT_WHILE = "WAIT_WHILE";
	const NUMBER = "NUMBER";
	const WHITESPACE = "WHITESPACE";
	const COLON = "COLON";
	const CONDITION = "CONDITION";
	const STRING = "STRING";
	const ASSIGN = "ASSIGN";
	const COMMENT = "COMMENT";
	const GOTO = "GOTO";
	const CODE = "CODE";
	const DOLLAR = "DOLLAR";
	const END = "END";
}

class Token {
	public $id;
	public $start_pos;
	public $end_pos;
	
	public function readToken() {
		switch($this->id) {
			case TokenIds::NONE:
				return "None";
			case TokenIds::NEW_LINE:
				return "NewLine";
			case TokenIds::IDENTIFIER:
				return "Identifier";
			case TokenIds::NUMBER:
				return "Number";
			case TokenIds::WHITESPACE:
				return "Whitespace";
			case TokenIds::COLON:
				return "Colon";
			case TokenIds::CONDITION:
				return "Condition";
			case TokenIds::STRING:
				return "String";
			case TokenIds::ASSIGN:
				return "Assign";
			case TokenIds::COMMENT:
				return "Comment";
			case TokenIds::GOTO:
				return "Goto";
			case TokenIds::CODE:
				return "Code";
			case TokenIds::END:
				return "End";
		}
		
		return "?";
	}
	
	public function __toString() {
		return $this->id." (".$this->start_pos." - ".$this->end_pos.")";
	}
}
?>