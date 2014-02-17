function completeInsertAttendance(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];
    //alert(message);
    var url = current_url;
    location.href = url;
}

var a = true;
var b = true;

function text_entercheck_widget(e){
    var qook = jQuery("#click_button_widget").get(0);
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

function attendance_check_widget(){
    var qook = jQuery("#click_button_widget").get(0);
    if(a){
        if(!b){return ;}
        a = false;
        qook.disabled=true;
        return procFilter(qook, insert_attendance);
    } else{ 
        alert(warn_msg);
    }
} 
