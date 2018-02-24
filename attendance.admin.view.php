<?PHP
/**
 * @class attendanceAdminView
 * @author BJRambo (sosifam@070805.co.kr)
 * @brief attendance module admin view class
 **/

class attendanceAdminView extends attendance
{
	/**
	 * @return BaseObject|Object
	 */
	function init()
	{
		/** @var moduleModel $oModuleModel */
		$oModuleModel = getModel('module');

		$module_srl = Context::get('module_srl');
		if (!$module_srl && $this->module_srl)
		{
			$module_srl = $this->module_srl;
			Context::set('module_srl', $module_srl);
		}


		if ($module_srl)
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			if (!$module_info)
			{
				Context::set('module_srl', '');
				$this->act = 'list';
			}
			else
			{
				ModuleModel::syncModuleToSite($module_info);
				$this->module_info = $module_info;
				Context::set('module_info', $module_info);
			}
		}

		if ($module_info && $module_info->module != 'attendance')
		{
			return $this->makeObject(-1, 'msg_invalid_request');
		}

		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);

		$viewType = str_replace('dispAttendanceAdmin', '', $this->act);
		$templeatFileName = strtolower($viewType);

		Context::set('viewType', $viewType);

		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile($templeatFileName);
	}

	function dispAttendanceAdminConfig()
	{
		$oAttendanceModel = getModel('attendance');

		$config = $oAttendanceModel->getConfig();
		Context::set('config', $config);

		$start_time = new stdClass();
		$start_time->hour = substr($config->start_time, 0, 2);
		$start_time->min = substr($config->start_time, 2, 2);
		$end_time = new stdClass();
		$end_time->hour = substr($config->end_time, 0, 2);
		$end_time->min = substr($config->end_time, 2, 2);

		Context::set('start_time', $start_time);
		Context::set('end_time', $end_time);

		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByMid('attendance');

		Context::set('module_info', $module_info);
		Context::set('module_srl', $module_info->module_srl);
		Context::set('object_cache_available', preg_match('/^(apc|file|memcache|redis|wincache|xcache|sqlite)/', Context::getDBInfo()->use_object_cache));
	}

	function dispAttendanceAdminTime()
	{
		/** @var attendanceAdminModel $oAttendanceAdminModel */
		$oAttendanceAdminModel = getAdminModel('attendance');

		if (!Context::get('selected_date'))
		{
			$selected_date = zDate(date('YmdHis'), "Ymd");
		}
		else
		{
			$selected_date = Context::get('selected_date');
		}

		$total_count = $oAttendanceAdminModel->getTodayTotalCount($selected_date);
		Context::set('total_count', $total_count);

		$timeCountData = array();
		$countList = $oAttendanceAdminModel->getTodayTimeCountList($selected_date);
		for ($time = 0; $time < 24; $time++)
		{
			if ($time < 10)
			{
				$timeOclock = '0' . $time;
			}
			else
			{
				$timeOclock = strval($time);
			}

			$timeCountData[$timeOclock] = new stdClass();
			$timeCountData[$timeOclock]->time = $time;
			$timeCountData[$timeOclock]->count = $countList[$timeOclock];

			if ($countList[$timeOclock] > 0)
			{
				$timeCountData[$timeOclock]->percent = (int)($countList[$timeOclock] / $total_count * 100);
			}
			else
			{
				$timeCountData[$timeOclock]->percent = 0;
			}
		}

		Context::set('timeCountData', $timeCountData);
	}

	function dispAttendanceAdminDay()
	{
		$oAttendanceAdminModel = getAdminModel('attendance');

		if (!Context::get('selected_date'))
		{
			$selected_date = zDate(date('YmdHis'), "Ymd");
		}
		else
		{
			$selected_date = Context::get('selected_date');
		}

		$user_data = $oAttendanceAdminModel->getAttendanceMemberList(20, 'day');

		$year = substr($selected_date, 0, 4);
		$month = substr($selected_date, 4, 2);
		$day = substr($selected_date, 6, 2);
		$end_day = date('t', mktime(0, 0, 0, $month, 1, $year));
		$check_month = sprintf("%s%s", $year, $month);

		$arrayUserData = array();
		foreach ($user_data->data as $data)
		{
			$arrayUserData[$data->member_srl] = new stdClass();
			$arrayUserData[$data->member_srl]->doChecked = getModel('attendance')->getIsCheckedMonth($data->member_srl, $check_month);
		}

		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups();

		Context::set('group_list', $group_list);
		Context::set('end_day', $end_day);
		Context::set('year', $year);
		Context::set('selected', $selected_date);
		Context::set('month', $month);
		Context::set('day', $day);
		Context::set('arrayUserData', $arrayUserData);
		Context::set('user_data', $user_data);
	}

	function dispAttendanceAdminRankweekly()
	{
		/** @var attendanceAdminModel $oAttendanceAdminModel */
		$oAttendanceAdminModel = getAdminModel('attendance');
		$user_data = $oAttendanceAdminModel->getAttendanceMemberList(20, 'rankweekly');
		if (!Context::get('selected_date'))
		{
			$selected_date = zDate(date('YmdHis'), "Ymd");
		}
		else
		{
			$selected_date = Context::get('selected_date');
		}

		/** @var attendanceModel $oAttendanceModel */
		$oAttendanceModel = getModel('attendance');
		$week = $oAttendanceModel->getWeek($selected_date);
		$position = 1 + ($user_data->page - 1) * 20;

		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups();

		Context::set('group_list', $group_list);
		Context::set('week', $week);
		Context::set('position', $position);
		Context::set('user_data', $user_data);
	}

	function dispAttendanceAdminRankmonthly()
	{
		if (!Context::get('selected_date'))
		{
			$selected_date = zDate(date('YmdHis'), "Ymd");
		}
		else
		{
			$selected_date = Context::get('selected_date');
		}

		/** @var attendanceAdminModel $oAttendanceAdminModel */
		$oAttendanceAdminModel = getAdminModel('attendance');
		$user_data = $oAttendanceAdminModel->getAttendanceMemberList(20, 'rankmonthly');

		$year_month = substr($selected_date, 0, 6);
		$year = substr($selected_date, 0, 4);
		$month = substr($selected_date, 4, 2);
		$eom = date('t', mktime(0, 0, 0, $month, 1, $year));
		$position = 1 + ($user_data->page - 1) * 20;

		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups();

		Context::set('group_list', $group_list);
		Context::set('position', $position);
		Context::set('eom', $eom);
		Context::set('year_month', $year_month);
		Context::set('user_data', $user_data);
	}

	function dispAttendanceAdminRankyearly()
	{
		/** @var attendanceAdminModel $oAttendanceAdminModel */
		$oAttendanceAdminModel = getAdminModel('attendance');

		if (!Context::get('selected_date'))
		{
			$selected_date = zDate(date('YmdHis'), "Ymd");
		}
		else
		{
			$selected_date = Context::get('selected_date');
		}

		$year = substr($selected_date, 0, 4);
		$pad = date('t', mktime(0, 0, 0, 02, 1, $year));
		if ($pad == 29)
		{
			$eoy = 366;
		}
		else
		{
			$eoy = 365;
		}

		$user_data = $oAttendanceAdminModel->getAttendanceMemberList(20, 'rankyearly');
		$position = 1 + ($user_data->page - 1) * 20;

		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups();

		Context::set('group_list', $group_list);
		Context::set('position', $position);
		Context::set('eoy', $eoy);
		Context::set('year', $year);
		Context::set('pad', $pad);
		Context::set('user_data', $user_data);
	}

	function dispAttendanceAdminRanktotal()
	{
		/** @var attendanceAdminModel $oAttendanceAdminModel */
		$oAttendanceAdminModel = getAdminModel('attendance');

		$user_data = $oAttendanceAdminModel->getAttendanceMemberList(20, 'ranktotal');
		$position = 1 + ($user_data->page - 1) * 20;

		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups();

		Context::set('group_list', $group_list);
		Context::set('position', $position);
		Context::set('user_data', $user_data);
	}

	function dispAttendanceAdminSpeedsearch()
	{
		/** @var attendanceAdminModel $oAttendanceAdminModel */
		$oAttendanceAdminModel = getAdminModel('attendance');
		/** @var attendanceModel $oAttendanceModel */
		$oAttendanceModel = getModel('attendance');

		if (!Context::get('selected_date'))
		{
			$selected_date = zDate(date('YmdHis'), "Ymd");
		}
		else
		{
			$selected_date = Context::get('selected_date');
		}

		$year = substr($selected_date, 0, 4);
		$year_month = substr($selected_date, 0, 6);
		$month = substr($selected_date, 4, 2);
		$week = $oAttendanceModel->getWeek($selected_date);
		$eom = date('t', mktime(0, 0, 0, $month, 1, $year));

		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups();

		Context::set('group_list', $group_list);
		Context::set('eom', $eom);
		Context::set('year_month', $year_month);
		Context::set('week', $week);
		Context::set('year', $year);

		$user_data = $oAttendanceAdminModel->getAttendanceMemberList(20, 'speedsearch');
		Context::set('user_data', $user_data);
	}

	function dispAttendanceAdminBoardConfig()
	{
		$oModuleModel = getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list', $skin_list);

		$mskin_list = $oModuleModel->getSkins($this->module_path, 'm.skins');
		Context::set('mskin_list', $mskin_list);

		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		$mobile_layout_list = $oLayoutModel->getLayoutList(0, "M");
		Context::set('mlayout_list', $mobile_layout_list);

		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);

		$module_info = $oModuleModel->getModuleInfoByMid('attendance');
		Context::set('module_info', $module_info);

		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('board_config');
	}

	function dispAttendanceAdminBoardSkinConfig()
	{
		$oModuleAdminModel = getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);

		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('skin_info');
	}

	function dispAttendanceAdminMobileBoardSkinConfig()
	{
		$oModuleAdminModel = getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleMobileSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);

		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('skin_info');
	}


	function dispAttendanceAdminGrantList()
	{
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByMid('attendance');

		$oModuleAdminModel = getAdminModel('module');
		$grant_content = $oModuleAdminModel->getModuleGrantHTML($module_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $grant_content);

		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('grant_list');
	}

	function dispAttendanceAdminModifyAttendance()
	{
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByMid('attendance');
		$oModuleModel->syncSkinInfoToModuleInfo($module_info);
		$attendance_srl = Context::get('attendance_srl');
		$oAttendanceModel = getModel('attendance');
		$oMemberModel = getModel('member');

		$oAttendance = $oAttendanceModel->getAttendanceDataSrl($attendance_srl);
		Context::set('oAttendance', $oAttendance);
		Context::set('oAttendanceModel', $oAttendanceModel);
		Context::set('oMemberModel', $oMemberModel);

		$template_path = sprintf("%sskins/%s/", $this->module_path, $module_info->skin);
		if (!is_dir($template_path) || !$module_info->skin)
		{
			$template_path = sprintf("%sskins/%s/", $this->module_path, $module_info->skin);
		}
		$this->setTemplatePath($template_path);
		$this->setTemplateFile('modify');
	}

	function dispAttendanceAdminBoardAdditionSetup()
	{
		$content = '';

		ModuleHandler::triggerCall('module.dispAdditionSetup', 'after', $content);
		Context::set('setup_content', $content);

		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('sosi_setup');
	}

	function dispAttendanceAdminGift()
	{
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

		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('index');
	}
}
