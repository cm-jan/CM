<?php

class CM_Maintenance_Cli extends CM_Cli_Runnable_Abstract {

	/**
	 * @synchronized
	 */
	public function common() {
		CM_Model_User::offlineOld();
		CM_ModelAsset_User_Roles::deleteOld();
		CM_Paging_Useragent_Abstract::deleteOlder(100 * 86400);
		CM_File_UserContent_Temp::deleteOlder(86400);
		CM_SVM::deleteOldTrainings(3000);
		CM_SVM::trainChanged();
		CM_Paging_Ip_Blocked::deleteOlder(7 * 86400);
		CM_Captcha::deleteOlder(3600);
		CM_ModelAsset_User_Roles::deleteOld();
		CM_Session::deleteExpired();
		CM_Wowza::getInstance()->synchronize();
		CM_Wowza::getInstance()->checkStreams();
		CM_KissTracking::getInstance()->exportEvents();
		CM_Mysql::exec("DELETE FROM TBL_SK_USER_INVITATIONCODE WHERE `createStamp` < ?", (time() - 30 * 86400));
		CM_Mysql::exec("DELETE FROM TBL_SK_USER_EMAILVERIFICATION WHERE `createStamp` < ?", (time() - 7 * 86400));
		CM_Mysql::exec("DELETE FROM TBL_SK_USER_PASSWORDRESET WHERE `createStamp` < ?", (time() - 1 * 86400));
		CM_Mysql::exec("DELETE FROM TBL_SK_USER_VIEWHISTORY WHERE `timeStamp` < ?", (time() - 31 * 86400));
	}

	/**
	 * @synchronized
	 */
	public function heavy() {
		CM_Mail::processQueue(500);
		CM_Action_Abstract::aggregate();
	}

	public static function getPackageName() {
		return 'maintenance';
	}

}
