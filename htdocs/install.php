<?php

/* phpEquiMon (C) 2007 by Christian Mandery

This file is part of phpEquiMon.

phpEquiMon is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

phpEquiMon is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>. */

define( "PHPEQUIMON", true );
require "config.php";

?>

<html>
<head>
<title>phpEquiMon Installation</title>
</head>
<body>
<h1>phpEquiMon Installation Script</h1>

<?php

$dbdir_lastchar = substr( $CONF[ "dbdir" ], -1 );
if ( $dbdir_lastchar != "/" && $dbdir_lastchar != "\\" )
	$CONF[ "dbdir" ] .= "/";

echo "Database directory: " . $CONF[ "dbdir" ] . "<br />\n";
echo "Database connect string: " . $CONF[ "pdostring" ] . "<br /><br />\n";

echo "PHP feature check...<br />\n";

if ( !function_exists( "ctype_digit" ) )
{
	echo "You have ctype disabled. phpEquiMon has its own workaround but it is generally recommended to add or " .
		"enable ctype support.<br />\n";
}

if ( get_magic_quotes_gpc() )
{
	echo "PHP Magic Quoting is enabled. There is no workaround for this in phpEquiMon yet. The installation " .
		"continue but you cannot start using phpEquiMon before disabling this \"feature\".<br />\n";
}

echo "<br />Checking permissions on dbdir...<br />\n";

if ( !touch( $CONF[ "dbdir" ] . "perm_test" ) )
	die( "Script user must have rwx permissions on dbdir!" );

unlink( $CONF[ "dbdir" ] . "perm_test" );

echo "Opening database file...<br />\n";

if ( isset( $CONF[ "pdouser" ] ) && isset( $CONF[ "pdopass" ] ) )
	$db = new PDO( $CONF[ "pdostring" ], $CONF[ "pdouser" ], $CONF[ "pdopass" ] );
else
	$db = new PDO( $CONF[ "pdostring" ] );

$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

// Ugly
if ( substr( $CONF[ "pdostring" ], 0, 6 ) == "sqlite" )
	$idstring = "INTEGER PRIMARY KEY NOT NULL";
else
	$idstring = "INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT";

echo "Dropping existing phpEquiMon tables in the database...<br />\n";

$db->exec( "DROP TABLE IF EXISTS settings;" );
$db->exec( "DROP TABLE IF EXISTS machines;" );
$db->exec( "DROP TABLE IF EXISTS events;" );
$db->exec( "DROP TABLE IF EXISTS authusers;" );
$db->exec( "DROP TABLE IF EXISTS authgroups;" );
$db->exec( "DROP TABLE IF EXISTS authgroupmembers;" );
$db->exec( "DROP TABLE IF EXISTS pxeserver;" );
$db->exec( "DROP TABLE IF EXISTS kernels;" );
$db->exec( "DROP TABLE IF EXISTS kernelparameters;" );
$db->exec( "DROP TABLE IF EXISTS nicvendors;" );

echo "Creating database tables...<br />\n";

$db->exec( "CREATE TABLE settings( keyname VARCHAR( 20 ) PRIMARY KEY NOT NULL, value TEXT );" );

$db->exec( "CREATE TABLE machines( id $idstring, " .
	"hostname VARCHAR( 20 ) UNIQUE NOT NULL, " .
	"groupid INTEGER NOT NULL, " .
	"lastupdate INTEGER, " .
	"updateby INTEGER, " .
	"vendor VARCHAR( 20 ), " .
	"model VARCHAR( 40 ), " .
	"state INTEGER, " .
	"arch VARCHAR( 10 ), " .
	"assettag VARCHAR( 40 ), " .
	"expiredate DATE, " .
	"expirestate INTEGER NOT NULL, " .
	"ip VARCHAR( 20 ), " .
	"mac VARCHAR( 20 ), " .
	"monfiles_data TEXT, " .
	"room VARCHAR( 20 ), " .
	"cpu VARCHAR( 20 ), " .
	"os VARCHAR( 20 ), " .
	"mem VARCHAR( 20 ), " .
	"disk VARCHAR( 20 ), " .
	"kernel VARCHAR( 20 ), " .
	"libc VARCHAR( 20 ), " .
	"compiler VARCHAR( 20 ), " .
	"usedby VARCHAR( 40 ), " .
	"usedby_id1 INTEGER, " .
	"usedby_id2 INTEGER, " .
	"notes TEXT, " .
	"rack INTEGER NOT NULL, " .
	"hostsystem VARCHAR( 20 ), " .
	"lastping INTEGER, " .
	"mailtarget VARCHAR( 40 ), " .
	"mailopts INTEGER NOT NULL, " .
	"wbem_info TEXT, " .
	"wbem_lastupdate INTEGER, " .
	"remoteadm INTEGER );" );

