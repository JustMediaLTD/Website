<?php
/************************************************************/



$rcsid='$Id: actions.php,v 1.46 2004/05/31 14:55:29 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/

function msdbIsPrimary($fname)
{
        global $dbMeta;

        $tname = $dbMeta->tname;
        $rn = $dbMeta->rowNum;

        $dstnct = msDbGetInt("select count(distinct $fname) from $tname");

        if ( $rn > 0 && $dstnct != $rn )
                return(false);

        return(true);
}

/************************************************************/

function msdbPkWhere()
{
        global $dbMeta;

        if ( ! $dbMeta->primaryKey ) {
                msdbMsg("msdbPkWhere: no Primary Key");
                return(null);
        }

        $pkName = msdbGetPost('msdbPkName');

        if ( $pkName != $dbMeta->primaryKey ) {
                // recheck
                if ( ! msdbIsPrimary($pkName) ) {
                        msdbMsg("Primary Key problem: $pkName used to be unique, but no longer");
                        msdbMsg("Can not safely perform the operation requested (yet)");
                        msdbMsg("Refresh and try again");
                        return(null);
                }
        }

        $rawValue = msdbGetPost('msdbPkval');
        if ( $rawValue === null )
                return(null);

        if ( ($pkValue = msDbSqlValue($dbMeta->pkField, $rawValue)) == null )
                return(null);

        $w = "where $pkName = $pkValue";
        return($w);
}

/************************************************************/

/*
 * show create table and insert statements
 */

function msdbGenInsert()
{
        global $dbMeta;
        global $msdbConfig;

        $tname = $dbMeta->tname;
        $pkName = $dbMeta->primaryKey;

        if ( $pkName )
                $ob = "order by $pkName" ;
        else
                $ob = "";

        $lots = $msdbConfig['LOTS_OF_ROWS'];

        if ( $dbMeta->rowNum == 0 ) {
                msdbMsg("$tname: 0 rows");
                return;
        }

        if ( $dbMeta->rowNum > $lots ) {
                // msdb is not intended as an admin tool
                msdbMsg("$tname: Too many Rows ($dbMeta->rowNum). use mysqldump.");
                return;
        }

        $rows = msDbGetAssoc("select * from $tname $ob");

        echo "<BR><BR><PRE>\n\n\n";

        foreach ( $rows as $row ) {
                $valueList = array();
                for($i=0;$i < $dbMeta->colNum;$i++) {
                        $f = & $dbMeta->msdbFields[$i] ;
                        $valueList[] = msDbSqlValue($f, $row[$f->fname]);
                }
                $str = "insert into $tname values (".implode(", ", $valueList).");";
                echo "$str\n";
        }
        echo "\n\n\n</PRE><BR><BR>\n";

}

/******************************/

/*
 * debugging tool to aid re-creating bugs in a separate environment
 */

function msdbGenCrtable()
{
        global $dbMeta;

        $tname = $dbMeta->tname;

        if ( ($crtable = msDbGetCreateTable($tname)) == null)
                return;

        echo "<BR><PRE><BR>\n\n";
        echo $crtable.";\n\n";
        echo "<BR></PRE><BR>\n\n";

        msdbGenInsert();
}

/************************************************************/

