<?php
$rcsid='$Id: cb.php,v 1.72 2004/08/16 16:44:21 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://www.engine.com/license.txt (BSD)";
/************************************************************/




/************************************************************/
error_reporting(E_ALL|2048) ;
/************************************************************/
require_once("config.php");
/************************************************************/
$cbFname = array("category", "toFrom", "Balance" );
$repfvalues = array();
$stCloseDates = 0; // ints of repfvalues[2]
$repnvalues = array( -2, -2, -2);
/************************************************************/
if ( ! cbEnter() )
        return;
if ( ! isset($_REQUEST['EA']) ) {
        cbMain();
        return;
}
$action = $_REQUEST['EA'];
$action();
return;
/************************************************************/
/************************************************************/
/************************************************************/

function cbCreateSampleData()
{
        static $previousDay = null;

        $today = msdbDayToday();
        list($y, $m, $d) = msdbDayBreak($today);

        for($iy=$y-1;$iy<=$y;$iy++) {
                for($im=1;$im<=12;$im++) {
                        $id = rand(1, 5);
                        $numEntries = rand(2, 5);
                        for($i=0;$i<$numEntries;$i++) {
                                cbCreateBogusEntry(msdbDayCompose($iy, $im, $id), $i == $numEntries-1);
                                $id += rand(3, 4);
                                if ( $iy == $y && $im >= $m && $id >= $id )
                                        return;
                                // numEntries will be less then $numEntries
                                // endOfMonth left open
                                if ( $id > 28 )
                                        break;
                        }
                }
        }
}

/******************************/

function cbCreateTable()
{
        global $cbTable;
        global $cbConfig;

        $crtfields = " category varchar(64), date date, amount double, toFrom varchar(64), notes varchar(64), closed date, entered date, wasClosed int, id int auto_increment NOT NULL, PRIMARY KEY(id) ";

        if ( msDbIsTable($cbTable) )
                        return(true);

        $crt = "create table $cbTable ( $crtfields )";
        cbMsg("Creating table $cbTable");
        $ret = msDbSql($crt);
        if ( $ret == -1 )
                return(false);
        $tables = msdbTables();
        if ( ! @$cbConfig['Tutor'] && @count($tables) != 1 )
                return(true);
        cbMsg("Throwing in some ramdom data in $cbTable");
        cbCreateSampleData();
        return(true);
}

/******************************/

function cbTableName()
{
        global $_SERVER;
        global $cbConfig;

        if ( isset($cbConfig['tableName']) )
                return($cbConfig['tableName']);

        if ( isset($_REQUEST['cbTable']) )
                return($_REQUEST['cbTable']);

        if ( isset($_SERVER['REMOTE_USER']) )
                return("cb_".$_SERVER['REMOTE_USER']);

        return("cashbook");
}

/******************************/

function cbEnter()
{
        global $cbTable;

        $cbTable = cbTableName();
        return(cbCreateTable());
}

/************************************************************/
global $lastAddDate;
$lastAddDate = 0;
/******************************/

function cbStPrevDate($closeDate)
{
        global $cbTable;

        return(msDbGetString("select max(date) from $cbTable where date < '$closeDate' and closed > 0"));
}

/******************************/


function cbPrevDate($isStatement, $closeDate)
{
        global $cbTable;

        static $thePrevDate = -1 ;

        if ( $thePrevDate != -1 )
                return($thePrevDate);

        if ( $isStatement ) {
                $thePrevDate = cbStPrevDate($closeDate);
                return($thePrevDate);
        }

        $cmd = "select max(date) from $cbTable where closed > 0" ;
        $thePrevDate = msDbGetString($cmd);
        if ( ! $thePrevDate )
                $thePrevDate = 0;


        return($thePrevDate);
}

/************************************************************/

/*
 * it is not an error to change or add consolidated data 
 * this can occur when entering or changing the date
 * or when accessing data from the restricted (unconsolidated) view
 * but the results do not show on screen, except for the previous balance
 * which may have changed
 */

function cbWarnPreCons($date)
{
        $p = msdbDayUnDash(cbPrevDate(0, 0));

        if ( $date > $p )
                return;

        cbMsg("Note: $date is before $p");
}

/************************************************************/

