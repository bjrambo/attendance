<?PHP
/**
 * @class attendanceModel
 * @author BJRambo (sosifam@070805.co.kr)
 * @brief attendance module model class
 **/

class attendanceModel extends attendance
{
	private static $config = NULL;
	private $oCacheHandler = NULL;

	/**
	 * @brief 초기화
	 */
	function init()
	{
	}

	/**
	 * @brief Get the attendance module config.
	 * @return Object
	 */
	function getConfig()
	{
		if (self::$config === NULL)
		{
			/** @var $oModuleModel moduleModel */
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('attendance');

			if (!$config->add_point)
			{
				$config->add_point = '5';
			}
			if (!$config->first_point)
			{
				$config->first_point = '30';
			}
			if (!$config->second_point)
			{
				$config->second_point = '15';
			}
			if (!$config->third_point)
			{
				$config->third_point = '5';
			}
			if (!$config->yearly_point)
			{
				$config->yearly_point = '500';
			}
			if (!$config->monthly_point)
			{
				$config->monthly_point = '50';
			}
			if (!$config->weekly_point)
			{
				$config->weekly_point = '5';
			}
			if (!$config->about_target)
			{
				$config->about_target = 'no';
			}
			if (!$config->about_continuity)
			{
				$config->about_continuity = 'no';
			}
			if (!$config->about_time_control)
			{
				$config->about_time_control = 'no';
			}
			if (!$config->about_diligence_yearly)
			{
				$config->about_diligence_yearly = 'no';
			}
			if (!$config->allow_duplicaton_ip_count)
			{
				$config->allow_duplicaton_ip_count = '3';
			}
			if (!$config->about_admin_check)
			{
				$config->about_admin_check = 'yes';
			}
			if (!$config->use_cache)
			{
				$config->use_cache = 'yes';
			}

			self::$config = $config;
		}

		return self::$config;
	}

	/**
	 * @brief Get the attendance module instance info data.
	 * @return mixed
	 */
	function getAttendanceInfo()
	{
		$output = executeQuery('attendance.getAttendance');
		$module_srl = $output->data->module_srl;
		if (!$module_srl)
		{
			return new stdClass();
		}

		$module_info = getModel('module')->getModuleInfoByModuleSrl($module_srl);

		return $module_info;
	}

	/**
	 * @param $today
	 * @param $ipaddress
	 * @return int
	 */
	function getDuplicateIpCount($today, $ipaddress)
	{
		//TODO(BJRambo): Only use to test. will be delete it.
		if (Context::get('logged_info')->is_admin == 'Y')
		{
			return 0;
		}
		$obj = new stdClass();
		$obj->today = $today;
		$obj->ipaddress = $ipaddress;
		$output = executeQuery('attendance.getDuplicateIpCount', $obj);
		return (int)$output->data->count;
	}

	/**
	 * @param $today
	 * @return int
	 */
	function getTodayGiftCount($today)
	{
		$args = new stdClass();
		$args->today = $today;
		$args->present_y = 'Y';
		$output = executeQuery('attendance.isExistTodayGift', $args);
		return (int)$output->data->count;
	}

	/**
	 * @param $member_srl
	 * @param $date
	 * @return mixed
	 */
	function getUserAttendanceData($member_srl, $date)
	{
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$arg->date = $date;
		$output = executeQuery('attendance.getUserAttendanceData', $arg);
		return $output->data;
	}

	/**
	 * @param $document_srl
	 * @return mixed
	 */
	function getGreetingsData($document_srl)
	{
		$arg = new stdClass();
		$arg->greetings = $document_srl;
		$output = executeQuery('attendance.getGreetingsData', $arg);
		return $output->data;
	}

	/**
	 * @param $attendance_srl
	 * @param $regdate
	 * @param null $today_point
	 * @param null $member_srl
	 * @param null $greetings
	 * @return object
	 */
	function updateAttendance($attendance_srl, $regdate, $today_point = null, $member_srl = null, $greetings = null)
	{
		$arg = new stdClass();
		$arg->attendance_srl = $attendance_srl;
		$arg->regdate = $regdate;
		$arg->today_point = $today_point;
		$arg->member_srl = $member_srl;
		$arg->greetings = $greetings;
		$output = executeQuery('attendance.updateAttendance', $arg);

		return $output;
	}

