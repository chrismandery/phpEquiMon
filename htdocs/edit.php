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
 * Shows a form for adding new or editing existing machines. Also calls pageOutputList().
 */
function pageEdit()
{
	global $backend, $lastaddid, $session;
	
	/**
	 * Validates a given entry.
	 * @param array $entry Entry array with all fields set to validate.
	 * @return boolean True if the entry is valid and is allowed to be written to the database.
	 */
	function validateEntry( $entry, $id )
	{
		global $backend;
		
		if ( !isset( $entry[ "hostname" ] ) || empty( $entry[ "hostname" ] ) )
		{
			echo "<p class=\"err\">Please enter a hostname.</p>\n";
			return false;
		}
		
		$hostnameid = $backend->queryIDFromHostname( $entry[ "hostname" ] );
		
		if ( $hostnameid && ( !$id || ( $id != $hostnameid ) ) )
		{
			echo "<p class=\"err\">Hostname already in use.</p>\n";
			return false;
		}
		
		if ( !( empty( $entry[ "ip" ] ) || helperIsIP( $entry[ "ip" ] ) ) )
		{
			echo "<p class=\"err\">Please leave IP field empty or enter a valid IP.</p>\n";
			return false;
		}
		
		if ( !( empty( $entry[ "mac" ] ) || helperIsMAC( $entry[ "mac" ] ) ) )
		{
			echo "<p class=\"err\">Please leave MAC field empty or enter a valid MAC.</p>\n";
			return false;
		}
		
		if ( !( empty( $entry[ "expiredate" ] ) || helperIsDate( $entry[ "expiredate" ] ) ) )
		{
			echo "<p class=\"err\">Please leave expiration date field empty of enter a valid date " .
				"(YYYY-MM-DD).</p>\n";
			return false;
		}
		
		return true;
	}
	
	/**
	 * Echos out a new row start (tr tag) with the correct background color.
	 */
	function rowHighlight()
	{
		static $highlight = false;
		
		$highlight = !$highlight;
		
		if ( $highlight )
			return "<tr class=\"editrow-dark\">";
		else
			return "<tr class=\"editrow-light\">";
	}
	
	/**
	 * Displays a combo box that allows to choose a user.
	 * @param array $userlist User list to display.
	 * @param integer $userid User ID to preselect.
	 */
	function printUserComboBox( $userlist, $userid )
	{
		if ( $userid )
			echo "<option value=\"0\">-</option>\n";
		else
			echo "<option value=\"0\" selected=\"selected\">-</option>\n";
		
		foreach( $userlist as $authentry )
		{
			$contact = unserialize( $authentry[ "contact" ] );
			
			echo "<option ";
			if ( $userid && $userid == $authentry[ "id" ] )
				echo "selected=\"selected\" ";
			
			echo "value=\"" . $authentry[ "id" ] . "\">" . helperEncodeHTML( $authentry[ "username" ] );
			
			if ( !empty( $contact[ "realname" ] ) )
				echo " - " . helperEncodeHTML( $contact[ "realname" ] );
			
			echo "</option>\n";
		}
	}
	
	/**
	 * Displays one row in the edit table.
	 * @param array $m Record array (given as a reference).
	 * @param string $name Name of the row ("hostname", "vendor" etc.).
	 * @param boolean $ro Readonly flag.
	 * @param array $gec Groupedit conflicts information for groupedit (given as a reference).
	 */
	function printEditRow( &$m, $name, $ro, &$gec )
	{
		global $backend, $session, $theme;
		
		static $tabindex = 1;
		
		// Set variables to append to HTML form tags
		if ( $ro )
		{
			$ro_html1 = "disabled=\"disabled\" ";
			$ro_html2 = "readonly=\"readonly\" ";
		}
		else
		{
			$ro_html1 = "";
			$ro_html2 = "";
		}
		
		echo rowHighlight();
		
		switch ( $name )
		{
			case "hostname":
				$desc = "Hostname for this record. Must be unique over the database.";
				
				echo "<td><label for=\"e_hostname\">Hostname</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td>";
				
				if ( $gec )
				{
					echo "<td>Groupediting ";
					
					$first = true;
					
					foreach ( $m[ "hostname" ] as $curhost )
					{
						if ( $first )
						{
							echo helperEncodeHTML( $curhost );
							$first = false;
						}
						else
							echo ", " . helperEncodeHTML( $curhost );
					}
				}
				else
				{
					echo "<td><input type=\"text\" name=\"hostname\" size=\"80\" value=\"" .
						helperEncodeHTML( $m[ "hostname" ] ) . "\" tabindex=\"" . ++$tabindex .
						"\" id=\"e_hostname\" $ro_html2/>";
				}
				
				break;
			
			case "groupid":
				$desc = "The group provides authentification. Every user that is a member of this " .
					"group is allowed to edit (or delete) this entry.";
				
				echo "<td><label for=\"e_groupid\">Group</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td><select name=\"groupid\" size=\"1\" tabindex=\"" . ++$tabindex .
					"\" id=\"e_groupid\" $ro_html1>\n";
				
				if ( $backend->checkAuth( 1 ) )
					$usergroups = $backend->listAuthGroups();
				else
					$usergroups = $backend->listAuthGroupMemberships( $session->getID() );
				
				foreach ( $usergroups as $g )
				{
					$gname = helperEncodeHTML( $g[ "groupname" ] );
					
					if ( $g[ "id" ] == $m[ "groupid" ] )
					{
						echo "<option selected=\"selected\" value=\"" . $g[ "id" ] .
							"\">$gname</option>\n";
					}
					else
						echo "<option value=\"" . $g[ "id" ] . "\">$gname</option>\n";
				}
				
				echo "</select>\n";
				
				break;
			
			case "vendor":
				$desc = "Select vendor from combobox (available vendors configurable by admin).";
				
				echo "<td><label for=\"e_vendor\">Vendor</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				$vendorstr = $backend->readConfig( "vendors" );
				
				if ( empty( $vendorstr ) )
					echo "<td>No vendors configured.";
				else
				{
					echo "</td><td><select name=\"vendor\" size=\"1\" tabindex=\"" . ++$tabindex .
						"\" id=\"e_vendor\" $ro_html1>\n";
					
					echo "<option value=\"\">-</option>\n";
					
					$vendora = explode( ",", $vendorstr );
					
					foreach ( $vendora as $vendor )
					{
						$vendor = helperEncodeHTML( $vendor );
						
						if ( $vendor == $m[ "vendor" ] || strtolower( $vendor ) == $m[
							"vendor" ] )
							echo "<option selected=\"selected\">$vendor</option>\n";
						else
							echo "<option>$vendor</option>\n";
					}
					
					echo "</select>\n";
				}
				
				break;
			
			case "model":
				$desc = "Free text field for a model descriptor.";
				
				echo "<td><label for=\"e_model\">Model</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td><input type=\"text\" name=\"model\" size=\"80\" value=\"" .
					helperEncodeHTML( $m[ "model" ] ) . "\" tabindex=\"" . ++$tabindex .
					"\" id=\"e_model\" $ro_html2/>";
				
				break;
			
			case "arch":
				$desc = "Choose architecture. Available architectures configurable by admin.<br />" .
					"<br />Leave empty to automatically fill in data from the monfile if " .
					"existing.";
				
				echo "<td><label for=\"e_arch\">Architecture</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td><select name=\"arch\" size=\"1\" tabindex=\"" . ++$tabindex .
					"\" id=\"e_arch\" $ro_html1>\n";
				
				echo "<option value=\"\">-</option>\n";
				
				$archstr = $backend->readConfig( "architectures" );
				$archa = explode( ",", $archstr );
				
				foreach ( $archa as $architecture )
				{
					$architecture = helperEncodeHTML( $architecture );
					
					if ( $archsearch == $m[ "arch" ] )
						echo "<option selected=\"selected\">$architecture</option>\n";
					else
						echo "<option>$architecture</option>\n";
				}
				
				echo "</select>";
				
				break;
			
			case "assettag":
				$desc = "Free text field for an asset tag.";
				
				echo "<td><label for=\"e_assettag\">Asset Tag</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td><input type=\"text\" name=\"assettag\" size=\"80\" value=\"" .
					helperEncodeHTML( $m[ "assettag" ] ) . "\" tabindex=\"" . ++$tabindex .
					"\" id=\"e_assettag\" $ro_html2/>";
				
				break;
			
			case "ip":
				$desc = "IP address of the machine.<br />Format: <b>123.123.123.123</b><br /><br />" .
					"Leave empty to automatically fill in DNS response for hostname.";
				
				echo "<td><label for=\"e_ip\">IP</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td><input type=\"text\" name=\"ip\" size=\"80\" value=\"" .
					helperEncodeHTML( $m[ "ip" ] ) . "\" tabindex=\"" . ++$tabindex .
					"\" id=\"e_ip\" $ro_html2/>";
				
				break;
			
			case "mac":
				$desc = "Physical address (MAC) of the machine.<br />Format: <b>12:34:56:78:90:AB" .
					"</b><br /><br />Leave empty to automatically fill in data from the monfile " .
					"if existing.";
				
				echo "<td><label for=\"e_mac\">MAC Address</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td><input type=\"text\" name=\"mac\" size=\"80\" value=\"" .
					helperEncodeHTML( $m[ "mac" ] ) . "\" tabindex=\"" . ++$tabindex . "\" id=\"" .
					"e_mac\" $ro_html2/>";
				
				break;
			
			case "room":
				$desc = "Enter the location of this entry here.<br />Format: <b>Room Location/Rack#" .
					"RackUnit</b> - If the machine occupies more than one height unit, use - to " .
					"specify a range, e.g.: <i>ROOM 1/1#2-4</i>";
				
				echo "<td><label for=\"e_room\">Room</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td>";
				
				$roomstr = $backend->readConfig( "rooms" );
				
				if ( !empty( $roomstr ) )
				{
					echo "<select name=\"roomcb\" size=\"1\" tabindex=\"" . ++$tabindex . "\"\" " .
						"$ro_html1>\n";
					
					echo "<option value=\"\"></option>\n";
					
					$rooma = explode( ",", $roomstr );
					$roomfound = false;
					
					foreach ( $rooma as $room )
					{
						$room = helperEncodeHTML( $room );
						
						if ( !$roomfound && substr( $m[ "room" ], 0, strlen( $room ) + 1 ) ==
							$room . " " )
						{
							$roomfound = true;
							$m[ "room" ] = substr( $m[ "room" ], strlen( $room ) + 1 );
							
							echo "<option selected=\"selected\">$room</option>\n";
						}
						else
							echo "<option>$room</option>\n";
					}
					
					echo "</select>\n";
				}
				
				echo "<input type=\"text\" name=\"room\" size=\"50\" value=\"" . helperEncodeHTML(
					$m[ "room" ] ) . "\" tabindex=\"" . ++$tabindex . "\" id=\"e_room\" " .
					"$ro_html2/>";
				
				break;
			
			case "state":
				$desc = "Select state of the machine. What in use/free means, is based on your " .
					"interpretation. Some users use this as a kind of \"productive or test\" " .
					"field.";
				
				echo "<td>State ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td>";
				
				if ( $m[ "state" ] )
				{
					echo "<input type=\"radio\" name=\"state\" value=\"1\" checked=\"checked\" " .
						"tabindex=\"" . ++$tabindex . "\" $ro_html1/> <span style=\"color: " .
						"green\">In use</span>&nbsp;&nbsp;";
					
					echo "<input type=\"radio\" name=\"state\" value=\"0\" tabindex=\"" .
						++$tabindex . "\" $ro_html1/> <span style=\"color: orange\">Free" .
						"</span>";
				}
				else
				{
					echo "<input type=\"radio\" name=\"state\" value=\"1\" tabindex=\"" .
						++$tabindex . "\" $ro_html1/> <span style=\"color: green\">In use" .
						"</span>&nbsp;&nbsp;";
					
					echo "<input type=\"radio\" name=\"state\" value=\"0\" checked=\"checked\" " .
						"tabindex=\"" . ++$tabindex . "\" $ro_html1/> <span style=\"color: " .
						"orange\">Free</span>";
				}
				
				break;
			
			case "os":
				$desc = "Free text field for the operating system. (WBEM-enabled)";
				
				echo "<td><label for=\"e_os\">Operating System</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td><input type=\"text\" name=\"os\" size=\"80\" value=\"" .
					helperEncodeHTML( $m[ "os" ] ) . "\" tabindex=\"" . ++$tabindex . "\" id=\"" .
					"e_os\" $ro_html2/>";
				
				break;
			
			case "cpu":
				$desc = "Free text field for information about the CPU(s). (WBEM-enabled)";
				
				echo "<td><label for=\"e_cpu\">CPU(s)</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td><input type=\"text\" name=\"cpu\" size=\"80\" value=\"" .
					helperEncodeHTML( $m[ "cpu" ] ) . "\" tabindex=\"" . ++$tabindex . "\" id=\"" .
					"e_cpu\" $ro_html2/>";
				
				break;
			
			case "mem":
				$desc = "Free text field for information about the memory. (WBEM-enabled)";
				
				echo "<td><label for=\"e_mem\">Memory</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td><input type=\"text\" name=\"mem\" size=\"80\" value=\"" .
					helperEncodeHTML( $m[ "mem" ] ) . "\" tabindex=\"" . ++$tabindex . "\" id=\"" .
					"e_mem\" $ro_html2/>";
				
				break;
			
			case "disk":
				$desc = "Free text field for information about harddiscs. (WBEM-enabled)";
				
				echo "<td><label for=\"e_disk\">Disk(s)</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td><input type=\"text\" name=\"disk\" size=\"80\" value=\"" .
					helperEncodeHTML( $m[ "disk" ] ) . "\" tabindex=\"" . ++$tabindex .
					"\" id=\"e_disk\" $ro_html2/>";
				
				break;
			
			case "kernel":
				$desc = "Free text field for the used kernel. (WBEM-enabled)";
				
				echo "<td><label for=\"e_kernel\">Kernel</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td><input type=\"text\" name=\"kernel\" size=\"80\" value=\"" .
					helperEncodeHTML( $m[ "kernel" ] ) . "\" tabindex=\"" . ++$tabindex .
					"\" id=\"e_kernel\" $ro_html2/>";
				
				break;
			
			case "libc":
				$desc = "Free text field for the libc version. (WBEM-enabled)";
				
				echo "<td><label for=\"e_libc\">libc</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td><input type=\"text\" name=\"libc\" size=\"80\" value=\"" .
					helperEncodeHTML( $m[ "libc" ] ) . "\" tabindex=\"" . ++$tabindex .
					"\" id=\"e_libc\" $ro_html2/>";
				
				break;
			
			case "compiler":
				$desc = "Free text field for the compiler version. (WBEM-enabled)";
				
				echo "<td><label for=\"e_compiler\">Compiler</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td><input type=\"text\" name=\"compiler\" size=\"80\" value=\"" .
					helperEncodeHTML( $m[ "compiler" ] ) . "\" tabindex=\"" . ++$tabindex .
					"\" id=\"e_compiler\" $ro_html2/>";
				
				break;
			
			case "usedby":
				$desc = "Select up to two phpEquiMon users that are responsible concerning usage or " .
					"maintenance of this item of equipment and/or use the free text field.";
				
				$authlist = $backend->listAuthUsers();
				
				echo "<td>Used by ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td><select name=\"usedby_id1\" size=\"1\" tabindex=\"" . ++$tabindex .
					" $ro_html1\">\n";
				
				printUserComboBox( $authlist, $m[ "usedby_id1" ] );
				
				echo "</select>&nbsp;<select name=\"usedby_id2\" size=\"1\" tabindex=\"" . ++$tabindex .
					" $ro_html1\">\n";
				
				printUserComboBox( $authlist, $m[ "usedby_id2" ] );
				
				echo "</select><br />\n";
				
				echo "<input name=\"usedby\" type=\"text\" size=\"80\" value=\"" . helperEncodeHTML(
					$m[ "usedby" ] ) . "\" tabindex=\"" . ++$tabindex . "\" $ro_html2 />";
				
				break;
			
			case "expiredate":
				$desc = "Date on which the machine will expire. Leave empty to disable expiration." .
					"<br />Format: <b>YYYY-MM-DD</b>";
				
				echo "<td><label for=\"e_expiredate\">Expiration Date</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td><input type=\"text\" name=\"expiredate\" size=\"80\" value=\"" .
					helperEncodeHTML( $m[ "expiredate" ] ) . "\" tabindex=\"" . ++$tabindex .
					"\" id=\"e_expiredate\" $ro_html2/>";
				
				break;
			
			case "expirestate":
				$desc = "Select whether this record expires and whether to notify if it does.";
				
				echo "<td>Expiration State ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				if ( $m[ "expirestate" ] > 0 )
				{
					echo "</td><td><input type=\"radio\" name=\"exps\" value=\"2\" tabindex=\"" .
						++$tabindex . "\" checked=\"checked\" id=\"e_exps_2\" $ro_html1/> " .
						"<label for=\"e_exps_2\">Notify <input type=\"text\" name=\"expsd\" " .
						"size=\"1\" value=\"" . $m[ "expirestate" ] . "\" tabindex=\"" .
						++$tabindex . "\" $ro_html2/>&nbsp;days before expiration and when " .
						"expired.</label><br />";
				}
				else
				{
					echo "</td><td><input type=\"radio\" name=\"exps\" value=\"2\" tabindex=\"" .
						++$tabindex . "\" id=\"e_exps_2\" $ro_html1/> <label for=\"e_exps_2" .
						"\">Notify <input type=\"text\" name=\"expsd\" size=\"1\" value=\"" .
						"5\" tabindex=\"" . ++$tabindex . "\" $ro_html2/>&nbsp;days before " .
						"expiration and when expired.</label><br />";
				}
				
				if ( $m[ "expirestate" ] < 0 )
				{
					echo "<input type=\"radio\" name=\"exps\" value=\"1\" checked=\"checked\" " .
						"tabindex=\"" . ++$tabindex . "\" id=\"e_exps_1\"  $ro_html1/> " .
						"<label for=\"e_exps_1\">Notify when expired.</label><br />";
				}
				else
				{
					echo "<input type=\"radio\" name=\"exps\" value=\"1\" tabindex=\"" .
						++$tabindex . "\" id=\"e_exps_1\" $ro_html1/> <label for=\"e_exps_1" .
						"\">Notify when expired.</label><br />";
				}
				
				if ( !$m[ "expirestate" ] )
				{
					echo "<input type=\"radio\" name=\"exps\" value=\"0\" checked=\"checked\" " .
						"tabindex=\"" . ++$tabindex . "\" id=\"e_exps_0\" $ro_html1/> <label " .
						"for=\"e_exps_0\">Don't notify.</label>";
				}
				else
				{
					echo "<input type=\"radio\" name=\"exps\" value=\"0\" tabindex=\"" .
						++$tabindex . "\" id=\"e_exps_0\" $ro_html1/> <label for=\"e_exps_0" .
						"\">Don't notify.</label>";
				}
				
				break;
			
			case "notes":
				$desc = "Free text field for additional notices for this record.";
				
				echo "<td><label for=\"e_notes\">Notes</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td><textarea name=\"notes\" cols=\"80\" rows=\"6\" tabindex=\"" .
					++$tabindex . "\" id=\"e_notes\" $ro_html2>" . helperEncodeHTML( $m[ "notes" ]
					) . "</textarea>";
				
				break;
			
			case "rack":
				$desc = "Describe location of the machine (inside of a rack, outside of a rack or " .
					"virtual).";
				
				echo "<td>Rack ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td>";
				
				if ( $m[ "rack" ] == 1 )
				{
					echo "<input type=\"radio\" name=\"rack\" value=\"1\" checked=\"checked\" " .
						"tabindex=\"" . ++$tabindex . "\" $ro_html1/><span style=\"color: " .
						"green\">Rack</span>&nbsp;&nbsp;";
					
					echo "<input type=\"radio\" name=\"rack\" value=\"0\" tabindex=\"" .
						++$tabindex . "\" $ro_html1/><span style=\"color: blue\">Floor</span>" .
						"&nbsp;&nbsp;";
					
					echo "<input type=\"radio\" name=\"rack\" value=\"2\" tabindex=\"" .
						++$tabindex . "\" $ro_html1/><span style=\"color: orange\">Virtual" .
						"</span>";
				}
				else if ( $m[ "rack" ] == 2 )
				{
					echo "<input type=\"radio\" name=\"rack\" value=\"1\" tabindex=\"" .
						++$tabindex . "\" $ro_html1/><span style=\"color: green\">Rack</span>" .
						"&nbsp;&nbsp;";
					
					echo "<input type=\"radio\" name=\"rack\" value=\"0\" tabindex=\"" .
						++$tabindex . "\" $ro_html1/><span style=\"color: blue\">Floor</span>" .
						"&nbsp;&nbsp;";
					
					echo "<input type=\"radio\" name=\"rack\" value=\"2\" checked=\"checked\" " .
						"tabindex=\"" . ++$tabindex . "\" $ro_html1/><span style=\"color: " .
						"orange\">Virtual</span>";
				}
				else
				{
					echo "<input type=\"radio\" name=\"rack\" value=\"1\" tabindex=\"" .
						++$tabindex . "\" $ro_html1/><span style=\"color: green\">Rack</span>" .
						"&nbsp;&nbsp;";
					
					echo "<input type=\"radio\" name=\"rack\" value=\"0\" checked=\"checked\" " .
						"tabindex=\"" . ++$tabindex . "\" $ro_html1/><span style=\"color: " .
						"blue\">Floor</span>&nbsp;&nbsp;";
					
					echo "<input type=\"radio\" name=\"rack\" value=\"2\" tabindex=\"" .
						++$tabindex . "\" $ro_html1/><span style=\"color: orange\">Virtual" .
						"</span>";
				}
				
				break;
			
			case "hostsystem":
				$desc = "For virtual machines enter the hostname of the host system here, if the " .
					"machine\'s hostname does not follow the [hostsys]v[number] naming scheme.";
				
				echo "<td><label for=\"e_hostsystem\">Host System</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td><input type=\"text\" name=\"hostsystem\" size=\"80\" value=\"" .
					helperEncodeHTML( $m[ "hostsystem" ] ) . "\" tabindex=\"" . ++$tabindex .
					"\" id=\"e_hostsystem\" $ro_html2/>";
				
				break;
			
			case "mailtarget":
				$desc = "If set, send information mails concerncing this machine to this address " .
					"instead of using the mail addresses of the users from the used by field.";
				
				echo "<td><label for=\"e_mailtarget\">Mail Target</label> ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td><input type=\"text\" name=\"mailtarget\" size=\"80\" value=\"" .
					helperEncodeHTML( $m[ "mailtarget" ] ) . "\" tabindex=\"" . ++$tabindex .
					"\" id=\"e_mailtarget\" " . "$ro_html2/>";
				
				break;
			
			case "mailopts":
				$desc = "This setting controls on which events phpEquiMon will send a mail to the " .
					"users given in the used by field or to 'Mail Target', if set. Also see " .
					"the mail settings for expiring above.";
				
				echo "<td>Mail Options ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td>";
				
				if ( $m[ "mailopts" ] )
				{
					echo "<input type=\"radio\" name=\"mailopts_con\" value=\"1\" checked=\"" .
						"checked\" tabindex=\"" . ++$tabindex . "\" $ro_html1/> Mail on " .
						"connectivity changes&nbsp;&nbsp;";
					
					echo "<input type=\"radio\" name=\"mailopts_con\" value=\"0\" tabindex=\"" .
						++$tabindex . "\" $ro_html1/> Don't mail on connectivity changes";
				}
				else
				{
					echo "<input type=\"radio\" name=\"mailopts_con\" value=\"1\" tabindex=\"" .
						++$tabindex . "\" $ro_html1/> Mail on connectivity changes&nbsp;" .
						"&nbsp;";
					
					echo "<input type=\"radio\" name=\"mailopts_con\" value=\"0\" checked=\"" .
						"checked\" tabindex=\"" . ++$tabindex . "\" $ro_html1/> Don't mail " .
						"on connectivity changes";
				}
				
				break;
				break;
			
			case "lastupdate":
				$desc = "Information about last update of this record.";
				
				echo "<td>Last Update ";
				
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$theme->printTooltip( "#", "?", $desc );
				
				echo "</td><td>";
				
				if ( isset( $m[ "updateby" ] ) && isset( $m[ "lastupdate" ] ) )
				{ 
					$username = "";
					$contact = $backend->getContact( $m[ "updateby" ], $username );
					
					echo "Updated by ";
					helperPrintUserInfo( $username, $contact );
					echo " on " . date( "D M j G:i:s T Y", $m[ "lastupdate" ] );
				}
				else
					echo "Will be updated with your username and time.";
				
				break;
			
			case "buttons":
				if ( isset( $session->_settings[ "edittooltips" ] ) )
					$cols = 2;
				else
					$cols = 3;
				
				if ( $gec )
				{
					++$cols;
					
					echo "<td colspan=\"$cols\"><input type=\"submit\" name=\"submitb\" value=\"" .
						"Save " . count( $m[ "id" ] ) . " entries\" tabindex=\"" .
						++$tabindex . "\" $ro_html1/>\n";
					
					echo "<input type=\"submit\" name=\"delete\" value=\"Delete " . count( $m[
						"id" ] ) . " entries from database\" $ro_html1/>\n";
					
					echo "<input type=\"reset\" name=\"reset\" value=\"Discard changes\" " .
						"tabindex=\"" . ++$tabindex . "\" $ro_html1/>";
					
					echo "<input type=\"hidden\" name=\"start_groupedit\" value=\"yes\" />\n";
					
					foreach ( $m[ "id" ] as $g_id )
					{
						echo "<input type=\"hidden\" name=\"groupedit_$g_id\" value=\"yes\" " .
							"/>\n";
					}
				}
				else if ( isset( $m[ "id" ] ) )
				{
					echo "<td colspan=\"$cols\"><input type=\"submit\" name=\"submitb\" value=\"" .
						"Save machine\" tabindex=\"" . ++$tabindex . "\" $ro_html1/>\n";
					
					if ( !isset( $_GET[ "l" ] ) )
					{
						echo "<input type=\"submit\" name=\"submit-next\" value=\"Save &amp; " .
							"Load next\" tabindex=\"" . ++$tabindex . "\" $ro_html1/>" .
							"&nbsp;<input type=\"submit\" name=\"load-next\" value=\"" .
							"Discard changes &amp; Load next\" tabindex=\"" . ++$tabindex .
							"\" $ro_html1/>\n";
					}
					
					echo "<input type=\"reset\" name=\"reset\" value=\"Discard changes\" " .
						"tabindex=\"" . ++$tabindex . "\" $ro_html1/>\n";
					
					echo "<input type=\"submit\" name=\"submit-clone\" value=\"Clone as new\" " .
						"tabindex=\"" . ++$tabindex . "\" $ro_html1/>\n";
					
					echo "<input type=\"submit\" name=\"delete\" value=\"Delete from database\" " .
						"$ro_html1/>\n";
					
					echo "&nbsp;&nbsp;&nbsp;&nbsp;";
					
					echo "<input type=\"checkbox\" name=\"autofill_wbem\" value=\"yes\" id=\"" .
						"e_autofill_wbem\" $ro_html1/> <label for=\"e_autofill_wbem\">Try to " .
						"fill empty fields with WBEM data.</label>";
				}
				else
				{
					echo "<td colspan=\"$cols\"><input type=\"submit\" name=\"submitb\" value=\"" .
						"Add &amp; return\" tabindex=\"" . ++$tabindex . "\" $ro_html1/>\n";
					
					echo "<input type=\"submit\" name=\"submit-next\" value=\"Add &amp; clone " .
						"for next\" tabindex=\"" . ++$tabindex . "\" $ro_html1/>\n";
					
					echo "<input type=\"reset\" name=\"reset\" value=\"Discard changes\" " .
						"tabindex=\"" . ++$tabindex . "\" $ro_html1/>\n";
					
					echo "&nbsp;&nbsp;&nbsp;&nbsp;";
					
					echo "<input type=\"checkbox\" name=\"autofill_wbem\" value=\"yes\" id=\"" .
						"e_autofill_wbem\" $ro_html1/> <label for=\"e_autofill_wbem\">Try to " .
						"fill empty fields with WBEM data.</label>";
				}
				
				break;
			
			default:
				echo "Unknown row name $name!\n";
		}
		
		echo "</td>";
		
		// Additional columns
		if ( $name != "buttons" )
		{
			if ( !isset( $session->_settings[ "edittooltips" ] )  )
				echo "<td>$desc</td>";
			
			if ( $gec && $name != "hostname" )
			{
				if ( $name == "usedby" )
				{
					$conflict = isset( $gec[ "usedby" ] ) || isset( $gec[ "usedby_id1" ] ) ||
						isset( $gec[ "usedby_id2" ] );
				}
				else
					$conflict = isset( $gec[ $name ] );
				
				if ( $conflict )
				{
					echo "<td style=\"white-space: nowrap; color: red\">Groupedit: Conflicting " .
						"entries.<br /><input type=\"checkbox\" name=\"groupedit_write_" .
						"$name\" value=\"yes\" /> Force override (use carefully!)</td>";
				}
				else
				{
					echo "<td style=\"white-space: nowrap; color: green\">Groupedit: No " .
						"conflicts. <input type=\"hidden\" name=\"groupedit_write_$name\" " .
						"value=\"yes\" /></td>";
				}
			}
		}
		
		echo "</tr>\n";
	}
	
	/**
	 * Updates a database entry with POST values.
	 * @param integer $id ID of the machine to update or false to create a new.
	 * @return boolean Whether the given POST data was valid and the database operation was performed.
	 */
	function updateEntry( $id )
	{
		global $backend, $lastaddid, $session;
		
		// Update an existing record -> Fetch existing data from database
		if ( $id )
		{
			// Check authentification
			if ( !$backend->checkEditAuth( $id ) )
			{
				echo "<p class=\"err\">Lacking rights to edit id = $id.</p>\n";
				return;
			}
			
			// Fetch entry
			$entry = $backend->getList( $id );
			if ( !$entry )
			{
				echo "<p class=\"err\">Invalid ID.</p>\n";
				return;
			}
		}
		
		// Create a new record -> Start with an blank record
		else
		{
			// Only allow the user to create entries which he is also allowed to edit then
			if ( !$backend->checkAuth( 1 ) && !( !isset( $_POST[ "groupid" ] ) || $backend->checkAuth(
				$_POST[ "groupid" ] ) ) )
			{
				echo "<p class=\"err\">You need to be an admin or set the group field to one of " .
					"your groups.</p>\n";
				return;
			}
			
			$entry = array( "hostname" => "unknown",
				"groupid" => 1,
				"vendor" => NULL,
				"model" => NULL,
				"arch" => NULL,
				"assettag" => NULL,
				"ip" => NULL,
				"mac" => NULL,
				"room" => NULL,
				"os" => NULL,
				"cpu" => NULL,
				"mem" => NULL,
				"disk" => NULL,
				"kernel" => NULL,
				"libc" => NULL,
				"compiler" => NULL,
				"state" => 1,
				"usedby" => NULL,
				"usedby_id1" => NULL,
				"usedby_id2" => NULL,
				"expiredate" => NULL,
				"expirestate" => 0,
				"notes" => NULL,
				"rack" => 1,
				"hostsystem" => NULL,
				"mailtarget" => NULL,
				"mailopts" => 1 );
		}
		
		// Change record values based on POST parameters
		if ( isset( $_POST[ "hostname" ] ) ) $entry[ "hostname" ] = $_POST[ "hostname" ];
		
		if ( isset( $_POST[ "groupid" ] ) )
			$entry[ "groupid" ] = empty( $_POST[ "groupid" ] ) ? 1 : $_POST[ "groupid" ];
		
		if ( isset( $_POST[ "vendor" ] ) )
			$entry[ "vendor" ] = empty( $_POST[ "vendor" ] ) ? NULL : $_POST[ "vendor" ];
		
		if ( isset( $_POST[ "model" ] ) )
			$entry[ "model" ] = empty( $_POST[ "model" ] ) ? NULL : $_POST[ "model" ];
		
		if ( isset( $_POST[ "arch" ] ) )
			$entry[ "arch" ] = empty( $_POST[ "arch" ] ) ? NULL : $_POST[ "arch" ];
		
		if ( isset( $_POST[ "assettag" ] ) )
			$entry[ "assettag" ] = empty( $_POST[ "assettag" ] ) ? NULL : $_POST[ "assettag" ];
		
		if ( isset( $_POST[ "os" ] ) )
			$entry[ "os" ] = empty( $_POST[ "os" ] ) ? NULL : $_POST[ "os" ];
		
		if ( isset( $_POST[ "cpu" ] ) )
			$entry[ "cpu" ] = empty( $_POST[ "cpu" ] ) ? NULL : $_POST[ "cpu" ];
		
		if ( isset( $_POST[ "mem" ] ) )
			$entry[ "mem" ] = empty( $_POST[ "mem" ] ) ? NULL : $_POST[ "mem" ];
		
		if ( isset( $_POST[ "disk" ] ) )
			$entry[ "disk" ] = empty( $_POST[ "disk" ] ) ? NULL : $_POST[ "disk" ];
		
		if ( isset( $_POST[ "kernel" ] ) )
			$entry[ "kernel" ] = empty( $_POST[ "kernel" ] ) ? NULL : $_POST[ "kernel" ];
		
		if ( isset( $_POST[ "libc" ] ) )
			$entry[ "libc" ] = empty( $_POST[ "libc" ] ) ? NULL : $_POST[ "libc" ];
		
		if ( isset( $_POST[ "compiler" ] ) )
			$entry[ "compiler" ] = empty( $_POST[ "compiler" ] ) ? NULL : $_POST[ "compiler" ];
		
		if ( isset( $_POST[ "state" ] ) && ( $_POST[ "state" ] == 0 || $_POST[ "state" ] == 1 ) )
			$entry[ "state" ] = $_POST[ "state" ];
		
		if ( isset( $_POST[ "usedby" ] ) )
			$entry[ "usedby" ] = empty( $_POST[ "usedby" ] ) ? NULL : $_POST[ "usedby" ];
		
		if ( isset( $_POST[ "usedby_id1" ] ) && helperIsDigit( $_POST[ "usedby_id1" ] ) )
			$entry[ "usedby_id1" ] = $_POST[ "usedby_id1" ];
		
		if ( isset( $_POST[ "usedby_id2" ] ) && helperIsDigit( $_POST[ "usedby_id2" ] ) )
			$entry[ "usedby_id2" ] = $_POST[ "usedby_id2" ];
		
		if ( isset( $_POST[ "expiredate" ] ) )
			$entry[ "expiredate" ] = empty( $_POST[ "expiredate" ] ) ? NULL : $_POST[ "expiredate" ];
		
		if ( isset( $_POST[ "notes" ] ) )
			$entry[ "notes" ] = empty( $_POST[ "notes" ] ) ? NULL : $_POST[ "notes" ];
		
		if ( isset( $_POST[ "rack" ] ) && ( $_POST[ "rack" ] == 0 || $_POST[ "rack" ] == 1 || $_POST[ "rack" ]
			== 2 ) )
			$entry[ "rack" ] = $_POST[ "rack" ];
		
		if ( isset( $_POST[ "hostsystem" ] ) )
			$entry[ "hostsystem" ] = empty( $_POST[ "hostsystem" ] ) ? NULL : $_POST[ "hostsystem" ];
		
		if ( isset( $_POST[ "mailtarget" ] ) )
			$entry[ "mailtarget" ] = empty( $_POST[ "mailtarget" ] ) ? NULL : $_POST[ "mailtarget" ];
		
		if ( isset( $_POST[ "mailopts_con" ] ) && ( $_POST[ "mailopts_con" ] == 0 || $_POST[ "mailopts_con" ]
			== 1 ) )
			$entry[ "mailopts" ] = $_POST[ "mailopts_con" ];
		
		// Set IP
		if ( isset( $_POST[ "ip" ] ) )
		{
			if ( empty( $_POST[ "ip" ] ) )
			{
				$dnsip = gethostbyname( $_POST[ "hostname" ] );
				if ( $dnsip != $_POST[ "hostname" ] )
					$entry[ "ip" ] = $dnsip;
			}
			else
				$entry[ "ip" ] = $_POST[ "ip" ];
		}
		
		// Read monfile if existing
		$mondata = helperReadMonfile( $_POST[ "hostname" ] );
		
		// Set architecture
		if ( isset( $_POST[ "arch" ] ) )
		{
			if ( empty( $_POST[ "arch" ] ) )
			{
				if ( isset( $mondata[ "arch" ] ) )
					$entry[ "arch" ] = $mondata[ "arch" ];
			}
			else
				$entry[ "mac" ] = $_POST[ "mac" ];
		}
		
		// Set MAC
		if ( isset( $_POST[ "mac" ] ) )
		{
			if ( empty( $_POST[ "mac" ] ) )
			{
				if ( isset( $mondata[ "net_eth0" ][ "mac" ] ) )
					$entry[ "mac" ] = $mondata[ "net_eth0" ][ "mac" ];
				else
					$entry[ "mac" ] = NULL;
			}
			else
				$entry[ "mac" ] = $_POST[ "mac" ];
		}
		
		// Set room
		if ( isset( $_POST[ "room" ] ) && isset( $_POST[ "roomcb" ] ) )
		{
			if ( empty( $_POST[ "room" ] ) && empty( $_POST[ "roomcb" ] ) )
				$entry[ "room" ] = NULL;
			else
				$entry[ "room" ] = trim( $_POST[ "roomcb" ] . " " . $_POST[ "room" ] );
		}
		
		// Set expiration fields
		if ( isset( $_POST[ "exps" ] ) && $_POST[ "exps" ] )
		{
			if ( $_POST[ "exps" ] == 2 )
			{
				if ( isset( $_POST[ "expsd" ] ) )
					$entry[ "expirestate" ] = $_POST[ "expsd" ];
			}
			else if ( $_POST[ "exps" ] == 1 )
				$entry[ "expirestate" ] = -1;
		}
		else
			$entry[ "expirestate" ] = 0;
		
		// Autofill empty fields with WBEM data if desired by user
		if ( isset( $_POST[ "autofill_wbem" ] ) && $_POST[ "autofill_wbem" ] == "yes" )
		{
			$wbem = helperParseWBEMString( helperQueryWBEM( $entry[ "hostname" ] ) );
			
			if ( !$entry[ "assettag" ] && !empty( $wbem[ "assettag" ] ) )
				$entry[ "assettag" ] = $wbem[ "assettag" ];
			
			if ( !$entry[ "os" ] && !empty( $wbem[ "os" ] ) )
				$entry[ "os" ] = $wbem[ "os" ];
			
			if ( !$entry[ "cpu" ] && !empty( $wbem[ "cpu" ] ) )
				$entry[ "cpu" ] = $wbem[ "cpu" ];
			
			if ( !$entry[ "mem" ] && !empty( $wbem[ "mem" ] ) )
				$entry[ "mem" ] = $wbem[ "mem" ];
			
			if ( !$entry[ "disk" ] && !empty( $wbem[ "disk" ] ) )
				$entry[ "disk" ] = $wbem[ "disk" ];
			
			if ( !$entry[ "kernel" ] && !empty( $wbem[ "kernel" ] ) )
				$entry[ "kernel" ] = $wbem[ "kernel" ];
		}
		
		// Check entry for validity
		if ( isset( $id ) )
		{
			if ( !validateEntry( $entry, $id ) )
				return false;
		}
		else if ( !validateEntry( $entry, false ) )
			return false;
		
		// Perform database operation
		if ( $id )
		{
			// Edit existing entry
			$backend->editEntry( $id, $entry );
			$backend->logEvent( $session->getID(), "Edit " . $entry[ "hostname" ] . " (id = " . $id .
				")." );
			
			echo "<p class=\"infobox\">" . $entry[ "hostname" ] . " updated.</p>\n";
		}
		else
		{
			// Add new entry
			$lastaddid = $backend->addEntry( $entry );
			$backend->logEvent( $session->getID(), "Added " . $entry[ "hostname" ] . " to the database " .
				"(id = $lastaddid)." );
			
			echo "<p class=\"infobox\">" . $entry[ "hostname" ] . " added.</p>\n";
		}
		
		// Regenerate DHCP configuration file if needed
		if ( !empty( $entry[ "ip" ] ) )
		{
			$pxeservers = $backend->listAllPXE();
			
			foreach ( $pxeservers as $pxe )
			{
				// Have we found the responsible PXE server for this subnet?
				if ( helperCheckSubnet( $entry[ "ip" ], $pxe[ "iprange" ] ) )
				{
					// Connect
					$pxecon = new PXEController( $pxe[ "address" ] );
					
					if ( $pxecon->getVersion() > 0 )
					{
						$dhcpcfg = helperGenerateDHCPConfig( $pxe[ "iprange" ] );
						$pxecon->writeConfiguration( $dhcpcfg );
					}
					
					break;
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Helper function for group editing.
	 */
	function setTemplate( &$entry, $field )
	{
		if ( isset( $_POST[ $field ] ) && isset( $_POST[ "groupedit_write_$field" ] ) )
			$entry[ $field ] = $_POST[ $field ];
	}
	
	/**
	 * Helper function for group editing.
	 */
	function groupEditMerge( &$master, &$entry, $field, &$gec )
	{
		if ( $master[ $field ] != $entry[ $field ] )
			$gec[ $field ] = true;
		
		$master[ $field ] = $entry[ $field ];
	}
	
	/**
	 * Groupedits an entry, i.e. overwrites all fields with POST data for which the groupedit_write_x flag is set.
	 * @param integer $id ID of the entry.
	 */
	function groupeditEntry( $id )
	{
		global $backend, $session;
		
		// Check authentification
		if ( !$backend->checkEditAuth( $id ) )
		{
			echo "<p class=\"err\">Lacking rights to edit id = $id.</p>\n";
			return;
		}
		
		// Fetch old entry
		$entry = $backend->getList( $id );
		if ( !$entry )
		{
			echo "<p class=\"err\">Invalid ID.</p>\n";
			return;
		}
		
		// Create template record containing all changes to be done
		$entry = array();
		
		setTemplate( $entry, "groupid" );
		setTemplate( $entry, "vendor" );
		setTemplate( $entry, "model" );
		setTemplate( $entry, "arch" );
		setTemplate( $entry, "room" );
		setTemplate( $entry, "state" );
		setTemplate( $entry, "os" );
		setTemplate( $entry, "cpu" );
		setTemplate( $entry, "mem" );
		setTemplate( $entry, "disk" );
		setTemplate( $entry, "kernel" );
		setTemplate( $entry, "libc" );
		setTemplate( $entry, "compiler" );
		
		if ( isset( $_POST[ "usedby" ] ) && isset( $_POST[ "usedby_id1" ] ) && isset( $_POST[ "usedby_id2" ] )
			&& isset( $_POST[ "groupedit_write_usedby" ] ) )
		{
			$entry[ "usedby" ] = $_POST[ "usedby" ];
			$entry[ "usedby_id1" ] = $_POST[ "usedby_id1" ];
			$entry[ "usedby_id2" ] = $_POST[ "usedby_id2" ];
		}
		
		setTemplate( $entry, "expiredate" );
		setTemplate( $entry, "expirestate" );
		setTemplate( $entry, "notes" );
		setTemplate( $entry, "rack" );
		setTemplate( $entry, "hostsystem" );
		setTemplate( $entry, "mailtarget" );
		setTemplate( $entry, "mailopts" );
		
		// Edit entry
		$backend->editEntry( $id, $entry, $session->getUsername() );
		$backend->logEvent( $session->getID(), "Groupedit id = $id." );
	}
	
	// ID given?
	if ( isset( $_GET[ "i" ] ) && helperIsDigit( $_GET[ "i" ] ) && !isset( $_POST[ "submit-clone" ] ) )
		$id = $_GET[ "i" ];
	
	// Start group editing?
	else if ( isset( $_POST[ "start_groupedit" ] ) )
	{
		$groupedit_ids = array();
		
		// Read all given POST values
		foreach ( $_POST as $postentry => $postvalue )
		{
			if ( $postvalue == "yes" && preg_match( "/groupedit_[[:digit:]]+/", $postentry ) )
			{
				// Extract ID from POST value
				$g_id = substr( $postentry, 10 );
				
				if ( helperIsDigit( $g_id ) )
					array_push( $groupedit_ids, $g_id );
			}
		}
		
		// Make sure we have enough machines for group editing to be sensible
		if ( count( $groupedit_ids ) < 2 )
		{
			echo "<p class=\"err\">You need to select more machines for group editing.</p>\n";
			return;
		}
	}
	
	// Actually change something?
	if ( isset( $_POST[ "submitb" ] ) || isset( $_POST[ "submit-next" ] ) )
	{
		$lastaddid = 0;  // hack
		
		// Normal edit?
		if ( isset( $id ) )
			$success = updateEntry( $id );
		
		// Groupedit?
		else if ( isset( $groupedit_ids ) )
		{
			foreach ( $groupedit_ids as $g_id )
				groupeditEntry( $g_id );
			
			echo "<p class=\"infobox\">" . count( $groupedit_ids ) . " machines updated.</p>\n";
			$success = true;
		}
		
		// Add new machine?
		else
			$success = updateEntry( false );
		
		// Display either next machine or the index page
		if ( $success )
		{
			if ( isset( $_POST[ "submit-next" ] ) )
			{
				if ( isset( $_POST[ "nextedit" ] ) && helperIsDigit( $_POST[ "nextedit" ] ) )
					$id = $_POST[ "nextedit" ];
				else
				{
					unset( $id );
					$_GET[ "c" ] = $lastaddid;
				}
			}
			else
			{
				pageOutputList();
				return;
			}
		}
	}
	
	// Load next machine?
	else if ( isset( $_POST[ "load-next" ] ) && isset( $_POST[ "nextedit" ] ) && helperIsDigit( $_POST[ "nextedit" ]
		) )
	{
		echo "<p class=\"infobox\">Changes discarded. Next data record loaded for editing.</p>\n";
		$id = $_POST[ "nextedit" ];
	}
	
	// Start in readonly mode
	$ro = true;
	
	// "Normal" edit: Fetch record from database
	if ( isset( $id ) )
	{
		// Fetch record from database
		$entry = $backend->getList( $id );
		if ( !$entry )
		{
			echo "<p class=\"err\">Record $id not found in database.</p>\n";
			return;
		}
		
		// Check authentification
		if ( $backend->checkEditAuth( $id ) )
		{
			// Delete machine?
			if ( isset( $_GET[ "delete" ] ) || isset( $_POST[ "delete" ] ) )
			{
				// Delete entry
				$backend->deleteEntry( $id );
				
				// Write log
				$backend->logEvent( $session->getID(), "Deleted " . $entry[ "hostname" ] . " (id = " .
					$id . ")." );
				
				echo "<p class=\"infobox\">Machine " . helperEncodeHTML( $entry[ "hostname" ] ) .
					" deleted from database.</p>\n";
				
				pageOutputList();
				
				return;
			}
			
			$ro = false;
		}
		
		// Save id in entry (for helper functions later which cannot access $id)
		$entry[ "id" ] = $id;
	}
	
	// Fetch multiple records for groupediting
	else if ( isset( $groupedit_ids ) )
	{
		$groupedit_conflicts = array( "groupedit" => true );  // Hack: Make sure the array evalutes to true ;-)
		
		$ro = false;
		
		foreach ( $groupedit_ids as $g_id )
		{
			// Check authentification
			if ( !$backend->checkEditAuth( $g_id ) )
			{
				echo "<p class=\"err\">Lacking rights to edit id = $g_id.</p>\n";
				return;
			}
			
			// Fetch entry
			$m_entry = $backend->getList( $g_id );
			if ( !$m_entry )
			{
				echo "<p class=\"err\">Invalid ID.</p>\n";
				return;
			}
			
			// Delete machine?
			if ( isset( $_POST[ "delete" ] ) )
			{
				// Delete entry
				$backend->deleteEntry( $g_id );
				
				// Write log
				$backend->logEvent( $session->getID(), "Deleted " . $m_entry[ "hostname" ] .
					" (id = " . $g_id . ")." );
			}
			
			// The master record contains all information that is common for all records to edit
			if ( isset( $entry ) )
			{
				array_push( $entry[ "hostname" ], $m_entry[ "hostname" ] );
				
				groupEditMerge( $entry, $m_entry, "groupid", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "vendor", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "model", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "arch", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "room", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "state", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "os", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "cpu", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "mem", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "disk", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "kernel", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "libc", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "compiler", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "usedby", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "usedby_id1", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "usedby_id2", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "expiredate", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "expirestate", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "notes", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "rack", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "hostsystem", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "mailtarget", $groupedit_conflicts );
				groupEditMerge( $entry, $m_entry, "mailopts", $groupedit_conflicts );
				
				array_push( $entry[ "id" ], $g_id );
			}
			
			// For the first machine: Initialize master record
			else
			{
				$entry = $m_entry;
				
				unset( $entry[ "hostname" ] );
				$entry[ "hostname" ] = array( $m_entry[ "hostname" ] );
				
				$entry[ "id" ] = array( $g_id );
			}
		}
		
		// If we performed a group delete, we are finished here
		if ( isset( $_POST[ "delete" ] ) )
		{
			echo "<p class=\"infobox\">Group delete finished.</p>\n";
			
			pageOutputList();
			
			return;
		}
	}
	
	// Add new entry
	else
	{
		// Clone existing machine?
		if ( ( isset( $_GET[ "c" ] ) && helperIsDigit( $_GET[ "c" ] ) ) )
		{
			// Fetch record from database
			$entry = $backend->getList( $_GET[ "c" ] );
			if ( !$entry )
			{
				echo "<p class=\"err\">Record " . $_GET[ "c" ] . " not found in database.</p>\n";
				return;
			}
			
			// Save hostnames of source machine for header
			$clonehost = $entry[ "hostname" ];
			
			// Clear fields that should not be cloned
			unset( $entry[ "id" ] );
			$entry[ "hostname" ] = "";
			$entry[ "assettag" ] = "";
			$entry[ "ip" ] = "";
			$entry[ "mac" ] = "";
		}
		
		// Start with empty fields
		else
		{
			// Let's start with an empty record
			$entry = array( "hostname" => "",
				"groupid" => 1,
				"vendor" => "na",
				"model" => "",
				"arch" => "na",
				"assettag" => "",
				"ip" => "",
				"mac" => "",
				"room" => "",
				"os" => "",
				"cpu" => "",
				"mem" => "",
				"disk" => "",
				"kernel" => "",
				"libc" => "",
				"compiler" => "",
				"state" => 1,
				"usedby" => "",
				"usedby_id1" => 0,
				"usedby_id2" => 0,
				"expiredate" => "",
				"expirestate" => "",
				"notes" => "",
				"rack" => 1,
				"hostsystem" => "",
				"mailtarget" => "",
				"mailopts" => 1 );
			
			// Fill in hostname when called by the "find free hostnames" feature
			if ( isset( $_GET[ "fh" ] ) )
				$entry[ "hostname" ] = $_GET[ "fh" ];
		}
		
		// Admins may create new entries
		if ( $backend->checkAuth( 1 ) )
			$ro = false;
		
		// User is no admin, is he logged in at all?
		else if ( $session->getID() )
		{
			// Check whether user has at least one group?
			$usergroups = $backend->listAuthGroupMemberships( $session->getID() );
			
			if ( empty( $usergroups ) )
			{
				echo "<p class=\"err\">You need to be in at least one group to create new database " .
					"records.</p>\n";
			}
			else
				$ro = false;
		}
		
		// Not logged in => No permissions.
		else
			echo "<p class=\"err\">You need to login to create new database records.</p>\n";
	}
	
	// Header line
	if ( isset( $groupedit_ids ) )
		echo "<h2>Groupedit " . count( $groupedit_ids ) . " entries</h2>\n";
	else if ( isset( $id ) )
		echo "<h2>Edit " . helperEncodeHTML( $entry[ "hostname" ] ) . "</h2>\n";
	else if ( isset( $clonehost ) )
		echo "<h2>Cloning " . helperEncodeHTML( $clonehost ) . "</h2>\n";
	else
		echo "<h2>Add entry</h2>\n";
	
	// Start form
	if ( isset( $id ) )
	{
		echo "<form action=\"?a=e&amp;i=" . $id . "#" . helperEncodeHTML( $entry[ "hostname" ] ) .
			"\" method=\"post\" id=\"focusform\">\n";
	}
	else
		echo "<form action=\"?a=e\" method=\"post\" id=\"focusform\">\n";
	
	// Start rendering the table
	echo "<table class=\"editlist\">\n";
	
	// Show notice if readonly is enabled
	if ( $ro )
	{
		echo rowHighlight() . "<td colspan=\"3\" style=\"color: red\">Lacking permissions. Running in " .
			"readonly mode!</td></tr>\n";
	}
	
	// Show information for help
	if ( isset( $session->_settings[ "edittooltips" ] ) )
		echo rowHighlight() . "<td colspan=\"2\">Hover the question marks for help.</td></tr>\n";
	
	if ( !isset( $groupedit_conflicts ) )
		$groupedit_conflicts = false;
	
	// Print edit rows
	printEditRow( $entry, "hostname", $ro, $groupedit_conflicts );
	printEditRow( $entry, "groupid", $ro, $groupedit_conflicts );
	printEditRow( $entry, "vendor", $ro, $groupedit_conflicts );
	printEditRow( $entry, "model", $ro, $groupedit_conflicts );
	printEditRow( $entry, "arch", $ro, $groupedit_conflicts );
	
	if ( !isset( $groupedit_ids ) )
	{
		printEditRow( $entry, "assettag", $ro, $groupedit_conflicts );
		printEditRow( $entry, "ip", $ro, $groupedit_conflicts );
		printEditRow( $entry, "mac", $ro, $groupedit_conflicts );
	}
	
	printEditRow( $entry, "room", $ro, $groupedit_conflicts );
	printEditRow( $entry, "state", $ro, $groupedit_conflicts );
	printEditRow( $entry, "os", $ro, $groupedit_conflicts );
	printEditRow( $entry, "cpu", $ro, $groupedit_conflicts );
	printEditRow( $entry, "mem", $ro, $groupedit_conflicts );
	printEditRow( $entry, "disk", $ro, $groupedit_conflicts );
	printEditRow( $entry, "kernel", $ro, $groupedit_conflicts );
	printEditRow( $entry, "libc", $ro, $groupedit_conflicts );
	printEditRow( $entry, "compiler", $ro, $groupedit_conflicts );
	printEditRow( $entry, "usedby", $ro, $groupedit_conflicts );
	printEditRow( $entry, "expiredate", $ro, $groupedit_conflicts );
	printEditRow( $entry, "expirestate", $ro, $groupedit_conflicts );
	printEditRow( $entry, "notes", $ro, $groupedit_conflicts );
	printEditRow( $entry, "rack", $ro, $groupedit_conflicts );
	printEditRow( $entry, "hostsystem", $ro, $groupedit_conflicts );
	printEditRow( $entry, "mailtarget", $ro, $groupedit_conflicts );
	printEditRow( $entry, "mailopts", $ro, $groupedit_conflicts );
	printEditRow( $entry, "lastupdate", $ro, $groupedit_conflicts );
	printEditRow( $entry, "buttons", $ro, $groupedit_conflicts );
	
	echo "</table>\n";
	
	// If editing from the index page ($_GET[ "l" ] not set), display the successors of this machine below
	if ( isset( $id ) && !$ro && !isset( $_GET[ "l" ] )  )
	{
		$nextedit = pageOutputList( $id );
		
		echo "<fieldset style=\"display: none\"><input type=\"hidden\" name=\"nextedit\" value=\"$nextedit\" " .
			"/></fieldset>\n";
	}
	
	echo "</form>\n";
}

?>
