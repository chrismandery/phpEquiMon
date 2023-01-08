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
 * Allows admins to change various system settings and adjust their installation to the environment.
 */
function pageSystemSettings()
{
	global $backend, $session;
	
	// Update to main settings
	if ( isset( $_POST[ "submit-settings" ] ) &&
		isset( $_POST[ "installation_name" ] ) &&
		isset( $_POST[ "installation_url" ] ) &&
		isset( $_POST[ "admin_mail" ] ) &&
		isset( $_POST[ "ldap_server" ] ) &&
		isset( $_POST[ "ldap_basedn" ] ) &&
		isset( $_POST[ "cron_interval" ] ) &&
		isset( $_POST[ "vendors" ] ) &&
		isset( $_POST[ "architectures" ] ) &&
		isset( $_POST[ "rooms" ] ) &&
		isset( $_POST[ "prog_fping" ] ) &&
		isset( $_POST[ "prog_wbemcli" ] ) &&
		isset( $_POST[ "prog_sqlite" ] ) &&
		isset( $_POST[ "enable_map" ] ) &&
		isset( $_POST[ "fh_prefix" ] ) &&
		isset( $_POST[ "fh_start" ] ) &&
		isset( $_POST[ "monfiles_dir" ] ) &&
		isset( $_POST[ "monfiles_maxage" ] ) &&
		isset( $_POST[ "wol_addr" ] ) &&
		isset( $_POST[ "wol_port" ] ) &&
		isset( $_POST[ "wbem_user" ] ) &&
		isset( $_POST[ "wbem_password" ] ) &&
		isset( $_POST[ "enable_html" ] ) &&
		isset( $_POST[ "enable_autologin" ] ) &&
		isset( $_POST[ "pxe_cert" ] ) &&
		isset( $_POST[ "pxe_key" ] ) &&
		isset( $_POST[ "pxe_ca" ] ) )
	{
		// Update configuration settings in database
		$backend->updateConfig( "installation_name", $_POST[ "installation_name" ] );
		$backend->updateConfig( "installation_url", $_POST[ "installation_url" ] );
		$backend->updateConfig( "admin_mail", $_POST[ "admin_mail" ] );
		$backend->updateConfig( "ldap_server", $_POST[ "ldap_server" ] );
		$backend->updateConfig( "ldap_basedn", $_POST[ "ldap_basedn" ] );
		$backend->updateConfig( "cron_interval", $_POST[ "cron_interval" ] );
		$backend->updateConfig( "vendors", $_POST[ "vendors" ] );
		$backend->updateConfig( "architectures", $_POST[ "architectures" ] );
		$backend->updateConfig( "rooms", $_POST[ "rooms" ] );
		$backend->updateConfig( "prog_fping", $_POST[ "prog_fping" ] );
		$backend->updateConfig( "prog_wbemcli", $_POST[ "prog_wbemcli" ] );
		$backend->updateConfig( "prog_sqlite", $_POST[ "prog_sqlite" ] );
		
		if ( $_POST[ "enable_map" ] == "yes" )
			$backend->updateConfig( "enable_map", "yes" );
		else
			$backend->updateConfig( "enable_map", "no" );
		
		$backend->updateConfig( "fh_prefix", $_POST[ "fh_prefix" ] );
		$backend->updateConfig( "fh_start", $_POST[ "fh_start" ] );
		$backend->updateConfig( "monfiles_dir", $_POST[ "monfiles_dir" ] );
		$backend->updateConfig( "monfiles_maxage", $_POST[ "monfiles_maxage" ] );
		$backend->updateConfig( "wol_addr", $_POST[ "wol_addr" ] );
		$backend->updateConfig( "wol_port", $_POST[ "wol_port" ] );
		$backend->updateConfig( "wbem_user", $_POST[ "wbem_user" ] );
		$backend->updateConfig( "wbem_password", $_POST[ "wbem_password" ] );
		$backend->updateConfig( "enable_html", $_POST[ "enable_html" ] );
		$backend->updateConfig( "enable_autologin", $_POST[ "enable_autologin" ] );
		$backend->updateConfig( "pxe_cert", $_POST[ "pxe_cert" ] );
		$backend->updateConfig( "pxe_key", $_POST[ "pxe_key" ] );
		$backend->updateConfig( "pxe_ca", $_POST[ "pxe_ca" ] );
		
		$backend->logEvent( $session->getID(), "System settings changed." );
		
		echo "<p class=\"infobox\">Settings saved.</p>\n";
	}
	
	// else display settings (and process changes on PXE servers)
	else
	{
		// Add new PXE server to database?
		if ( isset( $_POST[ "submit-addpxe" ] ) && isset( $_POST[ "addr" ] ) && isset( $_POST[ "iprange" ] ) )
			$backend->addPXE( $_POST[ "addr" ], $_POST[ "iprange" ] );
		
		// Or remove existing PXE server?
		else if ( isset( $_GET[ "deletepxe" ] ) && helperIsDigit( $_GET[ "deletepxe" ] ) )
			$backend->deletePXE( $_GET[ "deletepxe" ] );

?>

<h2>System Settings</h2>

<form method="post" action="?a=as">
<table class="editlist">

<tr class="editrow-xdark"><td>Setting Name</td><td>Setting Value</td><td>Description</td></tr>

<tr class="editrow-light"><td>Installation Name</td><td>
<input type="text" name="installation_name" value="<?php echo helperEncodeHTML( $backend->readConfig(
"installation_name" ) ); ?>" size="100" /></td><td>
Enter a name describing this installation here. This is currently only used for the page title and header.<br />
<i>Default: phpEquiMon</i></td></tr>

