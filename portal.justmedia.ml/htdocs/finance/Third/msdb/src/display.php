<?php
/************************************************************/
#include "msdb.h"
$rcsid='$Id: display.php,v 1.63 2004/08/23 09:17:35 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/

function msdbTableHead()
{
	global $dbMeta;

	$str = "" ;

	foreach ( $dbMeta->msdbFields as $f ) {
		if ( ! msdbIsVisible($f) )
			continue;
		$fname = $f->fname;
		$tdText = "<B>$fname</B> ";
		if ( $dbMeta->orderBy == $fname ) {
			$upOrDown = $dbMeta->isDesc == 'true' ? "sorterDown.gif" : "sorterUp.gif" ;
			$iconDir = ( $hu = msdbMyHomeUrl() ) ? "$hu".'icons' : 'icons' ;
			$obTxt = " <IMG BORDER=0 SRC=\"$iconDir/$upOrDown\"> " ;
		} else
			$obTxt = "";
		$sortByUrl = msdbJsCmd("", "&orderBy=$fname");
		$tdContent = "<A HREF=\"$sortByUrl\">$tdText</A>$obTxt";
		$str .= "\t\t<TD>$tdContent</TD>\n" ;
	}
	return($str);
}

/******************************/

function msdbTdAlignment($f)
{
	$ftype = $f->ftype;

	$tdAligns = array(
			'string' => 'ALIGN=LEFT VALIGN=TOP',
			'real' => 'ALIGN=RIGHT VALIGN=MIDDLE',
			'int' => 'ALIGN=RIGHT VALIGN=MIDDLE',
			'date' => 'ALIGN=LEFT VALIGN=MIDDLE',
			'datetime' => 'ALIGN=LEFT VALIGN=MIDDLE',
			'timestamp' => 'ALIGN=RIGHT VALIGN=MIDDLE',
			'time' => 'ALIGN=LEFT VALIGN=MIDDLE',
		);

	if ( isset($tdAligns[$f->ftype]) )
		return($tdAligns[$f->ftype]);

	msdbError($f->ftype);

	return('');

}

/******************************/

function msdbTableLineContent($row)
{
	global $dbMeta;

	$colNum = $dbMeta->colNum;

	$rowColNum = count($row);

	if ( $colNum != $rowColNum ) {
		MSDB_ERROR("msdbTableLineContent: row with $rowColNum columns, expected $colNum");
		return(null);
	}
	

	$str = "" ;
	for($i=0;$i<$colNum;$i++) {
		$f = $dbMeta->msdbFields[$i];
		if ( ! msdbIsVisible($f) )
			continue;

		$dbVal = $row[$i] ;
		if ( $f->ftype == 'date' && msdbDayIsZero($dbVal) )
			$show = '' ;
		else if ( isset($dbVal) && ! is_null($dbVal) )
			$show = htmlspecialchars($dbVal) ;
		else
			$show = "";

		$al = msdbTdAlignment($f);
		$str .= "\t<TD $al>$show</TD>\n" ;
	}
		

	return($str);
}

/******************************/

function msdbShowTableRow($row, $rowIndex)
{
	global $dbMeta;

	$pkf = & $dbMeta->pkField;

	$isKey = ( $pkf != null ) ;

	$lineContent = msdbTableLineContent($row);

	if ( $lineContent == null )
		return;

	if ( $isKey ) {
		if ( isset($row[$dbMeta->pkIndex]) )
			$pkValue = $row[$dbMeta->pkIndex] ;
		else {
			msdb_r($row);
			$sr = serialize($row);
			MSDB_ERROR("index=$dbMeta->pkIndex but row is $sr");
			$pkValue = 'None' ;
		}
	}

	if ( $dbMeta->curId && $isKey && $dbMeta->curId == $pkValue )
		$lc = 'currentLineClass' ;
	else if ( $rowIndex % 2 == 0 )
		$lc = 'evenLineClass' ;
	else
		$lc = 'oddLineClass' ;

	msdbInclude('include/line.h', array('lineClass' => $lc, 'lineNumber' => $rowIndex + 1));

	echo $lineContent;

	if ( ! $isKey ) {
		msdbInclude('include/line.noKey.t', null);
		return;
	}

	$q =  ( $pkf->ftype == 'string' ) ? "'" : "" ;
	if ( isset($_REQUEST['printerFriendly']) )
		echo "<TR>\n";
	else
		msdbInclude('include/line.t', array('pkValue' => $q.$pkValue.$q));
}

/******************************/

function msdbShowTableHeader()
{
	global $dbMeta;

	msdbInclude('include/tableHead.h');
	echo  msdbTableHead();
	if ( isset($_REQUEST['printerFriendly']) )
		echo "<TR>\n";
	else
		msdbInclude('include/tableHead.t');
}

/******************************/

function msdbShowTableTailer()
{
	echo "</TABLE>\n";
}

/******************************/

function msdbFieldInputSize($f)
{
	global $dbMeta;

	if ( $f->fname == $dbMeta->primaryKey )
		$sz = 5 ;
	else if ( $f->ftype == 'int' )
		$sz = 7 ;
	else if ( $f->ftype == 'real' )
		$sz = 9 ;
	else
		$sz = round(80 / $dbMeta->colNum);

	if ( $sz < 5 )
		return(5);

	return($sz);
	
}

/******************************/

