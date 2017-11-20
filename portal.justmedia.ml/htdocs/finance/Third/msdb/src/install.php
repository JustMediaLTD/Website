<?php
/************************************************************/
#include "msdb.h"
$rcsid='$Id: install.php,v 1.31 2004/09/02 12:10:57 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/
$insFiles = 	array(
		'Install/msdb_passwd.crtable',
		'Install/msdb_passwd.insert',
		'Install/msdb_permit.crtable',
		);

/******************************/
// return true if it looks like there exists a succeful installation of msdb
//
// can not use msDbIsTable here
// since it caches the list beforehand for spped in 'normal' pages

function msdbVerifyInstall()
{
	global $msdbConfig;

	$controlDB = $msdbConfig['DB_NAME'];
	$ispw = msDbDatabaseHasTable($controlDB, "msdb_passwd");

	$pwnum = 0;
	if ( $ispw )
		$pwnum = msDbRowNum("msdb_passwd");

	$ispr = msDbDatabaseHasTable($controlDB, "msdb_permit");

	if ( $pwnum > 0 && $ispr )
		return(true);
	
	msdbMsg("Installation not succesfull.");

	if ( ! $ispw )
		msdbMsg("msdb_passwd not created");

	if ( ! $ispr )
		msdbMsg("msdb_permit not created");

	if ( $ispw && $ispr ) // only the insert failed
		msdbMsg("msdb_passwd: no rows");

	echo "<BR>Please Report errors by <A HREF=\"mailto:nekko@engine.com?subject=Msdb Install Failed&body=Please include information about software versions: OS, mysql, php, and WEB browser. Also any errors you see on the screen, and any errors reported in the WEB Server error log file. Thanks.\">E-mail</A><BR>\n" ;

	return(false);
}

/******************************/
// do the install
// deliberatly ignore any execution errors
// let it print and later check if the install is OK
// in this way, if the install is permitted in stages by the admin
// there is not too much that can go wrong during multiple attempts

function msdbDoInstall()
{
	global $insFiles;

	$prefix = "";
	if ( ( $myHome = msdbMyHome()) )
		$prefix = "$myHome/" ;

		
	foreach ( $insFiles as $f ) {
		$sql = file_get_contents("$prefix$f");
		msdbMsg("Executing: $sql");
		msDbSql($sql);
	}

	if ( ! msdbVerifyInstall() )
		return(false);


	echo "<BR><BR>\n" ;
	msdBMsg("Installation succesful !!!. Please log on");
	echo "<BR><BR>\n" ;
	
	msdbInclude('include/logon.h', array('msdbUSER' => 'msdbUser') );
}

/******************************/

function msdbShowInstall()
{
	global $insFiles;

	$prefix = "";
	if ( ( $myHome = msdbMyHome()) )
		$prefix = "$myHome/" ;

	foreach ( $insFiles as $f ) {
		echo "<BR><PRE>";
		readfile("$prefix$f");
		echo ";</PRE><BR>";
	}
}

/******************************/

function msdbInstall()
{
	global $dbHandle;
	global $msdbConfig;

	$controlDB = $msdbConfig['DB_NAME'];

	// if configuration was performed
	// should be connected by now.

	if ( ! $dbHandle->connected ) {
		msdbMsg("Connection to the database was not established");
		msdbInclude('Install/config.h');
		return;
	}

	if ( isset($_GET['InstallMsdb']) ) {
		msdbDoInstall();
		return;
	}

	// have user confirm installation

	msdbMsg("Click the link below to confirm installation of Msdb in Database $controlDB");
	echo "<BR><PRE><BR><BR>\n\n";

	msdbShowInstall();

	echo "</PRE><BR>\n";

	$show = "<FONT COLOR=BLUE size=6>Install Msdb</FONT>";

	echo "<CENTER><A HREF=\"?InstallMsdb=\">$show</A></CENTER>\n";
}

/************************************************************/

function msdbInstallOnlyDB()
{
	$prefix = "";
	if ( ( $myHome = msdbMyHome()) )
		$prefix = "$myHome/" ;

	if ( ! msDbConnect() ) {
		msdbMsg("Unable to connect to database.");
		msdbMsg("Is the database name, user name and password set correcty in msdbConfig.php");
		return(false);
	}

	if( ! msDbIsTable('msdb_passwd') ) {
		$sql = file_get_contents($prefix."Install/msdb_passwd.crtable");
		msdbMsg("Executing: $sql");
		msDbSql($sql);
	}

	// userid (and sid) is 1=defaultUser in only_db mode
	if ( msDbGetString("select count(*) from msdb_passwd where userid = 1") != 1 ) {
		$sql = file_get_contents($prefix."Install/msdb_passwd.insert");
		msdbMsg("Executing: $sql");
		msDbSql($sql);
	}
	return(true);
}

/************************************************************/

// check if this is a first page hit to the system
// and needs to perform some admin tasks

function msdbPreInstall()
{
	global $msdbConfig;

	$controlDB = $msdbConfig['DB_NAME'];

	// quickly check for familiar faces

	if (
			isset($_REQUEST['msdbTNAME']) ||
			isset($_REQUEST['msdbSIDST']) ||
			isset($_REQUEST['msdbEA']) ||

			isset($_GET['msdbTNAME']) ||
			isset($_GET['msdbSIDST']) ||
			isset($_GET['msdbEA']) ||

			isset($_POST['msdbTNAME']) ||
			isset($_POST['msdbEA']) ||
			isset($_POST['msdbUSER']) ||
			false
		)
		return(true);
	
	if ( msDbIsOnlyDB() )
		return(msdbInstallOnlyDB());

	msdbHoldMsgs();

	if ( msDbIsTable('msdb_permit')) {
		msdbFlushMsgs();
		return(true);
	}

	// somthing in the installation is wrong

	if ( isset($_GET['InstallMsdb']) )
		$title = "Installing MSDB on $controlDB";
	else
		$title = "My Sql Data Browser: probably not configured";

	msdbInclude('include/doctype.h', null);
	msdbInclude('include/dochead.h', null);
	echo "<TITLE>$title</TITLE>\n";
	/*	msdbInclude('include/jsSrc.h', null);	*/
	echo "</HEAD>\n<BODY>\n\n" ;

	msdbInclude('include/header.h', null);
	msdbShowMenu();
	echo "<BR><BR>\n" ;

	msdbFlushMsgs();


	msdbInstall();

	echo "<BR><BR>\n" ;
	msdbShowMenu();
	msdbInclude('include/tailer.h', null);
	echo "</BODY>\n</HTML>\n" ;
	return(false);
}

/************************************************************/
?>
