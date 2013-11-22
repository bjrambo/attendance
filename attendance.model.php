<?PHP
/**
* @class 출석부 모듈의 attendanceModel 클래스
* @author 매실茶 (pppljh@gmail.com)
* @출석부 프로그램이 참조하는 일종의 라이브러리
*
* 각종 쿼리 처리 및 주요 계산을 담당하는 함수들이 모여있는 파일입니다.
**/

class attendanceModel extends attendance {

    /**
    * @brief 초기화
    **/
    function init(){
    }

    /**
    * @brief 오늘 같은 ip에서 몇번 출석했는지 출력~
    **/
    function getDuplicateIpCount($today, $ipaddress){
        $obj->today = $today;
        $obj->ipaddress = $ipaddress;
		$output = executeQuery('attendance.getDuplicateIpCount', $obj);
		return (int)$output->data->count;
    }

    /**
    * @brief 회원의 출석데이터 출력
    **/
    function getUserAttendanceData($member_srl, $date){
        $arg->member_srl = $member_srl;
        $arg->date = $date;
   		$output = executeQuery('attendance.getUserAttendanceData',$arg);
        return $output->data;
    }

    /**
    * @brief document_srl로 출석자료 찾기
    **/
    function getGreetingsData($document_srl){
        $arg->greetings = $document_srl;
   		$output = executeQuery('attendance.getGreetingsData',$arg);
        return $output->data;
    }

    /**
    * @brief 출석자료 업데이트
    **/
    function updateAttendance($attendance_srl, $regdate, $today_point=null, $member_srl=null, $greetings=null){
        $arg->attendance_srl = $attendance_srl;
        $arg->regdate = $regdate;
        $arg->today_point = $today_point;
        $arg->member_srl = $member_srl;
        $arg->greetings = $greetings;
   		executeQuery('attendance.updateAttendance',$arg);
    }

    /**
    * @brief attendance_srl로 출석자료 찾기
    **/
    function getAttendanceDataSrl($attendance_srl){
        $arg->attendance_srl = $attendance_srl;
   		$output = executeQuery('attendance.getAttendanceDataSrl',$arg);
        return $output->data;
    }

    /**
    * @brief 출석 여부 출력
    **/
	function getAttendanceData($member_srl, $selected_date){
		$flag=false;
		$arg->member_srl = $member_srl;
		$arg->selected_date = $selected_date;
		$output = executeQuery('attendance.getAttendanceData',$arg);
		$count = (int)$output->data->count;
		if($count==0){
			$flag = false;
		}else{
			$flag = true;
		}
		return $flag;
	}

    /**
    * @brief 오늘자 출석부 출력(선착순)
    **/
	function getAttendanceList($list_count, $today){
		$args->page = Context::get('page');
		$args->now = $today;
		$args->list_count = $list_count;
		$output = executeQueryArray('attendance.getAdminAttendanceList', $args);
		if(!$output->data) $output->data = array();
		return $output;
	}

    /**
    * @brief 오늘자 출석부 출력(역순)
    **/
	function getInverseList($list_count, $today){
		$args->page = Context::get('page');
		$args->now = $today;
		$args->list_count = $list_count;
		$output = executeQueryArray('attendance.getAdminInverseList', $args);
		if(!$output->data) $output->data = array();
		return $output;
	}

    /**
    * @brief 월별 출석통계 출력
    **/
	function getMonthlyData($monthly, $member_srl){
		$args->monthly = $monthly;
		$args->member_srl=$member_srl;
		$output = executeQuery('attendance.getMonthlyData', $args);
		return (int)$output->data->monthly_count;
	}

    /**
    * @brief 연도별 출석 통계 출력
    **/
	function getYearlyData($yearly, $member_srl){
		$args->yearly = $yearly;
		$args->member_srl=$member_srl;
		$output = executeQuery('attendance.getYearlyData', $args);
		return (int)$output->data->yearly_count;
	}

    /**
    * @brief 오늘 출석 했는지 확인
    **/
	function getIsChecked($member_srl){
		$arg->day = zDate(date('YmdHis'),"Ymd");
		$arg->member_srl = $member_srl;
		$output = executeQuery('attendance.getIsChecked',$arg);
		return (int)$output->data->count;
	}

