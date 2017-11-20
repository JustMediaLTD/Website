<?php
/************************************************************/
#include "msdb.h"
$rcsid='$Id: msdbConfig.php,v 1.58 2004/09/02 12:11:01 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/
// installation instructions:
//		having set the follwing 4 values correctly,
// 		to enable database access,
//		point the web browser to this folder.
//
//	e.g http://yourhost.yourdomain.com/MSDBinstallDir/
// 

global $msdbConfig;

if ( ! isset($msdbConfig['DB_USER']) ) {
	$msdbConfig['DB_HOST'] =  'localhost' ;
	$msdbConfig['DB_USER'] =  'msdb' ;
	$msdbConfig['DB_PW'] =  'msdb' ;
	$msdbConfig['DB_NAME'] =  'msdb' ;
}
/************************************************************/
$msdbConfig['mailErrorsTo'] =  'nekko@engine.com' ;
if ( ! isset($msdbConfig['ONLY_DB']) )
	$msdbConfig['ONLY_DB'] = true ; // only access $msdbConfig['DB_NAME']
/************************************************************/
?>
