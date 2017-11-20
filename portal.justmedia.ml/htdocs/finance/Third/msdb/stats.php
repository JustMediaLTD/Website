<?php
/************************************************************/



$rcsid='$Id: stats.php,v 1.38 2004/04/20 02:18:42 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/

function msdbGroupBySql($by)
{
        global $msdbStats;
        global $dbMeta;

        $tname = $dbMeta->tname;
        $str = "select $by, count(*)";

        foreach ( $msdbStats->totals as $total )
                $str .= ", sum($total)";

        $str .= " from $tname group by $by";

        return($str);
}


/******************************/

class msdbStatsVar
{
        function msdbStatsVar()
        {
                $this->started = null ;
                $this->groupBy = array(); // names of fields to group by
                $this->totals = array(); // names of fields to total
        }
}

/******************************/
$msdbStats = new msdbStatsVar();
/************************************************************/


// decide which are the groupBy - or 'category' - fields

function msdbStatGroupBys()
{
        global $dbMeta;
        global $msdbStats;

        $tname = $dbMeta->tname;


        foreach ( $dbMeta->msdbFields as $f ) {
                $fname = $f->fname;

                if ( $f->dbtype == 'string' )
                        $notNull = "$fname != '' and $fname is not null" ;
                else if ( $f->dbtype == 'int' )
                        $notNull = "$fname != 0 and $fname is not null" ;
                else
                        continue;

                $rowNum = msDbGetInt("select count(*) from $tname where $notNull");

                if ( $rowNum <= 7 )
                        continue;

                $dCnt = msDbGetInt("select count(distinct $fname) from $tname where $notNull");

                if ( $dCnt <= 1 || abs($rowNum-$dCnt) <= 5 )
                        continue;

                if ( $dCnt >= $rowNum / 4 )
                        continue;

                // skip large calculations
                if ( $dCnt > 20 )
                        continue;

                $dValues = msDbGetStrings("select $fname from $tname where  $notNull group by $fname having count(*) > 1");
                $dvCnt = count($dValues);

                if ( $dvCnt < 2 )
                        continue;

                $msdbStats->groupBy[] = $fname ;
        }
}


/************************************************************/


function msdbStatTotals()
{
        global $dbMeta;
        global $msdbStats;

        $tname = $dbMeta->tname;

        // decide which fields to total
        // don't the primary key nor anything that is a 'category' in the groupBy

        foreach ( $dbMeta->msdbFields as $f ) {
                if ( $f->fname == $dbMeta->primaryKey )
                        continue;
                if ( msdbArrValIn($f->fname, $msdbStats->groupBy))
                        continue;
                /*	if ( $f->dbtype != 'int' && $f->dbtype != 'real' )	*/
                        /*	continue;	*/
                if ( $f->dbtype != 'real' )
                        continue;
                $msdbStats->totals[] = $f->fname ;
        }
}

/************************************************************/

function msdbStatPrepare()
{
        // must first decide on the groupbys 
        // as this info is used for deciding which fields are the totals

        msdbStatGroupBys();
        msdbStatTotals();
}

/************************************************************/

function msdbStatHead($ind)
{
        global $dbMeta;
        global $msdbStats;

        $by = $msdbStats->groupBy[$ind];

        echo "\t\t<TR>\n" ;

        echo "\t\t\t<TD class=reportHead>$by</TD><TD COLSPAN=2 class=reportHead>#</TD>\n" ;

        foreach ( $msdbStats->totals as $totName )
                echo "\t\t\t<TD COLSPAN=2 class=reportHead>$totName</TD>\n" ;
        echo "\t\t</TR>\n";
}

/************************************************************/

