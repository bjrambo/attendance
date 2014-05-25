<?PHP
/**
* @class 출석부 모듈의 attendance 클래스
* @author BJRambo (sosifam@070805.co.kr)
* @출석부 모듈의 기본적인 작업을 담당
*
* 설치/업데이트에 관한 작업내용을 기록합니다.
**/

class attendance extends ModuleObject 
{

    /**
    * @brief 모듈 설치
    **/
	function moduleInstall()
	{
		//moduleController 등록
		$oModuleController = getController('module');
		$oModuleController->insertActionForward('attendance', 'view', 'dispAttendanceAdminList');
		$oModuleController->insertActionForward('attendance', 'view', 'dispAttendanceAdminBoardConfig');
		$oModuleController->insertActionForward('attendance', 'view', 'dispAttendancePersonalInfo');
		$oModuleController->insertActionForward('attendance', 'controller', 'procAttendanceInsertConfig');
		$oModuleController->insertActionForward('attendance', 'controller', 'procAttendanceDeleteData');
		$oModuleController->insertActionForward('attendance', 'controller', 'procAttendanceCheckData');

		$oModule = getModel('module');
		$module_info = $oModule->getModuleInfoByMid('attendance');
		if($module_info->module_srl)
		{
			//이미 만들어진 attendance mid가 있다면
			if($module_info->module != 'attendance')
			{
				return new Object(1,'attend_error_mid');
			}
		}
		else
		{
			/*Create mid*/
			$oModuleController = getController('module');
			$args = new stdClass;
			$args->mid = 'attendance';
			$args->module = 'attendance';
			$args->browser_title = '출석채크';
			$args->site_srl = 0;
			$args->skin = 'default';
			$args->order_type = 'desc';
			$output = $oModuleController->insertModule($args);
		}
	}

    /**
    * @brief 업데이트 할 만한 게 있는지 체크
    **/
	function checkUpdate()
	{
		$oDB = &DB::getInstance();
		// attendance 테이블에 greetings 필드 추가 (2009.02.04)
		$act = $oDB->isColumnExists("attendance", "greetings");
		if(!$act) return true;

		// attendance 테이블에 today_point 필드 추가 (2009.02.14)
		$act = $oDB->isColumnExists("attendance","today_point");
		if(!$act) return true;

		// attendance 테이블에 today_random 필드 추가 (2009.02.14)
		$act = $oDB->isColumnExists("attendance","today_random");
		if(!$act) return true;

		// attendance 테이블에 att_random_set 필드 추가 (2014.05.25)
		$act = $oDB->isColumnExists("attendance","att_random_set");
		if(!$act) return true;

		// attendance 테이블에 ipaddress 필드 추가 (2009.09.15)
		$act = $oDB->isColumnExists("attendance", "ipaddress");
		if(!$act) return true;

		// attendance 테이블에 member_srl 필드 추가 (2009.09.16)
		$act = $oDB->isColumnExists("attendance", "member_srl");
		if(!$act) return true;

		// attendance_total 테이블에 member_srl 필드 추가 (2009.09.16)
		$act = $oDB->isColumnExists("attendance_total", "member_srl");
		if(!$act) return true;

		// attendance_weekly 테이블에 member_srl 필드 추가 (2009.09.16)
		$act = $oDB->isColumnExists("attendance_weekly", "member_srl");
		if(!$act) return true;

		// attendance_monthly 테이블에 member_srl 필드 추가 (2009.09.16)
		$act = $oDB->isColumnExists("attendance_monthly", "member_srl");
		if(!$act) return true;

		// attendance_yearly 테이블에 member_srl 필드 추가 (2009.09.16)
		$act = $oDB->isColumnExists("attendance_yearly", "member_srl");
		if(!$act) return true;

		// attendance 테이블에 user_id 필드 제거 (2009.09.16)
		$act = $oDB->isColumnExists("attendance", "user_id");
		if($act) return true;
		// attendance_total 테이블에 user_id 필드 제거 (2009.09.16)
		$act = $oDB->isColumnExists("attendance_total", "user_id");
		if($act) return true;
		// attendance_weekly 테이블에 user_id 필드 제거 (2009.09.16)
		$act = $oDB->isColumnExists("attendance_weekly", "user_id");
		if($act) return true;
		// attendance_monthly 테이블에 user_id 필드 제거 (2009.09.16)
		$act = $oDB->isColumnExists("attendance_monthly", "user_id");
		if($act) return true;
		// attendance_yearly 테이블에 user_id 필드 제거 (2009.09.16)
		$act = $oDB->isColumnExists("attendance_yearly", "user_id");
		if($act) return true;

		if(!$oDB->isColumnExists("attendance", "perfect_m")) return true;

		//check a mid attendance
		$oModule = getModel('module');
		$module_info = $oModule->getModuleInfoByMid('attendance');
		//attendance 이름의 mid가 있는지 
		if($module_info->module != 'attendance')
		{
            return true;
        }
        if(!$module_info->module_srl) return true;

		//모듈 설정 확인
		$oModule = getModel('module');
		$config = $oModule->getModuleConfig('attendance');
		if(!$config) return TRUE;
		if(!$config->allow_duplicaton_ip_count) return true;
		if(!$config->about_admin_check) return true;
		if(!$config->about_birth_day) return true;
		if(!$config->about_birth_day_y) return true;
		if(!$config->about_time_control) return true;
		if(!$config->start_time) return true;
		if(!$config->end_time) return true;
		if(!$config->about_diligence_yearly) return true;
		if(!$config->diligence_yearly) return true;
		if(!$config->about_diligence_monthly) return true;
		if(!$config->diligence_monthly) return true;
		if(!$config->about_diligence_weekly) return true;
		if(!$config->diligence_weekly) return true;
		if(!$config->about_target) return true;
		if(!$config->target_day) return true;
		if(!$config->about_continuity) return true;
		if(!$config->about_random) return true;
		if(!$config->about_lottery) return true;

		//회원탈퇴시 출석정보도 같이 제거하는 trigger 추가
		$oModuleModel = getModel('module');
		if(!$oModuleModel->getTrigger('member.deleteMember', 'attendance', 'controller', 'triggerDeleteMember', 'after')) return true;

		//When a member is do login, 
		if(!$oModuleModel->getTrigger('member.doLogin', 'attendance', 'controller', 'triggerAutoAttend', 'after')) return true;

		if(!$oModuleModel->getTrigger('display', 'attendance', 'controller', 'triggerSou', 'before')) return true;

	}

