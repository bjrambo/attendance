@include('_header')
<table cellspacing="0" class="rowTable">
	@foreach($timeCountData as $val)
	<tr>
		<th width="40px">{{ $val->time }}{{ $lang->attendance_time }}</th>
		<td style="min-width:300px;">
			<div class="admin_graph">
				@if($total_count)
				@php $percent = (int)($val->count / $total_count * 100); @endphp
				@endif
				<div class="progress" style="width:230px;">
					<div class="bar bar-success" style="width:{{ $val->percent }}%;"></div>@if($val->percent >= 50){{ $val->percent }}%@else&nbsp;@endif
				</div>
				<div class="num" style="left:230px;width:70px;">
					@if($val->count)<strong>{{ $val->count }}</strong>@else<span>0</span>@endif/{{ $total_count }}({{ $val->percent }}%)
				</div>
			</div>
		</td>
	</tr>
	@endforeach
</table>
