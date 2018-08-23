<?PHP
/**
 * @class attendanceAdminController
 * @author BJRambo (sosifam@070805.co.kr)
 * @brief attendance module admin controller class
 **/

class attendanceAdminController extends attendance
{

	/**
	 * @brief 초기화
	 **/
	function init()
	{
	}

	function procAttendanceAdminDeleteAllData()
	{
		/*attendance admin model 객체 생성*/
		$oAttendanceAdminModel = getAdminModel('attendance');
		$obj = Context::getRequestVars();
		$oAttendanceAdminModel->deleteAllAttendanceData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceTotalData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceYearlyData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceMonthlyData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceWeeklyData($obj->member_srl);
		$this->setMessage('attend_deleted');
	}

	function procAttendanceAdminInsertAttendance()
	{
		$oModuleController = getController('module');

		$obj = Context::getRequestVars();

		$config = new stdClass;
		$config->about_admin_check = $obj->about_admin_check;
		$config->allow_duplicaton_ip_count = $obj->allow_duplicaton_ip_count;
		$config->about_auto_attend = $obj->about_auto_attend;
		$config->about_birth_day = $obj->about_birth_day;
		$config->about_birth_day_y = $obj->about_birth_day_y;
		$config->about_time_control = $obj->about_time_control;
		$config->start_time = sprintf("%02d%02d", $obj->start_hour, $obj->start_min);
		$config->end_time = sprintf("%02d%02d", $obj->end_hour, $obj->end_min);
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

		if (date('t', mktime(0, 0, 0, 02, 1, zDate(date('YmdHis'), "Y"))) == 29)
		{
			$end_of_year = 366;
		}
		else
		{
			$end_of_year = 365;
		}
		$end_of_month = date('t', mktime(0, 0, 0, zDate(date('YmdHis'), "m"), 1, zDate(date('YmdHis'), "Y")));

		if ($obj->continuity_day < 2)
		{
			$config->continuity_day = 2;
		}
		if ($obj->diligence_yearly >= $end_of_year || $obj->diligence_yearly < 32)
		{
			$config->diligence_yearly = $end_of_year - 1;
		}
		if ($obj->diligence_monthly >= $end_of_month || $obj->diligence_monthly < 8)
		{
			$config->diligence_monthly = $end_of_month - 1;
		}
		if ($obj->diligence_weekly >= 7 || $obj->diligence_weekly < 1)
		{
			$config->diligence_weekly = 6;
		}
		if (!$obj->allow_duplicaton_ip_count)
		{
			$config->allow_duplicaton_ip_count = 3;
		}

		$this->setMessage('success_updated');

		$oModuleController->updateModuleConfig('attendance', $config);

		if (!in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAttendanceAdminConfig');
			header('location: ' . $returnUrl);
			return;
		}
	}

	/**
	 * @brief 출석 포인트 변경
	 **/
	function procAttendanceAdminUpdatePoint()
	{
		$action = Context::get('action');
		$member_srl = Context::get('member_srl');
		$point = Context::get('point');
		if (!$point)
		{
			$point = 0;
		}

		$oAttendanceModel = getModel('attendance');
		$oPointController = getController('point');
		$oPointModel = getModel('point');

		//개인 포인트 꺼내오기
		$personal_point = $oPointModel->getPoint($member_srl);

		//총 출석 포인트 추출
		$total_point = $oAttendanceModel->getTotalPoint($member_srl);

		if ($action == 'add')
		{
			$total_point += $point;
			$personal_point += $point;
		}
		else if ($action == 'minus')
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
			//총 출석포인트 기록
			getController('attendance')->updateTotal($member_srl, $continuity = null, $total_attendance = null, $total_point, $regdate = null);
			//개인포인트 기록
			$oPointController->setPoint($member_srl, $personal_point, 'update');
			if ($action == 'update')
			{
				$this->setMessage('attend_updated_points');
			}
		}

