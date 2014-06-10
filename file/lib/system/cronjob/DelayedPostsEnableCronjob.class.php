<?php
namespace wbb\system\cronjob;
use \wbb\data\post\PostAction;
use \wbb\data\thread\ThreadAction;
use \wcf\data\cronjob\Cronjob;
use \wcf\system\cronjob\AbstractCronjob;
use \wcf\system\database\util\PreparedStatementConditionBuilder;
use \wcf\system\WCF;

/**
 * Enables delayed posts
 *
 * @author	Tim Düsterhus, Sascha Ehrler
 * @copyright	2010-2014 Tim Düsterhus
 * @license	Creative Commons BY-NC-SA <https://creativecommons.org/licenses/by-nc-sa/4.0/legalcode>
 * @package	de.bisaboard.wbb.delayedPosts
 * @subpackage	system.cronjob
 */
class DelayedPostsEnableCronjob extends AbstractCronjob {
	/**
	 * @see	\wcf\system\cronjob\ICronjob::execute()
	 */
	public function execute(Cronjob $cronjob) {
		parent::execute($cronjob);
		
		WCF::getDB()->beginTransaction();
		try {
			$threadIDs = array();
			$sql = "SELECT		t.threadID
				FROM		wbb".WCF_N."_thread t
				INNER JOIN	wbb".WCF_N."_post p
				ON		t.firstPostID = p.postID
				WHERE		p.isDisabled = ?
					AND	p.enableTime < ?
					AND	p.enableTime <> ?
				FOR UPDATE";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute(array(1, TIME_NOW, 0));
			while ($threadID = $statement->fetchColumn()) $threadIDs[] = $threadID;
			
			if (!empty($threadIDs)) {
				$action = new ThreadAction($threadIDs, 'update', array('data' => array(
					'time' => TIME_NOW
				)));
				$action->executeAction();
			}
			
			// select all posts which have to be enabled, will also enable threads
			$sql = "SELECT	postID
				FROM	wbb".WCF_N."_post
				WHERE		isDisabled = ?
					AND	enableTime < ?
					AND	enableTime <> ?";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute(array(1, TIME_NOW, 0));
			
			$postIDs = array();
			while ($postID = $statement->fetchColumn()) $postIDs[] = $postID;
			
			if (!empty($postIDs)) {
				// change date
				$action = new PostAction($postIDs, 'update', array('data' => array(
					'time' => TIME_NOW
				)));
				$action->executeAction();
				
				$action = new PostAction($postIDs, 'enable');
				try {
					$action->validateAction();
				}
				catch (\wcf\system\exception\PermissionDeniedException $e) {
					// validateAction may throw an undesired PermissionDeniedException
					// as the user executing the cronjob does not neccesarily have the
					// permission to enable posts
				}
				
				$action->executeAction();
			}
			
			// remove enable time on every post
			$sql = "SELECT	postID
				FROM	wbb".WCF_N."_post
				WHERE		isDisabled = ?
					AND	enableTime <> ?
				FOR UPDATE";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute(array(0, 0));
			
			$postIDs = array();
			while ($postID = $statement->fetchColumn()) $postIDs[] = $postID;
			
			if (!empty($postIDs)) {
				$action = new PostAction($postIDs, 'update', array('data' => array(
					'enableTime' => 0
				)));
				$action->executeAction();
			}
			
			WCF::getDB()->commitTransaction();
		}
		catch (\Exception $e) {
			WCF::getDB()->rollbackTransaction();
			
			throw $e;
		}
	}
}
