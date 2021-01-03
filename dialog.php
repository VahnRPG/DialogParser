<?php
require_once("ast/_node.php");
require_once("parser/_parser.php");

class Dialog {
	public $name = "";
	public $actor = "";
	public $state = DialogStates::NONE;

	private $file_name;
	private $parser;
	
	private $cur_section = NULL;
	private $cur_statement = 0;
	private $wait_action = NULL;
	
	private $say_choice = true;
	private $limit = 6;
	private $choices = array();
	private $next_section = NULL;
	
	private $states = array();
	
	public function __construct($file_name) {
		$this->name = pathinfo($file_name)["filename"];
		$this->file_name = $file_name;
	}
	
	public function start($actor, $section) {
		$this->resetState();
		$this->actor = $actor;
		
		$this->parser = new Parser($this->file_name);
		$this->sections = $this->parser->parse();
		
		$this->cur_statement = 0;
		$this->selectSection($section);
	}
	
	private function resetState() {
		$this->say_choice = true;
		$this->limit = 6;
		
		$remove_state = false;
		foreach($this->states as $key => $state) {
			if ($state["mode"] == "temp_once") {
				$remove_state = true;
			}
			
			if ($remove_state) {
				unset($this->states[$key]);
			}
		}
	}
	
	private function selectSection($section_name) {
		echo "Select: ".$section_name."\n";
		$this->clearChoices();
		$this->cur_section = NULL;
		foreach($this->sections as $section) {
			if ($section->name == $section_name) {
				$this->cur_section = $section;
				$this->state = DialogStates::START;
				$this->update();
				
				return;
			}
		}
		
		$this->state = DialogStates::NONE;
	}
	
	private function endDialog() {
		$this->cur_section = NULL;
		$this->state = DialogStates::NONE;
	}
	
	public function update() {
		switch ($this->state) {
			case DialogStates::NONE:
				break;
			case DialogStates::START:
			case DialogStates::RUNNING:
				$this->running();
				break;
			case DialogStates::WAIT_CHOICE:
				break;
			case DialogStates::WAIT_ANIMATION:
				$wait_action = $this->wait_action;
				if (is_null($wait_action) || $wait_action()) {
					$this->wait_action = NULL;
					$this->cur_statement++;
					$this->state = DialogStates::RUNNING;
				}
				break;
			case DialogStates::WAIT_SAY_CHOICE:
				$wait_action = $this->wait_action;
				if (is_null($wait_action) || $wait_action()) {
					$this->wait_action = NULL;
					$this->selectSection($this->next_section);
				}
				break;
		}
	}
	
	public function choose(int $choice_id) {
		if ($this->state != DialogStates::WAIT_CHOICE) {
			return;
		}
		
		$i = 1;
		foreach($this->choices as $choice) {
			if (is_null($choice)) {
				continue;
			}
			
			echo "  Here: ".$choice_id." == ".$i."!\n";
			echo "    ".$choice->expression->number."\n";
			if ($choice_id == $i++) {
				$expression = $choice->expression;
				foreach($choice->conditions as $condition) {
					echo "Here!\n";
				}
				
				if ($this->say_choice) {
					$this->state = DialogStates::WAIT_SAY_CHOICE;
					$this->wait_action = $this->say($this->actor, $expression->text);
					$this->next_section = $expression->goto->name;
					
					return;
				}
				
				$this->selectLabel($choice->expression->name);
				
				return;
			}
		}
		die;
	}
	
	public function say($actor, $text) {
		return function() use ($actor, $text) {
			echo ucwords($actor)." says, '".$text."'\n";
			
			return true;
		};
	}
	
