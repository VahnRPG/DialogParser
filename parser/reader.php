<?php
class Reader implements Iterator {
	public $token;
	
	private $position = 0;
	private $file_name;
	private $handle;
	//int & int
	private $lines = array();
	private $offset = -1;
	
	public function __construct($file_name) {
		$this->position = 0;
		$this->file_name = $file_name;
		$this->token = new Token();
		$this->handle = fopen($file_name, "r");
		$this->next();
	}
	
	public function __destruct() {
		fclose($this->handle);
	}
	
	public function getLine(Token $token = NULL) {
		if (is_null($token)) {
			$token = clone $this->token;
			$this->next();
		}
		
		$previous = -1;
		$previous_val = -1;
		foreach($this->lines as $key => $val) {
			if ($previous < $token->start_pos && $token->start_pos < $key) {
				return $val;
			}
			$previous = $key;
			$previous_val = $val;
		}
		
		return $previous_val + 1;
	}
	
	public function readToken(Token $token) {
		$start_pos = ftell($this->handle);
		$id = $this->readTokenId();
		while($id == TokenIds::WHITESPACE || $id == TokenIds::COMMENT || $id == TokenIds::NEW_LINE || $id == TokenIds::NONE) {
			$start_pos = ftell($this->handle);
			$id = $this->readTokenId();
		}
		$end_pos = ftell($this->handle);
		
		$token->id = $id;
		$token->start_pos = $start_pos;
		$token->end_pos = $end_pos;
		
		return true;
	}
	
	public function readText(int $start, int $length) {
		$output = "";
		fseek($this->handle, $start);
		for($i = 0; $i < $length; $i++) {
			$output .= fread($this->handle, 1);
		}
		
		return $output;
	}
	
	public function readTokenText(Token $token = NULL) {
		if (is_null($token)) {
			$token = clone $this->token;
			$this->next();
		}
		
		return $this->readText($token->start_pos, $token->end_pos - $token->start_pos);
	}
	
	private function readTokenId() {
		$char = fread($this->handle, 1);
		if (feof($this->handle)) {
			return TokenIds::END;
		}
		
		switch($char) {
			case "\0":
				return TokenIds::END;
			case "\n":
				$cur_pos = ftell($this->handle) - 1;
				if (!isset($this->lines[$cur_pos])) {
					$this->lines[$cur_pos] = ($this->offset == -1 ? 1 : $this->lines[$this->offset] + 1);
					$this->offset = $cur_pos;
				}
				
				return TokenIds::NEW_LINE;
			case "\t":
			case " ":
				while(trim($this->peek()) == "" && $this->peek() != "\n") {
					$this->ignore();
				}
				
				return TokenIds::WHITESPACE;
			case ":":
				return TokenIds::COLON;
			case "=":
				return TokenIds::ASSIGN;
			case "@":
				return TokenIds::SECTION;
			case "{":
				return $this->readCode();
			case "[":
				return $this->readCondition();
			case "\"":
				return $this->readString();
			case "#":
				return $this->readComment();
			case "/":
				if ($this->peek() == "/") {
					$this->ignore();
					
					return $this->readComment();
				}
				break;
			case "\$":
				return $this->readDollar();
		}
		
		if ($char == "-" && $this->peek() == ">") {
			$this->ignore();
			
			return TokenIds::GOTO;
		}
		elseif ($char == "-" || is_numeric($char)) {
			return $this->readNumber();
		}
		elseif (preg_match('/^[a-z]$/i', $char)) {
			return $this->readIdentifier($char);
		}
		
		echo "Unknown character: '".$char."'\n";
		
		return TokenIds::NONE;
	}
	
	public function readCode() {
		while($this->peek() != "}") {
			$this->ignore();
		}
		$this->ignore();
		#fseek($this->handle, ftell($this->handle) - 1);
		
		return TokenIds::CODE;
	}
	
	public function readCondition() {
		while($this->peek() != "]") {
			$this->ignore();
		}
		$this->ignore();
		
		return TokenIds::CONDITION;
	}
	
	public function readString() {
		$this->ignore(PHP_INT_MAX, "\"");
		
		return TokenIds::STRING;
	}
	
	public function readComment() {
		$this->ignore(PHP_INT_MAX, "\n");
		fseek($this->handle, ftell($this->handle) - 1);
		
		return TokenIds::COMMENT;
	}
	
	public function readDollar() {
		$char = "";
		while(($char = $this->peek()) != "[" && $char != " " && $char != "\n" && $char != "\0") {
			$this->ignore();
		}
		
		return TokenIds::DOLLAR;
	}
	
	public function readNumber() {
		while(is_numeric($this->peek())) {
			$this->ignore();
		}
		
		if ($this->peek() == ".") {
			$this->ignore();
		}
		
		while(is_numeric($this->peek())) {
			$this->ignore();
		}
		
		return TokenIds::NUMBER;
	}
	
	public function readIdentifier($char) {
		$id = "".$char;
		while(preg_match('/^[a-z0-9]+$/i', $this->peek()) || $this->peek() == "_") {
			$id .= fread($this->handle, 1);
		}
		
		if ($id == "wait_while") {
			$this->readCode();
			
			return TokenIds::WAIT_WHILE;
		}
		
		return TokenIds::IDENTIFIER;
	}
	
	public function peek(int $length = 1) {
		$cur_pos = ftell($this->handle);
		for($i=0; $i<$length; $i++) {
			$output = fread($this->handle, 1);
		}
		fseek($this->handle, $cur_pos);
		
		return $output;
	}
	
	public function ignore(int $length = 1, $check_char = "\0") {
		for($i = 0; $i < $length; $i++) {
			$char = fread($this->handle, 1);
			if ($char == $check_char) {
				break;
			}
		}
	}
	
	public function getPosition() {
		return $this->position;
	}
	
	public function setPosition($position) {
		$this->position = $position;
		$this->next();
	}
	
	public function rewind() {
		$this->position = 0;
	}
	
	public function current() {
		return NULL;
	}
	
	public function key() {
		return $this->position;
	}
	
	public function next() {
		fseek($this->handle, $this->position);
		$this->readToken($this->token);
		$this->position = ftell($this->handle);
		
		return $this;
	}
	
	public function tokenPeek(Token $old_token = NULL) {
		if ($old_token == NULL) {
			$old_token = clone $this->token;
		}
		
		$token = clone $old_token;
		fseek($this->handle, $old_token->end_pos);
		$this->readToken($token);
		fseek($this->handle, $old_token->start_pos);
		
		return $token;
	}
	
	public function valid() {
		return true;
	}
}
?>