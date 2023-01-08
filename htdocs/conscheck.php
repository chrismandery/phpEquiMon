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
 * Performs a consistency check with the database. Basically, the database gathers all the values by itself when asked
 * so via searchForWrongEntries() so this function just looks whether the IPs and MACs match and marks all entries that
 * fail this test in red.
 * @see DatabaseBackend::searchForWrongEntries()
 */
function pageConsistencyCheck()
{
	global $backend, $session;
	
	/**
	 * Outputs a field for the consistency check table.
	 * @param array $dupcount Reference on the dupcount array.
	 * @param string $value Value to print
	 */
	function outputField( &$dupcount, $value )
	{
		if ( $value )
		{
			echo "<td>" . helperEncodeHTML( $value );
			
			if ( $dupcount[ $value ] > 1 )
				echo " <span style=\"color: #880000; font-weight: bold\">(" . $dupcount[ $value ] .
					")</span>";
			
			echo "</td>\n";
		}
		else
			echo "<td>-</td>\n";
	}
	
	/**
	 * Outputs a field for the consistency check table (SOL version).
	 * @param array $dupcount Reference on the dupcount array.
	 * @param array $value Arrays of values to print: network interface => value
	 * @param string $field Name of the field (ip/mac).
	 */
	function outputFieldSOL( &$dupcount, $value, $field )
	{
		if ( $value )
		{
			echo "<td>";
			
			foreach ( $value as $interface => $ivalue )
			{
				echo helperEncodeHTML( $interface ) . ": " . helperEncodeHTML( $ivalue[ $field ] );
				
				if ( $dupcount[ $ivalue[ $field ] ] > 1 )
					echo " <span style=\"color: #880000; font-weight: bold\">(" . $dupcount[
						$ivalue[ $field ] ] . ")</span>";
				
				echo "<br />";
			}
			
			echo "</td>\n";
		}
		else
			echo "<td>-</td>\n";
	}
	
	echo "<h2>Consistency check</h2>\n";
	
	// Get group list
	$userid = $session->getID();
	$usergroups = array();
	$admin = false;
	
	if ( $userid )
		$usergroups = $backend->listAuthGroupMemberships( $userid );
	
	// Check if we are an admin
	foreach ( $usergroups as $g )
	{
		if ( $g[ "id" ] == 1 )
			$admin = true;
	}
	
	// Get list of all entries with all fields that are required for the consistency check
	$entries = $backend->getList();
	
	// Get list of all PXE servers
	$pxeservers = $backend->listAllPXE();
	$pxedata = array();
	
	foreach ( $pxeservers as $pxeserver )
	{
		$pxecon = new PXEController( $pxeserver[ "address" ] );
		$pxedata = array_merge( $pxedata, $pxecon->config );
	}
	
	$dupcount = array();
	$wrong = array();
	
	foreach ( $entries as $id => $e )
	{
		// Already added keeps track of the IPs and MACs that we already added for this machine to $dupcount
		// so that one single machine can increase the dupcount value only by one.
		$alreadyadded = array();
		
		unset( $curmac );
		unset( $curip );
		
		// IP from database
		if ( !empty( $e[ "ip" ] ) )
		{
			if ( isset( $dupcount[ $e[ "ip" ] ] ) )
				++$dupcount[ $e[ "ip" ] ];
			else
				$dupcount[ $e[ "ip" ] ] = 1;
			
			array_push( $alreadyadded, $e[ "ip" ] );
			
			$curip = $e[ "ip" ];
		}
		
		// IP from DNS
		$dnsresp[ $id ] = gethostbyname( $e[ "hostname" ] );
		if ( $dnsresp[ $id ] == $e[ "hostname" ] )
			$dnsresp[ $id ] = "";
		
		if ( $dnsresp[ $id ] && !in_array( $dnsresp[ $id ], $alreadyadded ) )
		{
			if ( isset( $dupcount[ $dnsresp[ $id ] ] ) )
				++$dupcount[ $dnsresp[ $id ] ];
			else
				$dupcount[ $dnsresp[ $id ] ] = 1;
			
			array_push( $alreadyadded, $dnsresp[ $id ] );
			
			if ( isset( $curip ) )
			{
				if ( $curip != $dnsresp[ $id ] )
					$wrong[ $id ] = true;
			}
			else
				$curip = $dnsresp[ $id ];
		}
		
		// MAC from database
		if ( !empty( $e[ "mac" ] ) )
		{
			if ( isset( $dupcount[ $e[ "mac" ] ] ) )
				++$dupcount[ $e[ "mac" ] ];
			else
				$dupcount[ $e[ "mac" ] ] = 1;
			
			array_push( $alreadyadded, $e[ "mac" ] );
			
			$curmac = $e[ "mac" ];
		}
		
		// Data from DHCP
		if ( isset( $pxedata[ $e[ "hostname" ] ] ) )
		{
			$sd = $pxedata[ $e[ "hostname" ] ];
			
			// Check IP for duplicate
			if ( !in_array( $sd[ "ip" ], $alreadyadded ) )
			{
				if ( isset( $dupcount[ $sd[ "ip" ] ] ) )
					++$dupcount[ $sd[ "ip" ] ];
				else
					$dupcount[ $sd[ "ip" ] ] = 1;
				
				array_push( $alreadyadded, $sd[ "ip" ] );
			}
			
			// Check IP for inner-machine consistency
			if ( isset( $curip ) )
			{
				if ( $curip != $sd[ "ip" ] )
					$wrong[ $id ] = true;
			}
			else
				$curip = $sd[ "ip" ];
			
			// Check MAC for duplicate
			if ( !in_array( $sd[ "mac" ], $alreadyadded ) )
			{
				if ( isset( $dupcount[ $sd[ "mac" ] ] ) )
					++$dupcount[ $sd[ "mac" ] ];
				else
					$dupcount[ $sd[ "mac" ] ] = 1;
				
				array_push( $alreadyadded, $sd[ "mac" ] );
			}
			
			// Check MAC for inner-machine consistency
			if ( isset( $curmac ) )
			{
				if ( $curmac != $sd[ "mac" ] )
					$wrong[ $id ] = true;
			}
			else
				$curmac = $sd[ "mac" ];
		}
		
		// Data from the monfiles directory
		$mddec[ $id ] = array();
		
		if ( $e[ "monfiles_data" ] )
		{
			$tmp = unserialize( $e[ "monfiles_data" ] );
			
			foreach ( $tmp as $iface => $mondata )
			{
				if ( substr( $iface, 0, 4 ) == "net_" )
					$mddec[ $id ][ substr( $iface, 4 ) ] = $mondata;
				else if ( $iface == "arch" )
				{
					$mfarch[ $id ] = $mondata;
					
					if ( !empty( $e[ "arch" ] ) && $e[ "arch" ] != $mondata )
						$wrong[ $id ] = true;
				}
				else
					continue;
			}
		}
		
		$rightipinmf = false;
		$rightmacinmf = false;
		$mfparsed = false;
		
		foreach ( $mddec[ $id ] as $iface => $mondata )
		{
			$mfparse = true;
			
			// Check IP for duplicate
			if ( !in_array( $mondata[ "ip" ], $alreadyadded ) )
			{
				if ( isset( $dupcount[ $mondata[ "ip" ] ] ) )
					++$dupcount[ $mondata[ "ip" ] ];
				else
					$dupcount[ $mondata[ "ip" ] ] = 1;
				
				array_push( $alreadyadded, $mondata[ "ip" ] );
			}
			
			// Check IP for inner-machine consistency
			if ( isset( $curip ) )
			{
				if ( $curip == $mondata[ "ip" ] )
					$rightipinmf = true;
			}
			
			// Check MAC for duplicate
			if ( !in_array( $mondata[ "mac" ], $alreadyadded ) )
			{
				if ( isset( $dupcount[ $mondata[ "mac" ] ] ) )
					++$dupcount[ $mondata[ "mac" ] ];
				else
					$dupcount[ $mondata[ "mac" ] ] = 1;
				
				array_push( $alreadyadded, $mondata[ "mac" ] );
			}
			
			// Check MAC for inner-machine consistency
			if ( isset( $curmac ) )
			{
				if ( $curmac == $mondata[ "mac" ] )
					$rightmacinmf = true;
			}
		}
		
		if ( $mfparsed && !( $rightipinmf && $rightmacinmf ) )
			$wrong[ $id ] = true;
	}
	
	// Start output
	echo "<p>Data that is considered to be inconsistent and should be checked is marked red:</p>\n";
	echo "<table class=\"editlist\" style=\"width: 100%\">\n";
	
	$i = 0;
	$highlight = false;
	
	foreach ( $entries as $id => $e )
	{
		// Repeat header lines every 20 entries
		if ( !( $i % 20 ) )
		{
			echo "<tr class=\"editrow-xdark\"><td>Hostname</td><td>IP in database</td>" .
				"<td>IP from DNS</td><td>IP from DHCP</td><td>IP from monfile</td><td>MAC in " .
				"database</td><td>Mac from DHCP</td><td>MAC from monfile</td><td>Arch in database" .
				"</td><td>Arch from monfile</td><td>Edit entry</td>" .
				"</tr>\n";
		}
		
		// Change layout every row
		if ( isset( $wrong[ $id ] ) )
			echo "<tr class=\"editrow-red\">";
		else if ( $highlight )
			echo "<tr class=\"editrow-dark\">";
		else
			echo "<tr class=\"editrow-light\">";
		
		$highlight = !$highlight;
		
		// Output entry
		echo "<td>" . helperEncodeHTML( $e[ "hostname" ] ) . "</td>";
		
		outputField( $dupcount, $e[ "ip" ] );
		outputField( $dupcount, $dnsresp[ $id ] );
		
		if ( isset( $pxedata[ $e[ "hostname" ] ] ) )
			outputField( $dupcount, $pxedata[ $e[ "hostname" ] ][ "ip" ] );
		else
			echo "<td>-</td>\n";
		
		outputFieldSOL( $dupcount, $mddec[ $id ], "ip" );
		outputField( $dupcount, $e[ "mac" ] );
		
		if ( isset( $pxedata[ $e[ "hostname" ] ] ) )
			outputField( $dupcount, $pxedata[ $e[ "hostname" ] ][ "mac" ] );
		else
			echo "<td>-</td>\n";
		
		outputFieldSOL( $dupcount, $mddec[ $id ], "mac" );
		
		if ( $e[ "arch" ] )
			echo "<td>" . $e[ "arch" ] . "</td>\n";
		else
			echo "<td>-</td>\n";
		
		if ( isset( $mfarch[ $id ] ) )
			echo "<td>" . $mfarch[ $id ] . "</td>\n";
		else
			echo "<td>-</td>\n";
		
		// Check for authentification to edit the machine
		$auth = false;
		
		if ( $admin )
			$auth = true;
		else
		{
			foreach ( $usergroups as $g )
			{
				if ( $g[ "id" ] == $e[ "groupid" ] )
				{
					$auth = true;
					break;
				}
			}
		}
		
		if ( $auth )
			echo "<td><a href=\"?a=e&amp;l=1&amp;i=" . $e[ "id" ] . "\">Edit</a></td>";
		else
			echo "<td>-</td>";
		
		echo "</tr>\n";
		
		++$i;
	}
	
	echo "</table>\n";
}

?>
