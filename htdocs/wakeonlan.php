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

/**
 * Sends the magic packet for Wake On LAN.
 */
function pageWakeOnLAN()
{
	global $backend, $session;
	
	// Read config settings
	$woladdr = $backend->readConfig( "wol_addr" );
	$wolport = $backend->readConfig( "wol_port" );
	
	// WOL setup correct?
	if ( !$woladdr || !$wolport )
	{
		echo "<p class=\"err\">Wake on LAN not configured.</p>\n";
		return;
	}
	
	// ID given?
	if ( isset( $_GET[ "i" ] ) && helperIsDigit( $_GET[ "i" ] ) )
		$id = $_GET[ "i" ];
	else
	{
		echo "<p class=\"err\">Missing ID</>\n";
		return;
	}
	
	// Check authentification
	if ( !$backend->checkEditAuth( $id ) )
	{
		echo "<p class=\"err\">Wake On LAN denied.</p>\n";
		return;
	}
	
	// Fetch record from database
	$entry = $backend->getList( $id );
	if ( !$entry )
	{
		echo "<p class=\"err\">Record $id not found in database.</p>\n";
		return;
	}
	
	// Check that we have got a MAC address to generate the packet
	if ( !helperIsMAC( $entry[ "mac" ] ) )
	{
		echo "<p class=\"err\">Entry has not set a valid MAC address.</p>";
		return;
	}
	
	// Generate wakeup packet
	$pkg = "\xFF\xFF\xFF\xFF\xFF\xFF";
	$mac = str_replace( ":", "", $entry[ "mac" ] );
	$hexmac = "";
	
	for ( $i = 0; $i < 6; ++$i )
		$hexmac .= chr( hexdec( substr( $mac, $i * 2, 2 ) ) );
	
	for ( $i = 0; $i < 16; ++$i )
		$pkg .= $hexmac;
	
	// Open socket
	$socket = fsockopen( "udp://" . $woladdr, $wolport, $err1, $err2, 2 );
	if ( !$socket )
	{
		echo "<p class=\"err\">Could not open socket: $err1 / $err2</p>\n";
		return;
	}
	
	// Send packet
	if ( !fwrite( $socket, $pkg ) )
	{
		fclose( $socket );
		echo "<p class=\"err\">Could not write on socket object.</p>\n";
		
		return;
	}
	
	// Cleanup
	fclose( $socket );
	
	// Write entry to event log
	$backend->logEvent( $session->getID(), "Wake On LAN for " . helperEncodeHTML( $entry[ "hostname" ] ) . " (" .
		helperEncodeHTML( $entry[ "mac" ] ) . ")." );
	
	echo "<p class=\"infobox\">Wake On LAN successful. If correctly configured, " . helperEncodeHTML( $entry[
		"hostname" ] ) . " should now boot up.</p>\n";
	
	// Output normal index page
	pageOutputList();
}

?>
