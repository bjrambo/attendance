<?php

/*
 * 클론탭 전용 페이지입니다.
 * 클론탭을 위해서는 리눅스 서버를 직접 운영할 수 있어야 합니다.
 * 해당 실행을 매 00시마다 실행되도록 하세요.
 */
define('__XE__', true);
require_once('../../config/config.inc.php'); //XE config.inc.php 주소
$oContext = &Context::getInstance();
$oContext->init();

$display = new DisplayHandler();
/** @var attendanceController $oAttendanceController */
$oAttendanceController = getController('attendance');
$oAttendanceController->setOpenAttendanceTime();
$display::getDebugInfo();
