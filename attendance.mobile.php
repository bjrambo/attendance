<?php
/**
 * @class attendanceMobile
 * @author BJRambo (sosifam@070805.co.kr)
 * @brief attendance module mobile class
 **/

require_once(_XE_PATH_ . 'modules/attendance/attendance.view.php');

class attendanceMobile extends attendanceView
{
	function init()
	{
		$template_path = sprintf("%sm.skins/%s/", $this->module_path, $this->module_info->mskin);
		if (!is_dir($template_path) || !$this->module_info->mskin)
		{
			$this->module_info->mskin = 'default';
			$template_path = sprintf("%sm.skins/%s/", $this->module_path, $this->module_info->mskin);
		}
		$this->setTemplatePath($template_path);
		$this->setTemplateFile('index');
	}

	function dispAttendanceMobileModifyAttendance()
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

		$template_path = sprintf("%sm.skins/%s/", $this->module_path, $this->module_info->mskin);
		if (!is_dir($template_path) || !$this->module_info->mskin)
		{
			$template_path = sprintf("%sm.skins/%s/", $this->module_path, $this->module_info->mskin);
		}
		$this->setTemplatePath($template_path);
		$this->setTemplateFile('modify');
	}
}