function cbPrevBalance($isStatement, $stCloseDate)
{
        global $cbTable;

        $pdate = cbPrevDate($isStatement, $stCloseDate);
        return(msdbGetString("select sum(-amount) from $cbTable where date <= '$pdate'"));
}

/************************************************************/

function cbSelect($which)
{
        global $cbFname, $repfvalues, $repnvalues;

        $args = array(
                        "DIALOGID" => "cbSEL".$cbFname[$which],
                        "CLASSNAME" => "cbSEL".$cbFname[$which]."Class"
                );
        msdbInclude("include/dialogHeader.h", $args);

        cbRepData();

        if ( $repnvalues[$which] <= 0 || $repnvalues[$which] == 1 && ! $repfvalues[$which][0] ) {
                msdbInclude("include/dialogFooter.h", $args);
                return(0);
        }

        for($i=0;$i<$repnvalues[$which];$i++) {
                if ( ! $repfvalues[$which][$i] )
                        continue;
                $val = $repfvalues[$which][$i];
                $jVal = msdbJsStr($repfvalues[$which][$i]);
                $br = ($i == $repnvalues[$which] - 1 ) ? "" : "<BR>" ;

                echo "\t\t\t<A HREF=\"javascript:cbSet($which, '$jVal')\">$val</A>$br\n";
        }
        msdbInclude("include/dialogFooter.h", $args);
        return(1);
}

/************************************************************/

function fltScan($s)
{
        if ( sscanf($s, "%lf", $ret) == 1 )
                return($ret);
        return(null);
}

/******************************/

function cbGetEnv()
{
        global $isSearch, $searchCond;

        $isSearch = false;
        $amtDtOps = array('>', '<', '=', '>=', '<=', '!=');
        $strOps = array('like', '%', '*', '='); // all treated as 'like'

        $ret = array();

        $ret = $_REQUEST;

        $d = @$_REQUEST['date'];

        // string searches
        foreach ( array('category', 'toFrom', 'notes') as $field ) {
                $strWop = split(' ', $_REQUEST[$field]);
                if ( count($strWop) > 1 && msdbArrValIn($strWop[0], $strOps) ) {
                        $isSearch = true;
                        $val = substr($_REQUEST[$field], strlen($strWop[0])+1);
                        $searchCond = "$field like '%$val%'";
                }
        }

        if ( $d == '' )
                $ret['date'] = msdbDayToday();
        else if ( ! ($ret['date'] = msdbDayScan($d)) ) {
                cbMsg("$d: Invalid date format");
                return(false);
        }
        $amtWop = split(' ', $_REQUEST['amount']);
        if ( count($amtWop) == 2 && msdbArrValIn($amtWop[0], $amtDtOps) ) {
                if ( ! ($ret['amount'] = fltScan($amtWop[1])) ) {
                        cbMsg($amtWop[1].": Invalid amount");
                        return(false);
                }
                $isSearch = true;
                $op = $amtWop[0];
                $val = $amtWop[1];
                $searchCond = "amount $op $val";
        } else if ( ! $isSearch && ($_REQUEST['amount']) == '' ) {
                cbMsg("Empty Amount - Ignored");
                return(false);
        } else if ( ! $isSearch && ! ($ret['amount'] = fltScan($amtWop[0])) ) {
                cbMsg($amtWop[0].": Invalid amount");
                return(false);
        }

        if ( ! @$ret['entered'] )
                $ret['entered'] = time();

        return($ret);
}

/******************************/

function cbAdd()
{
        global $cbTable;
        global $lastAddDate;
        global $isSearch, $searchCond;

        if ( ! ($cb = cbGetEnv()) )
                return(cbMain());

        /* searchCond, if at all, is set by cbGetEnv */

        if ( $isSearch )
                return(cbMain());

        $sql = msDbInsertSql($cbTable, $cb);
        $affected = msDbSql($sql);
        $cbDescribed = $cb['date']." ".$cb['amount']." ". $cb['toFrom']." ".$cb['category']." ".$cb['notes'];
        if ( $affected == 1 ) {
                cbMsg("Entered: $cbDescribed");
                cbWarnPreCons($cb['date']);
                $lastAddDate = $cb['date'];
        } else
                cbMsg("Not Inserted ($cbDescribed)");

        cbMain();

}

