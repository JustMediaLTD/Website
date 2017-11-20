<?php
/************************************************************/



$rcsid='$Id: head.php,v 1.109 2004/09/02 12:10:55 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/

function msdbTitle()
{
        global $msdbEnterVar;
        global $dbMeta;

        if( msDbIsOnlyDb() )
                $str = '' ;
        else if ( ! $msdbEnterVar->pwent )
                return("msdb: Not logged on");
        else
                $str = $msdbEnterVar->pwent['name'] ;

        if ( $dbMeta->DB )
                $str .= ": ".$dbMeta->DB ;

        if ( $dbMeta->tname )
                $str .= ": ".$dbMeta->tname ;

        $sr = msdbGetPost('msdbStatRestrict');
        if ( ! is_null($sr) )
                $str .= ": Stats Mining..." ;
        else if ( $dbMeta->isSearch )
                $str .= ": Search" ;
        else if ( $dbMeta->ea )
                $str .= ": ".$dbMeta->ea ;

        return($str);
}

/************************************************************/

function msdbDateRestr()
{
        global $dbMeta;

        if ( ($dateRange = msdbGetPost('msdbDateRestrict')) == null )
                return(false);

        $dateFname = $dbMeta->dateFname;

        $fromTo = split("-", $dateRange);
        if ( count($fromTo) != 2 ) {
                msdMsg("can not split date range $dateRange");
                return(false);
        }
        list($from, $to) = $fromTo;
        $dbMeta->where = "where $dateFname > $from and $dateFname <= $to";

        msdbUserPrefSetProperty('lastWhere', $dbMeta->where);

        return(true);
}

/************************************************************/

function msdbDateAutoWhere()
{
        global $dbMeta;
        global $msdbConfig;

        $showRows = $msdbConfig['showRows'] ;

        if ( $dbMeta->rowNum <= $showRows )
                return(false);

        if ( ! $dbMeta->lastDate )
                return(false);

        $dateFname = $dbMeta->dateFname ;
        $tname = $dbMeta->tname;

        $lymd = split("-", $dbMeta->lastDate);
        if ( count($lymd) != 3 ) {
                msdbMsg("can not split date $dbMeta->lastDate");
                return(false);
        }
        list($ly, $lm, $ld) = $lymd;

        $today = msdbDayToday();
        $lastDayInt = msdbDayConstruct($ly, $lm, $ld);

        // if there is data in the future
        // probably data is not entered in date sequence order
        if ( $lastDayInt > $today )
                return(false);

        $prevWhere = null ;

        for($y=$ly;$y>0;$y--) {
                $m = ($y == $ly) ? $lm : 12 ;
                for(;$m>0; $m--) {
                        $dt = msdbDayConstruct($y, $m, 1);
                        $w = "where $dateFname >= $dt " ;
                        $sql = "select count(*) from $tname $w";
                        $rc = msDbGetInt($sql);

                        if ( $rc > $showRows )
                                break;
                        $prevWhere = $w ;
                }
                if ( $rc > $showRows )
                        break;
        }

        if ( $y == 0 )
                return(false);

        if ( $prevWhere )
                $dbMeta->where = $prevWhere;
        else
                $dbMeta->where = $w;

        return(true);

}

/******************************/

function msdbDateWhere()
{
        if ( msdbDateAutoWhere() )
                return(true);
        return(false);
}

/******************************/

function msdbPrepDate()
{
        global $dbMeta;

        $tname = $dbMeta->tname;

        foreach ( $dbMeta->msdbFields as $f ) {
                if ( $f->ftype == 'date' ) {
                        $dbMeta->dateFname = $f->fname ;
                        break;
                }
        }

        $dateFname = $dbMeta->dateFname;

        if ( $dateFname ) {
                $dbMeta->lastDate =
                        msDbGetString("select max($dateFname) from $dbMeta->tname");
                // up to this 'epoc', accumulate a total in a single row, if any
                $sql = "select min($dateFname) from $tname where $dateFname > 19610215 and $dateFname != 0" ;
                $dbMeta->firstDate = msDbGetString("$sql");
        }

}

/************************************************************/

// query builder

function msdbUserWhere()
{
        global $dbMeta;

        $ret = false ;

        if ( msdbDateRestr() )
                return(true);

        foreach ( $dbMeta->msdbFields as $f ) {
                if ( is_null($f->urlField) )
                        continue;

                if ( is_null($f->urlField->op) )
                        continue;

                $ret = true;
                $dbMeta->isSearch = true;
                 // it is important that isSearch is on
                // even if I return false from here on
                // so as not to execute a 'new'


                $op = $f->urlField->op ;


                if ( $op == '*' || $op == '!*' ) {
                        if ( $f->ftype == 'string' ) {
                                $theOp = ( $op == '*' ) ? 'like' : 'not like' ;
                                $v = '%'.$f->urlField->val.'%';
                                $val = msDbSqlValue($f, $v);
                                if ( $val == null )
                                        return(false);
                                $cond = "$f->fname $theOp $val";
                        } else {
                                msdbMsg("* on non-String Field ignored. Using '='");
                                $theOp = ( $op == '*' ) ? '=' : '!=' ;
                                $val = msDbSqlValue($f, $f->urlField->val);
                                if ( $val == null )
                                        return(false);
                                $cond = "$f->fname $theOp $val" ;
                        }
                } else {
                        if (($val = msDbSqlValue($f, $f->urlField->val)) == null )
                                return(false);
                        $cond = "$f->fname $op $val";
                }


                if ( is_null($dbMeta->where ) )
                        $dbMeta->where = "where $cond" ;
                else
                        $dbMeta->where .= " and $cond" ;
        }

        return($ret);
}

