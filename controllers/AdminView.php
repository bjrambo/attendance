<?php
namespace Rhymix\Modules\Attendance\Controllers;

/**
 * Attendance module admin view actions.
 */
class AdminView extends Base
{
	/** @var object Module info resolved for the current admin context */
	public $att_module_info;

	public function init(): void
	{
		$oModuleModel = \ModuleModel::getInstance();

		$module_srl = \Context::get('module_srl');
		if (!$module_srl && $this->module_srl)
		{
			$module_srl = $this->module_srl;
			\Context::set('module_srl', $module_srl);
		}

		$module_info = null;
		if ($module_srl)
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			if (!$module_info)
			{
				\Context::set('module_srl', '');
				$this->act = 'list';
			}
			else
			{
				\ModuleModel::syncModuleToSite($module_info);
				$this->module_info = $module_info;
				\Context::set('module_info', $module_info);
				$this->att_module_info = $module_info;
			}
		}
		else
		{
			$output = executeQuery('attendance.getAttendance');
			if ($output->data)
			{
				$module_info = \ModuleModel::getModuleInfoByModuleSrl($output->data->module_srl);
				\Context::set('module_info', $module_info);
				$this->att_module_info = $module_info;
			}
		}

		if ($module_info && $module_info->module != 'attendance')
		{
			$this->makeObject(-1, 'msg_invalid_request');
			return;
		}

		$module_category = $oModuleModel->getModuleCategories();
		\Context::set('module_category', $module_category);

		$viewType = str_replace('dispAttendanceAdmin', '', $this->act);
		$templateFile = strtolower($viewType);
		\Context::set('viewType', $viewType);