/******************************/

function cbUpdate()
{
        global $cbTable;

        if ( isset($_REQUEST['cbAdd']) && $_REQUEST['cbAdd'] == "Copy" )
                return(cbAdd());

        if ( ! ($cb = cbGetEnv()) )
                return(cbMain());

        $sql = msDbUpdateSql($cbTable, $cb, "where id = ".$cb['id']);
        $affected = msDbSql($sql);
        $cbDescribed = $cb['date']." ".$cb['amount']." ". $cb['toFrom']." ".$cb['category']." ".$cb['notes'];
        if ( $affected == 1 ) {
                cbMsg("Updated: $cbDescribed");
                cbWarnPreCons($cb['date']);
        } else
                cbMsg("Nothing Changed ($cbDescribed)");
        cbMain();
}

/************************************************************/

function cbCons()
{
        global $cbTable;

        $id = $_REQUEST['id'];
        $date = msDbGetString("select date from $cbTable where id = $id");
        if ( ! $date ) {
                cbMsg("No Date for Consolidation");
                cbMain();
                return;
        }
        $ceidq = "select max(id) from $cbTable where date = '$date'";
        $ceid = msDbGetString($ceidq);
        if ( ! $ceid ) {
                cbMsg("Consolidation Date Error ($ceidq)");
                cbMain();
                return;
        }
        $today = msdbDayToday();
        msDbSql("update $cbTable set closed = '$today', wasClosed = 1 where id = $id");
        cbMain();
}

/************************************************************/

function cbUncons()
{
        global $cbTable;

        if ( ! ( $dt = msDbGetString("select max(date) from $cbTable where closed != 0")) ) {
                cbMsg("cbUncons: Nothing to Do ???");
                cbMain();
                return;
        }

        $sql = "update $cbTable set closed = 0 where date = '$dt'" ;
        msDbSql($sql);
        /*	cbMsg("cbUncons: $sql");	*/
        cbMain();
}

/************************************************************/

function cbMonthlyLine($year, $month, $bal)
{

        $args['cbLineClass'] = 'cbMonthLine';


        /*
	 * the date on the report is the first day of the month
	 * as is convenienced by the sql query (last_day() only as of mysql 4.1.1)
	 * I am converting it to the last day of the month
	 */
        $endDate = msdbDayDsub(msdbDayMadd(msdbDayConstruct($year, $month, 1)));

        $args['sEndDate'] = msdbDayDashIt($endDate) ;
        $args['amount'] = sprintf("%.2lf", $bal) ;

        msdbInclude("include/monthlyTotals.h", $args);
}

/************************************************************/

function cbLine(& $bal, $line, $isodd, $nextIsNextMonth)
{
        $isMonthly = isset($_REQUEST['cbMonthly']);

        $args = $line;

        $args['cbLineClass'] = ( $isodd ) ? 'cbOddLine' : 'cbEvenLine';

        if ( $line['amount'] < -0.004 )
                $args['negAmountClass'] = "class=cbNegAmount";
        else
                $args['negAmountClass'] = '';

        $bal += -$line['amount'];

        if ( $bal < -0.004 )
                $args['negBalClass'] = 'class=cbNegBal';
        else
                $args['negBalClass'] = '';

        // this hae to do with diffrent icons for folding
        /*	if ( $line['wasClosed'] )	*/
                /*	$args['CONSGIF'] = "redCons.gif";	*/
        /*	else if ( $nextIsNextMonth )	*/
                /*	$args['CONSGIF'] =  "whiteCons.gif";	*/
        /*	else	*/
                /*	$args['CONSGIF'] =  "cons.gif";	*/

        $args['amount'] = sprintf("%.02lf", $args['amount']);
        $args['balance'] = sprintf("%.02lf", $bal);

        msdbInclude($isMonthly ? "include/monthlyLine.h" : "include/line.h", $args);
}

/************************************************************/

