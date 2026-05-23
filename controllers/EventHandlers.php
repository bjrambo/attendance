<?php
namespace Rhymix\Modules\Attendance\Controllers;

/**
 * Attendance module event trigger handlers.
 * Registered via moduleUpdate(); called by Rhymix on module/member events.
 */
class EventHandlers extends Base
{
	/**
	 * Delete all attendance data when a member account is removed.
	 */
	public function triggerDeleteMember(object $obj): object
	{
		$oAttendanceAdminModel = getAdminModel('attendance');
		$oAttendanceAdminModel->deleteAllAttendanceData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceTotalData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceYearlyData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceMonthlyData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceWeeklyData($obj->member_srl);
		getModel('attendance')->clearCacheByMemberSrl($obj->member_srl);
		return $this->makeObject();
	}

	/**
	 * Auto-attend on every page load (moduleHandler.init).
	 */
	public function triggerAutoAttendToEvery(object $oModule): void
	{
		$today = date('Ymd');
		if ($_SESSION['is_attended'] === $today)
		{
			return;
		}

		if (!\Context::get('is_logged'))
		{
			return;
		}

		$oAttendanceModel = getModel('attendance');
		$config = $oAttendanceModel->getConfig();
		if ($config->about_auto_attend != 'yes')
		{
			return;
		}

		$logged_info = \Context::get('logged_info');

		if ($oCacheHandler = $oAttendanceModel->getCacheHandler())
		{
			$dayData = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "member:{$logged_info->member_srl}:day:{$today}"), time() - 86400);
			if ($dayData !== false && $dayData->data->count > 0)
			{
				return;
			}
		}
		else
		{
			$dayData = false;
		}

		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByMid('attendance');
		$grant = $oModuleModel->getGrant($module_info, $logged_info);
		if (!$grant->attendance)
		{
			return;
		}

		if (!$dayData)
		{
			$arg = new \stdClass;
			$arg->day = $today;
			$arg->member_srl = $logged_info->member_srl;
			$output = executeQuery('attendance.getIsChecked', $arg);
			if ($output->data->count > 0)
			{
				$_SESSION['is_attended'] = $today;
				return;
			}

			if (isset($oCacheHandler) && $oCacheHandler)
			{
				$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "member:{$logged_info->member_srl}:day:{$today}"), $output, 86400);
			}
		}

		$args = new \stdClass();
		$args->greetings = '^auto^';
		$output = $this->insertAttendance($args, $config, $logged_info->member_srl);
		if (!$output || !$output->toBool())
		{
			return;
		}

		$_SESSION['is_attended'] = $today;

		if (isset($oCacheHandler) && $oCacheHandler)
		{
			$arg = new \stdClass;
			$arg->day = $today;
			$arg->member_srl = $logged_info->member_srl;
			$cacheOutput = executeQuery('attendance.getIsChecked', $arg);
			if ($cacheOutput->data->count > 0)
			{
				$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "member:{$logged_info->member_srl}:day:{$today}"), $cacheOutput, 86400);
			}
		}
	}

	/**
	 * Stub for legacy doLogin trigger (superseded by triggerAutoAttendToEvery).
	 */
	public function triggerAutoAttend(object $obj): object
	{
		return $this->makeObject();
	}

	/**
	 * Prevent birthday display change in member edit form when locked.
	 */
	public function triggerBeforeDisplay(string &$content): object
	{
		$oAttendanceModel = getModel('attendance');
		$config = $oAttendanceModel->getConfig();
		$act = \Context::get('act');

		if ($act === 'dispMemberModifyInfo' && $config->about_birth_day === 'yes' && $config->about_birth_day_y === 'yes')
		{
			$content = str_replace(
				'<input type="text" placeholder="YYYY-MM-DD" name="birthday_ui"',
				\Context::getLang('출석부모듈에 의해 생일 변경이 금지되었습니다.') . '<br><input type="text" name="birthday" placeholder="YYYY-MM-DD" disabled="disabled"',
				$content
			);
			$content = str_replace('<input type="button" value="삭제"', '<input type="button" value="삭제" disabled="disabled"', $content);
		}

		return $this->makeObject();
	}

	/**
	 * Prevent birthday modification via member update when locked by config.
	 */
	public function triggerUpdateMemberBefore(object $args): ?object
	{
		$logged_info = \Context::get('logged_info');
		$oMemberModel = getModel('member');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($logged_info->member_srl);

		$config = getModel('module')->getModuleConfig('attendance');

		if ($logged_info->is_admin !== 'Y' && $config->about_birth_day === 'yes' && $config->about_birth_day_y === 'yes')
		{
			if ($member_info->birthday !== $args->birthday)
			{
				return $this->makeObject(-1, '출석부모듈에 의해 생일을 수정 할 수 없도록 되어있습니다.');
			}
		}

		return null;
	}

	/**
	 * Add attendance links to member popup menu.
	 */
	public function triggerAddMemberMenu(): object
	{
		if (!\Context::get('is_logged'))
		{
			return $this->makeObject();
		}

		$logged_info = \Context::get('logged_info');
		$target_srl = \Context::get('target_srl');
		$oMemberController = getController('member');
		$oMemberController->addMemberMenu('dispAttendanceMemberInfo', '출석사항');

		if ($logged_info->is_admin === 'Y')
		{
			$url = getUrl('', 'mid', 'attendance', 'act', 'dispAttendanceMemberInfo', 'member_srl', $target_srl);
			$oMemberController->addMemberPopupMenu($url, '회원출석사항', '');
		}

		return $this->makeObject();
	}
}
