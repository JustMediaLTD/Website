<?php
/************************************************************/



$rcsid='$Id: msDb.php,v 1.89 2004/09/02 12:11:03 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/

/*
 * msDb - Msdb Database Layer
 */
/******************************/

class msDbHandle
{
        function msDbHandle()
        {
                // connection
                $this->link = null ;
                $this->connected = null ;

                // protocol
                $this->result = null ;
                $this->error = null;
                $this->lastSql = null ;
        }
}

/******************************/
global $dbHandle ;
$dbHandle = new msDbHandle();
/******************************/

function msDbPing()
{
        global $dbHandle;

        if ( ! function_exists('mysql_ping') )
                return(true); // ?

        if ( @mysql_ping($dbHandle->link) )
                return(true);

        msdbError("msDb.php".": ". 44 .": ".("msDbPing: mysql_ping failed"));
        return(false);
}

/**********/

function msDbConnect()
{
        global $dbHandle;
        global $msdbConfig;

        if ( ! is_null($dbHandle->connected) ) {
                if ( ! $dbHandle->connected )
                        return(false);
                if ( ! msDbPing() ) {
                        msdbError("msDb.php".": ". 59 .": ".("msDbConnect: (re-connect): msDbPing failed"));
                        $dbHandle->connected = false ;
                        return(false);
                }
                return(true);
        }

        $dbHandle->connected = false;

        if ( isset($msdbConfig['DB_HOST']) )
                $host = $msdbConfig['DB_HOST'];
        else
                $host = 'localhost';

        $dbHandle->link = @mysql_connect(
                        $host,
                        $msdbConfig['DB_USER'],
                        $msdbConfig['DB_PW']
                );

        if ( ! $dbHandle->link ) {
                $err = $dbHandle->error = mysql_error() ;
                msdbError("msDb.php".": ". 81 .": ".("msDbConnect: Could not connect to database ($err)"));
                return(false);
        }

        // mysql will not allow queries without a selected DB
        // even if the query is from $db.$table
        $controlDB = $msdbConfig['DB_NAME'];

        if ( ! msDbSelectDB($controlDB) ) {
                msdbError("msDb.php".": ". 90 .": ".("msDbConnect: Unable to select $controlDB"));
                return(false);
        }


        $dbHandle->connected = true ;

        return(true);
}

/******************************/
// not to be confused with the user selecting a database
// presented by msdbListDBs()

function msDbSelectDB($dbname)
{
        global $dbHandle;
        global $msdbConfig;

        $cdb = $msdbConfig['DB_NAME'];

        if ( $dbname != $cdb && msDbIsOnlyDB() ) {
                msdbError("msDb.php".": ". 112 .": ".("msDbSelectDB: Unable to select $dbname in db only ($cdb) mode"));
                return(false);
        }

        if ( ! @mysql_select_db($dbname, $dbHandle->link) ) {
                msdbError("msDb.php".": ". 117 .": ".("msDbSelectDB: Unable to select $dbname"));
                return(false);
        }

        return(true);

}

/******************************/

// all sql queries to the databse go through here.

function msDbQuery($sql)
{
        global $dbHandle;
        global $msdbQueries;
        global $msdbStats;
        static $statQcnt = 0 ;

        if ( $msdbStats->started ) {
                if ( $statQcnt == 0 )
                        $msdbQueries[] = ' --------------- Starting Stats Queries --------';
                $statQcnt++;
        }

        $msdbQueries[] = $sql ;

        // stop stats reporting after this many queries
        if ( $statQcnt > 100 )
                return(false);

        $dbHandle->lastSql = $sql ;

        if ( ! ( msDbConnect()) )
                return(false);

        $dbHandle->result = @mysql_query($sql, $dbHandle->link);

        if ( ! $dbHandle->result ) {
                $dbHandle->error = @mysql_error($dbHandle->link) ;
                if ( stristr($dbHandle->error, "duplicate") || stristr($dbHandle->error, "You have an error in your SQL syntax near") ) {
                        msdbMsg("$dbHandle->error");
                        msdbMsg("$sql");
                } else
                        /*	MSDB_ERROR("$dbHandle->error: $sql");	*/
                        msdbMsg("$dbHandle->error");
                        msdbMsg("$sql");
                return(false);
        }

        return(true);
}

