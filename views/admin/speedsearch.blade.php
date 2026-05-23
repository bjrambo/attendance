@include('_header')

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
			<option value="user_id" @selected($search_target == 'user_id')>{{ $lang->user_id }}</option>
			<option value="email_address" @selected($search_target == 'email_address')>{{ $lang->email }}</option>
			<option value="nick_name" @selected($search_target == 'nick_name')>{{ $lang->nick_name }}</option>
		</select>
		<input type="text" name="search_keyword" value="{{ $search_keyword }}" class="inputTypeText" />
		<span class="button"><input type="submit" value="{{ $lang->cmd_search }}" /></span>
		<a href="{{ getUrl('','type',$type,'act',$act,'module',$module) }}" class="button"><span>{{ $lang->cmd_cancel }}</span></a>
	</fieldset>
</form>

@if($search_keyword || $selected_group_srl != 0)
@foreach($user_data->data as $val2)
<div id="attendance_list">
	<table cellspacing="0" class="rowTable attendanceTable">
		<tr>
			<th class="center">{{ $lang->email }}</th>
			<th class="center">{{ $lang->nick_name }}</th>
			<th class="center">{{ $lang->admin_graph }}</th>
			<th class="center">{{ $lang->getpoint }}</th>
		</tr>
		<tr>
			<td class="center" width="12%">{{ $val2->email_address }}</td>
			<td class="center" width="12%">
				<div class="member_{{ $val2->member_srl }}">{{ $val2->nick_name }}</div>
			</td>
			<td width="49%" style="width:300px;">
				@php $total_info = (new \Rhymix\Modules\Attendance\Models\Attendance())->getTotalData($val2->member_srl); @endphp
				<div class="admin_graph">
					@php $percent = $total_info->total ? 100 : 0; @endphp
					<div class="progress" style="width:230px;">
						<div class="bar bar-success" style="width:{{ $percent }}%;"></div>&nbsp;
						@if($percent >= 50){{ $lang->view_total_day }}@endif
					</div>
					<div class="num" style="left:230px;width:70px;">
						<strong>{{ $total_info->total }}</strong>/{{ $total_info->total }} {{ $lang->days }}
					</div>
				</div>
			</td>
			<td class="center">{{ $total_info->total_point }}({{ $lang->view_total_point }})</td>
		</tr>
		<tr>
			<td class="center" colspan="2">{{ $lang->yearly_info }}({{ $year }})</td>
			<td style="width:300px;">
				<div class="admin_graph_data">
					@php
					$pad = date('t', mktime(0, 0, 0, 02, 1, $year));
					$yearly = ($pad == 29) ? 366 : 365;
					$yearly_info = (new \Rhymix\Modules\Attendance\Models\Attendance())->getYearlyData($year, $val2->member_srl);
					$percent = (int)($yearly_info / $yearly * 100);
					@endphp
					<div class="admin_graph">
						<div class="progress" style="width:230px;">
							<div class="bar bar-success" style="width:{{ $percent }}%;"></div>@if($percent >= 50)@if($percent == 100){{ $lang->attendance_perfect }}@else {{ $percent }}%@endif@else&nbsp;@endif
						</div>
						<div class="num" style="left:230px;width:70px;">
							<strong>{{ $yearly_info }}</strong>/{{ $yearly }}@if($percent < 50) ({{ $percent }}%)@endif
						</div>
					</div>
				</div>
			</td>
			<td class="center">{{ (new \Rhymix\Modules\Attendance\Models\Attendance())->getTotalPoint($val2->member_srl) }}</td>
		</tr>
		<tr>
			<td class="center" colspan="2">{{ $lang->monthly_info }}({{ $year.'-'.$month }})</td>
			<td style="width:300px;">
				@php
				$monthly_information = (new \Rhymix\Modules\Attendance\Models\Attendance())->getMonthlyAttendance($val2->member_srl, $year_month);
				$percent = (int)($monthly_information->monthly / $eom * 100);
				@endphp
				<div class="admin_graph">
					<div class="progress" style="width:230px;">
						<div class="bar bar-success" style="width:{{ $percent }}%;"></div>@if($percent >= 50)@if($percent == 100){{ $lang->attendance_perfect }}@else {{ $percent }}%@endif@else&nbsp;@endif
					</div>
					<div class="num" style="left:230px;width:70px;">
						<strong>{{ $monthly_information->monthly }}</strong>/{{ $eom }}@if($percent < 50) ({{ $percent }}%)@endif
					</div>
				</div>
			</td>
			<td class="center">{{ $monthly_information->monthly_point }}</td>
		</tr>
		<tr>
			<td class="center" colspan="2">
				{{ $lang->weekly_info }}({{ substr($week->monday,0,4).'-'.substr($week->monday,4,2).'-'.substr($week->monday,6,2) }} ~ {{ substr($week->sunday,0,4).'-'.substr($week->sunday,4,2).'-'.substr($week->sunday,6,2) }})
			</td>
			<td style="width:300px;">
				@php
				$weekly_information = (new \Rhymix\Modules\Attendance\Models\Attendance())->getWeeklyData($val2->member_srl, $week);
				$percent = (int)($weekly_information->weekly / 7 * 100);
				@endphp
				<div class="admin_graph">
					<div class="progress" style="width:230px;">
						<div class="bar bar-success" style="width:{{ $percent }}%;"></div>@if($percent >= 50)@if($percent == 100){{ $lang->attendance_perfect }}@else {{ $percent }}%@endif@else&nbsp;@endif
					</div>
					<div class="num" style="left:230px;width:70px;">
						<strong>{{ $weekly_information->weekly }}</strong>/7@if($percent < 50) ({{ $percent }}%)@endif
					</div>
				</div>
			</td>
			<td class="center">{{ $weekly_information->weekly_point }}</td>
		</tr>
		<tr>
			<td class="center" colspan="2">
				{{ $lang->attend_attended_time }} : {{ substr($total_info->regdate,0,4).'-'.substr($total_info->regdate,4,2).'-'.substr($total_info->regdate,6,2) }} {{ substr($total_info->regdate,8,2) }}:{{ substr($total_info->regdate,10,2) }}
			</td>
			<td class="center">{{ $lang->attendance_continuity_day }} : {{ $total_info->continuity }}</td>
			<td class="center">{{ $lang->attendance_continuity_point }} : {{ $total_info->continuity_point }}</td>
		</tr>
	</table>
</div>
@endforeach

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
@else
<table cellspacing="0" class="rowTable">
	<td class="center" height="100px">{{ $lang->messages }}</td>
</table>
@endif
