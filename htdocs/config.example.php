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

if ( !defined( "PHPEQUIMON" ) )
	die;

///////////////////////////////////////////
//// Adjust values below to your needs ////
///////////////////////////////////////////

// Location of the database dir
// This must be an arbitrary directory with rwx rights for your webserver. If using SQLite, you should place the
// database file there and call it "database". This ensures that phpEquiMon's automatic backump dumps work.
$CONF[ "dbdir" ] = "../path/to/dir/with/write/access/...";

// PDO string to connect to database
// For SQLite 3.x: sqlite:/path/to/file + don't set pdouser/pdopass (i.e. "sqlite:/path/to/dbdir/database" )
// For SQLite 2.x: sqlite2:... (but please consider using SQLite 3.x instead)
// For MySQL: mysql:host=xxx;port=xxx;dbname=xxx + set pdouser/pdopass
// If you want to use another database, look in the PHP manual for information about how to build your PDO string
$CONF[ "pdostring" ] = "See above for example";

// The next two settings are only needed for databases that require an username and a password. You MUST remove/comment
// them if you want to use SQLite.
$CONF[ "pdouser" ] = "Comment out";
$CONF[ "pdopass" ] = "for SQLite";

// Do NOT change the following line
$CONF[ "release" ] = "1";