    /**
    * @brief 선택한 날짜에 출석 했는지 확인
    **/
	function getIsCheckedA($member_srl, $today){
		$arg->day = $today;
		$arg->member_srl = $member_srl;
		$output = executeQuery('attendance.getIsChecked',$arg);
		return (int)$output->data->count;
	}


    /**
    * @brief 출석부 기록 함수
    **/	
	function insertAttendance($about_position, $greetings, $member_srl=null){
        //#문자 한번 더 필터링
        if(preg_match("/^\#/",$obj->greetings)) return new Object(-1,'attend_greetings_error');

		/*사용자 정보 로드*/
		$logged_info = Context::get('logged_info');
		if(!$logged_info){
		    if($member_srl){
		        $oMemberModel = &getModel('member');
		        $logged_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
		    }else{
		        return;
		    }
		}
		
		$today = zDate(date('YmdHis'),"Ymd");
		$year = zDate(date('YmdHis'),"Y");
		$year_month = zDate(date('YmdHis'),"Ym");
		$yesterday = zDate(date("YmdHis",strtotime("-1 day")),"Ymd");

    if($_SESSION['is_attended'] == $today) return new Object(-1,'attend_already_checked');

		/*각종 포인트 설정값 꺼내오기*/
		$output = executeQuery('attendance.getConfigData');
		$config_data = $output->data;

		//포인트 모듈 연동
        $oPointController = &getController('point');

		$obj->continuity_day = $config_data->continuity_day;
		$obj->continuity_point = $config_data->continuity_point;
		$obj->today_point = $config_data->add_point;
		$obj->greetings = $greetings;
        $obj->member_srl = $logged_info->member_srl;

        $oModuleModel = &getModel('module');
        $config = $oModuleModel->getModuleConfig('attendance');
        //관리자 출석이 허가가 나지 않았다면,
        if($config->about_admin_check == 'no' && $logged_info->is_admin=='Y'){ return; }

		/*출석이 되어있는지 확인 : 오늘자 로그인 회원의 DB기록 확인*/
        if($this->getIsChecked($logged_info->member_srl)>0){ 
            return new Object(-1,'attend_already_checked');
        }
        

        //ip중복 횟수 확인
        if(!$config->allow_duplicaton_ip_count) $config->allow_duplicaton_ip_count = 3;
        $ip_count = $this->getDuplicateIpCount($today, $_SERVER['REMOTE_ADDR']);
        if($ip_count >= $config->allow_duplicaton_ip_count){
            return new Object(-1,'attend_duplication_ip_error');
        }
        $is_logged = Context::get('is_logged');
        if(!$is_logged){
            if($member_srl){
                $is_logged = true;
            }
        }

		if($this->getIsChecked($logged_info->member_srl)==0 
            && $this->availableCheck($config_data) == 0 
            && $is_logged){	//logged && 기록이 없고, 출석 제한 시간대가 아니면
			
			//등수 확인
			$position = $this->getPositionData($today);
			//1,2,3,등 가산점 부여
			if($about_position == 'yes'){
				if($position == 0){
					$obj->today_point += $config_data->first_point;
				}else if($position == 1){
					$obj->today_point += $config_data->second_point;
				}else if($position == 2){
					$obj->today_point += $config_data->third_point;
				}
			}

			/*연속출석*/
			if($this->isExistTotal($logged_info->member_srl, $today) >= 0){
				/*연속출석 일수 받기*/
				$yesterday_continuity_data = $this->isExistContinuity($logged_info->member_srl, $yesterday);
				if($yesterday_continuity_data > 0){	//어제 출석했다면
                    $continuity = $this->getContinuityData($logged_info->member_srl, $yesterday);
					/*연속출석일수가 설정된 일수보다 많고, 설정된 연속출석일이 0일이 아니고, 연속출석 여부가 yes 이면, 보너스 부여*/
					if($continuity->data+1 >=$obj->continuity_day && $obj->continuity_day != 0 && $config_data->about_continuity=='yes'){
						$obj->today_point += $obj->continuity_point;
						$continuity->point = $obj->continuity_point;
					}
					$continuity->data++;
				} else {    //어제 출석 정보가 없다면
					$continuity->data = 1;                        
                }
			}

			/*지정일 포인트 지급*/
			if($config_data->about_target == 'yes'){
				if($today == $config_data->target_day){
					$obj->today_point += $config_data->target_point;
				}
			}

			/*개근포인트 지급*/
			$about_perfect = $this->isPerfect($logged_info->member_srl, $today, false);
			if($about_perfect->yearly_perfect == 1){
				$obj->today_point += $config_data->yealy_point;
			}
			if($about_perfect->monthly_perfect == 1){
				$obj->today_point += $config_data->monthly_point;
			}
			$week = $this->getWeek($today);
			$weekly_data = $this->getWeeklyData($logged_info->member_srl, $week);
			if($weekly_data->weekly == 6){
				$obj->today_point += $config_data->weekly_point;
			}

            /*정근포인트 관련 추가*/
    if($config_data->about_diligence_yearly == 'yes'){
        if($this->checkYearlyDiligence($logged_info->member_srl, $config_data->diligence_yearly-1, null) == 1){
            $obj->today_point += $config_data->diligence_yearly_point;
                }
            }
    if($config_data->about_diligence_monthly == 'yes'){
        if($this->checkMonthlyDiligence($logged_info->member_srl, $config_data->diligence_monthly-1, null) == 1){
            $obj->today_point += $config_data->diligence_monthly_point;
                }
            }
    if($config_data->about_diligence_weekly == 'yes'){
        if($this->checkWeeklyDiligence($logged_info->member_srl, $config_data->diligence_weekly-1, null) == 1){
            $obj->today_point += $config_data->diligence_weekly_point;
                }
            }

    if(!$logged_info->member_srl){ return ;}

    $oModule = &getModel('module');
    $module_info = $oModule->getModuleInfoByMid('attendance');
    if(!$module_info->module_srl){
        return new Object(-1,'attend_no_board');
            }

    // document module의 model 객체 생성
    $oDocumentModel = &getModel('document');

    // document module의 controller 객체 생성
    $oDocumentController = &getController('document');

    if(strlen($obj->greetings) > 0 && $obj->greetings!='^auto^'){
        /*Document module connection : greetings process*/
        $d_obj->content = $obj->greetings;
        $d_obj->nick_name = $logged_info->nick_name;
        $d_obj->email_address = $logged_info->email_address;
        $d_obj->homepage = $logged_info->homepage;
        $d_obj->is_notice = 'N';
        $d_obj->module_srl = $module_info->module_srl;
        $d_obj->allow_comment = 'Y';

        $output = $oDocumentController->insertDocument($d_obj, false);
        if(!$output->get('document_srl')){
            return new Object(-1,'attend_error_no_greetings');
                      }
        $obj->greetings = "#".$output->get('document_srl');
           }


    /*접속자의 ip주소 기록*/
    $obj->ipaddress = $_SERVER['REMOTE_ADDR'];

    $obj->attendance_srl = getNextSequence();
    $obj->regdate = zDate(date("YmdHis"),"YmdHis");
    /*Query 실행 : 출석부 기록*/
    executeQuery("attendance.insertAttendance", $obj);
    $_SESSION['is_attended'] = $today;

			/*포인트 추가*/
    if($obj->today_point != 0 && $logged_info->member_srl){
        $oPointController->setPoint($logged_info->member_srl,$obj->today_point,'add');
            }
			
			/*attendance_total 테이블에 총 출석내용 및 연속출석데이터 기록(2009.02.15)*/
			if($this->isExistTotal($logged_info->member_srl) == 0){
				/*총 출석횟수 계산*/
				$total_attendance = $this->getTotalAttendance($logged_info->member_srl);
				/*총 출석 기록*/
				$this->insertTotal($logged_info->member_srl, $continuity, $total_attendance, $obj->today_point, null);
			}else{
				/*총 출석횟수 계산*/
				$total_attendance = $this->getTotalAttendance($logged_info->member_srl);
				/*총 출석포인트 받아오기*/
				$total_point = $this->getTotalPoint($logged_info->member_srl);
				$total_point += $obj->today_point;
				/*총 출석 기록*/
				$this->updateTotal($logged_info->member_srl, $continuity, $total_attendance, $total_point, null);
			}

			/*attendace_yearly 테이블에 연간 출석 데이터 기록(2009.02.15)*/
			if($this->isExistYearly($logged_info->member_srl, $year) == 0){	//연간 데이터가 없으면
				/*올 해 출석횟수 계산*/
				$yearly_data = $this->getYearlyData($year, $logged_info->member_srl);
				/*출석 포인트는 초기화(올 해 처음이므로)*/
				$yearly_point = $obj->today_point;
				/*연간출석데이터 추가*/
				$this->insertYearly($logged_info->member_srl, $yearly_data, $yearly_point, null);
			}else{
				/*올 해 출석횟수 계산*/
				$yearly_data = $this->getYearlyData($year, $logged_info->member_srl);
				/*출석 포인트는 예전 자료 꺼내기*/
				$year_info = $this->getYearlyAttendance($logged_info->member_srl, $year);
				$yearly_point = $year_info->yearly_point;
				$yearly_point += $obj->today_point;
				/*연간출석데이터 업데이트*/
				$this->updateYearly($logged_info->member_srl, $year, $yearly_data, $yearly_point,null);
			}

			/*attendance_monthly 테이블에 월간 출석 데이터 기록(2009.02.15)*/
			if($this->isExistMonthly($logged_info->member_srl, $year_month) == 0){	//월간 데이터가 없으면
				/*이달 출석횟수 계산*/
				$monthly_data = $this->getMonthlyData($year_month, $logged_info->member_srl);
				/*출석 포인트는 초기화(이달 처음이므로)*/
				$monthly_point = $obj->today_point;
				/*월간출석데이터 추가*/
				$this->insertMonthly($logged_info->member_srl, $monthly_data, $monthly_point, null);
			}else{
				/*이달 출석횟수 계산*/
				$monthly_data = $this->getMonthlyData($year_month, $logged_info->member_srl);
				/*출석 포인트는 예전 자료 꺼내기*/
				$month_info = $this->getMonthlyAttendance($logged_info->member_srl, $year_month);
				$monthly_point = $month_info->monthly_point;
				$monthly_point += $obj->today_point;
				/*월간출석데이터 업데이트*/
				$this->updateMonthly($logged_info->member_srl, $year_month, $monthly_data, $monthly_point, null);
			}

			/*attendance_weekly 테이블에 주간 출석 데이터 기록(2009.02.15)*/
			$week = $this->getWeek($today);
			if($this->isExistWeekly($logged_info->member_srl, $week) == 0 ){	//주간 데이터가 없으면
				/*이번 주 출석 횟수 계산*/
				$weekly_data = $this->getWeeklyAttendance($logged_info->member_srl, $week);
				/*출석 포인트는 오늘 받은 포인트로 초기화*/
				$weekly_point = $obj->today_point;
				/*주간 출석데이터 추가*/
				$this->insertWeekly($logged_info->member_srl, $weekly_data, $weekly_point, null);
			}else{
				/*이번 주 출석 횟수 계산*/
				$weekly_data = $this->getWeeklyAttendance($logged_info->member_srl, $week);
				/*출석 포인트는 예전자료 꺼내기*/
				$week_info = $this->getWeeklyData($logged_info->member_srl, $week);
				$weekly_point = $week_info->weekly_point;
				$weekly_point += $obj->today_point;
				/*주간 출석데이터 업데이트*/
				$this->updateWeekly($logged_info->member_srl, $week, $weekly_data, $weekly_point, null);	
			}
		}
	}

