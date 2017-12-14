<?php
/**
 * @class attendanceAdminModel
 * @author BJRambo (sosifam@070805.co.kr)
 * @brief attendance module admin model class
 **/

class attendanceAdminModel extends attendance
{

	/**
	 * @brief 초기화
	 **/
	function init()
	{
	}

	/**
	 * @brief 회원 목록을 구함
	 **/
	function getAttendanceMemberList($list, $new_type)
	{
		/*attendance model 객체 생성*/
		$oAttendanceModel = getModel('attendance');
		// 검색 옵션 정리
		$args = new stdClass();
		$args->is_admin = Context::get('is_admin')=='Y'?'Y':'';
		$args->is_denied = Context::get('is_denied')=='Y'?'Y':'';
		$args->selected_group_srl = Context::get('selected_group_srl');
		$args->unjoined_members = Context::get('unjoined_members')=='Y'?'Y':'';
		$type = $new_type;
		if(!$type) $type=Context::get('type');
		$args->year = substr(Context::get('selected_date'),0,4);
		$args->year_month = substr(Context::get('selected_date'),0,6);
		$week = $oAttendanceModel->getWeek(Context::get('selected_date'));
		$args->monday = $week->monday;
		$args->sunday = $week->sunday;

        //날짜정보가 없으면 오늘로 초기화
		if(!$args->year)
		{
			$args->year=zDate(date('YmdHis'),"Y");
		}
		if(!$args->year_month)
		{
			$args->year_month=zDate(date('YmdHis'),"Ym");
		}
		if(!$week->monday || !$week->sunday)
		{
			$week=$oAttendanceModel->getWeek(zDate(date('YmdHis'),"Ymd"));
			$args->monday = $week->monday;
			$args->sunday = $week->sunday;
		}

		$search_target = trim(Context::get('search_target'));
		$search_keyword = trim(Context::get('search_keyword'));

		if($search_target && $search_keyword)
		{
			switch($search_target)
			{
				case 'user_id' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_user_id = $search_keyword;
					break;
				case 'user_name' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_user_name = $search_keyword;
					break;
				case 'nick_name' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_nick_name = $search_keyword;
					break;
				case 'email_address' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_email_address = $search_keyword;
					break;
				case 'regdate' :
					$args->s_regdate = preg_replace("/[^0-9]/","",$search_keyword);
					break;
				case 'regdate_more' :
					$args->s_regdate_more = substr(preg_replace("/[^0-9]/","",$search_keyword) . '00000000000000',0,14);
					break;
				case 'regdate_less' :
					$args->s_regdate_less = substr(preg_replace("/[^0-9]/","",$search_keyword) . '00000000000000',0,14);
					break;
				case 'last_login' :
					$args->s_last_login = $search_keyword;
					break;
				case 'last_login_more' :
					$args->s_last_login_more = substr(preg_replace("/[^0-9]/","",$search_keyword) . '00000000000000',0,14);
					break;
				case 'last_login_less' :
					$args->s_last_login_less = substr(preg_replace("/[^0-9]/","",$search_keyword) . '00000000000000',0,14);
					break;
				case 'extra_vars' :
					$args->s_extra_vars = preg_replace("/[^0-9]/","",$search_keyword);
					break;
			}
		}

		if($type=='day' || $type=='speedsearch')
		{
			// selected_group_srl이 있으면 query id를 변경 (table join때문에)
			if($args->selected_group_srl)
			{
				$query_id = 'member.getMemberListWithinGroup';
				$args->sort_index = "member.member_srl";
				$args->sort_order = "desc";
			}
			else
			{
				$query_id = 'member.getMemberList';
				$args->sort_index = "member_srl";
				$args->sort_order = "desc";
			}
		}
		else if($type=='ranktotal')
		{
			// selected_group_srl이 있으면 query id를 변경 (table join때문에)
			if($args->selected_group_srl)
			{
				$query_id = 'attendance.getMemberListWithinGroup';
				$args->sort_index = "attendance_total.total";
			}
			else
			{
				$query_id = 'attendance.getMemberList';
				$args->sort_index = "attendance_total.total";
			}
		}
		else if($type=='rankyearly')
		{
			// selected_group_srl이 있으면 query id를 변경 (table join때문에)
			if($args->selected_group_srl)
			{
				$query_id = 'attendance.getMemberListWithinGroupYearly';
				$args->sort_index = "attendance_yearly.yearly";
			}
			else
			{
				$query_id = 'attendance.getMemberListYearly';
				$args->sort_index = "attendance_yearly.yearly";
			}
		}
		else if($type=='rankmonthly')
		{
			// selected_group_srl이 있으면 query id를 변경 (table join때문에)
			if($args->selected_group_srl)
			{
				$query_id = 'attendance.getMemberListWithinGroupMonthly';
				$args->sort_index = "attendance_monthly.monthly";
			}
			else
			{
				$query_id = 'attendance.getMemberListMonthly';
				$args->sort_index = "attendance_monthly.monthly";
			}
		}
		else if($type=='rankweekly')
		{
			// selected_group_srl이 있으면 query id를 변경 (table join때문에)
			if($args->selected_group_srl)
			{
				$query_id = 'attendance.getMemberListWithinGroupWeekly';
				$args->sort_index = "attendance_weekly.weekly";
			}
			else
			{
				$query_id = 'attendance.getMemberListWeekly';
				$args->sort_index = "attendance_weekly.weekly";
			}
		}

		// 기타 변수들 정리
		$args->page = Context::get('page');
		$args->list_count = $list;
		$args->page_count = 10;
		return executeQuery($query_id, $args);
	}