<tr class="editrow-dark"><td>Installation URL (with http:// + final slash)</td><td>
<input type="text" name="installation_url" value="<?php echo helperEncodeHTML( $backend->readConfig(
"installation_url" ) ); ?>" size="100" /></td><td>
The full URL to the index of phpEquiMon used for redirection when logging out. Please provide it with http/https and
a final slash like in the example below.<br />
<i>Default: http://localhost/</i></td></tr>
	
<tr class="editrow-light"><td>Admin Mail</td><td>
<input type="text" name="admin_mail" value="<?php echo helperEncodeHTML( $backend->readConfig( "admin_mail" ) ); ?>"
size="100" /></td><td>
Provide a mail address for the admin that gets information regarding entries without a responsible person entered in
the database.<br />
<i>Default: root@localhost</i></td></tr>

<tr class="editrow-dark"><td>LDAP Server</td><td>
<input type="text" name="ldap_server" value="<?php echo helperEncodeHTML( $backend->readConfig( "ldap_server" ) ); ?>"
size="100" /></td><td>
Hostname of a LDAP server to query contact information for all users. If this and the next entry are both not empty,
LDAP querying is automatically done every cron run.<br />
<i>Default: (empty)</i></td></tr>
	
<tr class="editrow-light"><td>LDAP Base DN</td><td>
<input type="text" name="ldap_basedn" value="<?php echo helperEncodeHTML( $backend->readConfig( "ldap_basedn" ) ); ?>"
size="100" /></td><td>
Base DN for looking up users via LDAP.<br />
<i>Default: (empty)</i></td></tr>

<tr class="editrow-dark"><td>Cron Interval</td><td>
<input type="text" name="cron_interval" value="<?php echo helperEncodeHTML( $backend->readConfig( "cron_interval" )
);?>" size="100" /></td><td>
For a fully functional phpEquiMon installation you need to run "/usr/bin/php /path/index.php cron" at a regular
interval, e.g. every fives minutes. This does things like pinging the machines, WBEM queries, LDAP queries, expiration
checks and other stuff. To allow phpEquiMon to check whether the cronjob works correctly, please provide the seconds
between two cron runs here.<br />
<i>Default: 300 (= 5 minutes)</i></td></tr>

