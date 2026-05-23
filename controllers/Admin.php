<?php
namespace Rhymix\Modules\Attendance\Controllers;

/**
 * Attendance module admin proc actions.
 */
class Admin extends Base
{
	public function init(): void {}

	public function procAttendanceAdminDeleteAllData(): void
	{
		$oAttendanceAdminModel = new \Rhymix\Modules\Attendance\Models\AdminAttendance();
		$obj = \Context::getRequestVars();
		$oAttendanceAdminModel->deleteAllAttendanceData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceTotalData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceYearlyData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceMonthlyData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceWeeklyData($obj->member_srl);
		$this->setMessage('attend_deleted');
	}

	public function procAttendanceAdminInsertAttendance()
	{
		$obj = \Context::getRequestVars();

		$config = (new \Rhymix\Modules\Attendance\Models\Attendance())->getConfig();
		$config->about_admin_check = $obj->about_admin_check;
		$config->allow_duplicaton_ip_count = $obj->allow_duplicaton_ip_count;
		$config->about_auto_attend = $obj->about_auto_attend;
		$config->about_birth_day = $obj->about_birth_day;
		$config->about_birth_day_y = $obj->about_birth_day_y;
		$config->about_time_control = $obj->about_time_control;
		$config->start_time = sprintf('%02d%02d', $obj->start_hour, $obj->start_min);
		$config->end_time = sprintf('%02d%02d', $obj->end_hour, $obj->end_min);
		$config->about_diligence_yearly = $obj->about_diligence_yearly;
		$config->diligence_yearly = $obj->diligence_yearly;
		$config->diligence_yearly_point = $obj->diligence_yearly_point;
		$config->about_diligence_monthly = $obj->about_diligence_monthly;
		$config->diligence_monthly = $obj->diligence_monthly;
		$config->diligence_monthly_point = $obj->diligence_monthly_point;
		$config->about_diligence_weekly = $obj->about_diligence_weekly;
		$config->diligence_weekly = $obj->diligence_weekly;
		$config->diligence_weekly_point = $obj->diligence_weekly_point;
		$config->add_point = $obj->add_point;
		$config->first_point = $obj->first_point;
		$config->second_point = $obj->second_point;
		$config->third_point = $obj->third_point;
		$config->yearly_point = $obj->yearly_point;
		$config->monthly_point = $obj->monthly_point;
		$config->weekly_point = $obj->weekly_point;
		$config->about_target = $obj->about_target;
		$config->target_day = $obj->target_day;
		$config->target_point = $obj->target_point;
		$config->about_continuity = $obj->about_continuity;
		$config->continuity_day = $obj->continuity_day;
		$config->continuity_point = $obj->continuity_point;
		$config->about_random = $obj->about_random;
		$config->minimum = $obj->minimum;
		$config->maximum = $obj->maximum;
		$config->about_lottery = $obj->about_lottery;
		$config->lottery = $obj->lottery;
		$config->brithday_point = $obj->brithday_point;
		$config->use_document = $obj->use_document;
		$config->use_random_sm = $obj->use_random_sm;
		$config->random_small_win = $obj->random_small_win;
		$config->random_small_point_f = $obj->random_small_point_f;
		$config->random_small_point_s = $obj->random_small_point_s;
		$config->random_big_point_f = $obj->random_big_point_f;
		$config->random_big_point_s = $obj->random_big_point_s;
		$config->giftname = $obj->giftname;
		$config->manygiftlist = $obj->manygiftlist;
		$config->gift_random = $obj->gift_random;
		$config->use_cache = $obj->attendance_use_cache === 'yes' ? 'yes' : 'no';
		$config->greeting_list = $obj->greeting_list;
		$config->start_rand_time = $obj->start_rand_time;

		if ($config->start_rand_time && (intval($config->start_rand_time) > 23 || intval($config->start_rand_time) <= 0))
		{
			unset($config->start_rand_time);
			return $this->makeObject(-1, '랜덤 숫자는 1에서 23 사이의 숫자를 입력하셔야 합니다.');
		}

		$end_of_year = date('t', mktime(0, 0, 0, 2, 1, zDate(date('YmdHis'), 'Y'))) == 29 ? 366 : 365;
		$end_of_month = date('t', mktime(0, 0, 0, zDate(date('YmdHis'), 'm'), 1, zDate(date('YmdHis'), 'Y')));

		if ($obj->continuity_day < 2) $config->continuity_day = 2;
		if ($obj->diligence_yearly >= $end_of_year || $obj->diligence_yearly < 32) $config->diligence_yearly = $end_of_year - 1;
		if ($obj->diligence_monthly >= $end_of_month || $obj->diligence_monthly < 8) $config->diligence_monthly = $end_of_month - 1;
		if ($obj->diligence_weekly >= 7 || $obj->diligence_weekly < 1) $config->diligence_weekly = 6;
		if (!$obj->allow_duplicaton_ip_count) $config->allow_duplicaton_ip_count = 3;

		$oModuleController = \ModuleController::getInstance();
		$output = $oModuleController->updateModuleConfig('attendance', $config);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_updated');

		if (!in_array(\Context::getRequestMethod(), ['XMLRPC', 'JSON']))
		{
			$returnUrl = \Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAttendanceAdminConfig');
			header('location: ' . $returnUrl);
			return;
		}
	}

