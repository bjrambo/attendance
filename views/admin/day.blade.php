@include('_header')
@load('^/modules/attendance/tpl/filter/check_attendance_data.xml')
@load('^/modules/attendance/tpl/filter/delete_attendance_data.xml')
@load('^/modules/attendance/tpl/filter/fix_attendance_data.xml')

<form action="{{ getUrl('') }}" method="POST" onsubmit="return procFilter(this, fix_attendance_data);" id="fixAttendanceData">
	<input type="hidden" name="module" value="{{ $module }}" />
	<input type="hidden" name="act" value="{{ $act }}" />
	<input type="hidden" name="type" value="{{ $type }}" />
	<input type="hidden" name="selected_date" value="" />
	<input type="hidden" name="member_srl" value="" />
	<input type="hidden" name="count" value="" />
</form>
<form action="{{ getUrl('') }}" method="get" class="adminSearch">
	<input type="hidden" name="mid" value="" />
	<input type="hidden" name="module" value="{{ $module }}" />
	<input type="hidden" name="act" value="{{ $act }}" />
	<input type="hidden" name="type" value="{{ $type }}" />
	<input type="hidden" name="selected_date" value="{{ $selected_date }}" />
	@php $year_month = substr($selected_date, 0, 6); @endphp
	<fieldset>
		<select name="selected_group_srl">
			<option value="0">{{ $lang->group }}</option>
			@foreach($group_list as $key => $val)
			<option value="{{ $val->group_srl }}" @selected($selected_group_srl == $val->group_srl)>{{ $val->title }}</option>
			@endforeach
		</select>
		<select name="search_target">
			<option value="" @selected(!$search_target)>{{ $lang->search_target }}</option>
			<option value="email_address" @selected($search_target == 'email_address')>{{ $lang->email }}</option>
			<option value="user_id" @selected($search_target == 'user_id')>{{ $lang->user_id }}</option>
			<option value="nick_name" @selected($search_target == 'nick_name')>{{ $lang->nick_name }}</option>
		</select>
		<input type="text" name="search_keyword" value="{{ $search_keyword }}" class="inputTypeText" />
		<span class="button"><input type="submit" value="{{ $lang->cmd_search }}" /></span>
		<a href="{{ getUrl('', 'module', 'admin', 'act', $act) }}" class="button"><span>{{ $lang->cmd_cancel }}</span></a>
	</fieldset>
</form>

<form id="attendanceMode" action="{{ getUrl('') }}" method="post">
	<input type="hidden" name="module" value="{{ $module }}" />
	<input type="hidden" name="act" value="{{ $act }}" />
	<input type="hidden" name="page" value="{{ $page }}" />
	<input type="hidden" name="type" value="{{ $type }}" />
	<input type="hidden" name="selected_date" value="{{ $selected_date }}" />
	<input type="hidden" name="check_day" value="" />
	<input type="hidden" name="member_srl" value="" />
</form>
<table cellspacing="0" class="attendanceTable">
	<col align="center" />
	<tr>
		<th class="center">{{ $lang->nick_name }}</th>
		<td class="center">{{ $lang->user_id }}</td>
		@for($j = 1; $j <= $end_day; $j++)
		<td class="center">
			@if($j == $day)<u>{{ $j }}</u>@else{{ $j }}@endif
		</td>
		@endfor
	</tr>
	@foreach($user_data->data as $val2)
	<tr>
		<th class="center">{{ $val2->nick_name }}</th>
		<td class="center">{{ $val2->user_id }}</td>
		@for($j = 1; $j <= $end_day; $j++)
		<td class="dotted">
			@php
			$check_day = sprintf('%s%02d', $year_month, $j);
			$check = $arrayUserData[$val2->member_srl]->doChecked[$check_day] ?? 0;
			@endphp
			@if($check >= 2)
			<strong><a href="#fixAttendanceData" onclick="att_fix_attendance_data('{{ $val2->member_srl }}','{{ $check_error }}','{{ $check_day }}','{{ $lang->attend_fix_att }}','{{ $val2->nick_name }}');">?</a></strong>
			@elseif($check)
			<a href="#uncheck" onclick="att_toggleStatus(0, '{{ $val2->member_srl }}', '{{ $check_day }}');">●</a>
			@else
			<a href="#check" onclick="att_toggleStatus(1, '{{ $val2->member_srl }}', '{{ $check_day }}');">×</a>
			@endif
		</td>
		@endfor
	</tr>
	@endforeach
</table>
<div class="pagination a1">
	<a href="{{ getUrl('page','','module_srl','') }}" class="prevEnd">{{ $lang->first_page }}</a>
	@while($page_no = $user_data->page_navigation->getNextPage())
	@if($user_data->page == $page_no)
	<strong>{{ $page_no }}</strong>
	@else
	<a href="{{ getUrl('page', $page_no, 'module_srl', '') }}">{{ $page_no }}</a>
	@endif
	@endwhile
	<a href="{{ getUrl('page', $user_data->page_navigation->last_page, 'module_srl', '') }}" class="nextEnd">{{ $lang->last_page }}</a>
</div>

<form action="{{ getUrl('') }}" method="get" class="adminSearch">
	<input type="hidden" name="mid" value="" />
	<input type="hidden" name="module" value="{{ $module }}" />
	<input type="hidden" name="act" value="{{ $act }}" />
	<input type="hidden" name="type" value="{{ $type }}" />
	<input type="hidden" name="selected_date" value="{{ $selected_date }}" />
	<fieldset>
		<select name="selected_group_srl">
			<option value="0">{{ $lang->group }}</option>
			@foreach($group_list as $key => $val)
			<option value="{{ $val->group_srl }}" @selected($selected_group_srl == $val->group_srl)>{{ $val->title }}</option>
			@endforeach
		</select>
		<select name="search_target">
			<option value="" @selected(!$search_target)>{{ $lang->search_target }}</option>
			<option value="email_address" @selected($search_target == 'user_id')>{{ $lang->email }}</option>
			<option value="user_id" @selected($search_target == 'user_id')>{{ $lang->user_id }}</option>
			<option value="nick_name" @selected($search_target == 'nick_name')>{{ $lang->nick_name }}</option>
		</select>
		<input type="text" name="search_keyword" value="{{ $search_keyword }}" class="inputTypeText" />
		<span class="button"><input type="submit" value="{{ $lang->cmd_search }}" /></span>
		<a href="#" onclick="location.href='{{ getUrl('','type',$type,'act',$act,'module',$module) }}';return false;" class="button"><span>{{ $lang->cmd_cancel }}</span></a>
	</fieldset>
</form>