	/**
	 * @brief 오늘 총 출석인원 계산
	 **/
	function getTodayTotalCount($today)
	{
		static $cache = array();
		if(isset($cache[$today]))
		{
			return $cache[$today];
		}
		
		// 이틀 이상 지난 데이터는 캐시 사용
		if(strtotime($today) < time() - (86400 * 2))
		{
			if($oCacheHandler = getModel('attendance')->getCacheHandler())
			{
				if(($cache[$today] = $oCacheHandler->get($oCacheHandler->getGroupKey('attendance', "todaytotal:$today"))) !== false)
				{
					return $cache[$today];
				}
			}
		}
		else
		{
			$oCacheHandler = null;
		}
		
		$arg = new stdClass();
		$arg->today = $today;
		$output = executeQuery("attendance.getTodayTotalCount",$arg);
		$cache[$today] = (int)$output->data->count;
		
		if($oCacheHandler)
		{
			$expires = 86400 * (31 - min(31, substr($today, -2)));
			$oCacheHandler->put($oCacheHandler->getGroupKey('attendance', "todaytotal:$today"), $cache[$today], $expires);
		}
		return $cache[$today];
	}

	/**
	 * @brief 시간대별 계산
	 **/
	function getTodayTimeCount($today_time)
	{
		static $cache = array();
		if(isset($cache[$today_time]))
		{
			return $cache[$today_time];
		}
		
		$arg = new stdClass();
		$arg->today_time = $today_time;
		$output = executeQuery("attendance.getTodayTimeCount",$arg);
		return $cache[$today_time] = (int)$output->data->count;
	}

	function getTodayTimeCountList($today)
	{
		$args = new stdClass();
		$args->today = $today;
		$output = executeQueryArray('attendance.getTodayTimeList', $args);

		$time_array = array();
		if($output->data)
		{
			foreach($output->data as $val)
			{
				$time_array[] = substr($val->regdate, 8, 2);
			}
		}
		else
		{
			return false;
		}

		return array_count_values($time_array);
	}

