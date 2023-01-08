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
 * Outputs the basic index table that shows the machines. Per default all machines in the database are shown but the
 * behavior can be changed with quite some parameters that the browser hands via $_GET to the script.
 * @param integer $editid If set, only the entry with $editid and its four successors are shown. This is used when
 * displaying the edit form.
 * @return If $editid was set, the parameter of the next machine in the list after $editid. This is used for
 * implementation of "Edit Next".
 */
function pageOutputList( $editid = 0 )
{
	global $backend, $session;
	
	/**
	 * Prints the index control panel allowing searching, filtering settings, paging settings etc.
	 */
	function printIndexControlPanel()
	{
		global $backend, $session;
		
		echo "<form action=\"?\" method=\"post\" id=\"indexcontrol\" name=\"indexcontrol\">\n";
		
		echo "<table class=\"listhead\"><tr><td rowspan=\"3\">\n";
		echo "<table><tr><td class=\"noborder\">";
		
		echo "<input type=\"hidden\" name=\"start\" value=\"0\" />\n";
		
		if ( $_SESSION[ "showmachines" ] == "all" )
		{
			echo "<input type=\"radio\" name=\"sh\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"all\" checked=\"checked\" />Show all\n";
			echo "<input type=\"radio\" name=\"sh\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"own\" />Show only own\n";
			echo "<input type=\"radio\" name=\"sh\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"edit\" />Show only editable\n";
		}
		else if ( $_SESSION[ "showmachines" ] == "own" )
		{
			echo "<input type=\"radio\" name=\"sh\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"all\" />Show all\n";
			echo "<input type=\"radio\" name=\"sh\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"own\" checked=\"checked\" />Show only own\n";
			echo "<input type=\"radio\" name=\"sh\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"edit\" />Show only editable\n";
		}
		else  // edit
		{
			echo "<input type=\"radio\" name=\"sh\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"all\" />Show all\n";
			echo "<input type=\"radio\" name=\"sh\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"own\" />Show only own\n";
			echo "<input type=\"radio\" name=\"sh\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"edit\" checked=\"checked\" />Show only editable\n";
		}
		
		echo "</td><td class=\"noborder\">\n";
		
		if ( $_SESSION[ "groupvirtual" ] == "yes" )
		{
			echo "<input type=\"radio\" name=\"g\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"yes\" checked=\"checked\" />Group virtual around physical\n";
			echo "<input type=\"radio\" name=\"g\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"no\" />Treat virtual like physical\n";
			echo "<input type=\"radio\" name=\"g\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"hide\" />Hide virtual\n";
		}
		else if ( $_SESSION[ "groupvirtual" ] == "no" )
		{
			echo "<input type=\"radio\" name=\"g\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"yes\" />Group virtual around physical\n";
			echo "<input type=\"radio\" name=\"g\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"no\" checked=\"checked\" />Treat virtual like physical\n";
			echo "<input type=\"radio\" name=\"g\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"hide\" />Hide virtual\n";
		}
		else  // hide
		{
			echo "<input type=\"radio\" name=\"g\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"yes\" />Group virtual around physical\n";
			echo "<input type=\"radio\" name=\"g\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"no\" />Treat virtual like physical\n";
			echo "<input type=\"radio\" name=\"g\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"hide\" checked=\"checked\" />Hide virtual\n";
		}
		
		echo "</td></tr>\n";
		echo "<tr><td class=\"noborder\">\n";
		
		if ( $_SESSION[ "showequipment" ] )
		{
			echo "<input type=\"radio\" name=\"e\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"yes\" checked=\"checked\" />Show all equipment\n";
			echo "<input type=\"radio\" name=\"e\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"no\" />Show only servers\n";
		}
		else
		{
			echo "<input type=\"radio\" name=\"e\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"yes\" />Show all equipment\n";
			echo "<input type=\"radio\" name=\"e\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"no\" checked=\"checked\" />Show only servers\n";
		}
		
		echo "</td><td class=\"noborder\">\n";
		
		if ( $_SESSION[ "onlinefilter" ] == "online" )
		{
			echo "<input type=\"radio\" name=\"o\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"all\" />Show all\n";
			echo "<input type=\"radio\" name=\"o\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"online\" checked=\"checked\" />Show online\n";
			echo "<input type=\"radio\" name=\"o\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"offline\" />Show offline\n";
			echo "<input type=\"radio\" name=\"o\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"dead\" />Show dead\n";
		}
		else if ( $_SESSION[ "onlinefilter" ] == "offline" )
		{
			echo "<input type=\"radio\" name=\"o\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"all\" />Show all\n";
			echo "<input type=\"radio\" name=\"o\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"online\" />Show online\n";
			echo "<input type=\"radio\" name=\"o\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"offline\" checked=\"checked\" />Show offline\n";
			echo "<input type=\"radio\" name=\"o\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"dead\" />Show dead\n";
		}
		else if ( $_SESSION[ "onlinefilter" ] == "dead" )
		{
			echo "<input type=\"radio\" name=\"o\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"all\" />Show all\n";
			echo "<input type=\"radio\" name=\"o\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"online\" />Show online\n";
			echo "<input type=\"radio\" name=\"o\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"offline\" />Show offline\n";
			echo "<input type=\"radio\" name=\"o\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"dead\" checked=\"checked\" />Show dead\n";
		}
		else  // "all"
		{
			echo "<input type=\"radio\" name=\"o\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"all\" checked=\"checked\" />Show all\n";
			echo "<input type=\"radio\" name=\"o\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"online\" />Show online\n";
			echo "<input type=\"radio\" name=\"o\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"offline\" />Show offline\n";
			echo "<input type=\"radio\" name=\"o\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"dead\" />Show dead\n";
		}
		
		echo "</td></tr></table>\n";
		echo "</td><td>";
		
		if ( empty( $_SESSION[ "search" ] ) )
		{
			echo "Search in all text fields:<br />\n";
			echo "<input name=\"sr\" type=\"text\" value=\"\" size=\"30\" />\n";
		}
		else
		{
			echo "Searched for \"" . helperEncodeHTML( $_SESSION[ "search" ] ) . "\".<br />\n";
			echo "<input name=\"sr\" type=\"text\" value=\"" . helperEncodeHTML( $_SESSION[ "search" ] ) .
				"\" size=\"30\" />\n";
		}
		
		echo "<input type=\"submit\" name=\"submit-search\" value=\"Search!\" />\n";
		
		echo "</td></tr><tr><td>\n";
		
		if ( $_SESSION[ "extendedfilter" ] )
		{
			echo "<input type=\"radio\" name=\"xf\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"no\" />Simple filter&nbsp;&nbsp;\n";
			echo "<input type=\"radio\" name=\"xf\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"yes\" checked=\"checked\" />Extended filter\n";
		}
		else
		{
			echo "<input type=\"radio\" name=\"xf\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"no\" checked=\"checked\" />Simple filter&nbsp;&nbsp;\n";
			echo "<input type=\"radio\" name=\"xf\" onchange=\"document.indexcontrol.submit();\" " .
				"value=\"yes\" />Extended filter\n";
		}
		
		echo "</td></tr><tr><td>\n";
		
		echo "<input type=\"submit\" name=\"resetfilter\" value=\"Reset all filter settings\" />\n";
		
		echo "</td></tr>\n";
		
		if ( $_SESSION[ "extendedfilter" ] )
		{
			echo "<tr><td colspan=\"2\">\n";
			
			echo "Vendor:\n";
			
			foreach ( $_SESSION[ "xf_vendor" ] as $vendor => $enabled )
			{
				$vendor = helperEncodeHTML( $vendor );
				
				if ( $enabled )
				{
					echo "<input type=\"checkbox\" name=\"xf_vendor_$vendor\" value=\"yes\" " .
						"checked=\"checked\" />$vendor&nbsp;&nbsp;\n";
				}
				else
				{
					echo "<input type=\"checkbox\" name=\"xf_vendor_$vendor\" value=\"yes\" />" .
						"$vendor&nbsp;&nbsp;\n";
				}
			}
			
			echo "<a href=\"javascript:xfVendorsAll()\">all</a>\n";
			echo "<a href=\"javascript:xfVendorsNone()\">none</a>\n";
			
			echo "<br />\n";
			echo "Architecture:\n";
			
			foreach ( $_SESSION[ "xf_arch" ] as $arch => $enabled )
			{
				$arch = helperEncodeHTML( $arch );
				
				if ( $enabled )
				{
					echo "<input type=\"checkbox\" name=\"xf_arch_$arch\" value=\"yes\" " .
						"checked=\"checked\" />$arch&nbsp;&nbsp;\n";
				}
				else
				{
					echo "<input type=\"checkbox\" name=\"xf_arch_$arch\" value=\"yes\" />$arch" .
						"&nbsp;&nbsp;\n";
				}
			}
			
			echo "<a href=\"javascript:xfArchsAll()\">all</a>\n";
			echo "<a href=\"javascript:xfArchsNone()\">none</a>\n";
			
			echo "<br />\n";
			echo "Room:\n";
			
			foreach ( $_SESSION[ "xf_room" ] as $room => $enabled )
			{
				$room = helperEncodeHTML( $room );
				
				if ( $enabled )
				{
					echo "<input type=\"checkbox\" name=\"xf_room_$room\" value=\"yes\" checked=" .
						"\"checked\" />$room&nbsp;&nbsp;\n";
				}
				else
				{
					echo "<input type=\"checkbox\" name=\"xf_room_$room\" value=\"yes\" />$room" .
						"&nbsp;&nbsp;\n";
				}
			}
			
			echo "<br />\n";
			echo "State:\n";
			
			if ( $_SESSION[ "xf_state" ] == "inuse" )
			{
				echo "<input type=\"radio\" name=\"xf_state\" value=\"all\" />Show all\n";
				echo "<input type=\"radio\" name=\"xf_state\" value=\"inuse\" checked=\"checked\" />" .
					"In use\n";
				echo "<input type=\"radio\" name=\"xf_state\" value=\"free\" />Free\n";
			}
			else if ( $_SESSION[ "xf_state" ] == "free" )
			{
				echo "<input type=\"radio\" name=\"xf_state\" value=\"all\" />Show all\n";
				echo "<input type=\"radio\" name=\"xf_state\" value=\"inuse\" />In use\n";
				echo "<input type=\"radio\" name=\"xf_state\" value=\"free\" checked=\"checked\" />" .
					"Free\n";
			}
			else  // $_SESSION[ "xf_state" ] == "all"
			{
				echo "<input type=\"radio\" name=\"xf_state\" value=\"all\" checked=\"checked\" />" .
					"Show all\n";
				echo "<input type=\"radio\" name=\"xf_state\" value=\"inuse\" />In use\n";
				echo "<input type=\"radio\" name=\"xf_state\" value=\"free\" />Free\n";
			}
			
			echo "<br />\n";
			echo "Rack:\n";
			
			if ( $_SESSION[ "xf_rack" ][ "rack" ] )
			{
				echo "<input type=\"checkbox\" name=\"xf_rack_rack\" value=\"yes\" checked=" .
					"\"checked\" />Rack&nbsp;&nbsp;\n";
			}
			else
			{
				echo "<input type=\"checkbox\" name=\"xf_rack_rack\" value=\"yes\" />Rack&nbsp;" .
					"&nbsp;\n";
			}
			
			if ( $_SESSION[ "xf_rack" ][ "floor" ] )
			{
				echo "<input type=\"checkbox\" name=\"xf_rack_floor\" value=\"yes\" checked=" .
					"\"checked\" />Floor&nbsp;&nbsp;\n";
			}
			else
			{
				echo "<input type=\"checkbox\" name=\"xf_rack_floor\" value=\"yes\" />Floor&nbsp;" .
					"&nbsp;\n";
			}
			
			if ( $_SESSION[ "xf_rack" ][ "virtual" ] )
			{
				echo "<input type=\"checkbox\" name=\"xf_rack_virtual\" value=\"yes\" checked=" .
					"\"checked\" />Virtual\n";
			}
			else
			{
				echo "<input type=\"checkbox\" name=\"xf_rack_virtual\" value=\"yes\" />Virtual\n";
			}
			echo "<br />\n";
			echo "User:\n";
			echo "<select name=\"xf_user\" size=\"1\">\n";
			echo "<option>all</option>\n";
			
			$userlist = $backend->listAuthUsers();
			
			foreach ( $userlist as $user )
			{
				$contact = unserialize( $user[ "contact" ] );
				
				echo "<option ";
				
				if ( $_SESSION[ "xf_user" ] == $user[ "id" ] )
					echo "selected=\"selected\" ";
				
				echo "value=\"" . $user[ "id" ] . "\">" . helperEncodeHTML( $user[ "username" ] );
				
				if ( !empty( $contact[ "realname" ] ) )
					echo " - " . helperEncodeHTML( $contact[ "realname" ] );
				
				echo "</option>\n";
			}
			
			echo "</select>\n";
			
			echo "<br /><br />\n";
			echo "<input type=\"submit\" name=\"submit-extendedfilter\" value=\"Change extended filter " .
				"settings!\" />\n";
			
			echo "</td></tr>\n";
		}
		
		echo "<tr><td>\n";
		
		echo "Connectivity field background colors:\n";
		echo "<span style=\"background-color: " . "#88FF88\">online</span> -\n";
		echo "<span style=\"background-color: #FFFF88\">offline (last ping failed)</span> -\n";
		echo "<span style=\"background-color: #FF8888\">dead (last ping &gt; 5d)</span>\n";
		
		echo "</td><td>\n";
		
		echo "Entries per page:\n";
		echo "<select name=\"mp\" size=\"1\" onchange=\"document.indexcontrol.submit();\">\n";
		
		if ( $_SESSION[ "mp" ] == 25 )
			echo "<option selected=\"selected\">25</option>\n";
		else
			echo "<option>25</option>\n";
		
		if ( $_SESSION[ "mp" ] == 50 )
			echo "<option selected=\"selected\">50</option>\n";
		else
			echo "<option>50</option>\n";
		
		if ( $_SESSION[ "mp" ] == 100 )
			echo "<option selected=\"selected\">100</option>\n";
		else
			echo "<option>100</option>\n";
		
		if ( $_SESSION[ "mp" ] == 250 )
			echo "<option selected=\"selected\">250</option>\n";
		else
			echo "<option>250</option>\n";
		
		if ( $_SESSION[ "mp" ] == "all" )
			echo "<option selected=\"selected\">all</option>\n";
		else
			echo "<option>all</option>\n";
		
		echo "</select>\n";
		
		echo "</td></tr>\n";
		
		echo "<tr><td>\n";
		
		echo "Table layout settings:\n";
		echo "<a href=\"?a=i&amp;tlayout=guest\">Load guest default</a> -\n";
		echo "<a href=\"?a=i&amp;tlayout=simple\">Load simple default</a> -\n";
		echo "<a href=\"?a=i&amp;tlayout=expert\">Load expert default</a> -\n";
		echo "<a href=\"?a=i&amp;tlayout=pxe\">Load pxe default</a> -\n";
		echo "<a href=\"?a=i&amp;tlayout=own\">Load own default</a> -\n";
		
		if ( isset( $_SESSION[ "tlayout" ] ) )
			echo "Current: " . $_SESSION[ "tlayout" ] . "\n";
		else
		{
			echo "Current: Customized.\n";
			
			if ( $session->getID() )
				echo "- <a href=\"?a=i&amp;save-tlayout=true\">Save as own default</a>\n";
		}
		
		echo "</td><td>\n";
		
		echo "Customize the layout:\n";
		echo "<select name=\"addfield\" size=\"1\" onchange=\"document.indexcontrol.submit();\">\n";
		echo "<option>Add new column</option>";
		
		$avfields = helperFieldList();
		
		foreach ( $avfields as $field )
		{
			$fieldinfo = helperFieldInfo( $field );
			echo "<option value=\"$field\">" . $fieldinfo[ "desc" ] . " (";
			
			if ( !$fieldinfo[ "sortable" ] )
				echo "not ";
			
			echo "sortable)</option>\n";
		}
		
		echo "</select>\n";
		
		echo "</td></tr>\n";
		echo "</table>\n";
		
		echo "</form>\n";
	}
	
	/**
	 * Prints the navigation box which shows the current page and allows the user to switch the page.
	 * @param boolean $top True if top navigation, false for bottom navigation (controls form tags for group edit).
	 * @param integer $machines Number of machines on the current page, usually equals $_SESSION[ "mp" ].
	 * @param integer $machinesfound Total number of machines that matched the search criteria.
	 */
	function printPageNavigation( $top, $machines, $machinesfound )
	{
		$groupedit = in_array( "groupedit", $_SESSION[ "fields" ] );
		
		if ( $top && $groupedit )
			echo "<form action=\"?a=e\" method=\"post\" name=\"geform\">\n";
		
		echo "<table class=\"listhead\"><tr><td style=\"width: 100%\">\n";
		
		if ( $_SESSION[ "mp" ] != "all" )
		{
			$pagesavailable = ceil( $machinesfound / $_SESSION[ "mp" ] );
			$curpage = floor( $_SESSION[ "start" ] / $_SESSION[ "mp" ] ) + 1;
			
			echo "$machinesfound entries found, $machines visible, $pagesavailable page(s).\n";
			
			echo "</td><td style=\"white-space: nowrap; font-weight: bold\">\n";
			
			if ( $curpage > 1 )
			{
				echo "<a href=\"?a=i&amp;start=0\">&lt;&lt;</a> ";
				echo "<a href=\"?a=i&amp;start=" . ( $curpage - 2 ) * $_SESSION[ "mp" ] .
					"\">&lt;</a> ";
			}
			else
				echo "&lt;&lt; &lt; ";
			
			$page = $curpage - 4;
			if ( $page < 1 )
				$page = 1;
			
			while ( $page < $curpage )
			{
				echo "<a href=\"?a=i&amp;start=" . ( $page - 1 ) * $_SESSION[ "mp" ] . "\">$page</a> ";
				++$page;
			}
			
			echo "<span style=\"text-decoration: underline\">$curpage</span> ";
			++$page;
			
			for ( $i = 0; $i < 4; ++$i )
			{
				if ( $page > $pagesavailable )
					break;
				
				echo "<a href=\"?a=i&amp;start=" . ( $page - 1 ) * $_SESSION[ "mp" ] . "\">$page</a> ";
				++$page;
			}
			
			if ( $curpage < $pagesavailable )
			{
				echo "<a href=\"?a=i&amp;start=" . $curpage * $_SESSION[ "mp" ] . "\">&gt;</a> ";
				echo "<a href=\"?a=i&amp;start=" . ( $pagesavailable - 1 ) * $_SESSION[ "mp" ] .
					"\">&gt;&gt;</a>";
			}
			else
				echo "&gt; &gt;&gt;";
		}
		else
			echo "$machinesfound entries found, $machines visible.\n";
		
		echo "</td>";
		
		if ( $groupedit )
		{
			echo "<td><input type=\"submit\" name=\"start_groupedit\" value=\"Groupedit selected " .
				"machines\" /></td>\n";
		}
		
		echo "</tr></table>\n";
		
		if ( !$top && $groupedit )
			echo "</form>\n";
	}
	
	/**
	 * Parses $_SESSION[ "fields" ] and prints the table header.
	 * @param string $mode One of top (links + arrows), reminder (text) or edit (only arrows, not sortable)
	 * @param boolean $virtual If true, insert one empty column first for "Virtual => ..." field
	 */
	function printHeaderRow( $mode = "top", $virtual )  // mode = { top, reminder, edit }
	{
		global $theme;
		
		if ( $mode == "top" )
		{
			echo "<tr class=\"listrow-header\" style=\"white-space: nowrap\">\n";
			
			if ( $virtual )
				echo "<td>&nbsp;</td>\n";
			
			$max = count( $_SESSION[ "fields" ] );
			for ( $i = 0; $i < $max; ++$i )
			{
				echo "<td>";
				
				if ( $i != 0 )
				{
					echo "<a href=\"?a=i&amp;col=swap&amp;n=$i\">" . $theme->printIcon( "arrowl" ) .
						"</a> ";
				}
				
				echo "<a href=\"?a=i&amp;col=del&amp;n=$i\">" . $theme->printIcon( "deletel" ) . "</a>";
				
				if ( $i != $max - 1 )
				{
					echo " <a href=\"?a=i&amp;col=swap&amp;n=" . ( $i + 1 ) . "\">" . $theme->
						printIcon( "arrowr" ) . "</a>";
				}
				
				echo "</td>\n";
			}
			
			echo "</tr>\n";
		}
		
		if ( $virtual )
		{
			echo "<tr class=\"listrow-virtual-header\">\n";
			echo "<td>&nbsp;</td>\n";
		}
		else
			echo "<tr class=\"listrow-header\">\n";
		
		foreach ( $_SESSION[ "fields" ] as $field )
		{
			$fieldinfo = helperFieldInfo( $field );
			echo "<td>";
			
			if ( $mode == "reminder" || !$fieldinfo[ "sortable" ] )
				$theme->printTooltip( "#", $fieldinfo[ "desc" ], $fieldinfo[ "extdesc" ] );
			else if ( $mode == "edit" )
			{
				if ( $field == $_SESSION[ "sort" ] )
				{
					if ( $_SESSION[ "reverse" ] )
					{
						echo $theme->printIcon( "arrowu" ) . $fieldinfo[ "desc" ];
					}
					else
					{
						echo $theme->printIcon( "arrowd" ) . $fieldinfo[ "desc" ];
					}
				}
				else
					echo $fieldinfo[ "desc" ];
			}
			else  // $mode == top
			{
				if ( $field == $_SESSION[ "sort" ] )
				{
					if ( $_SESSION[ "reverse" ] )
						echo $theme->printIcon( "arrowu" );
					else
						echo $theme->printIcon( "arrowd" );
				}
				
				if ( $field == $_SESSION[ "sort" ] && !$_SESSION[ "reverse" ] )
					$target = "?s=" . $field . "&amp;r=yes";
				else
					$target = "?s=" . $field . "&amp;r=no";
				
				$theme->printTooltip( $target, $fieldinfo[ "desc" ], $fieldinfo[ "extdesc" ] );
			}
			
			echo "</td>\n";
		}
		
		echo "</tr>\n";
	}
	
	/**
	 * Prints a field of a machine for the table.
	 * @param array $m Machine (given by reference).
	 * @param string $field Name of the field to output.
	 */
	function printMachineField( &$m, $field )
	{
		global $backend, $CONF, $lastcronfailed, $theme;
		
		static $groupmapping = false;
		
		$ttarget = "#" . helperEncodeHTML( $m[ "hostname" ] );
		
		switch ( $field )
		{
			case "hostname":
				if ( empty( $m[ "hostname" ] ) )
					echo "<td>-";
				else
				{
					echo "<td><a id=\"" . helperEncodeHTML( $m[ "hostname" ] ) . "\"></a>" .
						helperEncodeHTML( $m[ "hostname" ] );
				}
				
				break;
			
			case "connectivity":
				if ( $lastcronfailed )
					echo "<td style=\"font-weight: bold\">Fix cronjob";
				else if ( !isset( $m[ "pingdiff" ] ) )
					echo "<td>Please wait...";
				else
				{
					$color = $theme->calcDowntimeColor( $m[ "pingdiff" ] );
					
					$tooltip = "<b>Connectivity information for " . $m[ "hostname" ] . "</b>" .
						"<br /><br />";
					
					if ( $m[ "pingdiff" ] == -1 )
						$tooltip .= "Equipment was never online.";
					else
					{
						$diff = floor( $m[ "pingdiff" ] / 60 );
						
						if ( $diff < 60 )
							$tooltip .= "Last ping $diff minute(s) ago:";
						else
						{
							$diff = round( $diff / 60 );
							if ( $diff < 24 )
								$tooltip .= "Last ping $diff hour(s) ago:";
							else
							{
								$diff2 = floor( $diff / 24 );
								$diff = $diff % 24;
								$tooltip .= "Last ping $diff2 days and $diff hours " .
									"ago:";
							}
						}
						
						$tooltip .= "<br />" . date( "D M j G:i:s T Y", $m[ "lastping" ] );
					}
					
					if ( $m[ "pingdiff" ] != -1 && $m[ "pingdiff" ] < $backend->readConfig(
						"cron_interval" ) + 60 )
					{
						echo "<td style=\"background-color: $color; font-weight: bold\">";
						$theme->printTooltip( $ttarget, "online", $tooltip );
					}
					else
					{
						echo "<td style=\"background-color: $color; font-weight: bold\">";
						$theme->printTooltip( $ttarget, "<span style=\"color: #880000\">" .
							"offline</span>", $tooltip );
					}
				}
				
				break;
			
			case "id":
				echo "<td>" . $m[ "id" ];
				
				break;
			
			case "group":
				if ( !$groupmapping )
					$groupmapping = $backend->listAuthGroups();
				
				echo "<td>";
				
				foreach ( $groupmapping as $g )
				{
					if ( $g[ "id" ] == $m[ "groupid" ] )
					{
						echo helperEncodeHTML( $g[ "groupname" ] );
						break;
					}
				}
				
				break;
			
			case "lastupdate":
				echo "<td>" . date( "D M j G:i:s T Y", $m[ "lastupdate" ] );
				
				break;
			
			case "updateby":
				if ( $m[ "updateby" ] )
				{
					echo "<td>";
					
					$username = "";
					$contact = $backend->getContact( $m[ "updateby" ], $username );
					
					helperPrintUserInfo( $username, $contact );
				}
				else
					echo "<td>-";
				
				break;
			
			case "vendor":
				if ( empty( $m[ "vendor" ] ) )
					echo "<td>-";
				else
				{
					if ( file_exists( "vendorlogos/" . $m[ "vendor" ] . ".png" ) )
					{
						echo "<td><img src=\"vendorlogos/" . $m[ "vendor" ] . ".png\" alt=\"" .
							helperEncodeHTML( $m[ "vendor" ] ) . "\" />";
					}
					else if ( file_exists( "vendorlogos/" . strtolower( $m[ "vendor" ] ) . ".png" )
						)
					{
						echo "<td><img src=\"vendorlogos/" . strtolower( $m[ "vendor" ] ) .
							".png\" alt=\"" . helperEncodeHTML( $m[ "vendor" ] ) . "\" />";
					}
					else
						echo "<td>" . helperEncodeHTML( $m[ "vendor" ] );
				}
				
				break;
			
			case "model":
				if ( $m[ "model" ] )
					echo "<td>" . helperEncodeHTML( $m[ "model" ] );
				else
					echo "<td>-";
				
				break;
			
			case "state":
				if ( $m[ "state" ] )
					echo "<td><span style=\"color: green\">In use</span>\n";
				else
					echo "<td><span style=\"color: orange\">Free</span>\n";
				
				break;
			
			case "usedby":
				echo "<td>";
				
				if ( $m[ "usedby" ] || $m[ "usedby_id1" ] || $m[ "usedby_id2" ] )
				{
					if ( $m[ "usedby_id1" ] )
					{
						$username = "";
						$contact = $backend->getContact( $m[ "usedby_id1" ], $username );
						
						helperPrintUserInfo( $username, $contact );
						
						if ( $m[ "usedby_id2" ] )
							echo "&amp; ";
					}
					
					if ( $m[ "usedby_id2" ] )
					{
						$username = "";
						$contact = $backend->getContact( $m[ "usedby_id2" ], $username );
						
						helperPrintUserInfo( $username, $contact );
					}
					
					if ( $m[ "usedby" ] )
					{
						if ( $m[ "usedby_id1" ] || $m[ "usedby_id2" ] )
							echo "<br />\n";
						
						echo helperEncodeHTML( $m[ "usedby" ] );
					}
				}
				else
					echo "-";
				
				break;
			
			case "arch":
				if ( empty( $m[ "arch" ] ) )
					echo "<td>-";
				else
					echo "<td>" . helperEncodeHTML( $m[ "arch" ] );
				
				break;
			
			case "assettag":
				if ( empty( $m[ "assettag" ] ) )
					echo "<td>-";
				else
					echo "<td>" . helperEncodeHTML( $m[ "assettag" ] );
				
				break;
			
			case "expiredate":
				if ( empty( $m[ "expiredate" ] ) )
					echo "-";
				else if ( $m[ "expired" ] )
					echo "<td style=\"color: red; font-weight: bold\">" . helperEncodeHTML( $m[
						"expiredate" ] );
				else
					echo "<td>" . helperEncodeHTML( $m[ "expiredate" ] );
				
				break;
			
			case "expirestate":
				if ( $m[ "expirestate" ] > 0 )
					echo "<td>Notify " . $m[ "expirestate" ] . " days before and when expired.";
				else if ( $m[ "expirestate" ] < 0 )
					echo "<td>Notify when expired.";
				else
					echo "<td>Don't notify.";
				
				break;
			
			case "ip":
				echo "<td>";
				
				if ( empty( $m[ "ip" ] ) )
					echo "-";
				else if ( !empty( $m[ "mac" ] ) )
				{
					$tooltip = "MAC Address in database: " . $m[ "mac" ] . "<br />";
					
					$vendor = $backend->lookupVendor( strtoupper( substr( $m[ "mac" ], 0, 8 ) ) );
					if ( $vendor )
						$tooltip .= "Vendor: $vendor";
					else
						$tooltip .= "Vendor: Unknown";
					
					$theme->printTooltip( $ttarget, helperEncodeHTML( $m[ "ip" ] ), $tooltip );
				}
				else
					echo helperEncodeHTML( $m[ "ip" ] );
				
				break;
			
			case "mac":
				if ( empty( $m[ "mac" ] ) )
					echo "<td>-";
				else
					echo "<td>" . helperEncodeHTML( $m[ "mac" ] );
				
				break;
			
			case "mfdata":
				echo "<td>";
				
				if ( !empty( $m[ "monfiles_data" ] ) )
				{
					$mondata = unserialize( $m[ "monfiles_data" ] );
					
					foreach ( $mondata as $key => $data )
					{
						if ( substr( $key, 0, 4 ) == "net_" && isset( $data[ "ip" ] ) && isset(
							$data[ "mac" ] ) )
						{
							$vendor = $backend->lookupVendor( strtoupper( substr( $data[
								"mac" ], 0, 8 ) ) );
							if ( !$vendor )
								$vendor = "Unknown";
							
							echo helperEncodeHTML( substr( $key, 4 ) ) . ": " .
								helperEncodeHTML( $data[ "ip" ] ) . " / " .
								helperEncodeHTML( $data[ "mac" ] ) . " (Vendor: " .
								"$vendor)<br />\n";
						}
						else if ( $key == "arch" )
							echo "Architecture: " . helperEncodeHTML( $data ) . "<br />\n";
					}
				}
				
				break;
			
			case "room":
				echo "<td>";
				
				if ( empty( $m[ "room" ] ) )
					echo "-";
				else
				{
					$roomlist = $backend->readConfig( "rooms" );
					
					$rooms = explode( ",", $roomlist );
					$mroom = explode( " ", $m[ "room" ] );
					
					if ( count( $mroom ) > 1 && in_array( $mroom[ 0 ], $rooms ) && !empty(
						$mroom[ 1 ] ) )
					{
						$theme->printTooltip( "?a=x&amp;room=" . helperEncodeHTML( $mroom[ 0 ]
							) . "#machine" . $m[ "id" ], helperEncodeHTML( $m[ "room" ] ),
							"Click here to jump to the map." );
					}
					else
						echo helperEncodeHTML( $m[ "room" ] );
				}
				
				break;
			
			case "notes":
				if ( empty( $m[ "notes" ] ) )
					echo "<td>-";
				else
				{
					echo "<td>" . helperEncodeHTML( str_replace( array( "\r\n", "\r", "\n"),
						"<br/>", $m[ "notes" ] ) );
				}
				
				break;
			
			case "sysinfo":
				echo "<td style=\"white-space: nowrap\">";
				
				if ( $m[ "expiredate" ] )
				{
					if ( $m[ "expired" ] )
						$img = $theme->printIcon( "date_r" );
					else
						$img = $theme->printIcon( "date" );
					
					$tooltip = "<b>Expiration Information</b><br /><br />";
					
					if ( $m[ "expired" ] )
					{
						$tooltip .= "Machine expired on " . helperEncodeHTML( $m[ "expiredate"
							] ) . ".";
					}
					else
					{
						$tooltip .= "Machine expires on " . helperEncodeHTML( $m[ "expiredate"
							] ) . ".";
					}
					
					$theme->printTooltip( $ttarget, $img, $tooltip );
				}
				else
				{
					$theme->printTooltip( $ttarget, $theme->printIcon( "date_d" ),
						"No expiration date set." );
				}
				
				if ( $m[ "os" ] || $m[ "cpu" ] || $m[ "mem" ] || $m[ "disk" ] || $m[ "kernel" ] ||
					$m[ "libc" ] || $m[ "compiler" ] )
				{
					$tooltip = "<b>System Information</b><br />";
					
					if ( !empty( $m[ "os" ] ) )
						$tooltip .= "<br />OS: " . helperEncodeHTML( $m[ "os" ] );
					
					if ( !empty( $m[ "cpu" ] ) )
						$tooltip .= "<br />CPU: " . helperEncodeHTML( $m[ "cpu" ] );
					
					if ( !empty( $m[ "mem" ] ) )
						$tooltip .= "<br />Memory: " . helperEncodeHTML( $m[ "mem" ] );
					
					if ( !empty( $m[ "disk" ] ) )
						$tooltip .= "<br />Disk(s): " . helperEncodeHTML( $m[ "disk" ] );
					
					if ( !empty( $m[ "kernel" ] ) )
						$tooltip .= "<br />Kernel: " . helperEncodeHTML( $m[ "kernel" ] );
					
					if ( !empty( $m[ "libc" ] ) )
						$tooltip .= "<br />libc version: " . helperEncodeHTML( $m[ "libc" ] );
					
					if ( !empty( $m[ "compiler" ] ) )
						$tooltip .= "<br />Compiler: " . helperEncodeHTML( $m[ "compiler" ] );
					
					$theme->printTooltip( $ttarget, $theme->printIcon( "sysinfo" ), $tooltip );
				}
				else
				{
					$theme->printTooltip( $ttarget, $theme->printIcon( "sysinfo_d" ),
						"No system information available." );
				}
				
				if ( $backend->readConfig( "prog_wbemcli" ) && $backend->readConfig( "wbem_user" ) &&
					$backend->readConfig( "wbem_password" ) )
				{
					if ( $m[ "wbem_info" ] )
					{
						$wbem = helperParseWBEMString( $m[ "wbem_info" ] );
						
						$tooltip = "<b>WBEM Information</b><br /><br />Last update: " . date(
							"D M j G:i:s T Y", $m[ "wbem_lastupdate" ] ) . "<br />";
						
						foreach ( $wbem as $wkey => $wvalue )
							$tooltip .= "<br />$wkey: $wvalue";
						
						$theme->printTooltip( $ttarget, $theme->printIcon( "wbem" ), $tooltip );
					}
					else if ( $m[ "wbem_lastupdate" ] )
					{
						$theme->printTooltip( $ttarget, $theme->printIcon( "wbem_d" ),
							"No WBEM data received.<br /><br />Last attempt: " . date(
							"D M j G:i:s T Y", $m[ "wbem_lastupdate" ] ) );
					}
					else
					{
						$theme->printTooltip( $ttarget, $theme->printIcon( "wbem_d" ),
							"WBEM will be queried with next cronrun." );
					}
				}
				
				if ( $m[ "remoteadm" ] )
				{
					$theme->printTooltip( $ttarget, $theme->printIcon( "remoteadm" ),
						"<b>Remote Administration</b><br /><br />" . helperEncodeHTML( $m[
						"hostname" ] ) . "r seems up." );
				}
				else
				{
					$theme->printTooltip( $ttarget, $theme->printIcon( "remoteadm_d" ),
						"No remote administration available." );
				}
				
				if ( $m[ "assettag" ] )
				{
					$theme->printTooltip( $ttarget, $theme->printIcon( "assettag" ), "<b>Asset " .
						"Tag</b><br /><br />" . helperEncodeHTML( $m[ "assettag" ] ) );
				}
				else
				{
					$theme->printTooltip( $ttarget, $theme->printIcon( "assettag_d" ),
						"No asset tag in database." );
				}
				
				$mondata = unserialize( $m[ "monfiles_data" ] );
				
				if ( isset( $m[ "pxecfg" ] ) || !empty( $mondata ) )
				{
					$tooltip = "";
					
					if ( isset( $m[ "pxecfg" ] ) )
					{
						$tooltip .= "<b>DHCP settings</b><br /><br />";
						
						$tooltip .= "IP: " . helperEncodeHTML( $m[ "pxecfg" ][ "ip" ] ) .
							"<br />";
						$tooltip .= "Mac: " . helperEncodeHTML( $m[ "pxecfg" ][ "mac" ] ) .
							" =&gt; ";
						
						$vendor = $backend->lookupVendor( strtoupper( substr( $m[ "pxecfg" ]
							[ "mac" ], 0, 8 ) ) );
						if ( $vendor )
							$tooltip .= $vendor;
						else
							$tooltip .= "Unknown vendor!";
					}
					
					if ( !empty( $mondata ) )
					{
						if ( isset( $m[ "pxecfg" ] ) )
							$tooltip .= "<br /><br />";
						
						$tooltip .= "<b>Reported by machine (monfile)</b><br /><br />";
						
						foreach ( $mondata as $key => $data )
						{
							if ( substr( $key, 0, 4 ) == "net_" && isset( $data[ "ip" ] ) &&
								isset( $data[ "mac" ] ) )
							{
								$vendor = $backend->lookupVendor( strtoupper( substr(
									$data[ "mac" ], 0, 8 ) ) );
								if ( !$vendor )
									$vendor = "Unknown";
								
								$tooltip .= helperEncodeHTML( substr( $key, 4 ) ) .
									": " . helperEncodeHTML( $data[ "ip" ] ) .
									" / " . helperEncodeHTML( $data[ "mac" ] ) .
									" (Vendor: $vendor)<br />";
							}
							else if ( $key == "arch" )
							{
								$tooltip .= "Architecture: " . helperEncodeHTML(
									$data ) . "<br />";
							}
						}
					}
					
					$theme->printTooltip( $ttarget, $theme->printIcon( "dhcp" ), $tooltip );
				}
				else
				{
					$theme->printTooltip( $ttarget, $theme->printIcon( "dhcp_d" ),
						"No DHCP/monfiles data available." );
				}
				
				if ( $m[ "notes" ] )
				{
					$theme->printTooltip( $ttarget, $theme->printIcon( "notes" ), "<b>Notes</b>" .
						"<br /><br />" . helperEncodeHTML( str_replace( array( "\r\n", "\r",
						"\n"), "<br />", $m[ "notes" ] ) ) );
				}
				else
				{
					$theme->printTooltip( $ttarget, $theme->printIcon( "notes_d" ),
						"Notes field empty." );
				}
				
				break;
			
			case "os":
				if ( empty( $m[ "os" ] ) )
					echo "<td>-";
				else
					echo "<td>" . helperEncodeHTML( $m[ "os" ] );
				
				break;
			
			case "cpu":
				if ( empty( $m[ "cpu" ] ) )
					echo "<td>-";
				else
					echo "<td>" . helperEncodeHTML( $m[ "cpu" ] );
				
				break;
			
			case "mem":
				if ( empty( $m[ "mem" ] ) )
					echo "<td>-";
				else
					echo "<td>" . helperEncodeHTML( $m[ "mem" ] );
				
				break;
			
			case "disk":
				if ( empty( $m[ "disk" ] ) )
					echo "<td>-";
				else
					echo "<td>" . helperEncodeHTML( $m[ "disk" ] );
				
				break;
			
			case "kernel":
				if ( empty( $m[ "kernel" ] ) )
					echo "<td>-";
				else
					echo "<td>" . helperEncodeHTML( $m[ "kernel" ] );
				
				break;
			
			case "libc":
				if ( empty( $m[ "libc" ] ) )
					echo "<td>-";
				else
					echo "<td>" . helperEncodeHTML( $m[ "libc" ] );
				
				break;
			
			case "compiler":
				if ( empty( $m[ "compiler" ] ) )
					echo "<td>-";
				else
					echo "<td>" . helperEncodeHTML( $m[ "compiler" ] );
				
				break;
			
			case "rack":
				echo "<td>";
				
				if ( $m[ "rack" ] == 2 )
					echo "<span style=\"color: orange\">Virtual</span>\n";
				else if ( $m[ "rack" ] == 1 )
					echo "<span style=\"color: green\">Rack</span>\n";
				else
					echo "<span style=\"color: blue\">Floor</span>\n";
				
				break;
			
			case "hostsystem":
				if ( empty( $m[ "hostsystem" ] ) )
					echo "<td>-";
				else
					echo "<td>" . helperEncodeHTML( $m[ "hostsystem" ] );
				
				break;
			
			case "mailtarget":
				if ( empty( $m[ "mailtarget" ] ) )
					echo "<td>-";
				else
					echo "<td>" . helperEncodeHTML( $m[ "mailtarget" ] );
				
				break;
			
			case "mailopts":
				if ( $m[ "mailopts" ] )
					echo "<td>Mail on connectivity changes.";
				else
					echo "<td>Don't mail on connectivity changes.";
				
				break;
			
			case "edit":
				echo "<td>";
				
				if ( $m[ "auth" ] )
				{
					echo "<a href=\"?a=e&amp;i=" . $m[ "id" ] . "\">" . $theme->printIcon( "edit"
						) . "</a>";
				}
				else
				{
					echo "<a href=\"?a=e&amp;i=" . $m[ "id" ] . "\">" . $theme->printIcon( "view"
						) . "</a>";
				}
				
				break;
			
			case "clone":
				echo "<td>";
				
				if ( $m[ "auth" ] )
				{
					echo "<a href=\"?a=e&amp;c=" . $m[ "id" ] . "\">" . $theme->printIcon( "clone"
						) . "</a>";
				}
				else
					echo "-";
				
				break;
			
			case "delete":
				echo "<td>";
				
				if ( $m[ "auth" ] )
				{
					echo "<a href=\"?a=e&amp;i=" . $m[ "id" ] . "&amp;delete=true\">" .
						$theme->printIcon( "delete" ) . "</a>";
				}
				else
					echo "-";
				
				break;
			
			case "pxe":
				echo "<td>";
				
				if ( !isset( $m[ "pxeserver" ] ) )
				{
					$theme->printTooltip( $ttarget, $theme->printIcon( "pxe_d" ),
						"<b>PXE unavailable</b><br /><br />Known IP of this record is not " .
						"valid for any of the configured PXE servers." );
				}
				else if ( !isset( $m[ "pxecfg" ] ) )
				{
					$theme->printTooltip( $ttarget, $theme->printIcon( "pxe_d" ),
						"<b>PXE unavailable</b><br /><br />Server should be managed by " .
						$m[ "pxeserver" ] . " but the PXE server does not know about it." .
						"<br /><br /><b>Make sure, valid IP and MAC are in database!" );
				}
				else if ( $m[ "pxecfg" ][ "active" ] )
				{
					$theme->printTooltip( "?a=p&amp;i=" . $m[ "id" ], $theme->printIcon( "pxe_a" ),
						"<b>PXE available</b><br /><br />PXE is active!<br />Click here to " .
						"change PXE settings." );
				}
				else  // available but not active
				{
					$theme->printTooltip( "?a=p&amp;i=" . $m[ "id" ], $theme->printIcon( "pxe" ),
						"<b>PXE available</b><br /><br />PXE is not active!<br />Click here " .
						"to change PXE settings." );
				}
				
				break;
			
			case "wakeonlan":
				echo "<td>";
				
				if ( $m[ "auth" ] && ( $lastcronfailed || !isset( $m[ "pingdiff" ] ) || (
					$m[ "pingdiff" ] >= $backend->readConfig( "cron_interval" ) + 60 ) ) &&
					!empty( $m[ "mac" ] ) && helperIsMAC( $m[ "mac" ] ) )
				{
					echo "<a href=\"?a=w&amp;i=" . $m[ "id" ] . "\">" . $theme->printIcon(
						"wakeonlan" ) . "</a>";
				}
				else
					echo $theme->printIcon( "wakeonlan_d" );
				
				break;
			
			case "groupedit":
				if ( $m[ "auth" ] )
				{
					echo "<td><input type=\"checkbox\" name=\"groupedit_" . $m[ "id" ] . "\" " .
						"value=\"yes\" />";
				}
				else
				{
					echo "<td><input type=\"checkbox\" name=\"groupedit_" . $m[ "id" ] . "\" " .
						"value=\"yes\" disabled=\"disabled\" />";
				}
				
				break;
			
			default:
				echo "<td>Unknown field.";
		}
		
		echo "</td>\n";
	}
	
	/**
	 * Loads a table layout into $_SESSION[ "fields" ].
	 * @param string $tlayout One of: guest, simple, expert, pxe, own
	 * @param boolean $pxe Whether to include PXE fields
	 * @param boolean $wol Whether to include Wake on LAN fields
	 */
	function loadTableLayout( $tlayout, $pxe, $wol )
	{
		global $backend, $session;
		
		if ( $tlayout == "guest" )
		{
			$_SESSION[ "fields" ] = array( "hostname", "group", "connectivity", "vendor", "model", "arch",
				"state", "usedby", "ip", "room", "sysinfo" );
			$_SESSION[ "tlayout" ] = "guest";
		}
		else if ( $tlayout == "simple" )
		{
			$_SESSION[ "fields" ] = array( "hostname", "group", "connectivity", "vendor", "model", "arch",
				"state", "usedby", "ip", "room", "sysinfo", "edit" );
			$_SESSION[ "tlayout" ] = "simple";
			
			if ( $pxe )
				array_push( $_SESSION[ "fields" ], "pxe" );
			
			if ( $wol )
				array_push( $_SESSION[ "fields" ], "wakeonlan" );
		}
		else if ( $tlayout == "expert" )
		{
			$_SESSION[ "fields" ] = array( "hostname", "group", "connectivity", "vendor", "model", "arch",
				"state", "usedby", "ip", "room", "sysinfo", "room", "rack", "mailtarget", "hostsystem",
				"lastupdate", "updateby", "edit", "clone", "delete", "groupedit" );
			$_SESSION[ "tlayout" ] = "expert";
			
			if ( $pxe )
				array_push( $_SESSION[ "fields" ], "pxe" );
			
			if ( $wol )
				array_push( $_SESSION[ "fields" ], "wakeonlan" );
		}
		else if ( $tlayout == "pxe" )
		{
			$_SESSION[ "fields" ] = array( "hostname", "groupname", "connectivity", "arch", "ip", "mac",
				"room", "sysinfo", "pxe", "wakeonlan" );
			$_SESSION[ "tlayout" ] = "pxe";
		}
		else if ( $tlayout == "own" )
		{
			if ( isset( $session->_settings[ "index-fields" ] ) )
			{
				$_SESSION[ "fields" ] = $session->_settings[ "index-fields" ];
				$_SESSION[ "tlayout" ] = "own";
			}
			else
				echo "<p class=\"err\">No saved layout found in your configuration.</p>\n";
		}
		else
			echo "<p class=\"err\">Unknown table layout string.</p>\n";
	}
	
	// Get group list
	$userid = $session->getID();
	$usergroups = array();
	
	if ( $userid )
		$usergroups = $backend->listAuthGroupMemberships( $userid );
	
	//////////////////////////////////////////////////////////////////////////////////
	//// Check for parameters and fill empty $_SESSION fields with default values ////
	//////////////////////////////////////////////////////////////////////////////////
	
	// Get list of all PXE servers
	$pxeservers = $backend->listAllPXE();
	$pxecons = array();
	
	// Query Wake on LAN settings
	$woladdr = $backend->readConfig( "wol_addr" );
	$wolport = $backend->readConfig( "wol_port" );
	
	// Check if we should have the PXE and WOL fields in the default layout
	$pxeavail = !empty( $pxeservers );
	$wolavail = !empty( $woladdr ) && !empty( $wolport );
	
	// Save current layout as user default if desired
	if ( isset( $_SESSION[ "fields" ] ) && isset( $_GET[ "save-tlayout" ] ) && $_GET[ "save-tlayout" ] == "true" )
	{
		$session->_settings[ "index-fields" ] = $_SESSION[ "fields" ];
		$session->saveSettings();
		
		$_SESSION[ "tlayout" ] = "own";
	}
	
	// Load default layout values if desired
	if ( isset( $_GET[ "tlayout" ] ) )
		loadTableLayout( $_GET[ "tlayout" ], $pxeavail, $wolavail );
	
	// If no default loaded, load simple
	if ( !isset( $_SESSION[ "fields" ] ) )
		loadTableLayout( "simple", $pxeavail, $wolavail );
	
	// For new logins, load user defaults if existing
	if ( $session->getID() && !isset( $_SESSION[ "login-fieldsloaded" ] ) )
	{
		if ( isset( $session->_settings[ "loginlayout" ] ) )
			$loginlayout = $session->_settings[ "loginlayout" ];
		else
			$loginlayout = "simple";
		
		loadTableLayout( $loginlayout, $pxeavail, $wolavail );
		$_SESSION[ "login-fieldsloaded" ] = true;
	}
	
	// Sort field
	if ( isset( $_GET[ "s" ] ) )
		$_SESSION[ "sort" ] = $_GET[ "s" ];
	else if ( !isset( $_SESSION[ "sort" ] ) )
		$_SESSION[ "sort" ] = "hostname";
	
	// Reverse sort
	if ( ( isset( $_GET[ "r" ] ) && $_GET[ "r" ] == "yes" ) )
		$_SESSION[ "reverse" ] = true;
	else if ( ( isset( $_GET[ "r" ] ) && $_GET[ "r" ] == "no" ) || !isset( $_SESSION[ "reverse" ] ) )
		$_SESSION[ "reverse" ] = false;
	
	// Hide other ("show only own")
	if ( isset( $_POST[ "resetfilter" ] ) )
		$_SESSION[ "showmachines" ] = "all";
	else if ( isset( $_POST[ "sh" ] ) && ( $_POST[ "sh" ] == "all" || $_POST[ "sh" ] == "own" || $_POST[ "sh" ] ==
		"edit" ) )
		$_SESSION[ "showmachines" ] = $_POST[ "sh" ];
	else if ( !isset( $_SESSION[ "showmachines" ] ) )
		$_SESSION[ "showmachines" ] = "all";
	
	// Show all equipment
	if ( isset( $_POST[ "resetfilter" ] ) )
		$_SESSION[ "showequipment" ] = false;
	else if ( isset( $_POST[ "e" ] ) && ( $_POST[ "e" ] == "yes" || $_POST[ "e" ] == "no" ) )
		$_SESSION[ "showequipment" ] = ( $_POST[ "e" ] == "yes" );
	else if ( !isset( $_SESSION[ "showequipment" ] ) )
		$_SESSION[ "showequipment" ] = false;
	
	// Group virtual mode
	if ( isset( $_POST[ "resetfilter" ] ) )
		$_SESSION[ "groupvirtual" ] = "yes";
	else if ( isset( $_POST[ "g" ] ) && ( $_POST[ "g" ] == "yes" || $_POST[ "g" ] == "no" || $_POST[ "g" ] ==
		"hide" ) )
		$_SESSION[ "groupvirtual" ] = $_POST[ "g" ];
	else if ( !isset( $_SESSION[ "groupvirtual" ] ) )
		$_SESSION[ "groupvirtual" ] = "yes";
	
	// Filter online/offline/dead
	if ( isset( $_POST[ "resetfilter" ] ) )
		$_SESSION[ "onlinefilter" ] = "all";
	else if ( isset( $_POST[ "o" ] ) && ( $_POST[ "o" ] == "all" || $_POST[ "o" ] == "online" || $_POST[ "o" ] ==
		"offline" || $_POST[ "o" ] == "dead" ) )
		$_SESSION[ "onlinefilter" ] = $_POST[ "o" ];
	else if ( !isset( $_SESSION[ "onlinefilter" ] ) )
		$_SESSION[ "onlinefilter" ] = "all";
	
	// Extended filter options
	if ( isset( $_POST[ "resetfilter" ] ) )
		$_SESSION[ "extendedfilter" ] = false;
	else if ( isset( $_POST[ "xf" ] ) && ( $_POST[ "xf" ] == "yes" || $_POST[ "xf" ] == "no" ) )
		$_SESSION[ "extendedfilter" ] = ( $_POST[ "xf" ] == "yes" );
	else if ( !isset( $_SESSION[ "extendedfilter" ] ) )
		$_SESSION[ "extendedfilter" ] = false;
	
	// Vendor filtering (extended filter)
	$vendorstr = $backend->readConfig( "vendors" );
	$vendora = explode( ",", $vendorstr );
	
	foreach ( $vendora as $vendor )
	{
		if ( ( isset( $_POST[ "submit-extendedfilter" ] ) && isset( $_POST[ "xf_vendor_$vendor" ] ) ) ||
			!isset( $_SESSION[ "xf_vendor" ][ $vendor ] ) || isset( $_POST[ "resetfilter" ] ) )
			$_SESSION[ "xf_vendor" ][ $vendor ] = true;
		else if ( isset( $_POST[ "submit-extendedfilter" ] ) && !isset( $_POST[ "xf_vendor_$vendor" ] ) )
			$_SESSION[ "xf_vendor" ][ $vendor ] = false;
	}
	
	// Arch filtering (extended filter)
	$archstr = $backend->readConfig( "architectures" );
	$archa = explode( ",", $archstr );
	
	foreach ( $archa as $arch )
	{
		if ( ( isset( $_POST[ "submit-extendedfilter" ] ) && isset( $_POST[ "xf_arch_$arch" ] ) ) ||
			!isset( $_SESSION[ "xf_arch" ][ $arch ] ) || isset( $_POST[ "resetfilter" ] ) )
			$_SESSION[ "xf_arch" ][ $arch ] = true;
		else if ( isset( $_POST[ "submit-extendedfilter" ] ) && !isset( $_POST[ "xf_arch_$arch" ] ) )
			$_SESSION[ "xf_arch" ][ $arch ] = false;
	}
	
	// Room filtering (extended filter)
	$roomstr = $backend->readConfig( "rooms" );
	$rooma = explode( ",", $roomstr );
	
	foreach ( $rooma as $room )
	{
		$roomre = "xf_room_" . str_replace( ".", "_", $room );
		if ( ( isset( $_POST[ "submit-extendedfilter" ] ) && isset( $_POST[ $roomre ] ) ) ||
			!isset( $_SESSION[ "xf_room" ][ $room ] ) || isset( $_POST[ "resetfilter" ] ) )
			$_SESSION[ "xf_room" ][ $room ] = true;
		else if ( isset( $_POST[ "submit-extendedfilter" ] ) && !isset( $_POST[ $roomre ] ) )
			$_SESSION[ "xf_room" ][ $room ] = false;
	}
	
	// State filtering (extended filter)
	if ( isset( $_POST[ "resetfilter" ] ) )
		$_SESSION[ "xf_state" ] = "all";
	else if ( isset( $_POST[ "submit-extendedfilter" ] ) && isset( $_POST[ "xf_state" ] ) && ( $_POST[ "xf_state" ]
		== "all" || $_POST[ "xf_state" ] == "inuse" || $_POST[ "xf_state" ] == "free" ) )
		$_SESSION[ "xf_state" ] = $_POST[ "xf_state" ];
	else if ( !isset( $_SESSION[ "xf_state" ] ) )
		$_SESSION[ "xf_state" ] = "all";
	
	// Rack filtering (extended filter)
	if ( ( isset( $_POST[ "submit-extendedfilter" ] ) && isset( $_POST[ "xf_rack_rack" ] ) ) ||
		!isset( $_SESSION[ "xf_rack" ][ "rack" ] ) || isset( $_POST[ "resetfilter" ] ) )
		$_SESSION[ "xf_rack" ][ "rack" ] = true;
	else if ( isset( $_POST[ "submit-extendedfilter" ] ) && !isset( $_POST[ "xf_rack_rack" ] ) )
		$_SESSION[ "xf_rack" ][ "rack" ] = false;
	
	if ( ( isset( $_POST[ "submit-extendedfilter" ] ) && isset( $_POST[ "xf_rack_floor" ] ) ) ||
		!isset( $_SESSION[ "xf_rack" ][ "floor" ] ) || isset( $_POST[ "resetfilter" ] ) )
		$_SESSION[ "xf_rack" ][ "floor" ] = true;
	else if ( isset( $_POST[ "submit-extendedfilter" ] ) && !isset( $_POST[ "xf_rack_floor" ] ) )
		$_SESSION[ "xf_rack" ][ "floor" ] = false;
	
	if ( ( isset( $_POST[ "submit-extendedfilter" ] ) && isset( $_POST[ "xf_rack_virtual" ] ) ) ||
		!isset( $_SESSION[ "xf_rack" ][ "virtual" ]) || isset( $_POST[ "resetfilter" ] ) )
		$_SESSION[ "xf_rack" ][ "virtual" ] = true;
	else if ( isset( $_POST[ "submit-extendedfilter" ] ) && !isset( $_POST[ "xf_rack_virtual" ] ) )
		$_SESSION[ "xf_rack" ][ "virtual" ] = false;
	
	// User filtering (extended filter)
	if ( isset( $_POST[ "resetfilter" ] ) )
		$_SESSION[ "xf_user" ] = "all";
	else if ( isset( $_POST[ "submit-extendedfilter" ] ) && isset( $_POST[ "xf_user" ] ) && ( $_POST[ "xf_user" ]
		== "all" || helperIsDigit( $_POST[ "xf_user" ] ) ) )
		$_SESSION[ "xf_user" ] = $_POST[ "xf_user" ];
	else if ( !isset( $_SESSION[ "xf_user" ] ) )
		$_SESSION[ "xf_user" ] = "all";
	
	// Search
	if ( isset( $_POST[ "sr" ] ) && isset( $_POST[ "submit-search" ] ) )
	{
		if ( empty( $_POST[ "sr" ] ) && isset( $_SESSION[ "search" ] ) )
			unset( $_SESSION[ "search" ] );
		else
			$_SESSION[ "search" ] = $_POST[ "sr" ];
	}
	else if ( isset( $_POST[ "resetfilter" ] ) && isset( $_SESSION[ "search" ] ) )
		unset( $_SESSION[ "search" ] );
	
	// Machines per page (number or "all")
	if ( isset( $_POST[ "mp" ] ) && ( $_POST[ "mp" ] == "all" || helperIsDigit( $_POST[ "mp" ] ) ) )
		$_SESSION[ "mp" ] = $_POST[ "mp" ];
	else if ( isset( $_POST[ "mp" ] ) && ( $_POST[ "mp" ] == "all" || helperIsDigit( $_POST[ "mp" ] ) ) )
		$_SESSION[ "mp" ] = $_POST[ "mp" ];
	else if ( !isset( $_SESSION[ "mp" ] ) )
		$_SESSION[ "mp" ] = 50;
	
	// Start machine for paging
	if ( isset( $_GET[ "start" ] ) && helperIsDigit( $_GET[ "start" ] ) )
		$_SESSION[ "start" ] = $_GET[ "start" ];
	else if ( isset( $_POST[ "start" ] ) && helperIsDigit( $_POST[ "start" ] ) )
		$_SESSION[ "start" ] = $_POST[ "start" ];
	else if ( !isset( $_SESSION[ "start" ] ) )
		$_SESSION[ "start" ] = 0;
	
	// Sanity check (used when changing mp to force start value on page start)
	if ( $_SESSION[ "mp" ] != "all" )
		$_SESSION[ "start" ] = floor( $_SESSION[ "start" ] / $_SESSION[ "mp" ] ) * $_SESSION[ "mp" ];
	
	////////////////////////////////
	//// Process layout changes ////
	////////////////////////////////
	
	// Add new field to layout
	if ( isset( $_POST[ "addfield" ] ) && helperFieldInfo( $_POST[ "addfield" ] ) )
	{
		array_push( $_SESSION[ "fields" ], $_POST[ "addfield" ] );
		unset( $_SESSION[ "tlayout" ] );
	}
	else if ( isset( $_GET[ "col" ] ) && isset( $_GET[ "n" ] ) && helperIsDigit( $_GET[ "n" ] ) )
	{
		$maxcol = count( $_SESSION[ "fields" ] );
		
		switch ( $_GET[ "col" ] )
		{
			// Delete field
			case "del":
				if ( $_GET[ "n" ] >= $maxcol )
					break;
				
				$fieldsold = $_SESSION[ "fields" ];
				$_SESSION[ "fields" ] = array();
				
				foreach ( $fieldsold as $id => $oldfield )
				{
					if ( $id != $_GET[ "n" ] )
						array_push( $_SESSION[ "fields" ], $oldfield );
				}
				
				unset( $_SESSION[ "tlayout" ] );
				
				break;
			
			// Swap fields (move field left or right)
			case "swap":
				if ( $_GET[ "n" ] < 1 || $_GET[ "n" ] >= $maxcol )
					break;
				
				$swap = $_SESSION[ "fields" ][ $_GET[ "n" ] ];
				$_SESSION[ "fields" ][ $_GET[ "n" ] ] = $_SESSION[ "fields" ][ $_GET[ "n" ] - 1 ];
				$_SESSION[ "fields" ][ $_GET[ "n" ] - 1 ] = $swap;
				
				unset( $_SESSION[ "tlayout" ] );
				
				break;
		}
	}
	
	if ( $_SESSION[ "extendedfilter" ] )
	{
		$xf_vendor = $_SESSION[ "xf_vendor" ];
		$xf_arch = $_SESSION[ "xf_arch" ];
		$xf_room = $_SESSION[ "xf_room" ];
		$xf_state = $_SESSION[ "xf_state" ];
		$xf_rack = $_SESSION[ "xf_rack" ];
		$xf_user = helperIsDigit( $_SESSION[ "xf_user" ] ) ? $_SESSION[ "xf_user" ] : false;
	}
	else
	{
		$xf_vendor = false;
		$xf_arch = false;
		$xf_room = false;
		$xf_state = false;
		$xf_rack = false;
		$xf_user = ( $_SESSION[ "showmachines" ] == "own" ) ? $session->getID() : false;
	}
	
	if ( empty( $_SESSION[ "search" ] ) )
		$search = false;
	else
		$search = $_SESSION[ "search" ];
	
	// Do the actual database query
	$machines = $backend->getList( -1, $_SESSION[ "sort" ], $_SESSION[ "reverse" ], $search, $xf_user, $xf_vendor,
		$xf_arch, $xf_state, $xf_rack, $xf_room, $_SESSION[ "showequipment" ] );
	
	// Check whether we are an admin
	$admin = false;
	
	foreach ( $usergroups as $g )
	{
		if ( $g[ "id" ] == 1 )
			$admin = true;
	}
	
	// Disable "hide other" feature if other is not in any groups (except admin group)
	if ( $_SESSION[ "showmachines" ] == "edit" )
	{
		$_SESSION[ "showmachines" ] = "all";
		
		foreach ( $usergroups as $g )
		{
			if ( $g[ "id" ] != 1 )
				$_SESSION[ "showmachines" ] = "edit";
		}
		
		if ( $_SESSION[ "showmachines" ] == "all" )
			echo "<p class=\"err\">No user group. Disabling \"show only editable machines\".</p>\n";
	}
	
	// Print the index control panel allowing filtering
	if ( !$editid )
		printIndexControlPanel();
	
	// Group virtual machines around physical. Note: If group virtual is set to "hide", we do the same grouping
	// here but cull the machines out later after they were grouped.
	if ( $_SESSION[ "groupvirtual" ] != "no" )
	{
		// Ugly helper arrays for our very ugly algorithm (but it works)
		$machinesungrouped = $machines;
		$helperarray = $machines;
		
		$machines = array();
		
		foreach ( $machinesungrouped as $id => $machine )
		{
			if ( !empty( $machine[ "hostsystem" ] ) || preg_match( "/^[[:alnum:]]+v[[:digit:]]+$/",
				$machine[ "hostname" ] ) )
				continue;
			
			$machines[ $id ] = $machine;
			$machines[ $id ][ "virtual" ] = false;
			
			$machinesungrouped[ $id ][ "included" ] = true;
			
			foreach ( $helperarray as $innerid => $innermachine )
			{
				if ( ( $machine[ "hostname" ] && ( $innermachine[ "hostsystem" ] == $machine[
					"hostname" ] ) ) || ( preg_match( "/^" . $machine[ "hostname" ] .
					"v[[:digit:]]+$/", $innermachine[ "hostname" ] ) ) )
				{
					$machines[ $innerid ] = $innermachine;
					
					if ( $innermachine[ "hostsystem" ] )
						$machines[ $innerid ][ "virtual" ] = $innermachine[ "hostsystem" ];
					else
						$machines[ $innerid ][ "virtual" ] = $machine[ "hostname" ];
					
					$machinesungrouped[ $innerid ][ "included" ] = true;
				}
			}
			
			reset( $helperarray );
		}
		
		foreach ( $machinesungrouped as $id => $machine )
		{
			if ( !isset( $machine[ "included" ] ) )
			{
				$machines[ $id ] = $machine;
				
				if ( $machines[ $id ][ "hostsystem" ] )
					$machines[ $id ][ "virtual" ] = $machines[ $id ][ "hostsystem" ];
				else
				{
					$machines[ $id ][ "virtual" ] = substr( $machines[ $id ][ "hostname" ], 0,
						strrpos( $machines[ $id ][ "hostname" ], "v" ) );
				}
			}
		}
		
		unset( $machinesungrouped );
		unset( $helperarray );
	}
	
	$outputmachines = array();
	
	$machinesfound = 0;
	
	// Edit ID mode?
	if ( $editid )
		$editcount = 0;
	else
		$tstart = $_SESSION[ "start" ];
	
	// Get filter regexp from user configuration if set
	if ( isset( $session->_settings[ "filterregexp" ] ) )
		$filterregexp = $session->_settings[ "filterregexp" ];
	else
		$filterregexp = "";
	
	foreach ( $machines as $id => $m )  // Could be done more efficiently but works fine for now.
	{
		// Check users filter regexp
		if ( !empty( $filterregexp ) && preg_match( "/$filterregexp/", $m[ "hostname" ] ) )
			continue;
		
		// Check whether the user is authed to edit/delete the current machine (without admin group)
		$m[ "auth" ] = false;
		
		foreach ( $usergroups as $g )
		{
			if ( $g[ "id" ] == $m[ "groupid" ] )
			{
				$m[ "auth" ] = true;
				break;
			}
		}
		
		// Now enable auth for admins
		if ( $admin )
			$m[ "auth" ] = true;
		
		// Skip if not authed and user wants to see only his own equipment
		if ( $_SESSION[ "showmachines" ] == "edit" && !$m[ "auth" ] )
			continue;
		
		// Calculate seconds since last successful ping
		if ( $m[ "lastping" ] > 0 )
		{
			if ( $m[ "lastping" ] == 1 )
				$m[ "pingdiff" ] = -1;
			else
				$m[ "pingdiff" ] = time() - $m[ "lastping" ];
		}
		
		// Check online filter
		if ( ( $_SESSION[ "onlinefilter" ] == "online" && ( !isset( $m[ "pingdiff" ] ) || ( $m[ "pingdiff" ]
			>= $backend->readConfig( "cron_interval" ) + 60 ) ) ) ||
			( $_SESSION[ "onlinefilter" ] == "offline" && isset( $m[ "pingdiff" ] ) && ( $m[ "pingdiff" ] <
			$backend->readConfig( "cron_interval" ) + 60 ) ) ||
			( $_SESSION[ "onlinefilter" ] == "dead" && isset( $m[ "pingdiff" ] ) && ( $m[ "pingdiff" ] <
			3600 * 24 * 5 ) ) )
			continue;
		
		// Add host's location for virtual machines
		if ( $_SESSION[ "groupvirtual" ] == "yes" )
		{
			if ( $m[ "virtual" ] && !empty( $lastroom ) )
			{
				if ( empty( $m[ "room" ] ) )
					$m[ "room" ] = $lastroom;
			}
			else
				$lastroom = $m[ "room" ];
		}
		
		// This is the reason why we do NOT break even if we have a collected a full page of machines:
		// We want to provide the user the number of all machines that passed the filtering (which also enables
		// us to calculate the correct number of available pages)
		++$machinesfound;
		
		// If in edit mode...
		if ( $editid > 0 )
		{
			// Have we reached the machine that is being edited yet? Otherwise skip.
			if ( !$editcount && $editid != $id )
				continue;
			else
			{
				++$editcount;
				
				// Set return code for pageEdit()
				if ( $editcount == 2 )
					$nextedit = $id;
				
				// We're displaying the machine being edited and 5 successors. That's enough.
				if ( $editcount > 6 )
					continue;
			}
		}
		else
		{
			// Skip first $tstart visible machines (used for paging)
			if ( $tstart > 0 )
			{
				--$tstart;
				continue;
			}
			
			// Collected enough machines for a page?
			if ( $_SESSION[ "mp" ] != "all" && ( count( $outputmachines ) >= $_SESSION[ "mp" ] ) )
				continue;
		}
		
		// OK, machine should be visible, copy over to final $machines array
		$outputmachines[ $id ] = $m;
		
		// Check if PXE/DHCP data is available
		if ( isset( $outputmachines[ $id ][ "ip" ] ) )
		{
			foreach ( $pxeservers as $pxe )
			{
				if ( helperCheckSubnet( $m[ "ip" ], $pxe[ "iprange" ] ) )
				{
					$outputmachines[ $id ][ "pxeserver" ] = $pxe[ "address" ];
					break;
				}
			}
		}
		
		// If PXE/DHCP server is known, read configuration
		if ( isset( $outputmachines[ $id ][ "pxeserver" ] ) )
		{
			$addr = $outputmachines[ $id ][ "pxeserver" ];
			if ( !isset( $pxecons[ $addr ] ) )
				$pxecons[ $addr ] = new PXEController( $addr );
			
			if ( $pxecons[ $addr ]->getVersion() > 0 )
			{
				if ( isset( $pxecons[ $addr ]->config[ $outputmachines[ $id ][ "hostname" ] ] ) )
				{
					$outputmachines[ $id ][ "pxecfg" ] = $pxecons[ $addr ]->config[
						$outputmachines[ $id ][ "hostname" ] ];
				}
			}
		}
		
		// Check whether machine is expired
		$outputmachines[ $id ][ "expired" ] = false;
		
		if ( $outputmachines[ $id ][ "expiredate" ] && helperIsDate( $outputmachines[ $id ][ "expiredate" ] ) )
		{
			$expirefields = explode( "-", $outputmachines[ $id ][ "expiredate" ] );
			if ( mktime( 0, 0, 0, $expirefields[ 1 ], $expirefields[ 2 ], $expirefields[ 0 ] ) < mktime() )
				$outputmachines[ $id ][ "expired" ] = true;
		}
	}
	
	// Print navigation widget
	if ( !$editid )
		printPageNavigation( true, count( $outputmachines ), $machinesfound );
	
	echo "<table class=\"mainlist\">\n";
	
	// Print table header row
	if ( $editid )
		printHeaderRow( "edit", false );
	else
		printHeaderRow( "top", false );
	
	$highlight = false;
	$lastomitted = false;
	$virtheadersent = false;
	
	// Display all remaining machines
	foreach ( $outputmachines as $id => $machine )
	{
		// Virtual machines handling
		if ( $_SESSION[ "groupvirtual" ] != "no" && $machine[ "virtual" ] )
		{
			// If user wants to hide VMs, show a small notice for the first hidden VM
			if ( $_SESSION[ "groupvirtual" ] == "hide" || $lastomitted )
			{
				if ( !$virtheadersend )
				{
					$virtheadersend = true;
					
					echo "<tr class=\"listrow-virtual-dark\" style=\"height: 20px\"><td></td>";
					
					echo "<td colspan=\"" . count( $_SESSION[ "fields" ] ) .
						"\">Virtual machines of " . helperEncodeHTML( $machine[ "virtual" ] ) .
						" omitted.</td></tr>\n";
				}
				
				continue;
			}
			
			// If user wants VMs grouped, start VM subtable for first virtual equipment entry
			if ( !$virtheadersent )
			{
				$virtheadersent = true;
				
				if ( $highlight )
					echo "<tr class=\"listrow-dark\">";
				else
					echo "<tr class=\"listrow-light\">";
				
				$highlight = !$highlight;
				
				echo "<td colspan=\"" . count( $_SESSION[ "fields" ] ) . "\">";
				
				echo "<table class=\"mainlist\">\n";
				
				printHeaderRow( "reminder", true );
			}
		}
		
		// After last virtual machine with grouping enabled, close VM subtable and reprint table header
		else if ( $_SESSION[ "groupvirtual" ] == "yes" && !$machine[ "virtual" ] && $virtheadersent )
		{
			$highlight = false;
			$virtheadersent = false;
			
			echo "</table></td></tr>\n";
			
			printHeaderRow( "reminder", false );
		}
		
		// Start row
		if ( $_SESSION[ "groupvirtual" ] != "no" && $machine[ "virtual" ] )
		{
			if ( $machine[ "expired" ] )
				echo "<tr class=\"listrow-virtual-red\">";
			else if ( $highlight )
				echo "<tr class=\"listrow-virtual-dark\">";
			else
				echo "<tr class=\"listrow-virtual-light\">";
			
			echo "<td><i>Virtual =></i><br /><i>(on " . helperEncodeHTML( $machine[ "virtual" ] ) .
				")</i></td>\n";
		}
		else
		{
			if ( $machine[ "expired" ] )
				echo "<tr class=\"listrow-red\">";
			else if ( $highlight )
				echo "<tr class=\"listrow-dark\">";
			else
				echo "<tr class=\"listrow-light\">";
			
			$virtheadersend = false;
		}
		
		$highlight = !$highlight;
		$lastomitted = false;
		
		// Output machine database record
		foreach ( $_SESSION[ "fields" ] as $field )
			printMachineField( $machine, $field );
		
		echo "</tr>\n";
	}
	
	// End table
	if ( $virtheadersent )
		echo "</table></td></tr>\n";
	
	echo "</table>\n";
	
	// Print page navigation again
	if ( !$editid )
		printPageNavigation( false, count( $outputmachines ), $machinesfound );
	
	// Return ID of the next machine when running in edit mode
	if ( isset( $nextedit ) )
		return $nextedit;
	else
		return 0;
}

?>