<tr class="editrow-light"><td>Vendors</td><td>
<input type="text" name="vendors" value="<?php echo helperEncodeHTML( $backend->readConfig( "vendors" ) ); ?>"
size="100" /></td><td>
Provide a comma-separated list of vendors. These will be available when editing in a combobox.<br />
<i>Default: (empty)</i></td></tr>

<tr class="editrow-dark"><td>Architectures (comma separated)</td><td>
<input type="text" name="architectures" value="<?php echo helperEncodeHTML( $backend->readConfig( "architectures" )
); ?>" size="100" /></td><td>
Provide a comma-separated list of architectures. The default is usally okay but in case you have other architectures,
this is the place to add them. non-server is a bit special: It should be used for switches, printers etc. and is hidden
in the main index by default unless "Show all equipment" is selected.<br />
<i>Default: i386,x86_64,non-server</i></td></tr>

<tr class="editrow-light"><td>Rooms (comma separated)</td><td>
<input type="text" name="rooms" value="<?php echo helperEncodeHTML( $backend->readConfig( "rooms" ) ); ?>" size="100"
/></td><td>
Provide a comma-separated list of rooms. Currently, you only need to set this is you want to enable the room map.<br />
<i>Default: (empty)</i></td></tr>

<tr class="editrow-dark"><td>fping binary</td><td>
<input type="text" name="prog_fping" value="<?php echo helperEncodeHTML( $backend->readConfig( "prog_fping" ) ); ?>"
size="100" /></td><td>
Path to the fping binary (which must be installed setsuid root unless you dare to run the cronjob as root).<br />
<i>Default: /usr/sbin/fping</i></td></tr>

<tr class="editrow-light"><td>wbemcli binary</td><td>
<input type="text" name="prog_wbemcli" value="<?php echo helperEncodeHTML( $backend->readConfig( "prog_wbemcli" )
); ?>" size="100" /></td><td>
Path to the wbemcli binary. Note: WBEM is only enabled if you provide a WBEM user and password below.<br />
<i>Default: /usr/bin/wbemcli</i></td></tr>

<tr class="editrow-dark"><td>sqlite binary</td><td>
<input type="text" name="prog_sqlite" value="<?php echo helperEncodeHTML( $backend->readConfig( "prog_sqlite" ) ); ?>"
size="100" /></td><td>
Path to the sqlite(3) binary. If you do not use SQLite as the database backend, this setting is silently ignored but if
you use SQLite, it is used to generate daily gzipped database dumps in the database/backups subdirectory.<br />
<i>Default: /usr/bin/sqlite3</i></td></tr>

<tr class="editrow-light"><td>Enable room map</td><td>

<?php

		if ( $backend->readConfig( "enable_map" ) == "yes" )
		{
			echo "<input type=\"radio\" name=\"enable_map\" value=\"yes\" checked=\"checked\" /> Yes\n";
			echo "<input type=\"radio\" name=\"enable_map\" value=\"no\" /> No\n";
		}
		else
		{
			echo "<input type=\"radio\" name=\"enable_map\" value=\"yes\" /> Yes\n";
			echo "<input type=\"radio\" name=\"enable_map\" value=\"no\" checked=\"checked\" /> No\n";
		}

?>

</td><td>
Whether to enable the room map feature. For this to work, you need to define at least one room (see above).<br />
<i>Default: No</i>
</td></tr>

<tr class="editrow-dark"><td>Free hostnames prefix</td><td>
<input type="text" name="fh_prefix" value="<?php echo helperEncodeHTML( $backend->readConfig( "fh_prefix" ) ); ?>"
size="100" /></td><td>
If this and the next setting is set, phpEquiMon displays a new menu button that provides you with a feature to quickly
generate numerated hostnames. phpEquiMon will show you ten free hostnames (= neither in database nor respond to pings).
It starts by probing [Free hostnames prefix][Free hostnames start], then [Free hostnames prefix][Free hostnames start +
1] etc.<br />
<i>Default: (empty)</i></td></tr>

