<?php
/************************************************************/
#include "msdb.h"
$rcsid='$Id: htbrowse.php,v 1.96 2004/08/21 12:38:25 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/

function msdbHeader()
{
	$title = msdbTitle();

	msdbInclude('include/doctype.h', null);
	msdbInclude('include/dochead.h', null);
	echo "<TITLE>$title</TITLE>\n";
	msdbInclude('include/jsSrc.h', null);
	echo "</HEAD>\n<BODY>\n\n" ;

	msdbInclude('include/mOver.h', null);
	msdbInclude('include/header.h', null);
	msdbShowMenu();
	echo "<BR><BR><BR>\n" ;
}

/************************************************************/

function msdbTailer()
{
	echo "<BR><BR><BR>\n" ;
	msdbShowMenu();
	msdbInclude('include/tailer.h', null);
	echo "</BODY>\n</HTML>\n" ;

}

/************************************************************/

function msdbListDBs()
{
	if ( msDbIsOnlyDB() ) {
		msdbMsg("Only DB Mode");
		return;
	}
	msdbUserPrefSetProperty('DB', -1);

	msdbMsg("Select a Database");

	$dbs = msDbDataBases();

	// ??? ... only list tables when few databases are around
	// $listTables = ( count($dbs) <= 10 ) ;

	foreach ( $dbs as $db )
		$perms[$db] = msDbDatabaseHasTable($db, 'msdb_permit');

	msdbInclude('include/listDBs.h', null);
	foreach ( $perms as $db => $isPerm ) {
		$cls = ( $isPerm ) ? "CLASS=dbIsPerm" : "CLASS=dbIsntPerm";
		echo "<TR $cls>\n";
		echo "<TD>$db</TD>\n" ;
		echo "<TD>";
		if ( $isPerm ) {
			$ts = msDbDatabaseTables($db);
			foreach ( $ts as $t ) {
				$url = msdbJsCmd("", "&msdbDB=$db&msdbTNAME=$t");
				echo "<A HREF=\"$url\">$t</A> ";
			}
		} else {
			msdbMsg("Please install msdb_permit in Database $db:");
			echo "<PRE>\n";
			readfile('Install/msdb_permit.crtable');
			echo "</PRE>\n";
		}
		echo "</TD>";
		echo "</TR>\n";
	}

	msdbInclude('include/listDBs.t', null);
}

/******************************/

function msdbSelectTable()
{
	msdbMsg("Select a table to browse");

	$tables = msDbTables();

	msdbInclude('include/listTables.h', null);
	foreach ( $tables as $tname ) {
		$rownum = msDbRowNum($tname);
		$tableUrl =  msdbTableUrl($tname);
		$vars = array( 'selectTable' => $tname, 'tableUrl' => $tableUrl, 'rownum' => $rownum);
		msdbInclude('include/listTables.b', $vars);
	}
	msdbInclude('include/listTables.t', null);
	echo "</TABLE>";
}

/******************************/

function msdbAppBar($rows)
{
	global $dbMeta;

	$tname = $dbMeta->tname;
	$tableUrl =  msdbTableUrl($tname);

	$w = $dbMeta->where;
	$tlink="<A HREF=\"$tableUrl\">$tname</A>";
	$we = $dbMeta->whereEtc ;
	$rn = $dbMeta->rowNum;
	$s = ( $rows == 1 ) ? "" : "s" ;
	if ( $rows != $rn )
		$rns = "($rows row$s of $rn total)";
	else
		$rns = "" ;

	$str = "Showing $tlink $we $rns.";

	msdbInclude("include/appBar.h", array('appBarTitle' => $str));
}

/******************************/

function msdbWhereEtc()
{
	global $dbMeta;

	$ob = $dbMeta->orderBy;
	$iio = $dbMeta->insertIdOr;
	$mw = $dbMeta->where;
	$limit = $dbMeta->limit;

	if ( $iio && $limit )
		$w = "where $iio";
	else if ( $mw == "" )
		$w = "" ;
	else if ( $iio )
		$w = "$mw or $iio";
	else
		$w = $mw ;
		

	if ( $ob ) {
		$obStr = "order by $ob" ;
		if ( $dbMeta->isDesc == 'true' )
			$obStr .= " desc";
		
	} else
		$obStr = "" ;

	$dbMeta->whereEtc = "$w $obStr $limit";
}

/******************************/

function msdbPresentTable()
{
	global $dbMeta;

	MSDB_PERF('');
	msdbStatPrepare();
	MSDB_PERF('msdbStatPrepare');

	if ( $dbMeta->ea ) {
		MSDB_PERF('');
		msdbAction();
		MSDB_PERF('msdbAction');
	}

	MSDB_PERF('');

	// action if any, may have change curId
	// try to get an old one if not
	if ( ! $dbMeta->curId )
		$dbMeta->curId = msdbUserPrefGetProperty('curId');
	// and set insertIdOr so only now we can the full detail about 'whereEtc'
	msdbWhereEtc();

	MSDB_PERF('');

	$sql = "select * from $dbMeta->tname $dbMeta->whereEtc";
	if ( ($rows = msDbFetchRows($sql)) === null )
		MSDB_ERROR($sql);
	else
		msdbShowTable($rows);

	MSDB_PERF('msdbShowTable');

	msdbSend($rows);
	MSDB_PERF('msdbSend');
	msdbStatistics();
	MSDB_PERF('msdbStatistics');

	msdbInclude('include/crtableLink.h', null);

	// for some reason, placing this DIV before any links, disables
	// some of the links that follow in IE6 until the div is made visible (by user request).
	// if anyone knows how I can fix this, please e-mail nekko@engine.com , thanks
	msdbChangeForm();
	MSDB_PERF('msdbChangeForm');
	msdbSearchForm();
	MSDB_PERF('msdbSearchForm');
}

/******************************/

function msdbHtbrowse()
{
	global $dbMeta;
	global $msdbEnterVar;
	global $msdbConfig;

	MSDB_PERF('');

	msdbHoldMsgs();

	MSDB_PERF('');
	$isH =  msdbHead() ;
	MSDB_PERF('msdbHead');

	msdbHeader();

	MSDB_PERF('msdbHeader');

	msdbFlushMsgs();

	MSDB_PERF('msdbFlushMsg');

	if ( $isH || $dbMeta->noPerm ) {
		if ( $dbMeta->ea == 'msdbSelectDB' ) {
			// before a switch DB
			msdbListDBs();
			MSDB_PERF('msdbSelectDB');
		} else if ( ! $dbMeta->tname || $dbMeta->noPerm ) {
			msdbSelectTable();
			MSDB_PERF('msdbSelectTable');
		}
		else
			msdbPresentTable();
	} else {
		/*	msdb_r($msdbEnterVar->pwent);	*/

		if ( $msdbEnterVar->pwent )
			$user = $msdbEnterVar->pwent['name'];
		else if ( $msdbEnterVar->name )
			$user = $msdbEnterVar->name ;
		else
			$user = 'username' ;

		msdbInclude('include/logon.h', array('msdbUSER' => $user) );
	}

	MSDB_PERF('Done');


	if ( isset($msdbConfig['msdbInfo']) && $msdbConfig['msdbInfo'] || isset($_GET['msdbInfo']) ) {
		msdbShowPerf();
		msdbInfo();
	}

	msdbTailer();
}

/************************************************************/
?>
