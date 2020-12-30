<?php
class TokenIds {
	const NONE = "NONE";
	const NEW_LINE = "NEW_LINE";
	const IDENTIFIER = "IDENTIFIER";
	const WAIT_WHILE = "WAIT_WHILE";
	const NUMBER = "NUMBER";
	const WHITESPACE = "WHITESPACE";
	const SECTION = "SECTION";
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
	
	public function __toString() {
		return $this->id." (".$this->start_pos." - ".$this->end_pos.")";
	}
}
?>