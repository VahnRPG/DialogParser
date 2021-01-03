<?php
require_once("parser/reader.php");
require_once("parser/tokens.php");

class Parser {
	private $reader;
	
	public function __construct($file_name) {
		$this->reader = new Reader($file_name);
	}
	
	public function parse() {
		$sections = array();
		while (!$this->match(array(TokenIds::END))) {
			$section = $this->processSection();
			$sections[] = $section;
		}
		
		return $sections;
	}
	
	private function processSection() {
		$this->reader->next();
		
		$section = new Section($this->reader->readTokenText());
		#echo "Section: ".$section->name."!\n";
		do {
			if ($this->match(array(TokenIds::SECTION)) || $this->match(array(TokenIds::END))) {
				break;
			}
			
			$section->addStatement($this->processStatement($section));
		} while(true);
		
		return $section;
	}
	
	private function processStatement(Section $section) {
		$statement = new Statement($section);
		$statement->setExpression($this->processExpression($statement));
		
		while($this->match(array(TokenIds::CONDITION))) {
			$statement->addCondition($this->processCondition($statement));
		}
		
		return $statement;
	}
	
	private function processExpression(Statement $statement) {
		if ($this->match(array(TokenIds::IDENTIFIER, TokenIds::COLON, TokenIds::STRING))) {
			return $this->processSayExpression($statement);
		}
		if ($this->match(array(TokenIds::WAIT_WHILE))) {
			return $this->processWaitWhileExpression($statement);
		}
		if ($this->match(array(TokenIds::IDENTIFIER))) {
			return $this->processInstructionExpression($statement);
		}
		if ($this->match(array(TokenIds::GOTO))) {
			return $this->processGotoExpression($statement);
		}
		if ($this->match(array(TokenIds::NUMBER))) {
			return $this->processChoiceExpression($statement);
		}
		if ($this->match(array(TokenIds::CODE))) {
			return $this->processCodeExpression($statement);
		}
		
		return NULL;
	}
	
	private function processCondition($statement) {
		$text = $this->reader->readTokenText($this->reader->token);
		$condition_text = substr($text, 1, strlen($text) - 2);
		$line = $this->reader->getLine();
		
		if ($condition_text == "once") {
			return new OnceCondition($statement, $line);
		}
		elseif ($condition_text == "show_once") {
			return new ShowOnceCondition($statement, $line);
		}
		elseif ($condition_text == "once_ever") {
			return new OnceEverCondition($statement, $line);
		}
		elseif ($condition_text == "temp_once") {
			return new TempOnceCondition($statement, $line);
		}
		
		$condition = new CodeCondition($statement, $line);
		$condition->code = $condition_text;
		
		return $condition;
	}
	
	private function processSayExpression(Statement $statement) {
		$actor = $this->reader->readTokenText();
		$this->reader->next();
		$text = $this->reader->readTokenText();
		
		$expression = new SayExpression($statement);
		$expression->actor = $actor;
		$expression->text = substr($text, 1, strlen($text) - 2);
		
		return $expression;
	}
	
	private function processWaitWhileExpression(Statement $statement) {
		$wait_while = $this->reader->readTokenText();
		
		$expression = new WaitWhileExpression($statement);
		$expression->condition = substr($wait_while, 10);
		
		return $expression;
	}
	
	private function processInstructionExpression(Statement $statement) {
		$identifier = $this->reader->readTokenText();
		if ($identifier == "stop_talking") {
			return new StopTalkingExpression($statement);
		}
		elseif ($identifier == "pause") {
			$expression = new PauseExpression($statement);
			$expression->pause_time = (float) $this->reader->readTokenText();
			
			return $expression;
		}
		elseif ($identifier == "wait_for") {
			$expression = new WaitForExpression($statement);
			if ($this->reader->token->id == TokenIds::IDENTIFIER) {
				$expression->actor = $this->reader->readTokenText();
			}
			
			return $expression;
		}
		elseif ($identifier == "say_choice") {
			$expression = new SayChoiceExpression($statement);
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
		elseif ($identifier == "allow_objects") {
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
	
	private function processGotoExpression(Statement $statement) {
		$this->reader->next();
		
		$expression = new GotoExpression($statement);
		$expression->name = $this->reader->readTokenText();
		
		return $expression;
	}
	
	private function processCodeExpression(Statement $statement) {
		$code = $this->reader->readTokenText();
		
		$expression = new CodeExpression($statement);
		$expression->code = substr($code, 1, strlen($code) - 2);
		
		return $expression;
	}
	
	private function processChoiceExpression(Statement $statement) {
		$number = (int) $this->reader->readTokenText($this->reader->token);
		echo "Here: ".$number."!\n";
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
		$expression->goto = $this->processGotoExpression($statement);
		
		return $expression;
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
}
?>