		$this->setTemplatePath($this->module_path . 'views/admin');
		$this->setTemplateFile($templateFile . '.blade.php');
	}

	public function dispAttendanceAdminConfig(): void
	{
		$oAttendanceModel = new \Rhymix\Modules\Attendance\Models\Attendance();
		$config = $oAttendanceModel->getConfig();
		\Context::set('config', $config);

		$start_time = new \stdClass();
		$start_time->hour = substr($config->start_time, 0, 2);
		$start_time->min = substr($config->start_time, 2, 2);
		$end_time = new \stdClass();
		$end_time->hour = substr($config->end_time, 0, 2);
		$end_time->min = substr($config->end_time, 2, 2);

		\Context::set('start_time', $start_time);
		\Context::set('end_time', $end_time);

		$oModuleModel = \ModuleModel::getInstance();
		$module_info = $oModuleModel->getModuleInfoByMid('attendance');
		\Context::set('module_info', $module_info);
		\Context::set('module_srl', $module_info->module_srl);
		\Context::set('object_cache_available', $this->_getCacheType());
	}

	public function dispAttendanceAdminTime(): void
	{
		$oAttendanceAdminModel = new \Rhymix\Modules\Attendance\Models\AdminAttendance();

		$selected_date = \Context::get('selected_date') ?: zDate(date('YmdHis'), 'Ymd');

		$total_count = $oAttendanceAdminModel->getTodayTotalCount($selected_date);
		\Context::set('total_count', $total_count);

		$timeCountData = [];
		$countList = $oAttendanceAdminModel->getTodayTimeCountList($selected_date);
		for ($time = 0; $time < 24; $time++)
		{
			$timeOclock = sprintf('%02d', $time);
			$timeCountData[$timeOclock] = new \stdClass();
			$timeCountData[$timeOclock]->time = $time;
			$timeCountData[$timeOclock]->count = $countList[$timeOclock];
			$timeCountData[$timeOclock]->percent = ($countList[$timeOclock] > 0)
				? (int)($countList[$timeOclock] / $total_count * 100)
				: 0;
		}
		\Context::set('timeCountData', $timeCountData);
	}

	public function dispAttendanceAdminDay(): void
	{
		$oAttendanceAdminModel = new \Rhymix\Modules\Attendance\Models\AdminAttendance();
		$selected_date = \Context::get('selected_date') ?: zDate(date('YmdHis'), 'Ymd');
		$user_data = $oAttendanceAdminModel->getAttendanceMemberList(20, 'day');

		$year = substr($selected_date, 0, 4);
		$month = substr($selected_date, 4, 2);
		$day = substr($selected_date, 6, 2);
		$end_day = date('t', mktime(0, 0, 0, $month, 1, $year));
		$check_month = sprintf('%s%s', $year, $month);

		$arrayUserData = [];
		foreach ($user_data->data as $data)
		{
			$arrayUserData[$data->member_srl] = new \stdClass();
			$arrayUserData[$data->member_srl]->doChecked = (new \Rhymix\Modules\Attendance\Models\Attendance())->getIsCheckedMonth($data->member_srl, $check_month);
		}

		$group_list = \MemberModel::getInstance()->getGroups();
		\Context::set('group_list', $group_list);
		\Context::set('end_day', $end_day);
		\Context::set('year', $year);
		\Context::set('selected', $selected_date);
		\Context::set('month', $month);
		\Context::set('day', $day);
		\Context::set('arrayUserData', $arrayUserData);
		\Context::set('user_data', $user_data);
	}

	public function dispAttendanceAdminRankweekly(): void
	{
		$oAttendanceAdminModel = new \Rhymix\Modules\Attendance\Models\AdminAttendance();
		$selected_date = \Context::get('selected_date') ?: zDate(date('YmdHis'), 'Ymd');
		$user_data = $oAttendanceAdminModel->getAttendanceMemberList(20, 'rankweekly');
		$week = (new \Rhymix\Modules\Attendance\Models\Attendance())->getWeek($selected_date);
		$position = 1 + ($user_data->page - 1) * 20;

		\Context::set('group_list', \MemberModel::getInstance()->getGroups());
		\Context::set('week', $week);
		\Context::set('position', $position);
		\Context::set('user_data', $user_data);
	}

	public function dispAttendanceAdminRankmonthly(): void
	{
		$selected_date = \Context::get('selected_date') ?: zDate(date('YmdHis'), 'Ymd');
		$oAttendanceAdminModel = new \Rhymix\Modules\Attendance\Models\AdminAttendance();
		$user_data = $oAttendanceAdminModel->getAttendanceMemberList(20, 'rankmonthly');

		$year_month = substr($selected_date, 0, 6);
		$year = substr($selected_date, 0, 4);
		$month = substr($selected_date, 4, 2);
		$eom = date('t', mktime(0, 0, 0, $month, 1, $year));
		$position = 1 + ($user_data->page - 1) * 20;

		\Context::set('group_list', \MemberModel::getInstance()->getGroups());
		\Context::set('position', $position);
		\Context::set('eom', $eom);
		\Context::set('year_month', $year_month);
		\Context::set('user_data', $user_data);
	}

	public function dispAttendanceAdminRankyearly(): void
	{
		$oAttendanceAdminModel = new \Rhymix\Modules\Attendance\Models\AdminAttendance();
		$selected_date = \Context::get('selected_date') ?: zDate(date('YmdHis'), 'Ymd');
		$year = substr($selected_date, 0, 4);
		$pad = date('t', mktime(0, 0, 0, 02, 1, $year));
		$eoy = ($pad == 29) ? 366 : 365;

		$user_data = $oAttendanceAdminModel->getAttendanceMemberList(20, 'rankyearly');
		$position = 1 + ($user_data->page - 1) * 20;

		\Context::set('group_list', \MemberModel::getInstance()->getGroups());
		\Context::set('position', $position);
		\Context::set('eoy', $eoy);
		\Context::set('year', $year);
		\Context::set('pad', $pad);
		\Context::set('user_data', $user_data);
	}

	public function dispAttendanceAdminRanktotal(): void
	{
		$oAttendanceAdminModel = new \Rhymix\Modules\Attendance\Models\AdminAttendance();
		$user_data = $oAttendanceAdminModel->getAttendanceMemberList(20, 'ranktotal');
		$position = 1 + ($user_data->page - 1) * 20;

		\Context::set('group_list', \MemberModel::getInstance()->getGroups());
		\Context::set('position', $position);
		\Context::set('user_data', $user_data);
	}

	public function dispAttendanceAdminSpeedsearch(): void
	{
		$oAttendanceAdminModel = new \Rhymix\Modules\Attendance\Models\AdminAttendance();
		$oAttendanceModel = new \Rhymix\Modules\Attendance\Models\Attendance();
		$selected_date = \Context::get('selected_date') ?: zDate(date('YmdHis'), 'Ymd');

		$year = substr($selected_date, 0, 4);
		$year_month = substr($selected_date, 0, 6);
		$month = substr($selected_date, 4, 2);
		$week = $oAttendanceModel->getWeek($selected_date);
		$eom = date('t', mktime(0, 0, 0, $month, 1, $year));

		\Context::set('group_list', \MemberModel::getInstance()->getGroups());
		\Context::set('eom', $eom);
		\Context::set('year_month', $year_month);
		\Context::set('week', $week);
		\Context::set('year', $year);
		\Context::set('user_data', $oAttendanceAdminModel->getAttendanceMemberList(20, 'speedsearch'));
	}

	public function dispAttendanceAdminBoardConfig(): void
	{
		$oModuleModel = \ModuleModel::getInstance();
		$skin_list = $oModuleModel->getSkins($this->module_path);
		$mskin_list = $oModuleModel->getSkins($this->module_path, 'm.skins');
		$oLayoutModel = \LayoutModel::getInstance();

		\Context::set('skin_list', $skin_list);
		\Context::set('mskin_list', $mskin_list);
		\Context::set('layout_list', $oLayoutModel->getLayoutList());
		\Context::set('mlayout_list', $oLayoutModel->getLayoutList(0, 'M'));
		\Context::set('module_category', $oModuleModel->getModuleCategories());
		\Context::set('module_info', $oModuleModel->getModuleInfoByMid('attendance'));

		$this->setTemplatePath($this->module_path . 'views/admin');
		$this->setTemplateFile('boardconfig.blade.php');
	}

	public function dispAttendanceAdminBoardSkinConfig(): void
	{
		$oModuleAdminModel = \ModuleAdminModel::getInstance();
		$skin_content = $oModuleAdminModel->getModuleSkinHTML($this->att_module_info->module_srl);
		\Context::set('skin_content', $skin_content);
	}

	public function dispAttendanceAdminMobileBoardSkinConfig(): void
	{
		$oModuleAdminModel = \ModuleAdminModel::getInstance();
		$skin_content = $oModuleAdminModel->getModuleMobileSkinHTML($this->att_module_info->module_srl);
		\Context::set('skin_content', $skin_content);
	}

	public function dispAttendanceAdminGrantList(): void
	{
		$oModuleModel = \ModuleModel::getInstance();
		$module_info = $oModuleModel->getModuleInfoByMid('attendance');
		$oModuleAdminModel = \ModuleAdminModel::getInstance();
		$grant_content = $oModuleAdminModel->getModuleGrantHTML($module_info->module_srl, $this->xml_info->grant);
		\Context::set('grant_content', $grant_content);
	}

	public function dispAttendanceAdminModifyAttendance(): void
	{
		$oModuleModel = \ModuleModel::getInstance();
		$module_info = $oModuleModel->getModuleInfoByMid('attendance');
		$oModuleModel->syncSkinInfoToModuleInfo($module_info);
		$oAttendanceModel = new \Rhymix\Modules\Attendance\Models\Attendance();
		$attendance_srl = \Context::get('attendance_srl');

		$oAttendance = $oAttendanceModel->getAttendanceDataSrl($attendance_srl);
		\Context::set('oAttendance', $oAttendance);
		\Context::set('oAttendanceModel', $oAttendanceModel);
		\Context::set('oMemberModel', \MemberModel::getInstance());

		$template_path = sprintf('%sskins/%s/', $this->module_path, $module_info->skin);
		if (!is_dir($template_path) || !$module_info->skin)
		{
			$template_path = sprintf('%sskins/default/', $this->module_path);
		}
		$this->setTemplatePath($template_path);
		$this->setTemplateFile('modify');
	}

	public function dispAttendanceAdminBoardAdditionSetup(): void
	{
		$content = '';
		\ModuleHandler::triggerCall('module.dispAdditionSetup', 'before', $content);
		\ModuleHandler::triggerCall('module.dispAdditionSetup', 'after', $content);
		\Context::set('setup_content', $content);
	}

	public function dispAttendanceAdminGift(): void
	{
		$args = new \stdClass();
		$args->page = \Context::get('page');
		$args->list_count = '20';
		$args->page_count = '10';
		$output = executeQuery('attendance.getAdminGiftList', $args);

		\Context::set('total_count', $output->page_navigation->total_count);
		\Context::set('total_page', $output->page_navigation->total_page);
		\Context::set('page', $output->page);
		\Context::set('admingift_list', $output->data);
		\Context::set('page_navigation', $output->page_navigation);
	}

	public function dispAttendanceAdminList(): void
	{
		// placeholder — rendered by init() template derivation
	}
}
