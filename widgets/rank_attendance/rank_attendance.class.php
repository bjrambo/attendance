<?PHP

class rank_attendance extends WidgetHandler{

    function proc($args){
		             // 위젯 자체적으로 설정한 변수들을 체크
        $title = $args->title;
        $list_count = (int)$args->list_count;
        if(!$list_count) $list_count = 5;

        $oAttendanceModel = &getModel('attendance');

        $year = zDate(date('YmdHis'),"Y");
        $year_month = zDate(date('YmdHis'),"Ym");
        $today = zDate(date('YmdHis'),"Ymd");
        $week = $oAttendanceModel->getWeek($today);

        if($args->is_admin == 'Y') {
            $obj->is_admin=null;  
        } else {
            $obj->is_admin = 'Y';
                      }

		             //옵션에 따른 쿼리 호출
        $obj->list_count = $list_count;
        if($args->first_option == 'total'){
            if($args->second_option =='day'){
                $output = executeQuery("widgets.rank_attendance.totalRankingDay",$obj);
            }else if($args->second_option == 'point'){
                $output = executeQuery("widgets.rank_attendance.totalRankingPoint",$obj);
	                 }
        }else if($args->first_option == 'yearly'){
            $obj->year = $year;
            if($args->second_option =='day'){
                $output = executeQuery("widgets.rank_attendance.yearlyRankingDay",$obj);
            }else if($args->second_option == 'point'){
                $output = executeQuery("widgets.rank_attendance.yearlyRankingPoint",$obj);
	                 }
        }else if($args->first_option == 'monthly'){
            $obj->month = $year_month;
            if($args->second_option =='day'){
                $output = executeQuery("widgets.rank_attendance.monthlyRankingDay",$obj);
            }else if($args->second_option == 'point'){
                $output = executeQuery("widgets.rank_attendance.monthlyRankingPoint",$obj);
                      }
        }else if($args->first_option == 'weekly'){
            $obj->sunday = $week->sunday;
            $obj->monday = $week->monday;
            if($args->second_option =='day'){
                $output = executeQuery("widgets.rank_attendance.weeklyRankingDay",$obj);
            }else if($args->second_option == 'point'){
                $output = executeQuery("widgets.rank_attendance.weeklyRankingPoint",$obj);
                                }
                      }
        
                     //별명 글자길이 제한
        if(!$args->cut_nick_name) $widget_info->cut_nick_name = 0; else $widget_info->cut_nick_name = $args->cut_nick_name;

        $widget_info->title = $title;
        $widget_info->list_count = $list_count;
        $widget_info->point_list = $output;
        $widget_info->first_option = $args->first_option;
        $widget_info->second_option = $args->second_option;
        $widget_info->title_view = $args->title_view;
        $widget_info->addon_view = $args->addon_view;
        $widget_info->graph_view = $args->graph_view;
        $widget_info->border_color = $args->border_color;

        //load member module
        $oMemberModel = &getModel('member');        

        Context::set('widget_info', $widget_info);
        Context::set('colorset', $args->colorset);
        Context::set('Model', $oAttendanceModel);
        Context::set('oMemberModel', $oMemberModel);

		             // 템플릿의 스킨 경로를 지정 
        $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
        $tpl_file = 'body';
        Context::set('colorset', $args->colorset);

		             // 템플릿 컴파일
        $oTemplate = &TemplateHandler::getInstance();
        return $oTemplate->compile($tpl_path, $tpl_file);
        }
}
?>