<tr class="editrow-light"><td>Free hostnames start</td><td>
<input type="text" name="fh_start" value="<?php echo helperEncodeHTML( $backend->readConfig( "fh_start" ) ); ?>"
size="100" /></td><td>
See above. This must be a number.<br />
<i>Default: 1</i></td></tr>

<tr class="editrow-dark"><td>Monfiles directory</td><td>
<input type="text" name="monfiles_dir" value="<?php echo helperEncodeHTML( $backend->readConfig( "monfiles_dir" ) ); ?>"
size="100" /></td><td>
Path to a directory (likely on a NFS share) where your tracked machines can place monitoring information about them.
A monfile typically includes the output from uname and ifconfig. See the shipped util/monequip.example.sh!<br />
<i>Default: (empty)</i></td></tr>

<tr class="editrow-light"><td>Monfiles maximum age</td><td>
<input type="text" name="monfiles_maxage" value="<?php echo helperEncodeHTML( $backend->readConfig( "monfiles_maxage"
) ); ?>" size="100\" /></td><td>
Maximum age for a monfile to be considered valid. Monfiles with an older age are silently ignored as if they were not
present.<br />
<i>Default: 10</i></td></tr>

<tr class="editrow-dark"><td>Wake On LAN Address</td><td>
<input type="text" name="wol_addr" value="<?php echo helperEncodeHTML( $backend->readConfig( "wol_addr" ) ); ?>"
size="100" /></td><td>
Network address to send packets for Wake On LAN to. Usually your broadcast address.<br />
<i>Default: (empty)</i></td></tr>

<tr class="editrow-light"><td>Wake On LAN Port</td><td>
<input type="text" name="wol_port" value="<?php echo helperEncodeHTML( $backend->readConfig( "wol_port" ) ); ?>"
size="100" /></td><td>
Port to use for Wake On LAN. Commonly used are 0, 7 (echo) or 9 (discard).<br />
<i>Default: 9</i></td></tr>

<tr class="editrow-dark"><td>WBEM user</td><td>
<input type="text" name="wbem_user" value="<?php echo helperEncodeHTML( $backend->readConfig( "wbem_user" ) ); ?>"
size="100" /></td><td>
Username for WBEM.<br />
<i>Default: (empty)</i></td></tr>

<tr class="editrow-light"><td>WBEM password</td><td>
<input type="text" name="wbem_password" value="<?php echo helperEncodeHTML( $backend->readConfig( "wbem_password"
) ); ?>" size="100" /></td><td>
Password for WBEM.<br />
<i>Default: (empty)</i></td></tr>

<tr class="editrow-dark"><td>Allow HTML</td><td>

<?php

		if ( $backend->readConfig( "enable_html" ) == "yes" )
		{
			echo "<input type=\"radio\" name=\"enable_html\" value=\"yes\" checked=\"checked\" /> Yes " .
				"(Opens system for XSS attacks! Only use in trusted environments!)\n";
			echo "<input type=\"radio\" name=\"enable_html\" value=\"no\" /> No\n";
		}
		else
		{
			echo "<input type=\"radio\" name=\"enable_html\" value=\"yes\" /> Yes " .
				"(Opens system for XSS attacks! Only use in trusted environments!)\n";
			echo "<input type=\"radio\" name=\"enable_html\" value=\"no\" checked=\"checked\" /> No\n";
		}

?>

</td><td>
You can enable HTML for your users. If you set this to yes, no calls to htmlspecialchars() are performed and every
database output is given unescaped to your browser. You should NEVER enable this unless you have complete trust in all
your users since this renders you vulnerable to all sorts of (mostly XSS) attacks.<br />
<i>Default: No</i></td></tr>