	/**
	 * @param $attendance_srl
	 * @return mixed
	 */
	function getAttendanceDataSrl($attendance_srl)
	{
		$arg = new stdClass();
		$arg->attendance_srl = $attendance_srl;
		$output = executeQuery('attendance.getAttendanceDataSrl', $arg);
		return $output->data;
	}

	/**
	 * @param $member_srl
	 * @param $selected_date
	 * @return bool
	 */
	function getAttendanceData($member_srl, $selected_date)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->selected_date = $selected_date;
		$output = executeQuery('attendance.getAttendanceData', $args);
		$count = (int)$output->data->count;
		if ($count == 0)
		{
			$flag = false;
		}
		else
		{
			$flag = true;
		}
		return $flag;
	}

	/**
	 * @param $list_count
	 * @param $today
	 * @return mixed
	 */
	function getAttendanceList($list_count, $today)
	{
		$args = new stdClass();
		$args->page = Context::get('page');
		$args->now = $today;
		$args->list_count = $list_count;
		$output = executeQueryArray('attendance.getAdminAttendanceList', $args);
		if (!$output->data)
		{
			$output->data = array();
		}
		return $output;
	}

	/**
	 * @param $list_count
	 * @param $today
	 * @return object
	 */
	function getInverseList($list_count, $today)
	{
		$args = new stdClass();
		$args->page = Context::get('page');
		$args->now = $today;
		$args->list_count = $list_count;
		$output = executeQueryArray('attendance.getAdminInverseList', $args);
		if (!$output->data)
		{
			$output->data = array();
		}
		return $output;
	}

	/**
	 * @param $monthly
	 * @param $member_srl
	 * @return false|int|mixed
	 */
	function getMonthlyData($monthly, $member_srl)
	{
		if ($oCacheHandler = $this->getCacheHandler())
		{
			if (($result = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "member:$member_srl:monthly:$monthly"), time() - 86400)) !== false)
			{
				return $result;
			}
		}

		$args = new stdClass();
		$args->monthly = $monthly;
		$args->member_srl = $member_srl;
		$output = executeQuery('attendance.getMonthlyData', $args);
		$result = (int)$output->data->monthly_count;

		if ($oCacheHandler)
		{
			$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "member:$member_srl:monthly:$monthly"), $result, 86400);
		}
		return $result;
	}

	/**
	 * @param $yearly
	 * @param $member_srl
	 * @return false|int|mixed
	 */
	function getYearlyData($yearly, $member_srl)
	{
		if ($oCacheHandler = $this->getCacheHandler())
		{
			if (($result = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "member:$member_srl:yearly:$yearly"), time() - 86400)) !== false)
			{
				return $result;
			}
		}

		$args = new stdClass();
		$args->yearly = $yearly;
		$args->member_srl = $member_srl;
		$output = executeQuery('attendance.getYearlyData', $args);
		$result = (int)$output->data->yearly_count;

		if ($oCacheHandler)
		{
			$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "member:$member_srl:yearly:$yearly"), $result, 86400);
		}
		return $result;
	}

	/**
	 * @param $member_srl
	 * @return false|int|mixed
	 */
	function getIsChecked($member_srl)
	{
		return $this->getIsCheckedA($member_srl, zDate(date('YmdHis'), "Ymd"));
	}

	/**
	 * @param $member_srl
	 * @param $today
	 * @return false|int|mixed
	 */
	function getIsCheckedA($member_srl, $today)
	{
		if ($oCacheHandler = $this->getCacheHandler())
		{
			if (($result = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "member:$member_srl:daily:$today"), time() - 86400)) !== false)
			{
				return $result;
			}
		}

		$arg = new stdClass();
		$arg->day = $today;
		$arg->member_srl = $member_srl;
		$output = executeQuery('attendance.getIsChecked', $arg);
		$result = (int)$output->data->count;

		if ($oCacheHandler)
		{
			$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "member:$member_srl:daily:$today"), $result, 86400);
		}
		return $result;
	}

	/**
	 * 선택한 날자가 포함된 달에 출석한 날자를 모두 가져옴
	 * @param $member_srl
	 * @param $today
	 * @return array|bool
	 */
	function getIsCheckedMonth($member_srl, $today)
	{
		if (!$member_srl)
		{
			return false;
		}

		if ($oCacheHandler = $this->getCacheHandler())
		{
			if (($result = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "member:$member_srl:isCheckMonth:$today"), time() - 86400)) !== false)
			{
				return $result;
			}
		}

		$args = new stdClass();
		$args->regdate = $today;
		$args->member_srl = $member_srl;
		$output = executeQueryArray('attendance.getIsCheckedMonth', $args);

		$regdate_array = array();
		if ($output->data)
		{
			foreach ($output->data as $val)
			{
				$regdate_array[] = substr($val->regdate, 0, 8);
			}
			$result = array_count_values($regdate_array);

			if ($oCacheHandler)
			{
				$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "member:$member_srl:isCheckMonth:$today"), $result, 86400);
			}
		}
		else
		{
			return false;
		}

		return $result;
	}

	/**
	 * @param $today
	 * @param null $greetings
	 * @return mixed
	 */
	function getPositionData($today, $greetings = null)
	{
		$args = new stdClass();
		$args->today = $today;
		if ($greetings)
		{
			$args->greetings = $greetings;
		}
		else
		{
			$args->greetings = '^admin_checked^';
		}
		$output = executeQuery('attendance.getPositionData', $args);

		return $output->data->count;
	}

	/**
	 * @param $member_srl
	 * @param $today
	 * @param bool $real
	 * @return stdClass
	 */
	function isPerfect($member_srl, $today, $real = true)
	{
		$current_month = substr($today, 4, 2);
		$current_year = substr($today, 0, 4);
		$current_day = substr($today, 6, 2);
		$end_of_month = date('t', mktime(0, 0, 0, $current_month, 1, $current_year));
		$end_of_sosi = date('z', mktime(0, 0, 0, $current_month, $current_day, $current_year)) + 1;
		if (date('t', mktime(0, 0, 0, 02, 1, $current_year)) == 29)
		{
			$end_of_year = 366;
		}
		else
		{
			$end_of_year = 365;
		}

		$ym = sprintf("%s%s", $current_year, $current_month);
		$is_perfect_m = $this->getMonthlyData($ym, $member_srl);
		$is_perfect_y = $this->getYearlyData($current_year, $member_srl);

		$regularlyObject = new stdClass();
		if ($real == true)
		{
			if ($is_perfect_m >= $end_of_month && $current_day == $end_of_month)
			{
				$regularlyObject->monthly_perfect = true;
			}
			else
			{
				$regularlyObject->monthly_perfect = false;
			}
			if ($is_perfect_y >= $end_of_year && $current_day == $end_of_month)
			{
				$regularlyObject->yearly_perfect = true;
			}
			else
			{
				$regularlyObject->yearly_perfect = false;
			}
		}
		else
		{
			if ($is_perfect_m >= $end_of_month - 1 && $current_day == $end_of_month)
			{
				$regularlyObject->monthly_perfect = true;
			}
			else
			{
				$regularlyObject->monthly_perfect = false;
			}
			if ($is_perfect_y >= $end_of_year - 1 && $end_of_sosi == $end_of_year)
			{
				$regularlyObject->yearly_perfect = true;
			}
			else
			{
				$regularlyObject->yearly_perfect = false;
			}
		}

		return $regularlyObject;
	}

	/**
	 * @param $member_srl
	 * @return int
	 */
	function isExistTotal($member_srl)
	{
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$output = executeQuery('attendance.isExistTotal', $arg);
		return (int)$output->data->count;
	}

	/**
	 * @param $member_srl
	 * @param $yesterday
	 * @return int
	 */
	function isExistContinuity($member_srl, $yesterday)
	{
		if ($yesterday == null)
		{
			return 0;
		}
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$arg->yesterday = $yesterday;
		$output = executeQuery('attendance.isExistContinuity', $arg);
		return (int)$output->data->count;
	}

	/**
	 * @param $member_srl
	 * @param $yesterday
	 * @return stdClass
	 */
	function getContinuityData($member_srl, $yesterday)
	{
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$arg->yesterday = $yesterday;
		$output = executeQuery('attendance.getContinuityData', $arg);
		$continuity = new stdClass();
		$continuity->data = (int)$output->data->continuity;
		$continuity->point = (int)$output->data->continuity_point;
		return $continuity;
	}

	/**
	 * @param $member_srl
	 * @return Object
	 */
	function getContinuityDataByMemberSrl($member_srl)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;

		$output = executeQuery('attendance.getContinuityData', $args);
		if (count($output->data) != '1')
		{
			return $this->makeObject(-1, '한명의 회원의 정보만 입력이 가능합니다.');
		}
		return $output->data;
	}


	/**
	 * @param $member_srl
	 * @return false|int|mixed
	 */
	function getTotalAttendance($member_srl)
	{
		if ($oCacheHandler = $this->getCacheHandler())
		{
			if (($result = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "member:$member_srl:totalcount"), time() - 86400)) !== false)
			{
				return $result;
			}
		}

		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery('attendance.getTotalAttendance', $args);
		$result = (int)$output->data->total_count;

		if ($oCacheHandler)
		{
			$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "member:$member_srl:totalcount"), $result, 86400);
		}
		return $result;
	}

	/**
	 * @param $member_srl
	 * @return false|int|mixed
	 */
	function getTotalPoint($member_srl)
	{
		if ($oCacheHandler = $this->getCacheHandler())
		{
			if (($result = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "member:$member_srl:totalpoint"), time() - 86400)) !== false)
			{
				return $result;
			}
		}

		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery('attendance.getTotalPoint', $args);
		$result = (int)$output->data->total_point;

		if ($oCacheHandler)
		{
			$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "member:$member_srl:totalpoint"), $result, 86400);
		}
		return $result;
	}


	/**
	 * @param $member_srl
	 * @return stdClass
	 */
	function getTotalData($member_srl)
	{
		if ($oCacheHandler = $this->getCacheHandler())
		{
			if (($total_info = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "member:$member_srl:totaldata"), time() - 86400)) !== false)
			{
				return $total_info;
			}
		}

		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$output = executeQuery('attendance.getTotalData', $arg);
		$total_info = new stdClass();
		$total_info->total = (int)$output->data->total;
		$total_info->total_point = (int)$output->data->total_point;
		$total_info->continuity_point = (int)$output->data->continuity_point;
		$total_info->continuity = (int)$output->data->continuity;
		$total_info->regdate = $output->data->regdate;

		if ($oCacheHandler)
		{
			$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "member:$member_srl:totaldata"), $total_info, 86400);
		}
		return $total_info;
	}

	/**
	 * @param $member_srl
	 * @param $year
	 * @return int
	 */
	function isExistYearly($member_srl, $year)
	{
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$arg->year = $year;
		$output = executeQuery('attendance.isExistYearly', $arg);
		return (int)$output->data->count;
	}

	/**
	 * @param $member_srl
	 * @param $year
	 * @return stdClass
	 */
	function getYearlyAttendance($member_srl, $year)
	{
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$arg->year = $year;
		$output = executeQuery('attendance.getYearlyAttendance', $arg);
		$year_data = new stdClass();
		$year_data->yearly = (int)$output->data->yearly;
		$year_data->yearly_point = (int)$output->data->yearly_point;
		return $year_data;
	}


	/**
	 * @param $member_srl
	 * @param $year_month
	 * @return int
	 */
	function isExistMonthly($member_srl, $year_month)
	{
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$arg->month = $year_month;
		$output = executeQuery('attendance.isExistMonthly', $arg);
		return (int)$output->data->count;
	}

	/**
	 * @param $member_srl
	 * @param $year_month
	 * @return stdClass
	 */
	function getMonthlyAttendance($member_srl, $year_month)
	{
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$arg->month = $year_month;
		$output = executeQuery('attendance.getMonthlyAttendance', $arg);
		$month_data = new stdClass();
		$month_data->monthly = (int)$output->data->monthly;
		$month_data->monthly_point = (int)$output->data->monthly_point;
		return $month_data;
	}

	/**
	 * @param $today
	 * @return bool|object
	 */
	function getWeek($today)
	{
		if (!$today)
		{
			return false;
		}
		$week = new stdClass();
		$week->sunday = date('Ymd', strtotime('SUNDAY', strtotime($today))) . "235959";
		$week->sunday1 = date('Ymd', strtotime('SUNDAY', strtotime($today)));
		$week->monday = date('Ymd', strtotime('last MONDAY', strtotime($week->sunday))) . "000000";
		return $week;
	}

	/**
	 * @param $member_srl
	 * @param $week
	 * @return int
	 */
	function isExistWeekly($member_srl, $week)
	{
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$arg->monday = $week->monday;
		$arg->sunday = $week->sunday;
		$output = executeQuery('attendance.isExistWeekly', $arg);
		return (int)$output->data->count;
	}

	/**
	 * @param $member_srl
	 * @param $week
	 * @return int
	 */
	function getWeeklyAttendance($member_srl, $week)
	{
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$arg->sunday = $week->sunday;
		$arg->monday = $week->monday;
		$output = executeQuery('attendance.getWeeklyAttendance', $arg);
		return (int)$output->data->weekly_count;
	}

	/**
	 * @param $member_srl
	 * @param $week
	 * @return false|mixed|stdClass
	 */
	function getWeeklyData($member_srl, $week)
	{
		$week_cache_key = $week->sunday;
		if ($oCacheHandler = $this->getCacheHandler())
		{
			if (($week_data = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "member:$member_srl:weekly:$week_cache_key"), time() - 86400)) !== false)
			{
				return $week_data;
			}
		}

		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$arg->sunday = $week->sunday;
		$arg->monday = $week->monday;
		$output = executeQuery('attendance.getWeeklyData', $arg);
		$week_data = new stdClass();
		$week_data->weekly = (int)$output->data->weekly;
		$week_data->weekly_point = (int)$output->data->weekly_point;

		if ($oCacheHandler)
		{
			$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "member:$member_srl:weekly:$week_cache_key"), $week_data, 86400);
		}

		return $week_data;
	}

	/**
	 * @return bool
	 */
	function availableCheck()
	{
		// 모듈 설정값 가져오기
		$config = $this->getConfig();

		if ($config->about_time_control == 'yes')
		{
			$start = new stdClass();
			$end = new stdClass();
			$now = new stdClass();
			$start->hour = substr($config->start_time, 0, 2);
			$start->min = substr($config->start_time, 2, 2);
			$end->hour = substr($config->end_time, 0, 2);
			$end->min = substr($config->end_time, 2, 2);
			$now->hour = zDate(date('YmdHis'), "H");
			$now->min = zDate(date('YmdHis'), "i");
			if (mktime($now->hour, $now->min, 0, 0, 0) >= mktime($start->hour, $start->min, 0, 0, 0) && mktime($now->hour, $now->min, 0, 0, 0) < mktime($end->hour, $end->min, 0, 0, 0))
			{
				return true;   //금지시간대일 경우
			}
			return false;
		}
		return false;   //금지시간이 아닐 경우
	}

	/**
	 * @param $member_srl
	 * @param $diligence_yearly
	 * @param $year
	 * @return bool
	 */
	function checkYearlyDiligence($member_srl, $diligence_yearly, $year)
	{
		if (!$year)
		{
			$year = zDate(date('YmdHis'), "Y");
		}
		$year_data = $this->getYearlyData($year, $member_srl);
		if ($year_data)
		{
			if ($year_data == $diligence_yearly)
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @param $member_srl
	 * @param $diligence_monthly
	 * @param $year_month
	 * @return bool
	 */
	function checkMonthlyDiligence($member_srl, $diligence_monthly, $year_month)
	{
		if (!$year_month)
		{
			$year_month = zDate(date('YmdHis'), "Ym");
		}
		$month_data = $this->getMonthlyData($year_month, $member_srl);
		if ($month_data)
		{
			if ($month_data == $diligence_monthly)
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @param $member_srl
	 * @param $diligence_weekly
	 * @param $today
	 * @return bool
	 */
	function checkWeeklyDiligence($member_srl, $diligence_weekly, $today)
	{
		if (!$today)
		{
			$week = $this->getWeek(zDate(date('YmdHis'), "Ymd"));
		}
		else
		{
			$week = $this->getWeek($today);
		}
		$week_data = $this->getWeeklyData($member_srl, $week);
		if ($week_data->weekly)
		{
			if ($week_data->weekly == $diligence_weekly)
			{
				return true;
			}
		}
		else if ($diligence_weekly == 0)
		{
			return true;
		}
		return false;
	}

	/**
	 * @param $member_srl
	 * @return object
	 */
	function getGreetingsList($member_srl)
	{
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$output = executeQueryArray('attendance.getGreetingsList', $arg);
		if (!$output->data)
		{
			$output->data = array();
		}
		return $output;
	}

	/**
	 * @param $sign_date
	 * @param $total_attendance
	 * @return Object
	 */
	function getTotalAbsent($sign_date, $total_attendance)
	{
		$today = date('Y-m-d');
		$end_date = date(zdate($sign_date, 'Y-m-d'));
		$d_day = floor((strtotime(substr($end_date, 0, 10)) - strtotime($today)) / 86400);

		$total_absent_number = abs($d_day) - $total_attendance;

		$total_absent = preg_replace('/.0/', '', $total_absent_number);
		return $total_absent;
	}

	/**
	 * @param $member_srl
	 * @return string
	 */
	function getWeeklyAttendanceByMemberSrl($member_srl)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->weekly = 7;
		$output = executeQuery('attendance.getWeeklyAttendanceByMemberSrl', $args);

		return $output->data->count;
	}

	/**
	 * Delete the member attendace data cache by member_srl.
	 * @param $member_srl
	 * @param string $type
	 * @param object $condition
	 */
	function clearCacheByMemberSrl($member_srl, $type = 'all', $condition = null)
	{
		$member_srl = (int)$member_srl;
		if (!$member_srl)
		{
			return;
		}

		$oCacheHandler = $this->getCacheHandler();
		if (!$oCacheHandler)
		{
			return;
		}

		$daily = ($type === 'daily' && $condition) ? $condition : zDate(date('YmdHis'), "Ymd");
		$weekly = ($type === 'weekly' && $condition) ? $condition->sunday : $this->getWeek(date('YmdHis'))->sunday;
		$monthly = ($type === 'monthly' && $condition) ? $condition : zDate(date('YmdHis'), "Ym");
		$yearly = ($type === 'yearly' && $condition) ? $condition : zDate(date('YmdHis'), "Y");

		$oCacheHandler->delete($oCacheHandler->getGroupKey('attendance', "todaytotal:$daily"));
		$oCacheHandler->delete($oCacheHandler->getGroupKey('attendance', "member:$member_srl:daily:$daily"));
		$oCacheHandler->delete($oCacheHandler->getGroupKey('attendance', "member:$member_srl:isCheckMonth:$monthly"));
		$oCacheHandler->delete($oCacheHandler->getGroupKey('attendance', "member:$member_srl:weekly:$weekly"));
		$oCacheHandler->delete($oCacheHandler->getGroupKey('attendance', "member:$member_srl:monthly:$monthly"));
		$oCacheHandler->delete($oCacheHandler->getGroupKey('attendance', "member:$member_srl:yearly:$yearly"));
		$oCacheHandler->delete($oCacheHandler->getGroupKey('attendance', "member:$member_srl:totalcount"));
		$oCacheHandler->delete($oCacheHandler->getGroupKey('attendance', "member:$member_srl:totalpoint"));
		$oCacheHandler->delete($oCacheHandler->getGroupKey('attendance', "member:$member_srl:totaldata"));

		// For pr_take_roll widgets
		if (FileHandler::isDir(_XE_PATH_ . '/widgets/pr_take_roll'))
		{
			$oCacheHandler->invalidateGroupKey('widget_pr_take_roll');
		}
	}

	/**
	 * @brief Delete the all members attendance data cache.
	 */
	function clearCache()
	{
		$oCacheHandler = $this->getCacheHandler();
		if (!$oCacheHandler)
		{
			return;
		}

		$oCacheHandler->invalidateGroupKey('attendance');
		$oCacheHandler->invalidateGroupKey('widget_pr_take_roll');
	}

	/**
	 * Get the XpressEngine cache handler.
	 * @return bool|CacheHandler|null
	 */
	function getCacheHandler()
	{
		if ($this->oCacheHandler === NULL)
		{
			if ($this->getConfig()->use_cache !== 'yes')
			{
				$this->oCacheHandler = false;
			}
			else
			{
				$this->oCacheHandler = CacheHandler::getInstance('object');
				if (!$this->oCacheHandler->isSupport())
				{
					$this->oCacheHandler = false;
				}
			}
		}
		return $this->oCacheHandler;
	}
}
