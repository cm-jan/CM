<?php

final class CM_JobManager extends CM_Class_Abstract {

	/** @var array */
	private $_children;

	public function __construct() {
		declare(ticks = 1);
		pcntl_signal(SIGTERM, array($this, '_handleSignal'));
		pcntl_signal(SIGINT, array($this, '_handleSignal'));
	}

	public function start() {
		while (true) {
			if (count($this->_children) == $this->_getConfig()->workerCount) {
				if ($pid = pcntl_wait($status, WNOHANG) > 0) {
					unset($this->_children[$pid]);
					$this->_startWorker();
				}
				usleep(50000);
			} else {
				$this->_startWorker();
			}
		}
	}

	private function _startWorker() {
		$pid = pcntl_fork();
		switch ($pid) {
			case 0: //child
				$worker = new CM_JobWorker();
				$worker->run();
				exit;
				break;
			case -1: //failure
				throw new CM_Exception('Could not fork Gearman Job Manager');
			default: //parent
				$this->_children[$pid] = $pid;
		}
	}

	private function _handleSignal($signal) {
		switch ($signal) {
			case SIGTERM:
			case SIGINT:
				foreach ($this->_children as $child) {
					posix_kill($child, SIGKILL);
				}
				exit;
		}
	}

}