function msdbIsVisible($f)
{
	global $msdbConfig;
	global $dbMeta;

	$pkf = $dbMeta->pkField;
	$dontShow =
		( isset($msdbConfig['noAi']) && $msdbConfig['noAi'] == true || isset($_REQUEST['msdbNoAi']) ) &&
		$f->fname == $pkf->fname &&
		$pkf->isAutoInc
		;
	return(! $dontShow);
}

/******************************/

// present fields to search by
// IE6 crashes if too many fields are in this form/table
// so put harsh limits

// store for each type an array of field names and fields we crossed already.
$searchables = array();

function msdbIsSearchable($f)
{
	global $searchables;

	// limit each datatype to how many select fields
	$tyLimits = array(
			'string' => 5,
			'int' => 2,
			'real' => 1,
			'date' => 1,
			'datetime' => 1,
			'time' => 0,
			'timestamp' => 0,
		);


	$ftype = $f->ftype;
	$fname = $f->fname ;

	if ( ! isset($tyLimits[$ftype]) ) {
		MSDB_ERROR("msdbIsSearchable: No tyLimits entry for $ftype");
		return(false);
	}

	if ( ! isset($searchables[$ftype] ) )
		$searchables[$ftype] = array();

	if ( isset($searchables[$ftype][$fname]) )
		return($searchables[$ftype][$fname]);

	if ( count($searchables[$ftype]) < $tyLimits[$ftype] )
		$ret = true;
	else
		$ret = false;
	$searchables[$ftype][$fname] = $ret;
	return($ret);
}

/******************************/


// not much intelligence here
// should be decided field by field

function msdbNewOrChangeForm($isSearch = false)
{
	global $dbMeta;

	$fs = $dbMeta->msdbFields;

	echo "\t\t<TD>#</TD>\n" ;

	foreach ( $fs as $f ) {
		if ( $isSearch && ! msdbIsSearchable($f) )
			continue;
		$sz = msdbFieldInputSize($f);
		// it would be nice to show the values of uniserted data, here
		// so the javascript code can detect that this popup occured
		// and not fill with real data after a failed insert
		// but not if this is in the searchForm
		if ( ! $isSearch && $dbMeta->iuFailed )
			$value = "";
		else
			$value = "";
		if ( msdbIsVisible($f) ) {
			$textBox = "<INPUT type=text size=$sz maxlength=254 name=\"$f->fname\" value=\"$value\">" ;
			$colSpan = ( $isSearch ) ? 2 : 1 ;
			echo "\t\t<TD COLSPAN=$colSpan>$textBox</TD>\n" ;
		} else {
			$textBox = "<INPUT type=hidden name=\"$f->fname\" value=\"$value\">" ;
			echo "\t\t$textBox\n" ;
		}
	}
}

/******************************/

function msdbPopUpHeading()
{
	global $dbMeta;

	$str = "\t\t<TD></TD>\n" ;

	foreach ( $dbMeta->msdbFields as $f ) {
		if ( ! msdbIsVisible($f) )
			continue;
		$fname = $f->fname;
		$str .= "\t\t<TD>$fname</TD>\n" ;
	}
	$str .= "\t\t<TD COLSOAN=3></TD>\n\t</TR>\n\t<TR>\n";
	return($str);
}

/******************************/

function msdbSearchHeading()
{
	global $dbMeta;
	$msDdOps = array('=', '>', '<', '>=', '<=', '!=', 'like');
	/*	$msDdOps = array('=', '>', '<', '>=', '<=', '!=', 'like', 'not like');	*/

	$str = "\t\t<TD></TD>\n" ;

	foreach ( $dbMeta->msdbFields as $f ) {
		if ( ! msdbIsVisible($f) )
			continue;
		if ( ! msdbIsSearchable($f) )
			continue;
	
		$ops = msdbHtmlOptions($msDdOps, $msDdOps, '=', null, "msdbOp_$f->fname");
		$fname = $f->fname;
		$str .= "\t\t<TD ALIGN=LEFT>$fname</TD><TD ALIGN=RIGHT>$ops</TD>\n" ;
	}
	$str .= "\t\t<TD COLSOAN=3></TD>\n\t</TR>\n\t<TR>\n";
	return($str);
}

/******************************/

function msdbSearchForm()
{
	msdbInclude('include/search.h', array('msdbEA' => 'msdbSearch'));
	echo msdbSearchHeading();
	msdbNewOrChangeForm(true);
	msdbInclude('include/search.t', null);
}

/******************************/

function msdbChangeForm()
{
	msdbInclude('include/change.h', array('msdbEA' => 'msdbUpdate'));
	echo msdbPopUpHeading();
	msdbNewOrChangeForm();
	msdbInclude('include/change.t', null);
}

/******************************/

function msdbNewForm()
{
	msdbInclude('include/new.h', array('msdbEA' => 'msdbUpdate'));
	msdbNewOrChangeForm();
	msdbInclude('include/new.t', null);
}

/******************************/

function msdbShowTable($rows)
{
	msdbAppBar(count($rows));

	msdbShowTableHeader();
	for($i=0;$i<count($rows);$i++)
		msdbShowTableRow($rows[$i], $i);
	if ( ! isset($_REQUEST['printerFriendly']) )
		msdbNewForm();
	msdbShowTableTailer();
}

/******************************/
?>
