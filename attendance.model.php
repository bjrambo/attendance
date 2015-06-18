<?PHP
/**
* @class 출석부 모듈의 attendanceModel 클래스
* @author BJRambo (sosifam@070805.co.kr)
* @출석부 프로그램이 참조하는 일종의 라이브러리
*
* 각종 쿼리 처리 및 주요 계산을 담당하는 함수들이 모여있는 파일입니다.
**/

class attendanceModel extends attendance
{
	var $config;

	/**
	 * @brief 초기화
	 */
	function init()
	{
	}

	function getConfig()
	{
		if(!$this->config)
		{
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('attendance');

			if(!$config->add_point) $config->add_point == '5';
			if(!$config->first_point) $config->first_point == '30';
			if(!$config->second_point) $config->second_point == '15';
			if(!$config->third_point) $config->third_point == '5';
			if(!$config->yearly_point) $config->yearly_point == '500';
			if(!$config->monthly_point) $config->monthly_point == '50';
			if(!$config->weekly_point) $config->weekly_point == '5';
			if(!$config->about_target) $config->about_target == 'no';
			if(!$config->about_continuity) $config->about_continuity == 'no';
			if(!$config->about_time_control) $config->about_time_control == 'no';
			if(!$config->about_diligence_yearly) $config->about_diligence_yearly == 'no';
			//if(!$config->) $config-> == '';

			$this->config = $config;
		}

		return $this->config;
	}

	/**
	 * @brief Attendance 모듈의 존재를 나타내도록
	 */
	function getAttendanceInfo()
	{
		$output = executeQuery('attendance.getAttendance');
		if(!$output->data->module_srl) return;
		$oModuleModel = &getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($output->data->module_srl);

		return $module_info;
	}

	/**
	 * @brief 오늘 같은 ip에서 몇번 출석했는지 출력
	 */
	function getDuplicateIpCount($today, $ipaddress)
	{
		$obj = new stdClass;
		$obj->today = $today;
		$obj->ipaddress = $ipaddress;
		$output = executeQuery('attendance.getDuplicateIpCount', $obj);
		return (int)$output->data->count;
	}

