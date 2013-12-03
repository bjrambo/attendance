attendance
==========

XE 출석부

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

참고사항
--------
출석부모듈 1.6.5 버전부터 주간개근 함수 조건문이 바뀌었습니다.
다음과같이 수정을 해주셔야 합니다.
각 스킨 파일에서 다음 문구를 찾으세요.
각스킨의 index.html 파일에 있습니다.

$weekly->weekly == 7 && $selected_date == $week->sunday

이문구를 
$weekly->weekly == 7 && $selected_date == $week->sunday1

으로 바꿔주시기 바랍니다 (제일끝부분에 1을 추가하여 주시기 바랍니다.
단, 기본 스킨은 해당 모듈설치시 같이 변경이 되기 때문에 수정사항을 거치지 않아도 됩니다.

업뎃 사항 
---------

**Ver. 1.7.2** 
* 연간 개근하였을경우 매달 말일날 연간 개근항목이 뜨던 현상 개선.

**Ver. 1.7.1** 
* 출책 금지시간을 설정하여도 출책이 가능하던 문제점 개선

**Ver. 1.7**
* 사용자정보수집기능추가.(동의 동의안함 버튼으로 작동).
* 관리자 환경 대폭개선.
* 모바일 스킨을 선택하지 못하던 문제개선.
* 모바일 스킨설정페이지 추가
