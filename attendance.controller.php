<?PHP
/**
 * @class attendanceController
 * @author BJRambo (sosifam@070805.co.kr)
 * @brief attendance module controller class
 **/

class attendanceController extends attendance
{

	/**
	 * @brief 초기화
	 **/
	function init()
	{
	}

	/**
	 * @return Object
	 */
	function procAttendanceModifyContinuous()
	{
		$logged_info = Context::get('logged_info');

		if ($logged_info->is_admin != 'Y')
		{
			return $this->makeObject(-1, '관리자만 설정이 가능합니다.');
		}

		$obj = Context::getRequestVars();
		$member_srl = $obj->member_srl;
		if (!$member_srl)
		{
			return $this->makeObject(-1, '회원번호는 필수 입니다.');
		}
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->continuity = $obj->continuity;
		$output = executeQuery('attendance.updateTotal', $args);
		if ($output->toBool())
		{
			$this->setMessage('수정완료');
		}
		else
		{
			return $this->makeObject(-1, 'msg_do_not_attendance');
		}

		$oAttendanceModel = getModel('attendance');
		$oAttendanceModel->clearCacheByMemberSrl($member_srl);

		if (!in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', 'attendance', 'act', 'dispAttendanceModifyContinuous', 'member_srl', $member_srl);
			header('location: ' . $returnUrl);
			return;
		}
	}

	/**
	 * @brief 출석부 기록
	 * @return Object
	 **/
	function procAttendanceInsertAttendance()
	{
		$today = zDate(date('YmdHis'), "Ymd");

		$oDB = DB::getInstance();
		$oDB->begin();

		if ($_SESSION['is_attended'] === $today)
		{
			return $this->makeObject(-1, 'attend_already_checked');
		}

		$_SESSION['is_attended'] = $today;

		/*attendance model 객체 생성*/
		$oAttendanceModel = getModel('attendance');

		$logged_info = Context::get('logged_info');

		$config = $oAttendanceModel->getConfig();

		//관리자 출석이 허가가 나지 않았다면,
		if ($config->about_admin_check != 'yes' && $logged_info->is_admin == 'Y')
		{
			$_SESSION['is_attended'] = 0;
			return $this->makeObject(-1, '관리자는 출석할 수 없습니다.');
		}

		/*출석이 되어있는지 확인 : 오늘자 로그인 회원의 DB기록 확인*/
		if ($oAttendanceModel->getIsChecked($logged_info->member_srl) > 0)
		{
			$_SESSION['is_attended'] = 0;
			return $this->makeObject(-1, 'attend_already_checked');
		}

		$is_logged = Context::get('is_logged');
		if (!$is_logged)
		{
			return $this->makeObject(-1, '로그인 사용자만 출석 할 수 있습니다.');
		}
		if ($oAttendanceModel->getIsChecked($logged_info->member_srl) > 0 && $oAttendanceModel->availableCheck() != 0)
		{
			return $this->makeObject(-1, '일시적인 오류로 출석 할 수 없습니다.');
		}

		//ip중복 횟수 확인
		$ip_count = $oAttendanceModel->getDuplicateIpCount($today, $_SERVER['REMOTE_ADDR']);
		if ($ip_count >= $config->allow_duplicaton_ip_count)
		{
			$_SESSION['is_attended'] = 0;
			return $this->makeObject(-1, 'attend_allow_duplicaton_ip_count');
		}

		$g_obj = Context::getRequestVars();

		//인사말 필터링('#'시작문자 '^'시작문자 필터링)
		if (preg_match("/^\#/", $g_obj->greetings))
		{
			return $this->makeObject(-1, 'attend_greetings_error');
		}

		$output = $this->insertAttendance($g_obj, $config);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		if ($output->toBool())
		{
			$this->setMessage('att_success');
		}
		else
		{
			return $this->makeObject(-1, '출석을 하지 못했습니다.');
		}

		$oDB->commit();

		// TODO(BJRambo):Change the way to redirect url.
		if (!in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', 'attendance');
			header('location: ' . $returnUrl);
			return;
		}
	}


	/**
	 * @brief 출석부 기록 함수
	 */

