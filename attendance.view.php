<?PHP
/**
* @class 출석부 모듈의 attendanceView 클래스
* @author BJRambo (sosifam@070805.co.kr)
* @개인의 출석정보 출력 담당
*
* 관리자페이지에 표시할 내용과 사용변수에 대한 정의/전달
**/

class attendanceView extends attendance
{

	function init()
	{
		/**
		 * 스킨 경로를 미리 template_path 라는 변수로 설정함
		 * 스킨이 존재하지 않는다면 default로 변경
		 **/
		$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
		if(!is_dir($template_path)||!$this->module_info->skin)
		{
			$this->module_info->skin = 'default';
			$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
		}
		$this->setTemplatePath($template_path);
	}

	//개인의 출석달력
	function dispAttendancePersonalInfo()
	{

		//attendance model 객체 로드
		$oAttendanceModel = getModel('attendance');

		$selected_date = Context::get('selected_date');
		$date_info = new stdClass;
		if(!$selected_date)
		{
			$date_info->year = zDate(date('YmdHis'),"Y");
			$date_info->month = zDate(date('YmdHis'),"m");
			$date_info->day = zDate(date('YmdHis'),"d");
		}
		else
		{
			$date_info->year = substr($selected_date,0,4);
			$date_info->month = substr($selected_date,4,2);
			$date_info->day = substr($selected_date,6,2);
		}

		$date_info->day_max = date("t",mktime(0,0,0,$date_info->month,1,$date_info->year));
		$date_info->week_start = date("w",mktime(0,0,0,$date_info->month,1,$date_info->year));

		Context::set('admin_date_info',$date_info);
		Context::set('logged_info',Context::get('logged_info'));
		Context::set('oAttendanceModel',$oAttendanceModel);

		/*템플릿 설정*/
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('personal');
	}

    //출석게시판 출력
	function dispAttendanceBoard()
	{
		$oModuleModel = getModel('module');
		$oDocumentModel = getModel('document');
		$module_info = $oModuleModel->getModuleInfoByMid('attendance');
		$oModuleModel->syncSkinInfoToModuleInfo($module_info);
		$oAttendanceModel = getModel('attendance');
		$oMemberModel = getModel('member');
		$oAttendanceAdminModel = getAdminModel('attendance');

		//날짜 초기화
		$document_srl = Context::get('document_srl');
		$selected_date = Context::get('selected_date');
		if($document_srl)
		{
			$oAttendance = $oAttendanceModel->getGreetingsData("#".$document_srl);
			$selected_date = substr($oAttendance->regdate,0,8);
		}
		if(!$selected_date) $selected_date = zDate(date('YmdHis'),"Ymd");

		$module_info->start_num = $oAttendanceModel->getPositionData($selected_date,"^showall^");
		if(!$module_info->greetings_cut_size)$module_info->greetings_cut_size = 20;

		//module의 설정값 가져오기
		$oModuleModel = getModel('module');
		$config = $oAttendanceModel->getConfig();

		//출석가능 시간대인지 판단
		$is_available = $oAttendanceModel->availableCheck($config);

		if($config->greeting_list)
		{
			$greeting_list = explode("\r\n", $config->greeting_list);
			$grc = count($greeting_list);
			$rands = mt_rand(0, $grc-1);
			$greeting_name = $greeting_list[$rands];
			
			Context::set('greeting_name', $greeting_name);
		}


		//오름차순, 내림차순에 따라 출력방법 결정
		if($module_info->order_type == 'desc')
		{
			$oAttendance = $oAttendanceModel->getInverseList($module_info->list_count, $selected_date);
		}
		else
		{
			$oAttendance = $oAttendanceModel->getAttendanceList($module_info->list_count, $selected_date);
		}


		$logged_info = Context::get('logged_info');
		$args = new stdClass();
		$args->member_srl = $logged_info->member_srl;
		$args->present_y = 'Y';
		$args->today = $selected_date;
		$outputs = executeQuery('attendance.getTodayMyGiftList', $args);

		//출석달력 설정
		$date_info = new stdClass;
		$date_info->_year = substr($selected_date,0,4);
		$date_info->_month = substr($selected_date,4,2);
		$date_info->_day = substr($selected_date,6,2);
		$date_info->day_max = date("t",mktime(0,0,0,$date_info->_month,1,$date_info->_year));
		$date_info->week_start = date("w",mktime(0,0,0,$date_info->_month,1,$date_info->_year));

		Context::set('admin_date_info',$date_info);

		//변수 내보내기
		Context::set('todaymygift',$outputs->data);
		Context::set('selected_date',$selected_date);
		Context::set('is_available',$is_available);
		Context::set('oAttendance',$oAttendance);
		Context::set('oDocumentModel',$oDocumentModel);
		Context::set('oAttendanceAdminModel',$oAttendanceAdminModel);
		Context::set('oAttendanceModel',$oAttendanceModel);
		Context::set('oMemberModel',$oMemberModel);
		Context::set('module_info',$module_info);
		Context::set('config',$config);

		$this->setTemplateFile('index');
	}

	function dispAttendanceBoardGiftList()
	{
		$logged_info = Context::get('logged_info');

		if(!$logged_info)
		{
			return new Object(-1, '로그인 사용자만 이용 가능합니다.');
		}

		$args = new stdClass;
		$args->page = Context::get('page');
		$args->list_count = '20';
		$args->page_count = '10';
		$args->member_srl = $logged_info->member_srl;
		$output = executeQuery('attendance.getGiftList', $args);

		Context::set('total_count', $output->page_navigation->total_count);
		Context::set('total_page', $output->page_navigation->total_page);
		Context::set('page', $output->page);
		Context::set('attendance_gift', $output->data);
		Context::set('page_navigation', $output->page_navigation);
		/*템플릿 설정*/
		$this->setTemplateFile('gift');
	}

	function dispAttendanceModifyContinuous()
	{
		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin != 'Y')
		{
			return new Object(-1, '관리자만 접속 할 수 있습니다.');
		}

		$member_srl = Context::get('member_srl');
		if(!$member_srl)
		{
			return new Object(-1, '회원번호는 필수입니다.');
		}
		$oAttendanceModel = getModel('attendance');
		$data = $oAttendanceModel->getContinuityDataByMemberSrl($member_srl);
		debugPrint($data);
		Context::set('data', $data);
		$this->setTemplateFile('continuous');
		
	}
}