function cbSend_data($cbLines)
{

        $js[] = "\ncbl = new Array();\n";

        foreach ( $cbLines as $cb ) {
                $js[] = sprintf( "%s = new cbItem('%s', '%s', %.2lf, '%s', '%s', %d, %d, %d);\n",
                        "cbl[cbl.length]",
                        msdbJsStr($cb['category']),
                        $cb['date'],
                        $cb['amount'],
                        msdbJsStr($cb['toFrom']),
                        msdbJsStr($cb['notes']),
                        $cb['closed'],
                        $cb['entered'],
                        $cb['id']
                        );
        }
        $js[] = "\ncb.data = cbl;\n";

        msdbJs(implode("\n", $js));
}

/************************************************************/

function cbReportLine($n, $which)
{
        global $cbTable;
        global $cbFname, $repfvalues, $stCloseDates;

        $repRestrClass = array("cbCategoryRestrictClass", "cbToFromRestrictClass", "cbBalancesRestrictClass" );
        $repFvalClass = array("cbCategoryValClass", "cbToFromValClass", "cbBalancesValClass", );
        $repNegClass = array("cbCategoryNegTotalClass", "cbToFromNegTotalClass", "cbBalancesNegTotalClass" );
        $repPosClass = array( "cbCategoryTotalClass", "cbToFromTotalClass", "cbBalancesTotalClass" );
$repAlt = array( "Show Detail of this Category", "Show Detail of this Payee/Payer", "Show Statement Ending with this date");

        $fval = $repfvalues[$which][$n];

        if ( $which == 2 ) {
                $closeDate = $stCloseDates[$n];
                $cmd = "select sum(-amount) from $cbTable where date <= '$closeDate'";
        } else {
                $sval = str_replace("'", "\\'", $fval);
                $fname = $cbFname[$which];
                $cmd = "select sum(amount) from $cbTable where $fname = '$sval'";
        }

        $tot = msDbGetString($cmd);
        $args['Total'] = sprintf("%.02lf", $tot) ;
        $args['which'] = $which ;
        $args['fval'] = $fval ;
        $args['restrictClass'] = $repRestrClass[$which] ;

        if ( $tot < -0.004 )
                $args['totalClass'] = $repNegClass[$which];
        else
                $args['totalClass'] = $repPosClass[$which];


        $args['repAlt'] = $repAlt[$which];

        $args['fvalClass'] = $repFvalClass[$which];

        if ( $which == 2 ) {
                $args['cdate'] = $stCloseDates[$n];
                msdbInclude("include/balLine.h", $args);
        } else
                msdbInclude("include/repLine.h", $args);
}

/******************************/

function cbReportBy($which)
{
        global $cbFname, $repnvalues;
        $repFdesc = array("Category", "Paid To/From", "Balance as of" );

        if ( $repnvalues[$which] <= 0 )
                return(0);
        if ( $which == 1 && $repnvalues[1] > 80 )
                return(0);
        $args['moreHeading'] = ($which == 2) ? "<TR><TD COLSPAN=3 ALIGN=CENTER><B>Folded Pages</B></TD></TR>\n" : "" ;
        $args['reportBy'] = $cbFname[$which];
        $args['reportByDesc'] = $repFdesc[$which];
        msdbInclude("include/reportTableTag.h", $args);
        for($i=0;$i<$repnvalues[$which];$i++)
                cbReportLine($i, $which);
        printf("</TABLE>\n");
        return(1);
}

/******************************/

function cbRepData()
{
        global $cbTable;

        global $cbFname, $repfvalues, $stCloseDates, $repnvalues;


        if ( $repnvalues[0] != -2 )
                return(1);

        for($i=0;$i<2;$i++) {
                $fname = $cbFname[$i];
                $cmd = "select distinct $fname from $cbTable order by $fname";
                $repfvalues[$i] = msDbGetStrings($cmd);
                $repnvalues[$i] = count($repfvalues[$i]);
        }

        $cmd = "select date from $cbTable where closed > 0";
        $stCloseDates = msDbGetStrings($cmd);
        $repnvalues[2] = count($stCloseDates);
        for($i=0;$i<$repnvalues[2];$i++)
                $repfvalues[2][$i] = $stCloseDates[$i] ;
        return(1);
}

/******************************/

function cbReports()
{
        global $cbFname, $repnvalues;

        if ( $repnvalues[0] <= 0 && ($repnvalues[1] <= 0 || $repnvalues[1] > 80) )
                return(0);

        msdbInclude("include/reportsTableTag.h");
        printf("<TR><TD VALIGN=\"TOP\">\n");
        cbReportBy(0);
        printf("</TD><TD VALIGN=\"TOP\">\n");
        cbReportBy(1);
        printf("</TD><TD VALIGN=\"TOP\">\n");
        cbReportBy(2);
        printf("</TD></TR></TABLE>\n");
        return(1);
}

