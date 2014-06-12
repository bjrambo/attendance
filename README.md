attendance XE 출석부
====================

개요
----
이 소프트웨어는 'XE 출석부' 입니다.

이 소프트웨어는 GPL v2을 채택한 오픈 소스 소프트웨어로써 자유롭게 사용할 수 있는 자유 소프트웨어입니다.
이 라이선스 조건을 준수하는 조건하에 누구나 자유롭게 사용할 수 있습니다.

주요 기능
---------
* 출석채크를 할 때 인삿말을 남길 수 있음.
* 출석채크포인트를 조절 할 수 있음.
* 개근포인트및 정근포인트를 설정 할 수 있음.

Author
------
**BJ Rambo**

* website : http://sosifam.com
* email : sosifam@070805.co.kr
* 문의 : http://sosifam.com/question
* github : https://github.com/qw5414/attendance

Thanks to...
------------

소녀시대..(나에게 힘을 주니깐..),몽실아빠 (http://pomelove.com), [@BNU](https://github.com/BNU), [@smaker](https://github.com/smaker)

참고사항
--------
PR을 위한 코드공헌하시는 여러분들을 위한 출석부모듈만의 코드 규칙을 제공합니다.
`CONTRIBUTING.md` 파일을 참고하여 규칙을 따라주시기 바랍니다.


설정 부분 수정안내
------------------
최근 출석부 모듈의 config 설정(포인트 및 등등여러가지) 를 자주 바꾸는 이유는 요즘 모듈의 코드로 수정 하는 작업입니다.
추후 xe코어 업뎃으로 인해 지원이 안될 경우가 생길 것 같아서 요즘들어서 바꾸고 있습니다. 최대한 모듈의 호환성을 생각하여 수정된 것 입니다.

업뎃 사항
---------
**Ver. 3.0**
* PC버전 출석 현황보기를 모든 회원에게 보이지 않도록 바꿈. [#71](https://github.com/qw5414/attendance/issues/71)
* 니트레이아웃에서 Vertical-align 설정이 middel 으로 되어있어 스킨표현에 문제점 개선 [#75](https://github.com/qw5414/attendance/issues/75)
* 출석하기를 룰셋을 이용하여 작동하도록 개선 [#77](https://github.com/qw5414/attendance/issues/77)
* 총 출석관리에서 X표시를 클릭시 삭제가 되지 않는 문제 해결 [#82](https://github.com/qw5414/attendance/issues/82)
* 세션에 today 정보를 저장하지 않도록 함 [#83](https://github.com/qw5414/attendance/issues/83)
* 위젯코드 정리 [#84](https://github.com/qw5414/attendance/issues/84)
* 일부 위젯 php5.4 호환성 개선 [#85](https://github.com/qw5414/attendance/issues/85)
* 기본 스킨 파일의 코드 정리 [#86](https://github.com/qw5414/attendance/issues/86)
* insert를 처리하는 트리거 추가. [#89](https://github.com/qw5414/attendance/issues/89)
* 출석 완료 문구를 Lang 파일으로 처리 [#90](https://github.com/qw5414/attendance/issues/90)
* 연속출석일이 기록이 안되는 버그 해결 [#91](https://github.com/qw5414/attendance/issues/91)

**Ver. 2.3.1**
* 스킨 수정

**Ver. 2.3**
* 스킨 대폭개선 [#69](https://github.com/qw5414/attendance/pull/69) [@kaijess](https://github.com/kaijess)
* PC버전 날자 클릭하여 출석 현황보기를 모든회원에게 보이도록 개선. [#71](https://github.com/qw5414/attendance/issues/71)
* 모바일 상단 테스트 내용을 나타나지 않도록 할 필요성 있음 [#70](https://github.com/qw5414/attendance/issues/70)
* NOISSUES php 마지막 "?>" 문구제거
* 랜덤포인트를 세분화 하여 사용하도록 쓰기 [#67](https://github.com/qw5414/attendance/issues/67)

**Ver. 2.2**
* 옵션을 통해 첫 출석일부터 30일간격으로 월개근을 구할 수 있도록 개선. [#50](https://github.com/qw5414/attendance/pull/50) [CONORY](https://conory.com)
* 출석하면 문서모듈에서 기록할 수 있도록 한 것을 옵션화 하여 사이트들에 맞게 사용할 수 있도록 개선. [#63](https://github.com/qw5414/attendance/pull/63)
* XE1.5 지원코드 삭제 [#64](https://github.com/qw5414/attendance/pull/64)

**Ver. 2.1**
* $config 설정 값들을 정식 procAttendanceadmin에서 처리 할 수 있도록 개선.(attendance.admin.controller.php 파일에서처리합니다.) [#57](https://github.com/qw5414/attendance/pull/57)
* 기본 출석부 모듈 스킨 저장소를 부트스트랩 스킨으로 교체 [#55](https://github.com/qw5414/attendance/pull/55)
* SSL 사용하는 sosifam 원사이트를 따라 사용자정보수집용 ifream 의 주소 변경 [#58](https://github.com/qw5414/attendance/pull/58)
* 스킨의 ul table 같은 태그 속성의 css초기화를 모듈 스킨에서만 처리할 수 있도록 개선. [#59](https://github.com/qw5414/attendance/pull/59)
* 일부 코드 최적화 [#18](https://github.com/qw5414/attendance/pull/18)


**Ver. 2.0.3**
* XE자료실 쉬운설치 지원 패치

**Ver. 2.0.2**
* XE1.5 버전에서 2.0의 사용이 불가능 해 보여 2.0.2버전으로 업뎃.
* 모든 코드 최적화 ( 모바일 스킨 선택 일반 view 스킨 선택의 설정을 최적화 )


**Ver. 2.0.1**
* 부트스트랩 스킨을 attendance 모듈에 모두 통합.
* xe1.5 지원중단
* $config_data 테이블에 설정값이 있을경우 $config 으로 옮겨지도록 개선.
* PHP 호환성 개선 및 참조 연산자 삭제 ( [@smaker](https://github.com/smaker) ) [#42](https://github.com/qw5414/attendance/pull/42)


**Ver. 2.0**
* 어드민 페이지 내의 config_data 함수를 모두 제거. [#19](https://github.com/qw5414/attendance/issues/19)
* 모듈 설정값을 config 로잡고 모든 설정값 추가. [#19](https://github.com/qw5414/attendance/issues/19)
* 년간 개근 포인트의 함수가 바르지 않아 포인트 지급이 되지 않던 문제 개선.
* PHP5.4 버전에서 추가 오류사항 추가. ( [@smaker](https://github.com/smaker) ) [#34](https://github.com/qw5414/attendance/issues/34)


**Ver. 1.8.5**
* php5.4 버전에서 사용할 수 없던 문제 개선 ( [@smaker](https://github.com/smaker) ) [#26](https://github.com/qw5414/attendance/pull/26)
* 랜덤포인트에서 사용을 하지 않는 코드 삭제
* 사용자 환경 수집 캐시파일 위치 변경
* php5.4 호환성으로 인한 관리자임의 출석기록 오류 개선.

**Ver. 1.8.3**
* 타 모듈에서 트리거 디스플레이 사용되는 모듈들과 충돌이 일어나는 문제 개선 (이것으로 인해서 홈페이지내에서 설정이 먹히지 않았습니다. 정보 제공 : 몽실아빠)
* 회원의 회원정보중에 생일을 수정할 수 있도록 옵션 주도록 개선.
* $lang값의 부재를 다 채워 넣었..(하앍..????????????????????????????)

**Ver. 1.8.2**
* 생일 포인트 설정 관련 추가
* 생일 포인트는 module config 를 사용하도록 개선
* 생일포인트를 사용한다고 설정하였을때 출석부의 의해 회원정보중 생일을 수정할 수 없도록 개선(삭제도 못하도록 변경)

**Ver. 1.8.1**
* 어드민 페이지에서 사용자 환경수집을 동의 혹은 거부 하엿을경우 잘못된 링크로 연결되던 현상개선.
* 출석부 기본 스킨의 어드민으로 연결되는 링크 수정

**Ver. 1.8.0**
* 오타수정
* 1.7.4 버전의 호환성 개선  ( [@BNU](https://github.com/BNU) )

**Ver. 1.7.9**
* 꽝확률, 랜덤 포인트 설명이 $lang 변수로 만들지 못하고 잘못된 영어로만 들어간 것 개선

**Ver. 1.7.8**
* 랜덤포인트에 꽝걸릴수 있도록 개선
* 꽝일경우 기본 출석부스킨에 랜덤포인트 항목에 "꽝"이라는 문구가 뜨도록 개선.
* xe1.5 호환성 개선. #13 #14 

**Ver. 1.7.7**
* 랜덤 포인트 이벤트 마련
* 랜덤 포인트설정에서 최솟값의 숫자가 최댓값보다 크거나 같을 경우 작동하지 않도록 구성.
* 랜덤 포인트설정으로 인한 $lang 구문 추가
* 사용자 정보수집을 동의하였을경우 사용자정보 수집관련 iframe 구문은 어드민 아래쪽에서 나타나도록 개선.
* 사용자 스킨에서 {$data->today_random} 변수를 사용하면 랜덤 포인트로 받은 포인트만 개별적으로 뜨도록 개선

**Ver. 1.7.5**
* XE 1.5 버전에서 어드민페이지가 보이지 않던 현상 개선
* 사용자 수집현황을 어드민페이지에서 최상단에 보이도록 배치

**Ver. 1.7.4**
* 쉬운설치를 위한 재 압축 및 업로드

**Ver. 1.7.3** 
* xe1.4버전에서 어드민 페이지가 정상적으로 안뜰거라 생각하고 admin페이지 부분에 1.4를위한 코드 입력.
* 기존 제작자의 이름 수정 및 전체 적인 코드 최적화
* 기본 스킨 최적화

**Ver. 1.7.2** 
* 연간 개근하였을경우 매달 말일날 연간 개근항목이 뜨던 현상 개선.

**Ver. 1.7.1** 
* 출책 금지시간을 설정하여도 출책이 가능하던 문제점 개선

**Ver. 1.7**
* 사용자정보수집기능추가.(동의 동의안함 버튼으로 작동).
* 관리자 환경 대폭개선.
* 모바일 스킨을 선택하지 못하던 문제개선.
* 모바일 스킨설정페이지 추가
