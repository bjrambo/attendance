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

	private $triggers = array(
		array('member.deleteMember', 'attendance', 'controller', 'triggerDeleteMember', 'after'),
		array('member.doLogin', 'attendance', 'controller', 'triggerAutoAttend', 'after'),
		array('display', 'attendance', 'controller', 'triggerSou', 'before'),
		array('member.updateMember','attendance', 'controller', 'triggerUpdateMemberBefore', 'before')
	);

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

		// attendance 테이블에 a_continuity 필드 추가 (2014.08.24)
		$act = $oDB->isColumnExists("attendance","a_continuity");
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

		if(!$oDB->isColumnExists("attendance", "present_y")) return true;

		//check a mid attendance
		$oModule = getModel('module');
		$module_info = $oModule->getModuleInfoByMid('attendance');
		//attendance 이름의 mid가 있는지 
		if($module_info->module != 'attendance')
		{
            return true;
        }
        if(!$module_info->module_srl) return true;

		//회원탈퇴시 출석정보도 같이 제거하는 trigger 추가
		$oModuleModel = getModel('module');
		foreach ($this->triggers as $trigger)
		{
			if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) return TRUE;
		}
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

		if(!$oDB->isColumnExists("attendance", "a_continuity"))
		{
			$oDB->addColumn("attendance", "a_continuity", "number", 20);
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

		if(!$oDB->isColumnExists("attendance", "present_y"))
		{
			$oDB->addColumn("attendance", "present_y", "char", 1);
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
			$args->browser_title = '출석체크';
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

		//회원탈퇴시 출석정보도 같이 제거하는 trigger 추가
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		foreach ($this->triggers as $trigger)
		{
			if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
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
