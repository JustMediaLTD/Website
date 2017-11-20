<?php
/************************************************************/
$rcsid='$Id: msdbMoreConfig.php,v 1.18 2004/08/23 09:18:00 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/
// session expires after being idle this long
if ( ! isset($msdbConfig['SID_EXP']) )
        $msdbConfig['SID_EXP'] = 3600 ;
/******************************/
// tables not explicitely permitted in msdb_permit are read-only if this is set
if ( ! isset($msdbConfig['READ_ONLY_MODE']) )
        $msdbConfig['READ_ONLY_MODE'] = false ;
/******************************/
// how many rows is considered 'a lot'
if ( ! isset($msdbConfig['LOTS_OF_ROWS']) )
        $msdbConfig['LOTS_OF_ROWS'] = 1000;
/******************************/
// how many rows to show when no select criteria
if ( ! isset($msdbConfig['showRows']) )
        $msdbConfig['showRows'] = 20 ;
/******************************/
// maximum rows to show (even with a select criterion )
if ( ! isset($msdbConfig['limitRows']) )
        $msdbConfig['limitRows'] = 70 ;
/******************************/
// round to this many digits in floats in reports
if ( ! isset($msdbConfig['statsFloatRound']) )
        $msdbConfig['statsFloatRound'] = 2 ; // money style by default
/******************************/
// do not show autoIncrement field(s) on screen
if ( ! isset($msdbConfig['noAi']) )
        $msdbConfig['noAi'] = true ;
/******************************/
// show internal data structures and performance information
if ( ! isset($msdbConfig['msdbInfo']) )
        $msdbConfig['msdbInfo'] = false ;
/************************************************************/
$msdbDefOrderBy = array();
$msdbDefWhere = array();
// $msdbDefWhere['addressBook'] = "where name like 'ohad%'" ;
// $msdbDefOrderBy['addressBook'] = "name asc, zip desc";
/************************************************************/
?>
