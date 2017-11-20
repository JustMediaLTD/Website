<?php
/************************************************************/



$rcsid='$Id: slike.php,v 1.3 2004/05/08 12:23:37 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/

function msdbHtmlOptions($values, $output, $selected, $options, $name)
{
        $ret = '';

        if ( $name )
                $ret .= "<select name=\"$name\">\n";

        if ( ! $options ) {
                if ( ! $values || ! $output ) {
                        msdbError("msdbHtmlOptions: not enough array data");
                        return(null);
                }
                $options = array_combine($values, $output); // php 5 or msdb/compat.php
        }

        foreach ( $options as $v => $o ) {
                if ( $selected == $v )
                        $s = ' selected';
                else
                        $s = '' ; // reset it
                $sv = htmlspecialchars($v);
                $so = htmlspecialchars($o);
                $ret .= "\t<option value=\"$sv\"$s>$so</option>\n";
        }

        if ( $name )
                $ret .= "</select>\n";

        return($ret);
}

/************************************************************/
?>