$db->exec( "CREATE TABLE events( id $idstring, " .
	"user INTEGER NOT NULL, " .
	"time INTEGER NOT NULL, " .
	"content TEXT NOT NULL );" );

$db->exec( "CREATE TABLE authusers( id $idstring, " .
	"username VARCHAR( 20 ) UNIQUE NOT NULL, " .
	"password VARCHAR( 40 ), " .
	"lastuse INTEGER, " .
	"contact TEXT, " .
	"settings TEXT );" );

$db->exec( "CREATE TABLE authgroups( id $idstring, " .
	"groupname VARCHAR( 20 ) UNIQUE NOT NULL );" );

$db->exec( "CREATE TABLE authgroupmembers( userid INTEGER, groupid INTEGER );" );

$db->exec( "CREATE TABLE pxeserver( id $idstring, " .
	"address VARCHAR( 40 ) UNIQUE NOT NULL, " .
	"iprange VARCHAR( 17 ) UNIQUE NOT NULL );" );

$db->exec( "CREATE TABLE kernels( id $idstring, " .
	"filename VARCHAR( 40 ) UNIQUE NOT NULL, " .
	"type INTEGER NOT NULL, " .
	"description TEXT NOT NULL );" );

$db->exec( "CREATE TABLE kernelparameters( id $idstring, " .
	"kernel INTEGER NOT NULL, " .
	"paramline TEXT NOT NULL );" );

$db->exec( "CREATE TABLE nicvendors( macstart VARCHAR( 8 ) UNIQUE NOT NULL, vendor VARCHAR( 40 ) NOT NULL );" );

echo "Filling tables with default values...<br />\n";

$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'dbversion', '". $CONF[ "release" ] . "' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'installation_name', 'phpEquiMon' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'installation_url', 'http://localhost/' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'admin_mail', 'root@localhost' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'ldap_server', '' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'ldap_basedn', '' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'cron_interval', '300' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'vendors', '' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'architectures', 'i386,x86_64,non-server' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'rooms', '' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'prog_fping', '/usr/sbin/fping' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'prog_wbemcli', '/usr/bin/wbemcli' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'prog_sqlite', '/usr/bin/sqlite3' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'enable_map', 'no' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'fh_prefix', '' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'fh_start', '1' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'monfiles_dir', '' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'monfiles_maxage', '10' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'wol_addr', '' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'wol_port', '9' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'wbem_user', '' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'wbem_password', '' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'enable_html', 'no' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'enable_autologin', 'no' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'pxe_cert', '' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'pxe_key', '' );" );
$db->exec( "INSERT INTO settings( keyname, value ) VALUES( 'pxe_ca', '' );" );

$db->exec( "INSERT INTO events( user, time, content ) VALUES( 0, " . time() . ", 'phpEquiMon installed.' );" );

$db->exec( "INSERT INTO authusers( id, username, password ) VALUES( 1, 'admin', '\$1\$uC1er3WT\$WWDKxP5zfplPQS7omlQ5l/'" .
	");" );

$db->exec( "INSERT INTO authgroups( id, groupname ) VALUES( 1, 'admin' );" );

$db->exec( "INSERT INTO authgroupmembers( userid, groupid ) VALUES( 1, 1 );" );

echo "Creating directory for backup files...<br />\n";

if ( !file_exists( $CONF[ "dbdir" ] . "backups" ) )
	mkdir( $CONF[ "dbdir" ] . "backups" );

?>

<p>Installation script finished. You should now consider deleting install.php in an untrusted environment for security
reasons <b>after checking that phpEquiMon DOES work</b>. If you encounter problems, remember to check database and file
permissions, especially on the database directory and the SQLite database file (if used).</p>

<p>Default master login for phpEquiMon: <b>admin/admin.</b></p>

<p>You should now log in and change settings in the Admin Panel to adjust phpEquiMon to your needs.</p>

</body>
</html>