	public function procAttendanceAdminUpdatePoint(): void
	{
		$action = \Context::get('action');
		$member_srl = \Context::get('member_srl');
		$point = \Context::get('point') ?: 0;

		$oAttendanceModel = new \Rhymix\Modules\Attendance\Models\Attendance();
		$oPointController = \PointController::getInstance();
		$oPointModel = \PointModel::getInstance();

		$personal_point = $oPointModel->getPoint($member_srl);
		$total_point = $oAttendanceModel->getTotalPoint($member_srl);

		if ($action === 'add')
		{
			$total_point += $point;
			$personal_point += $point;
		}
		elseif ($action === 'minus')
		{
			$total_point -= $point;
			$personal_point -= $point;
		}
		else
		{
			$total_point = $point;
			$personal_point = $point;
		}

		if ($point == 0)
		{
			$this->setMessage('attend_no_zero');
		}
		else
		{
			(new \Rhymix\Modules\Attendance\Controllers\Index())->updateTotal($member_srl, null, null, $total_point, null);
			$oPointController->setPoint($member_srl, $personal_point, 'update');
			if ($action === 'update')
			{
				$this->setMessage('attend_updated_points');
			}
		}

		$oAttendanceModel->clearCacheByMemberSrl($member_srl);
	}

	public function procAttendanceAdminFixTotalData(): void
	{
		$oAttendanceModel = new \Rhymix\Modules\Attendance\Models\Attendance();
		$oAttendanceAdminModel = new \Rhymix\Modules\Attendance\Models\AdminAttendance();
		$oAttendanceController = new \Rhymix\Modules\Attendance\Controllers\Index();

		$obj = \Context::getRequestVars();
		$continuity = new \stdClass;
		$continuity->point = 0;
		$continuity->data = (!$obj->continuity || $obj->continuity < 1) ? 1 : $obj->continuity;

		$oAttendanceAdminModel->deleteAllAttendanceTotalData($obj->member_srl);
		$points = $oAttendanceAdminModel->getTotalPoint($obj->member_srl);
		$sum = 0;
		foreach ($points->data as $val)
		{
			$sum += $val->today_point;
		}
		$attendance = $oAttendanceModel->getTotalAttendance($obj->member_srl);
		$oAttendanceController->insertTotal($obj->member_srl, $continuity, $attendance, $sum, $obj->selected_date . '000000');

		$oAttendanceAdminModel->fixYearMonthWeek($obj);
		$oAttendanceModel->clearCacheByMemberSrl($obj->member_srl);
	}