function msdbInsert()
{
        global $dbMeta;

        $dbMeta->curId = null;
        $dbMeta->insertIdOr = null;

        // first, check to see that its not a search action rather than insert
        if ( $dbMeta->isSearch )
                return;

        $isEmpty = true;
        $row = array();
        foreach ( $dbMeta->msdbFields as $f ) {
                if ( $f->ftype == 'timestamp' )
                        continue;
                if ( $f->isAutoInc ) {
                        $autoIncFname = $f->fname;
                        continue;
                }

                $fstr = $f->urlField->val ;
                if ( $fstr != "" )
                        $isEmpty = false;

                if ( ($fvalue = msDbSqlValue($f, $fstr)) == null ) {
                        msdbMsg("Not Inserted");
                        return;
                }

                if ( $f->fname == $dbMeta->primaryKey && ! $f->isAutoInc ) {
                        $dbMeta->curId = $fstr;
                        $dbMeta->insertIdOr = "$f->fname = $fvalue";
                }

                $row[$f->fname] = $fvalue;
        }

        if ( $isEmpty ) {
                msdbMsg("Empty row not inserted");
                return;
        }

        if ( ! msDbPreInsert($dbMeta->tname, $row) )
                return;

        $ins = msDbInsertSql($dbMeta->tname, $row);

        $affected = msDbSql($ins);

        if ( $affected < 1 ) {
                msdbMsg("Nothing Inserted");
                return;
        }

        msdbMsg("One Row Inserted by: $ins");

        if ( is_null($dbMeta->curId) && ! isset($autoIncFname) )
                return;

        if ( ( $dbMeta->curId = @msDbInsertId() ) )
                $dbMeta->insertIdOr = "$autoIncFname = $dbMeta->curId" ;

        msdbUserPrefSetProperty('curId', $dbMeta->curId);
        msdbUserPrefsStoreDbPropoerties();

}

/************************************************************/

function msdbUpdate()
{
        global $dbMeta;

        if ( ( $w = msdbPkWhere()) == null ) {
                msdbError("actions.php".": ". 206 .": ".("msdbUpdate: No Key Where Clause: nothing Changed"));
                return;
        }

        $tname = $dbMeta->tname;

        $dbMeta->curId = msdbGetPost('msdbPkval');
        msdbUserPrefSetProperty('curId', $dbMeta->curId);
        msdbUserPrefsStoreDbPropoerties();

        $row = array();
        foreach ( $dbMeta->msdbFields as $f ) {
                if ( $f->ftype == 'timestamp' )
                        continue;
                $fname = $f->fname ;
                $fstr = $f->urlField->val ;
                if ( ($fvalue = msDbSqlValue($f, $fstr)) == null ) {
                        msdbMsg("Not updated");
                        return;
                }
                $row[$fname] = $fvalue;
        }

        $sql = msDbUpdateSql($tname, $row, $w);

        $affected = msDbSql($sql);

        if ( $affected == 1 ) {
                msdbMsg("One Row Affected by: $sql");
                return;
        }

        if ( $affected == 0 ) {
                msdbMsg("Nothing Changed: :::$sql:::");
                return;
        }
}

/************************************************************/

function msdbDelete()
{
        global $dbMeta;

        if ( ( $w = msdbPkWhere()) == null ) {
                msdbError("actions.php".": ". 251 .": ".("msdbDelete: No Key Where Clause: nothing Deleted"));
                return;
        }

        $tname = $dbMeta->tname;

        $del = "delete from $tname $w" ;

        $affected = msDbSql($del);

        if ( $affected == 1 ) {
                /*	MSDB_ERROR("msdbDelete: Deleted: :::$del:::");	*/
                msdbMsg("Deleted 1 row from $tname $w");
                msdbUserPrefSetProperty('curId', -1);
                return;
        }

        if ( $affected < 1 ) {
                msdbError("actions.php".": ". 269 .": ".("msdbDelete: Nothing Deleted: :::$del:::"));
                return;
        }

        msdbUserPrefSetProperty('curId', -1);

        msdbError("actions.php".": ". 275 .": ".("msdbDelete: hayWire SQL warning: :::$del::: affected $affected rows"));
}

/************************************************************/

function msdbAction()
{
        global $dbMeta;

        $ea = $dbMeta->ea;

        if ( $ea == 'msdbInsert' )
                msdbInsert();
        else if ( $ea == 'msdbDelete' )
                msdbDelete();
        else if ( $ea == 'msdbUpdate' )
                msdbUpdate();
        else if ( $ea == 'msdbTest' )
                msdbTest();
        else if ( $ea == 'msdbGenCrtable' )
                msdbGenCrtable();
        else if ( $ea == 'msdbSearch' )
                ; // don't fall back to the error
        else
                msdbError("actions.php".": ". 299 .": ".("msdbAction: $ea: unknown"));

        echo "<BR><BR>";
}

/************************************************************/
?>