/************************************************************/

function cbMainTable($prevDate, $prevBal, $cbLines)
{
        global $cbTable;
        static $curBal;

        $curBal = $prevBal;
        $isMonthly = isset($_REQUEST['cbMonthly']);

        msdbInclude("include/tableTag.h");
        if ( $prevDate )
                msdbInclude("include/prev.h", array(
                                        'prevDate' => $prevDate,
                                        'prevBal' => sprintf("%.02lf", $prevBal),
                                        'prevBalClass' => ($prevBal < -0.004) ? "cbNegPrev" : "cbPosPrev",
                                )
                        );
        msdbInclude($isMonthly ? "include/monthlyHead.h" : "include/head.h");


        for($i=0;$i<count($cbLines);$i++) {
                $nextIsNextMonth = ( $i != count($cbLines)-1 &&
                                        (
                                                ! $isMonthly && msdbDayMonthOf($cbLines[$i]['date']) != msdbDayMonthOf($cbLines[$i+1]['date']) ||
                                                $isMonthly && $cbLines[$i]['month'] != $cbLines[$i+1]['month']
                                        )
                                )
                        ;
                cbLine($curBal, $cbLines[$i], $i%2, $nextIsNextMonth);
                if ( $isMonthly && ($i == count($cbLines)-1 || $nextIsNextMonth ) ) {
                        cbMonthlyLine($cbLines[$i]['year'], $cbLines[$i]['month'], -$curBal);
                        $curBal = 0.0;
                }
        }

        if ( $isMonthly ) {
                printf("</TABLE>\n");
                return;

        }

        cbRepData();

        cbForm();

        printf("</TABLE>\n");

        cbSelect(0);
        cbSelect(1);

        cbReports();

        if ( ! $isMonthly )
                cbSend_data($cbLines);

        $args['cbTable'] = $cbTable;

        msdbInclude("include/change.h", $args);

}

/************************************************************/

function cbForm()
{
        global $cbTable;
        global $lastAddDate;
        global $repfvalues, $repnvalues;

        $args['cbTable'] = $cbTable;

        if ( $lastAddDate ) {
                $d = $lastAddDate;
                $args['date'] = sprintf("%d %d %d", ((int)($d/100))%100, $d%100, (int)($d/10000));
        } else
                $args['date'] = '' ;

        $args['categImg'] = 'cbNoImg' ;
        $args['toFromImg'] = 'cbNoImg' ;

        if ( $repnvalues[0] > 0 && ( $repnvalues[0] != 1 || $repfvalues[0] != '' ) )
                $args['categImg'] = 'IMG' ;
        if ( $repnvalues[1] > 0 && ( $repnvalues[1] != 1 || $repfvalues[1] != '' ) )
                $args['toFromImg'] = 'IMG' ;

        msdbInclude("include/new.h", $args);
}

/************************************************************/

function cbMsg($m)
{
        msdbMsg($m);
}

/************************************************************/

