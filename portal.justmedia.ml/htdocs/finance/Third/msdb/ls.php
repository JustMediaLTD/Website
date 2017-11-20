<?php
/************************************************************/
$rcsid='$Id: ls.php,v 1.3 2004/07/31 10:18:39 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://www.engine.com/license.txt (BSD)";
/************************************************************/
?>
<HTML>
<HEAD>
<TITLE>ls</TITLE>
</HEAD>
<BODY>


<?
/************************************************************/
$files = split ("\n", `ls`);
foreach ( $files as $file )
        echo "<A HREF=\"$file\">$file</A><BR>\n";
end
/************************************************************/
?>


</BODY>
</HTML>
