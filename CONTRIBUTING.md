출석부 모듈 코드 운영방침.
==================

## Pull request(PR)
* `master` 브랜치의 코드는 수정하지마세요
* PR은 `develop` 브랜치만 허용합니다.
* `develop` 브랜치를 부모로한 토픽 브랜치를 활용하면 편리합니다.


## Coding Guidelines
코드를 기여할 때 Coding conventions을 따라야합니다.

## 1. PHP파일
* 모든 text 파일의 charset은 BOM이 없는 UTF-8입니다
* newline은 UNIX type을 사용합니다. 일부 파일이 다른 type을 사용하더라도 절대 고치지 마세요!
* 들여쓰기는 1개의 탭으로 합니다
* class 선언과 function, if, foreach, for, while 등 중괄호의 `{}`는 다음 줄에 있어야 합니다
	* 마찬가지로 선언 다음에는 공백을 두지 않습니다. ex) CORRECT `if(...)`, INCORRECT `if (...)`
* **Coding convention에 맞지 않는 코드를 발견 하더라도 목적과 관계 없는 코드는 절대 고치지 마세요**


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
* **Coding convention에 맞지 않는 코드를 발견 하더라도 목적과 관계 없는 코드는 절대 고치지 마세요**


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
* **Coding convention에 맞지 않는 코드를 발견 하더라도 목적과 관계 없는 코드는 절대 고치지 마세요**

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