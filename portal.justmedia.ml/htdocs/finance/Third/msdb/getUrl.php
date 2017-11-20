<?php
/************************************************************/



$rcsid='$Id: getUrl.php,v 1.1 2004/05/31 14:55:57 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/

// get an url, giving it post data.
// return, the file content

function msdbPostUrl($url, $post)
{
        if ( ! ($fp = @fopen($url, "rb")) ) {
                msdbMsg("Can not open $url");
                return(null);
        }

}

/************************************************************/
?>