    /**
    * @brief 오늘 내 등수 체크
    **/
	function getPositionData($today, $greetings=null){
		$args->today = $today;
        if($greetings){
            $args->greetings = $greetings;
        } else {
            $args->greetings = '^admin_checked^';
        }
		$output = executeQuery('attendance.getPositionData', $args);
		return (int)$output->data->count;
	}

    /**
    * @brief 개근 여부 파악
    **/
	function isPerfect($member_srl, $today, $real=true){
		$current_month = substr($today,4,2);
		$current_year = substr($today,0,4);
		$end_of_month = date('t', mktime(0,0,0,$current_month,1,$current_year));
		$current_day = substr($today,6,2);
		if(date('t', mktime(0,0,0,02,1,$current_year))==29)
			$end_of_year = 366;
		else
			$end_of_year = 365;

		$ym = sprintf("%s%s",$current_year,$current_month);
		$is_perfect_m = $this->getMonthlyData($ym,$member_srl);
		$is_perfect_y = $this->getYearlyData($current_year,$member_srl);

		if($real == true){	//리스트에서 보여줄 때
			if($is_perfect_m >= $end_of_month && $current_day==$end_of_month)
				$arg->monthly_perfect = 1;
			else
				$arg->monthly_perfect = 0;

			if($is_perfect_y >= $end_of_year && $current_day==$end_of_year)
				$arg->yearly_perfect = 1;
			else
				$arg->yearly_perfect = 0;
		}else{			//출첵시
			if($is_perfect_m >= $end_of_month-1 && $current_day==$end_of_month)
				$arg->monthly_perfect = 1;
			else
				$arg->monthly_perfect = 0;

			if($is_perfect_y >= $end_of_year-1 && $current_day==$end_of_year)
				$arg->yearly_perfect = 1;
			else
				$arg->yearly_perfect = 0;
		}

		return $arg;
	}


/*******************************************************
	attendance_total 테이블 관련 함수
********************************************************/
    /**
    * @brief 총 출석 내용이 존재하는지 검사
    **/
	function isExistTotal($member_srl){
		$arg->member_srl = $member_srl;
		$output = executeQuery('attendance.isExistTotal',$arg);
		return (int)$output->data->count;
	}
	