function cbMain()
{
        global $cbTable;
        global $restrFname, $restrVal, $isMonthly, $searchOp, $isSearch, $searchCond;

        $restrFname = @$_REQUEST['restrFname'];
        $restrVal = @$_REQUEST['restrVal'];
        $isMonthly = isset($_REQUEST['cbMonthly']);
        $isStatement = $restrFname == "Statement" ;

        $stCloseDate = 0;
        if ( $isStatement )
                $stCloseDate = $restrVal;

        if ( $isStatement ) {
                $describe = "- Statement Ending $stCloseDate";
        } else if ( $isSearch ) {
                $describe = " - where $searchCond";
        } else if ( $restrFname )
                $describe = " - where $restrFname = '$restrVal'";
        else if ( $isMonthly )
                $describe = "- Month by Month Expenses";
        else
                $describe = '';

        $args = array(
                        'cbTable' => $cbTable,
                        'restrFname' => $restrFname,
                        'restrVal' => $restrVal,
                        'describe' => "$describe",
                );


        msdbInclude("include/cb.h", $args);


        msdbInclude("Third/msdb/include/mOver.h");



        $conds = array();

        if ( $isMonthly || $restrFname && ! $isStatement ) {
                $prevDate = 0;
        } else {
                $prevDate = cbPrevDate($isStatement, $stCloseDate) ;
                if ( $prevDate != 0 )
                        $conds[] = "date > '$prevDate'" ;
        }

        if ( $prevDate )
                $prevBal = cbPrevBalance($isStatement, $stCloseDate);
        else
                $prevBal = 0.0;
        if ( $isStatement )
                $conds[] = "date <= '$stCloseDate'";
        else if ( $isSearch )
                $conds[] = "$searchCond" ;
        else if ( $restrFname && ! $isStatement )
                $conds[] = "$restrFname = '$restrVal'" ;

        if ( count($conds) > 0 )
                $w = "where ".implode(" and ", $conds);
        else
                $w = "";

        if ( $isMonthly ) {
                $fields = "category, YEAR(date) as year, MONTH(date) as month, max(TO_DAYS(date)) as date, sum(amount) as amount";
                $gb = "group by YEAR(date), MONTH(date), category";
                $ob = "order by YEAR(date), MONTH(date), category";
                /*	$q = "select $fields from $cbTable where amount > 0 $gb $ob" ;	*/
                $q = "select $fields from $cbTable $gb $ob" ;
        } else
                $q = "select * from $cbTable $w order by date, entered";

        $cbLines = msDbGetAssoc($q);

        if ( $cbLines === null ) {
                cbMsg("Can not get data - $q");
                return;
        }

        cbMainTable($prevDate, $prevBal, $cbLines);
        msdbInclude("include/cb.t");
}

/************************************************************/

function cbDelete()
{
        global $cbTable;

        $id = $_REQUEST['id'];
        $affected = msDbSql("delete from $cbTable where id = $id");
        if ( $affected != 1 )
                cbMsg("Not Deleted");
        cbMain();
}

/************************************************************/

function cbNoOp()
{
        cbMain();
}

/************************************************************/

/* date,amount,toFrom,category,notes,entered,closed */



function cbExport()
{
        global $cbTable;

        $us="--------------------------------------------------------------------------------------";

        msdbMime("text/plain");


        $cbLines = msDbGetAssoc("select * from $cbTable order by date, entered");

        printf("%-9.9s %-19.19s %-19.19s %-19.19s %-24.24s %-14.14s %-9.9s\n",
                "Date", "Amount", "To/Form", "Category",
                "Notes", "Entered", "Closed");
        printf("%-9.9s %-19.19s %-19.19s %-19.19s %-24.24s %-14.14s %-9.9s\n", us, us, us, us, us, us, us);
        foreach($cbLiones as $cb)
                printf("%-9.9s %19.2lf %-19s %-19s %-24s %-14d %-9d\n",
                        $cb['date'],
                        $cb['amount'],
                        $cb['toFrom'],
                        $cb['category'],
                        $cb['notes'],
                        $cb['entered'],
                        $cb['closed']
                        );
}

/************************************************************/

function cbMonthly()
{
        $_REQUEST['cbMonthly'] = 1 ;
        cbMain();
}

/************************************************************/

function cbCreateBogusEntry($date, $isLastDayOfMonth)
{
        global $cbTable;
        static $lines = null;

        $closed = ($isLastDayOfMonth) ? 1 : 0 ;
        if ( $lines === null )
                foreach ( array('category', 'toFrom', 'notes') as $fname )
                        $lines[$fname] = msdbFile("include/bogusData/$fname");

        $cb = array(
                'category' => $lines['category'][rand(0, count($lines['category'])-2)],
                'date' => $date,
                'amount' => ( rand(0,1) * 2 - 1) * rand(40, 80) + rand(0,99)/100,
                'toFrom' => $lines['toFrom'][rand(0, count($lines['toFrom'])-2)],
                'notes' => str_replace("'", "\\'", $lines['notes'][rand(0, count($lines['notes'])-2)]),
                'closed' => $isLastDayOfMonth ? $date : 0,
                );
        msDbSql(msDbInsertSql($cbTable, $cb));
}

/************************************************************/
?>
