<?php
/************************************************************/



$rcsid='$Id: permit.php,v 1.18 2004/07/29 08:48:06 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/
$permBitNames = array(0 => 'Unknown Action', 1 => 'select', 2 => 'insert', 4 => 'Update', 8 => 'delete');
/************************************************************/

function msdbEaBit()
{
        global $dbMeta;

        $ea = $dbMeta->ea;

        if ( ! $ea || $dbMeta->isSearch )
                return(1);

        if ( $ea == 'msdbGenCrtable' || $ea == 'msdbTest' )
                return(1);

        // ???
        if ( $ea == 'msdbSelectDB' || $ea == 'msdbSelectTable' )
                return(1);

        // msdbInsert is also an explicit user search coming in.
        // having checked isSearch not being set, this is safe:
        if ( $ea == 'msdbInsert' )
                return(2);

        if ( $ea == 'msdbUpdate' )
                return(4);

        if ( $ea == 'msdbDelete' )
                return(8);

        msdbError("permit.php".": ". 38 .": ".("msdbEaBit: msdbEA=$ea Unknown msdbEA"));

        return(0);
}

/******************************/

function msdbGroupOf($user)
{
        global $msdbConfig;

        $controlDB = $msdbConfig['DB_NAME'];

        return(msDbGetString("select groupid from $controlDB.msdb_passwd where name = '$user'"));
}

/******************************/

function msdbPermit()
{
        global $dbMeta;
        global $dbPermit;
        global $permBitNames;
        global $msdbEnterVar;
        global $msdbConfig;

        $tname = $dbMeta->tname ;
        $pwent = $msdbEnterVar->pwent;
        $roMode = $msdbConfig['READ_ONLY_MODE'] ;

        $eaBit = msdbEaBit();

        // if there is no table the operation is permitted
        if ( ! $tname )
                return(true);

        $tperms = msDbGet1Assoc("select * from msdb_permit where tname = '$tname'");

        if ( $roMode != true && $tperms == null )
                return(true);

        if ( $roMode && $eaBit > 1 ) {
                msdbMsg("Read Only Mode. Sorry.");
                return(false);
        }

        if ( is_null($tperms) )
                $tbits = 1 ; // read only mode, 1 is the read bit
        else if ( $pwent['name'] == $tperms['owner'] )
                $tbits = $tperms['puser'] ;
        else if ( $pwent['groupid'] == msdbGroupOf($tperms['owner']) )
                $tbits = $tperms['pgroup'] ;
        else
                $tbits = $tperms['pother'] ;


        $perm = ( ($tbits & $eaBit) != 0 );

        if ($perm)
                return(true);

        // no permission
        $eaName = $permBitNames[$eaBit];

        $dbname = $dbMeta->DB;

        $u = $pwent['name'];
        msdbMsg("$u does not have $eaName access to table $tname in database $dbname. Sorry.");

        $dbMeta->noPerm = true;

        return(false);
}

/************************************************************/
?>