	// Why use the static function ?
	/**
	 * Insert of attendance
	 * @param object $g_obj
	 * @param $config
	 * @param $member_srl
	 * @param Object $r_args
	 * @return Object|bool
	 */
	function insertAttendance($g_obj, $config, $member_srl = null, $r_args = null)
	{
		$oMemberModel = getModel('member');
		$oAttendanceModel = getModel('attendance');
		if ($member_srl)
		{
			$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
		}
		else
		{
			$member_info = Context::get('logged_info');
		}

		if ($r_args !== null)
		{
			$todayDateTime = $r_args->regdate;
			$today = zDate($todayDateTime, "Ymd");
			$year = $r_args->year;
			$year_month = $r_args->year_month;
			$yesterday = null;
		}
		else
		{
			$todayDateTime = date('YmdHis');
			$today = zDate($todayDateTime,"Ymd");
			$year = zDate($todayDateTime, "Y");
			$year_month = zDate($todayDateTime, "Ym");
			$yesterday = zDate(date("YmdHis", strtotime("-1 day")), "Ymd");
		}

		$oPointController = getController('point');
		$obj = new stdClass();
		$obj->continuity_day = $config->continuity_day;
		$obj->continuity_point = $config->continuity_point;
		$obj->today_point = $config->add_point;
		$obj->greetings = $g_obj->greetings;
		$obj->member_srl = $member_info->member_srl;
		$position = $oAttendanceModel->getPositionData($today);

		if ($position == 0)
		{
			$obj->today_point += $config->first_point;
		}
		else if ($position == 1)
		{
			$obj->today_point += $config->second_point;
		}
		else if ($position == 2)
		{
			$obj->today_point += $config->third_point;
		}

		$yesterday_continuity_data = $oAttendanceModel->isExistContinuity($member_info->member_srl, $yesterday);

		if ($yesterday_continuity_data > 0)
		{
			$continuity = $oAttendanceModel->getContinuityData($member_info->member_srl, $yesterday);
			if ($continuity->data + 1 >= $obj->continuity_day && $obj->continuity_day != 0 && $config->about_continuity == 'yes')
			{
				$obj->today_point += $obj->continuity_point;
				$continuity->point = $obj->continuity_point;
			}

			// If insert attendance member in admin page, Initialize the continuity days.
			if ($r_args)
			{
				$continuity->data = 1;
			}
			else
			{
				$continuity->data++;
			}
		}
		else
		{
			$continuity = new stdClass();
			$continuity->data = 1;
			$obj->perfect_m = 'N';
		}
		$obj->a_continuity = $continuity->data;

		if ($config->about_target == 'yes')
		{
			if ($today == $config->target_day)
			{
				$obj->today_point += $config->target_point;
				$obj->present_y = 'N';
			}
		}
		elseif ($config->about_target == 'gift')
		{
			$todayGiftCount = $oAttendanceModel->getTodayGiftCount($today);
			if ($todayGiftCount <= $config->manygiftlist && $today == $config->target_day)
			{
				$intrand = rand(1, 100);
				if ($intrand <= $config->gift_random)
				{
					$gift_args = new stdClass();
					$gift_args->present_srl = getNextSequence();
					$gift_args->member_srl = $member_info->member_srl;
					$gift_args->present = $config->giftname;
					$gift_args->sender = 'N';
					$output_gift = executeQuery("attendance.insertPresent", $gift_args);
					if ($output_gift->toBool())
					{
						$obj->present_y = 'Y';
					}
					else
					{
						return $output_gift;
					}
				}
			}
			else
			{
				$obj->present_y = 'N';
			}
		}
		else
		{
			$obj->present_y = 'N';
		}

		$attendRegularly = $oAttendanceModel->isPerfect($member_info->member_srl, $today, false);
		if ($attendRegularly->yearly_perfect == true)
		{
			$obj->today_point += $config->yearly_point;
		}
		if ($attendRegularly->monthly_perfect == true)
		{
			$obj->today_point += $config->monthly_point;
		}

		$week = $oAttendanceModel->getWeek($today);
		$weekly_data = $oAttendanceModel->getWeeklyData($member_info->member_srl, $week);
		if ($weekly_data->weekly == 6)
		{
			$obj->today_point += $config->weekly_point;
		}

		if ($config->about_diligence_yearly == 'yes')
		{
			if ($oAttendanceModel->checkYearlyDiligence($member_info->member_srl, $config->diligence_yearly - 1, null) === 1)
			{
				$obj->today_point += $config->diligence_yearly_point;
			}
		}
		if ($config->about_diligence_monthly == 'yes')
		{
			if ($oAttendanceModel->checkMonthlyDiligence($member_info->member_srl, $config->diligence_monthly - 1, null) === 1)
			{
				$obj->today_point += $config->diligence_monthly_point;
			}
		}
		if ($config->about_diligence_weekly == 'yes')
		{
			if ($oAttendanceModel->checkWeeklyDiligence($member_info->member_srl, $config->diligence_weekly - 1, null) === 1)
			{
				$obj->today_point += $config->diligence_weekly_point;
			}
		}

		if ($config->about_random == 'yes' && $config->minimum <= $config->maximum && $config->minimum >= 0 && $config->maximum >= 0 && $config->use_random_sm == 'no')
		{
			$randNumber = mt_rand($config->minimum, $config->maximum);
			if ($config->about_lottery == 'yes' && $config->lottery > 0 && $config->lottery <= 100)
			{
				$win = mt_rand(1, 100);
				if ($win <= $config->lottery)
				{
					$obj->today_point += $randNumber;
					$obj->today_random = $randNumber;
					$obj->att_random_set = 0;
				}
				else
				{
					$obj->today_point;
					$obj->today_random = 0;
					$obj->att_random_set = 0;
				}
			}
			else
			{
				$obj->today_point += $randNumber;
				$obj->today_random = $randNumber;
				$obj->att_random_set = 0;
			}
		}
		elseif ($config->about_random == 'yes' && $config->random_small_point_f <= $config->random_small_point_s && $config->random_small_point_f >= 0 && $config->random_small_point_s >= 0 && $config->use_random_sm == 'yes')
		{
			if ($config->about_lottery == 'yes' && $config->lottery > 0 && $config->lottery <= 100)
			{
				$win = mt_rand(1, 100);
				if ($win <= $config->lottery)
				{
					if ($win <= $config->lottery && $win >= $config->random_small_win)
					{
						$randNumber = mt_rand($config->random_small_point_f, $config->random_small_point_s);
						$obj->today_point += $randNumber;
						$obj->today_random = $randNumber;
						$obj->att_random_set = 0;
					}
					elseif ($win < $config->random_small_win)
					{
						$randNumber = mt_rand($config->random_big_point_f, $config->random_big_point_s);
						$obj->today_point += $randNumber;
						$obj->today_random = $randNumber;
						$obj->att_random_set = 1;
					}
				}
				else
				{
					$obj->today_point;
					$obj->today_random = 0;
					$obj->att_random_set = 0;
				}
			}
			else
			{
				$randNumber = mt_rand($config->random_small_point_f, $config->random_small_point_s);
				$obj->today_point += $randNumber;
				$obj->today_random = $randNumber;
				$obj->att_random_set = 0;
			}
		}
		else
		{
			$obj->today_point;
			$obj->att_random_set = '0';
		}


		if ($config->about_birth_day == 'yes')
		{
			$birthdays = substr($member_info->birthday, 4, 4);
			$todays = substr($today, 4, 4);

			if ($todays == $birthdays)
			{
				$obj->today_point += $config->brithday_point;
			}
			else
			{
				$obj->today_point;
			}
		}
		else
		{
			$obj->today_point;
		}

		if (!$member_info->member_srl)
		{
			return $this->makeObject(-1, '로그인 사용자만 가능합니다.');
		}

		$module_info = $oAttendanceModel->getAttendanceInfo();
		if (!$module_info->module_srl)
		{
			return $this->makeObject(-1, 'attend_no_board');
		}

		if ($config->use_document == 'yes')
		{
			/** @var $oDocumentController documentController */
			$oDocumentController = getController('document');

			if (strlen($obj->greetings) > 0 && $obj->greetings != '^auto^')
			{
				$d_obj = new stdClass;
				$d_obj->content = $obj->greetings;
				$d_obj->nick_name = $member_info->nick_name;
				$d_obj->email_address = $member_info->email_address;
				$d_obj->homepage = $member_info->homepage;
				$d_obj->is_notice = 'N';
				$d_obj->module_srl = $module_info->module_srl;
				$d_obj->allow_comment = 'Y';
				$output = $oDocumentController->insertDocument($d_obj, false);
				if (!$output->get('document_srl'))
				{
					return $this->makeObject(-1, 'attend_error_no_greetings');
				}
				$obj->greetings = "#" . $output->get('document_srl');
			}
		}

		$obj->ipaddress = $_SERVER['REMOTE_ADDR'];
		$obj->attendance_srl = getNextSequence();
		$obj->regdate = zDate($todayDateTime,"YmdHis");

		$output = executeQuery("attendance.insertAttendance", $obj);

		$trigger_obj = new stdClass();
		$trigger_obj->regdate = $obj->regdate;
		$trigger_obj->ipaddress = $obj->ipaddress;
		$trigger_obj->today_point = $obj->today_point;
		$trigger_obj->greetings = $obj->greetings;
		$trigger_obj->member_srl = $obj->member_srl;

		ModuleHandler::triggerCall('attendance.insertAttendance', 'after', $trigger_obj);

		if ($obj->today_point != 0 && $member_info->member_srl)
		{
			$oPointController->setPoint($member_info->member_srl, $obj->today_point, 'add');
		}
		$selfOutput = self::addTotalDataUpdate($member_info, $year, $year_month, $obj, $continuity);
		if ($selfOutput !== true)
		{
			return $selfOutput;
		}
		return $output;
	}