	/**
	 * @brief 오늘 출석한 사람중에 선물당첨된 사람 수.
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
	 * @brief 회원의 출석데이터 출력
	 */
	function getUserAttendanceData($member_srl, $date)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->date = $date;
		$output = executeQuery('attendance.getUserAttendanceData',$arg);
		return $output->data;
	}

	/**
	 * @brief document_srl로 출석자료 찾기
	 */
	function getGreetingsData($document_srl)
	{
		$arg = new stdClass;
		$arg->greetings = $document_srl;
		$output = executeQuery('attendance.getGreetingsData',$arg);
		return $output->data;
	}

	/**
	 * @brief 출석자료 업데이트
	 */
	function updateAttendance($attendance_srl, $regdate, $today_point=null, $member_srl=null, $greetings=null)
	{
		$arg = new stdClass;
		$arg->attendance_srl = $attendance_srl;
		$arg->regdate = $regdate;
		$arg->today_point = $today_point;
		$arg->member_srl = $member_srl;
		$arg->greetings = $greetings;
		executeQuery('attendance.updateAttendance',$arg);
	}

	/**
	 * @brief attendance_srl로 출석자료 찾기
	 */
	function getAttendanceDataSrl($attendance_srl)
	{
		$arg = new stdClass;
		$arg->attendance_srl = $attendance_srl;
		$output = executeQuery('attendance.getAttendanceDataSrl',$arg);
		return $output->data;
	}

	/**
	 * @brief 출석 여부 출력
	 */
	function getAttendanceData($member_srl, $selected_date)
	{
		$flag=false;
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->selected_date = $selected_date;
		$output = executeQuery('attendance.getAttendanceData',$arg);
		$count = (int)$output->data->count;
		if($count==0)
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
	 * @brief 오늘자 출석부 출력(선착순)
	 */
	function getAttendanceList($list_count, $today)
	{
		$args = new stdClass;
		$args->page = Context::get('page');
		$args->now = $today;
		$args->list_count = $list_count;
		$output = executeQueryArray('attendance.getAdminAttendanceList', $args);
		if(!$output->data) $output->data = array();
		return $output;
	}

	/**
	 * @brief 오늘자 출석부 출력(역순)
	 */
	function getInverseList($list_count, $today)
	{
		$args = new stdClass;
		$args->page = Context::get('page');
		$args->now = $today;
		$args->list_count = $list_count;
		$output = executeQueryArray('attendance.getAdminInverseList', $args);
		if(!$output->data) $output->data = array();
		return $output;
	}

	/**
	 * @brief 월별 출석통계 출력
	 */
	function getMonthlyData($monthly, $member_srl)
	{
		$args = new stdClass;
		$args->monthly = $monthly;
		$args->member_srl = $member_srl;
		$output = executeQuery('attendance.getMonthlyData', $args);
		return (int)$output->data->monthly_count;
	}

	/**
	 * @brief 연도별 출석 통계 출력
	 */
	function getYearlyData($yearly, $member_srl)
	{
		$args = new stdClass;
		$args->yearly = $yearly;
		$args->member_srl=$member_srl;
		$output = executeQuery('attendance.getYearlyData', $args);
		return (int)$output->data->yearly_count;
	}

	/**
	 * @brief 오늘 출석 했는지 확인
	 */
	function getIsChecked($member_srl)
	{
		$arg = new stdClass;
		$arg->day = zDate(date('YmdHis'),"Ymd");
		$arg->member_srl = $member_srl;
		$output = executeQuery('attendance.getIsChecked',$arg);
		return (int)$output->data->count;
	}

	/**
	 * @brief 선택한 날짜에 출석 했는지 확인
	 */
	function getIsCheckedA($member_srl, $today)
	{
		$arg = new stdClass;
		$arg->day = $today;
		$arg->member_srl = $member_srl;
		$output = executeQuery('attendance.getIsChecked',$arg);
		return (int)$output->data->count;
	}

    /**
     * @brief 오늘 내 등수 체크
     */
	function getPositionData($today, $greetings=null)
	{
		$args = new stdClass;
		$args->today = $today;
		if($greetings)
		{
			$args->greetings = $greetings;
		}
		else
		{
			$args->greetings = '^admin_checked^';
		}
		$output = executeQuery('attendance.getPositionData', $args);
		return (int)$output->data->count;
	}
	/**
	 * @brief 개근 여부 파악
	 */
	function isPerfect($member_srl, $today, $real=true)
	{
		$oModuleModel = getModel('module');
		$config = $this->getConfig();

		$current_month = substr($today,4,2);
		$current_year = substr($today,0,4);
		$current_day = substr($today,6,2);
		$end_of_month = date('t', mktime(0,0,0,$current_month,1,$current_year));
		$end_of_sosi = date('z', mktime(0,0,0,$current_month,$current_day,$current_year))+1;
		if(date('t', mktime(0,0,0,02,1,$current_year))==29)
		{
			$end_of_year = 366;
		}
		else
		{
			$end_of_year = 365;
		}

		$ym = sprintf("%s%s",$current_year,$current_month);
		$is_perfect_m = $this->getMonthlyData($ym,$member_srl);
		$is_perfect_y = $this->getYearlyData($current_year,$member_srl);

		$arg = new stdClass;

		if($real == true)
		{
			if($is_perfect_m >= $end_of_month && $current_day==$end_of_month)
			{
				$arg->monthly_perfect = 1;
			}
			else
			{
				$arg->monthly_perfect = 0;
			}
			if($is_perfect_y >= $end_of_year && $end_of_sosi==$end_of_year)
			{
				$arg->yearly_perfect = 1;
			}
			else
			{
				$arg->yearly_perfect = 0;
			}
		}
		else
		{
			if($is_perfect_m >= $end_of_month-1 && $current_day==$end_of_month)
			{
				$arg->monthly_perfect = 1;
			}
			else
			{
				$arg->monthly_perfect = 0;
			}
			if($is_perfect_y >= $end_of_year-1 && $end_of_sosi==$end_of_year)
			{
				$arg->yearly_perfect = 1;
			}
			else
			{
				$arg->yearly_perfect = 0;
			}
		}

		return $arg;
	}


	/*******************************************************
	*   attendance_total 테이블 관련 함수                  *
	********************************************************/
	/**
	 * @brief 총 출석 내용이 존재하는지 검사
	 */
	function isExistTotal($member_srl)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$output = executeQuery('attendance.isExistTotal',$arg);
		return (int)$output->data->count;
	}

	/**
	 * @brief  연속출석중인지 검사
	 */
	function isExistContinuity($member_srl, $yesterday)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->yesterday = $yesterday;
		$output = executeQuery('attendance.isExistContinuity',$arg);
		return (int)$output->data->count;
	}

	/**
	 * @brief 어제의 연속출석 데이터 받기
	 */
	function getContinuityData($member_srl, $yesterday)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->yesterday = $yesterday;
		$output = executeQuery('attendance.getContinuityData',$arg);
		$continuity = new stdClass;
		$continuity->data = (int)$output->data->continuity;
		$continuity->point = (int)$output->data->continuity_point;
		return $continuity;
	}

	function getContinuityDataByMemberSrl($member_srl)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;

		$output = executeQuery('attendance.getContinuityData', $args);
		if(count($output->data) != '1')
		{
			return new Object(-1, '한명의 회원의 정보만 입력이 가능합니다.');
		}
		return $output->data;
	}

	/**
	 * @brief attendance_total 테이블 기록
	 */
	function insertTotal($member_srl, $continuity, $total_attendance, $total_point, $regdate)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->continuity = $continuity->data;
		$arg->continuity_point = $continuity->point;
		$arg->total = $total_attendance;
		$arg->total_point = $total_point;
		if($regdate){$arg->regdate = $regdate;}
		executeQuery("attendance.insertTotal", $arg);
	}

	/**
	 * @brief attendance_total 테이블 Update
	 */
	function updateTotal($member_srl, $continuity, $total_attendance, $total_point, $regdate)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->continuity = $continuity->data;
		$arg->continuity_point = $continuity->point;
		$arg->total = $total_attendance;
		$arg->total_point = $total_point;
		if($regdate){$arg->regdate = $regdate;}
		executeQuery("attendance.updateTotal", $arg);
	}

	/**
	 * @brief 총 출석횟수 계산
	 */
	function getTotalAttendance($member_srl)
	{
		$args = new stdClass;
		$args->member_srl=$member_srl;
		$output = executeQuery('attendance.getTotalAttendance', $args);
		return (int)$output->data->total_count;
	}

	/**
	 * @brief 총 출석 포인트 추출
	 */
	function getTotalPoint($member_srl)
	{
		$args = new stdClass;
		$args->member_srl = $member_srl;
		$output = executeQuery('attendance.getTotalPoint', $args);
		return (int)$output->data->total_point;
	}


	/**
	 * @brief 총 출석 테이블 데이터 전부 꺼내기
	 */
	function getTotalData($member_srl)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$output = executeQuery('attendance.getTotalData',$arg);
		$total_info = new stdClass;
		$total_info->total = (int)$output->data->total;
		$total_info->total_point = (int)$output->data->total_point;
		$total_info->continuity_point = (int)$output->data->continuity_point;
		$total_info->continuity = (int)$output->data->continuity;
		$total_info->regdate = $output->data->regdate;
		return $total_info;
	}

	/*******************************************************
	*    attendance_yearly 테이블 관련 함수                *
	********************************************************/
	/**
	 * @brief 연간 출석 데이터가 있는지 확인
	 */
	function isExistYearly($member_srl, $year)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->year = $year;
		$output = executeQuery('attendance.isExistYearly',$arg);
		return (int)$output->data->count;
	}

    /**
     * @brief 회원별 연간 출석통계 자료 생성
     */
	function insertYearly($member_srl, $yearly, $yearly_point, $regdate)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->yearly = $yearly;
		$arg->yearly_point = $yearly_point;
		if($regdate){$arg->regdate = $regdate;}
		executeQuery("attendance.insertYearly", $arg);
	}

	/**
	 * @brief 회원별 연간 출석통계 자료 Update
	 */
	function updateYearly($member_srl, $year, $yearly, $yearly_point, $regdate)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->yearly = $yearly;
		$arg->yearly_point = $yearly_point;
		$arg->year = $year;
		if($regdate){$arg->regdate = $regdate;}
		executeQuery("attendance.updateYearly", $arg);
	}

	/**
	* @brief 연간자료 꺼내기
	*/
	function getYearlyAttendance($member_srl, $year)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->year = $year;
		$output = executeQuery('attendance.getYearlyAttendance',$arg);
		$year_data = new stdClass;
		$year_data->yearly = (int)$output->data->yearly;
		$year_data->yearly_point = (int)$output->data->yearly_point;
		return $year_data;
	}


	/*******************************************************
	*    attendance_monthly 테이블 관련 함수               *
	********************************************************/

	/**
	 * @brief 월간 출석 데이터가 있는지 확인
	 */
	function isExistMonthly($member_srl, $year_month)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->month = $year_month;
		$output = executeQuery('attendance.isExistMonthly',$arg);
		return (int)$output->data->count;
	}

	/**
	 * @brief 회원별 월간 출석통계 자료 생성
	 */
	function insertMonthly($member_srl, $monthly, $monthly_point, $regdate)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->monthly = $monthly;
		$arg->monthly_point = $monthly_point;
		if($regdate){$arg->regdate = $regdate;}
		executeQuery("attendance.insertMonthly", $arg);
	}

	/**
	 * @brief 회원별 월간 출석통계 자료 Update
	 */
	function updateMonthly($member_srl, $year_month, $monthly, $monthly_point, $regdate)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->monthly = $monthly;
		$arg->monthly_point = $monthly_point;
		$arg->month = $year_month;
		if($regdate){$arg->regdate = $regdate;}
		executeQuery("attendance.updateMonthly", $arg);
	}

	/**
	 * @brief 월간자료 꺼내기
	 */
	function getMonthlyAttendance($member_srl, $year_month)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->month = $year_month;
		$output = executeQuery('attendance.getMonthlyAttendance',$arg);
		$month_data = new stdClass;
		$month_data->monthly = (int)$output->data->monthly;
		$month_data->monthly_point = (int)$output->data->monthly_point;
		return $month_data;
	}


	/*******************************************************
	*    attendance_weekly 테이블 관련 함수                *
	********************************************************/
	/**
	 * @brief 선택된 날짜의 기간 찾기
	 */
	//today의 값이 xe설정시각으로 변형되어있을것이므로 여기에선 zDate()사용 안함.
	function getWeek($today)
	{
		if(!$today)
		{
			return 0;
		}
		$week = new stdClass;
		$week->sunday = date('Ymd', strtotime('SUNDAY', strtotime($today)))."235959";
		$week->sunday1 = date('Ymd', strtotime('SUNDAY', strtotime($today)));
		$week->monday = date('Ymd', strtotime('last MONDAY', strtotime($week->sunday)))."000000";
		return $week;
	}

	/**
	 * @brief 주간 통계기록이 있는지 확인
	 */
	function isExistWeekly($member_srl, $week)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->monday = $week->monday;
		$arg->sunday = $week->sunday;
		$output = executeQuery('attendance.isExistWeekly',$arg);
		return (int)$output->data->count;
	}

	/**
	 * @brief 일주일 출석 횟수 알아오기
	 */
	function getWeeklyAttendance($member_srl, $week)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->sunday = $week->sunday;
		$arg->monday = $week->monday;
		$output = executeQuery('attendance.getWeeklyAttendance',$arg);
		return (int)$output->data->weekly_count;
	}

	/**
	 * @brief 주간 출석정보 입력
	 */
	function insertWeekly($member_srl, $weekly, $weekly_point, $regdate)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->weekly = $weekly;
		$arg->weekly_point = $weekly_point;
		if($regdate){$arg->regdate = $regdate;}
		executeQuery("attendance.insertWeekly", $arg);
	}

	/**
	 * @brief 주간 출석정보 Update
	 */
	function updateWeekly($member_srl, $week, $weekly, $weekly_point, $regdate)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->weekly = $weekly;
		$arg->sunday = $week->sunday;
		$arg->monday = $week->monday;
		$arg->weekly_point = $weekly_point;
		if($regdate){$arg->regdate = $regdate;}
		executeQuery("attendance.updateWeekly", $arg);
	}

	/**
	 * @brief 주간 출석정보 빼오기
	 */
	function getWeeklyData($member_srl, $week)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$arg->sunday = $week->sunday;
		$arg->monday = $week->monday;
		$output = executeQuery('attendance.getWeeklyData',$arg);
		$week_data = new stdClass;
		$week_data->weekly = (int)$output->data->weekly;
		$week_data->weekly_point = (int)$output->data->weekly_point;
		return $week_data;
	}

	/**
	 * @brief 출석 가능시간대 인지 확인
	 */
	function availableCheck($config)
	{
		// 모듈 설정값 가져오기
		$oModuleModel = getModel('module');
		$config = $this->getConfig();

		if($config->about_time_control == 'yes')
		{
			$start = new stdClass;
			$end = new stdClass;
			$now = new stdClass;
			$start->hour = substr($config->start_time,0,2);
			$start->min = substr($config->start_time,2,2);
			$end->hour = substr($config->end_time,0,2);
			$end->min = substr($config->end_time,2,2);
			$now->hour = zDate(date('YmdHis'),"H");
			$now->min = zDate(date('YmdHis'),"i");
			if(mktime($now->hour,$now->min,0,0,0) >= mktime($start->hour,$start->min,0,0,0) && mktime($now->hour,$now->min,0,0,0) < mktime($end->hour,$end->min,0,0,0))
			{
				return 1;   //금지시간대일 경우
			}
			return 0;
		}
		return 0;   //금지시간이 아닐 경우
	}

	/**
	 * @brief 연간 정근 여부 확인
	 */
	function checkYearlyDiligence($member_srl, $diligence_yearly, $year)
	{
		if(!$year)
		{
			$year = zDate(date('YmdHis'),"Y");
		}
		$year_data = $this->getYearlyData($year, $member_srl);
		if($year_data)
		{
			if($year_data == $diligence_yearly)
			{
				return 1;
			}
		}
		return 0;
	}

	/**
	 * @brief 월간 정근 여부 확인
	 */
	function checkMonthlyDiligence($member_srl, $diligence_monthly, $year_month)
	{
		if(!$year_month)
		{
			$year_month = zDate(date('YmdHis'),"Ym");
		}
		$month_data  = $this->getMonthlyData($year_month, $member_srl);
		if($month_data)
		{
			if($month_data == $diligence_monthly)
			{
				return 1;
			}
		}
		return 0;
	}

	/**
	 * @brief 주간 정근 여부 확인
	 */
	function checkWeeklyDiligence($member_srl, $diligence_weekly, $today)
	{
		if(!$today)
		{
			$week = $this->getWeek(zDate(date('YmdHis'),"Ymd")); 
		}
		else
		{
			$week = $this->getWeek($today);
		}
		$week_data = $this->getWeeklyData($member_srl, $week);
		if($week_data->weekly)
		{
			if($week_data->weekly == $diligence_weekly)
			{
				return 1;
			} 
		}
		else if($diligence_weekly == 0)
		{
			return 1;
		}
		return 0;
	}

	/**
	 * @brief member_srl로 인사말만 모두 뽑기
	 */
	function getGreetingsList($member_srl)
	{
		$arg = new stdClass;
		$arg->member_srl = $member_srl;
		$output = executeQueryArray('attendance.getGreetingsList',$arg);
		if(!$output->data) $output->data = array();
		return $output;
	}
}
