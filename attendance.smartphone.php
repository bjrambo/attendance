<?php
    /**
     * @class  attendanceSphone
     * @author haneul0318 (haneul0318@gmail.com)
     * @brief  board 모듈의 SmartPhone IPhone class
     **/

    class attendanceSPhone extends attendance {

        function procSmartPhone($oSmartPhone) {
            $oDocumentModel = &getModel('document');

            $oModuleModel = &getModel('module');
            $oDocumentModel = &getModel('document');
            $module_info = $oModuleModel->getModuleInfoByMid('attendance');
            $oModuleModel->syncSkinInfoToModuleInfo($module_info);
            $oAttendanceModel = &getModel('attendance');
            $oMemberModel = &getModel('member');
            $oAttendanceAdminModel = &getAdminModel('attendance');

            //날짜 초기화
            $document_srl = Context::get('document_srl');
            $selected_date = Context::get('selected_date');
            if($document_srl){
                $oAttendance = $oAttendanceModel->getGreetingsData("#".$document_srl);
                $selected_date = substr($oAttendance->regdate,0,8);
            }
            if(!$selected_date) $selected_date = zDate(date('YmdHis'),"Ymd");

            $module_info->start_num = $oAttendanceModel->getPositionData($selected_date,"^showall^");
            if(!$module_info->greetings_cut_size)$module_info->greetings_cut_size = 20;

		    /*각종 포인트 설정값 꺼내오기*/
		    $output = executeQuery('attendance.getConfigData');
		    $config_data = $output->data;

            //module의 설정값 가져오기
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('attendance');

            //출석가능 시간대인지 판단
            $is_available = $oAttendanceModel->availableCheck($config_data);

            //오름차순, 내림차순에 따라 출력방법 결정
            if($module_info->order_type == 'desc'){
                $oAttendance = $oAttendanceModel->getInverseList($module_info->list_count, $selected_date);
            }else{
                $oAttendance = $oAttendanceModel->getAttendanceList($module_info->list_count, $selected_date);
            }


            //출석달력 설정
            $date_info->_year = substr($selected_date,0,4);
            $date_info->_month = substr($selected_date,4,2);
            $date_info->_day = substr($selected_date,6,2);
            $date_info->day_max = date("t",mktime(0,0,0,$date_info->_month,1,$date_info->_year));
            $date_info->week_start = date("w",mktime(0,0,0,$date_info->_month,1,$date_info->_year));

            Context::set('admin_date_info',$date_info);
            Context::set('logged_info',Context::get('logged_info'));
            Context::set('oAttendanceModel',$oAttendanceModel);


            //변수 내보내기
            Context::set('selected_date',$selected_date);
            Context::set('is_available',$is_available);
            Context::set('oAttendance',$oAttendance);
            Context::set('oDocumentModel',$oDocumentModel);
            Context::set('oAttendanceAdminModel',$oAttendanceAdminModel);
            Context::set('oAttendanceModel',$oAttendanceModel);
            Context::set('oMemberModel',$oMemberModel);
            Context::set('module_info',$module_info);
       		Context::set('config',$config);
       		Context::set('config_data',$config_data);

            $tpl_file = 'index';
            $oTemplate = new TemplateHandler();
            $content = $oTemplate->compile($this->module_path.'skins/default', $tpl_file);
            $oSmartPhone->setContent($content);
        }
    }
?>
