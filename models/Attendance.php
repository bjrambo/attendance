<?php
namespace Rhymix\Modules\Attendance\Models;

/**
 * Attendance module model.
 */
class Attendance extends Base
{
	private static $config = null;
	private $oCacheHandler = null;

	public function getConfig(): object
	{
		if (self::$config === null)
		{
			$config = \ModuleModel::getInstance()->getModuleConfig('attendance');
			if (!$config) $config = new \stdClass();
			if (!$config->add_point) $config->add_point = '0';
			if (!$config->first_point) $config->first_point = '0';
			if (!$config->second_point) $config->second_point = '0';
			if (!$config->third_point) $config->third_point = '0';
			if (!$config->yearly_point) $config->yearly_point = '0';
			if (!$config->monthly_point) $config->monthly_point = '0';
			if (!$config->weekly_point) $config->weekly_point = '0';
			if (!$config->about_target) $config->about_target = 'no';
			if (!$config->about_continuity) $config->about_continuity = 'no';
			if (!$config->about_time_control) $config->about_time_control = 'no';
			if (!$config->about_diligence_yearly) $config->about_diligence_yearly = 'no';
			if (!$config->allow_duplicaton_ip_count) $config->allow_duplicaton_ip_count = '3';
			if (!$config->about_admin_check) $config->about_admin_check = 'yes';
			if (!$config->use_cache) $config->use_cache = 'yes';
			self::$config = $config;
		}
		return self::$config;
	}

	public function getAttendanceInfo(): object
	{
		$output = executeQuery('attendance.getAttendance');
		$module_srl = $output->data->module_srl;
		if (!$module_srl) return new \stdClass();
		return \ModuleModel::getInstance()->getModuleInfoByModuleSrl($module_srl);
	}

	public function getDuplicateIpCount(string $today, string $ipaddress): int
	{
		if (\Context::get('logged_info')->is_admin === 'Y') return 0;
		$obj = new \stdClass();
		$obj->today = $today;
		$obj->ipaddress = $ipaddress;
		$output = executeQuery('attendance.getDuplicateIpCount', $obj);
		return (int)$output->data->count;
	}

	public function getTodayGiftCount(string $today): int
	{
		$args = new \stdClass();
		$args->today = $today;
		$args->present_y = 'Y';
		$output = executeQuery('attendance.isExistTodayGift', $args);
		return (int)$output->data->count;
	}

	public function getUserAttendanceData(int $member_srl, string $date): object
	{
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$arg->date = $date;
		$output = executeQuery('attendance.getUserAttendanceData', $arg);
		return $output->data;
	}

	public function getGreetingsData(string $document_srl): object
	{
		$arg = new \stdClass();
		$arg->greetings = $document_srl;
		$output = executeQuery('attendance.getGreetingsData', $arg);
		return $output->data;
	}

	public function updateAttendance(int $attendance_srl, string $regdate, $today_point = null, $member_srl = null, $greetings = null): object
	{
		$arg = new \stdClass();
		$arg->attendance_srl = $attendance_srl;
		$arg->regdate = $regdate;
		$arg->today_point = $today_point;
		$arg->member_srl = $member_srl;
		$arg->greetings = $greetings;
		return executeQuery('attendance.updateAttendance', $arg);
	}

	public function getAttendanceDataSrl(int $attendance_srl): object
	{
		$arg = new \stdClass();
		$arg->attendance_srl = $attendance_srl;
		$output = executeQuery('attendance.getAttendanceDataSrl', $arg);
		return $output->data;
	}

	public function getAttendanceData(int $member_srl, string $selected_date): bool
	{
		$args = new \stdClass();
		$args->member_srl = $member_srl;
		$args->selected_date = $selected_date;
		$output = executeQuery('attendance.getAttendanceData', $args);
		return (int)$output->data->count > 0;
	}

	public function getAttendanceList(int $list_count, string $today): object
	{
		$args = new \stdClass();
		$args->page = \Context::get('page');
		$args->now = $today;
		$args->list_count = $list_count;
		$output = executeQueryArray('attendance.getAdminAttendanceList', $args);
		if (!$output->data) $output->data = [];
		return $output;
	}

