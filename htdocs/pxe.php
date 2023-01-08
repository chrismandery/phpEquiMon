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
 * Shows a form for editing the PXE settings of a machine.
 */
function pagePXE()
{
	global $backend, $session, $theme;
	
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
	
	// ID given?
	if ( isset( $_GET[ "i" ] ) && helperIsDigit( $_GET[ "i" ] ) )
		$id = $_GET[ "i" ];
	else
	{
		echo "<p class=\"err\">Missing ID</>\n";
		return;
	}
	
	// Fetch record from database
	$entry = $backend->getList( $id );
	if ( !$entry )
	{
		echo "<p class=\"err\">Record $id not found in database.</p>\n";
		return;
	}
	
	// Check that the machine has a IP in the database
	if ( empty( $entry[ "ip" ] ) )
	{
		echo "<p class=\"err\">No IP in database.</p>\n";
		return;
	}
	
	// Get a list of all PXE servers
	$pxeservers = $backend->listAllPXE();
	
	// Get a list of all PXE kernels
	$pxekernels = $backend->getAllKernels();
	
	// Search PXE server responsible for this machine
	$pxeaddr = false;
	foreach ( $pxeservers as $pxe )
	{
		if ( helperCheckSubnet( $entry[ "ip" ], $pxe[ "iprange" ] ) )
		{
			$pxeaddr = $pxe[ "address" ];
			break;
		}
	}
	
	// No PXE server found?
	if ( !$pxeaddr )
	{
		echo "<p class=\"err\">No PXE server is defined for the IP of this machine.</p>\n";
		return;
	}
	
	// Initialize PXE connection
	$pxecon = new PXEController( $pxeaddr );
	
	// Error?
	if ( !( $pxecon->getVersion() > 0 ) )
	{
		echo "<p class=\"err\">PXE server " . helperEncodeHTML( $pxeaddr ) . " is not ready. Contact " .
			"administrator.</p>\n";
		return;
	}
	
	// Get machine config from PXE data
	if ( !isset( $pxecon->config[ $entry[ "hostname" ] ] ) )
	{
		echo "<p class=\"err\">PXE server " . helperEncodeHTML( $pxeaddr ) . " does not know " . $entry[
			"hostname" ] . ". Try regenerating the DHCP configuration by editting the machine.</p>\n";
		return;
	}
	
	$pxecfg = $pxecon->config[ $entry[ "hostname" ] ];
	
	// Check authentification and determine whether we must operate readonly
	if ( $backend->checkEditAuth( $id ) )
	{
		$ro = false;
		$ro_html1 = "";
		$ro_html2 = "";
	}
	else
	{
		$ro = true;
		$ro_html1 = "disabled=\"disabled\" ";
		$ro_html2 = "readonly=\"readonly\" ";
	}
	
	// Get kernel information
	if ( isset( $_POST[ "kernelid" ] ) && helperIsDigit( $_POST[ "kernelid" ] ) )
		$curkernel = $backend->getKernelByID( $_POST[ "kernelid" ] );
	else
		$curkernel = $backend->getKernelByName( $pxecfg[ "kernel" ] );
	
	// Actually change settings? (submit button pressed)
	if ( !$ro && isset( $_POST[ "submitb" ] ) && isset( $_POST[ "status" ] ) && isset( $_POST[ "options" ] ) )
	{
		$bootopts = str_replace( "\r\n", " ", $_POST[ "options" ] );
		
		$bootopts = str_replace( array( "%ip", "%mac" ), array( $pxecfg[ "ip" ], $pxecfg[ "mac" ] ),
			$bootopts );
		
		if ( $pxecon->writePXEConfiguration( $entry[ "arch" ], ( ( $entry[ "arch" ] == "ia64" ) ? $pxecfg[
			"ip" ] : $pxecfg[ "mac" ] ), $_POST[ "status" ] == "1", isset( $_POST[ "watchdog" ] ),
			$curkernel[ "filename" ], $bootopts ) )
		{
			echo "<p class=\"infobox\">PXE settings for " . helperEncodeHTML( $entry[ "hostname" ] ) .
				" changed.</p>\n";
			
			$backend->logEvent( $session->getID(), "Changed PXE options for " . $entry[ "hostname" ] .
				" (id = $id)." );
		}
		else
			echo "<p class=\"err\">Error while changing PXE settings.</p>\n";
		
		pageOutputList();
		return;
	}
	
	// Load predefined parameter line?
	else if ( isset( $_POST[ "choose_pl" ] ) && helperIsDigit( $_POST[ "choose_pl" ] ) )
		$pxecfg[ "options" ] = $backend->getKernelParameterLine( $_POST[ "choose_pl" ] );
	
	// ... otherwise take options from POST if given
	else if ( isset( $_POST[ "options" ] ) )
		$pxecfg[ "options" ] = $_POST[ "options" ];
	
	// Take over other settings if given via POST
	if ( isset( $_POST[ "status" ] ) )
		$pxecfg[ "active" ] = ( $_POST[ "status" ] == "1" );
	
	// Start HTML output
	echo "<h2>PXE Settings for " . $entry[ "hostname" ] . "</h2>\n";
	echo "<form action=\"?a=p&amp;i=" . $id . "#" . helperEncodeHTML( $entry[ "hostname" ] ) . "\" method=\"" .
		"post\" name=\"pxeform\">\n";
	echo "<table class=\"editlist\">\n";
	
	// Show notice if readonly is enabled
	if ( $ro )
	{
		echo rowHighlight() . "<td colspan=\"2\" style=\"color: red\">Lacking permissions. Running in " .
			"readonly mode!</td></tr>\n";
	}
	
	$tabindex = 1;
	
	// Hostname
	echo rowHighlight() . "<td>Hostname</td><td colspan=\"2\"><span style=\"font-weight: bold\">" .
		helperEncodeHTML( $entry[ "hostname" ] ) . "</span>&nbsp;&nbsp;&nbsp;&nbsp;(DHCP IP: " .
		helperEncodeHTML( $pxecfg[ "ip" ] ) . " / DHCP MAC: " . helperEncodeHTML( $pxecfg[ "mac" ] ) .
		")</td></tr>\n";
	
	// Architecture
	echo rowHighlight() . "<td>Architecture</td><td colspan=\"2\">";
	
	if ( empty( $entry[ "arch" ] ) || $entry[ "arch" ] == "na" )
		echo "Not set.";
	else
	{
		echo helperEncodeHTML( $entry[ "arch" ] );
		$arch = $entry[ "arch" ];
	}
	
	echo "</td></tr>\n";
	
	// PXE active?
	echo rowHighlight() . "<td>Status</td><td colspan=\"2\">";
	echo "<input type=\"radio\" name=\"status\" value=\"1\" id=\"status-on\" ";
	
	if ( $pxecfg[ "active" ] )
		echo "checked=\"checked\" ";
	
	echo "tabindex=\"" . ++$tabindex . "\" $ro_html1/> <label for=\"status-on\"><span style=\"color: green\">" .
		"PXE enabled</span></label>";
	
	echo "&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<input type=\"radio\" name=\"status\" value=\"0\" id=\"status-off\" ";
	
	if ( !$pxecfg[ "active" ] )
		echo "checked=\"checked\"";
	
	echo "tabindex=\"" . ++$tabindex . "\" $ro_html1/> <label for=\"status-off\"><span style=\"color: orange\">" .
		"PXE disabled</span></label>";
	
	echo "</td></tr>\n";
	
	// Kernel selection
	echo rowHighlight() . "<td>PXE kernel ";
	
	$theme->printTooltip( "#", "?", "Hint: Only kernels matching the architecture of this machine are shown if " .
		"the architecture field is set." );
	
	echo "</td><td colspan=\"2\">";
	echo "<select name=\"kernelid\" size=\"1\" onchange=\"document.pxeform.submit()\" tabindex=\"" . ++$tabindex .
		" $ro_html1\">\n";
	
	echo "<option>-</option>\n";
	
	// Print list of available kernels
	foreach ( $pxekernels as $kernel )
	{
		// If architecture of the machine is known skip entry is architecture does not match
		if ( isset( $arch ) )
		{
			$kernelsplit = explode( ".", $kernel[ "filename" ] );
			if ( count( $kernelsplit ) == 2 && $kernelsplit[ 1 ] != $arch )
				continue;
		}
		
		// Print entry
		echo "<option value=\"" . $kernel[ "id" ] . "\"";
		
		if ( $kernel[ "filename" ] == $curkernel[ "filename" ] )
			echo " selected=\"selected\"";
		
		echo ">" . helperEncodeHTML( $kernel[ "filename" ] ) . " - " . helperEncodeHTML( $kernel[
			"description" ] ) . "</option>\n";
	}
	
	echo "</select>";
	
	// Print current setting
	if ( !empty( $pxecfg[ "kernel" ] ) )
		echo "&nbsp;&nbsp;&nbsp;&nbsp;Currently set: " . helperEncodeHTML( $pxecfg[ "kernel" ] );
	
	echo "</td></tr>\n";
	
	// List available parameter lines
	if ( isset( $curkernel[ "paramlines" ] ) )
	{
		echo rowHighlight() . "<td>Suggested options</td><td colspan=\"2\">";
		
		foreach ( $curkernel[ "paramlines" ] as $pl )
		{
			echo "<input type=\"radio\" name=\"choose_pl\" value=\"" . $pl[ "id" ] . "\" tabindex=\"" .
				++$tabindex . "\" id=\"pl" . $pl[ "id" ] . "\" onchange=\"document.pxeform.submit()" .
				"\" />\n";
			echo "<label for=\"pl" . $pl[ "id" ] . "\">" . helperEncodeHTML( $pl[ "text" ] ) .
				"</label><br />\n";
		}
		
		echo "</td></tr>\n";
	}
	
	// Allow the user to edit the options
	echo rowHighlight() . "<td>Options ";
	
	$theme->printTooltip( "#", "?", "CR/LF = space, special strings: %host, %ip, %mac, %netmask, %gateway" );
	
	echo "</td><td>";
	
	echo "<textarea name=\"options\" cols=\"100\" rows=\"10\" tabindex=\"" . ++$tabindex . "\" $ro_html2>" .
		helperEncodeHTML( str_replace( " ", "\n", $pxecfg[ "options" ] ) ) . "</textarea>\n";
	echo "<p>Recognized specials: %ip, %mac, %netmask, %gateway</p>\n";
	
	echo "</td><td>";
	
	if ( $curkernel[ "type" ] == 1 )
	{
		echo "<b>This is a SUSE kernel.</b></p>\n";
		echo "<p>Generally you should have at least the bold options given to the kernel for a successful " .
			"boot:\n";
		echo "<ul>\n";
		echo "<li><b>initrd=name</b> - Provide the initial ramdisk for the kernel.</li>\n";
		echo "<li><b>install=path</b> - NFS location of the CD images for installation.</li>\n";
		echo "<li><b>hostip=ip netmask=mask gateway=ip</b> - Network configuration.</li>\n";
		echo "<li><b>vnc=1 vncpassword=install</b> - To allow VNC access during the installation.</li>\n";
		echo "<li><b>netdevice=ethX</b> - Network interface to use.</li>\n";
	}
	else if ( $curkernel[ "type" ] == 2 )
	{
		echo "<p><b>This is a Red Hat kernel.</b></p>\n";
		echo "<p>Generally you should have at least the bold options given to the kernel for the successful " .
			"boot:\n";
		echo "<ul>\n";
		echo "<li><b>initrd=name</b> - Provide the initial ramdisk for the kernel.</li>\n";
		echo "<li><b>method=nfs:server:/path</b> - NFS location of the CD images for installation.</li>\n";
		echo "<li><b>ip=dhcp</b> - Active DHCP for network interface.</li>\n";
		echo "<li><b>vnc=1 vncpassword=install</b> - To allow VNC access during the installation.</li>\n";
		echo "<li><b>ksdevice=MAC</b> - Network interface to use.</li>\n";
		echo "<li><b>lang=name</b> - Language (if omitted => select over telnet!) [en_US].</li>\n";
		echo "<li><b>keymap=name</b> - Keymap (if omitted => select over telnet!) [de-latin1-nodeadkeys].</li>";
		echo "<li>text - Pure text mode</li>\n";
	}
	else
	{
		echo "<p><b>This is no SLES or RHEL kernel.</b></p>\n";
		echo "<p>Common options:\n";
		echo "<ul>\n";
		echo "<li>initrd=name - Provide the initial ramdisk for the kernel.</li>\n";
	}
	
	echo "<li>console=tty0 console=ttyS0,[19200/57600]n8 - Enable serial console (if wanted).</li>\n";
	echo "<li>kernel.sysrq=1 - Enable magic SysRq key (if wanted).</li>\n";
	echo "<li>vga=normal - Disable frame buffer (if wanted).</li>\n";
	echo "<li>noipv6 - Disable IPv6 to speed up boot process.</li>\n";
	echo "<li>insmod=xxx - Load kernel module to bypass hw detection, e.g. bnx2 network adapter.</li>\n";
	echo "<li>init=file - Instruct the kernel to launch file instead of /sbin/init.</li>\n";
	echo "<li>noht, noapic, noprobe, noacpi, pci=off - Disable kernel features.</li>\n";
	echo "</ul>\n";
	
	echo "</td></tr>\n";
	
	// PXE watchdog enabled?
	echo rowHighlight() . "<td>Watchdog ";
	
	$theme->printTooltip( "#", "?", "The watchdog is a small helper utility intended for PXE installations that " .
		"watches the logfile on the PXE server and disables PXE booting for this machine after the first " .
		"successful PXE boot." );
	
	echo "</td><td colspan=\"2\">";
	
	echo "<input type=\"checkbox\" name=\"watchdog\" value=\"yes\" ";
	
	if ( isset( $_POST[ "watchdog" ] ) )
		echo "checked=\"checked\"";
	
	echo "tabindex=\"" . ++$tabindex . "\" id=\"watchdog\" $ro_html1/>\n";
	echo "<label for=\"watchdog\">Enable the PXE watchdog.</label>";
	
	echo "</td></tr>\n";
	
	// Print buttons
	echo rowHighlight() . "<td colspan=\"3\"><input type=\"submit\" name=\"submitb\" value=\"Change PXE " .
		"settings\" tabindex=\"" . ++$tabindex . "\" $ro_html1/>&nbsp;<input type=\"reset\" name=\"reset\" " .
		"value=\"Discard changes\" tabindex=\"" . ++$tabindex . "\" $ro_html1/>\n";
	
	echo "</table>\n";
	echo "</form>\n";
}

?>