function msdbStatLine($fname, $fvalue, $ind, $rowInd)
{
        global $dbMeta;
        global $msdbStats;
        static $runTotals; // indexed by name of total field or 'cnt'

        // rowInd==0 as many times as count($msdbStats->groupBy)
        if ( $rowInd == 0 ) {
                $runTotals = array(); // GC
                $runTotals['cnt'] = 0.0;
                foreach ( $msdbStats->totals as $totName )
                        $runTotals[$totName] = 0.0 ;
        }

        $tname = $dbMeta->tname;
        $msdbfi = msdbMetaFieldIndex($fname);
        $msdbf = & $dbMeta->msdbFields[$msdbfi];

        $sqlval = msDbSqlValue($msdbf, $fvalue);
        $jsval = msdbJsStr($fvalue);

        // not clear how this behaves on non-string nulls ???
        if ( $sqlval == "''" )
                $w = "where $fname = '' or $fname is null";
        else
                $w = "where $fname = $sqlval";

        $cntSel = "select count(*) from $tname $w";
        $cnt = msDbGetInt($cntSel);
        $runTotals['cnt'] += $cnt;

        $hebrew = false;
        if ( $hebrew )
                $dname = heb_db2unicode($fvalue); // there is no such function yet!!!
        else
                $dname = $fvalue;

        if ( $dname == '' )
                $dname = '<FONT COLOR="white">(empty)</FONT>' ;

        $runCnt = $runTotals['cnt'];
        echo "\t\t<TR>\n";
        $linkStr = "<A HREF=\"javascript:msdbStatRestrict('$fname', '$jsval')\">$dname</A>";
        echo "\t\t\t<TD class=reportBy>$linkStr</TD>\n";
        echo "\t\t\t<TD class=reportCnt><B>$cnt</B></TD><TD class=reportCnt>$runCnt</TD>\n";

        foreach ( $msdbStats->totals as $totName ) {
                $cmd = "select sum($totName) from $tname $w" ;
                $rawTotal = msDbGetString($cmd);
                $total = msdbRound($rawTotal);
                $runTotals[$totName] += $rawTotal;
                $runTotal = msdbRound($runTotals[$totName]);
                echo "\t\t\t<TD class=reportTotal><B>$total</B></TD><TD class=reportTotal>$runTotal</TD>\n" ;
        }

        echo "\t\t</TR>\n";
}

/************************************************************/

function msdbStatReport($ind)
{
        global $dbMeta;
        global $msdbStats;

        $tname = $dbMeta->tname;

        $by = $msdbStats->groupBy[$ind];

        $cmd = "select distinct $by from $tname order by $by" ;

        $bys = msDbGetStrings($cmd);

        if ( ! $bys || count($bys) == 0 )
                return(false);

        echo "\t<TABLE BORDER=0 CELLPADDING=2 CELLSPACING=2>\n";
        msdbStatHead($ind);
        for($i=0;$i<count($bys);$i++)
                msdbStatLine($by, $bys[$i], $ind, $i);
        printf("\t</TABLE>\n");
        return(true);
}

/************************************************************/

function msdbDateList()
{
        global $dbMeta;

        $tname = $dbMeta->tname;
        $dateFname = $dbMeta->dateFname;
        $lastDate = $dbMeta->lastDate;
        $todayInt = msdbDayToday(); // not used (yet?)

        if ( ! $dateFname || ! $lastDate )
                return(false);

        if ( $dbMeta->rowNum == 0 )
                return(false);

        if ( ! $dbMeta->firstDate )
                return(false);

        $lymd = split("-", $lastDate);
        $fymd = split("-", $dbMeta->firstDate);
        if ( count($lymd) != 3 || count($fymd) != 3 ) {
                msdbMsg("$dbMeta->firstDate => $lastDate, can not split");
                return(false);
        }
        list($ly, $lm, $ld) = $lymd;
        list($fy, $fm, $fd) = $fymd;

        for($y=$fy;$y<$ly;$y++)
                $dbMeta->dateList[] = msdbDayConstruct($y, 12, 31);

        // I mark allways as 31, but never show the date of the month on the report
        if ( $ly == $fy )
                $m = $fm;
        else
                $m = 1;
        for(;$m<=$lm;$m++)
                $dbMeta->dateList[] = msdbDayConstruct($ly, $m, 31);


        return(true);
}