	/**
	* @brief 모듈 업데이트
	**/
	function moduleUpdate() {
		$oDB = DB::getInstance();
		//attendance 테이블에 greetings 필드 추가
		if (!$oDB->isColumnExists("attendance", "greetings"))
		{
			$oDB->addColumn("attendance", "greetings", "varchar", 20);
		}

		//attendance 테이블에 today_point 필드 추가
		if(!$oDB->isColumnExists("attendance","today_point"))
		{
			$oDB->addColumn("attendance", "today_point", "number", 20);
		}

		//attendance 테이블에 today_random 필드 추가
		if(!$oDB->isColumnExists("attendance","today_random"))
		{
			$oDB->addColumn("attendance", "today_random", "number", 20);
		}
	
		//attendance 테이블에 ipaddress 필드 추가
		if (!$oDB->isColumnExists("attendance", "ipaddress"))
		{
			$oDB->addColumn("attendance", "ipaddress", "varchar", 23);
		}

		if(!$oDB->isColumnExists("attendance", "att_random_set"))
		{
			$oDB->addColumn("attendance", "att_random_set", "number", 20);
		}

		//attendance 테이블에 member_srl 필드 추가
		if (!$oDB->isColumnExists("attendance", "member_srl"))
		{
			$oDB->addColumn("attendance", "member_srl", "number", 11);
			//attendance Table에서 user_id를 바탕으로 member_srl 재기록
			$oMemberModel = getModel('member');
			$user_ids = executeQueryArray('attendance.migrationGetIdAttendance');
			if(!$user_ids->data) $user_ids->data = array();
			foreach($user_ids->data as $value){
				$member = $oMemberModel->getMemberInfoByUserId($value->user_id);
				if($member->member_srl){
					$args->member_srl = $member->member_srl;
					$args->user_id = $member->user_id;
					executeQuery("attendance.migrationInsertMemberSrlAttendance", $args);
				}
			}
		}

		//attendance_total 테이블에 member_srl 필드 추가
		if (!$oDB->isColumnExists("attendance_total", "member_srl"))
		{
			$oDB->addColumn("attendance_total", "member_srl", "number", 11);
			//attendance_total Table에서 user_id를 바탕으로 member_srl 재기록
			$oMemberModel = getModel('member');
			$user_ids = executeQueryArray('attendance.migrationGetIdAttendanceTotal');
			if(!$user_ids->data) $user_ids->data = array();
			foreach($user_ids->data as $value)
			{
				$member = $oMemberModel->getMemberInfoByUserId($value->user_id);
				if($member->member_srl)
				{
					$args = new stdClass;
					$args->member_srl = $member->member_srl;
					$args->user_id = $member->user_id;
					executeQuery("attendance.migrationInsertMemberSrlAttendanceTotal", $args);
				}
			}
		}

		//attendance_weekly 테이블에 member_srl 필드 추가
		if (!$oDB->isColumnExists("attendance_weekly", "member_srl"))
		{
			$oDB->addColumn("attendance_weekly", "member_srl", "number", 11);
			//attendance_weekly Table에서 user_id를 바탕으로 member_srl 재기록
			$oMemberModel = getModel('member');
			$user_ids = executeQueryArray('attendance.migrationGetIdAttendanceWeekly');
			if(!$user_ids->data) $user_ids->data = array();
			foreach($user_ids->data as $value)
			{
				$member = $oMemberModel->getMemberInfoByUserId($value->user_id);
				if($member->member_srl)
				{
					$args = new stdClass;
					$args->member_srl = $member->member_srl;
					$args->user_id = $member->user_id;
					executeQuery("attendance.migrationInsertMemberSrlAttendanceWeekly", $args);
				}
			}
		}

		//attendance_monthly 테이블에 member_srl 필드 추가
		if (!$oDB->isColumnExists("attendance_monthly", "member_srl"))
		{
			$oDB->addColumn("attendance_monthly", "member_srl", "number", 11);
			//attendance_monthly Table에서 user_id를 바탕으로 member_srl 재기록
			$oMemberModel = getModel('member');
			$user_ids = executeQueryArray('attendance.migrationGetIdAttendanceMonthly');
			if(!$user_ids->data) $user_ids->data = array();
			foreach($user_ids->data as $value)
			{
				$member = $oMemberModel->getMemberInfoByUserId($value->user_id);
				if($member->member_srl)
				{
					$args = new stdClass;
					$args->member_srl = $member->member_srl;
					$args->user_id = $member->user_id;
					executeQuery("attendance.migrationInsertMemberSrlAttendanceMonthly", $args);
				}
			}
		}

		//attendance_yearly 테이블에 member_srl 필드 추가
		if (!$oDB->isColumnExists("attendance_yearly", "member_srl"))
		{
			$oDB->addColumn("attendance_yearly", "member_srl", "number", 11);
			//attendance_yearly Table에서 user_id를 바탕으로 member_srl 재기록
			$oMemberModel = getModel('member');
			$user_ids = executeQueryArray('attendance.migrationGetIdAttendanceYearly');
			if(!$user_ids->data) $user_ids->data = array();
			foreach($user_ids->data as $value)
			{
				$member = $oMemberModel->getMemberInfoByUserId($value->user_id);
				if($member->member_srl)
				{
					$args = new stdClass;
					$args->member_srl = $member->member_srl;
					$args->user_id = $member->user_id;
					executeQuery("attendance.migrationInsertMemberSrlAttendanceYearly", $args);
				}
			}
		}

		//attendance 테이블에 user_id 필드 제거
		if ($oDB->isColumnExists("attendance", "user_id") /*&& $oDB->isColumnExists("attendance", "member_srl")*/) {
			$oDB->dropColumn("attendance", "user_id");
		}
		//attendance_total 테이블에 user_id 필드 제거
		if ($oDB->isColumnExists("attendance_total", "user_id")/* && $oDB->isColumnExists("attendance_total", "member_srl") */) {
			$oDB->dropColumn("attendance_total", "user_id");
		}
		//attendance_weekly 테이블에 user_id 필드 제거
		if ($oDB->isColumnExists("attendance_weekly", "user_id")/* && $oDB->isColumnExists("attendance_weekly", "member_srl")*/) {
			$oDB->dropColumn("attendance_weekly", "user_id");
		}
		//attendance_monthly 테이블에 user_id 필드 제거
		if ($oDB->isColumnExists("attendance_monthly", "user_id") /*&& $oDB->isColumnExists("attendance_monthly", "member_srl")*/) {
			$oDB->dropColumn("attendance_monthly", "user_id");
		}
		//attendance_yearly 테이블에 user_id 필드 제거
		if ($oDB->isColumnExists("attendance_yearly", "user_id") /*&& $oDB->isColumnExists("attendance_yearly", "member_srl")*/) {
			$oDB->dropColumn("attendance_yearly", "user_id");
		}

		if(!$oDB->isColumnExists("attendance", "perfect_m"))
		{
			$oDB->addColumn("attendance", "perfect_m", "char", 1);
		}

		//check a mid attendance
		$oModule = getModel('module');
		$module_info = $oModule->getModuleInfoByMid('attendance');

		if(!$module_info->module_srl)
		{
			/*Create mid*/
			$oModuleController = getController('module');
			$args = new stdClass;
			$args->mid = 'attendance';
			$args->module = 'attendance';
			$args->browser_title = '출석채크';
			$args->site_srl = 0;
			$args->skin = 'default';
			$args->order_type = 'desc';
			$output = $oModuleController->insertModule($args);
		}
		else
		{
			if($module_info->module != 'attendance')
			{
				return new Object(1,'attend_error_mid');
			}
		}

        //설정 등록(중복ip 횟수)
		$oModule = getModel('module');
		$config = $oModule->getModuleConfig('attendance');

		$output = executeQuery('attendance.getConfigData');
		$config_data = $output->data;

		if(!$config)
		{
			$config = new stdClass;
		}

		if(!$config->allow_duplicaton_ip_count)
		{
			$oModuleController = getController('module');
			$config->allow_duplicaton_ip_count = 3;
		}

		if(!$config->about_admin_check)
		{
			$oModuleController = getController('module');
			$config->about_admin_check = 'yes';
		}

		if(!$config->about_birth_day)
		{
			$oModuleController = getController('module');
			$config->about_birth_day = 'no';
		}

		if(!$config->about_birth_day_y)
		{
			$oModuleController = getController('module');
			$config->about_birth_day_y = 'no';
		}

		if(!$config->about_time_control)
		{
			$oModuleController = getController('module');
			if(!$config_data->about_time_control)
			{
				$config->about_time_control = 'no';
			}
			elseif($config_data->about_time_control)
			{
				$config->about_time_control = $config_data->about_time_control;
			}
		}
		
		if(!$config->start_time)
		{
			$oModuleController = getController('module');
			if(!$config_data->start_time)
			{
				$config->start_time = '0000';
			}
			elseif($config_data->start_time)
			{
				$config->start_time = $config_data->start_time;
			}
		}

		if(!$config->end_time)
		{
			$oModuleController = getController('module');
			if(!$config_data->end_time)
			{
				$config->end_time = '0000';
			}
			elseif($config_data->end_time)
			{
				$config->end_time = $config_data->end_time;
			}
		}

		if(!$config->about_diligence_yearly)
		{
			$oModuleController = getController('module');
			if(!$config_data->about_diligence_yearly)
			{
				$config->about_diligence_yearly = 'no';
			}
			elseif($config_data->about_diligence_yearly)
			{
				$config->about_diligence_yearly = $config_data->about_diligence_yearly;
			}
		}

		if(!$config->diligence_yearly)
		{
			$oModuleController = getController('module');
			if(!$config_data->diligence_yearly)
			{
				$config->diligence_yearly = '364';
			}
			elseif($config_data->diligence_yearly)
			{
				$config->diligence_yearly = $config_data->diligence_yearly;
			}
		}

		if(!$config->diligence_yearly_point)
		{
			$oModuleController = getController('module');
			if(!$config_data->diligence_yearly_point)
			{
				$config->diligence_yearly_point = '0';
			}
			elseif($config_data->diligence_yearly_point)
			{
				$config->diligence_yearly_point = $config_data->diligence_yearly_point;
			}
		}

		if(!$config->about_diligence_monthly)
		{
			$oModuleController = getController('module');
			if(!$config_data->about_diligence_monthly)
			{
				$config->about_diligence_monthly = 'no';
			}
			elseif($config_data->about_diligence_monthly)
			{
				$config->about_diligence_monthly = $config_data->about_diligence_monthly;
			}
		}

		if(!$config->diligence_monthly)
		{
			$oModuleController = getController('module');
			if(!$config_data->diligence_monthly)
			{
				$config->diligence_monthly = '25';
			}
			elseif($config_data->diligence_monthly)
			{
				$config->diligence_monthly = $config_data->diligence_monthly;
			}
		}

		if(!$config->diligence_monthly_point)
		{
			$oModuleController = getController('module');
			if(!$config_data->diligence_monthly_point)
			{
				$config->diligence_monthly_point = '0';
			}
			elseif($config_data->diligence_monthly_point)
			{
				$config->diligence_monthly_point = $config_data->diligence_monthly_point;
			}
		}

		if(!$config->about_diligence_weekly)
		{
			$oModuleController = getController('module');
			if(!$config_data->about_diligence_weekly)
			{
				$config->about_diligence_weekly = 'no';
			}
			elseif($config_data->about_diligence_weekly)
			{
				$config->about_diligence_weekly = $config_data->about_diligence_weekly;
			}
		}

		if(!$config->diligence_weekly)
		{
			$oModuleController = getController('module');
			if(!$config_data->diligence_weekly)
			{
				$config->diligence_weekly = '6';
			}
			elseif($config_data->diligence_weekly)
			{
				$config->diligence_weekly = $config_data->diligence_weekly;
			}
		}

		if(!$config->diligence_weekly_point)
		{
			$oModuleController = getController('module');
			if(!$config_data->diligence_weekly_point)
			{
				$config->diligence_weekly_point = '0';
			}
			elseif($config_data->diligence_weekly_point)
			{
				$config->diligence_weekly_point = $config_data->diligence_weekly_point;
			}
		}

		if(!$config->add_point)
		{
			$oModuleController = getController('module');
			if(!$config_data->add_point)
			{
				$config->add_point = '5';
			}
			elseif($config_data->add_point)
			{
				$config->add_point = $config_data->add_point;
			}
		}
		
		if(!$config->first_point)
		{
			$oModuleController = getController('module');
			if(!$config_data->first_point)
			{
				$config->first_point = '0';
			}
			elseif($config_data->first_point)
			{
				$config->first_point = $config_data->first_point;
			}
		}

		if(!$config->second_point)
		{
			$oModuleController = getController('module');
			if(!$config_data->second_point)
			{
				$config->second_point = '0';
			}
			elseif($config_data->second_point)
			{
				$config->second_point = $config_data->second_point;
			}
		}

		if(!$config->third_point)
		{
			$oModuleController = getController('module');
			if(!$config_data->third_point)
			{
				$config->third_point = '0';
			}
			elseif($config_data->third_point)
			{
				$config->third_point = $config_data->third_point;
			}
		}

		if(!$config->yearly_point)
		{
			$oModuleController = getController('module');
			if(!$config_data->yearly_point)
			{
				$config->yearly_point = '0';
			}
			elseif($config_data->yearly_point)
			{
				$config->yearly_point = $config_data->yearly_point;
			}
		}

		if(!$config->monthly_point)
		{
			$oModuleController = getController('module');
			if(!$config_data->monthly_point)
			{
				$config->monthly_point = '0';
			}
			elseif($config_data->monthly_point)
			{
				$config->monthly_point = $config_data->monthly_point;
			}
		}

		if(!$config->weekly_point)
		{
			$oModuleController = getController('module');
			if(!$config_data->weekly_point)
			{
				$config->weekly_point = '0';
			}
			elseif($config_data->weekly_point)
			{
				$config->weekly_point = $config_data->weekly_point;
			}
		}

		if(!$config->about_target)
		{
			$oModuleController = getController('module');
			if(!$config_data->about_target)
			{
				$config->about_target = 'no';
			}
			elseif($config_data->about_target)
			{
				$config->about_target = $config_data->about_target;
			}
		}

		if(!$config->target_day)
		{
			$oModuleController = getController('module');
			if(!$config_data->target_day)
			{
				$config->target_day = '00000000';
			}
			elseif($config_data->target_day)
			{
				$config->target_day = $config_data->target_day;
			}
		}

		if(!$config->target_point)
		{
			$oModuleController = getController('module');
			if(!$config_data->target_point)
			{
				$config->target_point = '0';
			}
			elseif($config_data->target_point)
			{
				$config->target_point = $config_data->target_point;
			}
		}

		if(!$config->about_continuity)
		{
			$oModuleController = getController('module');
			if(!$config_data->about_continuity)
			{
				$config->about_continuity = 'no';
			}
			elseif($config_data->about_continuity)
			{
				$config->about_continuity = $config_data->about_continuity;
			}
		}

		if(!$config->continuity_day)
		{
			$oModuleController = getController('module');
			if(!$config_data->continuity_day)
			{
				$config->continuity_day = '0';
			}
			elseif($config_data->continuity_day)
			{
				$config->continuity_day = $config_data->continuity_day;
			}
		}


		if(!$config->continuity_point)
		{
			$oModuleController = getController('module');
			if(!$config_data->continuity_point)
			{
				$config->continuity_point = '0';
			}
			elseif($config_data->continuity_point)
			{
				$config->continuity_point = $config_data->continuity_point;
			}
		}

		if(!$config->about_random)
		{
			$oModuleController = getController('module');
			if(!$config_data->about_random)
			{
				$config->about_random = 'no';
			}
			elseif($config_data->about_random)
			{
				$config->about_random = $config_data->about_random;
			}
		}

		if(!$config->minimum)
		{
			$oModuleController = getController('module');
			if(!$config_data->minimum)
			{
				$config->minimum = '0';
			}
			elseif($config_data->minimum)
			{
				$config->minimum = $config_data->minimum;
			}
		}

		if(!$config->maximum)
		{
			$oModuleController = getController('module');
			if(!$config_data->maximum)
			{
				$config->maximum = '0';
			}
			elseif($config_data->maximum)
			{
				$config->maximum = $config_data->maximum;
			}
		}

		if(!$config->about_lottery)
		{
			$oModuleController = getController('module');
			if(!$config_data->about_lottery)
			{
				$config->about_lottery = 'no';
			}
			elseif($config_data->about_lottery)
			{
				$config->about_lottery = $config_data->about_lottery;
			}
		}

		if(!$config->lottery)
		{
			$oModuleController = getController('module');
			if(!$config_data->lottery)
			{
				$config->lottery = '0';
			}
			elseif($config_data->lottery)
			{
				$config->lottery = $config_data->lottery;
			}
		}

		if(!$config->brithday_point)
		{
			$oModuleController = getController('module');
			if(!$config_data->brithday_point)
			{
				$config->brithday_point = '0';
			}
			elseif($config_data->brithday_point)
			{
				$config->brithday_point = $config_data->brithday_point;
			}
		}
		$oModuleController->insertModuleConfig('attendance', $config);



		//회원탈퇴시 출석정보도 같이 제거하는 trigger 추가
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		if(!$oModuleModel->getTrigger('member.deleteMember', 'attendance', 'controller', 'triggerDeleteMember', 'after')){
			$oModuleController->insertTrigger('member.deleteMember', 'attendance', 'controller', 'triggerDeleteMember', 'after');
		}

		//Add a Auto Login trigger
		if(!$oModuleModel->getTrigger('member.doLogin', 'attendance', 'controller', 'triggerAutoAttend', 'after')){
			$oModuleController->insertTrigger('member.doLogin', 'attendance', 'controller', 'triggerAutoAttend', 'after');
		}

		//displaySou 트리거 설치
		if(!$oModuleModel->getTrigger('display', 'attendance', 'controller', 'triggerSou', 'before')){
			$oModuleController->insertTrigger('display', 'attendance', 'controller', 'triggerSou', 'before');
		}

		return new Object(0,'success_updated');
	}

	/**
	 * @brief 캐쉬파일 재생성
	 **/
    function recompileCache()
	{
    }
}
?>