	public function procAttendanceAdminFixAttendanceData(): void
	{
		$oAttendanceModel = new \Rhymix\Modules\Attendance\Models\Attendance();
		$oAttendanceAdminModel = new \Rhymix\Modules\Attendance\Models\AdminAttendance();
		$oAttendanceController = new \Rhymix\Modules\Attendance\Controllers\Index();
		$oPointModel = \PointModel::getInstance();
		$oPointController = \PointController::getInstance();

		$obj = \Context::getRequestVars();
		$output = $oAttendanceAdminModel->getDuplicatedData($obj->member_srl, $obj->selected_date);
		$j = 1;
		$sum_of_point = 0;
		$today_point = 0;
		$greetings = '';
		$regdate = '';
		foreach ($output->data as $val)
		{
			if ($j === 1)
			{
				$today_point = $val->today_point;
				$greetings = $val->greetings;
				$regdate = $val->regdate;
			}
			$sum_of_point += $val->today_point;
			$j++;
		}
		$sum_of_point -= $today_point;

		$oAttendanceAdminModel->deleteDuplicatedData($obj->member_srl, $obj->selected_date);

		$member_info = \MemberModel::getInstance()->getMemberInfoByMemberSrl($obj->member_srl);
		$my_point = $oPointModel->getPoint($member_info->member_srl) - $today_point;
		$oPointController->setPoint($member_info->member_srl, $my_point, 'update');

		$week = $oAttendanceModel->getWeek($obj->selected_date);
		$week_data = $oAttendanceModel->getWeeklyData($obj->member_srl, $week);
		$week_data->weekly = $oAttendanceModel->getWeeklyAttendance($obj->member_srl, $week) + 1;
		$week_data->weekly_point = $week_data->weekly_point - $today_point;
		$oAttendanceController->updateWeekly($obj->member_srl, $week, $week_data->weekly, $week_data->weekly_point, $regdate);

		$year_month = substr($obj->selected_date, 0, 6);
		$monthly_data = $oAttendanceModel->getMonthlyAttendance($obj->member_srl, $year_month);
		$monthly_data->monthly = $oAttendanceModel->getMonthlyData($year_month, $obj->member_srl) + 1;
		$monthly_data->monthly_point = $monthly_data->monthly_point - $today_point;
		$oAttendanceController->updateMonthly($obj->member_srl, $year_month, $monthly_data->monthly, $monthly_data->monthly_point, $regdate);

		$year = substr($obj->selected_date, 0, 4);
		$yearly_data = $oAttendanceModel->getYearlyAttendance($obj->member_srl, $year);
		$yearly_data->yearly = $oAttendanceModel->getYearlyData($year, $obj->member_srl) + 1;
		$yearly_data->yearly_point = $yearly_data->yearly_point - $today_point;
		$oAttendanceController->updateYearly($obj->member_srl, $year, $yearly_data->yearly, $yearly_data->yearly_point, $regdate);

		$total_data = $oAttendanceModel->getTotalData($obj->member_srl);
		$total_data->total = $oAttendanceModel->getTotalAttendance($obj->member_srl) + 1;
		$total_data->total_point = $total_data->total_point - $today_point;
		$continuity = new \stdClass();
		$continuity->data = $total_data->continuity;
		$continuity->point = $total_data->continuity_point;
		$oAttendanceController->updateTotal($obj->member_srl, $continuity, $total_data->total, $total_data->total_point, $total_data->regdate);

		$args = new \stdClass();
		$args->attendance_srl = getNextSequence();
		$args->regdate = $regdate;
		$args->member_srl = $obj->member_srl;
		$args->greetings = $greetings;
		$args->today_point = $today_point;
		$output = executeQuery('attendance.insertAttendance', $args);
		if (!$output->toBool())
		{
			return;
		}
		$this->setMessage('attend_fixed_doublecheck');

		$oAttendanceModel->clearCacheByMemberSrl($obj->member_srl);
	}

	public function procAttendanceAdminInitAll(): void
	{
		$oAttendanceModel = new \Rhymix\Modules\Attendance\Models\Attendance();
		$oAttendanceAdminModel = new \Rhymix\Modules\Attendance\Models\AdminAttendance();
		$oPointController = \PointController::getInstance();
		$obj = \Context::getRequestVars();

		$continuity = new \stdClass;
		$continuity->point = 0;
		$continuity->data = (!$obj->continuity || $obj->continuity < 1) ? 1 : $obj->continuity;

		$output = executeQuery('attendance.migrationGetSrlTotal');
		if (!$output->data) $output->data = [];
		foreach ($output->data as $value)
		{
			$obj->member_srl = $value->member_srl;
			$points = $oAttendanceAdminModel->getTotalPoint($obj->member_srl);
			$sum = 0;
			foreach ($points->data as $val)
			{
				$sum += $val->today_point;
			}
			$oAttendanceAdminModel->deleteAllAttendanceTotalData($obj->member_srl);
			$attendance = $oAttendanceModel->getTotalAttendance($obj->member_srl);
			(new \Rhymix\Modules\Attendance\Controllers\Index())->insertTotal($obj->member_srl, $continuity, $attendance, $sum, $obj->selected_date . '000000');
			$oPointController->setPoint($obj->member_srl, $sum, 'add');
			$oAttendanceAdminModel->fixYearMonthWeek($obj);
		}

		$oAttendanceModel->clearCache();
	}

