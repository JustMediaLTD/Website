<?php
$rcsid='$Id: msdbTest.php,v 1.3 2004/01/13 13:08:00 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
function msdbTest()
{
        global $dbData;

        $n = msDbGetRows("select * from msdb_passwd limit 0");
        echo "msDbGetRows returned $n rows";
        msdbInfo();
}


?>
