// 관리자 페이지에서 날짜 이동
function changeSelectedDate(selected_date) {
    var fo_obj = jQuery('#fo_counter').get(0);
    fo_obj.selected_date.value = selected_date;
    fo_obj.submit();
}

function att_toggleStatus(type, member_srl, day) {
	var o = jQuery('#attendanceMode').get(0);
		o.member_srl.value = member_srl;
		o.check_day.value = day;
	if (type) procFilter(o, check_attendance_data);
	else procFilter(o, delete_attendance_data);
	return false;
}

function att_delete_all_data(member_srl) {
	var o = jQuery('#deleteAllData').get(0);
        o.member_srl.value = member_srl;
    procFilter(o, delete_all_data);
    return false;
}

function updatePoint(member_srl, action){
    var pointEl = jQuery("#point_"+member_srl);
    var e = jQuery("#update_member_srl").val(member_srl);
           e = jQuery("#update_action").val(action);
           e = jQuery("#update_point").val(pointEl.attr("value"));
    var hF = jQuery("#updateForm").get(0);
    procFilter(hF, update_point);
}

function att_fix_total_data(member_srl, lang1, lang2, nick) {
	var o = jQuery("#fixTotalData").get(0);
        o.member_srl.value = member_srl;
    alert(nick+lang1+nick+lang2);
    procFilter(o, fix_total_data);
    return false;
}

function att_fix_attendance_data(member_srl, count, date,lang, nick) {
	var o = jQuery("#fixAttendanceData").get(0);
        o.member_srl.value = member_srl;
        o.count.value = count;
        o.selected_date.value = date;
    alert(nick+lang);
    procFilter(o, fix_attendance_data);
    return false;
}

dclick = true;
function att_ban_dclick(msg){
    if(dclick){
        dclick = false;
        return true;
    } 
    alert(msg);
    return false;
}
