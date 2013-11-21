<?PHP
/**
* @class 출석부 모듈의 attendance 클래스
* @author 매실茶 (pppljh@gmail.com)
* @출석부 모듈의 기본적인 작업을 담당
*
* 설치/업데이트에 관한 작업내용을 기록합니다.
**/

class attendance extends ModuleObject 
{

    /**
    * @brief 모듈 설치
    **/
	function moduleInstall() {
		//moduleController 등록
		$oModuleController = &getController('module');
		$oModuleController->insertActionForward('attendance', 'view', 'dispAdminAttendanceList');
        $oModuleController->insertActionForward('attendance', 'view', 'dispAttendanceAdminBoardConfig');
		$oModuleController->insertActionForward('attendance', 'view', 'dispAttendancePersonalInfo');
		$oModuleController->insertActionForward('attendance', 'controller', 'procAttendanceInsertConfig');
		$oModuleController->insertActionForward('attendance', 'controller', 'procAttendanceDeleteData');
		$oModuleController->insertActionForward('attendance', 'controller', 'procAttendanceCheckData');

        //설정값이 하나도 없을 경우
		$output = executeQuery('attendance.isExistConfig',$arg);
        if((int)$output->data->count == 0){
            executeQuery("attendance.insertConfig", $obj);
            $config->about_admin_check = 'yes';
            $config->allow_duplicaton_ip_count = '3';
            $oModuleController = &getController('module');
            $oModuleController->insertModuleConfig('attendance', $config);
        }

        $oModule = &getModel('module');
        $module_info = $oModule->getModuleInfoByMid('attendance');
        if($module_info->module_srl){
            //이미 만들어진 attendance mid가 있다면
            if($module_info->module != 'attendance'){
                return new Object(1,'attend_error_mid');
            }
        }else{
            /*Create mid*/
            $oModuleController = &getController('module');
            $args->mid = 'attendance';
            $args->module = 'attendance';
            $args->browser_title = '출석게시판';
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

		// attendance_config 테이블에 about_time_control 필드 추가 (2009.04.11)
		$act = $oDB->isColumnExists("attendance_config", "about_time_control");
		if(!$act) return true;

		// attendance_config 테이블에 start_time 필드 추가 (2009.04.11)
		$act = $oDB->isColumnExists("attendance_config", "start_time");
		if(!$act) return true;

		// attendance_config 테이블에 end_time 필드 추가 (2009.04.11)
		$act = $oDB->isColumnExists("attendance_config", "end_time");
		if(!$act) return true;

		// attendance_config 테이블에 about_diligence_yearly 필드 추가 (2009.04.14)
		$act = $oDB->isColumnExists("attendance_config", "about_diligence_yearly");
		if(!$act) return true;

		// attendance_config 테이블에 diligence_yearly 필드 추가 (2009.04.14)
		$act = $oDB->isColumnExists("attendance_config", "diligence_yearly");
		if(!$act) return true;

		// attendance_config 테이블에 diligence_yearly_point 필드 추가 (2009.04.14)
		$act = $oDB->isColumnExists("attendance_config", "diligence_yearly_point");
		if(!$act) return true;

		// attendance_config 테이블에 about_diligence_monthly 필드 추가 (2009.04.14)
		$act = $oDB->isColumnExists("attendance_config", "about_diligence_monthly");
		if(!$act) return true;

		// attendance_config 테이블에 diligence_monthly 필드 추가 (2009.04.14)
		$act = $oDB->isColumnExists("attendance_config", "diligence_monthly");
		if(!$act) return true;

		// attendance_config 테이블에 diligence_monthly_point 필드 추가 (2009.04.14)
		$act = $oDB->isColumnExists("attendance_config", "diligence_monthly_point");
		if(!$act) return true;

		// attendance_config 테이블에 about_diligence_weekly 필드 추가 (2009.04.14)
		$act = $oDB->isColumnExists("attendance_config", "about_diligence_weekly");
		if(!$act) return true;

		// attendance_config 테이블에 diligence_weekly 필드 추가 (2009.04.14)
		$act = $oDB->isColumnExists("attendance_config", "diligence_weekly");
		if(!$act) return true;

		// attendance_config 테이블에 diligence_weekly 필드 추가 (2009.04.14)
		$act = $oDB->isColumnExists("attendance_config", "diligence_weekly_point");
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

        //check a mid attendance
        $oModule = &getModel('module');
        $module_info = $oModule->getModuleInfoByMid('attendance');
        //attendance 이름의 mid가 있는지 
        if($module_info->module != 'attendance'){
            return true;
        }
        if(!$module_info->module_srl) return true;

        //모듈 설정 확인
        $oModule = &getModel('module');
        $config = $oModule->getModuleConfig('attendance');
        if(!$config->allow_duplicaton_ip_count) return true;
        if(!$config->about_admin_check) return true;

        //회원탈퇴시 출석정보도 같이 제거하는 trigger 추가
        $oModuleModel = &getModel('module');
        if(!$oModuleModel->getTrigger('member.deleteMember', 'attendance', 'controller', 'triggerDeleteMember', 'after')) return true;
        
        //When a member is do login, 
        $oModuleModel = &getModel('module');
        if(!$oModuleModel->getTrigger('member.doLogin', 'attendance', 'controller', 'triggerAutoAttend', 'after')) return true;

		return false;
	}

    /**
    * @brief 모듈 업데이트
    **/
    function moduleUpdate() {
        $oDB = &DB::getInstance();
        //attendance 테이블에 greetings 필드 추가
        if (!$oDB->isColumnExists("attendance", "greetings")) {
	        $oDB->addColumn("attendance", "greetings", "varchar", 20);
        }

		//attendance 테이블에 today_point 필드 추가
		if(!$oDB->isColumnExists("attendance","today_point")){
			$oDB->addColumn("attendance", "today_point", "number", 20);
		}

		// attendance_config 테이블에 about_time_control 필드 추가 (2009.04.11)
		if(!$oDB->isColumnExists("attendance_config", "about_time_control")){
            $oDB->addColumn("attendance_config", "about_time_control", "varchar", 5);
        }

		// attendance_config 테이블에 start_time 필드 추가 (2009.04.11)
		if(!$oDB->isColumnExists("attendance_config", "start_time")){
            $oDB->addColumn("attendance_config", "start_time", "varchar", 4);
        }

		// attendance_config 테이블에 end_time 필드 추가 (2009.04.11)
		if(!$oDB->isColumnExists("attendance_config", "end_time")){
            $oDB->addColumn("attendance_config", "end_time", "varchar", 4);
        }

		// attendance_config 테이블에 about_diligence_yearly 필드 추가 (2009.04.14)
		if(!$oDB->isColumnExists("attendance_config", "about_diligence_yearly")){
            $oDB->addColumn("attendance_config", "about_diligence_yearly", "varchar", 5);
        }

		// attendance_config 테이블에 diligence_yearly 필드 추가 (2009.04.14)
		if(!$oDB->isColumnExists("attendance_config", "diligence_yearly")){
            $oDB->addColumn("attendance_config", "diligence_yearly", "number",11);
        }

		// attendance_config 테이블에 diligence_yearly_point 필드 추가 (2009.04.14)
		if(!$oDB->isColumnExists("attendance_config", "diligence_yearly_point")){
            $oDB->addColumn("attendance_config", "diligence_yearly_point", "number",11);
        }

		// attendance_config 테이블에 about_diligence_monthly 필드 추가 (2009.04.14)
		if(!$oDB->isColumnExists("attendance_config", "about_diligence_monthly")){
            $oDB->addColumn("attendance_config", "about_diligence_monthly", "varchar", 5);
        }

		// attendance_config 테이블에 diligence_monthly 필드 추가 (2009.04.14)
		if(!$oDB->isColumnExists("attendance_config", "diligence_monthly")){
            $oDB->addColumn("attendance_config", "diligence_monthly", "number",11);
        }

		// attendance_config 테이블에 diligence_monthly_point 필드 추가 (2009.04.14)
		if(!$oDB->isColumnExists("attendance_config", "diligence_monthly_point")){
            $oDB->addColumn("attendance_config", "diligence_monthly_point", "number",11);
        }

		// attendance_config 테이블에 about_diligence_weekly 필드 추가 (2009.04.14)
		if(!$oDB->isColumnExists("attendance_config", "about_diligence_weekly")){
            $oDB->addColumn("attendance_config", "about_diligence_weekly", "varchar", 5);
        }

		// attendance_config 테이블에 diligence_weekly 필드 추가 (2009.04.14)
		if(!$oDB->isColumnExists("attendance_config", "diligence_weekly")){
            $oDB->addColumn("attendance_config", "diligence_weekly", "number",11);
        }

		// attendance_config 테이블에 diligence_weekly_point 필드 추가 (2009.04.14)
		if(!$oDB->isColumnExists("attendance_config", "diligence_weekly_point")){
            $oDB->addColumn("attendance_config", "diligence_weekly_point", "number",11);
        }

        //attendance 테이블에 ipaddress 필드 추가
        if (!$oDB->isColumnExists("attendance", "ipaddress")) {
	        $oDB->addColumn("attendance", "ipaddress", "varchar", 23);
        }

        //attendance 테이블에 member_srl 필드 추가
        if (!$oDB->isColumnExists("attendance", "member_srl")) {
	        $oDB->addColumn("attendance", "member_srl", "number", 11);
            //attendance Table에서 user_id를 바탕으로 member_srl 재기록
            $oMemberModel = &getModel('member');
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
        if (!$oDB->isColumnExists("attendance_total", "member_srl")) {
	        $oDB->addColumn("attendance_total", "member_srl", "number", 11);
            //attendance_total Table에서 user_id를 바탕으로 member_srl 재기록
            $oMemberModel = &getModel('member');
            $user_ids = executeQueryArray('attendance.migrationGetIdAttendanceTotal');
            if(!$user_ids->data) $user_ids->data = array();
            foreach($user_ids->data as $value){
                $member = $oMemberModel->getMemberInfoByUserId($value->user_id);
                if($member->member_srl){
                    $args->member_srl = $member->member_srl;
                    $args->user_id = $member->user_id;
                    executeQuery("attendance.migrationInsertMemberSrlAttendanceTotal", $args);
                }
            }
        }

        //attendance_weekly 테이블에 member_srl 필드 추가
        if (!$oDB->isColumnExists("attendance_weekly", "member_srl")) {
	        $oDB->addColumn("attendance_weekly", "member_srl", "number", 11);
            //attendance_weekly Table에서 user_id를 바탕으로 member_srl 재기록
            $oMemberModel = &getModel('member');
            $user_ids = executeQueryArray('attendance.migrationGetIdAttendanceWeekly');
            if(!$user_ids->data) $user_ids->data = array();
            foreach($user_ids->data as $value){
                $member = $oMemberModel->getMemberInfoByUserId($value->user_id);
                if($member->member_srl){
                    $args->member_srl = $member->member_srl;
                    $args->user_id = $member->user_id;
                    executeQuery("attendance.migrationInsertMemberSrlAttendanceWeekly", $args);
                }
            }
        }

        //attendance_monthly 테이블에 member_srl 필드 추가
        if (!$oDB->isColumnExists("attendance_monthly", "member_srl")) {
	        $oDB->addColumn("attendance_monthly", "member_srl", "number", 11);
            //attendance_monthly Table에서 user_id를 바탕으로 member_srl 재기록
            $oMemberModel = &getModel('member');
            $user_ids = executeQueryArray('attendance.migrationGetIdAttendanceMonthly');
            if(!$user_ids->data) $user_ids->data = array();
            foreach($user_ids->data as $value){
                $member = $oMemberModel->getMemberInfoByUserId($value->user_id);
                if($member->member_srl){
                    $args->member_srl = $member->member_srl;
                    $args->user_id = $member->user_id;
                    executeQuery("attendance.migrationInsertMemberSrlAttendanceMonthly", $args);
                }
            }
        }

        //attendance_yearly 테이블에 member_srl 필드 추가
        if (!$oDB->isColumnExists("attendance_yearly", "member_srl")) {
	        $oDB->addColumn("attendance_yearly", "member_srl", "number", 11);
            //attendance_yearly Table에서 user_id를 바탕으로 member_srl 재기록
            $oMemberModel = &getModel('member');
            $user_ids = executeQueryArray('attendance.migrationGetIdAttendanceYearly');
            if(!$user_ids->data) $user_ids->data = array();
            foreach($user_ids->data as $value){
                $member = $oMemberModel->getMemberInfoByUserId($value->user_id);
                if($member->member_srl){
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

        //check a mid attendance
        $oModule = &getModel('module');
        $module_info = $oModule->getModuleInfoByMid('attendance');

        if(!$module_info->module_srl){
            /*Create mid*/
            $oModuleController = &getController('module');
            $args->mid = 'attendance';
            $args->module = 'attendance';
            $args->browser_title = '출석게시판';
            $args->site_srl = 0;
            $args->skin = 'default';
            $args->order_type = 'desc';
            $output = $oModuleController->insertModule($args);
        } else{
            if($module_info->module != 'attendance'){
                return new Object(1,'attend_error_mid');
            }
        }

        //설정 등록(중복ip 횟수)
        $oModule = &getModel('module');
        $config = $oModule->getModuleConfig('attendance');
        if(!$config->allow_duplicaton_ip_count){
            $oModuleController = &getController('module');
            $config->allow_duplicaton_ip_count = 3;
            $oModuleController->insertModuleConfig('attendance', $config);
        }

        $oModule = &getModel('module');
        $config = $oModule->getModuleConfig('attendance');
        if(!$config->about_admin_check){
            $oModuleController = &getController('module');
            $config->about_admin_check = 'yes';
            $oModuleController->insertModuleConfig('attendance', $config);
        }

        //회원탈퇴시 출석정보도 같이 제거하는 trigger 추가
        $oModuleModel = &getModel('module');
        $oModuleController = &getController('module');
        if(!$oModuleModel->getTrigger('member.deleteMember', 'attendance', 'controller', 'triggerDeleteMember', 'after')){
            $oModuleController->insertTrigger('member.deleteMember', 'attendance', 'controller', 'triggerDeleteMember', 'after');
        }
        
        //Add a Auto Login trigger
        $oModuleModel = &getModel('module');
        $oModuleController = &getController('module');
        if(!$oModuleModel->getTrigger('member.doLogin', 'attendance', 'controller', 'triggerAutoAttend', 'after')){
            $oModuleController->insertTrigger('member.doLogin', 'attendance', 'controller', 'triggerAutoAttend', 'after');
        }

		return new Object(0,'success_updated');
    }

    /**
    * @brief 캐쉬파일 재생성
    **/
    function recompileCache() {
    }
}
?>
