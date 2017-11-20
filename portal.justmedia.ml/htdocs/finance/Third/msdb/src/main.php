<?php
/************************************************************/
#include "msdb.h"
$rcsid='$Id: main.php,v 1.12 2004/05/07 19:55:30 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/
error_reporting(E_ALL|2048) ; // 2048 is E_STRICT
/************************************************************/
require_once("globals.php");
require_once("functions.php");
/************************************************************/
if ( msdbPreInstall() )
	msdbHtbrowse() ;
/************************************************************/
?>
