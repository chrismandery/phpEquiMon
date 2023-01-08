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
 * Display a page that allows the user to change his/her personal settings (password etc.)
*/
function pageUserSettings()
{
	global $backend, $session;
	
	echo "<h2>User Settings</h2>\n";
	
	// Edit other profile
	if ( isset( $_GET[ "i" ] ) && helperIsDigit( $_GET[ "i" ] ) )
	{
		// This is only allowed for admins
		if ( !$backend->checkAuth( 1 ) )
		{
			echo "<p class=\"err\>Only an admin user can edit other people's profiles!</p>\n";
			return;
		}
		
		$userid = $_GET[ "i" ];
		
		$userinfo = $backend->getUserInfo( $userid );
		$username = $userinfo[ "username" ];
		$editown = false;
	}
	
	// Edit our own profile
	else
	{
		// Abort if user is not logged in
		$userid = $session->getID();
		if ( !$userid )
		{
			echo "<p class=\"err\">You are not logged in.</p>\n";
			return;
		}
		
		$username = $session->getUsername();
		$editown = true;
	}
	
	// Change settings
	if ( isset( $_POST[ "submit" ] ) )
	{
		// Change password?
		if ( !empty( $_POST[ "password-1" ] ) && !empty( $_POST[ "password-2" ] ) )
		{
			// Check that passwords match
			if ( $_POST[ "password-1" ] == $_POST[ "password-2" ] )
			{
				$backend->updateAuthUserPassword( $userid, $_POST[ "password-1" ] );
				
				echo "<p>Password changed.</p>\n";
			}
			else
				echo "<p class=\"err\">Passwords did not match, try again.</p>\n";
		}
		
		// Update contact data
		if ( isset( $_POST[ "c_realname" ] ) && isset( $_POST[ "c_mail" ] ) && isset( $_POST[ "c_tel" ] ) &&
			isset( $_POST[ "c_building" ] ) && isset( $_POST[ "c_room" ] ) )
		{
			$contact = array();
			
			if ( !empty( $_POST[ "c_realname" ] ) )
				$contact[ "realname" ] = $_POST[ "c_realname" ];
			
			if ( !empty( $_POST[ "c_mail" ] ) )
				$contact[ "mail" ] = $_POST[ "c_mail" ];
			
			if ( !empty( $_POST[ "c_tel" ] ) )
				$contact[ "tel" ] = $_POST[ "c_tel" ];
			
			if ( !empty( $_POST[ "c_building" ] ) )
				$contact[ "building" ] = $_POST[ "c_building" ];
			
			if ( !empty( $_POST[ "c_room" ] ) )
				$contact[ "room" ] = $_POST[ "c_room" ];
			
			$backend->updateContact( $userid, serialize( $contact ) );
		}
		
		// Change and user settings
		if ( $editown && isset( $_POST[ "theme" ] ) && isset( $_POST[ "loginlayout" ] ) )
		{
			// LDAP disabled for this user?
			if ( isset( $_POST[ "forbidldap" ] ) )
				$session->_settings[ "forbidldap" ] = true;
			else
				unset( $session->_settings[ "forbidldap" ] );
			
			// Theme
			$session->_settings[ "theme" ] = $_POST[ "theme" ];
			
			// Login layout
			if ( $_POST[ "loginlayout" ] == "guest" || $_POST[ "loginlayout" ] == "simple" ||
				$_POST[ "loginlayout" ] == "extended" || $_POST[ "loginlayout" ] == "own" )
				$session->_settings[ "loginlayout" ] = $_POST[ "loginlayout" ];
			else
				echo "<p class=\"err\">Login layout must be one of: guest, simple, extended, own</p>\n";
			
			// Filter regexp
			$session->_settings[ "filterregexp" ] = $_POST[ "filterregexp" ];
			
			// Tooltips setting
			if ( isset( $_POST[ "edittooltips" ] ) )
				$session->_settings[ "edittooltips" ] = true;
			else if ( isset( $session->_settings[ "edittooltips" ] ) )
				unset( $session->_settings[ "edittooltips" ] );
			
			$session->saveSettings();
			
			$backend->logEvent( $userid, "Changed own profile." );
		}
		else
			$backend->logEvent( $session->getID(), "Changed profile of user with id $userid." );
		
		echo "<p>Updated data.</p>\n";
	}
	
	// Display edit form
	else
	{
		$nametmp = "";
		$contacts = $backend->getContact( $userid, $nametmp );
		
		if ( empty( $contacts ) )
			$contact = array();
		else
			$contact = unserialize( $contacts );
		
		if ( $editown )
			echo "<form action=\"?a=s\" method=\"post\">\n";
		else
			echo "<form action=\"?a=s&amp;i=$userid\" method=\"post\">\n";
		
		echo "<table class=\"editlist\">\n";
		
		echo "<tr class=\"editrow-dark\"><td>Username</td><td>" . helperEncodeHTML( $username ) .
			"</td></tr>\n";
		
		echo "<tr class=\"editrow-light\"><td>Password</td><td><input type=\"password\" name=\"password-1\" " .
			"size=\"20\" /></td></tr>\n";
		echo "<tr class=\"editrow-dark\"><td>Password (repeat)</td><td><input type=\"password\" name=\"" .
			"password-2\" size=\"20\" /></td></tr>\n";
		
		echo "<tr class=\"editrow-light\"><td>Realname</td><td><input type=\"text\" name=\"c_realname\" " .
			"size=\"40\" ";
		
		if ( isset( $contact[ "realname" ] ) )
			echo "value=\"" . helperEncodeHTML( $contact[ "realname" ] ) . "\" ";
		
		echo "/></td></tr>\n";
		
		echo "<tr class=\"editrow-dark\"><td>Mail</td><td><input type=\"text\" name=\"c_mail\" size=\"40\" ";
		
		if ( isset( $contact[ "mail" ] ) )
			echo "value=\"" . helperEncodeHTML( $contact[ "mail" ] ) . "\" ";
		
		echo "/></td></tr>\n";
		
		echo "<tr class=\"editrow-light\"><td>Telephone</td><td><input type=\"text\" name=\"c_tel\" " .
			"size=\"40\" ";
		
		if ( isset( $contact[ "tel" ] ) )
			echo "value=\"" . helperEncodeHTML( $contact[ "tel" ] ) . "\" ";
		
		echo "/></td></tr>\n";
		
		echo "<tr class=\"editrow-dark\"><td>Building</td><td><input type=\"text\" name=\"c_building\" " .
			"size=\"40\" ";
		
		if ( isset( $contact[ "building" ] ) )
			echo "value=\"" . helperEncodeHTML( $contact[ "building" ] ) . "\" ";
		
		echo "/></td></tr>\n";
		
		echo "<tr class=\"editrow-light\"><td>Room</td><td><input type=\"text\" name=\"c_room\" size=\"40\" ";
		
		if ( isset( $contact[ "room" ] ) )
			echo "value=\"" . helperEncodeHTML( $contact[ "room" ] ) . "\" ";
		
		echo "/></td></tr>\n";
		
		// User settings only available for own profile
		if ( $editown )
		{
			echo "<tr class=\"editrow-dark\"><td>LDAP</td><td><input type=\"checkbox\" name=\"" .
				"forbidldap\" ";
			
			if ( isset( $session->_settings[ "forbidldap" ] ) )
				echo "checked=\"checked\" ";
			
			echo "/> Forbid LDAP contact update for this user.</td></tr>\n";
			
			echo "<tr class=\"editrow-light\"><td>Theme to use</td><td>\n";
			
			if ( isset( $session->_settings[ "theme" ] ) )
			{
				echo "<input type=\"text\" name=\"theme\" value=\"" . helperEncodeHTML( $session->
					_settings[ "theme" ] ) . "\" />\n";
			}
			else
				echo "<input type=\"text\" name=\"theme\" value=\"default\" />\n";
			
			echo "</td></tr>\n";
			
			echo "<tr class=\"editrow-dark\"><td>Layout to load after login...<br />(guest/simple/" .
				"extended/own)</td><td>\n";
			
			if ( isset( $session->_settings[ "loginlayout" ] ) )
			{
				echo "<input type=\"text\" name=\"loginlayout\" value=\"" . helperEncodeHTML(
					$session->_settings[ "loginlayout" ] ) . "\" />\n";
			}
			else
				echo "<input type=\"text\" name=\"loginlayout\" value=\"simple\" />\n";
			
			echo "</td></tr>\n";
			
			echo "<tr class=\"editrow-light\"><td>Filter regexp (if hostname matches, machine is hidden)" .
				"</td><td>\n";
			
			if ( isset( $session->_settings[ "filterregexp" ] ) )
			{
				echo "<input type=\"text\" name=\"filterregexp\" value=\"" . helperEncodeHTML(
					$session->_settings[ "filterregexp" ] ) . "\" />\n";
			}
			else
				echo "<input type=\"text\" name=\"filterregexp\" />\n";
			
			echo "</td></tr>\n";
			
			echo "<tr class=\"editrow-dark\"><td>Tooltips</td><td><input type=\"checkbox\" name=\"" .
				"edittooltips\" ";
			
			if ( isset( $session->_settings[ "edittooltips" ] ) )
				echo "checked=\"checked\" ";
			
			echo "/> Use tooltips instead of an own table column on the edit page (for low-res " .
				"displays).</td></tr>\n";
		}
		else
		{
			echo "<tr class=\"editrow-dark\"><td colspan=\"2\">User settings not available when " .
				"editting other users.</td></tr>\n";
		}
		
		echo "<tr class=\"editrow-light\"><td colspan=\"2\"><input type=\"submit\" name=\"submit\" value=\"" .
			"Submit\" />&nbsp;&nbsp;<input type=\"reset\" name=\"reset\" value=\"Reset\" /></td></tr>\n";
		
		echo "</table>\n";
		echo "</form>\n";
	}
}

?>