	public function procAttendanceAdminInsertBoard(): void
	{
		$oModuleController = \ModuleController::getInstance();
		$oModuleModel = \ModuleModel::getInstance();

		$args = \Context::getRequestVars();
		$args->module = 'attendance';
		$args->mid = 'attendance';
		$info = $oModuleModel->getModuleInfoByMid('attendance');
		$args->module_srl = $info->module_srl;
		if (!$args->skin) $args->skin = 'default';

		if ($args->use_category != 'Y') $args->use_category = 'N';
		if ($args->except_notice != 'Y') $args->except_notice = 'N';
		if ($args->use_anonymous != 'Y') $args->use_anonymous = 'N';
		if ($args->consultation != 'Y') $args->consultation = 'N';
		if (!$args->order_target) $args->order_target = 'list_order';
		if (!$args->order_type) $args->order_type = 'asc';

		$output = $oModuleController->updateModule($args);
		if (!$output->toBool())
		{
			return;
		}

		$this->add('page', \Context::get('page'));
		$this->add('module_srl', $output->get('module_srl'));
		$this->setMessage('success_updated');
	}

	public function procAttendanceAdminDeleteBoard(): void
	{
		$oModuleModel = \ModuleModel::getInstance();
		$oModuleController = \ModuleController::getInstance();
		$module_info = $oModuleModel->getModuleInfoByMid('attendance');
		$oModuleController->deleteModule($module_info->module_srl);
		(new \Rhymix\Modules\Attendance\Models\Attendance())->clearCache();
	}

	public function procAttendanceAdminInsertGift()
	{
		$args = new \stdClass();
		$args->present_srl = \Context::get('present_srl');
		$output = executeQuery('attendance.updateAttendanceGift', $args);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('att_gift_success');

		if (!in_array(\Context::getRequestMethod(), ['XMLRPC', 'JSON']))
		{
			$returnUrl = \Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAttendanceAdminGift');
			header('location: ' . $returnUrl);
			return;
		}
	}

	public function procAttendanceAdminCheckData(): void
	{
		$obj = \Context::getRequestVars();
		$oAttendanceModel = new \Rhymix\Modules\Attendance\Models\Attendance();
		$config = $oAttendanceModel->getConfig();

		$g_obj = new \stdClass();
		$g_obj->about_position = 'No';
		$g_obj->greetings = '^admin_checked^';

		$r_args = new \stdClass();
		$r_args->regdate = sprintf('%s235959', $obj->check_day);
		$r_args->year = substr($obj->check_day, 0, 4);
		$r_args->year_month = substr($obj->check_day, 0, 6);
		$r_args->week = $oAttendanceModel->getWeek($obj->check_day);

		(new \Rhymix\Modules\Attendance\Controllers\Index())->insertAttendance($g_obj, $config, $obj->member_srl, $r_args);
	}

