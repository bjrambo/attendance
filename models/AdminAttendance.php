<?php
namespace Rhymix\Modules\Attendance\Models;

/**
 * Attendance module admin model.
 */
class AdminAttendance extends Base
{
	public function getAttendanceMemberList(int $list, string $new_type): object
	{
		$oAttendanceModel = new Attendance();

		$args = new \stdClass();
		$args->is_admin = \Context::get('is_admin') === 'Y' ? 'Y' : '';
		$args->is_denied = \Context::get('is_denied') === 'Y' ? 'Y' : '';
		$args->selected_group_srl = \Context::get('selected_group_srl');
		$args->unjoined_members = \Context::get('unjoined_members') === 'Y' ? 'Y' : '';

		$type = $new_type ?: \Context::get('type');
		$args->year = substr(\Context::get('selected_date'), 0, 4);
		$args->year_month = substr(\Context::get('selected_date'), 0, 6);
		$week = $oAttendanceModel->getWeek(\Context::get('selected_date'));
		$args->monday = $week->monday;
		$args->sunday = $week->sunday;

		if (!$args->year) $args->year = zDate(date('YmdHis'), 'Y');
		if (!$args->year_month) $args->year_month = zDate(date('YmdHis'), 'Ym');
		if (!$week->monday || !$week->sunday)
		{
			$week = $oAttendanceModel->getWeek(zDate(date('YmdHis'), 'Ymd'));
			$args->monday = $week->monday;
			$args->sunday = $week->sunday;
		}

		$search_target = trim(\Context::get('search_target'));
		$search_keyword = trim(\Context::get('search_keyword'));

		if ($search_target && $search_keyword)
		{
			switch ($search_target)
			{
				case 'user_id':
					$args->s_user_id = str_replace(' ', '%', $search_keyword);
					break;
				case 'user_name':
					$args->s_user_name = str_replace(' ', '%', $search_keyword);
					break;
				case 'nick_name':
					$args->s_nick_name = str_replace(' ', '%', $search_keyword);
					break;
				case 'email_address':
					$args->s_email_address = str_replace(' ', '%', $search_keyword);
					break;
				case 'regdate':
					$args->s_regdate = preg_replace('/[^0-9]/', '', $search_keyword);
					break;
				case 'regdate_more':
					$args->s_regdate_more = substr(preg_replace('/[^0-9]/', '', $search_keyword) . '00000000000000', 0, 14);
					break;
				case 'regdate_less':
					$args->s_regdate_less = substr(preg_replace('/[^0-9]/', '', $search_keyword) . '00000000000000', 0, 14);
					break;
				case 'last_login':
					$args->s_last_login = $search_keyword;
					break;
				case 'last_login_more':
					$args->s_last_login_more = substr(preg_replace('/[^0-9]/', '', $search_keyword) . '00000000000000', 0, 14);
					break;
				case 'last_login_less':
					$args->s_last_login_less = substr(preg_replace('/[^0-9]/', '', $search_keyword) . '00000000000000', 0, 14);
					break;
				case 'extra_vars':
					$args->s_extra_vars = preg_replace('/[^0-9]/', '', $search_keyword);
					break;
			}
		}

		if ($type === 'day' || $type === 'speedsearch')
		{
			if ($args->selected_group_srl)
			{
				$query_id = 'member.getMemberListWithinGroup';
				$args->sort_index = 'member.member_srl';
				$args->sort_order = 'desc';
			}
			else
			{
				$query_id = 'member.getMemberList';
				$args->sort_index = 'member_srl';
				$args->sort_order = 'desc';
			}
		}
		elseif ($type === 'ranktotal')
		{
			$query_id = $args->selected_group_srl ? 'attendance.getMemberListWithinGroup' : 'attendance.getMemberList';
			$args->sort_index = 'attendance_total.total';
		}
		elseif ($type === 'rankyearly')
		{
			$query_id = $args->selected_group_srl ? 'attendance.getMemberListWithinGroupYearly' : 'attendance.getMemberListYearly';
			$args->sort_index = 'attendance_yearly.yearly';
		}
		elseif ($type === 'rankmonthly')
		{
			$query_id = $args->selected_group_srl ? 'attendance.getMemberListWithinGroupMonthly' : 'attendance.getMemberListMonthly';
			$args->sort_index = 'attendance_monthly.monthly';
		}
		elseif ($type === 'rankweekly')
		{
			$query_id = $args->selected_group_srl ? 'attendance.getMemberListWithinGroupWeekly' : 'attendance.getMemberListWeekly';
			$args->sort_index = 'attendance_weekly.weekly';
		}

		$args->page = \Context::get('page');
		$args->list_count = $list;
		$args->page_count = 10;
		return executeQuery($query_id, $args);
	}

