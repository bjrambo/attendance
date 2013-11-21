<?PHP
/**
* @class 출석부 모듈의 attendanceAdminController 클래스
* @author 매실茶 (pppljh@gmail.com)
* @JS필터를 통해 요청된 작업 수행
*
* 출석부 기록, 관리자의 임의 출석내용 변경 시 호출되는 함수들이 있습니다.
**/

class attendanceAdminController extends attendance {

    /**
    * @brief 초기화
    **/
	function init(){
	}

    function procAdminAttendanceDeleteAllData(){
		/*attendance admin model 객체 생성*/
		$oAttendanceAdminModel = &getAdminModel('attendance');
        $obj=Context::getRequestVars();
        $oAttendanceAdminModel->deleteAllAttendanceData($obj->member_srl);
        $oAttendanceAdminModel->deleteAllAttendanceTotalData($obj->member_srl);
        $oAttendanceAdminModel->deleteAllAttendanceYearlyData($obj->member_srl);
        $oAttendanceAdminModel->deleteAllAttendanceMonthlyData($obj->member_srl);
        $oAttendanceAdminModel->deleteAllAttendanceWeeklyData($obj->member_srl);
        $this->setMessage('attend_deleted');
    }

    /**
     * @brief 출석 포인트 변경
     **/
    function procAttendanceAdminUpdatePoint() {
        $action = Context::get('action');
        $member_srl = Context::get('member_srl');
        $point = Context::get('point');
        if(!$point) $point=0;

        $oAttendanceModel = &getModel('attendance');
        $oPointController = &getController('point');
        $oPointModel = &getModel('point');

        //개인 포인트 꺼내오기
        $oMemberModel = &getModel('member');
        $member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
		$personal_point = $oPointModel->getPoint($member_info->member_srl);

        //총 출석 포인트 추출
        $total_point = $oAttendanceModel->getTotalPoint($member_srl);

        if($action=='add'){
            $total_point += $point;
            $personal_point += $point;
        }else if($action=='minus'){
            $total_point -= $point;
            $personal_point -= $point;
        } else{
            $total_point = $point;
            $personal_point = $point;
        }

        if($point == 0 ){
            $this->setMessage('attend_no_zero');
        } else {
            //총 출석포인트 기록
            $oAttendanceModel->updateTotal($member_srl, $continuity=null, $total_attendance=null, $total_point, $regdate=null);
            //개인포인트 기록
            $oPointController->setPoint($member_info->member_srl,$personal_point,'update');
            if($action=='update') $this->setMessage('attend_updated_points');
        }
    }


    /**
    * @brief 출석 정보 수정
    **/
    function procAttendanceAdminFixTotalData(){
        $oAttendanceModel = &getModel('attendance');
        $oAttendanceAdminModel = &getAdminModel('attendance');
        $obj = Context::getRequestVars();
        $continuity->point = 0;
        if(!$obj->continuity){
            $continuity->data=1; 
        } else {
            if($obj->continuity < 1) {
                $continuity->data = 1;
            } else {
                $continuity->data=$obj->continuity;
            }
        }
        $oAttendanceAdminModel->deleteAllAttendanceTotalData($obj->member_srl);
        $points = $oAttendanceAdminModel->getTotalPoint($obj->member_srl);
        $sum=0;
        foreach($points->data as $val){
            $sum+=$val->today_point;
        }
        $attendance = $oAttendanceModel->getTotalAttendance($obj->member_srl);
        $oAttendanceModel->insertTotal($obj->member_srl, $continuity, $attendance, $sum, $obj->selected_date.'000000');

        $oAttendanceAdminModel->fixYearMonthWeek($obj);
    }

