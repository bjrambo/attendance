<?php
class attendanceMobile extends \Rhymix\Modules\Attendance\Controllers\View
{
	public function init(): void
	{
		$template_path = sprintf('%sm.skins/%s/', $this->module_path, $this->module_info->mskin);
		if (!is_dir($template_path) || !$this->module_info->mskin)
		{
			$this->module_info->mskin = 'default';
			$template_path = sprintf('%sm.skins/%s/', $this->module_path, $this->module_info->mskin);
		}
		$this->setTemplatePath($template_path);
		$this->setTemplateFile('index');
	}

	public function dispAttendanceMobileModifyAttendance(): void
	{
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByMid('attendance');
		$oModuleModel->syncSkinInfoToModuleInfo($module_info);
		$attendance_srl = \Context::get('attendance_srl');
		$oAttendanceModel = getModel('attendance');

		$oAttendance = $oAttendanceModel->getAttendanceDataSrl($attendance_srl);
		\Context::set('oAttendance', $oAttendance);
		\Context::set('oAttendanceModel', $oAttendanceModel);
		\Context::set('oMemberModel', getModel('member'));

		$template_path = sprintf('%sm.skins/%s/', $this->module_path, $this->module_info->mskin);
		if (!is_dir($template_path) || !$this->module_info->mskin)
		{
			$template_path = sprintf('%sm.skins/default/', $this->module_path);
		}
		$this->setTemplatePath($template_path);
		$this->setTemplateFile('modify');
	}
}
