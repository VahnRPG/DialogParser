<?php
class TokenIds {
	const NONE = "NONE";
	const WHITESPACE = "WHITESPACE";
	const NEW_LINE = "NEW_LINE";
	const COMMENT = "COMMENT";
	const NUMBER = "NUMBER";
	const STRING = "STRING";
	const ASSIGN = "ASSIGN";
	const IDENTIFIER = "IDENTIFIER";
	const DOLLAR = "DOLLAR";
	const SECTION = "SECTION";
	const CONDITION = "CONDITION";
	const COLON = "COLON";
	const GOTO = "GOTO";
	const CODE = "CODE";
	const WAIT_WHILE = "WAIT_WHILE";
	const END = "END";
}

class Token {
	public $id;
	public $start_pos;
	public $end_pos;
	
	public function __toString() {
		return $this->id." (".$this->start_pos." - ".$this->end_pos.")";
	}
}
?>