	public function getTodayTotalCount(string $today): int
	{
		static $cache = [];
		if (isset($cache[$today])) return $cache[$today];

		if (strtotime($today) < time() - (86400 * 2))
		{
			if ($oCacheHandler = (new Attendance())->getCacheHandler())
			{
				$cached = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "todaytotal:$today"));
				if ($cached !== false) return $cache[$today] = $cached;
			}
		}
		else
		{
			$oCacheHandler = null;
		}

		$arg = new \stdClass();
		$arg->today = $today;
		$output = executeQuery('attendance.getTodayTotalCount', $arg);
		$cache[$today] = (int)$output->data->count;

		if (isset($oCacheHandler) && $oCacheHandler)
		{
			$expires = 86400 * (31 - min(31, (int)substr($today, -2)));
			$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "todaytotal:$today"), $cache[$today], $expires);
		}
		return $cache[$today];
	}

	public function getTodayTimeCount(string $today_time): int
	{
		static $cache = [];
		if (isset($cache[$today_time])) return $cache[$today_time];
		$arg = new \stdClass();
		$arg->today_time = $today_time;
		$output = executeQuery('attendance.getTodayTimeCount', $arg);
		return $cache[$today_time] = (int)$output->data->count;
	}

	public function getTodayTimeCountList(string $today)
	{
		$args = new \stdClass();
		$args->today = $today;
		$output = executeQueryArray('attendance.getTodayTimeList', $args);

		if (!$output->data) return false;

		$time_array = [];
		foreach ($output->data as $val)
		{
			$time_array[] = substr($val->regdate, 8, 2);
		}
		return array_count_values($time_array);
	}

	public function deleteAllAttendanceData(int $member_srl): void
	{
		$oAttendanceModel = new Attendance();
		$oDocumentController = \DocumentController::getInstance();
		$memberAttendanceInfo = $oAttendanceModel->getGreetingsList($member_srl);

		if (!$memberAttendanceInfo->data->greetings)
		{
			foreach ($memberAttendanceInfo->data as $data)
			{
				if (substr($data->greetings, 0, 1) === '#')
				{
					$document_srl = substr($data->greetings, 1);
					$oDocumentController->deleteDocument($document_srl, true);
				}
			}
		}

		$args = new \stdClass();
		$args->member_srl = $member_srl;
		executeQuery('attendance.deleteAllAttendanceData', $args);
		$oAttendanceModel->clearCacheByMemberSrl($member_srl);
	}

	public function deleteAllAttendanceTotalData(int $member_srl): void
	{
		$args = new \stdClass();
		$args->member_srl = $member_srl;
		executeQuery('attendance.deleteAllAttendanceTotalData', $args);
		(new Attendance())->clearCacheByMemberSrl($member_srl);
	}

	public function deleteAllAttendanceYearlyData(int $member_srl): void
	{
		$args = new \stdClass();
		$args->member_srl = $member_srl;
		executeQuery('attendance.deleteAllAttendanceYearlyData', $args);
		(new Attendance())->clearCacheByMemberSrl($member_srl);
	}

	public function deleteAllAttendanceMonthlyData(int $member_srl): void
	{
		$args = new \stdClass();
		$args->member_srl = $member_srl;
		executeQuery('attendance.deleteAllAttendanceMonthlyData', $args);
		(new Attendance())->clearCacheByMemberSrl($member_srl);
	}

	public function deleteAllAttendanceWeeklyData(int $member_srl): void
	{
		$args = new \stdClass();
		$args->member_srl = $member_srl;
		executeQuery('attendance.deleteAllAttendanceWeeklyData', $args);
		(new Attendance())->clearCacheByMemberSrl($member_srl);
	}

	public function deleteAttendanceWeeklyData(int $member_srl, object $week): void
	{
		$args = new \stdClass();
		$args->monday = $week->monday;
		$args->sunday = $week->sunday;
		$args->member_srl = $member_srl;
		executeQuery('attendance.deleteAttendanceWeeklyData', $args);
		(new Attendance())->clearCacheByMemberSrl($member_srl, 'weekly', $week);
	}

	public function deleteAttendanceMonthlyData(int $member_srl, string $monthly): void
	{
		$args = new \stdClass();
		$args->monthly = $monthly;
		$args->member_srl = $member_srl;
		executeQuery('attendance.deleteAttendanceMonthlyData', $args);
		(new Attendance())->clearCacheByMemberSrl($member_srl, 'monthly', $monthly);
	}

	public function deleteAttendanceYearlyData(int $member_srl, string $year): void
	{
		$args = new \stdClass();
		$args->year = $year;
		$args->member_srl = $member_srl;
		executeQuery('attendance.deleteAttendanceYearlyData', $args);
		(new Attendance())->clearCacheByMemberSrl($member_srl, 'yearly', $year);
	}

	public function getWeeklyPoint(int $member_srl, $week): object
	{
		$args = new \stdClass();
		$args->member_srl = $member_srl;
		$args->monday = $week->monday;
		$args->sunday = $week->sunday;
		$output = executeQueryArray('attendance.getWeeklyPoint', $args);
		if (!$output->data) $output->data = [];
		return $output;
	}

	public function getMonthlyPoint(int $member_srl, string $monthly): object
	{
		$args = new \stdClass();
		$args->member_srl = $member_srl;
		$args->monthly = $monthly;
		$output = executeQueryArray('attendance.getMonthlyPoint', $args);
		if (!$output->data) $output->data = [];
		return $output;
	}

	public function getYearlyPoint(int $member_srl, string $year): object
	{
		$args = new \stdClass();
		$args->member_srl = $member_srl;
		$args->year = $year;
		$output = executeQueryArray('attendance.getYearlyPoint', $args);
		if (!$output->data) $output->data = [];
		return $output;
	}

	public function getTotalPoint(int $member_srl): object
	{
		$args = new \stdClass();
		$args->member_srl = $member_srl;
		$output = executeQueryArray('attendance.getYearlyPoint', $args);
		if (!$output->data) $output->data = [];
		return $output;
	}

	public function getDuplicatedData(int $member_srl, string $selected_date): object
	{
		$args = new \stdClass();
		$args->member_srl = $member_srl;
		$args->selected_date = $selected_date;
		$output = executeQueryArray('attendance.getDuplicatedData', $args);
		if (!$output->data) $output->data = [];
		return $output;
	}

	public function deleteDuplicatedData(int $member_srl, string $selected_date): object
	{
		$args = new \stdClass();
		$args->member_srl = $member_srl;
		$args->selected_date = $selected_date;
		return executeQuery('attendance.deleteAllAttendanceData', $args);
	}

	public function fixYearMonthWeek(object $obj): void
	{
		$oAttendanceModel = new Attendance();
		$oAttendanceController = new \Rhymix\Modules\Attendance\Controllers\Index();

		$year = substr($obj->selected_date, 0, 4);
		$this->deleteAttendanceYearlyData($obj->member_srl, $year);
		$points = $this->getYearlyPoint($obj->member_srl, $year);
		$sum = 0;
		foreach ($points->data as $val) $sum += $val->today_point;
		$attendance = $oAttendanceModel->getYearlyData($year, $obj->member_srl);
		$oAttendanceController->insertYearly($obj->member_srl, $attendance, $sum, $obj->selected_date . '000000');

		$monthly = substr($obj->selected_date, 0, 6);
		$this->deleteAttendanceMonthlyData($obj->member_srl, $monthly);
		$points = $this->getMonthlyPoint($obj->member_srl, $monthly);
		$sum = 0;
		foreach ($points->data as $val) $sum += $val->today_point;
		$attendance = $oAttendanceModel->getMonthlyData($monthly, $obj->member_srl);
		$oAttendanceController->insertMonthly($obj->member_srl, $attendance, $sum, $obj->selected_date . '000000');

		$week = $oAttendanceModel->getWeek($obj->selected_date);
		$this->deleteAttendanceWeeklyData($obj->member_srl, $week);
		$points = $this->getWeeklyPoint($obj->member_srl, $week);
		$sum = 0;
		foreach ($points->data as $val) $sum += $val->today_point;
		$attendance = $oAttendanceModel->getWeeklyAttendance($obj->member_srl, $week);
		$oAttendanceController->insertWeekly($obj->member_srl, $attendance, $sum, $obj->selected_date . '000000');

		$oAttendanceModel->clearCacheByMemberSrl($obj->member_srl);
	}
}