	/**
	 * @brief attendance테이블의 개인데이터 모두 삭제
	 **/
	function deleteAllAttendanceData($member_srl)
	{
		//등록된 인사말을 모두 지우기 위한 작업
		$oAttendanceModel = getModel('attendance');
		$oDocumentController = getController('document');
		$memberAttendanceInfo = $oAttendanceModel->getGreetingsList($member_srl);

		//등록된 인사말 모두 제거
		if(!$memberAttendanceInfo->data->greetings)
		{
			foreach($memberAttendanceInfo->data as $data)
			{
				if(substr($data->greetings,0,1) == '#')
				{
					$length = strlen($data->greetings) -1;
					$document_srl = substr($data->greetings, 1, $length);
					$oDocumentController->deleteDocument($document_srl,true);
				}
			}
		}

		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery("attendance.deleteAllAttendanceData",$args);

		$oAttendanceModel->clearCacheByMemberSrl($member_srl);
	}

	/**
	 * @brief attendance_total 테이블의 개인데이터 모두 삭제
	 **/
	function deleteAllAttendanceTotalData($member_srl)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery("attendance.deleteAllAttendanceTotalData",$args);

		$oAttendanceModel = getModel('attendance');
		$oAttendanceModel->clearCacheByMemberSrl($member_srl);
	}

	/**
	 * @brief attendance_yearly 테이블의 개인데이터 모두 삭제
	 **/
	function deleteAllAttendanceYearlyData($member_srl)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery("attendance.deleteAllAttendanceYearlyData",$args);

		$oAttendanceModel = getModel('attendance');
		$oAttendanceModel->clearCacheByMemberSrl($member_srl);
	}

	/**
	 * @brief attendance_monthly 테이블의 개인데이터 모두 삭제
	 **/
	function deleteAllAttendanceMonthlyData($member_srl)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery("attendance.deleteAllAttendanceMonthlyData",$args);

		$oAttendanceModel = getModel('attendance');
		$oAttendanceModel->clearCacheByMemberSrl($member_srl);
	}

	/**
	 * @brief attendance_weekly 테이블의 개인데이터 모두 삭제
	 **/
	function deleteAllAttendanceWeeklyData($member_srl)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery("attendance.deleteAllAttendanceWeeklyData",$args);

		$oAttendanceModel = getModel('attendance');
		$oAttendanceModel->clearCacheByMemberSrl($member_srl);
	}

	/**
	 * @brief attendance_weekly 테이블의 개인데이터 삭제
	 **/
	function deleteAttendanceWeeklyData($member_srl, $week)
	{
		$args = new stdClass();
		$args->monday = $week->monday;
		$args->sunday = $week->sunday;
		$args->member_srl = $member_srl;
		$output = executeQuery("attendance.deleteAttendanceWeeklyData",$args);

		$oAttendanceModel = getModel('attendance');
		$oAttendanceModel->clearCacheByMemberSrl($member_srl, 'weekly', $week);
	}

	/**
	 * @brief attendance_monthly 테이블의 개인데이터 삭제
	 **/
	function deleteAttendanceMonthlyData($member_srl, $monthly)
	{
		$args = new stdClass();
		$args->monthly = $monthly;
		$args->member_srl = $member_srl;
		$output = executeQuery("attendance.deleteAttendanceMonthlyData",$args);

		$oAttendanceModel = getModel('attendance');
		$oAttendanceModel->clearCacheByMemberSrl($member_srl, 'monthly', $monthly);
	}

	/**
	 * @brief attendance_yearly 테이블의 개인데이터 삭제
	 **/
	function deleteAttendanceYearlyData($member_srl, $year)
	{
		$args = new stdClass();
		$args->year = $year;
		$args->member_srl = $member_srl;
		$output = executeQuery("attendance.deleteAttendanceYearlyData",$args);

		$oAttendanceModel = getModel('attendance');
		$oAttendanceModel->clearCacheByMemberSrl($member_srl, 'yearly', $yearly);
	}

	/**
	 * @brief 주간 획득포인트 구하는 쿼리
	 **/
	function getWeeklyPoint($member_srl, $week)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->monday = $week->monday;
		$args->sunday = $week->sunday;
		$output = executeQueryArray("attendance.getWeeklyPoint",$args);
		if(!$output->data) $output->data = array();
		return $output;
	}

	/**
	 * @brief 월간 획득포인트 구하는 쿼리
	 **/
	function getMonthlyPoint($member_srl, $monthly)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->monthly = $monthly;
		$output = executeQueryArray("attendance.getMonthlyPoint",$args);
		if(!$output->data) $output->data = array();
		return $output;
	}

	/**
	 * @brief 연간 획득포인트 구하는 쿼리
	 **/
	function getYearlyPoint($member_srl, $year)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->year = $year;
		$output = executeQueryArray("attendance.getYearlyPoint",$args);
		if(!$output->data) $output->data = array();
		return $output;
	}

	/**
	 * @brief 총 출석포인트 구하는 쿼리(연간 획득포인트 쿼리 이용)
	 **/
	function getTotalPoint($member_srl)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQueryArray("attendance.getYearlyPoint",$args);
		if(!$output->data) $output->data = array();
		return $output;
	}

	/**
	 * @brief 중복 출석정보 구하는 쿼리
	 **/
	function getDuplicatedData($member_srl,$selected_date)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->selected_date = $selected_date;
		$output = executeQueryArray("attendance.getDuplicatedData",$args);
		if(!$output->data) $output->data = array();
		return $output;
	}

	/**
	 * @brief attendance 테이블의 중복출석데이터 삭제(deleteAllAttendanceData 쿼리 이용)
	 **/
	function deleteDuplicatedData($member_srl, $selected_date)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->selected_date = $selected_date;
		$output = executeQuery("attendance.deleteAllAttendanceData",$args);
		return $output;
	}

	/**
	 * @brief 연/월/주간 출석정보 fix
	 **/
	function fixYearMonthWeek($obj)
	{
		$oAttendanceModel = getModel('attendance');
		$oAttendanceAdminModel = getAdminModel('attendance');

		$year = substr($obj->selected_date,0,4);
		$oAttendanceAdminModel->deleteAttendanceYearlyData($obj->member_srl, $year);
		$points = $oAttendanceAdminModel->getWeeklyPoint($obj->member_srl, $year);
		$sum=0;
		foreach($points->data as $val)
		{
			$sum+=$val->today_point;
		}
		$attendance = $oAttendanceModel->getYearlyData($year,$obj->member_srl);
		$oAttendanceModel->insertYearly($obj->member_srl, $attendance, $sum, $obj->selected_date.'000000');

		$monthly = substr($obj->selected_date,0,6);
		$oAttendanceAdminModel->deleteAttendanceMonthlyData($obj->member_srl, $monthly);
		$points = $oAttendanceAdminModel->getMonthlyPoint($obj->member_srl, $monthly);
		$sum=0;
		foreach($points->data as $val)
		{
			$sum+=$val->today_point;
		}
		$attendance = $oAttendanceModel->getMonthlyData($monthly,$obj->member_srl);
		$oAttendanceModel->insertMonthly($obj->member_srl, $attendance, $sum, $obj->selected_date.'000000');

		$week = $oAttendanceModel->getWeek($obj->selected_date);
		$oAttendanceAdminModel->deleteAttendanceWeeklyData($obj->member_srl, $week);
		$points = $oAttendanceAdminModel->getWeeklyPoint($obj->member_srl, $week);
		$sum=0;
		foreach($points->data as $val)
		{
			$sum+=$val->today_point;
		}
		$attendance = $oAttendanceModel->getWeeklyAttendance($obj->member_srl, $week);
		$oAttendanceModel->insertWeekly($obj->member_srl, $attendance, $sum, $obj->selected_date.'000000');
		
		$oAttendanceModel->clearCacheByMemberSrl($obj->member_srl);
	}
}
