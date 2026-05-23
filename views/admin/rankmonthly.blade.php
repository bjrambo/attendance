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
		<a href="#" onclick="location.href='{{ getUrl('','type',$type,'act',$act,'module',$module) }}';return false;" class="button"><span>{{ $lang->cmd_cancel }}</span></a>
	</fieldset>
</form>

<table cellspacing="0" class="rowTable attendanceTable">
	<tr>
		<th class="center">{{ $lang->position }}</th>
		<td class="center">{{ $lang->email }}</td>
		<th class="center">{{ $lang->nick_name }}</th>
		<th width="40%" style="min-width:240px;" class="center">{{ $lang->view_monthly_day }}</th>
		<td class="center">{{ $lang->view_monthly_point }}</td>
	</tr>
	@php $position = 1; @endphp
	@foreach($user_data->data as $ranking)
	<tr>
		<th class="center">{{ $position }}</th>
		<td class="center">{{ $ranking->email_address }}</td>
		<th class="center">
			<div class="member_{{ $ranking->member_srl }}">{{ $ranking->nick_name }}</div>
		</th>
		<th width="40%" style="width:300px;">
			<div class="admin_graph">
				@php $percent = (int)($ranking->monthly / $eom * 100); @endphp
				<div class="progress" style="width:230px;">
					<div class="bar bar-success" style="width:{{ $percent }}%;"></div>@if($percent >= 50)@if($percent == 100){{ $lang->attendance_perfect }}@else {{ $percent }}%@endif@else&nbsp;@endif
				</div>
				<div class="num" style="left:230px;width:70px;">
					<strong>{{ $ranking->monthly }}</strong>/{{ $eom }}@if($percent < 50) ({{ $percent }}%)@endif
				</div>
			</div>
		</th>
		<td class="center">{{ $ranking->monthly_point }}</td>
	</tr>
	@php $position++; @endphp
	@endforeach
</table>

<div class="pagination a1">
	<a href="{{ getUrl('page', $user_data->page_navigation->first_page, 'module_srl', '') }}" class="prevEnd">{{ $lang->first_page }}</a>
	@if($user_data->page_navigation)
	@while($page_no = $user_data->page_navigation->getNextPage())
	@if($user_data->page == $page_no)
	<strong>{{ $page_no }}</strong>
	@else
	<a href="{{ getUrl('page', $page_no, 'module_srl', '') }}">{{ $page_no }}</a>
	@endif
	@endwhile
	@else
	@php $page_no = 1; @endphp
	<strong>{{ $page_no }}</strong>
	@endif
	<a href="{{ getUrl('page', $user_data->page_navigation->last_page, 'module_srl', '') }}" class="nextEnd">{{ $lang->last_page }}</a>
</div>
