<?PHP
/**
* @class 출석부 모듈의 attendanceController 클래스
* @author BJRambo (sosifam@070805.co.kr)
* @JS필터를 통해 요청된 작업 수행
*
* 출석부 기록, 관리자의 임의 출석내용 변경 시 호출되는 함수들이 있습니다.
**/

class attendanceController extends attendance
{

	/**
	 * @brief 초기화
	 **/
	function init()
	{
	}

	/**
	 * @brief 출석부 기록
	 **/
	function procAttendanceInsertAttendance()
	{
		$today = zDate(date('YmdHis'),"Ymd");
		//if($_SESSION['is_attended'] == $today) return new Object(-1,'attend_already_checked');

		/*attendance model 객체 생성*/
		$oAttendanceModel = getModel('attendance');
		$obj = Context::getRequestVars();

		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('attendance');
		if(!$config)
		{
			$config = new stdClass;
		}
		$ip_count = $oAttendanceModel->getDuplicateIpCount($today, $_SERVER['REMOTE_ADDR']);
		if($ip_count >= $config->allow_duplicaton_ip_count)
		{
			return new Object(-1, 'attend_allow_duplicaton_ip_count');
		}

		//인사말 필터링('#'시작문자 '^'시작문자 필터링)
		if(preg_match("/^\#/",$obj->greetings)) return new Object(-1, 'attend_greetings_error');



		$oAttendanceModel->insertAttendance($obj->about_position, $obj->greetings);

		$this->setMessage('att_success');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', 'attendance');
			header('location: ' . $returnUrl);
			return;
		}

	}

	/**
	 * @brief 관리자 임의 출석 제거
	 **/
	function procAttendanceDeleteData()
	{

		//포인트 모듈 연동
		$oPointController = getController('point');

		/*attendance model 객체 생성*/
		$oAttendanceModel = getModel('attendance');

		//넘겨받고
		$obj = Context::getRequestVars();
		$year = substr($obj->check_day,0,4);
		$year_month = substr($obj->check_day,0,6);
		$args = new stdClass;
		$args->check_day = $obj->check_day;
		$args->member_srl = $obj->member_srl;

		$oMemberModel = getModel('member');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($obj->member_srl);

		/*출석당일 포인트 꺼내오기*/
		$daily_info = $oAttendanceModel->getUserAttendanceData($member_info->member_srl, $obj->check_day);

		//삭제시 출책여부를 하지않았다고 인식시켜 스킨용 표기 방법 찾게 (2013.12.11 by BJRambo)
		//$_SESSION['is_attended'] = '0';

		if($oAttendanceModel->getIsCheckedA($obj->member_srl, $obj->check_day)!=0)
		{
			//포인트도 감소
			$oPointController->setPoint($member_info->member_srl, $daily_info->today_point, 'minus');


			//등록된 인사말 제거
			if(substr($daily_info->greetings,0,1) == '#')
			{
				$length = strlen($daily_info->greetings) -1;
				$document_srl = substr($daily_info->greetings, 1, $length);
				$oDocumentController = getController('document');
				$oDocumentController->deleteDocument($document_srl,true);
			}

			//출석기록에서 제거(쿼리 수행)
			executeQuery("attendance.deleteAttendanceData", $args);

			/*기록될 날짜!!*/
			$regdate =  sprintf("%s235959",$obj->check_day);
			$continuity = new stdClass;
			$continuity->data = 1;
			$continuity->point = 0;
			//총 출석데이터 갱신
			if($oAttendanceModel->isExistTotal($obj->member_srl) == 0)
			{
				/*총 출석횟수 계산*/
				$total_attendance = $oAttendanceModel->getTotalAttendance($obj->member_srl);
				/*총 출석 기록*/
				$oAttendanceModel->insertTotal($obj->member_srl, $continuity, $total_attendance, 0, $regdate);
			}
			else
			{
				/*총 출석횟수 계산*/
				$total_attendance = $oAttendanceModel->getTotalAttendance($obj->member_srl);
				/*총 출석포인트 받아오기*/
				$total_point = $oAttendanceModel->getTotalPoint($obj->member_srl);
				$total_point -= $daily_info->today_point;
				if($total_point < 0) { $total_point = 0; }
				/*총 출석 기록*/
				$oAttendanceModel->updateTotal($obj->member_srl, $continuity, $total_attendance, $total_point, $regdate);
			}

			//연간 출석데이터 갱신 연간 데이터가 없으면
			if($oAttendanceModel->isExistYearly($obj->member_srl, $year) == 0)
			{
				/*올 해 출석횟수 계산*/
				$yearly_data = $oAttendanceModel->getYearlyData($year, $obj->member_srl);
				/*연간출석데이터 추가*/
				$oAttendanceModel->insertYearly($obj->member_srl, $yearly_data, 0, $regdate);
			}
			else
			{
				/*올 해 출석횟수 계산*/
				$yearly_data = $oAttendanceModel->getYearlyData($year, $obj->member_srl);
				/*출석 포인트는 예전 자료 꺼내기*/
				$year_info = $oAttendanceModel->getYearlyAttendance($obj->member_srl, $year);
				$yearly_point = $year_info->yearly_point;
				$yearly_point -= $daily_info->today_point;
				if($yearly_point <0 )
				{
					$yearly_point = 0;
				}
				/*연간출석데이터 업데이트*/
				$oAttendanceModel->updateYearly($obj->member_srl, $year, $yearly_data, $yearly_point, $regdate);
			}

			//월간 출석데이터 갱신
			if($oAttendanceModel->isExistMonthly($obj->member_srl, $year_month) == 0)
			{
				/*이달 출석횟수 계산*/
				$monthly_data = $oAttendanceModel->getMonthlyData($year_month, $obj->member_srl);
				/*월간출석데이터 추가*/
				$oAttendanceModel->insertMonthly($obj->member_srl, $monthly_data, 0, $regdate);
			}
			else
			{
				/*이달 출석횟수 계산*/
				$monthly_data = $oAttendanceModel->getMonthlyData($year_month, $obj->member_srl);
				/*출석 포인트는 예전 자료 꺼내기*/
				$month_info = $oAttendanceModel->getMonthlyAttendance($obj->member_srl, $year_month);
				$monthly_point = $month_info->monthly_point;
				$monthly_point -= $daily_info->today_point;
				if($monthly_point < 0)
				{
					$monthly_point = 0;
				}
				/*월간출석데이터 업데이트*/
				$oAttendanceModel->updateMonthly($obj->member_srl, $year_month, $monthly_data, $monthly_point, $regdate);
			}

			//주간 출석데이터 갱신
			$week = $oAttendanceModel->getWeek($obj->check_day);
			if($oAttendanceModel->isExistWeekly($obj->member_srl, $week) == 0 )
			{
				/*이번 주 출석 횟수 계산*/
				$weekly_data = $oAttendanceModel->getWeeklyAttendance($obj->member_srl, $week);
				/*주간 출석데이터 추가*/
				$oAttendanceModel->insertWeekly($obj->member_srl, $weekly_data, 0, $regdate);
			}
			else
			{
				/*이번 주 출석 횟수 계산*/
				$weekly_data = $oAttendanceModel->getWeeklyAttendance($obj->member_srl, $week);
				/*출석 포인트는 예전자료 꺼내기*/
				$week_info = $oAttendanceModel->getWeeklyData($obj->member_srl, $week);
				$weekly_point = $week_info->weekly_point;
				$weekly_point -= $daily_info->today_point;
				if($weekly_point < 0){ $weekly_point = 0; }
				/*주간 출석데이터 업데이트*/
				$oAttendanceModel->updateWeekly($obj->member_srl, $week, $weekly_data, $weekly_point, $regdate);	
			}
			$this->setMessage("success_deleted");
		}
	}

	/**
	 * @brief 관리자 임의 출석 기록
	 **/
	function procAttendanceCheckData()
	{

		//포인트 모듈 연동
		$oPointController = getController('point');

		/*attendance model 객체 생성*/
		$oAttendanceModel = getModel('attendance');

		//넘겨받고
		$obj = Context::getRequestVars();
		$year = substr($obj->check_day,0,4);
		$args = new stdClass;
		$year_month = substr($obj->check_day,0,6);
		$args->check_day = $obj->check_day;
		$args->member_srl = $obj->member_srl;

		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('attendance');

		$oMemberModel = getModel('member');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($obj->member_srl);

		$obj->today_point = $config->add_point;

		if($oAttendanceModel->getIsCheckedA($obj->member_srl, $obj->check_day)==0)
		{
			/*기록될 날짜!!*/
			$regdate = sprintf("%s235959",$obj->check_day);
			$year = substr($obj->check_day,0,4);
			$year_month = substr($obj->check_day,0,6);
			$week = $oAttendanceModel->getWeek($obj->check_day);

			/*지정일 포인트 지급*/
			if($config->about_target == 'yes')
			{
				if($obj->check_day == $config->target_day)
				{
					$obj->today_point += $config->target_point;
				}
			}

			/*개근포인트 지급*/
			$about_perfect = $oAttendanceModel->isPerfect($obj->member_srl, $obj->check_day, false);
			if($about_perfect->yearly_perfect == 1)
			{
				$obj->today_point += $config->yearly_point;
			}
			if($about_perfect->monthly_perfect == 1)
			{
				$obj->today_point += $config->monthly_point;
			}
			$weekly_data = $oAttendanceModel->getWeeklyData($obj->member_srl, $week);
			if($weekly_data->weekly == 6 && $obj->check_day==$week->sunday)
			{
				$obj->today_point += $config->weekly_point;
			}

			/*정근포인트 관련 추가*/
			if($config->about_diligence_yearly == 'yes')
			{
				if($oAttendanceModel->checkYearlyDiligence($obj->member_srl, $config->diligence_yearly-1, $year) == 1)
				{
					$obj->today_point += $config->diligence_yearly_point;
				}
			}
			if($config->about_diligence_monthly == 'yes'){
				if($oAttendanceModel->checkMonthlyDiligence($obj->member_srl, $config->diligence_monthly-1, $year_month) == 1){
					$obj->today_point += $config->diligence_monthly_point;
				}
			}
			if($config->about_diligence_weekly == 'yes'){
				if($oAttendanceModel->checkWeeklyDiligence($obj->member_srl, $config->diligence_weekly-1, $obj->check_day) == 1){
					$obj->today_point += $config->diligence_weekly_point;
				}
			}

			/* 랜덤포인트 추가 */
			if($config->about_random == 'yes' && $config->minimum <= $config->maximum && $config->minimum >= 0 && $config->maximum >= 0 && $config->use_random_sm == 'no')
			{
				$sosirandom = mt_rand($config->minimum,$config->maximum);
				if($config->about_lottery == 'yes' && $config->lottery > 0 && $config->lottery <= 100)
				{
					$win = mt_rand(1,100);
					if($win<=$config->lottery) //30설정시 30퍼센트 확률로 당첨되도록 수정.(방향 수정)
					{
						$obj->today_point += $sosirandom;
						$obj->today_random = $sosirandom;
					}
					else
					{
						$obj->today_point;
						$obj->today_random = 0;
					}
				}
				else
				{
					$obj->today_point += $sosirandom;
					$obj->today_random = $sosirandom;
				}
			}
			elseif($config->about_random == 'yes' && $config->random_small_point_f <= $config->random_small_point_s && $config->random_small_point_f >= 0 && $config->random_small_point_s >= 0 && $config->use_random_sm == 'yes')
			{
				if($config->about_lottery == 'yes' && $config->lottery > 0 && $config->lottery <= 100)
				{
					$win = mt_rand(1,100);
					if($win<=$config->lottery)
					{
						// $win 이 small_win 보다 크고, big_win보다 작을경우
						if($win<=$config->lottery && $win>=$config->random_small_win)
						{
							$sosirandom = mt_rand($config->random_small_point_f,$config->random_small_point_s);
							$obj->today_point += $sosirandom;
							$obj->today_random = $sosirandom;
						}
						elseif($win<$config->random_small_win)
						{
							$sosirandom = mt_rand($config->random_big_point_f,$config->random_big_point_s);
							$obj->today_point += $sosirandom;
							$obj->today_random = $sosirandom;
						}
					}
					else
					{
						$obj->today_point;
						$obj->today_random = 0;
					}
				}
				else
				{
					$sosirandom = mt_rand($config->random_small_point_f,$config->random_small_point_s);
					$obj->today_point += $sosirandom;
					$obj->today_random = $sosirandom;
				}
			}
			else
			{
				$obj->today_point;
			}

			$args->regdate = $regdate;
			$args->attendance_srl = getNextSequence();
			$args->greetings="^admin_checked^";
			$args->today_point = $obj->today_point;
			$args->today_random = $obj->today_random;

			//출석부에 기록(쿼리 수행)
			executeQuery("attendance.insertAttendance", $args);

			//포인트도 기록
			$new_point += $obj->today_point;
			$oPointController->setPoint($member_info->member_srl,$obj->today_point, 'add');

			//총 출석데이터 갱신
			if($oAttendanceModel->isExistTotal($obj->member_srl) == 0)
			{
				/*총 출석횟수 계산*/
				$total_attendance = $oAttendanceModel->getTotalAttendance($obj->member_srl);
				$continuity = new stdClass;
				$continuity->data = 1;
				$continuity->point = 0;
				/*총 출석 기록*/
				$oAttendanceModel->insertTotal($obj->member_srl, $continuity, $total_attendance, $obj->today_point, $regdate);
			}
			else
			{
				/*총 출석횟수 계산*/
				$total_attendance = $oAttendanceModel->getTotalAttendance($obj->member_srl);
				/*총 출석포인트 받아오기*/
				$total_point = $oAttendanceModel->getTotalPoint($obj->member_srl);
				$total_point += $obj->today_point;
				$continuity = new stdClass;
				$continuity->data = null;
				$continuity->point = 0;
				/*총 출석 기록*/
				$oAttendanceModel->updateTotal($obj->member_srl, $continuity=null, $total_attendance, $total_point, $regdate);
			}

			//연간 출석데이터 갱신 
			if($oAttendanceModel->isExistYearly($obj->member_srl, $year) == 0)
			{
				/*올 해 출석횟수 계산*/
				$yearly_data = $oAttendanceModel->getYearlyData($year, $obj->member_srl);
				/*연간출석데이터 추가*/
				$oAttendanceModel->insertYearly($obj->member_srl, $yearly_data, $obj->today_point, $regdate);
			}
			else
			{
				/*올 해 출석횟수 계산*/
				$yearly_data = $oAttendanceModel->getYearlyData($year, $obj->member_srl);
				/*출석 포인트는 예전 자료 꺼내기*/
				$year_info = $oAttendanceModel->getYearlyAttendance($obj->member_srl, $year);
				$yearly_point = $year_info->yearly_point;
                $yearly_point += $obj->today_point;
				/*연간출석데이터 업데이트*/
				$oAttendanceModel->updateYearly($obj->member_srl, $year, $yearly_data, $yearly_point, $regdate);
			}

			//월간 출석데이터 갱신
			if($oAttendanceModel->isExistMonthly($obj->member_srl, $year_month) == 0)
			{
				/*이달 출석횟수 계산*/
				$monthly_data = $oAttendanceModel->getMonthlyData($year_month, $obj->member_srl);
				/*월간출석데이터 추가*/
				$oAttendanceModel->insertMonthly($obj->member_srl, $monthly_data, $obj->today_point, $regdate);
			}
			else
			{
				/*이달 출석횟수 계산*/
				$monthly_data = $oAttendanceModel->getMonthlyData($year_month, $obj->member_srl);
				/*출석 포인트는 예전 자료 꺼내기*/
				$month_info = $oAttendanceModel->getMonthlyAttendance($obj->member_srl, $year_month);
				$monthly_point = $month_info->monthly_point;
                $monthly_point += $obj->today_point;
				/*월간출석데이터 업데이트*/
				$oAttendanceModel->updateMonthly($obj->member_srl, $year_month, $monthly_data, $monthly_point, $regdate);
			}

			//주간 출석데이터 갱신
			if($oAttendanceModel->isExistWeekly($obj->member_srl, $week) == 0 )
			{
				/*이번 주 출석 횟수 계산*/
				$weekly_data = $oAttendanceModel->getWeeklyAttendance($obj->member_srl, $week);
				/*주간 출석데이터 추가*/
				$oAttendanceModel->insertWeekly($obj->member_srl, $weekly_data, $obj->today_point, $regdate);
			}
			else
			{
				/*이번 주 출석 횟수 계산*/
				$weekly_data = $oAttendanceModel->getWeeklyAttendance($obj->member_srl, $week);
				/*출석 포인트는 예전자료 꺼내기*/
				$week_info = $oAttendanceModel->getWeeklyData($obj->member_srl, $week);
				$weekly_point = $week_info->weekly_point;
                $weekly_point +=$obj->today_point;
				/*주간 출석데이터 업데이트*/
				$oAttendanceModel->updateWeekly($obj->member_srl, $week, $weekly_data, $weekly_point, $regdate);	
			}
            $this->setMessage('success_registed');
		}
	}

	/**
	 * @brief 출석내용 수정
	 **/
    function procAttendanceModifyData()
	{
		$obj = Context::getRequestVars();
		$oPointController = getController('point');
		$oAttendanceModel = getModel('attendance');
		$oAttendance = $oAttendanceModel->getAttendanceDataSrl($obj->attendance_srl);

		//입력한 시간 형식이 맞는지 판단하기
		if(strlen($obj->regdate) != 6) return ;
		$hour = substr($obj->regdate,0,2);
		$min = substr($obj->regdate,2,2);
		$sec = substr($obj->regdate,4,2);
		if(!$hour || $hour < 0 || $hour > 23) return;
		if(!$min || $min < 0 || $min > 59) return;
		if(!$sec || $sec < 0 || $sec > 59) return;

		$year = substr($obj->selected_date,0,4);
		$year_month = substr($obj->selected_date,0,6);
		$week = $oAttendanceModel->getWeek($obj->selected_date);

		//주간 출석포인트 꺼내기
		$weekly_info = $oAttendanceModel->getWeeklyData($oAttendance->member_srl, $week);
		//월간 출석포인트 꺼내기
		$monthly_info = $oAttendanceModel->getMonthlyAttendance($oAttendance->member_srl, $year_month);
		//연간 출석포인트 꺼내기
		$yearly_info = $oAttendanceModel->getYearlyAttendance($oAttendance->member_srl, $year);
		//총 출석포인트 꺼내기
		$total_info = $oAttendanceModel->getTotalData($oAttendance->member_srl);
		$continuity = new stdClass;
		$continuity->data = $total_info->continuity;
		$continuity->point = $total_info->continuity_point;
		//오늘날짜와 등록시각 수정
		$regdate = sprintf("%s%s",$obj->selected_date,$obj->regdate);

		//입력된 당일기록포인트가 기존포인트와 비교하여 차이만큼 회원의 포인트 추가/차감
		if(!$obj->today_point) return;
		if($obj->today_point < $oAttendance->today_point)
		{
			/*포인트 차이만큼 빼기*/
			$value = $oAttendance->today_point - $obj->today_point;
			$oPointController->setPoint($oAttendance->member_srl,$value,'minus');
			$oAttendanceModel->updateWeekly($oAttendance->member_srl, $week, null, $weekly_info->weekly_point -$value, $regdate);
			$oAttendanceModel->updateMonthly($oAttendance->member_srl, $year_month, null, $monthly_info->monthly_point-$value, $regdate);
			$oAttendanceModel->updateYearly($oAttendance->member_srl, $year, null, $yearly_info->yearly_point-$value, $regdate);
			$oAttendanceModel->updateTotal($oAttendance->member_srl, $continuity, null, $total_info->total_point-$value, $regdate);
		}
		else if($obj->today_point > $oAttendance->today_point)
		{
			/*포인트 차이만큼 더하기*/
			$value = $obj->today_point - $oAttendance->today_point;
			$oPointController->setPoint($oAttendance->member_srl,$value,'add');
			$oAttendanceModel->updateWeekly($oAttendance->member_srl, $week, null, $weekly_info->weekly_point + $value, $regdate);
			$oAttendanceModel->updateMonthly($oAttendance->member_srl, $year_month, null, $monthly_info->monthly_point + $value, $regdate);
			$oAttendanceModel->updateYearly($oAttendance->member_srl, $year, null, $yearly_info->yearly_point + $value, $regdate);
			$oAttendanceModel->updateTotal($oAttendance->member_srl, $continuity, null, $total_info->total_point + $value, $regdate);
		}

		//출석내용 최종 수정
		$oAttendanceModel->updateAttendance($obj->attendance_srl, $regdate, $obj->today_point, null, null);

		//회원의 출석통계 갱신
	}

    /**
     * @brief 회원 탈퇴시 출석 기록을 모두 제거하는 trigger
     **/
	function triggerDeleteMember($obj)
	{
		/*attendance admin model 객체 생성*/
		$oAttendanceAdminModel = getAdminModel('attendance');
		$oAttendanceAdminModel->deleteAllAttendanceData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceTotalData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceYearlyData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceMonthlyData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceWeeklyData($obj->member_srl);
		return new Object();
	}
    
	/**
	 * @brief Auto Attend trigger
	 **/
	function triggerAutoAttend($obj)
	{
		$today = zDate(date('YmdHis'),"Ymd");
		$arg = new stdClass;
		$arg->day = $today;
		$arg->member_srl = $obj->member_srl;
		$output = executeQuery('attendance.getIsChecked',$arg);
		if($output->data->count > 0 )
		{
			$_SESSION['is_attended'] = $today;
			return;
		}
		
		//module의 설정값 가져오기
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('attendance');
		
		if($config->about_auto_attend == 'yes')
		{
			$oAttendanceModel = getModel('attendance');
			$oAttendanceModel->insertAttendance('yes','^auto^',$obj->member_srl);
		}
	}
	
	function triggerSou(&$content)
	{
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('attendance');
		$logged_info = Context::get('logged_info');

		if($act == 'dispMemberModifyInfo' && $config->about_birth_day=='yes' && $config->about_birth_day_y=='yes')
		{
			$oAttendanceModel = getModel('attendance');
			$oMemberModel = getModel('member');
			//module의 설정값 가져오기

			$member_srl = $logged_info->member_srl;

			$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
			$content = str_replace('<input type="text" placeholder="YYYY-MM-DD" name="birthday_ui"',Context::getLang('출석부모듈에 의해 생일 변경이 금지되었습니다.').'<br><input type="text" name="birthday" placeholder="YYYY-MM-DD" disabled="disabled"', $content);
			$content = str_replace('<input type="button" value="삭제"','<input type="button" value="삭제" disabled="disabled"', $content);
		}

		return new Object();
	}


}
