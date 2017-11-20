<?php
/************************************************************/
#include "msdb.h"
$rcsid='$Id: date.php,v 1.19 2004/09/02 12:10:53 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/

function msdbDayLeap($y)
{
	return( $y % 4 == 0 && $y % 100 != 0 || $y % 400 == 0 );
}

/******************************/

function msdbMonthLength($m, $y = null)
{
	$monthlen = array( 0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31) ;

	if ( $y == null )
		return($monthlen[$m]);

	return($monthlen[$m] + ( ( $m == 2 && msdbDayLeap($y)) ? 1 : 0 ));
}

/************************************************************/
/************************************************************/

function msdbDayDsub($date)
{

	list($y, $m, $d) = msdbDayBreak($date);

	if ( $d > 1 )
		return($date-1);

	if ( $m == 1 )
		return(($y-1)*10000 + 1231);

	$m--;

	$d = msdbMonthLength($m, $y);

	return(msdbDayCompose($y, $m, $d));
}

/******************************/

function msdbDayDadd($date)
{

	list($y, $m, $d) = msdbDayBreak($date);

	$mdays = msdbMonthLength($m, $y);

	if ( $d < $mdays )
		return($date+1);

	$d = 1;
	if ( $m < 12 )
		$m = $m+1;
	else {
		$m = 1;
		$y = $y+1;
	}
	return(msdbDayCompose($y, $m, $d));
}

/******************************/

function msdbDayMonthOf($date)
{
	return(((int)($date/100))%100);
}
/******************************/

function msdbDayMadd($date)
{
	list($y, $m, $d) = msdbDayBreak($date);

	if ( $m < 12 )
		return($date + 100 );
	else
		return($date - 1100 + 10000);
}

/******************************/

function msdbDayMsub($date)
{
	list($d, $m, $y) = msdbDayBreak($date);

	if ( $m > 1 )
		return($date - 100 );
	else
		return($date + 1100 - 10000);
}

/******************************/

function msdbDayWadd($date)
{
	for($i=0;$i<7;$i++)
		$date = msdbDayDadd($date);
	return($date);
}

/******************************/

function msdbDayWsub($date)
{
	for($i=0;$i<7;$i++)
		$date = msdbDayDsub($date);
	return($date);
}

/******************************/
function msdbDayYadd($date) { return($date+10000); }
function msdbDayYsub($date) { return($date-10000); }
/************************************************************/
/************************************************************/

// a human readable string representing the date
// (without the time)

function msdbDayFmt($date, $withWeek = true)
{
	if ( $withWeek )
		$fmt = "l F j, Y";
	else
		$fmt = "F j, Y";

	$ts = msdbDayToTs($date);

	return(date($fmt, $ts));
}

/******************************/

// the time now, as in 18:47 or 8:05

function msdbTimeNow()
{
	return(date("G:i"));
}

/******************************/

// a sql suitable string representing now.

function msdbDateTimeNow()
{
	
	return(date("Y-m-d H:i:s"));
}

/************************************************************/

// no, this is not dawn

function msdbDayBreak($dt)
{
	$d = $dt % 100 ;
	$m = ( (int)($dt/100) ) % 100 ;
	$y = (int)($dt / 10000);

	return(array($y, $m, $d));
}

/******************************/

function msdbDayCompose($y, $m, $d)
{
	return($y * 10000 + $m * 100 + $d);
	
}
/********************/

function msdbDayConstruct($y, $m, $d)
{
	list($ty, $tm, $td) = msdbDayBreak(msdbDayToday());

	if ( $y < 0 )
		return(null);
	else if ( $y == 0 )
		$y = $ty;
	else if ( $y < 50 )
		$y += 2000 ;
	else if ( $y < 100 )
		$y += 1900;

	if ( is_int($m) && $m == 0 )
		$m = $tm;
	else if ( $m < 1 || $m > 12 )
		return(null);

	// this will accept 2/31/1961 ???
	if ( $d < 1 || $d > 31 )
		return(null);

	return(msdbDayCompose($y, $m, $d));
}

/******************************/

