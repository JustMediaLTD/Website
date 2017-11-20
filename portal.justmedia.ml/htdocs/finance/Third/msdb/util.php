<?php
/************************************************************/



$rcsid='$Id: util.php,v 1.72 2004/09/02 12:11:07 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/

function msdb_r($var)
{
        /*	echo "\n<BR><HR><PRE>\n";	*/
        echo "\n<PRE>\n";
        print_r($var);
        /*	echo "\n</PRE><HR><BR>\n";	*/
        echo "\n</PRE>\n";
}

/************************************************************/

function msdbMailTo($to, $err)
{
        if ( strstr("$to", "@") ) {
                mail($to, 'msdbError', "$err");
                return;
        }
        if ( ! function_exists('posix_getpwnam') )
                return;

        if ( ! ( $pw = posix_getpwnam($to) ) )
                return;

        mail($to, 'msdbError', $err);
}

/******************************/

/*
 * log a msg on stderr (normally the server side error log file)
 */

global $msdbStderr;
$msdbStderr = null;

function msdbLogError($msg)
{
        global $msdbStderr;
        global $msdbVersion;
        global $msdbConfig;
        global $_SERVER;
        global $SERVER_NAME;

        if ( isset($SERVER_NAME) )
                $sn = $SERVER_NAME;
        else if ( isset($_SERVER['SERVER_NAME']) )
                $sn = $_SERVER['SERVER_NAME'];
        else
                $sn = "(server?)";

        if ( ! ($ip = msdbRemoteIp()) )
                $ip = '';

        if ( is_null($msdbStderr) )
                $msdbStderr = fopen('php://stderr', 'w');

        $date = msdbDateTimeNow();
        if ( ! ($url = @ $_SERVER['QUERY_STRING'] ) )
                $url = "" ;

        // fgrep msdbError /.../error_log*
        $logError = "$msdbVersion: $date: $msg (ip: $ip, URL: $url, server: $sn)";
        fputs($msdbStderr, "msdbError: $logError\n");

        if ( isset($msdbConfig['mailErrorsTo']) )
                msdbMailTo($msdbConfig['mailErrorsTo'], $logError);
}

/************************************************************/
$msdbMsgBuf = array();
$msdbMsgsIsHold = false;
/******************************/

function msdbHoldMsgs()
{
        global $msdbMsgsIsHold;

        $msdbMsgsIsHold = true;
}

/******************************/

function msdbShowMsg($msg)
{
        $m = htmlspecialchars($msg);

        echo "<FONT COLOR='BLUE' size='2'><B>$m</B></FONT><BR>\n" ;
}

/******************************/

function msdbMsg($msg)
{
        global $msdbMsgBuf;
        global $msdbMsgsIsHold;

        if ( $msdbMsgsIsHold )
                $msdbMsgBuf[] = $msg;
        else
                msdbShowMsg($msg);
}

/******************************/

function msdbFlushMsgs()
{
        global $msdbMsgBuf;
        global $msdbMsgsIsHold;

        $msdbMsgsIsHold = false;

        foreach ( $msdbMsgBuf as $msg )
                msdbShowMsg($msg);

        $msdbMsgBuf = array();
}

/******************************/

/*
 * an error messages.
 * anything that is a failure of  any sort not derived from
 * a user action comes through here
 * this can be due to several reasons, like:
 * 
 *		1. A bug in msdb (this is what this is here for).
 *			(php/mysql/webServer software unexpeted rev inconsistecy included)
 *		2. a malfunction in the enviroment. (e.g. database down)
 *		3. a user-created or modified URL
 */

function msdbError($msg)
{

        msdbMsg($msg);
        msdbLogError($msg);
}

/************************************************************/

 // as of php 4.1 can use $_REQUEST and do away with GET/POST

function msdbGetPost($name)
{
        if ( isset($_REQUEST[$name]) )
                $val = $_REQUEST[$name];
        else if ( isset($_GET[$name]) )
                $val = $_GET[$name];
        else if ( isset($_POST[$name]) )
                $val = $_POST[$name];
        else
                return(null);

        // remove all backslashes from input put in by web browser and/or server
        // you have to pass 2 bs's to str_replace, so it shows up as 4 here
        $ret = str_replace("\\\\", "", $val);
        return($ret);
}

/************************************************************/
/*
 * not to be confused with phph's include
 * this is more of:
 *  get the file,
 *	parse the $variables,
 * the rest is HTML,
 * toss it out to the output stream
 *
 * $var is an array of this to replace, as if created with
 *		array=('user' => 'Ohad', ...)
 *
 */
/******************************/
/*
 * msdbStrParse() is not concious of the $ signs
 * so if msdbSID appears before msdbSIDST
 * in the substitutions array
 * then msdbSIDST translates to 5ST when msdbSID = 5
 */

function msdbStrParse($str, $vars)
{
        $ret = $str ;

        foreach ( $vars as $name => $value ) {
                $rIn = $ret;
                $rSearch = "\$$name" ;
                $rReplace = $value ;
                /*	echo "rIn = <$rIn><BR>\nrSearch=<$rSearch><BR>\nrReplace=<$rReplace><BR>\n" ;	*/
                $rt = str_replace($rSearch, $rReplace, $rIn);
                /*	echo "Now at ".__LINE__." ret = <$ret>\n" ;	*/
                if ( isset($rt) )
                        $ret = $rt;

        }
        return($ret);
}