/**********/

function msDbSql($sql)
{
        global $dbHandle;

        if ( ! msDbQuery($sql) )
                return(-1);

        $affected = @mysql_affected_rows($dbHandle->link);

        /* result is bool(true) in this case so don't mysql_free_result() */
        /*	var_dump($dbHandle->result);	*/

        if ( $affected < 0 )
                return(-1);

        return($affected);
}

/******************************/

function msDbInsertId()
{
        return(@mysql_insert_id());
}

/************************************************************/

function msDbDatabaseTables($db)
{
        $q = "show tables from $db";
        $ret = msDbGetStrings($q);
        if ( ! is_array($ret) ) // empty DB ?
                return(array());
        natcasesort($ret);
        return($ret);
}

/******************************/

function msDbDatabaseHasTable($db, $table)
{
        return(msdbArrValIn($table, msDbDatabaseTables($db)));
}

/******************************/

function msDbDataBases()
{
        $msdbDbList = msDbGetStrings("show databases");

        return($msdbDbList);
}

/******************************/

function msDbTables()
{
        if ( ! ($msdbTableList = msDbGetStrings("show tables")) )
                return(array());

        natcasesort($msdbTableList);

        return($msdbTableList);
}

/******************************/

function msDbIsTable($t)
{
        return(msdbArrValIn($t, msDbTables()));
}

/******************************/

function msDbRowNum($tname)
{
        return(msDbGetInt("select count(*) from $tname"));
}

/************************************************************/

function msDbFetchRows($sql)
{
        global $dbHandle;

        if ( ! msDbQuery($sql) )
                return(null);

        $ret = array();

        while($row = @mysql_fetch_row($dbHandle->result))
                $ret[] = $row ;

        @mysql_free_result($dbHandle->result);

        return($ret);
}

/******************************/

function msDbFetchRow($sql)
{
        if ( ( $rows = msDbFetchRows($sql)) == null || count($rows) == 0 )
                return(null);
        return($rows[0]);
}

/******************************/

function msDbGetStrings($sql)
{
        if ( ( $rows = msDbFetchRows($sql)) == null || count($rows) < 0)
                return(null);

        foreach ( $rows as $row )
                $ret[] = $row[0] ;

        return($ret);
}

/******************************/

function msDbGetString($sql)
{
        if ( ( $row = msDbFetchRow($sql)) == null )
                return(null);

        return($row[0]);
}

/******************************/

function msDbGetInt($sql)
{
        if ( ! ($ret = msDbGetString($sql)) )
                return(null);

   return((int)$ret);
}

/************************************************************/

function msDbGetAssoc($cmd)
{
        global $dbHandle;

        if ( ! msDbQuery($cmd) )
                return(null);

        $numRows = @mysql_num_rows($dbHandle->result);

        $ret = array();

        while($r = @mysql_fetch_assoc($dbHandle->result))
                $ret[] = $r ;

        @mysql_free_result($dbHandle->result);

        return($ret);
}

/******************************/

function msDbGet1Assoc($cmd)
{
        $ret = msDbGetAssoc($cmd);

        if ( ! is_array($ret) || count($ret) != 1 )
                return(null);
        return($ret[0]);
}

/************************************************************/

function msDbFetchObjects($cmd)
{
        global $dbHandle;

        if ( ! msDbQuery($cmd) )
                return(null);

        $nr = @mysql_num_rows($dbHandle->result);

        $ret = array();
        while($obj = @mysql_fetch_object($dbHandle->result))
                $ret[] = $obj ;

        @mysql_free_result($dbHandle->result);

        return($ret);
}

/******************************/

function msDbGetObject($cmd)
{
        $rows = msDbFetchObjects($cmd);
        if ( count($rows) != 1 )
                return(null);

        return($rows[0]);
}

