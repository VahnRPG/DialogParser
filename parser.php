<?php
require_once("node.php");

class Parser {
	private $reader;
	
	public function __construct(Reader $reader) {
		$this->reader = $reader;
	}
	
	public function parse() {
		$labels = array();
		while (!$this->match(array(TokenIds::END))) {
			$labels[] = $this->parseLabel();
		}
		echo "Here: ".count($labels)."!\n";
		print_r($labels);
		
		return $labels;
	}
	
	private function parseLabel() {
		$this->reader->next();
		
		$label = new Label($this->reader->readTokenText());
		echo "Label: ".$label->name."!\n";
		do {
			if ($this->match(array(TokenIds::COLON)) || $this->match(array(TokenIds::END))) {
				break;
			}
			
			#echo "Statement\n";
			$label->addStatement($this->parseStatement($label));
		} while(true);
		echo "\n";
		
		return $label;
	}
	
	private function parseStatement(Label $label) {
		$statement = new Statement($label);
		$statement->setExpression($this->parseExpression($statement));
		
		while($this->match(array(TokenIds::CONDITION))) {
			$statement->addCondition($this->parseCondition($statement));
		}
		
		return $statement;
	}
	
	private function parseCondition($statement) {
		$text = $this->reader->readTokenText($this->reader->token);
		$condition_text = substr($text, 1, strlen($text) - 2);
		$line = $this->reader->getLine();
		
		if ($condition_text == "once") {
			return new OnceCondition($statement, $line);
		}
		elseif ($condition_text == "showonce") {
			return new ShowOnceCondition($statement, $line);
		}
		elseif ($condition_text == "onceever") {
			return new OnceEverCondition($statement, $line);
		}
		elseif ($condition_text == "temponce") {
			return new TempOnceCondition($statement, $line);
		}
		
		$condition = new CodeCondition($statement, $line);
		$condition->code = $condition_text;
		
		return $condition;
	}
	
	private function parseExpression(Statement $statement) {
		if ($this->match(array(TokenIds::IDENTIFIER, TokenIds::COLON, TokenIds::STRING))) {
			echo "  Expression 1\n";
			return $this->parseSayExpression($statement);
		}
		if ($this->match(array(TokenIds::WAIT_WHILE))) {
			echo "  Expression 2\n";
			return $this->parseWaitWhileExpression($statement);
		}
		if ($this->match(array(TokenIds::IDENTIFIER))) {
			echo "  Expression 3\n";
			return $this->parseInstructionExpression($statement);
		}
		if ($this->match(array(TokenIds::GOTO))) {
			echo "  Expression 4\n";
			return $this->parseGotoExpression($statement);
		}
		echo "Token Check5: ".$this->reader->token->id."!\n";
		if ($this->match(array(TokenIds::NUMBER))) {
			echo "  Expression 5\n";
			return $this->parseChoiceExpression($statement);
		}
		echo "Token Check6: ".$this->reader->token->id."!\n";
		if ($this->match(array(TokenIds::CODE))) {
			echo "  Expression 6\n";
			return $this->parseCodeExpression($statement);
		}
		echo "Token Check7: ".$this->reader->token->id."!\n";
			echo "  Expression 7\n";
		
		return NULL;
	}
	
	private function match(array $token_ids) {
		//This function makes sure the tokens follow the given token id setup
		//{ identifier, colon, string } means the line has to be "[person]: text"
		$token = clone $this->reader->token;
		foreach($token_ids as $token_id) {
			if ($token->id != $token_id) {
				return false;
			}
			$token = $this->reader->tokenPeek($token);
		}
	
		return true;
	}
	
	private function parseSayExpression(Statement $statement) {
		$actor = $this->reader->readTokenText($this->reader->token);
		$this->reader->next();
		$text = $this->reader->readTokenText();
		$this->reader->next();
		
		$expression = new SayExpression($statement);
		$expression->actor = $actor;
		$expression->text = substr($text, 1, strlen($text) - 2);
		
		return $expression;
	}
	
	private function parseWaitWhileExpression(Statement $statement) {
		$wait_while = $this->reader->readTokenText();
		
		$expression = new WaitWhileExpression($statement);
		$expression->condition = substr($wait_while, 10);
		
		return $expression;
	}
	
	private function parseInstructionExpression(Statement $statement) {
		$identifier = $this->reader->readTokenText();
		echo "Identifier: ".$identifier."!\n";
		if ($identifier == "shutup") {
			return new ShutupExpression($statement);
		}
		elseif ($identifier == "pause") {
			$expression = new PauseExpression($statement);
			$expression->pause_time = (float) $this->reader->readTokenText();
			
			return $expression;
		}
		elseif ($identifier == "waitfor") {
			$expression = new WaitForExpression($statement);
			if ($this->reader->token->id == TokenIds::IDENTIFIER) {
				$expression->actor = $this->reader->readTokenText();
			}
			
			return $expression;
		}
		elseif ($identifier == "parrot") {
			$expression = new ParrotExpression($statement);
			if ($this->reader->token->id == TokenIds::IDENTIFIER) {
				$expression->active = (strtolower($this->reader->readTokenText()) == "yes");
			}
			
			return $expression;
		}
		elseif ($identifier == "dialog") {
			$expression = new DialogExpression($statement);
			if ($this->reader->token->id == TokenIds::IDENTIFIER) {
				$expression->actor = $this->reader->readTokenText();
			}
			
			return $expression;
		}
		elseif ($identifier == "override") {
			$expression = new OverrideExpression($statement);
			if ($this->reader->token->id == TokenIds::IDENTIFIER) {
				$expression->node = $this->reader->readTokenText();
			}
			
			return $expression;
		}
		elseif ($identifier == "allowobjects") {
			$expression = new AllowObjectsExpression($statement);
			if ($this->reader->token->id == TokenIds::IDENTIFIER) {
				$expression->allow = (strtolower($this->reader->readTokenText()) == "yes");
			}
			
			return $expression;
		}
		elseif ($identifier == "limit") {
			$expression = new LimitExpression($statement);
			if ($this->reader->token->id == TokenIds::NUMBER) {
				$expression->limit = (int) $this->reader->readTokenText();
			}
			
			return $expression;
		}
		
		throw new \Exception("Unknown instruction: ".$identifier);
	}
	
	private function parseGotoExpression(Statement $statement) {
		$this->reader->next();
		
		$expression = new GotoExpression($statement);
		$expression->name = $this->reader->readTokenText();
		
		return $expression;
	}
	
	private function parseCodeExpression(Statement $statement) {
		$code = $this->reader->readTokenText();
		
		$expression = new CodeExpression($statement);
		$expression->code = substr($code, 1);
		
		return $expression;
	}
	
	private function parseChoiceExpression(Statement $statement) {
		$number = (int) $this->reader->readTokenText($this->reader->token);
		$this->reader->next();
		
		$text = "";
		if ($this->reader->token->id == TokenIds::DOLLAR) {
			$text = $this->reader->readTokenText($this->reader->token);
		}
		else {
			$text = $this->reader->readTokenText($this->reader->token);
			$text = substr($text, 1, strlen($text) - 2);
		}
		
		$this->reader->next();
		$expression = new ChoiceExpression($statement);
		$expression->number = $number;
		$expression->text = $text;
		$expression->goto = $this->parseGotoExpression($statement);
		
		return $expression;
	}
}
?>