function msdbDayToTs($dt)
{
	list($y, $m, $d) = msdbDayBreak($dt);

	$ts = mktime(0, 0, 0, $m, $d, $y);

	return($ts);
}

/******************************/

function msdbPhpDate($dt)
{
	$ts = msdbDayToTs($dt);
	return(getdate($ts));
}

/******************************/

function msdbWdayLname($wday)
{
	$wdaylstring = array(
		"Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
	);

	return($wdaylstring[$wday]);
}

/******************************/

function msdbWdayName($wday)
{
	$wdaystring = array(
		"Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"
	);

	return($wdaystring[$wday]);
}

/******************************/

function msdbMonthLname($m)
{
	$monthlname = array(
		"",
		"January", "February", "March", "April", "May", "June",
		"July", "August", "Sepember", "October", "November", "December"
	);

	return($monthlname[$m]);
}

/******************************/

function msdbMonthName($m)
{
	$monthname = array(
		"",
		"Jan", "Feb", "Mar", "Apr", "May", "Jun",
		"Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
	);

	return($monthname[$m]);
}

/******************************/

function msdbDayWday($dt)
{
	$a = msdbPhpDate($dt);
	return($a['wday']);
}

/******************************/

function msdbDayFromTs($ts)
{
	$arr = getdate($ts);
	$ret = msdbDayCompose($arr['year'], $arr['mon'], $arr['mday']);

	return($ret);
}

/******************************/

function msdbDayToday()
{
	return(msdbDayFromTs(time()));
}

/************************************************************/

function msdbDayScanEuro($s)
{
	$dmy = @ split('\.', $s);
	if ( count($dmy) == 3 )
		return(msdbDayConstruct($dmy[2], $dmy[1], $dmy[0]));
	if ( count($dmy) == 2 )
		return(msdbDayConstruct(0, $dmy[1], $dmy[0]));

	return(null);
}

/******************************/

// elimiate things that strtotime() is wrong about.

function msdbDateMakesSense($s)
{
	$BadExps = array(
		'^[a-su-z]$',
		);

	$goodExpsxps = array(
		'^[ 	]*[+-]?[   ]*[0-9jfmasondtlJFMASONDLT]',
		);

	foreach ( $BadExps as $exp )
		if ( @ereg($exp, $s) )
			return(false);

	foreach ( $goodExpsxps as $exp )
		if ( @ereg($exp, $s) )
			return(true);

	return(false);
}

/******************************/

// scan m/d/yyyy only
// but allow pre-unix-epoc

function msdbDaySimpleScan($s)
{
	if ( $s == 't' )
		return(msdbDayToday());
	$mdy = @ split('/', $s);
	if ( count($mdy) == 3 )
		return(msdbDayConstruct($mdy[2], $mdy[0], $mdy[1]));
	if ( count($mdy) == 2 )
		return(msdbDayConstruct(0, $mdy[0], $mdy[1]));


	$mdy = @ split(' ', $s);
	if ( count($mdy) == 3 )
		return(msdbDayConstruct($mdy[2], $mdy[0], $mdy[1]));
	if ( count($mdy) == 2 )
		return(msdbDayConstruct(0, $mdy[0], $mdy[1]));

	if ( @ereg("[+-]", $s) )
		return(null);

	$d = @(int)$s ;

	if ( $d >= 1 && $d <= 31 )
		return(msdbDayConstruct(0, 0, $d));
	return(null);

}

/******************************/

function msdbDayIsZero($s)
{
	if ( 
			$s == '0'
			|| $s == ''
			|| $s == '0000-00-00'
			)
		return(true);
	return(false);
}

/******************************/
// try a bit better than strtotime()