/******************************/

function msdbWhereMakesSense($tname, $where)
{
        $whereWords = preg_split('/[\s]+/', $where);
        /*	$whereWords = split(' ', $where); // is probably good enough	*/

        if ( ! is_array($whereWords) ) {
                msdbError("head.php".": ". 231 .": ".("msdbWhereMakesSense: :::$where:::"));
                return(true);
        }

        for($i=0;$i<count($whereWords);$i++) {
                if ( $i != 0 && msdbArrValIn($whereWords[$i-1], array('where', 'and'))) {
                        if ( msdbMetaFieldIndex($whereWords[$i]) == -1 ) {
                                msdbMsg("ipups: found obsolete senseless Where Clause '$where' for $tname");
                                return(false);
                        }
                }
        }

        return(true);
}

/******************************/

function msdbWhereClause()
{
        global $dbMeta;
        global $msdbDefWhere;

        if ( msdbUserWhere() ) {
                msdbUserPrefSetProperty('lastWhere', $dbMeta->where);
                return(true);
        }

        $prefWhere = msdbUserPrefGetProperty('lastWhere');

        if ( $prefWhere ) {
                if ( msdbWhereMakesSense($dbMeta->tname, $prefWhere) ) {
                        $dbMeta->where = $prefWhere ;
                        return(true);
                } else
                        msdbUserPrefSetProperty('lastWhere', -1);
                        return(true);
        }

        if ( isset($msdbDefWhere[$dbMeta->tname]) ) {
                $dbMeta->where = $msdbDefWhere[$dbMeta->tname];
                return(true);
        }
        if ( $dbMeta->dateFname && msdbDateWhere())
                return(true);

        $dbMeta->where = "" ;

        return(false);
}

/************************************************************/

function msdbGetObjData()
{
        global $dbMeta;

        for($i=0;$i<$dbMeta->colNum;$i++) {
                $f = & $dbMeta->msdbFields[$i] ;
                $f->urlField = new msdbUrlField($f->fname);
        }
}

/******************************/

function msdbLimit()
{
        global $dbMeta;
        global $msdbConfig;

        $w = $dbMeta->where;

        if ( $w == "" )
                $rn = $dbMeta->rowNum;
        else
                $rn = msDbGetInt("select count(*) from $dbMeta->tname $w");

        $sr = $msdbConfig['showRows'];
        $lr = $msdbConfig['limitRows'];


        if ( $rn < $lr ) {
                $dbMeta->limit = "";
                return;
        }

        if ( $w == "" )
                $dbMeta->limit = "limit $sr" ;
        else
                $dbMeta->limit = "limit $lr";
}

/************************************************************/

/*
 * order result by this logic:
 *
 * 1. if there is an incoming new order request,
 * 							( and update userPrefs,)
 * 2. ( if ther is a userPref for this table )
 * 3. by date
 * 4. by primary key
 */

function msdbOrderBy()
{
        global $dbMeta;
        global $msdbConfig;
        global $msdbDefOrderBy;


        $dbOb = msdbUserPrefGetProperty('orderBy');

        $urlOb = msdbGetPost("orderBy");

        $isDesc = msdbUserPrefGetProperty('isDesc');

        if ( $urlOb ) {
                if ( $dbOb == $urlOb ) {
                        // reverse the order
                        if ( $isDesc == 'true' )
                                $isDesc = -1 ;
                        else
                                $isDesc = 'true';

                        $dbMeta->orderBy = $dbOb ;
                        $dbMeta->isDesc = $isDesc ;
                        msdbUserPrefSetProperty('isDesc', $isDesc);
                } else {
                        // new order by selected
                        msdbUserPrefSetProperty('orderBy', $urlOb);
                        $dbMeta->orderBy = $urlOb ;
                        msdbUserPrefSetProperty('isDesc', -1);
                        $dbMeta->isDesc = null ;
                }
                return(true);
        }


        if ( $dbOb ) {
                if ( msdbMetaFieldIndex($dbOb) == -1 ) {
                        msdbMsg("ipups: found obsolete senseless orderBy field '$dbOb' for $dbMeta->tname");
                        msdbUserPrefSetProperty('orderBy', -1);
                        // and don't return, continue to look for orderBy below
                } else {
                        $dbMeta->orderBy = $dbOb ;
                        $dbMeta->isDesc = $isDesc ;
                        return(true);
                }
        }

        if ( isset($msdbDefOrderBy[$dbMeta->tname]) ) {
                $dbMeta->orderBy = $msdbDefOrderBy[$dbMeta->tname] ;
                return(true);

        }

        // reverse the order if LIMITing display to a subset
        $isDesc = ( $dbMeta->limit != null && $dbMeta->limit != "" ) ? 'true' : null ;

        if ( $dbMeta->dateFname ) {
                $dbMeta->orderBy = $dbMeta->dateFname ;
                $dbMeta->isDesc = $isDesc ;
                return(true);
        }

        if ( $dbMeta->primaryKey ) {
                $dbMeta->orderBy = $dbMeta->primaryKey ;
                $dbMeta->isDesc = $isDesc ;
                return(true);
        }

        return(false);
}

