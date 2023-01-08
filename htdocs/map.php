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
 * Display a page that shows a "map" of the server room.
*/
function pageShowMap()
{
	global $backend, $theme;
	
	echo "<h2>Room Map</h2>\n";
	
	// Mapping feature enabled?
	if ( $backend->readConfig( "enable_map" ) != "yes" )
	{
		echo "<p class=\"err\">Map is disabled in the configuration.</p>\n";
		return;
	}
	
	echo "<form action=\"?\" method=\"get\">\n";
	
	echo "<p><input type=\"hidden\" name=\"a\" value=\"x\" />\n";
	echo "<select name=\"room\" size=\"1\">\n";
	
	// Read config
	$roomstr = $backend->readConfig( "rooms" );
	$rooma = explode( ",", $roomstr );
	
	// Display available rooms fromconfig
	foreach ( $rooma as $room )
	{
		$room = helperEncodeHTML( $room );
		
		if ( isset( $_GET[ "room" ] ) && ( $room == $_GET[ "room" ] ) )
			echo "<option selected=\"selected\">$room</option>\n";
		else
			echo "<option>$room</option>\n";
	}
	
	echo "</select>\n";
	echo "<input type=\"submit\" name=\"submit\" value=\"Show map\" />\n";
	echo "</p></form>\n";
	
	// If the user has not made a choice yet, abort now
	if ( !isset( $_GET[ "room" ] ) || !in_array( $_GET[ "room" ], $rooma ) )
		return;
	
	// Get list with all machines in the selected room
	foreach ( $rooma as $room )
		$roomfilter[ $room ] = ( $_GET[ "room" ] == $room );
		
	$machines = $backend->getList( -1, false, false, false, false, false, false, false, false, $roomfilter );
	$map = array();
	
	foreach ( $machines as $id => $m )
	{
		// Room field valid?
		if ( !preg_match( "/^" . $_GET[ "room" ] . " [[:alnum:]]+\/[[:digit:]]+#[[:digit:]]+" .
			"(-[[:digit:]]+)?$/", $m[ "room" ] ) )
			continue;
		
		// Extract row number and rack number from room string
		$sp = substr( $m[ "room" ], strlen( $_GET[ "room" ] ) + 1 );
		$sp = explode( "#", $sp );
		
		$place = explode( "/", $sp[ 0 ] );
		
		$row = $place[ 0 ];
		$rack = $place[ 1 ];
		
		// Extract position and height from room string
		if ( strpos( $sp[ 1 ], "-" ) )
		{
			$sp = explode( "-", $sp[ 1 ] );
			
			$pos = max( $sp[ 0 ], $sp[ 1 ] );
			$height = abs( $sp[ 0 ] - $sp[ 1 ] ) + 1;
		}
		else
		{
			$pos = $sp[ 1 ];
			$height = 1;
		}
		
		// Create row array if not existing
		if ( !isset( $map[ $row ] ) )
			$map[ $row ] = array( $rack => array(), "maxrack" => $rack );
		
		// Create rack array if not existing
		else if ( !isset( $map[ $row ][ $rack ] ) )
		{
			$map[ $row ][ $rack ] = array();
			
			if ( $rack > $map[ $row ][ "maxrack" ] )
				$map[ $row ][ "maxrack" ] = $rack;
		}
		
		// Generate array for free slot search
		unset( $slot );
		$slotavail = array();
		
		for ( $s = 1; $s <= 5; ++$s )  // magic number = hardcoded maximum of slots in a rack
			$slotavail[ $s ] = true;
		
		foreach ( $map[ $row ][ $rack ] as $spos => $content )
		{
			if ( is_array( $content ) )
			{
				foreach ( $content as $cslot => $im )
				{
					// Check if machines collide
					if ( $pos < $spos )
					{
						if ( $spos - $im[ "height" ] < $pos )
							$slotavail[ $cslot ] = false;
					}
					else if ( $pos > $spos )
					{
						if ( $pos - $height < $spos )
							$slotavail[ $cslot ] = false;
					}
					else
						$slotavail[ $cslot ] = false;
				}
			}
		}
		
		// Find free slot
		foreach ( $slotavail as $cslot => $res )
		{
			if ( $res )
			{
				$slot = $cslot;
				break;
			}
		}
		
		// No free slot found, we cannot include this machine
		if ( !isset( $slot ) )
		{
			echo "<p class=\"err\">Slots exhausted. Machine skipped.</p>\n";
			continue;
		}
		
		// Create position in rack of not existing
		if ( !isset( $map[ $row ][ $rack ][ $pos ] ) )
			$map[ $row ][ $rack ][ $pos ] = array();
		
		// Insert machine
		$map[ $row ][ $rack ][ $pos ][ $slot ] = array( "height" => $height, "id" => $id );
		
		// Update height of the rack if the new entry exceeds the current height
		if ( !isset( $map[ $row ][ $rack ][ "height" ] ) || $map[ $row ][ $rack ][ "height" ] < $pos )
			$map[ $row ][ $rack ][ "height" ] = $pos;
		
		// Update slot count for this rack
		if ( !isset( $map[ $row ][ $rack ][ "slots" ] ) || $map[ $row ][ $rack ][ "slots" ] < $slot )
			$map[ $row ][ $rack ][ "slots" ] = $slot;
	}
	
	// Output quicklinks for rows and racks
	foreach ( $map as $rownum => $row )
	{
		echo "<p><a href=\"#row$rownum\">Jump to Row #$rownum:</a>\n";
		
		for ( $i = 1; $i <= $row[ "maxrack" ]; $i += 5 )
			echo "<a href=\"#row${rownum}rack$i\">Rack $i</a>\n";
		
		echo "</p>\n";
	}
	
	foreach ( $map as $rownum => $row )
	{
		echo "<a name=\"row$rownum\"><h2>Rack Row #$rownum</h2></a>\n";
		
		$startrack = 1;
		while ( $startrack <= $row[ "maxrack" ] )
		{
			// Calculate endrack
			$endrack = $startrack + 4;
			if ( $endrack > $row[ "maxrack" ] )
				$endrack = $row[ "maxrack" ];
			
			echo "<a name=\"row${rownum}rack$startrack\"><h2>Racks #$startrack - #$endrack</h2></a>\n";
			echo "<p><a href=\"#\">Back to top!</a></p>\n";
			
			// Calculate height of the 5 currently displayed racks
			$height = 0;
			for ( $i = $startrack; $i <= $endrack; ++$i )
			{
				if ( isset( $row[ $i ][ "height" ] ) && $row[ $i ][ "height" ] > $height )
					$height = $row[ $i ][ "height" ];
			}
			
			// Print table header
			echo "<table class=\"racklist\">\n";
			echo "<tr class=\"rackrow-header\">\n";
			echo "<td>&nbsp;</td>\n";
			
			for ( $j = $startrack; $j <= $endrack; ++$j )
			{
				if ( !isset( $row[ $j ][ "slots" ] ) )
					$row[ $j ][ "slots" ] = 1;
				
				echo "<td style=\"width: 200px\" colspan=\"" . $row[ $j ][ "slots" ] . "\" >" .
					helperEncodeHTML( $_GET[ "room" ] ) . " $rownum/$j</td>";
			}
			
			echo "<td>&nbsp;</td>\n";
			echo "</tr>\n";
			
			// Loops over all heights of the selected 5 racks
			$highlight = true;
			for ( $i = $height; $i; --$i )
			{
				if ( $highlight )
					echo "<tr class=\"rackrow-light\">\n";
				else
					echo "<tr class=\"rackrow-dark\">\n";
				
				$highlight = !$highlight;
				
				echo "<td>$i</td>\n";
				
				// Render the 5 columns for the 5 racks
				for ( $j = $startrack; $j <= $endrack; ++$j )
				{
					// Loop over slots for this rack
					for ( $s = 1; $s <= $row[ $j ][ "slots" ]; ++$s )
					{
						// Continue machine from row above? (rowspan)
						if ( isset( $row[ $j ][ $s ][ "continuem" ] ) && $row[ $j ][ $s ]
							[ "continuem" ] > 0 )
							--$row[ $j ][ $s ][ "continuem" ];
						
						// New machine at this position?
						else if ( isset( $row[ $j ][ $i ][ $s ] ) )
						{
							$id = $row[ $j ][ $i ][ $s ][ "id" ];
							$height = $row[ $j ][ $i ][ $s ][ "height" ];
							
							// Make table cell colorful based on downtime (like on the index list)
							$downtime = time() - $machines[ $id ][ "lastping" ];
							$color = $theme->calcDowntimeColor( $downtime );
							
							echo "<td style=\"background-color: $color\"";
							
							// Start rowspan if height > 1
							if ( $height > 1 )
							{
								echo " rowspan=\"$height\"";
								$row[ $j ][ $s ][ "continuem" ] = $height - 1;
							}
							
							echo ">\n";
							
							$tooltip = "";
							
							if ( !empty( $machines[ $id ][ "vendor" ] ) || !empty( $machines[ $id ]
								[ "model" ] ) )
							{
								$tooltip .= "Type: " . helperEncodeHTML( $machines[ $id ]
									[ "vendor" ] ) . " " . helperEncodeHTML( $machines
									[ $id ][ "model" ] );
							}
							
							if ( $machines[ $id ][ "usedby_id1" ] || $machines[ $id ]
								[ "usedby_id2" ] || $machines[ $id ][ "usedby" ] )
							{
								$tooltip .= "<br />Used by: ";
								
								if ( $machines[ $id ][ "usedby_id1" ] )
								{
									$username = "";
									$userinfo = $backend->getContact( $machines
										[ $id ][ "usedby_id1" ], $username );
									
									if ( $userinfo )
									{
										$ca = unserialize( $userinfo );
										
										if ( isset( $ca[ "realname" ] ) )
										{
											$tooltip .= helperEncodeHTML(
												$ca[ "realname" ] );
										}
										else
										{
											$tooltip .= helperEncodeHTML(
												$username );
										}
									}
									else
									{
										$tooltip .= helperEncodeHTML(
											$username );
									}
								}
								
								if ( $machines[ $id ][ "usedby_id2" ] )
								{
									if ( $machines[ $id ][ "usedby_id1" ] )
										$tooltip .= " &amp; ";
									
									$username = "";
									$userinfo = $backend->getContact( $machines
										[ $id ][ "usedby_id2" ], $username );
									
									if ( $userinfo )
									{
										$ca = unserialize( $userinfo );
										
										if ( isset( $ca[ "realname" ] ) )
										{
											$tooltip .= helperEncodeHTML(
												$ca[ "realname" ] );
										}
										else
										{
											$tooltip .= helperEncodeHTML(
												$username );
										}
									}
									else
									{
										$tooltip .= helperEncodeHTML(
											$username );
									}
								}
								
								if ( $machines[ $id ][ "usedby" ] )
								{
									if ( $machines[ $id ][ "usedby_id1" ] || $machines
										[ $id ][ "usedby_id2" ] )
										$tooltip .= " (" . helperEncodeHTML( $machines
											[ $id ][ "usedby" ] ) . ")";
									else
										$tooltip .= helperEncodeHTML( $machines[ $id ]
											[ "usedby" ] );
								}
							}
							
							if ( $machines[ $id ][ "notes" ] )
							{
								$tooltip .= "<br />Notes: " . helperEncodeHTML( str_replace(
									array( "\r\n", "\r", "\n"), "<br />", $machines
									[ $id  ][ "notes" ] ) );
							}
							
							$tooltip .= "<br /><br />Click on machine name to view/edit machine " .
								"details.";
								
							$theme->printTooltip( "?a=e&amp;i=$id", $machines[ $id ][ "hostname" ],
								$tooltip, "machine$id" );
							
							echo "</td>\n";
						}
						
						// Empty slot
						else
							echo "<td>&nbsp;</td>\n";
					}
				}
				
				echo "<td>$i</td>\n";
				
				echo "</tr>\n";
			}
			
			echo "</table>\n";
			
			$startrack += 5;
		}
	}
}

?>
