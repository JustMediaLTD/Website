<?php
/************************************************************/



$rcsid='$Id: logon.php,v 1.40 2004/07/29 08:48:04 engine Exp engine $ ';
$copyRight="Copyright (c) Ohad Aloni 1990-2004. All rights reserved.";
$licenseId="Released under http://ohad.dyndns.org/license.txt (BSD)";
/************************************************************/
/*
 * you can enter with a user name
 * and password
 * or you can enter with a valid sid:
 * it must be fresh enough, and passworded with a started timestamp
 * that will match its DB counterpart
 */
/************************************************************/
// must reuse the session started value if the lasthit
// is still valid
// or else multiple windows will overwrite each other's values
// multiple users logging in with the same passwd info counts as multiple windows as well
// and may be common in some installations
//
// this means that too many users with the same name reduce secuirty by de-facto
// stretching session non-idle times, except when the following:
//
// multiple users use the same account logging out will force the other users
// to log on.

function msdbSidStart()
{
        global $msdbEnterVar;
        global $msdbConfig;

        $controlDB = $msdbConfig['DB_NAME'];

        $now = time();

        $pwent = & $msdbEnterVar->pwent;
        $uid = $pwent['userid'] ;
        $lasthit = $pwent['lasthit'] ;

        // lasthit == 0 means not logged on
        // the arithmetic should be the same but check anyway
        // might just save some performance or SID_EXP might be set to something huge.
        if ( $lasthit == 0 || ( $now - $lasthit ) > $msdbConfig['SID_EXP'] ) {
                $q = "update $controlDB.msdb_passwd set started = $now, lasthit = $now where userid = $uid" ;
                $msdbEnterVar->started = $now ;
        }
        else {
                $q = "update $controlDB.msdb_passwd set lasthit = $now where userid = $uid" ;
                $msdbEnterVar->started = $pwent['started'] ;
        }

        $msdbEnterVar->sid = $uid ;
        $msdbEnterVar->pwent['lasthit'] = $now ;

        // if two concurrent users hit this with the same now value
        // update only needs to occur once and msdbSql might return 0
        if ( msDbSql($q) < 0 ) {
                msdbError("logon.php".": ". 59 .": ".("msdbSidStart: '$q' failed"));
                return(false);
        }
        return(true);
}

/******************************/

function msdbSidContinue()
{
        global $msdbEnterVar;
        global $msdbConfig;

        msdbPerf("logon.php", 72, (''));
        $controlDB = $msdbConfig['DB_NAME'];
        msdbPerf("logon.php", 74, (''));

        $t = time();
        $uid = $msdbEnterVar->pwent['userid'] ;

        $q = "update $controlDB.msdb_passwd set lasthit = $t where userid = $uid" ;

        msdbPerf("logon.php", 81, (''));
        if ( msDbSql($q) < 0 ) {
                msdbError("logon.php".": ". 83 .": ".("msdbSidContinue: '$q' failed"));
                return(false);
        }
        msdbPerf("logon.php", 86, (''));
        return(true);
}

/******************************/

function msdbEnterByPw()
{
        global $msdbEnterVar ;
        global $msdbConfig;

        $controlDB = $msdbConfig['DB_NAME'];

        if ( ! $msdbEnterVar->passwd ) {
                msdbMsg("msdbEnterByPw User but no Password");
                return(false);
        }
        $w = "where name = '$msdbEnterVar->name' and passwd = '$msdbEnterVar->passwd'" ;

        if ( ! ( $msdbEnterVar->pwent =
                        msDbGet1Assoc("select * from $controlDB.msdb_passwd $w") ) ) {
                msdbMsg("Login Failed");
                return(false);
        }

        return(msdbSidStart());


}

/******************************/

function msdbEnterBySid()
{
        global $msdbConfig;
        global $msdbEnterVar ;
        global $msdbConfig;

        $controlDB = $msdbConfig['DB_NAME'];

        $now = time();

        if ( ! $msdbEnterVar->started ) {
                msdbError("logon.php".": ". 129 .": ".("msdbEnterBySid: sid but not started"));
                return(false);
        }
        $w = "where userid = $msdbEnterVar->sid" ;
        if ( ! ( $msdbEnterVar->pwent =
                        msDbGet1Assoc("select * from $controlDB.msdb_passwd $w") ) ) {
                msdbMsg("Bad Session Sid");
                return(false);
        }

        $lasthit = $msdbEnterVar->pwent['lasthit'] ;

        if ( $lasthit == 0 ) {
                $uname = $msdbEnterVar->pwent['name'];
                msdbMsg("$uname Not Logged On");
                return(false);
        }

        if ( $msdbEnterVar->started != $msdbEnterVar->pwent['started'] ) {
                // this is an expired session after which somebody else
                // with the same username, or another window with the same user
                // has logged on with a passwd
                // thereby resetting the session-started value
                // (or else the url was tampered with)
                msdbMsg("Session Expired.");
                return(false);
        }


        if ( ( $now - $lasthit ) > $msdbConfig['SID_EXP'] ) {
                /*	msdbMsg("Session Expired ($now too long after $lasthit)");	*/
                // keep the message different
                msdbMsg("This Session Has Expired (Idle Time Too Long)");
                return(false);
        }

        return(msdbSidContinue());

}

/******************************/

class msdbEnterClass
{
        function msdbEnterClass()
        {

                $this->name = null;
                $this->passwd = null;
                $this->sid = null;
                $this->started = null;
                $this->ip = null;
                $this->pwent = null; // database entry for this user
        }
}

/**********/

function msdbEnter()
{
        global $msdbEnterVar ;
        global $dbMeta ;
        global $msdbConfig;

        $controlDB = $msdbConfig['DB_NAME'];

        $msdbEnterVar = new msdbEnterClass();

        $msdbEnterVar->name = msdbGetPost('msdbUSER');
        $msdbEnterVar->passwd = msdbGetPost('msdbPW');
        $msdbEnterVar->sid = msdbGetPost('msdbSID');
        $msdbEnterVar->started = msdbGetPost('msdbSIDST');
        $msdbEnterVar->ip = msdbRemoteIp();

        if ( $dbMeta->ea == 'msdbLogout' ) {
                // must enter first in order to touch the table
                if ( ! msdbEnterBySid() )
                        return(false);
                $sid = $msdbEnterVar->sid;
                $sql = "update $controlDB.msdb_passwd set started = 0, lasthit = 0 where userid = $sid";
                msdbSql($sql);
                return(false);
        }

        if ( $msdbEnterVar->name )
                return(msdbEnterByPw());

        if ($msdbEnterVar->sid )
                return(msdbEnterBySid());


        if ( msDbIsOnlyDB() ) {
                $msdbEnterVar->name = 'defaultUser' ;
                $msdbEnterVar->passwd = 'defaultPasswd' ;
                return(msdbEnterByPw());
        }

        msdbMsg("No User nor session information (with ONLY_DB=false)");

        return(false);
}


/************************************************************/
?>
