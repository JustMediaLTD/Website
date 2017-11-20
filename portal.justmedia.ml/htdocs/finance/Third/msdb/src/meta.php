<?php
/************************************************************/
#include "msdb.h"
$rcsid='$Id: meta.php,v 1.60 2004/08/21 12:38:28 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/

$msdbDataTypes = array (
	'string' => 'string',
	'int' => 'int',
	'real' => 'real',
	'date' => 'date',
	'datetime' => 'datetime',
	'time' => 'time',
	'timestamp' => 'timestamp',

	'blob' => 'string',
	'year' => 'int'
);

/******************************/

function msdbDataType($dbtype)
{
	global $msdbDataTypes;

	if ( ! msdbArrKeyIn($dbtype, $msdbDataTypes) ) {
		MSDB_ERROR("$dbtype: unsupported db data type. your mysql rev ?");
		return('');
	}
	return($msdbDataTypes[$dbtype]);
}

/************************************************************/

class dbMetaField
{
	function dbMetaField($fname, $dbtype)
	{
		$this->fname = $fname ;
		$this->dbtype = $dbtype ;
		$this->ftype = msdbDataType($dbtype);
		$this->isAutoInc = false ;
	}
}

/******************************/

class dbMetaClass
{
	function dbMetaClass()
	{
		$this->DB = null ; // current Database, (not control DB, though this is the default)
		$this->tname = null ;
		$this->isTable = null ;
		// $this->fields = null; // not set. see msDbMeta()
		// $this->showColumns = null; // not set. see msdbShowColumnsInfo()
		$this->noPerm = null; // true if permission to perform the operation is denied

		$this->primaryKey = null; // name of pk

		$this->pkIndex = null; // index into data row array

		$this->rowNum = null;
		$this->colNum = null;
		$this->dateFname = null;
		$this->firstDate = null; // in table
		$this->lastDate = null;
		$this->dateList = null ; // list of dates for report by date
		$this->orderBy = null; // fname
		$this->isDesc = null; // null or 'true' (the string) or -1, (-1 is not used as such but is set and means 'conciously ascendind)
							  // while null means ascending by default
		$this->dateCntBalance = 0; // balance total of how many itms are totaled in date reports
		$this->dateBalances = null; // array of balances (one item for each column totaled)
		$this->where = null;  // where clause to be custructed
		$this->limit = null;  // "LIMIT $rownum"
		$this->whereEtc = null;  // the entire SQL less the trivial select part
		$this->isSearch = null; // is op a search
		$this->ea = null; // is op a search
		$this->isInserted = null; // was the insert successful
		$this->isUpdated = null; // was the update successful
		$this->curId = null; // value of primary key of row being affected
		$this->insertIdOr = null; // '...or row was just inserted'
		$this->uFailed = null; // update faied
		$this->iFailed = null; // insert faied
		$this->iuFailed = null; // either, send to the javascript database in the page

		$this->msdbFields = null; // Field attributes
		$this->pkField = null;	// $this->msdbFields[$this->$pkIndex]


		$this->ret = false ;
	}
}

/******************************/

global $dbMeta;
$dbMeta = new dbMetaClass();


/******************************/

function msdbMetaFieldIndex($fname)
{
	global $dbMeta;

	$i = 0;
	foreach ( $dbMeta->msdbFields as $f ) {
		if ( $f->fname == $fname)
			return($i);
		$i++;
	}
	return(-1);
}

/************************************************************/

function msdbShowColumnsInfo()
{
	global $dbMeta;

	$tname = $dbMeta->tname;

	$ci = msDbGetAssoc("show columns from $tname");
	// for msdbInfo
	$dbMeta->colInfo = $ci ;

	// dont break, there can be more than one autoincrement column
	for($i=0;$i<count($ci);$i++) {
		if ( ! isset($ci[$i]['Extra']) )
			continue;
		if ( $ci[$i]['Extra'] != 'auto_increment' )
			continue;

		$dbMeta->msdbFields[$i]->isAutoInc = true;
		$dbMeta->primaryKey = $dbMeta->msdbFields[$i]->fname ;
		$dbMeta->pkIndex = $i ;
		$dbMeta->pkField = & $dbMeta->msdbFields[$i];
		break;
	}
}

/**********/

// look for a primary key to base updates and deletes on.
// only trust auto increment or a the result from a distinct()

function msdbLookForPrimary()
{
	global $dbMeta;
	global $msdbConfig;

	$rn = $dbMeta->rowNum;
		
	msdbShowColumnsInfo();

	if ( $dbMeta->primaryKey )
		return;

	// above this many rows, you should really consider putting a key in
	$lots = $msdbConfig['LOTS_OF_ROWS'];
	if ( $rn > $lots )
		return(false); 

	$tname = $dbMeta->tname;

	for($i=0;$i<$dbMeta->colNum;$i++) {
		$f = & $dbMeta->msdbFields[$i];
		$fname = $f->fname;
		if ( $rn <= 1 )
			$dstnct = 0 ; // any column is good
		else
			$dstnct = msDbGetInt("select count(distinct $fname) from $tname");
		if ( $rn > 1 && $rn != $dstnct )
			continue;
		$dbMeta->primaryKey = $f->fname ;
		$dbMeta->pkIndex = $i ;
		$dbMeta->pkField = & $f ;
		break;
	}
}

/******************************/

function msDbMeta()
{
	global $dbHandle;
	global $dbMeta;

	$tname = $dbMeta->tname;

	$sql = "select * from $tname limit 0" ;

	if ( ! msDbQuery($sql) ) {
		MSDB_ERROR("dbMetaClass: $sql: Can not get meta data for $tname");
		return($dbMeta->ret);
	}

	$dbMeta->colNum = mysql_num_fields($dbHandle->result);

	for($i=0;$i<$dbMeta->colNum;$i++) {
		$f = mysql_fetch_field($dbHandle->result, $i);
		// for msdbInfo
		/*	$dbMeta->fields[] = $f ;	*/
		$dbMeta->msdbFields[] = new dbMetaField($f->name, $f->type);
	}
	mysql_free_result($dbHandle->result);

	$dbMeta->rowNum = msDbRowNum($dbMeta->tname);

	msdbLookForPrimary();

	$dbMeta->ret = true;


	return(true);
}

/******************************/
?>
