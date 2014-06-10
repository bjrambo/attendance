출석부 모듈 코드 운영방침.
==================

개요
---
* 출석부 모듈의 코드 공헌을 받을 때 기본적인 규칙을 정하여 공통된 관리를 위함입니다.

공통
---
* 들여쓰기는 항상 탭을 사용하도록 합니다. (띄어쓰기가 4번들어가는 탭은 금지)

1. PHP파일
---------
* 기본 `if문, 반복문 등` 은 기본적으로 대갈호를 한 줄 아래로 내려서 대갈호를 엽니다. (중갈호로만 구성하는 라인이 생기도록 하는게 방침입니다.)

Ex)
```php

if($회사 == 'SMent')
{
	if($contest->sosi == 'Y')
	{
		$context->sosi = $obj->sosi
	}
}

for($1,$2,$3)
{
	$s1 = 'sosi';
}

```

* 큰 따옴표보다는 작은 따옴표를 사용하십시오. (예외, 날자와 관련하여 따옴표가 두번씩 들어가야할 경우 비교를 통해서 예외를 칭합니다.)
* 날짜 개념의 zData() 함수를 사용하는 것과 같이 함수 안의 내용이 두번의 함수로 이루어질경우 비교를 위해서 갈호 재일 안쪽의 함수가 작은따옴표 밖에 위치한경우 큰따옴표를 사용하십시오

```php

$context->sosi = '1';

$selected_date = zDate(date('YmdHis'),"Ymd");

```

2. HTML
-------

* 기본 들여쓰기는 한 라인에 하나의 태그의 열고 닫힘 내용만 위치하도록 합니다.
* span, a, 태그 역시 마찬가지로 한 라인에 하나의 열고 닫힌 내용만 위치하도록 합니다.
* 한 라인에 하나의 태그가 열고 닫힘이 모두 들어갈 수 있는 내용으로는 i 태그로 인한 아이콘 태그만 가능. i태사용시에는 그뒤 나올 내용을 같이 한 라인에 포함하세요.
* if문의 시작과 끝의 문구는 한 라인에만 위치하도록 합니다.
* 그 외 규칙이 맞지 않음에도 불구하고 소스가 규칙에 어긋날경우 그 소스의 규칙에 맞게 냅두시기 바랍니다. (현 규칙과 동일화 하도록 하시지 마세요.)


ex)
```HTML

1.	<div class="sosi" cond="$is_logged">

2.	</div>

1.	<div class="sosifam" cond="$is_logged">
2.		<span class="sosi-item">
3.			<i class="fa fa-sosi"></i> 소녀시대
4.		</span>
5.	</div>

1.	<!--@if($logged_info->is_admins =='Y')-->
2.	<!--@end-->
```
* 아이콘은 이미지파일을 사용하시지 마시고, <i class=""></i> 형태를 이용한 font-awesome 을 사용하십시오.

3. CSS
------

* CSS는 기본적으로 클래스, ID값과 동일한 라인에 대갈호를 열도록 합니다.
* 다수의 클래스를 지정할경우 각 한클래스의 마지막에 `,` 를 붙이도록 합니다.
* 한라인에는 하나의 클래스만 지정이 됩니다.
* 각 옵션은 한라인에 하나씩 넣습니다.
* 옵션간의 들여쓰기는 탭으로 구분합니다.
* 이와 다른 규칙의 소스를 규칙에 맞게 건들이지 마세요.

```CSS
.navi .att-btn {
	display: inline-block;
	padding: 6px 12px;
	margin-bottom: 0;
	font-size: 14px;
	font-weight: bold;
	line-height: 1.428571429;
	text-align: center;
	white-space: nowrap;
	vertical-align: middle;
	cursor: pointer;
	border: 1px solid transparent;
	border-radius: 4px;
	-webkit-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	-o-user-select: none;
	user-select: none;
}

.att-btn-primary:hover,
.att-btn-primary:focus,
.att-btn-primary:active,
.att-btn-primary.active,
.open .dropdown-toggle.att-btn-primary {
	color: #ffffff;
	background-color: #3276b1;
	border-color: #285e8e;
}


```