	public function procAttendanceAdminDeleteData(): void
	{
		$oPointController = \PointController::getInstance();
		$oAttendanceModel = new \Rhymix\Modules\Attendance\Models\Attendance();
		$oAttendanceController = new \Rhymix\Modules\Attendance\Controllers\Index();

		$obj = \Context::getRequestVars();
		$year = substr($obj->check_day, 0, 4);
		$year_month = substr($obj->check_day, 0, 6);
		$args = new \stdClass;
		$args->check_day = $obj->check_day;
		$args->member_srl = $obj->member_srl;

		$member_info = \MemberModel::getInstance()->getMemberInfoByMemberSrl($obj->member_srl);
		$daily_info = $oAttendanceModel->getUserAttendanceData($member_info->member_srl, $obj->check_day);

		unset($_SESSION['is_attended']);

		$week = $oAttendanceModel->getWeek($obj->check_day);
		if ($oAttendanceModel->getIsCheckedA($obj->member_srl, $obj->check_day) == 0)
		{
			return;
		}

		$oPointController->setPoint($member_info->member_srl, $daily_info->today_point, 'minus');

		if (substr($daily_info->greetings, 0, 1) === '#')
		{
			$document_srl = substr($daily_info->greetings, 1);
			\DocumentController::getInstance()->deleteDocument($document_srl, true);
		}

		$output = executeQuery('attendance.deleteAttendanceData', $args);
		if (!$output->toBool())
		{
			return;
		}

		// Preserve existing continuity and regdate — deleting one attendance record
		// must not reset the member's streak to 1.
		$existing_total = $oAttendanceModel->getTotalData($obj->member_srl);
		$continuity = new \stdClass;
		$continuity->data = max(1, (int)$existing_total->continuity);
		$continuity->point = (int)$existing_total->continuity_point;
		$regdate = $existing_total->regdate ?: sprintf('%s235959', $obj->check_day);

		if ($oAttendanceModel->isExistTotal($obj->member_srl) == 0)
		{
			$continuity->data = 1;
			$continuity->point = 0;
			$total_attendance = $oAttendanceModel->getTotalAttendance($obj->member_srl);
			$oAttendanceController->insertTotal($obj->member_srl, $continuity, $total_attendance, 0, $regdate);
		}
		else
		{
			$total_attendance = $oAttendanceModel->getTotalAttendance($obj->member_srl);
			$total_point = max(0, $oAttendanceModel->getTotalPoint($obj->member_srl) - $daily_info->today_point);
			$oAttendanceController->updateTotal($obj->member_srl, $continuity, $total_attendance, $total_point, $regdate);
		}

		if ($oAttendanceModel->isExistYearly($obj->member_srl, $year) == 0)
		{
			$yearly_data = $oAttendanceModel->getYearlyData($year, $obj->member_srl);
			$oAttendanceController->insertYearly($obj->member_srl, $yearly_data, 0, $regdate);
		}
		else
		{
			$yearly_data = $oAttendanceModel->getYearlyData($year, $obj->member_srl);
			$year_info = $oAttendanceModel->getYearlyAttendance($obj->member_srl, $year);
			$yearly_point = max(0, $year_info->yearly_point - $daily_info->today_point);
			$oAttendanceController->updateYearly($obj->member_srl, $year, $yearly_data, $yearly_point, $regdate);
		}

		if ($oAttendanceModel->isExistMonthly($obj->member_srl, $year_month) == 0)
		{
			$monthly_data = $oAttendanceModel->getMonthlyData($year_month, $obj->member_srl);
			$oAttendanceController->insertMonthly($obj->member_srl, $monthly_data, 0, $regdate);
		}
		else
		{
			$monthly_data = $oAttendanceModel->getMonthlyData($year_month, $obj->member_srl);
			$month_info = $oAttendanceModel->getMonthlyAttendance($obj->member_srl, $year_month);
			$monthly_point = max(0, $month_info->monthly_point - $daily_info->today_point);
			$oAttendanceController->updateMonthly($obj->member_srl, $year_month, $monthly_data, $monthly_point, $regdate);
		}

		if ($oAttendanceModel->isExistWeekly($obj->member_srl, $week) == 0)
		{
			$weekly_data = $oAttendanceModel->getWeeklyAttendance($obj->member_srl, $week);
			$oAttendanceController->insertWeekly($obj->member_srl, $weekly_data, 0, $regdate);
		}
		else
		{
			$weekly_data = $oAttendanceModel->getWeeklyAttendance($obj->member_srl, $week);
			$week_info = $oAttendanceModel->getWeeklyData($obj->member_srl, $week);
			$weekly_point = max(0, $week_info->weekly_point - $daily_info->today_point);
			$oAttendanceController->updateWeekly($obj->member_srl, $week, $weekly_data, $weekly_point, $regdate);
		}

		$this->setMessage('success_deleted');

		$oAttendanceModel->clearCacheByMemberSrl($obj->member_srl, 'daily', $obj->check_day);
		$oAttendanceModel->clearCacheByMemberSrl($obj->member_srl, 'weekly', $week);
		$oAttendanceModel->clearCacheByMemberSrl($obj->member_srl, 'monthly', $year_month);
		$oAttendanceModel->clearCacheByMemberSrl($obj->member_srl, 'yearly', $year);
	}
}
