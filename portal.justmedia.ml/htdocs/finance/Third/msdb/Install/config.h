
<PRE>


Configuration Help
------------------

It is possible that the configuration of My SQL Data Browser was not performed
or is incorrect. If so, here are some instructions:

Edit the file

	msdbConfig.php


Set the mysql User and Password.


$msdbConfig['DB_USER'] = 'msdb' ;
$msdbConfig['DB_PW'] = 'msdb' ;


Set the name of the primary database you are browsing.

$msdbConfig['DB_NAME'] = 'msdb' ;

this database must exist, and presumably there are some tables there to browse.

</PRE>
