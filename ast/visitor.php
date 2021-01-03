<?php
abstract class Visitor {
	protected $dialog;
	protected $accepted = false;
	
	public function __construct(Dialog $dialog) {
		$this->dialog = $dialog;
	}
	
	abstract public function visit(Node $node);
	
	public function defaultVisit(Node $node) {
	}
	
	public function isAccepted() {
		return $this->accepted;
	}
}

class ExpressionVisitor extends Visitor {
	private $wait_action = NULL;
	
	public function visit(Node $node) {
		if ($node instanceof SayExpression) {
			$function = $this->dialog->say($node->actor, $node->text);
			$this->wait_action = function() use ($function) {
				return $function();
			};
		}
		if ($node instanceof CodeExpression) {
			$this->dialog->execute($node->code);
		}
		if ($node instanceof GotoExpression) {
			$this->dialog->goToSection($node->name);
		}
		if ($node instanceof StopTalkingExpression) {
			$this->dialog->stopTalking();
		}
		if ($node instanceof PauseExpression) {
			$this->wait_action = $this->dialog->pause($node->pause_time);
		}
		if ($node instanceof WaitForExpression) {
			$this->wait_action = $this->dialog->waitFor($node->condition);
		}
		if ($node instanceof SayChoiceExpression) {
			$this->dialog->sayChoice($node->active);
		}
		if ($node instanceof DialogExpression) {
			$this->dialog->dialog($node->actor);
		}
		if ($node instanceof OverrideExpression) {
			$this->dialog->override($node->node);
		}
		if ($node instanceof AllowObjectsExpression) {
			$this->dialog->allowObjects($node->allow);
		}
		if ($node instanceof WaitWhileExpression) {
			$this->wait_action = $this->dialog->waitWhile($node->condition);
		}
		if ($node instanceof LimitExpression) {
			$this->dialog->setLimit($node->limit);
		}
	}
	
	public function getWaitAction() {
		return $this->wait_action;
	}
}

class ConditionVisitor extends Visitor {
	public function visit(Node $node) {
		if ($node instanceof OnceCondition) {
			return $this->dialog->processIsOnce($node->getLine());
		}
		if ($node instanceof ShowOnceCondition) {
			return $this->dialog->processIsShowOnce($node->getLine());
		}
		if ($node instanceof OnceEverCondition) {
			return $this->dialog->processIsOnceEver($node->getLine());
		}
		if ($node instanceof TempOnceCondition) {
			return $this->dialog->processIsTempOnce($node->getLine());
		}
		if ($node instanceof CodeCondition) {
			return $this->dialog->processIsCodeCondition($node->code);
		}
	}
}

class ConditionStateVisitor extends Visitor {
	private $select_mode;
	private $state = array();
	
	public function __construct(Dialog $dialog, string $select_mode) {
		parent::__construct($dialog);
		
		$this->select_mode = $select_mode;
	}
	
	public function visit(Node $node) {
		if ($node instanceof OnceCondition) {
			if ($this->select_mode == "choose") {
				$this->setState($node->getLine(), "once");
			}
		}
		if ($node instanceof ShowOnceCondition) {
			if ($this->select_mode == "show") {
				$this->setState($node->getLine(), "show_once");
			}
		}
		if ($node instanceof OnceEverCondition) {
			if ($this->select_mode == "show") {
				$this->setState($node->getLine(), "once_ever");
			}
		}
		if ($node instanceof TempOnceCondition) {
			if ($this->select_mode == "show") {
				$this->setState($node->getLine(), "temp_once");
			}
		}
	}
	
	public function getState() {
		return $this->state;
	}
	
	private function setState(int $line, string $mode) {
		$this->state = array(
			"line" => $line,
			"mode" => $mode,
			"dialog" => $this->dialog,
			"actor" => $this->dialog->actor,
		);
	}
}
?>