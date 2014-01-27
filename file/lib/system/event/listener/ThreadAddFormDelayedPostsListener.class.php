<?php
namespace wbb\system\event\listener;
use \wcf\system\exception\UserInputException;
use \wcf\system\event\IEventListener;
use \wcf\system\WCF;

/**
 * Settings for delayed posts when creating post / thread
 *
 * @author	Tim Düsterhus, Sascha Ehrler
 * @copyright	2010-2014 Tim Düsterhus
 * @license	Creative Commons BY-NC-SA <https://creativecommons.org/licenses/by-nc-sa/4.0/legalcode>
 * @package	de.bisaboard.wbb.delayedPosts
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
		if ($className == 'wbb\form\ThreadAddForm' && !$eventObj->board->getModeratorPermission('canEnableThread')) return;
		if ($className == 'wbb\form\PostAddForm' && !$eventObj->board->getModeratorPermission('canEnablePost')) return;
		
		switch ($eventName) {
			case 'assignVariables':
				WCF::getTPL()->assign(array(
					'delayedEnable' => $this->delayedEnable,
					'delayedTime' => $this->timestamp
				));
			break;
			
			case 'readFormParameters':
				if (isset($_POST['delayedTime'])) $this->timestamp = strtotime($_POST['delayedTime'].' GMT');
				if (isset($_POST['delayedEnable'])) $this->delayedEnable = true;
			break;
			
			case 'validate':
				if ($this->delayedEnable) {
					if (!$this->timestamp || $this->timestamp < TIME_NOW) {
						throw new UserInputException('delayedTime', 'notValid');
					}
				}
			break;
			
			case 'save':
				if (!$this->delayedEnable) return;
				
				if ($className == 'wbb\form\ThreadAddForm') {
					$eventObj->additionalPostFields['enableTime'] = $this->timestamp;
					$eventObj->disableThread = true;
				}
				else if ($className == 'wbb\form\PostAddForm') {
					$eventObj->additionalFields['enableTime'] = $this->timestamp;
					$eventObj->disablePost = true;
				}
			break;
		}
	}
}
