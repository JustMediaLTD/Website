<?php
/************************************************************/



$rcsid='$Id: globals.php,v 1.54 2004/07/29 08:48:00 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/
$msdbVersion = '1.1.2' ;
/******************************/
$dbHandle = null; // connection information
$dbMeta = null; // Meta Data and other globabl info
$msdbEnterVar = null; // logon Info
$dbTnameRows = array(); // rows displayed on this page
$msdbPerfStamps = array() ; // performance measurements
$msdbQueries = array() ; // queries to db
$msdbUserPrefs = array('fromDB' => array(), 'fromUser' => array(), 'runWith' => array()) ;
$msdbStats = null ;
/************************************************************/
?>
