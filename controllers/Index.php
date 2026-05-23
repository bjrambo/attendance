<?php
namespace Rhymix\Modules\Attendance\Controllers;

/**
 * Attendance module user-facing proc actions and shared attendance write helpers.
 */
class Index extends EventHandlers
{
	public function init(): void {}

	// ── User proc actions ─────────────────────────────────────────────────────

	public function procAttendanceModifyContinuous()
	{
		$logged_info = \Context::get('logged_info');
		if ($logged_info->is_admin != 'Y')
		{
			return $this->makeObject(-1, '관리자만 설정이 가능합니다.');
		}

		$obj = \Context::getRequestVars();
		$member_srl = $obj->member_srl;
		if (!$member_srl)
		{
			return $this->makeObject(-1, '회원번호는 필수 입니다.');
		}

		$args = new \stdClass();
		$args->member_srl = $member_srl;
		$args->continuity = $obj->continuity;
		$args->regdate = $obj->regdate . '235959';
		$output = executeQuery('attendance.updateTotal', $args);
		if ($output->toBool())
		{
			$this->setMessage('수정완료');
		}
		else
		{
			return $this->makeObject(-1, 'msg_do_not_attendance');
		}

		(new \Rhymix\Modules\Attendance\Models\Attendance())->clearCacheByMemberSrl($member_srl);

		if (!in_array(\Context::getRequestMethod(), ['XMLRPC', 'JSON']))
		{
			$returnUrl = \Context::get('success_return_url')
				?: getNotEncodedUrl('', 'mid', 'attendance', 'act', 'dispAttendanceModifyContinuous', 'member_srl', $member_srl);
			header('location: ' . $returnUrl);
			return;
		}
	}

	public function procAttendanceInsertAttendance()
	{
		if (!\Context::get('is_logged'))
		{
			return $this->makeObject(-1, '로그인 사용자만 출석 할 수 있습니다.');
		}

		if (!\Context::get('logged_info')->member_srl)
		{
			return $this->makeObject(-1, '알 수 없는 로그인 정보로 출석 할 수 없습니다.');
		}

		if (!$this->grant->attendance)
		{
			return $this->makeObject(-1, '권한이 없습니다.');
		}

		$today = date('Ymd');
		$oAttendanceModel = new \Rhymix\Modules\Attendance\Models\Attendance();
		$logged_info = \Context::get('logged_info');
		$config = $oAttendanceModel->getConfig();

		if ($config->about_admin_check != 'yes' && $logged_info->is_admin == 'Y')
		{
			return $this->makeObject(-1, '관리자는 출석할 수 없습니다.');
		}

		// DB check first (session can be bypassed)
		$isCheckCount = $oAttendanceModel->getIsChecked($logged_info->member_srl);
		if ($isCheckCount > 0)
		{
			$_SESSION['is_attended'] = $today;
			return $this->makeObject(-1, 'attend_already_checked');
		}

		// Session cache to avoid extra DB query
		if ($_SESSION['is_attended'] === $today)
		{
			return $this->makeObject(-1, 'attend_already_checked');
		}

		if ($oAttendanceModel->getDuplicateIpCount($today, $_SERVER['REMOTE_ADDR']) >= $config->allow_duplicaton_ip_count)
		{
			unset($_SESSION['is_attended']);
			return $this->makeObject(-1, 'attend_allow_duplicaton_ip_count');
		}

		$g_obj = \Context::getRequestVars();
		if (preg_match('/^\#/', $g_obj->greetings))
		{
			return $this->makeObject(-1, 'attend_greetings_error');
		}

		$oDB = \DB::getInstance();
		$oDB->begin();

		$output = $this->insertAttendance($g_obj, $config);
		if (!$output->toBool())
		{
			unset($_SESSION['is_attended']);
			$oDB->rollback();
			return $output;
		}

		$oDB->commit();
		$_SESSION['is_attended'] = $today;
		$this->setMessage('att_success');

		if (!in_array(\Context::getRequestMethod(), ['XMLRPC', 'JSON']))
		{
			$returnUrl = \Context::get('success_return_url')
				?: getNotEncodedUrl('', 'mid', 'attendance');
			header('location: ' . $returnUrl);
			return;
		}
	}

