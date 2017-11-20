<HTML>
<HEAD><TITLE>Cash Book Balance - $cbTable</TITLE></HEAD>

<LINK REL="STYLESHEET" TYPE="text/css" HREF="Third/msdb/JSlib/dialogstyle.css"></LINK>
<LINK REL="STYLESHEET" TYPE="text/css" HREF="JSlib/cb.css"></LINK>

<SCRIPT LANGUAGE="JavaScript" SRC="Third/msdb/JSlib/msdb.js"></SCRIPT>
<SCRIPT LANGUAGE="JavaScript" SRC="Third/msdb/JSlib/dialog.js"></SCRIPT>
<SCRIPT LANGUAGE="JavaScript" SRC="JSlib/cb.js"></SCRIPT>

<SCRIPT LANGUAGE="javascript">
	var cb = new theCB('$cbTable', '$restrFname', '$restrVal') ;
	var msdbTop = new msdb('', '', '', '', '', '');
</SCRIPT>
<BODY>


<A HREF="javascript:cbNoOp()">$cbTable</A> $describe<BR>