	public function getInverseList(int $list_count, string $today): object
	{
		$args = new \stdClass();
		$args->page = \Context::get('page');
		$args->now = $today;
		$args->list_count = $list_count;
		$output = executeQueryArray('attendance.getAdminInverseList', $args);
		if (!$output->data) $output->data = [];
		return $output;
	}

	public function getMonthlyData(string $monthly, int $member_srl)
	{
		if ($oCacheHandler = $this->getCacheHandler())
		{
			$cached = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "member:$member_srl:monthly:$monthly"), time() - 86400);
			if ($cached !== false) return $cached;
		}

		$args = new \stdClass();
		$args->monthly = $monthly;
		$args->member_srl = $member_srl;
		$output = executeQuery('attendance.getMonthlyData', $args);
		$result = (int)$output->data->monthly_count;

		if (isset($oCacheHandler) && $oCacheHandler)
		{
			$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "member:$member_srl:monthly:$monthly"), $result, 86400);
		}
		return $result;
	}

	public function getYearlyData(string $yearly, int $member_srl)
	{
		if ($oCacheHandler = $this->getCacheHandler())
		{
			$cached = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "member:$member_srl:yearly:$yearly"), time() - 86400);
			if ($cached !== false) return $cached;
		}

		$args = new \stdClass();
		$args->yearly = $yearly;
		$args->member_srl = $member_srl;
		$output = executeQuery('attendance.getYearlyData', $args);
		$result = (int)$output->data->yearly_count;

		if (isset($oCacheHandler) && $oCacheHandler)
		{
			$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "member:$member_srl:yearly:$yearly"), $result, 86400);
		}
		return $result;
	}

	public function getIsChecked(int $member_srl): int
	{
		return $this->getIsCheckedA($member_srl, zDate(date('YmdHis'), 'Ymd'));
	}

	public function getIsCheckedA(int $member_srl, string $today): int
	{
		if ($oCacheHandler = $this->getCacheHandler())
		{
			$cached = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "member:$member_srl:daily:$today"), time() - 86400);
			if ($cached !== false) return $cached;
		}

		$arg = new \stdClass();
		$arg->day = $today;
		$arg->member_srl = $member_srl;
		$output = executeQuery('attendance.getIsChecked', $arg);
		$result = (int)$output->data->count;

		if (isset($oCacheHandler) && $oCacheHandler)
		{
			$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "member:$member_srl:daily:$today"), $result, 86400);
		}
		return $result;
	}

	public function getIsCheckedMonth(int $member_srl, string $today)
	{
		if (!$member_srl) return false;

		if ($oCacheHandler = $this->getCacheHandler())
		{
			$cached = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "member:$member_srl:isCheckMonth:$today"), time() - 86400);
			if ($cached !== false) return $cached;
		}

		$args = new \stdClass();
		$args->regdate = $today;
		$args->member_srl = $member_srl;
		$output = executeQueryArray('attendance.getIsCheckedMonth', $args);

		if (!$output->data) return false;

		$regdate_array = [];
		foreach ($output->data as $val)
		{
			$regdate_array[] = substr($val->regdate, 0, 8);
		}
		$result = array_count_values($regdate_array);

		if (isset($oCacheHandler) && $oCacheHandler)
		{
			$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "member:$member_srl:isCheckMonth:$today"), $result, 86400);
		}
		return $result;
	}

	public function getPositionData(string $today, ?string $greetings = null): int
	{
		$args = new \stdClass();
		$args->today = $today;
		$args->greetings = $greetings ?: '^admin_checked^';
		$output = executeQuery('attendance.getPositionData', $args);
		return (int)$output->data->count;
	}

	public function isPerfect(int $member_srl, string $today, bool $real = true): object
	{
		$current_month = substr($today, 4, 2);
		$current_year = substr($today, 0, 4);
		$current_day = substr($today, 6, 2);
		$end_of_month = date('t', mktime(0, 0, 0, $current_month, 1, $current_year));
		$end_of_sosi = date('z', mktime(0, 0, 0, $current_month, $current_day, $current_year)) + 1;
		$end_of_year = date('t', mktime(0, 0, 0, 2, 1, $current_year)) == 29 ? 366 : 365;

		$ym = $current_year . $current_month;
		$is_perfect_m = $this->getMonthlyData($ym, $member_srl);
		$is_perfect_y = $this->getYearlyData($current_year, $member_srl);

		$obj = new \stdClass();
		if ($real)
		{
			$obj->monthly_perfect = ($is_perfect_m >= $end_of_month && $current_day == $end_of_month);
			$obj->yearly_perfect = ($is_perfect_y >= $end_of_year && $current_day == $end_of_month);
		}
		else
		{
			$obj->monthly_perfect = ($is_perfect_m >= $end_of_month - 1 && $current_day == $end_of_month);
			$obj->yearly_perfect = ($is_perfect_y >= $end_of_year - 1 && $end_of_sosi == $end_of_year);
		}
		return $obj;
	}

	public function isExistTotal(int $member_srl): int
	{
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$output = executeQuery('attendance.isExistTotal', $arg);
		return (int)$output->data->count;
	}

	public function isExistContinuity(int $member_srl, ?string $yesterday): int
	{
		if ($yesterday === null) return 0;
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$arg->yesterday = $yesterday;
		$output = executeQuery('attendance.isExistContinuity', $arg);
		return (int)$output->data->count;
	}

	public function getContinuityData(int $member_srl, string $yesterday): object
	{
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$arg->yesterday = $yesterday;
		$output = executeQuery('attendance.getContinuityData', $arg);
		$continuity = new \stdClass();
		$continuity->data = (int)$output->data->continuity;
		$continuity->point = (int)$output->data->continuity_point;
		return $continuity;
	}

	public function getContinuityDataByMemberSrl(int $member_srl, ?string $regdate = null)
	{
		$args = new \stdClass();
		$args->member_srl = $member_srl;
		if ($regdate) $args->yesterday = $regdate;
		$output = executeQuery('attendance.getContinuityData', $args);
		if (is_array($output->data) && count($output->data) !== 1)
		{
			return $this->makeObject(-1, '한명의 회원의 정보만 입력이 가능합니다.');
		}
		return $output->data;
	}

	public function getTotalAttendance(int $member_srl): int
	{
		if ($oCacheHandler = $this->getCacheHandler())
		{
			$cached = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "member:$member_srl:totalcount"), time() - 86400);
			if ($cached !== false) return $cached;
		}

		$args = new \stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery('attendance.getTotalAttendance', $args);
		$result = (int)$output->data->total_count;

		if (isset($oCacheHandler) && $oCacheHandler)
		{
			$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "member:$member_srl:totalcount"), $result, 86400);
		}
		return $result;
	}

	public function getTotalPoint(int $member_srl): int
	{
		if ($oCacheHandler = $this->getCacheHandler())
		{
			$cached = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "member:$member_srl:totalpoint"), time() - 86400);
			if ($cached !== false) return $cached;
		}

		$args = new \stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery('attendance.getTotalPoint', $args);
		$result = (int)$output->data->total_point;

		if (isset($oCacheHandler) && $oCacheHandler)
		{
			$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "member:$member_srl:totalpoint"), $result, 86400);
		}
		return $result;
	}

	public function getTotalData(int $member_srl): object
	{
		if ($oCacheHandler = $this->getCacheHandler())
		{
			$cached = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "member:$member_srl:totaldata"), time() - 86400);
			if ($cached !== false) return $cached;
		}

		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$output = executeQuery('attendance.getTotalData', $arg);
		$total_info = new \stdClass();
		$total_info->total = (int)$output->data->total;
		$total_info->total_point = (int)$output->data->total_point;
		$total_info->continuity_point = (int)$output->data->continuity_point;
		$total_info->continuity = (int)$output->data->continuity;
		$total_info->regdate = $output->data->regdate;

		if (isset($oCacheHandler) && $oCacheHandler)
		{
			$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "member:$member_srl:totaldata"), $total_info, 86400);
		}
		return $total_info;
	}

	public function isExistYearly(int $member_srl, string $year): int
	{
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$arg->year = $year;
		$output = executeQuery('attendance.isExistYearly', $arg);
		return (int)$output->data->count;
	}

	public function getYearlyAttendance(int $member_srl, string $year): object
	{
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$arg->year = $year;
		$output = executeQuery('attendance.getYearlyAttendance', $arg);
		$data = new \stdClass();
		$data->yearly = (int)$output->data->yearly;
		$data->yearly_point = (int)$output->data->yearly_point;
		return $data;
	}

	public function isExistMonthly(int $member_srl, string $year_month): int
	{
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$arg->month = $year_month;
		$output = executeQuery('attendance.isExistMonthly', $arg);
		return (int)$output->data->count;
	}

	public function getMonthlyAttendance(int $member_srl, string $year_month): object
	{
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$arg->month = $year_month;
		$output = executeQuery('attendance.getMonthlyAttendance', $arg);
		$data = new \stdClass();
		$data->monthly = (int)$output->data->monthly;
		$data->monthly_point = (int)$output->data->monthly_point;
		return $data;
	}

	public function getWeek(string $today)
	{
		if (!$today) return false;
		$week = new \stdClass();
		$week->sunday = date('Ymd', strtotime('SUNDAY', strtotime($today))) . '235959';
		$week->sunday1 = date('Ymd', strtotime('SUNDAY', strtotime($today)));
		$week->monday = date('Ymd', strtotime('last MONDAY', strtotime($week->sunday))) . '000000';
		return $week;
	}

	public function isExistWeekly(int $member_srl, object $week): int
	{
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$arg->monday = $week->monday;
		$arg->sunday = $week->sunday;
		$output = executeQuery('attendance.isExistWeekly', $arg);
		return (int)$output->data->count;
	}

	public function getWeeklyAttendance(int $member_srl, object $week): int
	{
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$arg->sunday = $week->sunday;
		$arg->monday = $week->monday;
		$output = executeQuery('attendance.getWeeklyAttendance', $arg);
		return (int)$output->data->weekly_count;
	}

	public function getWeeklyData(int $member_srl, object $week): object
	{
		$week_cache_key = $week->sunday;
		if ($oCacheHandler = $this->getCacheHandler())
		{
			$cached = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "member:$member_srl:weekly:$week_cache_key"), time() - 86400);
			if ($cached !== false) return $cached;
		}

		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$arg->sunday = $week->sunday;
		$arg->monday = $week->monday;
		$output = executeQuery('attendance.getWeeklyData', $arg);
		$week_data = new \stdClass();
		$week_data->weekly = (int)$output->data->weekly;
		$week_data->weekly_point = (int)$output->data->weekly_point;

		if (isset($oCacheHandler) && $oCacheHandler)
		{
			$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "member:$member_srl:weekly:$week_cache_key"), $week_data, 86400);
		}
		return $week_data;
	}

	public function availableCheck(): bool
	{
		$config = $this->getConfig();

		if ($config->about_time_control === 'yes')
		{
			$start = new \stdClass();
			$end = new \stdClass();
			$now = new \stdClass();
			$start->hour = substr($config->start_time, 0, 2);
			$start->min = substr($config->start_time, 2, 2);
			$end->hour = substr($config->end_time, 0, 2);
			$end->min = substr($config->end_time, 2, 2);
			$now->hour = zDate(date('YmdHis'), 'H');
			$now->min = zDate(date('YmdHis'), 'i');
			return mktime($now->hour, $now->min, 0) >= mktime($start->hour, $start->min, 0)
				&& mktime($now->hour, $now->min, 0) < mktime($end->hour, $end->min, 0);
		}

		if ($config->about_time_control === 'rand')
		{
			$today = date('Ymd');
			$isReloadConfig = false;
			if ($today !== $config->rand_open_day)
			{
				if ((new \Rhymix\Modules\Attendance\Controllers\Index())->setOpenAttendanceTime())
				{
					$isReloadConfig = true;
				}
			}
			if ($isReloadConfig) $config = $this->getConfig();
			return time() < strtotime($config->rand_open_time);
		}

		return false;
	}

	public function checkYearlyDiligence(int $member_srl, int $diligence_yearly, ?string $year): bool
	{
		if (!$year) $year = zDate(date('YmdHis'), 'Y');
		$year_data = $this->getYearlyData($year, $member_srl);
		return $year_data && $year_data == $diligence_yearly;
	}

	public function checkMonthlyDiligence(int $member_srl, int $diligence_monthly, ?string $year_month): bool
	{
		if (!$year_month) $year_month = zDate(date('YmdHis'), 'Ym');
		$month_data = $this->getMonthlyData($year_month, $member_srl);
		return $month_data && $month_data == $diligence_monthly;
	}

	public function checkWeeklyDiligence(int $member_srl, int $diligence_weekly, ?string $today): bool
	{
		$week = $today ? $this->getWeek($today) : $this->getWeek(zDate(date('YmdHis'), 'Ymd'));
		$week_data = $this->getWeeklyData($member_srl, $week);
		if ($week_data->weekly)
		{
			return $week_data->weekly == $diligence_weekly;
		}
		return $diligence_weekly == 0;
	}

	public function getGreetingsList(int $member_srl): object
	{
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$output = executeQueryArray('attendance.getGreetingsList', $arg);
		if (!$output->data) $output->data = [];
		return $output;
	}

	public function getTotalAbsent(string $sign_date, int $total_attendance): int
	{
		$today = date('Y-m-d');
		$end_date = date(zdate($sign_date, 'Y-m-d'));
		$d_day = floor((strtotime(substr($end_date, 0, 10)) - strtotime($today)) / 86400);
		$total_absent_number = abs($d_day) - $total_attendance;
		return (int)preg_replace('/.0/', '', $total_absent_number);
	}

	public function getWeeklyAttendanceByMemberSrl(int $member_srl): int
	{
		$args = new \stdClass();
		$args->member_srl = $member_srl;
		$args->weekly = 7;
		$output = executeQuery('attendance.getWeeklyAttendanceByMemberSrl', $args);
		return (int)$output->data->count;
	}

	public function clearCacheByMemberSrl(int $member_srl, string $type = 'all', $condition = null): void
	{
		$member_srl = (int)$member_srl;
		if (!$member_srl) return;

		$oCacheHandler = $this->getCacheHandler();
		if (!$oCacheHandler) return;

		$daily = ($type === 'daily' && $condition) ? $condition : zDate(date('YmdHis'), 'Ymd');
		$weekly = ($type === 'weekly' && $condition) ? $condition->sunday : $this->getWeek(date('YmdHis'))->sunday;
		$monthly = ($type === 'monthly' && $condition) ? $condition : zDate(date('YmdHis'), 'Ym');
		$yearly = ($type === 'yearly' && $condition) ? $condition : zDate(date('YmdHis'), 'Y');

		$oCacheHandler->delete($oCacheHandler->getGroupKey('attendance', "todaytotal:$daily"));
		$oCacheHandler->delete($oCacheHandler->getGroupKey('attendance', "member:$member_srl:daily:$daily"));
		$oCacheHandler->delete($oCacheHandler->getGroupKey('attendance', "member:$member_srl:isCheckMonth:$monthly"));
		$oCacheHandler->delete($oCacheHandler->getGroupKey('attendance', "member:$member_srl:weekly:$weekly"));
		$oCacheHandler->delete($oCacheHandler->getGroupKey('attendance', "member:$member_srl:monthly:$monthly"));
		$oCacheHandler->delete($oCacheHandler->getGroupKey('attendance', "member:$member_srl:yearly:$yearly"));
		$oCacheHandler->delete($oCacheHandler->getGroupKey('attendance', "member:$member_srl:totalcount"));
		$oCacheHandler->delete($oCacheHandler->getGroupKey('attendance', "member:$member_srl:totalpoint"));
		$oCacheHandler->delete($oCacheHandler->getGroupKey('attendance', "member:$member_srl:totaldata"));

		if (\FileHandler::isDir(\RX_BASEDIR . 'widgets/pr_take_roll'))
		{
			$oCacheHandler->invalidateGroupKey('widget_pr_take_roll');
		}
	}

	public function clearCache(): void
	{
		$oCacheHandler = $this->getCacheHandler();
		if (!$oCacheHandler) return;
		$oCacheHandler->invalidateGroupKey('attendance');
		$oCacheHandler->invalidateGroupKey('widget_pr_take_roll');
	}

	public function getCacheHandler()
	{
		if ($this->oCacheHandler === null)
		{
			if ($this->getConfig()->use_cache !== 'yes')
			{
				$this->oCacheHandler = false;
			}
			else
			{
				$this->oCacheHandler = \CacheHandler::getInstance('object');
				if (!$this->oCacheHandler->isSupport())
				{
					$this->oCacheHandler = false;
				}
			}
		}
		return $this->oCacheHandler;
	}
}
