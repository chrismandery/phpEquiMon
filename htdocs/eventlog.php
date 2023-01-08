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
 * Shows the event log.
 */
function pageEventLog()
{
	global $backend;
	
	// Print headline
	echo "<h2>View event log</h2>\n";
	
	// Fetch number of available events
	$num = $backend->countEvents();
	
	if ( !$num )
		return;
	
	// Paging
	$ep = 50;  // Entries per page
	$pagesavailable = ceil( $num / $ep );
	
	if ( isset( $_POST[ "page_jump" ] ) && isset( $_POST[ "page" ] ) && helperIsDigit( $_POST[ "page" ] ) &&
		$_POST[ "page" ] >= 1 && $_POST[ "page" ] <= $pagesavailable )
		$page = $_POST[ "page" ];
	else if ( isset( $_POST[ "page_last" ] ) )
		$page = $pagesavailable;
	else
		$page = 1;
	
	// Display page navigation
	echo "<p>$pagesavailable page(s). $num events logged in database.</p>\n";

?>

<form action="?a=l" method="post"><p>
<input type="submit" name="page_first" value="First Page" />&nbsp;&nbsp;&nbsp;&nbsp;
<input type="text" name="page" value="<?php echo $page ?>" size="3" />
<input type="submit" name="page_jump" value="Jump to page" />&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" name="page_last" value="Last Page" />
</p></form>

<?php

	// Retrieve events from the database
	$events = $backend->viewEvents( ( $page - 1 ) * $ep, $ep );
	
	// Render table
	$highlight = true;
	
	echo "<table class=\"editlist\" style=\"width: 100%\">\n";
	echo "<tr class=\"editrow-xdark\"><td>Time</td><td>User</td><td>Event content</td></tr>\n";
	
	// Loop over all events to display
	foreach ( $events as $e )
	{
		if ( $highlight )
			echo "<tr class=\"editrow-light\">\n";
		else
			echo "<tr class=\"editrow-dark\">\n";
		
		$highlight = !$highlight;
		
		echo "<td>" . date( "D M j G:i:s T Y", $e[ "time" ] ) . "</td>\n";
		
		echo "<td>";
		if ( $e[ "user" ] )
		{
			$username = "Unknown";
			$contact = $backend->getContact( $e[ "user" ], $username );
			
			helperPrintUserInfo( $username, $contact );
		}
		else
			echo "phpEquiMon";
		
		echo "</td>\n";
		
		echo "<td>" . helperEncodeHTML( $e[ "content" ] ) . "</td>\n";
		
		echo "</tr>\n";
	}
	
	echo "</table>\n";
}

?>
