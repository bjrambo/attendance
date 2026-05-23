<?php
namespace Rhymix\Modules\Attendance\Controllers;

/**
 * Attendance module installer and updater.
 */
class Install extends Base
{
	private $_triggers = [
		['member.deleteMember', 'attendance', 'controller', 'triggerDeleteMember', 'after'],
		['member.doLogin', 'attendance', 'controller', 'triggerAutoAttend', 'after'],
		['display', 'attendance', 'controller', 'triggerBeforeDisplay', 'before'],
		['member.updateMember', 'attendance', 'controller', 'triggerUpdateMemberBefore', 'before'],
		['moduleHandler.init', 'attendance', 'controller', 'triggerAutoAttendToEvery', 'after'],
	];

	private $_delete_triggers = [
		['display', 'attendance', 'controller', 'triggerSou', 'before'],
	];

	public function moduleInstall()
	{
		$oModuleController = getController('module');
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
			$args = new \stdClass;
			$args->mid = 'attendance';
			$args->module = 'attendance';
			$args->browser_title = \Context::getLang('attendance_module');
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

	public function checkUpdate()
	{
		$oModuleModel = getModel('module');

		if (\FileHandler::exists('./modules/attendance/lang/lang.xml'))
		{
			return true;
		}

		$oDB = \DB::getInstance();
		if (!$oDB->isColumnExists('attendance', 'greetings')) return true;
		if (!$oDB->isColumnExists('attendance', 'today_point')) return true;
		if (!$oDB->isColumnExists('attendance', 'today_random')) return true;
		if (!$oDB->isColumnExists('attendance', 'att_random_set')) return true;
		if (!$oDB->isColumnExists('attendance', 'a_continuity')) return true;
		if (!$oDB->isColumnExists('attendance', 'ipaddress')) return true;
		if (!$oDB->isColumnExists('attendance', 'member_srl')) return true;
		if (!$oDB->isColumnExists('attendance_total', 'member_srl')) return true;
		if (!$oDB->isColumnExists('attendance_weekly', 'member_srl')) return true;
		if (!$oDB->isColumnExists('attendance_monthly', 'member_srl')) return true;
		if (!$oDB->isColumnExists('attendance_yearly', 'member_srl')) return true;
		if (!$oDB->isColumnExists('attendance', 'perfect_m')) return true;
		if (!$oDB->isColumnExists('attendance', 'present_y')) return true;
		if ($oDB->isColumnExists('attendance', 'user_id')) return true;
		if ($oDB->isColumnExists('attendance_total', 'user_id')) return true;
		if ($oDB->isColumnExists('attendance_weekly', 'user_id')) return true;
		if ($oDB->isColumnExists('attendance_monthly', 'user_id')) return true;
		if ($oDB->isColumnExists('attendance_yearly', 'user_id')) return true;
		if (!$oDB->isIndexExists('attendance', 'idx_regdate')) return true;
		if (!$oDB->isIndexExists('attendance', 'idx_member_srl')) return true;
		if (!$oDB->isIndexExists('attendance_weekly', 'idx_regdate')) return true;
		if (!$oDB->isIndexExists('attendance_weekly', 'idx_member_srl')) return true;
		if (!$oDB->isIndexExists('attendance_monthly', 'idx_regdate')) return true;
		if (!$oDB->isIndexExists('attendance_monthly', 'idx_member_srl')) return true;
		if (!$oDB->isIndexExists('attendance_yearly', 'idx_regdate')) return true;
		if (!$oDB->isIndexExists('attendance_yearly', 'idx_member_srl')) return true;
		if (!$oDB->isIndexExists('attendance_total', 'idx_regdate')) return true;
		if (!$oDB->isIndexExists('attendance_total', 'idx_member_srl')) return true;
		if ($oDB->getColumnInfo('attendance', 'ipaddress')->size < 128) return true;

		$module_info = getModel('attendance')->getAttendanceInfo();
		if (!$module_info->module_srl) return true;

		foreach ($this->_triggers as $trigger)
		{
			if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				return true;
			}
		}
		foreach ($this->_delete_triggers as $delete_trigger)
		{
			if ($oModuleModel->getTrigger($delete_trigger[0], $delete_trigger[1], $delete_trigger[2], $delete_trigger[3], $delete_trigger[4]))
			{
				return true;
			}
		}
	}

	public function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		$oMemberModel = getModel('member');
		$oDB = \DB::getInstance();

		if (\FileHandler::exists('./modules/attendance/lang/lang.xml'))
		{
			if (!\FileHandler::exists('./modules/attendance/lang/ko.php'))
			{
				\Rhymix\Framework\Parsers\LangParser::convertDirectory(\RX_BASEDIR . 'modules/attendance/lang', ['ko']);
				if (!\FileHandler::exists('./modules/attendance/lang/ko.php'))
				{
					return new \BaseObject(-1, '언어 변환에 문제가 있습니다.');
				}
			}
			\FileHandler::removeFile('./modules/attendance/lang/lang.xml');
		}

