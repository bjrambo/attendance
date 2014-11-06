<?PHP
/**
 * @class 출석부 모듈의 attendanceAdminView 클래스
 * @author BJRambo (sosifam@070805.co.kr)
 * @관리자페이지의 출력 담당
 *
 * 관리자페이지에 표시할 내용과 사용변수에 대한 정의/전달
 **/

class attendanceAdminView extends attendance
{

	function init()
	{
		// module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
		$module_srl = Context::get('module_srl');
		if(!$module_srl && $this->module_srl)
		{
			$module_srl = $this->module_srl;
			Context::set('module_srl', $module_srl);
		}

		// module model 객체 생성 
		$oModuleModel = getModel('module');

		// module_srl이 넘어오면 해당 모듈의 정보를 미리 구해 놓음
		if($module_srl)
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			if(!$module_info)
			{
				Context::set('module_srl','');
				$this->act = 'list';
			}
			else
			{
				ModuleModel::syncModuleToSite($module_info);
				$this->module_info = $module_info;
				Context::set('module_info',$module_info);
			}
		}

		if($module_info && $module_info->module != 'attendance') return $this->stop("msg_invalid_request");

		// 모듈 카테고리 목록을 구함
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);
	}

	//출석부 관리페이지 출력
	function dispAttendanceAdminList()
	{
		$document_srl = Context::get('document_srl');
		if($document_srl)
		{
			return $this->stop('msg_admin_to_use');
		}
		$mid = Context::get('mid');
		if($mid)
		{
			return $this->stop('msg_admin_to_use');
		}
		/*attendance model 객체 생성*/
		$oAttendanceModel = getModel('attendance');
		Context::set('Model',$oAttendanceModel);

		$selected_date = Context::get('selected_date'); //선택한 날짜 받아오기

		$type=Context::get('type');


		/*attendance admin model 객체 생성*/
		$oAttendanceAdminModel = getAdminModel('attendance');
		Context::set('oAttendanceAdminModel',$oAttendanceAdminModel);
		if($type!='config' && $type!='time')
		{
			$user_data = $oAttendanceAdminModel->getAttendanceMemberList(20,$type);
			Context::set('user_data',$user_data);
		}

		// 멤버모델 객체 생성
		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups();
		Context::set('group_list',$group_list);

		/*날짜 관련*/
		if(!$selected_date)
		{
			$selected_date = zDate(date('YmdHis'),"Ymd");
		}
		$year = substr($selected_date, 0, 4);
		$month = substr($selected_date, 4, 2);
		$day = substr($selected_date,6,2);
		$end_day = date('t', mktime(0,0,0,$month,1,$year));

		$oMemberModel = getModel('member');

		Context::set('end_day',$end_day);
		Context::set('year',$year);
		Context::set('selected',$selected_date);
		Context::set('month',$month);
		Context::set('day',$day);
		Context::set('ipaddress',$_SERVER['REMOTE_ADDR']);
		Context::set('oMemberModel',$oMemberModel);

		//module의 설정값 가져오기
		$oModuleModel = getModel('module');
		$config = $oAttendanceModel->getConfig();
		$oModuleAdminModel = getAdminModel('module');
		Context::set('config',$config);

		$start_time = new stdClass;
		$start_time->hour = substr($config->start_time,0,2);
		$start_time->min = substr($config->start_time,2,2);
		$end_time = new stdClass;
		$end_time->hour = substr($config->end_time,0,2);
		$end_time->min = substr($config->end_time,2,2);
		Context::set('start_time',$start_time);
		Context::set('end_time',$end_time);

		// 스킨 목록을 구해옴
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByMid('attendance');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);

		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);

		// 레이아웃 목록을 구해옴
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
		Context::set('mlayout_list', $mobile_layout_list);

		// 모듈 카테고리 목록을 구함
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);

		// 공통 모듈 권한 설정 페이지 호출
		$skin_content = $oModuleAdminModel->getModuleSkinHTML($module_info->module_srl);
		Context::set('skin_content', $skin_content);

		Context::set('module_info',$module_info);
		Context::set('module_srl', $module_info->module_srl);

		// 사용환경정보 전송 확인
		$attendance_module_info = $oModuleModel->getModuleInfoXml('attendance');
		$agreement_file = FileHandler::getRealPath(sprintf('%s%s.txt', './files/attendance/attendance-', $attendance_module_info->version));

		$agreement_ver_file = FileHandler::getRealPath(sprintf('%s%s.txt', './files/attendance/attendance_ver-', $attendance_module_info->version));

		if(file_exists($agreement_file))
		{
			$agreement = FileHandler::readFile($agreement_file);
			Context::set('_attendance_env_agreement', $agreement);
			$agreement_ver = FileHandler::readFile($agreement_ver_file);
			if($agreement == 'Y')
			{
				$_attendance_iframe_url = 'http://sosifam.com/index.php?mid=attendance_iframe';
				if(!$agreement_ver)
				{
					$_host_info = urlencode($_SERVER['HTTP_HOST']) . '-NC' . $attendance_module_info->version . '-PHP' . phpversion() . '-XE' . __XE_VERSION__;
				}
				Context::set('_attendance_iframe_url', $_attendance_iframe_url . '&_host='. $_host_info);
				Context::set('attendance_module_info', $attendance_module_info);
			}
			FileHandler::writeFile($agreement_ver_file, 'Y');
		}
		else
		{
			Context::set('_attendance_env_agreement', 'NULL');
		}

		/*템플릿 설정*/
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('index');
	}

	/**
	 * @brief 출석부 게시판 설정페이지
	 **/
	function dispAttendanceAdminBoardConfig()
	{
		// 스킨 목록을 구해옴
		$oModuleModel = getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);

		$mskin_list = $oModuleModel->getSkins($this->module_path, 'm.skins');
		Context::set('mskin_list', $mskin_list);

		// 레이아웃 목록을 구해옴
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
		Context::set('mlayout_list', $mobile_layout_list);

		// 모듈 카테고리 목록을 구함
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);

		$module_info = $oModuleModel->getModuleInfoByMid('attendance');
		Context::set('module_info',$module_info);

		/*템플릿 설정*/
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('board_config');
	}

	/**
	 * @brief 출석부 게시판스킨 설정페이지
	 **/
	function dispAttendanceAdminBoardSkinConfig()
	{
		//모듈정보 로드
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByMid('attendance');

		// 공통 모듈 권한 설정 페이지 호출
		$oModuleAdminModel = getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
		/*Context::set('module_info', $module_info);
		Context::set('module_srl', $module_info->module_srl);
		Context::set('mid', $module_info->mid);*/

		/*템플릿 설정*/
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('skin_info');
	}

	/**
	 * @brief 출석부 모바일 게시판스킨 설정페이지
	 **/
	function dispAttendanceAdminMobileBoardSkinConfig()
	{
		//모듈정보 로드
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByMid('attendance');

		// 공통 모듈 권한 설정 페이지 호출
		$oModuleAdminModel = getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleMobileSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
		/*Context::set('module_info', $module_info);
		Context::set('module_srl', $module_info->module_srl);
		Context::set('mid', $module_info->mid);*/

		/*템플릿 설정*/
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('skin_info');
	}


	/**
	 * @brief 출석부 게시판 권한 설정페이지
	 **/
	function dispAttendanceAdminGrantList()
	{
		//모듈정보 로드
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByMid('attendance');

		// 공통 모듈 권한 설정 페이지 호출
		$oModuleAdminModel = getAdminModel('module');
		$grant_content = $oModuleAdminModel->getModuleGrantHTML($module_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $grant_content);

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('grant_list');
	}

    /**
    * @brief 출석자료 수정 페이지
    **/
	function dispAttendanceAdminModifyAttendance()
	{
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByMid('attendance');
		$oModuleModel->syncSkinInfoToModuleInfo($module_info);
		$attendance_srl = Context::get('attendance_srl');
		$oAttendanceModel = getModel('attendance');
		$oMemberModel = getModel('member');

		$oAttendance = $oAttendanceModel->getAttendanceDataSrl($attendance_srl);
		Context::set('oAttendance',$oAttendance);
		Context::set('oAttendanceModel',$oAttendanceModel);
		Context::set('oMemberModel',$oMemberModel);

		$template_path = sprintf("%sskins/%s/",$this->module_path, $module_info->skin);
		if(!is_dir($template_path)||!$module_info->skin)
		{
			$template_path = sprintf("%sskins/%s/",$this->module_path, $module_info->skin);
		}
		$this->setTemplatePath($template_path);
		$this->setTemplateFile('modify');
	}

	/**
	 * @brief display the additional setup panel
	 * additonal setup panel is for connecting the service modules with other modules
	 **/
	function dispAttendanceAdminBoardAdditionSetup()
	{
		// sice content is obtained from other modules via call by reference, declare it first
		$content = '';

		// get the addtional setup trigger
		// the additional setup triggers can be used in many modules
		$output = ModuleHandler::triggerCall('module.dispAdditionSetup', 'after', $content);
		Context::set('setup_content', $content);

		// setup the template file
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('sosi_setup');
	}

	/**
	 * @brief diplay the gift List
	 */
	function dispAttendanceAdminGift()
	{
		$oMemberModel = getModel('member');

		$args = new stdClass();
		$args->page = Context::get('page');
		$args->list_count = '20';
		$args->page_count = '10';
		$output = executeQuery('attendance.getAdminGiftList', $args);

		Context::set('total_count', $output->page_navigation->total_count);
		Context::set('total_page', $output->page_navigation->total_page);
		Context::set('page', $output->page);
		Context::set('admingift_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('index');
	}
}
