<?php
/************************************************************/

/*	#define MSDB_MSG(m) msdbMsg(__FILE__.": ".__LINE__.": ".(m))	*/

$rcsid='$Id: aliases.php,v 1.6 2004/01/23 19:07:30 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/******************************/
/*
 * Arrays
 */

/******************************/

function msdbArrValIn($v, $a)
{
        if ( ! is_array($a) )
                return(false);

        return(in_array($v, $a));
}

/******************************/

function msdbArrKeyIn($k, $a)
{
        if ( ! is_array($a) )
                return(false);

        return(array_key_exists($k, $a));
}

/************************************************************/

?>