/********************/
function reverseStrlenCmp($s1, $s2) { return(strlen($s2) - strlen($s1)); }
/********************/
/*
 * msdbInclude() is a simple and effectve template system.
 * template can contain $varname, nothing else
 * the scope is a set of globals (see below)
 * and an explicitely passed array of name=value pairs.
 */

function msdbInclude($fname, $vars = null)
{
        global $msdbEnterVar;
        global $dbMeta;
        global $msdbVersion;


        if ( ( $myHome = msdbMyHome()) )
                $fpath = "$myHome/$fname" ;
        else
                $fpath = $fname ;

        if ( ( $myHomeUrl = msdbMyHomeUrl()) ) {
                $msdbMyHomeUrl = $myHomeUrl;
                $iconDir = $myHomeUrl.'icons' ;
                $jsDir = $myHomeUrl.'JSlib' ;
        } else {
                $msdbMyHomeUrl = '';
                $iconDir = 'icons' ;
                $jsDir = 'JSlib' ;
        }

        $std = array (
                        /*	'msdbMyHomeUrl' => $msdbMyHomeUrl,	*/
                        'msdbDB' => $dbMeta->DB,
                        'msdbTNAME' => $dbMeta->tname,
                        'msdb_t0' => sprintf("%d", time()),
                        'DATE' => date("F j, Y, g:i a"),
                        'msdbSIDST' => $msdbEnterVar->started,
                        'msdbSID' => $msdbEnterVar->sid,
                        'msdbPkName' => $dbMeta->primaryKey,
                        'msdbVersion' => $msdbVersion,
                        'iconDir' => $iconDir,
                        'jsDir' => $jsDir,
                );


        if ( isset($vars) )
                $varList = array_merge($vars, $std);
        else
                $varList = $std ;

        if ( ! is_readable($fpath) ) {
                msdbMsg("msdbInclude: Can not read file: $fpath");
                return(false);
        }

        if ( ! ($f = file($fpath)) ) {
                msdbError("util.php".": ". 265 .": ".("msdbInclude: php Can not read file: $fpath"));
                return(false);
        }

        /*	msdb_r($varList);	*/

        // parser ?!
        // sort by key length, descending to prevent $ohad from replacing $ohadaloni
        uksort($varList, "reverseStrlenCmp");

        foreach ( $f as $line ) {
                $parsed = msdbStrParse($line, $varList);
                $out = msdbStrParse($parsed, $_GET); // only what was not parsed already is allowed to use _GET
                echo "$out";
        }
        return(true);
}

/************************************************************/

// msdbRound is for reports, so they don't clutter with data coming
// pourly rounded from mysql
// $str is a string that may represt an integer, or a floating pint.
// in the case of a floating point, it might need some rounding
// according to msdbConfig rounding value

function msdbRound($str)
{
        global $msdbConfig;

        if ( ($decimal = strstr($str, '.')) == null )
                return($str);

        $roundBy = $msdbConfig['statsFloatRound'];

        if ( strlen($decimal) <= $roundBy + 1 )
                return($str);

        // now go numeric
        $ret = (float)$str;
        $rnd = pow(10, $roundBy);
        $ret *= $rnd ;
        $ret += ( $ret > 0 ) ? 0.5 : -0.5 ;
        $ret = (int)$ret;
        $ret /= $rnd ;
        $ret = (string)$ret;
        return($ret);
}

/************************************************************/

function msdbRoundTest()
{
        $a = array( '2', '4.5', '-3.77777', '3.77777', '5.99999567' );

        foreach ( $a as $f ) {
                $rf = msdbRound($f);
                msdbMsg("::$f:: => ::$rf::");
        }
}

/************************************************************/

function msdbRemoteIp()
{
        global $_SERVER;

        $rh = @$_SERVER["REMOTE_HOST"];
        $ra = @$_SERVER["REMOTE_ADDR"];
        $xff = @$_SERVER['HTTP_X_FORWARDED_FOR'];

        if ( $xff )
                $ip=$xff;
        else if ( $rh )
                $ip = $rh;
        else if ( $ra )
                $ip = $ra;
        else
                return(null);

        if ( $ip == '-' )
                return(null);

        return($ip);
}

/************************************************************/

function msdbMyHomeUrl()
{
        global $msdbConfig;
        global $_SERVER;

        if ( isset($msdbConfig['myHomeUrl']) ) {
                $ret = $msdbConfig['myHomeUrl'];
                if ( $ret[strlen($ret)-1] != '/' )
                        return("$ret/");
                return($ret);
        }

        /*	$ret = "/var/www/html/msdb/" ;	*/
        /*	if ( ( is_dir($ret) || is_link($ret)  ) && is_readable($ret) )	*/
                /*	return('msdb/');	*/

        return(null);
}

/******************************/

function msdbMyHome()
{
        global $msdbConfig;
        global $_SERVER;

        if ( isset($msdbConfig['myHome']) )
                return($msdbConfig['myHome']);

        /*	$ret = "/usr/local/msdb" ;	*/
        /*	if ( ( is_dir($ret) || is_link($ret)  ) && is_readable($ret) )	*/
                /*	return($ret);	*/

        return(null);
}

/************************************************************/

// like file() but without the newlines.

function msdbFile($fname)
{
        if ( ! ( $c =file_get_contents($fname) ) )
                return(null);
        $ret = split("\n", $c);
        array_pop($ret);
        return($ret);
}

/************************************************************/
?>