/************************************************************/

function msDbSqlValue($field, $val)
// dbMetaField $field;
{
        $ftype = $field->ftype;

        if ( $ftype == 'string' ) {
                // both should work
                /*	$v = str_replace("'", "''", $val);	*/
                $v = str_replace("'", "\\'", $val);
                return("'$v'");
        }

        if ( $ftype == 'real' ) {
                if ( is_null($val) || $val == "" )
                        return("0.0");
                $d = 0.0 ;
                 if ( sscanf($val, "%lf", & $d) == 1 )
                        return("$d");
                msdbMsg("$field->fname: $val: not a valid $field->dbtype ");
                return(null);
        }

        if ( $ftype == 'int' ) {
                if ( is_null($val) || $val == "" )
                        return("0");
                $i = 0 ;
                if ( sscanf($val, "%d", & $i) == 1)
                        return("$i");
                msdbMsg("$field->fname: $val: not a valid $field->dbtype");
                return(null);
        }

        if ( $ftype == 'date' ) {
                if ( msdbDayIsZero($val) )
                        $dt = 0 ;
                else
                        $dt = msdbDayScan($val);
                if ( ! is_null($dt) )
                        return("'$dt'");
                msdbMsg("$field->fname: $val: not a valid $field->dbtype");
                return(null);
        }

        if ( $ftype == 'datetime' ) {
                // ???
                return("'$val'");
        }

        if ( $ftype == 'timestamp' ) {
                // ???
                return("'$val'");
        }

        if ( $ftype == 'time' ) {
                // ???
                return("'$val'");
        }

        // ftype is ''
        msdbError("msDb.php".": ". 436 .": ".("msDbSqlValue: $field->dbtype: unsupported data type: value is :::$val:::"));
        return(null);
}

/************************************************************/

function msDbGetCreateTable($tname)
{
        $sql = "show create table $tname";

        $rows = msDbGetAssoc("show create table $tname");

        if ( count($rows) != 1 ) {
                msdbError("msDb.php".": ". 449 .": ".(""));
                return(null);
        }
        return($rows[0]['Create Table']);
}

/************************************************************/

function msDbColNames($table)
{
        $showColumns = msDbGetAssoc("show columns from $table");
        foreach ( $showColumns as $col )
                $ret[] = $col['Field'];
        return($ret);
}

/************************************************************/

// register an insert trigger
// (same as defining a function with name)
// preInsert_$tblName($row)
// row is associative with field names as indices

$insertTriggers = array();

function msDbInsertTrigger($tname, $trigger)
{
        global $insertTriggers;

        $insertTriggers[$tname] = $trigger ;
}

/********************/

// call the preinsert trigger
// abort the insert if it does not return true

function msDbPreInsert($tname, & $row)
{
        global $insertTriggers;

        if ( function_exists("preInsert_$tname") ) {
                $funcName = "preInsert_$tname";
                $ret = $funcName($row);
                return($ret);
        }

        if ( ! isset($insertTriggers[$tname] ) )
                return(true);
        $trigger = $insertTriggers[$tname];
        if ( ! function_exists($trigger) ) {
                msdbError("msDb.php".": ". 500 .": ".("msDbPreInsert: registered trigger '$tirgger' does not exist"));
                return(false);
        }

        $ret = $trigger($row);

        return($ret);
}

/******************************/

// create an insert statement based on the associative array of name value pairs in $row

function msDbInsertSql($tableName, $data)
{
        $names = array();
        $values = array();

        $colNames = msDbColNames($tableName);
        if ( ! $colNames ) {
                msdbMsg("Can Not Get Column Names for table $tableName");
                return(false);
        }

        foreach ( $data as $name => $value ) {
                if ( ! msdbArrValIn($name, $colNames) )
                        continue;
                if ( msDbIsAI($tableName, $name ) )
                        continue;

                $names[] = $name;
                // fix unquoted values
                if ( $value == '' )
                        $values[] = "''";
                else if ( $value[0] == "'" )
                        $values[] = $value;
                else
                        $values[] = "'$value'";
        }

        $nameList = implode(', ', $names);
        $valueList = implode(', ', $values);

        $ret = "insert into $tableName ( $nameList ) values ( $valueList )" ;

        return($ret);
}

