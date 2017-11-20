<?php
/************************************************************/
#include "msdb.h"
$rcsid='$Id: test.php,v 1.9 2004/01/28 13:15:53 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/
/*
 * test something
 *
 * this function is called when msdbEA=Test in the URL.
 * 
 * php has the msdb environment in place.
 * the page has its msdb wrapping in place, and is otherwise
 empty save what this test output at the moment.
 */
/******************************/


function msdbTest()
{
	global $dbMeta;

	$str = serialize($dbMeta->whereEtc);
	var_dump($str);
	$ev = unserialize($str);
	var_dump($ev);
	msdb_r($ev);

	msdbInfo();
}

/************************************************************/
?>