<?php
		
		echo "<tr class=\"editrow-light\"><td>SSL auto login</td><td>";
		
		if ( $backend->readConfig( "enable_autologin" ) == "yes" )
		{
			echo "<input type=\"radio\" name=\"enable_autologin\" value=\"yes\" checked=\"checked\" /> " .
				"Yes (SSL needed!)\n";
			echo "<input type=\"radio\" name=\"enable_autologin\" value=\"no\" /> No\n";
		}
		else
		{
			echo "<input type=\"radio\" name=\"enable_autologin\" value=\"yes\" /> " .
				"Yes (SSL needed!)\n";
			echo "<input type=\"radio\" name=\"enable_autologin\" value=\"no\" checked=\"checked\" /> No\n";
		}

?>

</td><td>
If set to yes, users can login automatically via SSL certificates. This is an unsupported feature, see index.php for
details about how it works but do not complain if it does not work for you.<br />
<i>Default: No</i></td></tr>

<tr class="editrow-dark"><td>PXE helper certificate</td><td>
<input type="text" name="pxe_cert" value="<?php echo helperEncodeHTML( $backend->readConfig( "pxe_cert" ) ); ?>"
size="100" /></td><td>
Certificate to use when contacting PXE servers. This and the following two settings are only useful if you have an
SSL-secured link between phpEquiMon and the PXE servers and want to use SSL for authentification.<br />
<i>Default: (empty)</i></td></tr>

<tr class="editrow-light"><td>PXE helper key</td><td>
<input type="text" name="pxe_key" value="<?php echo helperEncodeHTML( $backend->readConfig( "pxe_key" ) ); ?>"
size="100" /></td><td>
Key matching to the certificate above for contacting PXE servers.<br />
<i>Default: (empty)</i></td></tr>

<tr class="editrow-dark"><td>PXE helper CA</td><td>
<input type="text" name="pxe_ca" value="<?php echo helperEncodeHTML( $backend->readConfig( "pxe_ca" ) ); ?>" size="100"
/></td><td>
CA for the certificate and the key above.<br />
<i>Default: (empty)</i></td></tr>

</td></tr>

<tr class="editrow-light"><td colspan="3"><input type="submit" name="submit-settings" value="Submit">
<input type="reset" name="reset" value="Reset"></td></tr>

</table>
</form>

<h2 style="margin-top: 30px">PXE Server</h2>

<form method="post" action="?a=as">
<p>Add: <input type="text" name="addr" value="http://hostname:port" size="60" />
<input type="text" name="iprange" value="Consigned IP range (a.b.c.d/x)" size="30" />
<input type="submit" name="submit-addpxe" value="Add!" /></p>
</form>

<table class="editlist">
<tr class="editrow-xdark"><td>Address</td><td>Consigned IP range</td><td>Status/health</td><td>Delete</td></tr>

<?php

		// Retrieve list from database
		$pxeserver = $backend->listAllPXE();
		$highlight = true;
		
		// Render list
		foreach ( $pxeserver as $p )
		{
			if ( $highlight )
				echo "<tr class=\"editrow-light\">";
			else
				echo "<tr class=\"editrow-dark\">";
			
			$highlight = !$highlight;
			
			echo "<td>" . helperEncodeHTML( $p[ "address" ] ) . "</td>";
			echo "<td>" . helperEncodeHTML( $p[ "iprange" ] ) . "</td>";
			
			$pxecon = new PXEController( $p[ "address" ] );
			$version = $pxecon->getVersion();
			
			if ( !$version )
				echo "<td style=\"color: red\">Connection FAILED.</td>";
			else if ( $version > 0 )
				echo "<td style=\"color: green\">Connection OK and ready. Procotol version: " .
					"$version. Managing " . count( $pxecon->config ) . ( count( $pxecon->config )
					== 1 ? " machine" : " machines" ) . ".</td>";
			else
			{
				echo "<td style=\"color: red\">Connection OK but NOT ready. Procotol version: " .
					-$version . "</td>";
			}
			
			echo "<td><a href=\"?a=as&amp;deletepxe=" . $p[ "id" ] . "\">Delete</a></td>";
			echo "</tr>\n";
		}
		
		echo "</table>\n";
	}
}

?>
