<?php
namespace wbb\system\cronjob;
use \wbb\data\post\PostAction;
use \wcf\data\cronjob\Cronjob;
use \wcf\system\cronjob\AbstractCronjob;
use \wcf\system\database\util\PreparedStatementConditionBuilder;
use \wcf\system\WCF;

/**
 * Enables delayed posts
 *
 * @author		Sascha Ehrler
 * @copyright	2013 Sascha Ehrler
 * @license		Creative Commons BY-NC-ND <http://creativecommons.org/licenses/by-nc-nd/3.0/de/>
 * @package		de.bisaboard.wbb.delayedPosts
 * @subpackage	system.cronjob
 */
class DelayedPostsEnableCronjob extends AbstractCronjob {
	/**
	 * @see	\wcf\system\cronjob\ICronjob::execute()
	 */
	public function execute(Cronjob $cronjob) {
		parent::execute($cronjob);
		
		WCF::getDB()->beginTransaction();
		
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
			$conditions = new PreparedStatementConditionBuilder();
			$conditions->add("threadID IN (?)", array($threadIDs));
			
			$sql = "UPDATE
					wbb".WCF_N."_thread
				SET
					time = ".TIME_NOW."
				".$conditions;
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditions->getParameters());
		}
		
		$sql = "UPDATE
				wbb".WCF_N."_post
			SET
				time = ".TIME_NOW."
			WHERE
				isDisabled = 1
			AND	enableTime < ".TIME_NOW."
			AND	enableTime <> 0";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		WCF::getDB()->commitTransaction();
		
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
			$action = new PostAction($postIDs, 'enable');
			$action->executeAction();
		}
	}
}
