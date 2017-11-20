<?php
/************************************************************/
#include "msdb.h"
$rcsid='$Id: compat.php,v 1.4 2004/05/07 19:55:26 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/

if (!function_exists('file_get_contents')) {
	function file_get_contents($filename) {
	return implode("", file($filename));
	}
}

/************************************************************/

if ( ! function_exists('file_put_contents') ) {
    function file_put_contents($filename, $contents) {
        if ( ! ($h = fopen($filename, "w")) ) 
            return(-1);
        if ( $ret = fwrite($h, $content) )
            return(-1);
        fclose($h);
        return($ret);
    }
}

/************************************************************/

if ( ! function_exists('array_combine')) {
	function array_combine($k, $v) {
		$ret = array();
		for($i=0;$i<count($k);$i++)
			$ret[$k[$i]] = $v[$i];
		return($ret);
	}
}

/************************************************************/
?>
