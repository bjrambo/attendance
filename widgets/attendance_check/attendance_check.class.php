<?PHP

class attendance_check extends WidgetHandler{

    function proc($args){

        /* 템플릿 파일에서 사용할 변수들을 세팅 */
        $obj->title = $args->title;

        //attendance model 객체 로드
        $oAttendanceModel = &getModel('attendance');

        $selected_date = Context::get('choose_date');
        if(!$selected_date){ $selected_date = zDate(date('YmdHis'),"Ymd");}
        $obj->start_num = $oAttendanceModel->getPositionData($selected_date,"^showall^");

        /*각종 포인트 설정값 꺼내오기*/
        $output = executeQuery('attendance.getConfigData');
        $config_data = $output->data;

        //module의 설정값 가져오기
        $oModuleModel = &getModel('module');
        $config = $oModuleModel->getModuleConfig('attendance');
        if(!$config->about_admin_check)$config->about_admin_check = 'yes';
        Context::set('config2',$config);

        //포인트 변수
        $point = $config_data->add_point;

        //리스트 변수
        if(!$args->list_count)
            $args->list_count = 5;

        //변수 보내기
        $obj->position = $args->position;
        $obj->reg_time = $args->reg_time;
        $obj->nick_name = $args->nick_name;
        $obj->user_point = $args->user_point;
        $obj->user_id = $args->user_id;
        $obj->bonus_point = $args->bonus_point;
        $obj->list_count = $args->list_count;
        $obj->greetings = $args->greetings;
        $obj->today_point = $args->today_point;
        $obj->inverse = $args->inverse;
        $obj->title_view = $args->title_view;
        $obj->ipaddress = $args->ipaddress;
        $obj->addon_view = $args->addon_view;
        $obj->button_view = $args->button_view;
        $obj->navigator_view = $args->navigator_view;
        $obj->border_color = $args->border_color;
        if(!$args->cut_nick)
            $obj->cut_nick=0; 
        else 
            $obj->cut_nick = $args->cut_nick;
        
        $obj->greetings_message = $args->greetings_message;
        
        if(!$args->greetings_length)
            $obj->greetings_length = 10; 
        else 
            $obj->greetings_length = $args->greetings_length;

        /*연속출석 관련 설정*/
        $continuity->about_continuity = $config_data->about_continuity;
        $continuity->continuity_day = $config_data->continuity_day;

        //지정일 포인트 설정
        $obj->about_target = $config_data->about_target;
        $obj->target_day = $config_data->target_day;

        //등수포인트 초기화
        $obj->add_1st = $config_data->first_point;
        $obj->add_2nd = $config_data->second_point;
        $obj->add_3rd = $config_data->third_point;

        //출석했는지 여부 알아보기		
        $flag = 1;
        $logged_info = Context::get('logged_info');
        if($oAttendanceModel->getIsChecked($logged_info->member_srl) == 0)
            $flag = 0;
        else
            $flag = 1;

        //포인트 모듈 연동
        $oPointModel = &getModel('point');

        //member 모듈 연동
        $oMemberModel = &getModel('member');

        //출석가능 시간대인지 판단
        $is_available = $oAttendanceModel->availableCheck($config_data);

        $oDocumentModel = &getModel('document');
        
        //변수 내보내기
        Context::set('selected_date',$selected_date);
        Context::set('continuity',$continuity);
        Context::set('add_point',$point);
        Context::set('point_model',$oPointModel);
        Context::set('flag',$flag);		
        Context::set('model',$oAttendanceModel);
        Context::set('obj', $obj);
        Context::set('colorset', $args->colorset);
        Context::set('logged_info', $logged_info);
        Context::set('config_data', $config_data);
        Context::set('is_available', $is_available);
        Context::set('oMemberModel',$oMemberModel);
        Context::set('oDocumentModel',$oDocumentModel);

        //모바일 페이지용 각종 스크립트 로드
        if($args->mobile_skin == 'yes'){
            Context::addJsFile("./common/js/jquery.js", true, '', -100005);
            Context::addJsFile("./common/js/js_app.js", true, '', -100004);
            Context::addJsFile("./common/js/common.js", true, '', -100003);
            Context::addJsFile("./common/js/xml_handler.js", true, '', -100002);
            Context::addJsFile("./common/js/xml_js_filter.js", true, '', -100001);
        }

        // 템플릿의 스킨 경로를 지정 
        $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
        $tpl_file = 'body';

        // 템플릿 컴파일
        $oTemplate = &TemplateHandler::getInstance();
        return $oTemplate->compile($tpl_path, $tpl_file);
    }
}
?>
