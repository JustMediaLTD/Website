<?php
/************************************************************/
#include "msdb.h"
$rcsid='$Id: url.php,v 1.1 2004/05/31 14:56:42 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/
// url utilities
/************************************************************/

// make a url more valid
// non http[s] and relative urls are not treated with respect

function msdbUrlFix($raw)
{
	$parsed = parse_url($raw);

	if ( ! isset($parsed['host']) ) {
		$take2 = "http://$raw" ;
		$parsed = parse_url($take2);
	}

	if ( ! isset($parsed['scheme']) )
		$parsed['scheme'] = "http";

	$http = $parsed['scheme'];

	$url = "$http://";

	if ( ! isset($parsed['host']) ) {
		msdbMsg("$raw: No Host");
		msdb_r($parsed);
		return(null);
	}

	$url .= $parsed['host'];

	if ( isset($parsed['port']) ) {
		$port = $parsed['port'];
		$url = ":$port";
	}
	
	if ( isset($parsed['path']) )
		$url .= $parsed['path'];
	else
		$url .= '/' ;
	
	if ( isset($parsed['query']) ) {
		$query = $parsed['query'];
		$url = "?$query";
	}
	
	if ( isset($parsed['fragment']) ) {
		$fragment = $parsed['fragment'];
		$url = "#$fragment";
	}
	
	return($url);
}

/******************************/

function msdbGetUrl($url, $postData = null)
{

	if ( ($fixedUrl = msdbUrlFix($url)) == null )
		return(null);

	$parsedUrl = parse_url($fixedUrl);

	$host = $parsedUrl['host'];
	if ( isset($parsedUrl['port'] ) )
		$port = $parsedUrl['port'];
	else
		$port = 80;

	$h = strstr($fixedUrl, $host);
	$uri = strstr($h, "/");
	if ( ! $uri )
		$uri = "/" ;

	if ( $postData ) {
		$method = "POST";
		$inArr = array();
		foreach(array_keys($postData) as $i)
			$inArr[] = $i."=".urlencode($postData[$i]);

		$postStr = implode('&',$inArr);
		$postData = "$postStr\n";
		$ContentLength = strlen($postStr);
		$postHeaders =	"Content-Type: application/x-www-form-urlencoded\n".
						"Content-Length: $ContentLength\n";
						/*	"host: $host\n".	*/
						/*	"User-Agent: msdb Api\n".	*/
	} else {
		$method = "GET";
		$postHeaders = '';
		$postData = '';
	}

	$istream = "$method $uri HTTP/1.1\n$postHeaders\n$postData";

	/*	msdb_r("http://$host:$port/<BR>:::$istream:::");	*/

	$output = array();

	// Open the connection to the host
	if ( ! ($socket = fsockopen($host, $port, $errno, $errstr, 1)) ) {
		msdbMsg("msdbUrlPostTo: Can not fsockopen($host, $port)<BR>$errstr");
		return(null);
	}

	fputs($socket, $istream);

	$output = array();
	$startCollecting = false ;
	while (!feof($socket)) {
		// skip returned headers
		$s = fgets($socket, 65536);
		if ( $startCollecting ) {
			$output[] = $s;
			continue;
		}
		if ( $s == "\n" )
			$startCollecting = true ; // from the next line
	}

	fclose($socket);

	$ret = implode("\n", $output);

	return($ret);
}

/************************************************************/
?>