		if (!$oDB->isColumnExists('attendance', 'greetings')) $oDB->addColumn('attendance', 'greetings', 'varchar', 20);
		if (!$oDB->isColumnExists('attendance', 'today_point')) $oDB->addColumn('attendance', 'today_point', 'number', 20);
		if (!$oDB->isColumnExists('attendance', 'today_random')) $oDB->addColumn('attendance', 'today_random', 'number', 20);
		if (!$oDB->isColumnExists('attendance', 'ipaddress')) $oDB->addColumn('attendance', 'ipaddress', 'varchar', 23);
		if (!$oDB->isColumnExists('attendance', 'att_random_set')) $oDB->addColumn('attendance', 'att_random_set', 'number', 20);
		if (!$oDB->isColumnExists('attendance', 'a_continuity')) $oDB->addColumn('attendance', 'a_continuity', 'number', 20);

		if (!$oDB->isColumnExists('attendance', 'member_srl'))
		{
			$oDB->addColumn('attendance', 'member_srl', 'number', 11);
			$user_ids = executeQueryArray('attendance.migrationGetIdAttendance');
			if (!$user_ids->data) $user_ids->data = [];
			foreach ($user_ids->data as $value)
			{
				$member = $oMemberModel->getMemberInfoByUserId($value->user_id);
				if ($member->member_srl)
				{
					$args = new \stdClass();
					$args->member_srl = $member->member_srl;
					$args->user_id = $member->user_id;
					executeQuery('attendance.migrationInsertMemberSrlAttendance', $args);
				}
			}
		}

		foreach (['Total', 'Weekly', 'Monthly', 'Yearly'] as $table)
		{
			$tbl = 'attendance_' . strtolower($table);
			if (!$oDB->isColumnExists($tbl, 'member_srl'))
			{
				$oDB->addColumn($tbl, 'member_srl', 'number', 11);
				$user_ids = executeQueryArray('attendance.migrationGetIdAttendance' . $table);
				if (!$user_ids->data) $user_ids->data = [];
				foreach ($user_ids->data as $value)
				{
					$member = $oMemberModel->getMemberInfoByUserId($value->user_id);
					if ($member->member_srl)
					{
						$args = new \stdClass;
						$args->member_srl = $member->member_srl;
						$args->user_id = $member->user_id;
						executeQuery('attendance.migrationInsertMemberSrlAttendance' . $table, $args);
					}
				}
			}
		}

		if ($oDB->getColumnInfo('attendance', 'ipaddress')->size < 128)
		{
			$oDB->modifyColumn('attendance', 'ipaddress', 'varchar', 128, null, true);
		}

		foreach (['attendance', 'attendance_total', 'attendance_weekly', 'attendance_monthly', 'attendance_yearly'] as $tbl)
		{
			if ($oDB->isColumnExists($tbl, 'user_id')) $oDB->dropColumn($tbl, 'user_id');
		}

		if (!$oDB->isColumnExists('attendance', 'perfect_m')) $oDB->addColumn('attendance', 'perfect_m', 'char', 1);
		if (!$oDB->isColumnExists('attendance', 'present_y')) $oDB->addColumn('attendance', 'present_y', 'char', 1);

		foreach (['attendance', 'attendance_weekly', 'attendance_monthly', 'attendance_yearly', 'attendance_total'] as $tbl)
		{
			if (!$oDB->isIndexExists($tbl, 'idx_regdate')) $oDB->addIndex($tbl, 'idx_regdate', ['regdate']);
			if (!$oDB->isIndexExists($tbl, 'idx_member_srl')) $oDB->addIndex($tbl, 'idx_member_srl', ['member_srl']);
		}

		if (!$oModuleModel->getActionForward('procAttendanceInsertConfig')->module)
		{
			$oModuleController->deleteActionForward('attendance', 'controller', 'procAttendanceInsertConfig');
		}

		$module_info = $oModuleModel->getModuleInfoByMid('attendance');
		if (!$module_info->module_srl)
		{
			$args = new \stdClass;
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

		foreach ($this->_triggers as $trigger)
		{
			if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}
		foreach ($this->_delete_triggers as $delete_trigger)
		{
			if ($oModuleModel->getTrigger($delete_trigger[0], $delete_trigger[1], $delete_trigger[2], $delete_trigger[3], $delete_trigger[4]))
			{
				$oModuleController->deleteTrigger($delete_trigger[0], $delete_trigger[1], $delete_trigger[2], $delete_trigger[3], $delete_trigger[4]);
			}
		}

		return $this->makeObject(0, 'success_updated');
	}

	public function recompileCache(): void {}
}
