<?php
/************************************************************/
$rcsid='$Id: send.php,v 1.10 2004/07/29 08:48:06 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/

function msdbSendColnames()
{
        global $dbMeta;

        $flds = & $dbMeta->msdbFields ;
        foreach ($flds as $f ) {
                $fname = $f->fname ;
                $ftype = $f->ftype;
                echo "\t\t msdbTop.colnames[msdbTop.colnames.length] = '$fname' ;\n" ;
                echo "\t\t msdbTop.coltypes[msdbTop.coltypes.length] = '$ftype' ;\n" ;
        }
}

/******************************/

function msdbSendLine($row)
{
        global $dbMeta;

        if ( $dbMeta->primaryKey )
                $pkValue = $row[$dbMeta->pkIndex] ;
        else
                $pkValue = "no key - Untouchable";

        echo "msdbTop.rows[msdbTop.rows.length] = new msdbRow('$pkValue');\n";
        echo "l = msdbTop.rows[msdbTop.rows.length -1].row;\n" ;

        foreach ( $row as $f ) {
                $v = msdbJsStr($f);
                echo "\tl[l.length] = '$v';\n" ;
        }
}

/******************************/

function msdbSend($rows)
{
        global $dbMeta;

        echo "<SCRIPT LANGUAGE=\"JavaScript\">\n" ;

        foreach ( $rows as $row )
                msdbSendLine($row);

        msdbSendColNames();

        if ( $dbMeta->iuFailed ) {
                $iuf = $dbMeta->iuFailed ;
                echo "\t\tmsdbTop.iuFailed = $iuf ;\n" ;

                if ( $dbMeta->uFailed == true ) {
                        $cid = $dbMeta->curId ;
                        echo "\t\tmsdbTop.uFailedId = $cid ;\n" ;
                }
        }
        echo "</SCRIPT>\n" ;
}

/************************************************************/
?>