/******************************/

function msdbDateStatsHead()
{
        global $dbMeta;
        global $msdbStats;

        $dateFname = $dbMeta->dateFname;

        echo "\t\t<TR CLASS=dateStatHead><TD>$dateFname</TD><TD COLSPAN=2><B>#</B></TD>\n";

        foreach ( $msdbStats->totals as $totName )
                echo "\t\t\t<TD COLSPAN=2>$totName</TD>\n" ;
        echo "\t\t</TR>\n" ;

}

/******************************/

function msdbDateStatsLine($i)
{
        global $dbMeta;
        global $msdbStats;

        $tname = $dbMeta->tname;
        $dateFname = $dbMeta->dateFname;

        if ( $i == 0 )
                $fromDate = 0;
        else
                $fromDate= $dbMeta->dateList[$i-1];

        $toDate = $dbMeta->dateList[$i];

        $href = "javascript:msdbDateRestrict('$fromDate-$toDate')" ;


        $w = "where  $dateFname > $fromDate and $dateFname <= $toDate";
        $cmd = "select count(*) from $tname $w";

        $cnt = msDbGetInt($cmd);
        if ( $cnt == null ) // no rows in this query
                $cnt = 0;

        $dbMeta->dateCntBalance += $cnt ;
        $bal = $dbMeta->dateCntBalance ;

        list($tdy, $tdm, $tdd) = msdbDayBreak($toDate);

        $monthStr = "<A HREF=\"$href\">$tdm/$tdy</A>" ;
        echo "<TR><TD CLASS=dateStatMonth>$monthStr</TD><TD CLASS=dateStatCnt><B>$cnt</B></TD><TD CLASS=dateStatCnt>$bal</TD>\n";

        foreach ( $msdbStats->totals as $totName ) {

                $cmd = "select sum($totName) from $tname $w" ;
                $rawSum = msDbGetString($cmd);
                $theSum = msdbRound($rawSum);
                $dbMeta->dateBalances[$totName] += $rawSum ;
                $curTotal = msdbRound($dbMeta->dateBalances[$totName]);

                echo "\t\t\t<TD CLASS=dateStatBal><B>$theSum</B></TD><TD CLASS=dateStatBal>$curTotal</TD>\n" ;
        }


        echo "</TR>\n";
}

/******************************/

function msdbDateStats()
{
        global $dbMeta;
        global $msdbStats;

        if ( ! $dbMeta->dateFname )
                return(false);

        if ( ! msdbDateList() )
                return(false);

        echo "<TD VALIGN=TOP><TABLE BORDER=0 CELLPADDING=2 CELLSPACING=2 CLASS=dateStat>\n" ;
        msdbDateStatsHead();

        foreach ( $msdbStats->totals as $totName )
                $dbMeta->dateBalances[$totName] = 0 ;

        for($i=0;$i<count($dbMeta->dateList);$i++)
                msdbDateStatsLine($i);

        echo "</TABLE></TD>\n" ;
        return(true);
}

/************************************************************/

function msdbStatistics()
{
        global $dbMeta;
        global $msdbStats;

        $msdbStats->started = true ;

        $gbcnt = count($msdbStats->groupBy) ;

        if ( $gbcnt == 0 && ! $dbMeta->firstDate )
                return(false);

        echo "<BR><B>Statistics and Data Mining</B>\n";

        echo("<TABLE class=reportsClass BORDER=0 CELLPADDING=4 CELLSPACING=4><TR>\n");
        if ( $gbcnt > 0 ) {
                for($ind=0;$ind<$gbcnt;$ind++) {
                        echo "<TD VALIGN=TOP>\n" ;
                        msdbStatReport($ind);
                        echo "</TD>\n" ;
                }
        }

        msdbDateStats();

        echo "</TR></TABLE>\n";
}

/************************************************************/
?>
