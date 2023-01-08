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
 * Returns the viewer 10 free hostnames (free = not in database, free != not responding to ping)
 */
function pageFreeHostnames()
{
	global $backend;
	
	echo "<h2>Probe for Free Hostnames</h2>\n";
	
	$prefix = $backend->readConfig( "fh_prefix" );
	$i = $backend->readConfig( "fh_start" );
	
	if ( !$prefix || !helperIsAlnum( $prefix ) || !$i || !helperIsDigit( $i ) )
	{
		echo "<p class=\"err\">Free hostnames settings not valid in configuration.</p>\n";
		return;
	}
	
	echo "<p class=\"infobox\"><b>10 available hostnames:</b> (green = does not pong, red = pongs!) (click on " .
		"hostname to add to the Hardware Database)<br />\n";
	
	$machines = $backend->getList();
	
	$found = 0;
	
	while ( $found < 10 )
	{
		$assigned = false;
		foreach( $machines as $m )
		{
			if ( $m[ "hostname" ] == $prefix . $i )
			{
				$assigned = true;
				break;
			}
		}
		
		if ( !$assigned )
		{
			$cmd = "/usr/sbin/fping -t 50 $prefix$i";
			
			if ( exec( $cmd ) == ( "$prefix$i is alive" ) )
				echo "<span style=\"color: red\">$prefix$i</span>\n";
			else
				echo "<a href=\"?a=e&amp;fh=$prefix$i\" style=\"color: green\">$prefix$i</a>\n";
			
			++$found;
		}
		
		++$i;
	}
	
	echo "</p>\n";
	
	pageOutputList();
}

?>
