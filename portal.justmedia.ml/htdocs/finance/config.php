<?php
/************************************************************/
$rcsid='$Id: config.php,v 1.9 2004/08/05 10:04:22 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://www.engine.com/license.txt (BSD)";
/************************************************************/
$cbVersion = '1.0' ;
/************************************************************/
// installation and first use instruction
// 1. set database access DB_HOST DB_USER DB_PW and DB_NAME below.
// 2. point the browser to the installation folder.

$msdbConfig['DB_HOST'] = 'localhost' ;
$msdbConfig['DB_USER'] = 'cb' ;
$msdbConfig['DB_PW'] = 'cb' ;
$msdbConfig['DB_NAME'] = 'cb' ;
// $cbConfig['tableName'] = 'someTableName' ;
/******************************/
// the name of the table can be set here, otherwise:
// It is 'cashbook' by default,
// or if authentication using htpasswd is in place it is cb_$user
// or it can be passed as cbTable=tableName in the url
/************************************************************/
// Smarty not used in this version

/*	$cbDir = "/var/www/html/cb" ;	*/
/*	$thirdDir = "$cbDir/Third" ;	*/
/*	$msdbDir = "$thirdDir/msdb" ;	*/
/*	$smartyInstallDir = "$thirdDir/Smarty" ;	*/
/*	$smartyRunDir = "$cbDir/smarty" ;	*/
/************************************************************/
/*	require_once("$smartyInstallDir/libs/Smarty.class.php");	*/
/*	global $smarty;	*/
/*	$smarty = new Smarty();	*/
/*	$smarty->template_dir	= "$cbDir";	*/
/*	$smarty->compile_dir    = "$smartyRunDir/compile/";	*/
/*	$smarty->config_dir     = "$smartyRunDir/config/";	*/
/*	$smarty->cache_dir		= "$smartyRunDir/cache/";	*/
/************************************************************/
require_once("Third/msdb/library.php");
/************************************************************/
// create data with each table cb creates on its own
$cbConfig['Tutor'] = false ;
/************************************************************/
?>
