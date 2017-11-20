<?php
/************************************************************/
#include "msdb.h"
$rcsid='$Id: info.php,v 1.37 2004/07/29 08:48:02 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/

function msdbInfoPre($n)
{
	echo "<TR><TD class=msdbInfoName>$n</TD><TD class=msdbInfoData>";
}

/**********/

function msdbInfoPost()
{
	echo "</TD></TR>\n";
}

/**********/

function msdbInfo1($varname)
{
	msdbInfoPre($varname);

	$str = "global \$$varname;\n\tif( isset(\$$varname) )\n\t\tmsdb_r(\$$varname);\n\telse\n\t\techo 'Not Set';\n" ;

	/*	echo "<HR><BR><PRE>\n"; echo htmlspecialchars($str); echo "\n</PRE><BR><HR>\n" ;	*/

	eval($str);

	msdbInfoPost();
}

/**********/

function msdbInfo()
{
	$title = "<FONT COLOR=BLUE><B>msdbInfo(): Internals and Debugging Information</B></FONT>";

	echo "<BR>";

	echo "<TABLE BORDER=0 CELLPADDING=2 CELLSPACING=2>\n";

	echo "<TR><TD COLSPAN=2><CENTER>$title</CENTER></TD></TR>\n";

	/*	msdbInfo1('msdbUserPrefs');	*/
	msdbInfo1('dbMeta');
	/*	msdbInfo1('_SESSION');	*/
	/*	msdbInfo1('_COOKIE');	*/
	/*	msdbInfo1('_GET');	*/
	/*	msdbInfo1('_POST');	*/
	/*	msdbInfo1('_REQUEST');	*/
	/*	msdbInfo1('_SERVER');	*/
	/*	msdbInfo1('msdbQueries');	*/


	// msdbConfig holds the mysql entry password
	// msdbEnterVar holds the msdb entry password (user supplied and database entry)
	// don't display these in a non-secure environment
	//
	/*	msdbInfo1('msdbConfig');	*/
	msdbInfo1('msdbEnterVar');



	/*	msdbInfo1('dbHandle');	*/
	/*	msdbInfo1('msdbStats');	*/
	/*	msdbInfo1('msdbPerfStamps');	*/
	msdbInfo1('searchables');

	echo "</TABLE>\n\n" ;
}

/************************************************************/
?>
