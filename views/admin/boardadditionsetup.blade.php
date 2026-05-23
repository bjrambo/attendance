@load('^/modules/attendance/tpl/filter/insert_greetingsboard.xml')
@include('_header')

@if($XE_VALIDATOR_MESSAGE)
<div class="message {{ $XE_VALIDATOR_MESSAGE_TYPE }}">
	<p>{!! $XE_VALIDATOR_MESSAGE !!}</p>
</div>
@endif
{!! $setup_content !!}
