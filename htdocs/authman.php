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
 * Shows the page for auth management. Only administrators can access this page and modify the access control here.
 * With this page new user/group pairs can be added or old ones removed (editing is currently not possible).
 */
function pageAuthManagement()
{
	global $backend, $session;
	
	// Add new group?
	if ( isset( $_POST[ "submit-addgroup" ] ) && !empty( $_POST[ "name" ] ) )
	{
		$backend->addAuthGroup( $_POST[ "name" ] );
		$backend->logEvent( $session->getID(), "New user group created: " . $_POST[ "name" ] );
	}
	
	// Delete existing group?
	else if ( isset( $_GET[ "delgroup" ] ) && helperIsDigit( $_GET[ "delgroup" ] ) )
	{
		$backend->deleteAuthGroup( $_GET[ "delgroup" ] );
		$backend->logEvent( $session->getID(), "Deleted user group: id = " . $_GET[ "delgroup" ] );
	}
	
	// Add new user?
	else if ( isset( $_POST[ "submit-adduser" ] ) && !empty( $_POST[ "name" ] ) )
	{
		$backend->addAuthUser( $_POST[ "name" ] );
		$backend->logEvent( $session->getID(), "New user created: " . $_POST[ "name" ] );
	}
	
	// Delete existing user?
	else if ( isset( $_GET[ "deluser" ] ) && helperIsDigit( $_GET[ "deluser" ] ) )
	{
		$backend->deleteAuthUser( $_GET[ "deluser" ] );
		$backend->logEvent( $session->getID(), "Deleted user: id = " . $_GET[ "deluser" ] );
	}
	
	// Add new mapping?
	else if ( isset( $_POST[ "submit-addmapping" ] ) )
	{
		foreach ( $_POST as $postkey => $postvalue )
		{
			if ( preg_match( "/group_user_[[:digit:]]+/", $postkey ) && $postvalue != "n" )
			{
				$user = substr( $postkey, 11 );
				
				$backend->addAuthGroupMembership( $user, $postvalue );
				$backend->logEvent( $session->getID(), "User id = $user joined group id = $postvalue." );
			}
		}
	}
	
	// Delete existing mapping?
	else if ( isset( $_GET[ "delmapping_user" ] ) && helperIsDigit( $_GET[ "delmapping_user" ] ) &&
		isset( $_GET[ "delmapping_group" ] ) && helperIsDigit( $_GET[ "delmapping_group" ] ) )
	{
		$backend->deleteAuthGroupMembership( $_GET[ "delmapping_user" ], $_GET[ "delmapping_group" ] );
		$backend->logEvent( $session->getID(), "Removed user " . $_GET[ "delmapping_user" ] . " from group " .
			$_GET[ "delmapping_group" ] . "." );
	}
	
	// Get auth list from database
	$userlist = $backend->listAuthUsers();
	$grouplist = $backend->listAuthGroups();

?>

<h2>Authentification Management</h2>

<p style="font-weight: bold">Available groups:</p>

<form action="?a=am" method="post">
<p>Add new group:
<input type="text" name="name" size="20" />
<input type="submit" name="submit-addgroup" value="Add Group" />
</p></form>

<table class="editlist">

<?php

	$x = 0;
	
	foreach ( $grouplist as $group )
	{
		if ( $group[ "id" ] != 1 )
		{
			if ( ( $x % 5 ) == 0 )
			{
				if ( $x != 0 )
					echo "</tr>\n";
				
				echo "<tr class=\"editrow-light\">\n";
			}
			
			echo "<td>" . helperEncodeHTML( $group[ "groupname" ] ) . " - <a href=\"?a=am&amp;delgroup=" .
				$group[ "id" ] . "\">Delete</a></td>\n";
			
			++$x;
		}
	}

?>

</tr></table>

<p style="font-weight: bold; padding-top: 40px">Available users:</p>

<form action="?a=am" method="post">
<p>Add new user:
<input type="text" name="name" size="20" />
<input type="submit" name="submit-adduser" value="Add User" />
</p>

<table class="editlist">
<tr class="editrow-xdark">
<td style="font-weight: bold">User</td>
<td style="font-weight: bold">Password</td>
<td style="font-weight: bold">Settings</td>
<td style="font-weight: bold">Group(s)</td>
<td style="font-weight: bold">Add user to group</td>
<td style="font-weight: bold">Last active</td></tr>

<?php

	$highlight = true;
	
	foreach ( $userlist as $user )
	{
		$usergroups = $backend->listAuthGroupMemberships( $user[ "id" ] );
		
		if ( $highlight )
			echo "<tr class=\"editrow-light\"><td>";
		else
			echo "<tr class=\"editrow-dark\"><td>";
		
		$highlight = !$highlight;
		
		helperPrintUserInfo( $user[ "username" ], $user[ "contact" ] );
		
		echo " - <a href=\"?a=am&amp;deluser=" . $user[ "id" ] . "\">Delete</a></td>\n";
		
		if ( $user[ "password" ] )
			echo "<td>Set.</td>\n";
		else
			echo "<td>Not set.</td>\n";
		
		echo "<td><a href=\"?a=s&amp;i=" . $user[ "id" ] . "\">Change</a></td>\n";
		
		echo "<td>";
		
		foreach ( $usergroups as $g )
		{
			echo helperEncodeHTML( $g[ "groupname" ] ) . " - <a href=\"?a=am&amp;delmapping_user=" . $user[
				"id" ] . "&amp;delmapping_group=" . $g[ "id" ] . "\">Remove</a><br />\n";
		}
		
		echo "</td>\n";
		echo "<td>";
		
		echo "<select name=\"group_user_" . $user[ "id" ] . "\" size=\"1\">\n";
		
		echo "<option value=\"n\">Select</option>\n";
		
		foreach ( $grouplist as $g )
			echo "<option value=\"" . $g[ "id" ] . "\">" . helperEncodeHTML( $g[ "groupname" ] ) .
				"</option>\n";
		
		echo "</select>\n";
		
		echo "<input type=\"submit\" name=\"submit-addmapping\" value=\"Add To Group\" />\n";
		
		echo "</td>\n";
		
		if ( $user[ "lastuse" ] )
			echo "<td>" . date( "D M j G:i:s T Y", $user[ "lastuse" ] ) . "</td></tr>\n";
		else
			echo "<td>-</td></tr>\n";
	}
	
	echo "</table>\n";
	echo "</form>\n";
}

?>