	function addTotalDataUpdate($member_info, $year, $year_month, $obj, $continuity)
	{
		/** @var attendanceModel $oAttendanceModel */
		$regdate = $obj->regdate;
		$oAttendanceModel = getModel('attendance');
		if ($oAttendanceModel->isExistTotal($member_info->member_srl) == 0)
		{
			$total_attendance = $oAttendanceModel->getTotalAttendance($member_info->member_srl);
			$totalOutput = $this->insertTotal($member_info->member_srl, $continuity, $total_attendance, $obj->today_point, $regdate);
			if (!$totalOutput->toBool())
			{
				return $totalOutput;
			}
		}
		else
		{
			$total_attendance = $oAttendanceModel->getTotalAttendance($member_info->member_srl);
			$total_point = $oAttendanceModel->getTotalPoint($member_info->member_srl);
			$total_point += $obj->today_point;
			$totalOutput = $this->updateTotal($member_info->member_srl, $continuity, $total_attendance, $total_point, $regdate);
			if (!$totalOutput->toBool())
			{
				return $totalOutput;
			}
		}

		if ($oAttendanceModel->isExistYearly($member_info->member_srl, $year) == 0)
		{
			$yearly_data = $oAttendanceModel->getYearlyData($year, $member_info->member_srl);
			$yearly_point = $obj->today_point;
			$yearlyOutput = $this->insertYearly($member_info->member_srl, $yearly_data, $yearly_point, $regdate);
			if (!$yearlyOutput->toBool())
			{
				return $yearlyOutput;
			}
		}
		else
		{
			$yearly_data = $oAttendanceModel->getYearlyData($year, $member_info->member_srl);
			$year_info = $oAttendanceModel->getYearlyAttendance($member_info->member_srl, $year);
			$yearly_point = $year_info->yearly_point;
			$yearly_point += $obj->today_point;
			$yearlyOutput = $this->updateYearly($member_info->member_srl, $year, $yearly_data, $yearly_point, $regdate);
			if (!$yearlyOutput->toBool())
			{
				return $yearlyOutput;
			}
		}

		if ($oAttendanceModel->isExistMonthly($member_info->member_srl, $year_month) == 0)
		{
			$monthlyCount = $oAttendanceModel->getMonthlyData($year_month, $member_info->member_srl);
			$monthly_point = $obj->today_point;
			$monthlyOutput = $this->insertMonthly($member_info->member_srl, $monthlyCount, $monthly_point, $regdate);
			if (!$monthlyOutput->toBool())
			{
				return $monthlyOutput;
			}
		}
		else
		{
			$monthlyCount = count($oAttendanceModel->getIsCheckedMonth($member_info->member_srl, $year_month));
			$month_info = $oAttendanceModel->getMonthlyAttendance($member_info->member_srl, $year_month);
			$monthly_point = $month_info->monthly_point;
			$monthly_point += $obj->today_point;
			$monthlyOutput = $this->updateMonthly($member_info->member_srl, $year_month, $monthlyCount, $monthly_point, $regdate);
			if (!$monthlyOutput->toBool())
			{
				return $monthlyOutput;
			}
		}

		$week = $oAttendanceModel->getWeek($regdate);
		if ($oAttendanceModel->isExistWeekly($member_info->member_srl, $week) == 0)
		{
			$weekly_data = $oAttendanceModel->getWeeklyAttendance($member_info->member_srl, $week);
			$weekly_point = $obj->today_point;
			$weekOutput = $this->insertWeekly($member_info->member_srl, $weekly_data, $weekly_point, $regdate);
			if (!$weekOutput->toBool())
			{
				return $weekOutput;
			}
		}
		else
		{
			$weekly_data = $oAttendanceModel->getWeeklyAttendance($member_info->member_srl, $week);
			$week_info = $oAttendanceModel->getWeeklyData($member_info->member_srl, $week);
			$weekly_point = $week_info->weekly_point;
			$weekly_point += $obj->today_point;
			$weekOutput = $this->updateWeekly($member_info->member_srl, $week, $weekly_data, $weekly_point, $regdate);
			if (!$weekOutput->toBool())
			{
				return $weekOutput;
			}
		}

		$oAttendanceModel->clearCacheByMemberSrl($member_info->member_srl);

		return true;
	}