    /**
    * @brief  연속출석중인지 검사
    **/
	function isExistContinuity($member_srl, $yesterday){
		$arg->member_srl = $member_srl;
		$arg->yesterday = $yesterday;
		$output = executeQuery('attendance.isExistContinuity',$arg);
		return (int)$output->data->count;
	}

    /**
    * @brief 어제의 연속출석 데이터 받기
    **/
	function getContinuityData($member_srl, $yesterday){
		$arg->member_srl = $member_srl;
		$arg->yesterday = $yesterday;
		$output = executeQuery('attendance.getContinuityData',$arg);
		$continuity->data = (int)$output->data->continuity;
		$continuity->point = (int)$output->data->continuity_point;
		return $continuity;
	}

    /**
    * @brief attendance_total 테이블 기록
    **/
	function insertTotal($member_srl, $continuity, $total_attendance, $total_point, $regdate){
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
    **/
	function updateTotal($member_srl, $continuity, $total_attendance, $total_point, $regdate){
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
    **/
	function getTotalAttendance($member_srl){
		$args->member_srl=$member_srl;
		$output = executeQuery('attendance.getTotalAttendance', $args);
		return (int)$output->data->total_count;
	}

    /**
    * @brief 총 출석 포인트 추출
    **/
	function getTotalPoint($member_srl){
		$args->member_srl = $member_srl;
		$output = executeQuery('attendance.getTotalPoint', $args);
		return (int)$output->data->total_point;
	}


    /**
    * @brief 총 출석 테이블 데이터 전부 꺼내기
    **/
	function getTotalData($member_srl){
		$arg->member_srl = $member_srl;
		$output = executeQuery('attendance.getTotalData',$arg);
		$total_info->total = (int)$output->data->total;
		$total_info->total_point = (int)$output->data->total_point;
		$total_info->continuity_point = (int)$output->data->continuity_point;
		$total_info->continuity = (int)$output->data->continuity;
		$total_info->regdate = $output->data->regdate;
		return $total_info;
	}

/*******************************************************
	attendance_yearly 테이블 관련 함수
********************************************************/
    /**
    * @brief 연간 출석 데이터가 있는지 확인
    **/
	function isExistYearly($member_srl, $year){
		$arg->member_srl = $member_srl;
		$arg->year = $year;
		$output = executeQuery('attendance.isExistYearly',$arg);
		return (int)$output->data->count;
	}
    /**
    * @brief 회원별 연간 출석통계 자료 생성
    **/
	function insertYearly($member_srl, $yearly, $yearly_point, $regdate){
		$arg->member_srl = $member_srl;
		$arg->yearly = $yearly;
		$arg->yearly_point = $yearly_point;
		if($regdate){$arg->regdate = $regdate;}
		executeQuery("attendance.insertYearly", $arg);
	}

    /**
    * @brief 회원별 연간 출석통계 자료 Update
    **/
	function updateYearly($member_srl, $year, $yearly, $yearly_point, $regdate){
		$arg->member_srl = $member_srl;
		$arg->yearly = $yearly;
		$arg->yearly_point = $yearly_point;
		$arg->year = $year;
		if($regdate){$arg->regdate = $regdate;}
		executeQuery("attendance.updateYearly", $arg);
	}

    /**
    * @brief 연간자료 꺼내기
    **/
	function getYearlyAttendance($member_srl, $year){
		$arg->member_srl = $member_srl;
		$arg->year = $year;
		$output = executeQuery('attendance.getYearlyAttendance',$arg);
		$year_data->yearly = (int)$output->data->yearly;
		$year_data->yearly_point = (int)$output->data->yearly_point;
		return $year_data;
	}


/*******************************************************
	attendance_monthly 테이블 관련 함수
********************************************************/

    /**
    * @brief 월간 출석 데이터가 있는지 확인
    **/
	function isExistMonthly($member_srl, $year_month){
		$arg->member_srl = $member_srl;
		$arg->month = $year_month;
		$output = executeQuery('attendance.isExistMonthly',$arg);
		return (int)$output->data->count;
	}

    /**
    * @brief 회원별 월간 출석통계 자료 생성
    **/
	function insertMonthly($member_srl, $monthly, $monthly_point, $regdate){
		$arg->member_srl = $member_srl;
		$arg->monthly = $monthly;
		$arg->monthly_point = $monthly_point;
		if($regdate){$arg->regdate = $regdate;}
		executeQuery("attendance.insertMonthly", $arg);
	}

    /**
    * @brief 회원별 월간 출석통계 자료 Update
    **/
	function updateMonthly($member_srl, $year_month, $monthly, $monthly_point, $regdate){
		$arg->member_srl = $member_srl;
		$arg->monthly = $monthly;
		$arg->monthly_point = $monthly_point;
		$arg->month = $year_month;
		if($regdate){$arg->regdate = $regdate;}
		executeQuery("attendance.updateMonthly", $arg);
	}

    /**
    * @brief 월간자료 꺼내기
    **/
	function getMonthlyAttendance($member_srl, $year_month){
		$arg->member_srl = $member_srl;
		$arg->month = $year_month;
		$output = executeQuery('attendance.getMonthlyAttendance',$arg);
		$month_data->monthly = (int)$output->data->monthly;
		$month_data->monthly_point = (int)$output->data->monthly_point;
		return $month_data;
	}


/*******************************************************
	attendance_weekly 테이블 관련 함수
********************************************************/
    /**
    * @brief 선택된 날짜의 기간 찾기
    **/
	function getWeek($today){   //today의 값이 xe설정시각으로 변형되어있을것이므로 여기에선 zDate()사용 안함.
        if(!$today){ return 0; }
    $week->sunday = date('Ymd', strtotime('SUNDAY', strtotime($today)))."235959";
    $week->monday = date('Ymd', strtotime('last MONDAY', strtotime($week->sunday)))."000000";
    return $week;
	}

    /**
    * @brief 주간 통계기록이 있는지 확인
    **/
	function isExistWeekly($member_srl, $week){
		$arg->member_srl = $member_srl;
		$arg->monday = $week->monday;
		$arg->sunday = $week->sunday;
		$output = executeQuery('attendance.isExistWeekly',$arg);
		return (int)$output->data->count;
	}

    /**
    * @brief 일주일 출석 횟수 알아오기
    **/
	function getWeeklyAttendance($member_srl, $week){
		$arg->member_srl = $member_srl;
		$arg->sunday = $week->sunday;
		$arg->monday = $week->monday;
		$output = executeQuery('attendance.getWeeklyAttendance',$arg);
		return (int)$output->data->weekly_count;
	}

    /**
    * @brief 주간 출석정보 입력
    **/
	function insertWeekly($member_srl, $weekly, $weekly_point, $regdate){
		$arg->member_srl = $member_srl;
		$arg->weekly = $weekly;
		$arg->weekly_point = $weekly_point;
		if($regdate){$arg->regdate = $regdate;}
		executeQuery("attendance.insertWeekly", $arg);
	}

    /**
    * @brief 주간 출석정보 Update
    **/
	function updateWeekly($member_srl, $week, $weekly, $weekly_point, $regdate){
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
    **/
	function getWeeklyData($member_srl, $week){
		$arg->member_srl = $member_srl;
		$arg->sunday = $week->sunday;
		$arg->monday = $week->monday;
		$output = executeQuery('attendance.getWeeklyData',$arg);
		$week_data->weekly = (int)$output->data->weekly;
		$week_data->weekly_point = (int)$output->data->weekly_point;
		return $week_data;
	}


/*****************************************************************************/

    /**
    * @brief 출석 가능시간대 인지 확인
    **/
    function availableCheck($config_data){
        if($config_data->about_time_control == 'yes'){
            $start->hour = substr($config_data->start_time,0,2);
            $start->min = substr($config_data->start_time,2,2);
            $end->hour = substr($config_data->end_time,0,2);
            $end->min = substr($config_data->end_time,2,2);
            $now->hour = zDate(date('YmdHis'),"H");
            $now->min = zDate(date('YmdHis'),"i");
            if(mktime($now->hour,$now->min,0,0,0) >= mktime($start->hour,$start->min,0,0,0) 
                && mktime($now->hour,$now->min,0,0,0) < mktime($end->hour,$end->min,0,0,0)){
                return 1;   //금지시간대일 경우
            }
            return 0;
        }
        return 0;   //금지시간이 아닐 경우
    }

    /**
    * @brief 연간 정근 여부 확인
    **/
    function checkYearlyDiligence($member_srl, $diligence_yearly, $year){
        if(!$year){
            $year = zDate(date('YmdHis'),"Y");
        }
        $year_data = $this->getYearlyData($year, $member_srl);
        if($year_data){
            if($year_data == $diligence_yearly){
                return 1;
            }
        }
        return 0;
    }

    /**
    * @brief 월간 정근 여부 확인
    **/
    function checkMonthlyDiligence($member_srl, $diligence_monthly, $year_month){
        if(!$year_month){
            $year_month = zDate(date('YmdHis'),"Ym");
        }
        $month_data  =$this->getMonthlyData($year_month, $member_srl);
        if($month_data){
            if($month_data == $diligence_monthly){
                return 1;
            }
        }
        return 0;
    }

    /**
    * @brief 주간 정근 여부 확인
    **/
    function checkWeeklyDiligence($member_srl, $diligence_weekly, $today){
        if(!$today){
            $week = $this->getWeek(zDate(date('YmdHis'),"Ymd")); 
        } else{
            $week = $this->getWeek($today);
        }
        $week_data = $this->getWeeklyData($member_srl, $week);
        if($week_data->weekly){
            if($week_data->weekly == $diligence_weekly){
                return 1;
            } 
        } else if($diligence_weekly == 0){
            return 1;
        }
        return 0;
    }

    /**
    * @brief member_srl로 인사말만 모두 뽑기
    **/
    function getGreetingsList($member_srl){
        $arg->member_srl = $member_srl;
        $output = executeQueryArray('attendance.getGreetingsList',$arg);
        if(!$output->data) $output->data = array();
        return $output;
    }

}
?>
