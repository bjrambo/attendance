<?PHP
/**
 * @class attendanceView
 * @author BJRambo (sosifam@070805.co.kr)
 * @brief attendance module view class
 **/

class attendanceView extends attendance
{

	function init()
	{
		$template_path = sprintf("%sskins/%s/", $this->module_path, $this->module_info->skin);
		if (!is_dir($template_path) || !$this->module_info->skin)
		{
			$this->module_info->skin = 'default';
			$template_path = sprintf("%sskins/%s/", $this->module_path, $this->module_info->skin);
		}
		$this->setTemplatePath($template_path);
	}

	/**
	 * @brief Display the personal attendance data.
	 */
	function dispAttendancePersonalInfo()
	{
		//attendance model 객체 로드
		$oAttendanceModel = getModel('attendance');

		$selected_date = Context::get('selected_date');
		$date_info = new stdClass();
		if (!$selected_date)
		{
			$date_info->year = zDate(date('YmdHis'), "Y");
			$date_info->month = zDate(date('YmdHis'), "m");
			$date_info->day = zDate(date('YmdHis'), "d");
		}
		else
		{
			$date_info->year = substr($selected_date, 0, 4);
			$date_info->month = substr($selected_date, 4, 2);
			$date_info->day = substr($selected_date, 6, 2);
		}

		$date_info->day_max = date("t", mktime(0, 0, 0, $date_info->month, 1, $date_info->year));
		$date_info->week_start = date("w", mktime(0, 0, 0, $date_info->month, 1, $date_info->year));

		Context::set('admin_date_info', $date_info);
		Context::set('logged_info', Context::get('logged_info'));
		Context::set('oAttendanceModel', $oAttendanceModel);

		/*템플릿 설정*/
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('personal');
	}

	/**
	 * @brief Display the attendance board.
	 */
	function dispAttendanceBoard()
	{
		$oMemberModel = getModel('member');
		$oDocumentModel = getModel('document');
		$oAttendanceModel = getModel('attendance');

		$module_info = $oAttendanceModel->getAttendanceInfo();
		getModel('module')->syncSkinInfoToModuleInfo($module_info);
		$oAttendanceAdminModel = getAdminModel('attendance');

		$document_srl = Context::get('document_srl');
		$selected_date = Context::get('selected_date');
		if ($document_srl)
		{
			$oAttendance = $oAttendanceModel->getGreetingsData("#" . $document_srl);
			$selected_date = substr($oAttendance->regdate, 0, 8);
		}
		if (!$selected_date)
		{
			$selected_date = zDate(date('YmdHis'), "Ymd");
		}

		$module_info->start_num = $oAttendanceModel->getPositionData($selected_date, "^showall^");
		if (!$module_info->greetings_cut_size)
		{
			$module_info->greetings_cut_size = 20;
		}

		$config = $oAttendanceModel->getConfig();

		$is_available = $oAttendanceModel->availableCheck();

		if ($config->greeting_list)
		{
			$greeting_list = explode("\r\n", $config->greeting_list);
			$grc = count($greeting_list);
			$rands = mt_rand(0, $grc - 1);
			$greeting_name = $greeting_list[$rands];

			Context::set('greeting_name', $greeting_name);
		}

		if ($module_info->order_type == 'desc')
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

		$date_info = new stdClass();
		$date_info->_year = substr($selected_date, 0, 4);
		$date_info->_month = substr($selected_date, 4, 2);
		$date_info->_day = substr($selected_date, 6, 2);
		$date_info->day_max = date("t", mktime(0, 0, 0, $date_info->_month, 1, $date_info->_year));
		$date_info->week_start = date("w", mktime(0, 0, 0, $date_info->_month, 1, $date_info->_year));

		Context::set('admin_date_info', $date_info);
		Context::set('todaymygift', $outputs->data);
		Context::set('selected_date', $selected_date);
		Context::set('is_available', $is_available);
		Context::set('oAttendance', $oAttendance);
		Context::set('oDocumentModel', $oDocumentModel);
		Context::set('oAttendanceAdminModel', $oAttendanceAdminModel);
		Context::set('oAttendanceModel', $oAttendanceModel);
		Context::set('oMemberModel', $oMemberModel);
		Context::set('module_info', $module_info);
		Context::set('config', $config);

		$this->setTemplateFile('index');
	}

	function dispAttendanceBoardGiftList()
	{
		$logged_info = Context::get('logged_info');

		if (!Context::get('is_logged'))
		{
			return $this->makeObject(-1, '로그인 사용자만 이용 가능합니다.');
		}

		$args = new stdClass();
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

		if ($logged_info->is_admin != 'Y')
		{
			return $this->makeObject(-1, '관리자만 접속 할 수 있습니다.');
		}

		$member_srl = Context::get('member_srl');
		if (!$member_srl)
		{
			return $this->makeObject(-1, '회원번호는 필수입니다.');
		}
		$oAttendanceModel = getModel('attendance');
		$data = $oAttendanceModel->getContinuityDataByMemberSrl($member_srl);
		Context::set('data', $data);
		$this->setTemplateFile('continuous');
	}

	function dispAttendanceMemberInfo()
	{
		if (!Context::get('is_logged'))
		{
			return $this->makeObject(-1, '로그인 사용자만 사용가능합니다.');
		}

		$oAttendanceModel = getModel('attendance');

		$logged_info = Context::get('logged_info');
		$member_srl = Context::get('member_srl');

		if (!$member_srl)
		{
			$member_srl = $logged_info->member_srl;
			$member_info = $logged_info;
		}
		else
		{
			$member_info = getModel('member')->getMemberInfoByMemberSrl($member_srl);
		}

		$user_sign_up_date = $member_info->regdate;
		$total_attendance = $oAttendanceModel->getTotalAttendance($member_srl);
		$total_absent = $oAttendanceModel->getTotalAbsent($user_sign_up_date, $total_attendance);
		$total_weekly = $oAttendanceModel->getWeeklyAttendanceByMemberSrl($member_srl);

		$member_data = new stdClass();
		$member_data->nick_name = $member_info->nick_name;
		$member_data->profile_img = $member_info->profile_image->src;
		$member_data->member_srl = $member_srl;
		$member_data->total_attendance = $total_attendance;
		$member_data->total_absent = $total_absent;
		$member_data->total_weekly = $total_weekly;

		Context::set('member_data', $member_data);

		$this->setTemplateFile('member_info');
	}
}