    /**
    * @brief 중복출석 수정
    **/
    function procAttendanceAdminFixAttendanceData(){
        //모델 연동
        $oAttendanceModel = &getModel('attendance');
        $oAttendanceAdminModel = &getAdminModel('attendance');
		//포인트 모듈 연동
		$oPointModel = &getModel('point');
        $oPointController = &getController('point');
        $obj = Context::getRequestVars();
        $output = $oAttendanceAdminModel->getDuplicatedData($obj->member_srl, $obj->selected_date);
        $j = 1;
        $sum_of_point = 0;
        foreach($output->data as $val){
            if($j==1){
                $today_point = $val->today_point;
                $greetings = $val->greetings;
                $regdate = $val->regdate;
            }
            $sum_of_point+=$val->today_point;
            $j++;
        }
        $sum_of_point-=$today_point;
        //중복된 출석내용 제거
        $oAttendanceAdminModel->deleteDuplicatedData($obj->member_srl, $obj->selected_date);
        //비정상적으로 지급된 포인트 차감(포인트모듈 연동)
        $oMemberModel = &getModel('member');
        $member_info = $oMemberModel->getMemberInfoByMemberSrl($obj->member_srl);
        $my_point = $oPointModel->getPoint($member_info->member_srl);
        $my_point -=$today_point;
        $oPointController->setPoint($member_info->member_srl, $my_point, 'update');
        //주간 출석정보 수정
        $week = $oAttendanceModel->getWeek($obj->selected_date);
        $week_data = $oAttendanceModel->getWeeklyData($obj->member_srl, $week);
        $week_data->weekly = $oAttendanceModel->getWeeklyAttendance($obj->member_srl, $week) +1;
        $week_data->weekly_point = $week_data->weekly_point -$today_point;
        $oAttendanceModel->updateWeekly($obj->member_srl, $week, $week_data->weekly, $week_data->weekly_point, $regdate);
        //월간 출석정보 수정
        $year_month = substr($obj->selected_date,0,6);
        $monthly_data = $oAttendanceModel->getMonthlyAttendance($obj->member_srl, $year_month);
        $monthly_data->monthly = $oAttendanceModel->getMonthlyData($year_month,$obj->member_srl) +1;
        $monthly_data->monthly_point = $monthly_data->monthly_point - $today_point;
        $oAttendanceModel->updateMonthly($obj->member_srl, $year_month, $monthly_data->monthly, $monthly_data->monthly_point, $regdate);
        //연간 출석정보 수정
        $year = substr($obj->selected_date,0,4);
        $yearly_data = $oAttendanceModel->getYearlyAttendance($obj->member_srl, $year);
        $yearly_data->yearly = $oAttendanceModel->getYearlyData($year,$obj->member_srl) +1;
        $yearly_data->yearly_point = $yearly_data->yearly_point - $today_point;
        $oAttendanceModel->updateYearly($obj->member_srl, $year, $yearly_data->yearly, $yearly_data->yearly_point, $regdate);
        //총 출석정보 수정
        $total_data = $oAttendanceModel->getTotalData($obj->member_srl);
        $total_data->total = $oAttendanceModel->getTotalAttendance($obj->member_srl) +1;
        $total_data->total_point = $total_data->total_point - $today_point;
        $continuity->data = $total_data->continuity;
        $continuity->point = $total_data->continuity_point;
        $oAttendanceModel->updateTotal($obj->member_srl, $continuity, $total_data->total, $total_data->total_point, $total_data->regdate);
        //정상적인 출석정보 삽입
        $args->attendance_srl = getNextSequence();
        $args->regdate = $regdate;
        $args->member_srl = $obj->member_srl;
        $args->greetings = $greetings;
        $args->today_point = $today_point;
        executeQuery("attendance.insertAttendance", $args);
        $this->setMessage('attend_fixed_doublecheck');
    }

    /**
    * @brief 출석포인트 재계산
    **/
    function procAttendanceAdminInitAll(){
        $oAttendanceModel = &getModel('attendance');
        $oAttendanceAdminModel = &getAdminModel('attendance');
        $oPointController = &getController('point');
        $obj = Context::getRequestVars();
        $continuity->point = 0;
        if(!$obj->continuity){
            $continuity->data=1; 
        } else {
            if($obj->continuity < 1) {
                $continuity->data = 1;
            } else {
                $continuity->data=$obj->continuity;
            }
        }
        $output = executeQuery('attendance.migrationGetSrlTotal');
        if(!$output->data) $output->data = array();
        foreach($output->data as $value){
            $obj->member_srl = $value->member_srl;
            $points = $oAttendanceAdminModel->getTotalPoint($obj->member_srl);
            $sum=0;
            foreach($points->data as $val){
                $sum+=$val->today_point;
            }
            $oAttendanceAdminModel->deleteAllAttendanceTotalData($obj->member_srl);
            $attendance = $oAttendanceModel->getTotalAttendance($obj->member_srl);
            $oAttendanceModel->insertTotal($obj->member_srl, $continuity, $attendance, $sum, $obj->selected_date.'000000');

            //포인트 지급            
            $oPointController->setPoint($obj->member_srl, $sum, 'add');

            $oAttendanceAdminModel->fixYearMonthWeek($obj);
        }
    }

    /**
    * @brief 출석부 게시판 설정값 등록
    **/
    function procAttendanceAdminInsertBoard(){
        // module 모듈의 model/controller 객체 생성
        $oModuleController = &getController('module');
        $oModuleModel = &getModel('module');

        // 게시판 모듈의 정보 설정
        $args = Context::getRequestVars();
        $args->module = 'attendance';
        $args->mid = 'attendance';
        $info = $oModuleModel->getModuleInfoByMid('attendance');
        $args->module_srl = $info->module_srl;
        if(!$args->skin) $args->skin='default';

        // 기본 값외의 것들을 정리
        if($args->use_category!='Y') $args->use_category = 'N';
        if($args->except_notice!='Y') $args->except_notice = 'N';
        if($args->use_anonymous!='Y') $args->use_anonymous= 'N';
        if($args->consultation!='Y') $args->consultation = 'N';
        if(!$args->order_target) $args->order_target = 'list_order';
        if(!$args->order_type) $args->order_type = 'asc';

        //설정 업데이트
        $output = $oModuleController->updateModule($args);
        $msg_code = 'success_updated';

        if(!$output->toBool()) return $output;

        $this->add('page',Context::get('page'));
        $this->add('module_srl',$output->get('module_srl'));
        $this->setMessage($msg_code);
    }

  function procAttendanceAdminEnviromentGatheringAgreement()
 {
   $vars = Context::getRequestVars();
   $oModuleModel = &getModel('module');
    $attendance_module_info = $oModuleModel->getModuleInfoXml('attendance');
   $agreement_file = FileHandler::getRealPath(sprintf('%s%s.txt', './files/attendance/', $attendance_module_info->version));

    FileHandler::writeFile($agreement_file, $vars->is_agree);

    if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
    {
      $returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminAttendanceList');
      header('location: ' . $returnUrl);
      return;
    }
  }
    /**
    * @brief 출석부 게시판 삭제
    **/
    function procAttendanceAdminDeleteBoard(){
        $oModuleModel = &getModel('module');
        $oModuleController = &getController('module');
        $module_info = $oModuleModel->getModuleInfoByMid('attendance');
        $oModuleController->deleteModule($module_info->module_srl);
    }
}
?>
