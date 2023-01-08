#!/usr/bin/php

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

// Helper script for PXE/DHCP servers

// Do not change the next lines - configuration is below!

require( "pxehelper_conf.php" );

if ( $_SERVER[ "argc" ] != 2 )
	die( "Syntax: pxehelper_wa.php <tftpcfgfile>\n" );

$tftpcfgfile = $_SERVER[ "argv" ][ 1 ];

$CONF[ "logregexp" ] = "/Serving " . str_replace( "/", "\/", $tftpcfgfile ) . "/";

// DO NOT CHANGE ANYTHING BEYOND THIS LINE

set_time_limit( 0 );

$finished = false;
$elapsedtime = 0;

$f = fopen( $CONF[ "logfile" ], "r" );
if ( !$f )
	die( "Could not open logfile!\n" );

if ( fseek( $f, 0, SEEK_END ) == -1 )
	die( "fseek failed!\n" );

$pos = ftell( $f );
fclose( $f );

echo "Waiting for target host to boot... ";

do
{	
	$f = fopen( $CONF[ "logfile" ], "r" );
	if ( !$f )
		die( "Could not open logfile!\n" );
	
	if ( fseek( $f, $pos ) == -1 )
		die( "fseek failed!\n" );
	
	while ( !feof( $f ) )
	{
		$line = fgets( $f );
		if ( preg_match( $CONF[ "logregexp" ], $line ) )
			$finished = true;
	}
	
	$pos = ftell( $f );
	fclose( $f );
	
	if ( !file_exists( $tftpcfgfile ) )
	{
		echo "Config file not found!\n";
		die;
	}
	
	sleep( $CONF[ "sleeptime" ] );
	$elapsedtime += $CONF[ "sleeptime" ];
} while ( !$finished && $elapsedtime < $CONF[ "maxwait" ] );

if ( $finished )
{
	echo "Waiting another " . $CONF[ "renamedelay" ] . " seconds... ";
	
	sleep( $CONF[ "renamedelay" ] );
	
	if ( rename( $tftpcfgfile, $tftpcfgfile . ".inactive" ) )
		echo "Done.\n";
	else
		echo "Renaming failed!\n";
}
else
	echo "Timeout.\n";

?>
