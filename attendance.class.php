<?PHP
/**
 * @class attendance
 * @author BJRambo (sosifam@070805.co.kr)
 * @brief attendance module class
 **/

class attendance extends ModuleObject
{

	private $triggers = array(
		array('member.deleteMember', 'attendance', 'controller', 'triggerDeleteMember', 'after'),
		array('member.doLogin', 'attendance', 'controller', 'triggerAutoAttend', 'after'),
		array('display', 'attendance', 'controller', 'triggerBeforeDisplay', 'before'),
		array('member.updateMember', 'attendance', 'controller', 'triggerUpdateMemberBefore', 'before'),
		array('moduleHandler.init', 'attendance', 'controller', 'triggerAutoAttendToEvery', 'after'),
	);

	private $delete_triggers = array(
		array('display', 'attendance', 'controller', 'triggerSou', 'before'),
	);

	/**
	 * @brief Install module in xpressengine.
	 **/
	function moduleInstall()
	{
		/** @var $oModuleController moduleController */
		$oModuleController = getController('module');

		/** @var $oModuleModel moduleModel */
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByMid('attendance');
		if ($module_info->module_srl)
		{
			if ($module_info->module != 'attendance')
			{
				return $this->makeObject(1, 'attend_error_mid');
			}
		}
		else
		{
			$args = new stdClass;
			$args->mid = 'attendance';
			$args->module = 'attendance';
			$args->browser_title = Context::getLang('attendance_module');
			$args->site_srl = 0;
			$args->skin = 'default';
			$args->order_type = 'desc';
			$output = $oModuleController->insertModule($args);
			if (!$output->toBool())
			{
				return $this->makeObject(-1, 'msg_invalid_request');
			}
		}
	}

