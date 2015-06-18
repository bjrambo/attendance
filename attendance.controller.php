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

	function procAttendanceModifyContinuous()
	{
		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin != 'Y')
		{
			return new Object(-1, '관리자만 설정이 가능합니다.');
		}

		$oAttendanceModel = getModel('attendance');
		$obj = Context::getRequestVars();
		$member_srl = $obj->member_srl;
		if(!$member_srl)
		{
			return new Object(-1, '회원번호는 필수 입니다.');
		}
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->continuity = $obj->continuity;
		$output = executeQuery('attendance.updateTotal', $args);
		if($output->toBool())
		{
			$this->setMessage('수정완료');
		}
		else
		{
			$this->errorMessage('에러발생');
		}
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', 'attendance', 'act', 'dispAttendanceModifyContinuous', 'member_srl', $member_srl);
			header('location: ' . $returnUrl);
			return;
		}
	}

	/**
	 * @brief 출석부 기록
	 **/
	function procAttendanceInsertAttendance()
	{
		$today = zDate(date('YmdHis'),"Ymd");
		if($_SESSION['is_attended'] == $today)
		{
			return new Object(-1,'attend_already_checked');
		}

		$_SESSION['is_attended'] = $today;

		/*attendance model 객체 생성*/
		$oAttendanceController = getController('attendance');
		$oAttendanceModel = getModel('attendance');
		$obj = Context::getRequestVars();

		$config = $oAttendanceModel->getConfig();

		$ip_count = $oAttendanceModel->getDuplicateIpCount($today, $_SERVER['REMOTE_ADDR']);
		if($ip_count >= $config->allow_duplicaton_ip_count)
		{
			return new Object(-1, 'attend_allow_duplicaton_ip_count');
		}

		//인사말 필터링('#'시작문자 '^'시작문자 필터링)
		if(preg_match("/^\#/",$obj->greetings)) return new Object(-1, 'attend_greetings_error');

		$output = $oAttendanceController->insertAttendance($obj->about_position, $obj->greetings);

		$this->setMessage('att_success');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', 'attendance');
			header('location: ' . $returnUrl);
			return;
		}
	}


	/**
	 * @brief 출석부 기록 함수
	 */
	function insertAttendance($about_position, $greetings, $member_srl=null)
	{
		$oAttendanceModel = getModel('attendance');
		/*사용자 정보 로드*/
		$logged_info = Context::get('logged_info');
		if(!$logged_info)
		{
			if($member_srl)
			{
				$oMemberModel = getModel('member');
				$logged_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
			}
			else
			{
				return;
			}
		}

		$today = zDate(date('YmdHis'),"Ymd");
		$year = zDate(date('YmdHis'),"Y");
		$year_month = zDate(date('YmdHis'),"Ym");
		$yesterday = zDate(date("YmdHis",strtotime("-1 day")),"Ymd");

		$oModuleModel = getModel('module');
		$config = $oAttendanceModel->getConfig();

		//포인트 모듈 연동
		$oPointController = getController('point');
		$obj = new stdClass;
		$obj->continuity_day = $config->continuity_day;
		$obj->continuity_point = $config->continuity_point;
		$obj->today_point = $config->add_point;
		$obj->greetings = $greetings;
		$obj->member_srl = $logged_info->member_srl;

		//관리자 출석이 허가가 나지 않았다면,
		if($config->about_admin_check != 'yes' && $logged_info->is_admin=='Y')
		{
			return new Object(-1, '관리자는 출석할 수 없습니다.');
		}

		/*출석이 되어있는지 확인 : 오늘자 로그인 회원의 DB기록 확인*/
		if($oAttendanceModel->getIsChecked($logged_info->member_srl)>0)
		{
			return new Object(-1, 'attend_already_checked');
		}

		//ip중복 횟수 확인
		$ip_count = $oAttendanceModel->getDuplicateIpCount($today, $_SERVER['REMOTE_ADDR']);
		if($ip_count >= $config->allow_duplicaton_ip_count)
		{
			return new Object(-1, 'attend_allow_duplicaton_ip_count');
		}

		$is_logged = Context::get('is_logged');
		if(!$is_logged)
		{
			if($member_srl)
			{
				$is_logged = true;
			}
		}

		//logged && 기록이 없고, 출석 제한 시간대가 아니면
		if($oAttendanceModel->getIsChecked($logged_info->member_srl)==0 && $oAttendanceModel->availableCheck($config) == 0 && $is_logged)
		{
			//등수 확인
			$position = $oAttendanceModel->getPositionData($today);
			//1,2,3,등 가산점 부여
			if($about_position == 'yes')
			{
				if($position == 0)
				{
					$obj->today_point += $config->first_point;
				}
				else if($position == 1)
				{
					$obj->today_point += $config->second_point;
				}
				else if($position == 2)
				{
					$obj->today_point += $config->third_point;
				}
			}

			/*연속출석*/
			if($oAttendanceModel->isExistTotal($logged_info->member_srl, $today) >= 0)
			{
				/*연속출석 일수 받기*/
				$yesterday_continuity_data = $oAttendanceModel->isExistContinuity($logged_info->member_srl, $yesterday);

				//어제 출석했다면
				if($yesterday_continuity_data > 0)
				{
					$continuity = $oAttendanceModel->getContinuityData($logged_info->member_srl, $yesterday);
					/*연속출석일수가 설정된 일수보다 많고, 설정된 연속출석일이 0일이 아니고, 연속출석 여부가 yes 이면, 보너스 부여*/
					if($continuity->data+1 >=$obj->continuity_day && $obj->continuity_day != 0 && $config->about_continuity=='yes')
					{
						$obj->today_point += $obj->continuity_point;
						$continuity->point = $obj->continuity_point;
					}
					$continuity->data++;
				}
				else
				{
					//어제 출석 정보가 없다면
					$continuity->data = 1;
					$obj->perfect_m = 'N';
				}
				$obj->a_continuity = $continuity->data;
			}

			/*지정일 포인트 지급*/
			if($config->about_target == 'yes')
			{
				if($today == $config->target_day)
				{
					$obj->today_point += $config->target_point;
					$obj->present_y = 'N';
				}
			}
			elseif($config->about_target == 'gift')
			{
				$todaygift = $oAttendanceModel->getTodayGiftCount($today);
				if($todaygift <= $config->manygiftlist && $today == $config->target_day)
				{
					$intrand = rand(1,100);
					if($intrand <= $config->gift_random)	
					{
						$gift_args = new stdClass();
						$gift_args->present_srl = getNextSequence();
						$gift_args->member_srl = $logged_info->member_srl;
						$gift_args->present = $config->giftname;
						$gift_args->sender = 'N';
						$output_gift = executeQuery("attendance.insertPresent", $gift_args);
						$obj->present_y = 'Y';
					}

				}
				else
				{
					$obj->present_y = 'N';
				}
			}
			else
			{
				$obj->present_y = 'N';
			}

			/*개근포인트 지급*/
			$about_perfect = $oAttendanceModel->isPerfect($logged_info->member_srl, $today, false);
			if($about_perfect->yearly_perfect == 1)
			{
				$obj->today_point += $config->yearly_point;
			}
			if($about_perfect->monthly_perfect == 1)
			{
				$obj->today_point += $config->monthly_point;
			}

			$week = $oAttendanceModel->getWeek($today);
			$weekly_data = $oAttendanceModel->getWeeklyData($logged_info->member_srl, $week);
			if($weekly_data->weekly == 6)
			{
				$obj->today_point += $config->weekly_point;
			}

			/*정근포인트 관련 추가*/
			if($config->about_diligence_yearly == 'yes')
			{
				if($oAttendanceModel->checkYearlyDiligence($logged_info->member_srl, $config->diligence_yearly-1, null) == 1)
				{
					$obj->today_point += $config->diligence_yearly_point;
				}
			}
			if($config->about_diligence_monthly == 'yes')
			{
				if($oAttendanceModel->checkMonthlyDiligence($logged_info->member_srl, $config->diligence_monthly-1, null) == 1)
				{
					$obj->today_point += $config->diligence_monthly_point;
				}
			}
			if($config->about_diligence_weekly == 'yes')
			{
				if($oAttendanceModel->checkWeeklyDiligence($logged_info->member_srl, $config->diligence_weekly-1, null) == 1)
				{
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
						$obj->att_random_set = 0;
					}
					else
					{
						$obj->today_point;
						$obj->today_random = 0;
						$obj->att_random_set = 0;
					}
				}
				else
				{
					$obj->today_point += $sosirandom;
					$obj->today_random = $sosirandom;
					$obj->att_random_set = 0;
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
							$obj->att_random_set = 0;
						}
						elseif($win<$config->random_small_win)
						{
							$sosirandom = mt_rand($config->random_big_point_f,$config->random_big_point_s);
							$obj->today_point += $sosirandom;
							$obj->today_random = $sosirandom;
							$obj->att_random_set = 1;
						}
					}
					else
					{
						$obj->today_point;
						$obj->today_random = 0;
						$obj->att_random_set = 0;
					}
				}
				else
				{
					$sosirandom = mt_rand($config->random_small_point_f,$config->random_small_point_s);
					$obj->today_point += $sosirandom;
					$obj->today_random = $sosirandom;
					$obj->att_random_set = 0;
				}
			}
			else
			{
				$obj->today_point;
				$obj->att_random_set = '0';
			}


			if($config->about_birth_day=='yes')
			{
				/* 생일 포인트 추가 */
				$oMemberModel = getModel('member');
				$member_srl = $logged_info->member_srl;
				$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
				$birthdays = substr($member_info->birthday,4,4);
				$todays = substr($today,4,4);

				if($todays==$birthdays)
				{
					$obj->today_point += $config->brithday_point;
				}
				else
				{
					$obj->today_point;
				}
			}
			else
			{
				$obj->today_point;
			}

			if(!$logged_info->member_srl)
			{
				return;
			}

			$oModule = getModel('module');
			$module_info = $oModule->getModuleInfoByMid('attendance');
			if(!$module_info->module_srl)
			{
				return new Object(-1, 'attend_no_board');
			}

			if($config->use_document == 'yes')
			{
				// document module의 model 객체 생성
				$oDocumentModel = getModel('document');

				// document module의 controller 객체 생성
				$oDocumentController = getController('document');

				if(strlen($obj->greetings) > 0 && $obj->greetings!='^auto^')
				{
					/*Document module connection : greetings process*/
					$d_obj = new stdClass;
					$d_obj->content = $obj->greetings;
					$d_obj->nick_name = $logged_info->nick_name;
					$d_obj->email_address = $logged_info->email_address;
					$d_obj->homepage = $logged_info->homepage;
					$d_obj->is_notice = 'N';
					$d_obj->module_srl = $module_info->module_srl;
					$d_obj->allow_comment = 'Y';
					$output = $oDocumentController->insertDocument($d_obj, false);
					if(!$output->get('document_srl'))
					{
						return new Object(-1, 'attend_error_no_greetings');
					}
					$obj->greetings = "#".$output->get('document_srl');
				}
			}

			/*접속자의 ip주소 기록*/
			$obj->ipaddress = $_SERVER['REMOTE_ADDR'];
			$obj->attendance_srl = getNextSequence();
			$obj->regdate = zDate(date("YmdHis"),"YmdHis");

			/*Query 실행 : 출석부 기록*/
			$output = executeQuery("attendance.insertAttendance", $obj);
			if(!$output->toBool())
			{
				return $output;
			}


			$trigger_obj = new stdClass();
			$trigger_obj->regdate = $obj->regdate;
			$trigger_obj->ipaddress = $obj->ipaddress;
			$trigger_obj->today_point = $obj->today_point;
			$trigger_obj->greetings = $obj->greetings;
			$trigger_obj->member_srl = $obj->member_srl;
			// Attendance insert Trigger setting
			if($output->toBool())
			{
				$trigger_output = ModuleHandler::triggerCall('attendance.insertAttendance', 'after', $trigger_obj);
				if(!$trigger_output->toBool())
				{
					return $trigger_output;
				}
			}

			/*포인트 추가*/
			if($obj->today_point != 0 && $logged_info->member_srl)
			{
				$oPointController->setPoint($logged_info->member_srl,$obj->today_point,'add');
			}

			/*attendance_total 테이블에 총 출석내용 및 연속출석데이터 기록(2009.02.15)*/
			if($oAttendanceModel->isExistTotal($logged_info->member_srl) == 0)
			{
				/*총 출석횟수 계산*/
				$total_attendance = $oAttendanceModel->getTotalAttendance($logged_info->member_srl);
				/*총 출석 기록*/
				$oAttendanceModel->insertTotal($logged_info->member_srl, $continuity, $total_attendance, $obj->today_point, null);
			}
			else
			{
				/*총 출석횟수 계산*/
				$total_attendance = $oAttendanceModel->getTotalAttendance($logged_info->member_srl);
				/*총 출석포인트 받아오기*/
				$total_point = $oAttendanceModel->getTotalPoint($logged_info->member_srl);
				$total_point += $obj->today_point;
				/*총 출석 기록*/
				$oAttendanceModel->updateTotal($logged_info->member_srl, $continuity, $total_attendance, $total_point, null);
			}

			/* attendace_yearly 테이블에 연간 출석 데이터 기록(2009.02.15) */
			if($oAttendanceModel->isExistYearly($logged_info->member_srl, $year) == 0)
			{
				/*올 해 출석횟수 계산*/
				$yearly_data = $oAttendanceModel->getYearlyData($year, $logged_info->member_srl);
				/*출석 포인트는 초기화(올 해 처음이므로)*/
				$yearly_point = $obj->today_point;
				/*연간출석데이터 추가*/
				$oAttendanceModel->insertYearly($logged_info->member_srl, $yearly_data, $yearly_point, null);
			}
			else
			{
				/*올 해 출석횟수 계산*/
				$yearly_data = $oAttendanceModel->getYearlyData($year, $logged_info->member_srl);
				/*출석 포인트는 예전 자료 꺼내기*/
				$year_info = $oAttendanceModel->getYearlyAttendance($logged_info->member_srl, $year);
				$yearly_point = $year_info->yearly_point;
				$yearly_point += $obj->today_point;
				/*연간출석데이터 업데이트*/
				$oAttendanceModel->updateYearly($logged_info->member_srl, $year, $yearly_data, $yearly_point,null);
			}
			
			/*attendance_monthly 테이블에 월간 출석 데이터 기록(2009.02.15)*/
			if($oAttendanceModel->isExistMonthly($logged_info->member_srl, $year_month) == 0)
			{
				/*이달 출석횟수 계산*/
				$monthly_data = $oAttendanceModel->getMonthlyData($year_month, $logged_info->member_srl);
				/*출석 포인트는 초기화(이달 처음이므로)*/
				$monthly_point = $obj->today_point;
				/*월간출석데이터 추가*/
				$oAttendanceModel->insertMonthly($logged_info->member_srl, $monthly_data, $monthly_point, null);
			}
			else
			{
				/*이달 출석횟수 계산*/
				$monthly_data = $oAttendanceModel->getMonthlyData($year_month, $logged_info->member_srl);
				/*출석 포인트는 예전 자료 꺼내기*/
				$month_info = $oAttendanceModel->getMonthlyAttendance($logged_info->member_srl, $year_month);
				$monthly_point = $month_info->monthly_point;
				$monthly_point += $obj->today_point;
				/*월간출석데이터 업데이트*/
				$oAttendanceModel->updateMonthly($logged_info->member_srl, $year_month, $monthly_data, $monthly_point, null);
			}
			
			/*attendance_weekly 테이블에 주간 출석 데이터 기록(2009.02.15)*/
			$week = $oAttendanceModel->getWeek($today);
			if($oAttendanceModel->isExistWeekly($logged_info->member_srl, $week) == 0 )
			{
				/*이번 주 출석 횟수 계산*/
				$weekly_data = $oAttendanceModel->getWeeklyAttendance($logged_info->member_srl, $week);
				/*출석 포인트는 오늘 받은 포인트로 초기화*/
				$weekly_point = $obj->today_point;
				/*주간 출석데이터 추가*/
				$oAttendanceModel->insertWeekly($logged_info->member_srl, $weekly_data, $weekly_point, null);
			}
			else
			{
				/*이번 주 출석 횟수 계산*/
				$weekly_data = $oAttendanceModel->getWeeklyAttendance($logged_info->member_srl, $week);
				/*출석 포인트는 예전자료 꺼내기*/
				$week_info = $oAttendanceModel->getWeeklyData($logged_info->member_srl, $week);
				$weekly_point = $week_info->weekly_point;
				$weekly_point += $obj->today_point;
				/*주간 출석데이터 업데이트*/
				$oAttendanceModel->updateWeekly($logged_info->member_srl, $week, $weekly_data, $weekly_point, null);	
			}
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
		$_SESSION['is_attended'] = '0';

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
		$config = $oAttendanceModel->getConfig();

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
		$oAttendanceModel = getModel('attendance');
		$config = $oAttendanceModel->getConfig();

		if($config->about_auto_attend == 'yes')
		{
			$this->insertAttendance('yes','^auto^',$obj->member_srl);
		}
	}
	
	function triggerSou(&$content)
	{
		$oModuleModel = getModel('module');
		$oAttendanceModel = getModel('attendance');
		$config = $oAttendanceModel->getConfig();
		$logged_info = Context::get('logged_info');
		$act = Context::get('act');

		if($act == 'dispMemberModifyInfo' && $config->about_birth_day=='yes' && $config->about_birth_day_y=='yes')
		{
			$oMemberModel = getModel('member');
			//module의 설정값 가져오기

			$member_srl = $logged_info->member_srl;

			$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
			$content = str_replace('<input type="text" placeholder="YYYY-MM-DD" name="birthday_ui"',Context::getLang('출석부모듈에 의해 생일 변경이 금지되었습니다.').'<br><input type="text" name="birthday" placeholder="YYYY-MM-DD" disabled="disabled"', $content);
			$content = str_replace('<input type="button" value="삭제"','<input type="button" value="삭제" disabled="disabled"', $content);
		}

		return new Object();
	}

	function triggerUpdateMemberBefore($args)
	{
		// 로그인 정보 가져옴
		$logged_info = Context::get('logged_info');
		$oMemberModel = getModel('member');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($logged_info->member_srl);

		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('attendance');

		if($logged_info->is_admin=='N' && $config->about_birth_day=='yes' && $config->about_birth_day_y=='yes')
		{
			if($member_info->birthday!=$args->birthday)
			{
				return new Object(-1, '출석부모듈에 의해 생일을 수정 할 수 없도록 되어있습니다.');
			}
		}
	}

}