/******************************/

function msdbSetDB()
{
        global $msdbConfig;
        global $dbMeta;

        $dbDB = msdbUserPrefGetProperty('DB');
        $urlDB = msdbGetPost('msdbDB');
        $controlDB = $msdbConfig['DB_NAME'];

        $dbMeta->DB = null;

        if ( $urlDB )
                $db = $urlDB;
        else if ( $dbDB )
                $db = $dbDB;
        else {
                $db = $controlDB;
                return(true);
        }

        if ( $db != $controlDB && msDbIsOnlyDB() ) {
                msdbMsg("Unable to select $db in db only ($controlDB) mode");
                msdbUserPrefSetProperty('DB', -1);
                return(true); // continue in the only db
        }

        $dbMeta->DB = $db;

        if ( $db != $controlDB && ! msDbDatabaseHasTable($db, 'msdb_permit') ) {
                msdbMsg("Can not find table msdb_permit in Database $db");
                /*	echo "<BR><PRE><BR>\n\n";	*/
                /*	readfile('Install/msdb_permit.crtable');	*/
                /*	echo "<BR></PRE><BR>\n\n";	*/
                return(false);
        }

        if ( $urlDB && $dbDB != $urlDB )
                msdbUserPrefSetProperty('DB', $urlDB);

        $ret = msDbSelectDB($dbMeta->DB);

        return($ret);
}

/******************************/

function msdbSetTname()
{
        global $dbMeta;

        // actions for which the DB value of tname is obsolete
        // yet no new one is set
        $eaTnameObsolete = array('msdbSelectTable', 'msdbSelectDB');

        $dbTname = msdbUserPrefGetProperty('tname');
        $urlTname = msdbGetPost('msdbTNAME');
        $ea = $dbMeta->ea;


        $dbMeta->tname = null;

        if ( msdbArrValIn($ea, $eaTnameObsolete) ) {
                msdbUserPrefSetProperty('tname', -1);
                return(false);
        }

        if ( $urlTname ) {
                if ( ! msdbIsTable($urlTname) ) {
                        msdbMsg("table $urlTname does not exist in database $dbMeta->DB");
                        return(false);
                }
                $dbMeta->tname = $urlTname;
                if ( $dbTname != $urlTname )
                        msdbUserPrefSetProperty('tname', $urlTname);
                return(true);

        }

        if ( $dbTname ) {
                if ( ! msdbIsTable($dbTname) )
                        return(false);
                $dbMeta->tname = $dbTname;
                return(true);
        }

        return(false);
}

/******************************/

function msdbHead()
{
        global $dbMeta;

        // ea could also be msdbLogout, msdbSelectTable, msdbSelectDB (etc. ?)
        // so needed before msdbEnter() and others
        // 
        $dbMeta->ea = msdbGetPost('msdbEA');

        msdbPerf("head.php", 507, (''));

        if ( ! msdbEnter() )
                return(false);

        msdbPerf("head.php", 512, (''));
        msdbUserPrefsSetDbPropoerties();

        if ( ! msdbSetDB() ) {
                msdbMsg("Database not Selected");
                return(false);
        }


        $dbMeta->isTable = msdbSetTname();

        if ( $dbMeta->isTable ) {
                msDbMeta();
                msdbGetObjData();
                msdbPrepDate();
                msdbWhereClause();
                // msdbWhereClause sets the user requested isSearch
                // which implies that msdbInsert is really a search
                // so checking permissions must be after msdbUserWhere()
                if ( ! msDbIsOnlyDB() && ! msdbPermit() )
                        return(false);

                msdbLimit();
                msdbOrderBy();
        }

        msdbUserPrefsStoreDbPropoerties();

        return(true);
}

/************************************************************/

function msdbShowMenu()
{
        if ( msDbIsOnlyDB() )
                msdbInclude('include/menu.h', null);
        else
                msdbInclude('include/menuDB.h', null);
}

/************************************************************/
?>
