<?php
namespace wbb\system\event\listener;
use \wcf\system\exception\UserInputException;
use \wcf\system\event\IEventListener;
use \wcf\system\WCF;

/**
 * Settings for delayed posts in PostEditForm
 *
 * @author	Sascha Ehrler
 * @copyright	2013 Sascha Ehrler
 * @license		Creative Commons BY-NC-ND <http://creativecommons.org/licenses/by-nc-nd/3.0/de/>
 * @package		de.bisaboard.wbb.delayedPosts
 * @subpackage	system.event.listener
 */
class PostEditFormDelayedPostsListener implements IEventListener {
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
	 * @see	EventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		if (!$eventObj->board->getModeratorPermission('canEnablePost')) return;
		
		
		switch ($eventName) {
			case 'assignVariables':
				WCF::getTPL()->assign(array(
					'delayedEnable' => $this->delayedEnable,
					'delayedTime' => $this->timestamp
				));
			break;
			
			case 'readData':
				if (empty($_POST) && $eventObj->post->enableTime != 0) {
					$this->timestamp = $eventObj->post->enableTime;
					$this->delayedEnable = true;
				}
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
				// delete the timestamp if no longer delayed
				if (!$this->delayedEnable) {
					$eventObj->additionalFields['enableTime'] = 0;
					return;
				}
				
				$eventObj->additionalFields['enableTime'] = $this->timestamp;
			break;
		}
	}
}