		// 캐시 비우기
		$oAttendanceModel->clearCacheByMemberSrl($member_srl);
	}


	/**
	 * @brief 출석 정보 수정
	 **/
	function procAttendanceAdminFixTotalData()
	{
		$oAttendanceModel = getModel('attendance');
		$oAttendanceAdminModel = getAdminModel('attendance');
		$oAttendanceController = getController('attendance');

		$obj = Context::getRequestVars();
		$continuity = new stdClass;
		$continuity->point = 0;
		if (!$obj->continuity)
		{
			$continuity->data = 1;
		}
		else
		{
			if ($obj->continuity < 1)
			{
				$continuity->data = 1;
			}
			else
			{
				$continuity->data = $obj->continuity;
			}
		}
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

		// 캐시 비우기
		$oAttendanceModel->clearCacheByMemberSrl($obj->member_srl);
	}

	/**
	 * @brief 중복출석 수정
	 **/
	function procAttendanceAdminFixAttendanceData()
	{
		//모델 연동
		$oAttendanceModel = getModel('attendance');
		$oAttendanceAdminModel = getAdminModel('attendance');
		$oAttendnaceController = getController('attendance');
		//포인트 모듈 연동
		$oPointModel = getModel('point');
		$oPointController = getController('point');
		$obj = Context::getRequestVars();
		$output = $oAttendanceAdminModel->getDuplicatedData($obj->member_srl, $obj->selected_date);
		$j = 1;
		$sum_of_point = 0;
		foreach ($output->data as $val)
		{
			if ($j == 1)
			{
				$today_point = $val->today_point;
				$greetings = $val->greetings;
				$regdate = $val->regdate;
			}
			$sum_of_point += $val->today_point;
			$j++;
		}
		//TODO : check again
		$sum_of_point -= $today_point;
		//중복된 출석내용 제거
		$oAttendanceAdminModel->deleteDuplicatedData($obj->member_srl, $obj->selected_date);
		//비정상적으로 지급된 포인트 차감(포인트모듈 연동)
		$oMemberModel = getModel('member');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($obj->member_srl);
		$my_point = $oPointModel->getPoint($member_info->member_srl);
		$my_point -= $today_point;
		$oPointController->setPoint($member_info->member_srl, $my_point, 'update');
		//주간 출석정보 수정
		$week = $oAttendanceModel->getWeek($obj->selected_date);
		$week_data = $oAttendanceModel->getWeeklyData($obj->member_srl, $week);
		$week_data->weekly = $oAttendanceModel->getWeeklyAttendance($obj->member_srl, $week) + 1;
		$week_data->weekly_point = $week_data->weekly_point - $today_point;
		$oAttendnaceController->updateWeekly($obj->member_srl, $week, $week_data->weekly, $week_data->weekly_point, $regdate);
		//월간 출석정보 수정
		$year_month = substr($obj->selected_date, 0, 6);
		$monthly_data = $oAttendanceModel->getMonthlyAttendance($obj->member_srl, $year_month);
		$monthly_data->monthly = $oAttendanceModel->getMonthlyData($year_month, $obj->member_srl) + 1;
		$monthly_data->monthly_point = $monthly_data->monthly_point - $today_point;
		$oAttendnaceController->updateMonthly($obj->member_srl, $year_month, $monthly_data->monthly, $monthly_data->monthly_point, $regdate);
		//연간 출석정보 수정
		$year = substr($obj->selected_date, 0, 4);
		$yearly_data = $oAttendanceModel->getYearlyAttendance($obj->member_srl, $year);
		$yearly_data->yearly = $oAttendanceModel->getYearlyData($year, $obj->member_srl) + 1;
		$yearly_data->yearly_point = $yearly_data->yearly_point - $today_point;
		$oAttendnaceController->updateYearly($obj->member_srl, $year, $yearly_data->yearly, $yearly_data->yearly_point, $regdate);
		//총 출석정보 수정
		$total_data = $oAttendanceModel->getTotalData($obj->member_srl);
		$total_data->total = $oAttendanceModel->getTotalAttendance($obj->member_srl) + 1;
		$total_data->total_point = $total_data->total_point - $today_point;
		$continuity = new stdClass();
		$continuity->data = $total_data->continuity;
		$continuity->point = $total_data->continuity_point;
		$oAttendnaceController->updateTotal($obj->member_srl, $continuity, $total_data->total, $total_data->total_point, $total_data->regdate);
		//정상적인 출석정보 삽입
		$args = new stdClass();
		$args->attendance_srl = getNextSequence();
		$args->regdate = $regdate;
		$args->member_srl = $obj->member_srl;
		$args->greetings = $greetings;
		$args->today_point = $today_point;
		$output = executeQuery('attendance.insertAttendance', $args);
		if (!$output->toBool())
		{
			return $output;
		}
		$this->setMessage('attend_fixed_doublecheck');

		// 캐시 비우기
		$oAttendanceModel->clearCacheByMemberSrl($obj->member_srl);
	}

	/**
	 * @brief 출석포인트 재계산
	 **/
	function procAttendanceAdminInitAll()
	{
		$oAttendanceModel = getModel('attendance');
		$oAttendanceAdminModel = getAdminModel('attendance');
		$oPointController = getController('point');
		$obj = Context::getRequestVars();
		$continuity = new stdClass;
		$continuity->point = 0;
		if (!$obj->continuity)
		{
			$continuity->data = 1;
		}
		else
		{
			if ($obj->continuity < 1)
			{
				$continuity->data = 1;
			}
			else
			{
				$continuity->data = $obj->continuity;
			}
		}
		$output = executeQuery('attendance.migrationGetSrlTotal');
		if (!$output->data)
		{
			$output->data = array();
		}
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
			getController('attendance')->insertTotal($obj->member_srl, $continuity, $attendance, $sum, $obj->selected_date . '000000');

			//포인트 지급            
			$oPointController->setPoint($obj->member_srl, $sum, 'add');

			$oAttendanceAdminModel->fixYearMonthWeek($obj);
		}

		// 캐시 비우기
		$oAttendanceModel->clearCache();
	}

	/**
	 * @brief 출석부 게시판 설정값 등록
	 **/
	function procAttendanceAdminInsertBoard()
	{
		// module 모듈의 model/controller 객체 생성
		$oModuleController = getController('module');
		$oModuleModel = getModel('module');

		// 게시판 모듈의 정보 설정
		$args = Context::getRequestVars();
		$args->module = 'attendance';
		$args->mid = 'attendance';
		$info = $oModuleModel->getModuleInfoByMid('attendance');
		$args->module_srl = $info->module_srl;
		if (!$args->skin)
		{
			$args->skin = 'default';
		}

		// 기본 값외의 것들을 정리
		if ($args->use_category != 'Y')
		{
			$args->use_category = 'N';
		}
		if ($args->except_notice != 'Y')
		{
			$args->except_notice = 'N';
		}
		if ($args->use_anonymous != 'Y')
		{
			$args->use_anonymous = 'N';
		}
		if ($args->consultation != 'Y')
		{
			$args->consultation = 'N';
		}
		if (!$args->order_target)
		{
			$args->order_target = 'list_order';
		}
		if (!$args->order_type)
		{
			$args->order_type = 'asc';
		}

		//설정 업데이트
		$output = $oModuleController->updateModule($args);
		$msg_code = 'success_updated';

		if (!$output->toBool())
		{
			return $output;
		}

		$this->add('page', Context::get('page'));
		$this->add('module_srl', $output->get('module_srl'));
		$this->setMessage($msg_code);
	}

	/**
	 * @brief 출석부 게시판 삭제
	 **/
	function procAttendanceAdminDeleteBoard()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		$module_info = $oModuleModel->getModuleInfoByMid('attendance');
		$oModuleController->deleteModule($module_info->module_srl);

		// 캐시 비우기
		// TODO(BJRambo): Do not load the attendanceModel class.
		getModel('attendance')->clearCache();
	}

	function procAttendanceAdminInsertGift()
	{
		$args = new stdCLass();
		$present_srl = Context::get('present_srl');
		$args->present_srl = $present_srl;
		$output = executeQuery('attendance.updateAttendanceGift', $args);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('att_gift_success');

		if (!in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAttendanceAdminGift');
			header('location: ' . $returnUrl);
			return;
		}
	}


	function procAttendanceAdminCheckData()
	{
		$obj = Context::getRequestVars();
		$oAttendanceModel = getModel('attendance');
		$config = $oAttendanceModel->getConfig();

		$g_obj = new stdClass();

		$g_obj->about_position = 'No';
		$g_obj->greetings = '^admin_checked^';
		$member_srl = $obj->member_srl;

		//기록될 날짜부터
		$r_args = new stdClass();
		$r_args->regdate = sprintf('%s235959', $obj->check_day);
		$r_args->year = substr($obj->check_day, 0, 4);
		$r_args->year_month = substr($obj->check_day, 0, 6);
		$r_args->week = $oAttendanceModel->getWeek($obj->check_day);

		$oAttendanceController = getController('attendance');
		$output = $oAttendanceController->insertAttendance($g_obj, $config, $member_srl, $r_args);
	}

	/**
	 * @brief 관리자 임의 출석 제거
	 **/
	function procAttendanceAdminDeleteData()
	{
		//포인트 모듈 연동
		$oPointController = getController('point');

		/*attendance model 객체 생성*/
		$oAttendanceModel = getModel('attendance');
		$oAttendanceController = getcontroller('attendance');

		//넘겨받고
		$obj = Context::getRequestVars();
		$year = substr($obj->check_day, 0, 4);
		$year_month = substr($obj->check_day, 0, 6);
		$args = new stdClass;
		$args->check_day = $obj->check_day;
		$args->member_srl = $obj->member_srl;

		$oMemberModel = getModel('member');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($obj->member_srl);

		/*출석당일 포인트 꺼내오기*/
		$daily_info = $oAttendanceModel->getUserAttendanceData($member_info->member_srl, $obj->check_day);

		//If delete Attendace, should the is_attended session initialize.
		$_SESSION['is_attended'] = '0';

		$week = $oAttendanceModel->getWeek($obj->check_day);
		if ($oAttendanceModel->getIsCheckedA($obj->member_srl, $obj->check_day) != 0)
		{
			$oPointController->setPoint($member_info->member_srl, $daily_info->today_point, 'minus');

			if (substr($daily_info->greetings, 0, 1) == '#')
			{
				$length = strlen($daily_info->greetings) - 1;
				$document_srl = substr($daily_info->greetings, 1, $length);
				$oDocumentController = getController('document');
				$oDocumentController->deleteDocument($document_srl, true);
			}

			$output = executeQuery("attendance.deleteAttendanceData", $args);
			if (!$output->toBool())
			{
				return $output;
			}

			$regdate = sprintf("%s235959", $obj->check_day);
			$continuity = new stdClass;
			$continuity->data = 1;
			$continuity->point = 0;
			if ($oAttendanceModel->isExistTotal($obj->member_srl) == 0)
			{
				$total_attendance = $oAttendanceModel->getTotalAttendance($obj->member_srl);
				$oAttendanceController->insertTotal($obj->member_srl, $continuity, $total_attendance, 0, $regdate);
			}
			else
			{
				$total_attendance = $oAttendanceModel->getTotalAttendance($obj->member_srl);
				$total_point = $oAttendanceModel->getTotalPoint($obj->member_srl);
				$total_point -= $daily_info->today_point;
				if ($total_point < 0)
				{
					$total_point = 0;
				}
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
				$yearly_point = $year_info->yearly_point;
				$yearly_point -= $daily_info->today_point;
				if ($yearly_point < 0)
				{
					$yearly_point = 0;
				}
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
				$monthly_point = $month_info->monthly_point;
				$monthly_point -= $daily_info->today_point;
				if ($monthly_point < 0)
				{
					$monthly_point = 0;
				}
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
				$weekly_point = $week_info->weekly_point;
				$weekly_point -= $daily_info->today_point;
				if ($weekly_point < 0)
				{
					$weekly_point = 0;
				}
				$oAttendanceController->updateWeekly($obj->member_srl, $week, $weekly_data, $weekly_point, $regdate);
			}
			$this->setMessage("success_deleted");
		}

		// delete cache.
		$oAttendanceModel->clearCacheByMemberSrl($obj->member_srl, 'daily', $obj->check_day);
		$oAttendanceModel->clearCacheByMemberSrl($obj->member_srl, 'weekly', $week);
		$oAttendanceModel->clearCacheByMemberSrl($obj->member_srl, 'monthly', $year_month);
		$oAttendanceModel->clearCacheByMemberSrl($obj->member_srl, 'yearly', $year);
	}
}
