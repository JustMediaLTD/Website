<?php
/************************************************************/



$rcsid='$Id: user.php,v 1.20 2004/07/29 08:48:07 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/
// allways strings strings (incl 'true' for isDesc).
/************************************************************/

$msdbUserPrefNames = array('DB', 'tname', 'orderBy', 'isDesc', 'lastWhere', 'curId');

function msdbUserPrefClearProperties()
{
        global $msdbUserPrefNames;
        global $msdbUserPrefs;

        foreach ( $msdbUserPrefNames as $name ) {
                $msdbUserPrefs['fromDB'][$name] = null;
                $msdbUserPrefs['fromUser'][$name] = null ;
                $msdbUserPrefs['runWith'][$name] = null ;
        }
}

/******************************/

function msdbUserPrefGetProperty($name)
{
        global $msdbUserPrefs;

        if ( ! msdbArrKeyIn($name, $msdbUserPrefs['fromDB']) ) {
                return(null);
        }

        $ret = $msdbUserPrefs['fromDB'][$name];

        return($ret);
}

/******************************/

// intialized individually to null,
// a property can ber set to the interger -1,
// which means clear the property, side effecting other properties
// see msdbUserPrefsMergePropoerties()
// and msdbUserPrefsStoreDbPropoerties()

function msdbUserPrefSetProperty($name, $value)
{
        global $msdbUserPrefs;

        $msdbUserPrefs['fromUser'][$name] = $value ;
}

/******************************/
//  1. never store in DB these fields without a table name and a db name
//	2. URL should allwways have a DB.tname if there is an orderBy, where,curid(insert/update).
//	3. if a requested  db.table is not whats in the database,
//		reset the rest of the values to null, except	as specified in the URL.

function msdbUserPrefsMergePropoerties()
{
        global $msdbUserPrefNames;
        global $msdbUserPrefs;
        global $msdbConfig;
        global $dbMeta;


        $controlDB = $msdbConfig['DB_NAME'];
        $fu = $msdbUserPrefs['fromUser'];
        $fdb = $msdbUserPrefs['fromDB'];

        // DB
        if ( $fu['DB'] == -1 ) {
                $msdbUserPrefs['runWith'] = array();
                return;
        }
        if ( $fu['DB'] )
                $msdbUserPrefs['runWith']['DB'] = $fu['DB'];
        else if ( $fdb['DB'] )
                $msdbUserPrefs['runWith']['DB'] = $fdb['DB'];
        else
                $msdbUserPrefs['runWith']['DB'] = $controlDB;

        // tname
        if ( $fu['tname'] == -1 ) {
                $msdbUserPrefs['runWith']['tname'] = null;
                return;
        }
        if ( $fu['tname'] )
                $tname = $fu['tname'] ;
        else if ( $fdb['tname'] )
                $tname = $fdb['tname'] ;
        else
                return; // clear all other properties too
        if ( ! msDbDatabaseHasTable($msdbUserPrefs['runWith']['DB'], $tname) )
                return; // clear all other properties too
        $msdbUserPrefs['runWith']['tname'] = $tname;

        // do not run with anything else if no tname
        if ( ! $dbMeta->tname )
                return;

        // orderBy, isDesc
        if ( $fu['orderBy'] )
                $ob = $fu['orderBy'];
        else if ( $fdb['orderBy'] )
                $ob = $fdb['orderBy'] ;
        else
                $ob = null;


        // isDesc is never explicitly set by the user, rahter head.php
        // sets it when the orderBy is requested on a field for which
        // Get property return the same name
        if ( $fu['isDesc'] )
                $isDesc = $fu['isDesc'];
        else if ( $fdb['isDesc'] )
                $isDesc = $fdb['isDesc'];
        else
                $isDesc = null;

        // if orderBy is not in this table, then lastWhere and curId
        // are probably not either so skip them
        if ( $ob == -1 || $ob != null && msdbMetaFieldIndex($ob) == -1 )
                return;

        $msdbUserPrefs['runWith']['orderBy'] = $ob;
        $msdbUserPrefs['runWith']['isDesc'] = $isDesc;

        // lastWhere and curId are now safe to set
        if ( $fu['lastWhere'] )
                $lastWhere = $fu['lastWhere'];
        else if ( $fdb['lastWhere'] )
                $lastWhere = $fdb['lastWhere'];
        else
                $lastWhere = null;
        $msdbUserPrefs['runWith']['lastWhere'] = $lastWhere;
        if ( $fu['curId'] )
                $curId = $fu['curId'];
        else if ( $fdb['curId'] )
                $curId = $fdb['curId'];
        else
                $curId = null;
        $msdbUserPrefs['runWith']['curId'] = $curId;
}

/************************************************************/

function msdbUserPrefsStoreDbPropoerties()
{
        global $msdbUserPrefs;
        global $msdbConfig;
        global $msdbEnterVar;

        msdbUserPrefsMergePropoerties();

        $store = $msdbUserPrefs['runWith'];

        /*	ksort($store);	*/

        $str = "" ;

        foreach ( $store as $name => $value ) {
                if ( $value == null || $value == -1 )
                        continue; // erase from the database. e.g. isDesc => nothing.
                $encoded = rawurlencode($value);
                $pair = $name."=".$encoded;
                if ( $str == "" )
                        $str = $pair;
                else
                        $str = $str."&".$pair ;

        }

        if ( $msdbEnterVar->pwent['userprefs'] == $str )
                return; // nothing to update

        $controlDb = $msdbConfig['DB_NAME'];
        $uid = $msdbEnterVar->pwent['userid'];

        $sql = "update $controlDb.msdb_passwd set userprefs = '$str' where userid = $uid";

        msDbSql("$sql");
}


/************************************************************/

// parse properties from the database
// and set in the fromDB global

function msdbUserPrefsSetDbPropoerties()
{
        global $msdbUserPrefs;
        global $msdbEnterVar;

        msdbUserPrefClearProperties();

        $str = $msdbEnterVar->pwent['userprefs'] ;

        if ( $str == "" )
                return;

        $nameVals = split('&', $str);

        foreach ( $nameVals as $nv ) {
                $nvPair = split('=', $nv);
                if ( count($nvPair) != 2 ) {
                        msdbMsg("User config value $nv not parsed");
                        continue;
                }
                list($name, $val) = $nvPair;
                $decoded = rawurldecode($val);
                $msdbUserPrefs['fromDB'][$name] = $decoded;
        }
}

/************************************************************/
?>
