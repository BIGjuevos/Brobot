<?php

namespace Brobot\Plugin;

class PluginMsgQueue extends \Brobot\Plugin {
	const MESSAGE_SECONDS = 5;
	const MESSAGE_BATCH = 3;
	const MESSAGE_HOURS = 2;
	const MESSAGE_MAXLEN = 255;

	private $_lastTime;

	public function __construct($bot) {
		parent::__construct($bot);
		$this->_lastTime = time();
	}

	public function execute() {
		$bot = $this->_bot;
		$now = time();
		if ($this->_lastTime > ($now - self::MESSAGE_SECONDS)) {
			return;
		}
		$this->_lastTime = $now;
		$msgs = $this->getMessages();
		foreach ($msgs as $m) {
			$message = substr(str_replace("\n",' ',$m['command']),0,self::MESSAGE_MAXLEN);
			$bot->send($message);
		}
		$this->setSent($msgs);
	}

	protected function getMessages() {
		$msgs = array();
		if ($db = $this->getDb()) {
			$query = "select * from botCmdQueue commandTime > CURRENT_TIMESTAMP - interval ".self::MESSAGE_HOURS." hour order by commandTime limit ".self::MESSAGE_BATCH;
			if ($result = $db->query($query)) {
				while ($row = $result->fetch_assoc()) {
					$msgs[] = $row;
				}
			}
		}
		return $msgs;
	}

	protected function setSent($msgs) {
		if ($db = $this->getDb()) {
			$ids = array();
			foreach ($msgs as $m) {
				$ids[] = $m['id'];
			}
			if (!empty($ids)) {
				$query = "delete from botCmdQueue where id in (".implode(',',$ids).")";
				$db->query($query);
			}
		}
	}

	protected function getDb() {
		return Db::getInstance();
	}

	public function queueMessage($message) {
		$db = Db::getInstance();
		$query = "
			INSERT INTO botCmdQueue
			SET
				command='".$db->real_escape_string($message)."',
				commandTime=NOW()
			";
		$db->query($query);
	}
}

?>
