<?php
namespace wbb\system\event\listener;
use \wcf\system\exception\UserInputException;
use \wcf\system\event\IEventListener;
use \wcf\system\WCF;

/**
 * Settings for delayed posts when creating post / thread
 *
 * @author		Sascha Ehrler
 * @copyright	2013 Sascha Ehrler
 * @license		Creative Commons BY-NC-ND <http://creativecommons.org/licenses/by-nc-nd/3.0/de/>
 * @package		de.bisaboard.wbb.delayedPosts
 * @subpackage	system.event.listener
 */
class ThreadAddFormDelayedPostsListener implements IEventListener {
	/**
	 * is delay enabled
	 * 
	 * @var boolean
	 */
	public $delayedEnable = false;
	/**
	 * chosen datetime
	 * 
	 * @var datetime
	 */
	public $timestamp = TIME_NOW;
	
	/**
	 * @see	\wbb\system\event\IEventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		if ($className == 'ThreadAddForm' && !$eventObj->board->getModeratorPermission('canEnableThread')) return;
		if ($className == 'PostAddForm' && !$eventObj->board->getModeratorPermission('canEnablePost')) return;

		switch ($eventName) {
			case 'assignVariables':
				WCF::getTPL()->assign(array(
					'delayedEnable' => $this->delayedEnable,
					'delayedTime' => $this->timestamp
				));
			break;
			case 'readFormParameters':
				if (isset($_POST['delayedTime'])) $this->timestamp = strtotime($_POST['delayedTime']);
				if (isset($_POST['delayedEnable'])) $this->delayedEnable = true;
			break;
			case 'validate':
				if ($this->delayedEnable) {
					if (!$this->timestamp || $this->timestamp < TIME_NOW) throw new UserInputException('delayedTime', 'notValid');
				}
			break;
			case 'save':
				if (!$this->delayedEnable) return;
				
				if ($className == 'ThreadAddForm') {
					$eventObj->additionalPostFields['enableTime'] = $this->timestamp;
					$eventObj->disableThread = true;
				} else if ($className == 'PostAddForm') {
					$eventObj->additionalFields['enableTime'] = $this->timestamp;
					$eventObj->disablePost = true;
				}
			break;
		}
	}
}
?>