	private function running() {
		if (is_null($this->cur_section)) {
			$this->state = DialogStates::NONE;
			
			return;
		}
		
		$statements = count($this->cur_section->statements);
		if ($this->cur_statement == $statements) {
			echo "  Goto next\n";
			$this->gotoNextLabel();
			
			return;
		}
		
		$this->state = DialogStates::RUNNING;
		while($this->cur_statement < $statements && $this->state == DialogStates::RUNNING) {
			$statement = $this->cur_section->statements[$this->cur_statement];
			if (!$this->acceptConditions($statement)) {
				$this->cur_statement++;
				continue;
			}
			
			if ($statement->expression instanceof ChoiceExpression) {
				$this->addChoice($statement, $statement->expression);
				$this->cur_statement++;
				continue;
			}
			
			if ($this->choicesReady()) {
				$this->state = DialogStates::WAIT_CHOICE;
				
				return;
			}
			
			$this->run($statement);
			$statements = (!is_null($this->cur_section) ? count($this->cur_section->statements) : 0);
			if ($this->state != DialogStates::WAIT_ANIMATION) {
				$this->cur_statement++;
			}
		}
		
		if ($this->choicesReady()) {
			$this->state = DialogStates::WAIT_CHOICE;
			
			return;
		}
		
		if ($this->state == DialogStates::RUNNING) {
			$this->gotoNextLabel();
		}
	}
	
	private function run(Statement $statement) {
		if (!$this->acceptConditions($statement)) {
			return;
		}
		
		$visitor = new ExpressionVisitor($this);
		$statement->expression->accept($visitor);
		$this->wait_action = $visitor->getWaitAction();
		if ($this->wait_action) {
			$this->state = DialogStates::WAIT_ANIMATION;
		}
	}
	
	private function gotoNextLabel() {
		if (is_null($this->cur_section)) {
			$this->endDialog();
			
			return false;
		}
		
		$cur_key = NULL;
		foreach($this->sections as $key => $section) {
			if ($section->name == $this->cur_section->name) {
				$cur_key = $key;
				break;
			}
		}
		
		if ($cur_key == count($this->sections)) {
			$this->endDialog();
			
			return false;
		}
		$cur_key++;
		if ($cur_key == count($this->sections)) {
			$this->endDialog();
			
			return false;
		}
		$this->selectSection($this->sections[$cur_key]->name);
		
		return true;
	}
	
	private function clearChoices() {
		$this->choices = array();
		for($i=0; $i<9; $i++) {
			$this->choices[$i] = NULL;
		}
	}
	
	private function addChoice(Statement $statement, ChoiceExpression $expression) {
		if (isset($this->choices[$expression->number]) && !is_null($this->choices[$expression->number])) {
			return;
		}
		
		$count = 0;
		foreach($this->choices as $choice) {
			if ($choice != NULL) {
				$count++;
			}
		}
		if($count >= $this->limit) {
			return;
		}
		
		$this->choices[$expression->number - 1] = $statement;
	}
	
	private function choicesReady() {
		foreach($this->choices as $choice) {
			if ($choice != NULL) {
				return true;
			}
		}
	
		return false;
	}
	
	private function acceptConditions(Statement $statement) {
		$visitor = new ConditionVisitor($this);
		foreach($statement->conditions as $condition) {
			$condition->accept($visitor);
			if (!$visitor->isAccepted()) {
				return false;
			}
		}
		
		$visitor = new ConditionStateVisitor($this, "show");
		foreach($statement->conditions as $condition) {
			$condition->accept($visitor);
			$state = $visitor->getState();
			if ($state != array()) {
				$this->states[] = $state;
			}
		}
		
		return true;
	}
	
	public function goToSection($section) {
		$this->selectSection($section);
	}
	
	public function processIsOnce(int $line) {
		return $this->checkState("once", $line);
	}
	
	public function processIsShowOnce(int $line) {
		return $this->checkState("show_once", $line);
	}
	
	public function processIsOnceEver(int $line) {
		return $this->checkState("once_ever", $line, false);
	}
	
	public function processIsTempOnce(int $line) {
		return $this->checkState("temp_once", $line);
	}
	
	private function checkState(string $mode, int $line, $check_actor = true) {
		$found = 0;
		foreach($this->states as $key => $state) {
			if ($state["mode"] == $mode && ($check_actor && $state["actor"] == $this->actor) && $state["dialog"] == $this && $state["line"] == $line) {
				$found = $key;
				break;
			}
		}
		
		return ($found == count($this->states));
	}
	
	public function processIsCodeCondition(string $code) {
		
	}
}

class DialogStates {
	const NONE = "NONE";
	const START = "START";
	const RUNNING = "RUNNING";
	const WAIT_CHOICE = "WAIT_CHOICE";
	const WAIT_ANIMATION = "WAIT_ANIMATION";
	const WAIT_SAY_CHOICE = "WAIT_SAY_CHOICE";
}
?>