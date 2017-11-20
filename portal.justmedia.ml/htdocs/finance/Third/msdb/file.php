<?php
/************************************************************/
$rcsid='$Id: file.php,v 1.3 2004/02/26 11:32:03 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/

function msdbLs($dir)
{
        $h = opendir($dir) ;

        if ( ! $h )
                return(null);

        $ret = array();

        while ( 1 ) {
                $file = readdir($h);
                if ( ! is_string($file) ) {
                        closedir($h);
                        sort($ret);
                        return($ret);
                }
                if ( $file == '.' || $file == '..' )
                        continue;

                $ret[] = $file;
        }
        /* not reached */
        return(null);
}

/************************************************************/
?>
