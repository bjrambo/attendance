@load('^/modules/attendance/tpl/filter/insert_greetingsboard.xml')
@include('_header')

@if($XE_VALIDATOR_MESSAGE)
<div class="message {{ $XE_VALIDATOR_MESSAGE_TYPE }}">
	<p>{!! $XE_VALIDATOR_MESSAGE !!}</p>
</div>
@endif
<form action="{{ getUrl('') }}" method="post" onsubmit="return procFilter(this, insert_greetingsboard);" enctype="multipart/form-data" class="x_form-horizontal">
	<input type="hidden" name="module" value="{{ $module }}" />
	<input type="hidden" name="act" value="{{ $act }}" />
	<input type="hidden" name="type" value="{{ $type }}" />
	<input type="hidden" name="selected_date" value="{{ $selected_date }}" />
	<section class="section">
		<div class="x_control-group">
			<label class="x_control-label" for="layout_srl">{{ $lang->module_category }}</label>
			<div class="x_controls">
				<select name="module_category_srl" id="module_category_srl">
					<option value="0">{{ $lang->notuse }}</option>
					@foreach($module_category as $key => $val)
					<option value="{{ $val->layout_srl }}">{{ $val->title }}</option>
					@endforeach
				</select>
				<a href="#aboutcategory" data-toggle class="x_icon-question-sign">{{ $lang->help }}</a>
				<p class="x_help-block" id="aboutcategory" hidden>{{ $lang->about_module_category }}</p>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label" for="browser_title">{{ $lang->browser_title }}</label>
			<div class="x_controls">
				<input type="text" name="browser_title" id="browser_title" value="{{ $module_info->browser_title }}" class="lang_code" />
				<a href="#aboutBrowserTitle" data-toggle class="x_icon-question-sign">{{ $lang->help }}</a>
				<p class="x_help-block" id="aboutBrowserTitle" hidden>{{ $lang->about_browser_title }}</p>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label" for="layout_srl">{{ $lang->layout }}</label>
			<div class="x_controls">
				<select name="layout_srl" id="layout_srl">
					<option value="0">{{ $lang->notuse }}</option>
					@foreach($layout_list as $key => $val)
					<option value="{{ $val->layout_srl }}" @selected($module_info->layout_srl == $val->layout_srl)>{{ $val->title }} ({{ $val->layout }})</option>
					@endforeach
				</select>
				<a href="#aboutLayout" data-toggle class="x_icon-question-sign">{{ $lang->help }}</a>
				<p class="x_help-block" id="aboutLayout" hidden>{{ $lang->about_layout }}</p>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label" for="skin">{{ $lang->skin }}</label>
			<div class="x_controls">
				<select name="skin" id="skin" style="width:auto">
					@foreach($skin_list as $key => $val)
					<option value="{{ $key }}" @selected($module_info->skin == $key || (!$module_info->skin && $key == 'default'))>{{ $val->title }} ({{ $key }})</option>
					@endforeach
				</select>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label">{{ $lang->mobile_view }}</label>
			<div class="x_controls">
				<select name="use_mobile" style="width:auto">
					<option value="Y" @selected($module_info->use_mobile == 'Y')>{{ $lang->use }}</option>
					<option value="N" @selected($module_info->use_mobile == 'N')>{{ $lang->notuse }}</option>
				</select>
				<a href="#mobile_view_help" class="x_icon-question-sign" data-toggle>{{ $lang->help }}</a>
				<p id="mobile_view_help" class="x_help-block" hidden>{{ $lang->about_mobile_attendance_view }}</p>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label" for="mlayout_srl">{{ $lang->mobile_layout }}</label>
			<div class="x_controls">
				<select name="mlayout_srl" id="mlayout_srl">
					<option value="0">{{ $lang->notuse }}</option>
					@foreach($mlayout_list as $key => $val)
					<option value="{{ $val->layout_srl }}" @selected($module_info->mlayout_srl == $val->layout_srl)>{{ $val->title }} ({{ $val->layout }})</option>
					@endforeach
				</select>
				<a href="#aboutmLayout" data-toggle class="x_icon-question-sign">{{ $lang->help }}</a>
				<p class="x_help-block" id="aboutmLayout" hidden>{{ $lang->about_layout }}</p>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label" for="mskin">{{ $lang->mobile }} {{ $lang->skin }}</label>
			<div class="x_controls">
				<select name="mskin" id="mskin" style="width:auto">
					@foreach($mskin_list as $key => $val)
					<option value="{{ $key }}" @selected($module_info->mskin == $key || (!$module_info->skin && $key == 'default'))>{{ $val->title }} ({{ $key }})</option>
					@endforeach
				</select>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label">{{ $lang->mobile_view }}</label>
			<div class="x_controls">
				<select name="order_type" style="width:auto">
					<option value="asc" @selected($module_info->order_type == 'asc')>{{ $lang->order_asc }}</option>
					<option value="desc" @selected($module_info->order_type == 'desc')>{{ $lang->order_desc }}</option>
				</select>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label">{{ $lang->attend_display_bonus }}</label>
			<div class="x_controls">
				<select name="display_bonus" style="width:auto">
					<option value="N" @selected($module_info->display_bonus == 'N')>{{ $lang->attendance_no }}</option>
					<option value="Y" @selected($module_info->display_bonus == 'Y')>{{ $lang->attendance_yes }}</option>
				</select>
				<a href="#about_attend_display_bonus_help" class="x_icon-question-sign" data-toggle>{{ $lang->help }}</a>
				<p id="about_attend_display_bonus_help" class="x_help-block" hidden>{{ $lang->about_attend_display_bonus }}</p>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label">{{ $lang->list_count }}</label>
			<div class="x_controls">
				<input type="text" name="list_count" value="{{ $module_info->list_count ?: 20 }}" />
				<a href="#about_list" data-toggle class="x_icon-question-sign">{{ $lang->help }}</a>
				<p class="x_help-block" id="about_list" hidden>{{ $lang->about_list_count }}</p>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label">{{ $lang->page_count }}</label>
			<div class="x_controls">
				<input type="text" name="page_count" value="{{ $module_info->page_count ?: 10 }}" />
				<a href="#about_page" data-toggle class="x_icon-question-sign">{{ $lang->help }}</a>
				<p class="x_help-block" id="about_page" hidden>{{ $lang->about_page_count }}</p>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label">{{ $lang->description }}</label>
			<div class="x_controls">
				<textarea name="description">{{ $module_info->description }}</textarea>
				<a href="#about_description" data-toggle class="x_icon-question-sign">{{ $lang->help }}</a>
				<p class="x_help-block" id="about_description" hidden>{{ $lang->about_description }}</p>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label">{{ $lang->header_text }}</label>
			<div class="x_controls">
				<textarea name="header_text" id="header_text">{{ $module_info->header_text }}</textarea><a href="{{ getUrl('','module','module','act','dispModuleAdminLangcode','target','header_text') }}" onclick="popopen(this.href);return false;" class="buttonSet buttonSetting"><span>{{ $lang->cmd_find_langcode }}</span></a>
				<a href="#cmd_find_langcode" data-toggle class="x_icon-question-sign">{{ $lang->help }}</a>
				<p class="x_help-block" id="cmd_find_langcode" hidden>{{ $lang->about_header_text }}</p>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label">{{ $lang->footer_text }}</label>
			<div class="x_controls">
				<textarea name="footer_text" id="footer_text">{{ $module_info->footer_text }}</textarea><a href="{{ getUrl('','module','module','act','dispModuleAdminLangcode','target','footer_text') }}" onclick="popopen(this.href);return false;" class="buttonSet buttonSetting"><span>{{ $lang->cmd_find_langcode }}</span></a>
				<a href="#about_footer_text" data-toggle class="x_icon-question-sign">{{ $lang->help }}</a>
				<p class="x_help-block" id="about_footer_text" hidden>{{ $lang->about_footer_text }}</p>
			</div>
		</div>
	</section>
	<div class="x_clearfix btnArea">
		<div class="x_pull-right">
			<button class="x_btn x_btn-primary" type="submit" accesskey="s">{{ $lang->cmd_submit }}</button>
		</div>
	</div>
</form>