/************************************************************/
// register an update trigger
// to be called as triggerName($tableName, $row)
// below is the simpler interface also:
// preUpdate$tblName($row)
// row is associative with field names as indices

$updateTriggers = array();

function msDbUpdateTrigger($tname, $trigger)
{
        global $updateTriggers;

        $updateTriggers[$tname] = $trigger ;
}

/********************/

// call the preupdate trigger
// abort the update if it does not return true

function msDbPreUpdate($tname, & $row)
{
        global $updateTriggers;

        if ( function_exists("preUpdate_$tname") ) {
                $funcName = "preUpdate_$tname";
                $ret = $funcName($row);
                return($ret);
        }

        if ( ! isset($updateTriggers[$tname] ) )
                return(true);
        $trigger = $updateTriggers[$tname];
        if ( ! function_exists($trigger) ) {
                msdbError("msDb.php".": ". 583 .": ".("msDbPreUpdate: registered trigger '$trigger' does not exist"));
                return(false);
        }

        $ret = $trigger($tname, $row);

        return($ret);
}

/******************************/

// create an update statment based on the associative array of name value pairs in $row
// return null if the pre update trigger exists and returned false
// $w is the key where clause that updates the row
// $row can be $_REQUEST 

function msDbUpdateSql($tableName, $row, $w)
{
        if ( ! msDbPreUpdate($tableName, $row) )
                return(null);

        $names = array();
        $values = array();
        $colNames = msDbColNames($tableName);

        $updates = array();
        foreach ( $row as $n => $val ) {
                if ( ! msdbArrValIn($n, $colNames) )
                        continue;

                if ( $val == '' )
                        $v = "''";
                else if ( $val[0] == "'" )
                        $v = $val;
                else
                        $v = "'$val'";

                $updates[] = "$n = $v";
        }

        $u = implode(', ', $updates);

        $ret = "update $tableName set $u $w limit 1";

        return($ret);
}

/************************************************************/
/*------------------------------------------------------------------------------------------------------
mysql> show index from cb_cb ;
+-------+------------+----------+--------------+-------------+-----------+-------------+----------+--------+---------+
| Table | Non_unique | Key_name | Seq_in_index | Column_name | Collation | Cardinality | Sub_part | Packed | Comment |
+-------+------------+----------+--------------+-------------+-----------+-------------+----------+--------+---------+
| cb_cb |          0 | PRIMARY  |            1 | id          | A         |           0 |     NULL | NULL   |         |
+-------+------------+----------+--------------+-------------+-----------+-------------+----------+--------+---------+
1 row in set (0.00 sec)

mysql> show columns from cb_cb ;
+-----------+-------------+------+-----+---------+----------------+
| Field     | Type        | Null | Key | Default | Extra          |
+-----------+-------------+------+-----+---------+----------------+
| category  | varchar(64) | YES  |     | NULL    |                |
| date      | date        | YES  |     | NULL    |                |
| amount    | double      | YES  |     | NULL    |                |
| toFrom    | varchar(64) | YES  |     | NULL    |                |
| notes     | varchar(64) | YES  |     | NULL    |                |
| closed    | int(11)     | YES  |     | NULL    |                |
| entered   | date        | YES  |     | NULL    |                |
| wasClosed | int(11)     | YES  |     | NULL    |                |
| id        | int(11)     |      | PRI | NULL    | auto_increment |
+-----------+-------------+------+-----+---------+----------------+
9 rows in set (0.00 sec)
------------------------------------------------------------------------------------------------------------------------*/