	/**
	 * @brief 출석내용 수정
	 **/
	function procAttendanceModifyData()
	{
		$oPointController = getController('point');
		$oAttendanceModel = getModel('attendance');
		$oAttendance = $oAttendanceModel->getAttendanceDataSrl($obj->attendance_srl);

		$obj = Context::getRequestVars();

		if (strlen($obj->regdate) != 6)
		{
			return;
		}
		$hour = substr($obj->regdate, 0, 2);
		$min = substr($obj->regdate, 2, 2);
		$sec = substr($obj->regdate, 4, 2);
		if (!$hour || $hour < 0 || $hour > 23)
		{
			return;
		}
		if (!$min || $min < 0 || $min > 59)
		{
			return;
		}
		if (!$sec || $sec < 0 || $sec > 59)
		{
			return;
		}

		$year = substr($obj->selected_date, 0, 4);
		$year_month = substr($obj->selected_date, 0, 6);
		$week = $oAttendanceModel->getWeek($obj->selected_date);

		$weekly_info = $oAttendanceModel->getWeeklyData($oAttendance->member_srl, $week);
		$monthly_info = $oAttendanceModel->getMonthlyAttendance($oAttendance->member_srl, $year_month);
		$yearly_info = $oAttendanceModel->getYearlyAttendance($oAttendance->member_srl, $year);
		$total_info = $oAttendanceModel->getTotalData($oAttendance->member_srl);
		$continuity = new stdClass;
		$continuity->data = $total_info->continuity;
		$continuity->point = $total_info->continuity_point;
		$regdate = sprintf("%s%s", $obj->selected_date, $obj->regdate);

		if (!$obj->today_point)
		{
			return;
		}
		if ($obj->today_point < $oAttendance->today_point)
		{
			$value = $oAttendance->today_point - $obj->today_point;
			$oPointController->setPoint($oAttendance->member_srl, $value, 'minus');
			$this->updateWeekly($oAttendance->member_srl, $week, null, $weekly_info->weekly_point - $value, $regdate);
			$this->updateMonthly($oAttendance->member_srl, $year_month, null, $monthly_info->monthly_point - $value, $regdate);
			$this->updateYearly($oAttendance->member_srl, $year, null, $yearly_info->yearly_point - $value, $regdate);
			$this->updateTotal($oAttendance->member_srl, $continuity, null, $total_info->total_point - $value, $regdate);
		}
		else if ($obj->today_point > $oAttendance->today_point)
		{
			$value = $obj->today_point - $oAttendance->today_point;
			$oPointController->setPoint($oAttendance->member_srl, $value, 'add');
			$this->updateWeekly($oAttendance->member_srl, $week, null, $weekly_info->weekly_point + $value, $regdate);
			$this->updateMonthly($oAttendance->member_srl, $year_month, null, $monthly_info->monthly_point + $value, $regdate);
			$this->updateYearly($oAttendance->member_srl, $year, null, $yearly_info->yearly_point + $value, $regdate);
			$this->updateTotal($oAttendance->member_srl, $continuity, null, $total_info->total_point + $value, $regdate);
		}
		$oAttendanceModel->updateAttendance($obj->attendance_srl, $regdate, $obj->today_point, null, null);
		$oAttendanceModel->clearCacheByMemberSrl($oAttendance->member_srl);

		$this->setMessage('success_updated');

		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'mid', $obj->mid, 'act', 'dispAttendanceAdminModifyAttendance', 'attendance_srl', $obj->attendance_srl, 'selected_date', $obj->selected_date));
		}
	}

	/**
	 * @brief If leave member, delete the member attendance data.
	 * @param object $obj
	 * @return Object
	 **/
	function triggerDeleteMember($obj)
	{
		$oAttendanceAdminModel = getAdminModel('attendance');
		$oAttendanceAdminModel->deleteAllAttendanceData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceTotalData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceYearlyData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceMonthlyData($obj->member_srl);
		$oAttendanceAdminModel->deleteAllAttendanceWeeklyData($obj->member_srl);
		$oAttendanceModel = getModel('attendance');
		$oAttendanceModel->clearCacheByMemberSrl($obj->member_srl);
		return $this->makeObject();
	}

	/**
	 * @brief Auto Attend trigger
	 **/
	function triggerAutoAttend($obj)
	{
		$today = zDate(date('YmdHis'), "Ymd");
		$arg = new stdClass;
		$arg->day = $today;
		$arg->member_srl = $obj->member_srl;
		$output = executeQuery('attendance.getIsChecked', $arg);
		if ($output->data->count > 0)
		{
			$_SESSION['is_attended'] = $today;
			return $this->makeObject();
		}

		$oAttendanceModel = getModel('attendance');
		$config = $oAttendanceModel->getConfig();

		$args = new stdClass();
		$args->greetings = '^auto^';
		if ($config->about_auto_attend == 'yes')
		{
			$this->insertAttendance($args, $config, $obj->member_srl);
		}
	}

	/**
	 * @brief If change the member info, do not change the birthday.
	 * @param $content
	 * @return Object
	 */
	function triggerBeforeDisplay(&$content)
	{
		$oAttendanceModel = getModel('attendance');
		$config = $oAttendanceModel->getConfig();
		$act = Context::get('act');

		if ($act == 'dispMemberModifyInfo' && $config->about_birth_day == 'yes' && $config->about_birth_day_y == 'yes')
		{
			$content = str_replace('<input type="text" placeholder="YYYY-MM-DD" name="birthday_ui"', Context::getLang('출석부모듈에 의해 생일 변경이 금지되었습니다.') . '<br><input type="text" name="birthday" placeholder="YYYY-MM-DD" disabled="disabled"', $content);
			$content = str_replace('<input type="button" value="삭제"', '<input type="button" value="삭제" disabled="disabled"', $content);
		}

		return $this->makeObject();
	}

	/**
	 * @brief If change the member info, do not change the birthday.
	 * @param $args
	 * @return Object
	 */
	function triggerUpdateMemberBefore($args)
	{
		// 로그인 정보 가져옴
		$logged_info = Context::get('logged_info');
		$oMemberModel = getModel('member');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($logged_info->member_srl);

		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('attendance');

		if ($logged_info->is_admin == 'N' && $config->about_birth_day == 'yes' && $config->about_birth_day_y == 'yes')
		{
			if ($member_info->birthday != $args->birthday)
			{
				return $this->makeObject(-1, '출석부모듈에 의해 생일을 수정 할 수 없도록 되어있습니다.');
			}
		}
	}

	/**
	 * @brief Add to member menu.
	 * @return Object
	 */
	function triggerAddMemberMenu()
	{
		$logged_info = Context::get('logged_info');
		if (!Context::get('is_logged'))
		{
			return $this->makeObject();
		}

		$target_srl = Context::get('target_srl');

		$oMemberController = getController('member');
		// Can i use the the Context::getLang in str (string)?
		$oMemberController->addMemberMenu('dispAttendanceMemberInfo', '출석사항');

		if ($logged_info->is_admin == 'Y')
		{
			$url = getUrl('', 'mid', 'attendance', 'act', 'dispAttendanceMemberInfo', 'member_srl', $target_srl);
			$str = '회원출석사항';
			$oMemberController->addMemberPopupMenu($url, $str, '');
		}
	}

	/**
	 * @brief Insert total attendance data by member_srl.
	 * @param int $member_srl
	 * @param object $continuity
	 * @param string $total_attendance
	 * @param string $total_point
	 * @param string $regdate
	 * @return object
	 */
	function insertTotal($member_srl, $continuity, $total_attendance, $total_point, $regdate)
	{
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$arg->continuity = $continuity->data;
		$arg->continuity_point = $continuity->point;
		$arg->total = $total_attendance;
		$arg->total_point = $total_point;
		if ($regdate)
		{
			$arg->regdate = $regdate;
		}
		$output = executeQuery("attendance.insertTotal", $arg);

		return $output;
	}

	/**
	 * @brief attendance_total 테이블 Update
	 */
	function updateTotal($member_srl, $continuity, $total_attendance, $total_point, $regdate)
	{
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$arg->continuity = $continuity->data;
		$arg->continuity_point = $continuity->point;
		$arg->total = $total_attendance;
		$arg->total_point = $total_point;
		if ($regdate)
		{
			$arg->regdate = $regdate;
		}
		$output = executeQuery("attendance.updateTotal", $arg);

		return $output;
	}

	/**
	 * @brief 회원별 연간 출석통계 자료 생성
	 */
	function insertYearly($member_srl, $yearly, $yearly_point, $regdate)
	{
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$arg->yearly = $yearly;
		$arg->yearly_point = $yearly_point;
		if ($regdate)
		{
			$arg->regdate = $regdate;
		}
		$output = executeQuery("attendance.insertYearly", $arg);

		return $output;
	}

	/**
	 * @brief 회원별 연간 출석통계 자료 Update
	 */
	function updateYearly($member_srl, $year, $yearly, $yearly_point, $regdate)
	{
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$arg->yearly = $yearly;
		$arg->yearly_point = $yearly_point;
		$arg->year = $year;
		if ($regdate)
		{
			$arg->regdate = $regdate;
		}
		$output = executeQuery("attendance.updateYearly", $arg);

		return $output;
	}

	/**
	 * @brief 회원별 월간 출석통계 자료 생성
	 */
	function insertMonthly($member_srl, $monthly, $monthly_point, $regdate)
	{
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$arg->monthly = $monthly;
		$arg->monthly_point = $monthly_point;
		if ($regdate)
		{
			$arg->regdate = $regdate;
		}
		$output = executeQuery("attendance.insertMonthly", $arg);

		return $output;
	}

	/**
	 * @brief 회원별 월간 출석통계 자료 Update
	 */
	function updateMonthly($member_srl, $year_month, $monthly, $monthly_point, $regdate)
	{
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$arg->monthly = $monthly;
		$arg->monthly_point = $monthly_point;
		$arg->month = $year_month;
		if ($regdate)
		{
			$arg->regdate = $regdate;
		}
		$output = executeQuery("attendance.updateMonthly", $arg);

		return $output;
	}

	/**
	 * @brief 주간 출석정보 입력
	 */
	function insertWeekly($member_srl, $weekly, $weekly_point, $regdate)
	{
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$arg->weekly = $weekly;
		$arg->weekly_point = $weekly_point;
		if ($regdate)
		{
			$arg->regdate = $regdate;
		}
		$output = executeQuery("attendance.insertWeekly", $arg);

		return $output;
	}

	/**
	 * @brief 주간 출석정보 Update
	 */
	function updateWeekly($member_srl, $week, $weekly, $weekly_point, $regdate)
	{
		$arg = new stdClass();
		$arg->member_srl = $member_srl;
		$arg->weekly = $weekly;
		$arg->sunday = $week->sunday;
		$arg->monday = $week->monday;
		$arg->weekly_point = $weekly_point;
		if ($regdate)
		{
			$arg->regdate = $regdate;
		}
		$output = executeQuery("attendance.updateWeekly", $arg);

		return $output;
	}
}