	/**
	 * @brief Check the update list.
	 **/
	function checkUpdate()
	{
		/** @var  $oModuleModel moduleModel */
		$oModuleModel = getModel('module');

		if(FileHandler::exists('./modules/attendance/lang/lang.xml'))
		{
			return true;
		}

		$oDB = DB::getInstance();
		// This line start to add to database column list check.
		if (!$oDB->isColumnExists("attendance", "greetings"))
		{
			return true;
		}
		if (!$oDB->isColumnExists("attendance", "today_point"))
		{
			return true;
		}
		if (!$oDB->isColumnExists("attendance", "today_random"))
		{
			return true;
		}
		if (!$oDB->isColumnExists("attendance", "att_random_set"))
		{
			return true;
		}
		if (!$oDB->isColumnExists("attendance", "a_continuity"))
		{
			return true;
		}
		if (!$oDB->isColumnExists("attendance", "ipaddress"))
		{
			return true;
		}
		if (!$oDB->isColumnExists("attendance", "member_srl"))
		{
			return true;
		}
		if (!$oDB->isColumnExists("attendance_total", "member_srl"))
		{
			return true;
		}
		if (!$oDB->isColumnExists("attendance_weekly", "member_srl"))
		{
			return true;
		}
		if (!$oDB->isColumnExists("attendance_monthly", "member_srl"))
		{
			return true;
		}
		if (!$oDB->isColumnExists("attendance_yearly", "member_srl"))
		{
			return true;
		}
		if (!$oDB->isColumnExists("attendance", "perfect_m"))
		{
			return true;
		}
		if (!$oDB->isColumnExists("attendance", "present_y"))
		{
			return true;
		}

		// This line start to delete database column list check.
		if ($oDB->isColumnExists("attendance", "user_id"))
		{
			return true;
		}
		if ($oDB->isColumnExists("attendance_total", "user_id"))
		{
			return true;
		}
		if ($oDB->isColumnExists("attendance_weekly", "user_id"))
		{
			return true;
		}
		if ($oDB->isColumnExists("attendance_monthly", "user_id"))
		{
			return true;
		}
		if ($oDB->isColumnExists("attendance_yearly", "user_id"))
		{
			return true;
		}

		// Index check.
		if (!$oDB->isIndexExists('attendance', 'idx_regdate'))
		{
			return true;
		}
		if (!$oDB->isIndexExists('attendance', 'idx_member_srl'))
		{
			return true;
		}
		if (!$oDB->isIndexExists('attendance_weekly', 'idx_regdate'))
		{
			return true;
		}
		if (!$oDB->isIndexExists('attendance_weekly', 'idx_member_srl'))
		{
			return true;
		}
		if (!$oDB->isIndexExists('attendance_monthly', 'idx_regdate'))
		{
			return true;
		}
		if (!$oDB->isIndexExists('attendance_monthly', 'idx_member_srl'))
		{
			return true;
		}
		if (!$oDB->isIndexExists('attendance_yearly', 'idx_regdate'))
		{
			return true;
		}
		if (!$oDB->isIndexExists('attendance_yearly', 'idx_member_srl'))
		{
			return true;
		}
		if (!$oDB->isIndexExists('attendance_total', 'idx_regdate'))
		{
			return true;
		}
		if (!$oDB->isIndexExists('attendance_total', 'idx_member_srl'))
		{
			return true;
		}

		if($oDB->getColumnInfo('attendance', 'ipaddress')->size < 128)
		{
			return true;
		}

		$module_info = getModel('attendance')->getAttendanceInfo('attendance');
		if (!$module_info->module_srl)
		{
			return true;
		}

		foreach ($this->triggers as $trigger)
		{
			if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				return true;
			}
		}
		foreach ($this->delete_triggers as $delete_trigger)
		{
			if ($oModuleModel->getTrigger($delete_trigger[0], $delete_trigger[1], $delete_trigger[2], $delete_trigger[3], $delete_trigger[4]))
			{
				return true;
			}
		}
	}

	/**
	 * @brief update module.
	 **/
	function moduleUpdate()
	{
		/** @var $oModuleModel moduleModel */
		$oModuleModel = getModel('module');
		/** @var $oModuleController moduleController */
		$oModuleController = getController('module');
		$oMemberModel = getModel('member');

		$oDB = DB::getInstance();

		if(FileHandler::exists('./modules/attendance/lang/lang.xml'))
		{
			// 보통은 업데이트시 다운로드 받은 최신버전의 파일이 존재 하기에 실행할 필요 없지만, 파일이 없을 경우에 재 생성.
			if(!FileHandler::exists('./modules/attendance/lang/ko.php'))
			{
				Rhymix\Framework\Parsers\LangParser::convertDirectory(RX_BASEDIR . 'modules/attendance/lang', ['ko']);
				if(!FileHandler::exists('./modules/attendance/lang/ko.php'))
				{
					return new BaseObject(-1, '언어 변환에 문제가 있습니다.');
				}
			}

			FileHandler::removeFile('./modules/attendance/lang/lang.xml');
		}

		if (!$oDB->isColumnExists("attendance", "greetings"))
		{
			$oDB->addColumn("attendance", "greetings", "varchar", 20);
		}

		if (!$oDB->isColumnExists("attendance", "today_point"))
		{
			$oDB->addColumn("attendance", "today_point", "number", 20);
		}

		if (!$oDB->isColumnExists("attendance", "today_random"))
		{
			$oDB->addColumn("attendance", "today_random", "number", 20);
		}

		if (!$oDB->isColumnExists("attendance", "ipaddress"))
		{
			$oDB->addColumn("attendance", "ipaddress", "varchar", 23);
		}

		if (!$oDB->isColumnExists("attendance", "att_random_set"))
		{
			$oDB->addColumn("attendance", "att_random_set", "number", 20);
		}

		if (!$oDB->isColumnExists("attendance", "a_continuity"))
		{
			$oDB->addColumn("attendance", "a_continuity", "number", 20);
		}

		if (!$oDB->isColumnExists("attendance", "member_srl"))
		{
			$oDB->addColumn("attendance", "member_srl", "number", 11);
			$user_ids = executeQueryArray('attendance.migrationGetIdAttendance');
			if (!$user_ids->data)
			{
				$user_ids->data = array();
			}
			foreach ($user_ids->data as $value)
			{
				$member = $oMemberModel->getMemberInfoByUserId($value->user_id);
				if ($member->member_srl)
				{
					$args = new stdClass();
					$args->member_srl = $member->member_srl;
					$args->user_id = $member->user_id;
					executeQuery("attendance.migrationInsertMemberSrlAttendance", $args);
				}
			}
		}

		if (!$oDB->isColumnExists("attendance_total", "member_srl"))
		{
			$oDB->addColumn("attendance_total", "member_srl", "number", 11);
			$user_ids = executeQueryArray('attendance.migrationGetIdAttendanceTotal');
			if (!$user_ids->data)
			{
				$user_ids->data = array();
			}
			foreach ($user_ids->data as $value)
			{
				$member = $oMemberModel->getMemberInfoByUserId($value->user_id);
				if ($member->member_srl)
				{
					$args = new stdClass();
					$args->member_srl = $member->member_srl;
					$args->user_id = $member->user_id;
					executeQuery("attendance.migrationInsertMemberSrlAttendanceTotal", $args);
				}
			}
		}

		if (!$oDB->isColumnExists("attendance_weekly", "member_srl"))
		{
			$oDB->addColumn("attendance_weekly", "member_srl", "number", 11);
			$user_ids = executeQueryArray('attendance.migrationGetIdAttendanceWeekly');
			if (!$user_ids->data)
			{
				$user_ids->data = array();
			}
			foreach ($user_ids->data as $value)
			{
				$member = $oMemberModel->getMemberInfoByUserId($value->user_id);
				if ($member->member_srl)
				{
					$args = new stdClass;
					$args->member_srl = $member->member_srl;
					$args->user_id = $member->user_id;
					executeQuery("attendance.migrationInsertMemberSrlAttendanceWeekly", $args);
				}
			}
		}

		if (!$oDB->isColumnExists("attendance_monthly", "member_srl"))
		{
			$oDB->addColumn("attendance_monthly", "member_srl", "number", 11);
			$user_ids = executeQueryArray('attendance.migrationGetIdAttendanceMonthly');
			if (!$user_ids->data)
			{
				$user_ids->data = array();
			}
			foreach ($user_ids->data as $value)
			{
				$member = $oMemberModel->getMemberInfoByUserId($value->user_id);
				if ($member->member_srl)
				{
					$args = new stdClass;
					$args->member_srl = $member->member_srl;
					$args->user_id = $member->user_id;
					executeQuery("attendance.migrationInsertMemberSrlAttendanceMonthly", $args);
				}
			}
		}

		if (!$oDB->isColumnExists("attendance_yearly", "member_srl"))
		{
			$oDB->addColumn("attendance_yearly", "member_srl", "number", 11);
			$user_ids = executeQueryArray('attendance.migrationGetIdAttendanceYearly');
			if (!$user_ids->data)
			{
				$user_ids->data = array();
			}
			foreach ($user_ids->data as $value)
			{
				$member = $oMemberModel->getMemberInfoByUserId($value->user_id);
				if ($member->member_srl)
				{
					$args = new stdClass;
					$args->member_srl = $member->member_srl;
					$args->user_id = $member->user_id;
					executeQuery("attendance.migrationInsertMemberSrlAttendanceYearly", $args);
				}
			}
		}

		if($oDB->getColumnInfo('attendance', 'ipaddress')->size < 128)
		{
			$oDB->modifyColumn('attendance', 'ipaddress', 'varchar', 128, null, true);
		}

		if ($oDB->isColumnExists("attendance", "user_id"))
		{
			$oDB->dropColumn("attendance", "user_id");
		}

		if ($oDB->isColumnExists("attendance_total", "user_id"))
		{
			$oDB->dropColumn("attendance_total", "user_id");
		}

		if ($oDB->isColumnExists("attendance_weekly", "user_id"))
		{
			$oDB->dropColumn("attendance_weekly", "user_id");
		}

		if ($oDB->isColumnExists("attendance_monthly", "user_id"))
		{
			$oDB->dropColumn("attendance_monthly", "user_id");
		}

		if ($oDB->isColumnExists("attendance_yearly", "user_id"))
		{
			$oDB->dropColumn("attendance_yearly", "user_id");
		}

		if (!$oDB->isColumnExists("attendance", "perfect_m"))
		{
			$oDB->addColumn("attendance", "perfect_m", "char", 1);
		}

		if (!$oDB->isColumnExists("attendance", "present_y"))
		{
			$oDB->addColumn("attendance", "present_y", "char", 1);
		}

		// Index check.
		if (!$oDB->isIndexExists('attendance', 'idx_regdate'))
		{
			$oDB->addIndex('attendance', 'idx_regdate', array('regdate'));
		}
		if (!$oDB->isIndexExists('attendance', 'idx_member_srl'))
		{
			$oDB->addIndex('attendance', 'idx_member_srl', array('member_srl'));
		}
		if (!$oDB->isIndexExists('attendance_weekly', 'idx_regdate'))
		{
			$oDB->addIndex('attendance_weekly', 'idx_regdate', array('regdate'));
		}
		if (!$oDB->isIndexExists('attendance_weekly', 'idx_member_srl'))
		{
			$oDB->addIndex('attendance_weekly', 'idx_member_srl', array('member_srl'));
		}
		if (!$oDB->isIndexExists('attendance_monthly', 'idx_regdate'))
		{
			$oDB->addIndex('attendance_monthly', 'idx_regdate', array('regdate'));
		}
		if (!$oDB->isIndexExists('attendance_monthly', 'idx_member_srl'))
		{
			$oDB->addIndex('attendance_monthly', 'idx_member_srl', array('member_srl'));
		}
		if (!$oDB->isIndexExists('attendance_yearly', 'idx_regdate'))
		{
			$oDB->addIndex('attendance_yearly', 'idx_regdate', array('regdate'));
		}
		if (!$oDB->isIndexExists('attendance_yearly', 'idx_member_srl'))
		{
			$oDB->addIndex('attendance_yearly', 'idx_member_srl', array('member_srl'));
		}
		if (!$oDB->isIndexExists('attendance_total', 'idx_regdate'))
		{
			$oDB->addIndex('attendance_total', 'idx_regdate', array('regdate'));
		}
		if (!$oDB->isIndexExists('attendance_total', 'idx_member_srl'))
		{
			$oDB->addIndex('attendance_total', 'idx_member_srl', array('member_srl'));
		}

		if (!$oModuleModel->getActionForward('procAttendanceInsertConfig')->module)
		{
			$oModuleController->deleteActionForward('attendance', 'controller', 'procAttendanceInsertConfig');
		}

		$module_info = $oModuleModel->getModuleInfoByMid('attendance');

		if (!$module_info->module_srl)
		{
			$args = new stdClass;
			$args->mid = 'attendance';
			$args->module = 'attendance';
			$args->browser_title = '출석체크';
			$args->site_srl = 0;
			$args->skin = 'default';
			$args->order_type = 'desc';
			$output = $oModuleController->insertModule($args);
			if ($output->toBool())
			{
				return $output;
			}
		}
		else
		{
			if ($module_info->module != 'attendance')
			{
				return $this->makeObject(1, 'attend_error_mid');
			}
		}

		foreach ($this->triggers as $trigger)
		{
			if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		foreach ($this->delete_triggers as $delete_trigger)
		{
			if ($oModuleModel->getTrigger($delete_trigger[0], $delete_trigger[1], $delete_trigger[2], $delete_trigger[3], $delete_trigger[4]))
			{
				$oModuleController->deleteTrigger($delete_trigger[0], $delete_trigger[1], $delete_trigger[2], $delete_trigger[3], $delete_trigger[4]);
			}
		}

		return $this->makeObject(0, 'success_updated');
	}

	/**
	 * @brief Recompile cache files
	 **/
	function recompileCache()
	{
	}

	/**
	 * Create new Object for php7.2
	 * @param int $code
	 * @param string $msg
	 * @return BaseObject|Object
	 */
	public function makeObject($code = 0, $msg = 'success')
	{
		return class_exists('BaseObject') ? new BaseObject($code, $msg) : new Object($code, $msg);
	}

	/**
	 * Add to cache type.
	 * Get the https://github.com/poesis/xe-supercache/blob/a2b5cf100c7768c29df541dd792c13428f78d6ac/supercache.class.php#L130
	 * @return bool
	 */
	protected function _getCacheType()
	{
		if(defined('RX_BASEDIR'))
		{
			return config('cache.type') ? true : false;
		}
		else
		{
			$db_info = Context::getDbInfo();
			return isset($db_info->use_object_cache) ?: false;
		}
	}
}