function msDbIsAI($table, $field)
{
        /*	$si = msDbGetAssoc("show index from $table");	*/
        $sc = msDbGetAssoc("show columns from $table");

        foreach ( $sc as $column ) {
                if ( $column['Field'] == $field ) {
                        if ( $column['Extra'] == 'auto_increment' ) {
                                /*	msDbMsg("Found Auto Increment $table:$field");	*/
                                return(true);
                        } else {
                                return(false);
                        }
                }
        }

        msdbError("msDb.php".": ". 674 .": ".("Can not find $field in $table"));
        return(false);
}

/************************************************************/

function msDbIsOnlyDB()
{
        global $msdbConfig;

        if ( isset($msdbConfig['ONLY_DB']) && $msdbConfig['ONLY_DB'] == 'true' )
                return(true);
        return(false);
}
/************************************************************/


/*
 * break down from the url the value of the field from a possible search operator
 * and/or handle the explicit search:
 * str is what the user typed, op is the comparison operator, wether explicit or typed,
 * val is the value, without the op
 *
 * the 'url' may be a post
 */

class msdbUrlField
{
        function msdbUrlField($fname)
        {
                global $dbMeta;
                $searchOps = array(">=", "<=", ">", "<", "=", "!=", "*", "!*", "like", "not like");

                $this->str = null;
                $this->op = null ;
                $this->val = null ;

                $this->str = msdbGetPost($fname);

                if ( $this->str == null )
                        return; // op must stay null so this field can be ignored later,
                                        //even though the sarch form always passes some op

                // in msdb screens, this is define by the explicit search div
                // if ( $dbMeta->ea == 'msdbSearch' ) {
                if ( isset($_REQUEST["msdbOp_$fname"] ) ) {
                        $this->op = msdbGetPost("msdbOp_$fname");
                        $this->val = $this->str;
                        if ( $this->op == 'like' )
                                $this->val .= "%" ;
                        return;
                }

                /*
		 * we want '<= ohad aloni' to go
		 * op === '<='
		 * val === 'ohad aloni'
		 */

                $str = $this->str;

                list($op, $val) = sscanf($str, "%s %s");

                if ( is_null($op) )
                        return;

                if ( ! msdbArrValIn($op, $searchOps) ) {
                        $this->val = $str;
                        return;
                }

                $this->op = $op;

                $this->val = substr($str, strlen($op)+1);
        }
}

/************************************************************/

// simple query builder
// fields in $_REQUEST with names matching that of columns in the table
// are parsed for 'op value'. A string values is implied '%val%', numbers and dates are subject to = <=, etc.
// the result is a string:  a set of conditions anded together.
// if (( $conds = msDbQbuild())
//	$sql = "select * from $table where $conds";

function msDbQueryBuild($table)
{

        global $dbMeta;

        $ret = array();

        $dbMeta->tname = $table;
        msDbMeta();


        foreach ( $dbMeta->msdbFields as $f ) {
                if ( ! isset($_REQUEST[$f->fname]) || ! $_REQUEST[$f->fname] )
                        continue;

                if ( ! ( $urlField = new msdbUrlField($f->fname) ) )
                        continue;
                if ( is_null($urlField->op) )
                        continue;

                $op = $urlField->op ;

                if ( $op == '*' || $op == '!*' ) {
                        if ( $f->ftype == 'string' ) {
                                $theOp = ( $op == '*' ) ? 'like' : 'not like' ;
                                $v = '%'.$urlField->val.'%';
                                $val = msDbSqlValue($f, $v);
                                if ( $val == null )
                                        return(false);
                                $ret[] = "$f->fname $theOp $val";
                        } else {
                                /*	msdbMsg("* on non-String Field ignored. Using '='");	*/
                                $theOp = ( $op == '*' ) ? '=' : '!=' ;
                                $val = msDbSqlValue($f, $urlField->val);
                                if ( $val == null )
                                        return(false);
                                $ret[] = "$f->fname $theOp $val" ;
                        }
                } else {
                        if (($val = msDbSqlValue($f, $urlField->val)) == null )
                                return(false);
                        $ret[] = "$f->fname $op $val";
                }
        }

        return((count($ret) == 0) ? false : implode(" and ", $ret));
}

/************************************************************/
?>
