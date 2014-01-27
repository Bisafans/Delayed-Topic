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
	 * is this the starting post of an thread
	 * 
	 * @var boolean
	 */
	public $startingPost = false;
	
	/**
	 * @see	EventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		if (!$eventObj->board->getModeratorPermission('canEnablePost')) return;
		
		if ($eventObj->post->enableTime != 0) {
			$this->timestamp = $eventObj->post->enableTime;
			$this->delayedEnable = true;
		}
		
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
				// delete the timestamp if no longer delayed
				if (!$this->delayedEnable) {
					if ($eventObj->isFirstPost) {
						$eventObj->additionalPostFields['enableTime'] = 0;
					}
					else {
						$eventObj->additionalFields['enableTime'] = 0;
					}
					return;
				}
				
				if ($eventObj->isFirstPost) {
					$eventObj->additionalPostFields['enableTime'] = $this->timestamp;
					$eventObj->disableThread = true;
				}
				else {
					$eventObj->additionalFields['enableTime'] = $this->timestamp;
					$eventObj->disablePost = true;
				}
			break;
		}
	}
}