function msdbDayScan($s)
{

	// euro first
	if ( ($ret = msdbDayScanEuro($s)) != null)
		return($ret);

	// simple scan allows pre-1970 values
	if ( ($ret = msdbDaySimpleScan($s)) != null)
		return($ret);

	// change '+15' to '+15 Days' 't+15' likewise and 't-15' liekwise
	$words = split(' ', $s);
	if ( count($words) == 1 && strlen($words[0]) >= 2 && ($words[0][0] == '+' || $words[0][0] == '-') )
		return(msdbDayScan("$s Days"));
	if ( count($words) == 1 && strlen($words[0]) >= 3 && $words[0][0] == 't' && ($words[0][1] == '+' || $words[0][1] == '-') ) {
		$compat = substr($s, 1)." Days";
		return(msdbDayScan($compat));
	}

	if ( ! msdbDateMakesSense($s) )
		return(null);

	// is it an int as in '20040131' or 20040131
	$start = msdbDayConstruct(1000, 1, 1);
	$finish = msdbDayConstruct(9999, 1, 1);
	$ret = @(int)$s;
	if ( $ret >= $start && $ret <= $finish )
		return($ret);

	// strtotime will scan it ok, so avoid it
	if ( msdbDayIsZero($s) )
		return(0);

	$ts = @ strtotime($s);

	if ( $ts == -1 )
		return(null);
	$ret = msdbDayFromTs($ts);
	if ( $ts == -1 && ($ts = @strtotime($s." day")) == -1 )
		return(null);

	// compenstate for daylite savings time unknown errors
	// on or around '19700101'
	if ( $ts < -(24 * 3600) )
		return(null);

	if ( $ret >= $start && $ret <= $finish )
		return($ret);

	return(null);
}

/************************************************************/

// change an int or string of the form 20040301 to the string 2004-03-01

function msdbDayDashIt($d)
{
	$i = @( (int)$d) ;

	if ( $i < 1900*100*100 || $i > 10000*100*100 )
		return($d);

	list($y, $m, $d) = msdbDayBreak($i);
	return(sprintf("%02d-%02d-%02d", $y, $m, $d));
}

/******************************/

// short scanning for known valid dates in mysql format

function msdbDayUnDash($d)
{
	return(str_replace('-', '', $d));
}

/************************************************************/

function msdbDateScanTest()
{
	$dates = array (
		'',
		'0',
		'1970-01-01',
		'1970-09-17',
		'70-9-17',
		'70-09-17',
		'9/17/72',
		'24 September 1972',
		'24 Sept 72',
		'24 Sep 72',
		'Sep 24, 1972',
		'24-sep-72',
		'24sep72',
		'now',
		'10 September 2000',
		'September10 2000',
		'+1',
		't',
		'Thu',
		'thu',
		'+1 day',
		'4 2 3',
		'7/8',
		'7 8',
		'28',
		'+1 week',
		'+1 week 2 days 4 hours 2 seconds',
		'next Thursday',
		'last Monday',
		'None',
		'385.4',
		'20040131',
		'',
		'( select * from msdb_passwd )',
		'k',
		'z',
		'January 15, 2004 special',
		'January 15, 2004',
		'zeptember',
		'today',
		'msdbDayScanEuro() Scans dot notation<BR>as a reverse m/d notation:',
		'7.10.2004',
		'msdbDaySimpleScan() takes pre-epoc<BR>but php native does not:',
		'15.2.1961',
		'2/15/61',
		'a',
		'February 15 1961',
		'February 15 1991',
		'February 15 some year',
		'February 2004 no day of the month',
		'February 2004',
		'February 15 year',
		'February 15 none',
		'-3 months',
		20040131
	);

	echo "<TABLE BORDER=1>";
	foreach ( $dates as $d ) {
		$di =  msdbDayScan($d) ;
		if ( is_null($di) )
			$di = 'null' ;
		echo "<TR><TD>" ; var_dump($d) ; echo "</TD><TD>$di</TD></TR>" ;
	}
	$di =  msdbDayToday($d) ;
	$tn = msdbTimeNow();
	$dtn = msdbDateTimeNow();
	echo "<TR><TD>msdbDayToday()</TD><TD>$di</TD></TR>" ;
	echo "<TR><TD>msdbTimeNow()</TD><TD>$tn</TD></TR>" ;
	echo "<TR><TD>msdbDateTimeNow()</TD><TD>$dtn</TD></TR>" ;
	echo "</TABLE>";
}
	
/************************************************************/
?>
