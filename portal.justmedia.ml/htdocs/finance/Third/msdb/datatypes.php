<?php
/************************************************************/
$rcsid='$Id: datatypes.php,v 1.7 2004/02/26 12:30:25 engine Exp engine $ ';
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
?>
