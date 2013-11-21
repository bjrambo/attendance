var a = true;
var b = true;
function text_entercheck_mobile(e){
    var qook = jQuery("#click_button_mobile").get(0);
    if(e.keyCode == 13){
        if(b){
            if(!a){return ;}
            b = false;
            qook.disabled = true;
            return procFilter(qook, insert_attendance);
        } else{ 
            alert(warn_msg);
        }
        return false;
    } else{
        return true;
    }
} 

function attendance_check_mobile(){
    var qook = jQuery('#click_button_mobile').get(0);
    if(a){
        if(!b){return ;}
        a = false;
        qook.disabled=true;
        return procFilter(qook, insert_attendance);
    } else{ 
        alert(warn_msg);
    }
}

function documentView(srl){
    jQuery(document).ready(function(){
        jQuery("#"+srl).slideToggle("slow");
    });
}

function bonusView(srl){
    jQuery(document).ready(function(){
        jQuery("#"+srl).slideToggle("slow");
    });
}

function modify_att(){
    var m = jQuery('#modify_submit').get(0);
    return procFilter(m, update_attendance);
}

jQuery(document).ready(function(){
    jQuery("#point_click").click(function(){ jQuery("#point_list").slideToggle("slow"); });
    jQuery("#calendar_click").click(function(){ jQuery("#calendar_view").slideToggle("slow"); });
});