	public function procAttendanceModifyData()
	{
		$oPointController = \PointController::getInstance();
		$oAttendanceModel = new \Rhymix\Modules\Attendance\Models\Attendance();

		$obj = \Context::getRequestVars();
		$oAttendance = $oAttendanceModel->getAttendanceDataSrl($obj->attendance_srl);

		if (strlen($obj->regdate) != 6) return;

		$hour = substr($obj->regdate, 0, 2);
		$min = substr($obj->regdate, 2, 2);
		$sec = substr($obj->regdate, 4, 2);
		if (!$hour || $hour < 0 || $hour > 23) return;
		if (!$min || $min < 0 || $min > 59) return;
		if (!$sec || $sec < 0 || $sec > 59) return;

		$year = substr($obj->selected_date, 0, 4);
		$year_month = substr($obj->selected_date, 0, 6);
		$week = $oAttendanceModel->getWeek($obj->selected_date);

		$weekly_info = $oAttendanceModel->getWeeklyData($oAttendance->member_srl, $week);
		$monthly_info = $oAttendanceModel->getMonthlyAttendance($oAttendance->member_srl, $year_month);
		$yearly_info = $oAttendanceModel->getYearlyAttendance($oAttendance->member_srl, $year);
		$total_info = $oAttendanceModel->getTotalData($oAttendance->member_srl);
		$continuity = new \stdClass;
		$continuity->data = $total_info->continuity;
		$continuity->point = $total_info->continuity_point;
		$regdate = sprintf('%s%s', $obj->selected_date, $obj->regdate);

		if (!$obj->today_point) return;

		if ($obj->today_point < $oAttendance->today_point)
		{
			$value = $oAttendance->today_point - $obj->today_point;
			$oPointController->setPoint($oAttendance->member_srl, $value, 'minus');
			$this->updateWeekly($oAttendance->member_srl, $week, null, $weekly_info->weekly_point - $value, $regdate);
			$this->updateMonthly($oAttendance->member_srl, $year_month, null, $monthly_info->monthly_point - $value, $regdate);
			$this->updateYearly($oAttendance->member_srl, $year, null, $yearly_info->yearly_point - $value, $regdate);
			$this->updateTotal($oAttendance->member_srl, $continuity, null, $total_info->total_point - $value, $regdate);
		}
		elseif ($obj->today_point > $oAttendance->today_point)
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

		if (\Context::get('success_return_url'))
		{
			$this->setRedirectUrl(\Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'mid', $obj->mid, 'act', 'dispAttendanceAdminModifyAttendance', 'attendance_srl', $obj->attendance_srl, 'selected_date', $obj->selected_date));
		}
	}

	// ── Core attendance insert helpers ────────────────────────────────────────

	/**
	 * Inserts a single attendance record and awards all applicable points.
	 * Called both by procAttendanceInsertAttendance and admin check.
	 */
	public function insertAttendance($g_obj, $config, $member_srl = null, $r_args = null)
	{
		$oMemberModel = \MemberModel::getInstance();
		$oAttendanceModel = new \Rhymix\Modules\Attendance\Models\Attendance();

		if ($member_srl)
		{
			$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
		}
		else
		{
			$member_info = \Context::get('logged_info');
		}

		if (isset($r_args->regdate))
		{
			$todayDateTime = $r_args->regdate;
			$today = zDate($todayDateTime, 'Ymd');
			$year = $r_args->year;
			$year_month = $r_args->year_month;
			$yesterday = null;
		}
		else
		{
			$todayDateTime = date('YmdHis');
			$today = zDate($todayDateTime, 'Ymd');
			$year = zDate($todayDateTime, 'Y');
			$year_month = zDate($todayDateTime, 'Ym');
			$yesterday = date('Ymd', strtotime('-1 day'));
		}

		$oPointController = \PointController::getInstance();
		$obj = new \stdClass();
		$obj->continuity_day = $config->continuity_day;
		$obj->continuity_point = $config->continuity_point;
		$obj->today_point = $config->add_point;
		$obj->greetings = $g_obj->greetings;
		$obj->member_srl = $member_info->member_srl;

		\ModuleHandler::triggerCall('attendance.insertAttendance', 'before', $obj);

		$position = $oAttendanceModel->getPositionData($today);
		if ($position == 0) $obj->today_point += $config->first_point;
		elseif ($position == 1) $obj->today_point += $config->second_point;
		elseif ($position == 2) $obj->today_point += $config->third_point;

		$yesterday_continuity_data = $oAttendanceModel->isExistContinuity($member_info->member_srl, $yesterday);
		if ($yesterday_continuity_data > 0)
		{
			$continuity = $oAttendanceModel->getContinuityData($member_info->member_srl, $yesterday);
			if ($continuity->data + 1 >= $obj->continuity_day && $obj->continuity_day != 0 && $config->about_continuity == 'yes')
			{
				$obj->today_point += $obj->continuity_point;
				$continuity->point = $obj->continuity_point;
			}
			$continuity->data++;
		}
		else
		{
			$continuity = new \stdClass();
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
					$gift_args = new \stdClass();
					$gift_args->present_srl = getNextSequence();
					$gift_args->member_srl = $member_info->member_srl;
					$gift_args->present = $config->giftname;
					$gift_args->sender = 'N';
					$output_gift = executeQuery('attendance.insertPresent', $gift_args);
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
		if ($attendRegularly->yearly_perfect) $obj->today_point += $config->yearly_point;
		if ($attendRegularly->monthly_perfect) $obj->today_point += $config->monthly_point;

		$week = $oAttendanceModel->getWeek($today);
		$weekly_data = $oAttendanceModel->getWeeklyData($member_info->member_srl, $week);
		if ($weekly_data->weekly == 6) $obj->today_point += $config->weekly_point;

		if ($config->about_diligence_yearly == 'yes' && $oAttendanceModel->checkYearlyDiligence($member_info->member_srl, $config->diligence_yearly - 1, null) === 1)
		{
			$obj->today_point += $config->diligence_yearly_point;
		}
		if ($config->about_diligence_monthly == 'yes' && $oAttendanceModel->checkMonthlyDiligence($member_info->member_srl, $config->diligence_monthly - 1, null) === 1)
		{
			$obj->today_point += $config->diligence_monthly_point;
		}
		if ($config->about_diligence_weekly == 'yes' && $oAttendanceModel->checkWeeklyDiligence($member_info->member_srl, $config->diligence_weekly - 1, null) === 1)
		{
			$obj->today_point += $config->diligence_weekly_point;
		}

		$obj->today_random = 0;
		$obj->att_random_set = 0;

		if ($config->about_random == 'yes' && $config->use_random_sm == 'no'
			&& $config->minimum <= $config->maximum && $config->minimum >= 0 && $config->maximum >= 0)
		{
			$randNumber = mt_rand($config->minimum, $config->maximum);
			if ($config->about_lottery == 'yes' && $config->lottery > 0 && $config->lottery <= 100)
			{
				$win = mt_rand(1, 100);
				if ($win <= $config->lottery)
				{
					$obj->today_point += $randNumber;
					$obj->today_random = $randNumber;
				}
			}
			else
			{
				$obj->today_point += $randNumber;
				$obj->today_random = $randNumber;
			}
		}
		elseif ($config->about_random == 'yes' && $config->use_random_sm == 'yes'
			&& $config->random_small_point_f <= $config->random_small_point_s
			&& $config->random_small_point_f >= 0 && $config->random_small_point_s >= 0)
		{
			if ($config->about_lottery == 'yes' && $config->lottery > 0 && $config->lottery <= 100)
			{
				$win = mt_rand(1, 100);
				if ($win <= $config->lottery)
				{
					if ($win >= $config->random_small_win)
					{
						$randNumber = mt_rand($config->random_small_point_f, $config->random_small_point_s);
					}
					else
					{
						$randNumber = mt_rand($config->random_big_point_f, $config->random_big_point_s);
						$obj->att_random_set = 1;
					}
					$obj->today_point += $randNumber;
					$obj->today_random = $randNumber;
				}
			}
			else
			{
				$randNumber = mt_rand($config->random_small_point_f, $config->random_small_point_s);
				$obj->today_point += $randNumber;
				$obj->today_random = $randNumber;
			}
		}

		if ($config->about_birth_day == 'yes')
		{
			if (substr($member_info->birthday, 4, 4) === substr($today, 4, 4))
			{
				$obj->today_point += $config->brithday_point;
			}
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

		if ($config->use_document == 'yes' && strlen($obj->greetings) > 0 && $obj->greetings != '^auto^')
		{
			$d_obj = new \stdClass;
			$d_obj->content = $obj->greetings;
			$d_obj->nick_name = $member_info->nick_name;
			$d_obj->email_address = $member_info->email_address;
			$d_obj->homepage = $member_info->homepage;
			$d_obj->is_notice = 'N';
			$d_obj->module_srl = $module_info->module_srl;
			$d_obj->allow_comment = 'Y';
			$output = \DocumentController::getInstance()->insertDocument($d_obj, false);
			if (!$output->get('document_srl'))
			{
				return $this->makeObject(-1, 'attend_error_no_greetings');
			}
			$obj->greetings = '#' . $output->get('document_srl');
		}

		$obj->ipaddress = $_SERVER['REMOTE_ADDR'];
		$obj->attendance_srl = getNextSequence();
		$obj->regdate = zDate($todayDateTime, 'YmdHis');

		$output = executeQuery('attendance.insertAttendance', $obj);

		$trigger_obj = new \stdClass();
		$trigger_obj->regdate = $obj->regdate;
		$trigger_obj->ipaddress = $obj->ipaddress;
		$trigger_obj->today_point = $obj->today_point;
		$trigger_obj->greetings = $obj->greetings;
		$trigger_obj->member_srl = $obj->member_srl;
		\ModuleHandler::triggerCall('attendance.insertAttendance', 'after', $trigger_obj);

		if ($obj->today_point != 0 && $member_info->member_srl)
		{
			$oPointModel = \PointModel::getInstance();
			if ($oPointModel->getConfig()->able_module == 'Y')
			{
				$pointOutput = $oPointController->setPoint($member_info->member_srl, $obj->today_point, 'add');
				if (!$pointOutput->toBool())
				{
					return $pointOutput;
				}
			}
		}

		$selfOutput = $this->addTotalDataUpdate($member_info, $year, $year_month, $obj, $continuity);
		if (!$selfOutput->toBool())
		{
			return $selfOutput;
		}
		return $output;
	}

	public function addTotalDataUpdate($member_info, $year, $year_month, $obj, $continuity)
	{
		$regdate = $obj->regdate;
		$oAttendanceModel = new \Rhymix\Modules\Attendance\Models\Attendance();
		$oDB = \DB::getInstance();
		$oDB->begin();

		// attendance_total
		$totalData = $oAttendanceModel->isExistTotal($member_info->member_srl);
		if ($totalData == 0)
		{
			$total_attendance = $oAttendanceModel->getTotalAttendance($member_info->member_srl);
			$totalOutput = $this->insertTotal($member_info->member_srl, $continuity, $total_attendance, $obj->today_point, $regdate);
		}
		else
		{
			$total_attendance = $oAttendanceModel->getTotalAttendance($member_info->member_srl);
			$total_point = $oAttendanceModel->getTotalPoint($member_info->member_srl) + $obj->today_point;
			$totalOutput = $this->updateTotal($member_info->member_srl, $continuity, $total_attendance, $total_point, $regdate);
		}
		if (!$totalOutput->toBool()) { $oDB->rollback(); return $totalOutput; }

		// attendance_yearly
		if ($oAttendanceModel->isExistYearly($member_info->member_srl, $year) == 0)
		{
			$yearly_data = $oAttendanceModel->getYearlyData($year, $member_info->member_srl);
			$yearlyOutput = $this->insertYearly($member_info->member_srl, $yearly_data, $obj->today_point, $regdate);
		}
		else
		{
			$yearly_data = $oAttendanceModel->getYearlyData($year, $member_info->member_srl);
			$year_info = $oAttendanceModel->getYearlyAttendance($member_info->member_srl, $year);
			$yearlyOutput = $this->updateYearly($member_info->member_srl, $year, $yearly_data, $year_info->yearly_point + $obj->today_point, $regdate);
		}
		if (!$yearlyOutput->toBool()) { $oDB->rollback(); return $yearlyOutput; }

		// attendance_monthly
		if ($oAttendanceModel->isExistMonthly($member_info->member_srl, $year_month) == 0)
		{
			$monthlyCount = $oAttendanceModel->getMonthlyData($year_month, $member_info->member_srl);
			$monthlyOutput = $this->insertMonthly($member_info->member_srl, $monthlyCount, $obj->today_point, $regdate);
		}
		else
		{
			$monthlyCount = count($oAttendanceModel->getIsCheckedMonth($member_info->member_srl, $year_month));
			$month_info = $oAttendanceModel->getMonthlyAttendance($member_info->member_srl, $year_month);
			$monthlyOutput = $this->updateMonthly($member_info->member_srl, $year_month, $monthlyCount, $month_info->monthly_point + $obj->today_point, $regdate);
		}
		if (!$monthlyOutput->toBool()) { $oDB->rollback(); return $monthlyOutput; }

		// attendance_weekly
		$week = $oAttendanceModel->getWeek($regdate);
		if ($oAttendanceModel->isExistWeekly($member_info->member_srl, $week) == 0)
		{
			$weekly_data = $oAttendanceModel->getWeeklyAttendance($member_info->member_srl, $week);
			$weekOutput = $this->insertWeekly($member_info->member_srl, $weekly_data, $obj->today_point, $regdate);
		}
		else
		{
			$weekly_data = $oAttendanceModel->getWeeklyAttendance($member_info->member_srl, $week);
			$week_info = $oAttendanceModel->getWeeklyData($member_info->member_srl, $week);
			$weekOutput = $this->updateWeekly($member_info->member_srl, $week, $weekly_data, $week_info->weekly_point + $obj->today_point, $regdate);
		}
		if (!$weekOutput->toBool()) { $oDB->rollback(); return $weekOutput; }

		$oAttendanceModel->clearCacheByMemberSrl($member_info->member_srl);
		$oDB->commit();
		return $this->makeObject();
	}

	// ── DB write helpers (used by admin and event handlers too) ───────────────

	public function insertTotal($member_srl, $continuity, $total_attendance, $total_point, $regdate)
	{
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$arg->continuity = $continuity->data;
		$arg->continuity_point = $continuity->point;
		$arg->total = $total_attendance;
		$arg->total_point = $total_point;
		if ($regdate) $arg->regdate = $regdate;
		$output = executeQuery('attendance.insertTotal', $arg);
		if (!$output->toBool()) return $this->makeObject(-1, '토탈 정보를 입력하는 과정에서 문제가 발생되었습니다.');
		return $output;
	}

	public function updateTotal($member_srl, $continuity, $total_attendance, $total_point, $regdate)
	{
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$arg->continuity = $continuity->data;
		$arg->continuity_point = $continuity->point;
		$arg->total = $total_attendance;
		$arg->total_point = $total_point;
		if ($regdate) $arg->regdate = $regdate;
		$output = executeQuery('attendance.updateTotal', $arg);
		if (!$output->toBool()) return $this->makeObject(-1, '토탈 정보를 업데이트 하는 과정에서 문제가 발생되었습니다.');
		return $output;
	}

	public function insertYearly($member_srl, $yearly, $yearly_point, $regdate)
	{
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$arg->yearly = $yearly;
		$arg->yearly_point = $yearly_point;
		if ($regdate) $arg->regdate = $regdate;
		return executeQuery('attendance.insertYearly', $arg);
	}

	public function updateYearly($member_srl, $year, $yearly, $yearly_point, $regdate)
	{
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$arg->yearly = $yearly;
		$arg->yearly_point = $yearly_point;
		$arg->year = $year;
		if ($regdate) $arg->regdate = $regdate;
		return executeQuery('attendance.updateYearly', $arg);
	}

	public function insertMonthly($member_srl, $monthly, $monthly_point, $regdate)
	{
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$arg->monthly = $monthly;
		$arg->monthly_point = $monthly_point;
		if ($regdate) $arg->regdate = $regdate;
		return executeQuery('attendance.insertMonthly', $arg);
	}

	public function updateMonthly($member_srl, $year_month, $monthly, $monthly_point, $regdate)
	{
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$arg->monthly = $monthly;
		$arg->monthly_point = $monthly_point;
		$arg->month = $year_month;
		if ($regdate) $arg->regdate = $regdate;
		return executeQuery('attendance.updateMonthly', $arg);
	}

	public function insertWeekly($member_srl, $weekly, $weekly_point, $regdate)
	{
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$arg->weekly = $weekly;
		$arg->weekly_point = $weekly_point;
		if ($regdate) $arg->regdate = $regdate;
		return executeQuery('attendance.insertWeekly', $arg);
	}

	public function updateWeekly($member_srl, $week, $weekly, $weekly_point, $regdate)
	{
		$arg = new \stdClass();
		$arg->member_srl = $member_srl;
		$arg->weekly = $weekly;
		$arg->sunday = $week->sunday;
		$arg->monday = $week->monday;
		$arg->weekly_point = $weekly_point;
		if ($regdate) $arg->regdate = $regdate;
		return executeQuery('attendance.updateWeekly', $arg);
	}

	public function setOpenAttendanceTime(): bool
	{
		$oAttendanceModel = new \Rhymix\Modules\Attendance\Models\Attendance();
		$today = date('Ymd');
		$config = $oAttendanceModel->getConfig();

		if ($config->about_time_control != 'rand') return false;
		if ($config->rand_open_time && $config->rand_open_day == $today) return false;

		$randMin = rand(0, 59);
		$hour = sprintf('%02d', intval($config->start_rand_time));
		$min = sprintf('%02d', $randMin);
		$randTime = $today . $hour . $min . '00';

		$config->rand_open_time = $randTime;
		$config->rand_open_day = $today;

		$output = \ModuleController::getInstance()->updateModuleConfig('attendance', $config);
		return $output->toBool();
	}
}
