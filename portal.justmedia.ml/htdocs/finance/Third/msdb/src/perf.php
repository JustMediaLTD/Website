<?php
/************************************************************/
$rcsid='$Id: perf.php,v 1.3 2004/01/29 08:25:53 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/

class msdbPerfStamp
{
	function msdbPerfStamp($file, $line, $comment)
	{
		$this->file = $file;
		$this->line = $line;
		$this->comment = $comment;

		$this->mt = microtime();

		list($usec, $sec) = explode(" ", $this->mt); 

		$this->sec = $sec ;
		$this->usec = (int)(1000000 * $usec);
	}
}



/******************************/

function msdbPerf($file, $line, $comment)
{
	global $msdbPerfStamps;

	$msdbPerfStamps[] = new msdbPerfStamp($file, $line, $comment);
}

/************************************************************/

function msdbPerfDiff($t1, $t2)
{
	$usec = $t1->usec - $t2->usec ;
	$sec = $t1->sec - $t2->sec;

	return($sec * 1000000 + $usec);

}

/******************************/

function msdbPerfLine($i)
{
	global $msdbPerfStamps;

	$stamp = & $msdbPerfStamps[$i] ;

	if ( $i == 0 )
		$ms = 0;
	else
		$ms = msdbPerfDiff(& $stamp, & $msdbPerfStamps[$i-1]);

	$showMs = number_format($ms);

	$title = $stamp->file.": ".$stamp->line.": ".$stamp->comment ;

	$vars = array(
		'msdbPerfName' => $title,
		'msdbPerfMicrosecs' => $showMs
		);

	msdbInclude('include/perf.b', $vars);
}

/******************************/

function msdbShowPerf()
{
	
	global $msdbPerfStamps;

	$n = count($msdbPerfStamps);

	msdbInclude('include/perf.h');

	for($i=0;$i<$n;$i++)
		msdbPerfLine($i);

	$total = msdbPerfDiff(& $msdbPerfStamps[$n-1], & $msdbPerfStamps[0]);
	$showTotal = number_format($total);

	msdbInclude('include/perf.t', array('msdbPerfTotal' => $showTotal) );
}

/************************************************************/
?>
