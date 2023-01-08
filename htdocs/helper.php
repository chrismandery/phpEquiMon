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
 * Exception handler.
 * @param various $e Exception.
 */
function helperHandleException( $e )
{
	echo "<p class=\"err\">Terminating on unhandled exception. No further information is shown here for " .
		"security reasons.</p>\n";
}

/**
 * Returns an array with a available field names.
 * @return array All available fields.
 */
function helperFieldList()
{
	return array( "hostname", "id", "group", "lastupdate", "updateby", "vendor", "model", "state", "usedby",
		"arch", "assettag", "expiredate", "expirestate", "ip", "mac", "mfdata", "room", "notes", "sysinfo",
		"os", "cpu", "mem", "disk", "kernel", "libc", "compiler", "rack", "hostsystem", "mailtarget",
		"mailopts", "edit", "clone", "delete", "pxe", "wakeonlan", "groupedit" );
}

/**
 * Returns information about a database field.
 * @param string $name Name of the field.
 * @return array Array: desc => Description, sortable => Whether field is sortable
 */
function helperFieldInfo( $name )
{
	switch ( $name )
	{
		case "hostname": return array( "desc" => "Hostname", "extdesc" => "Displays the hostname.",
			"sortable" => true );
		
		case "connectivity": return array( "desc" => "Connectivity", "extdesc" => "Shows whether the machine " .
			"is online or how long it has been offline.", "sortable" => true );
		
		case "id": return array( "desc" => "ID", "extdesc" => "Displays the internal ID of the machine in " .
			"the database (usually not needed).", "sortable" => true );
		
		case "group": return array( "desc" => "Groupname", "extdesc" => "Displays the groupname of this " .
			"entry. Every member of this group is allowed to edit or delete this record.", "sortable" =>
			true );
		
		case "lastupdate": return array( "desc" => "Last Updated", "extdesc" => "Time when the machine " .
			"was updated last.", "sortable" => true );
		
		case "updateby": return array( "desc" => "Last Update By", "extdesc" => "User that updated this " .
			"record the last time.", "sortable" => true );
		
		case "vendor": return array( "desc" => "Vendor", "extdesc" => "Vendor which manufactured this " .
			"system.", "sortable" => true );
		
		case "model": return array( "desc" => "Model", "extdesc" => "Model name", "sortable" => true );
		
		case "state": return array( "desc" => "State", "extdesc" => "State: Free/In use", "sortable" => true );
		
		case "usedby": return array( "desc" => "Used By", "extdesc" => "One or two users that are " .
			"responsible for this machine. This does not imply any kind of editing permission for the " .
			"database, see vendor and groupname for that.", "sortable" => false );
		
		case "arch": return array( "desc" => "Architecture", "extdesc" => "Displays the architecture.",
			"sortable" => true );
		
		case "assettag": return array( "desc" => "Asset Tag", "extdesc" => "Displays the asset tag.",
			"sortable" => true );
		
		case "expiredate": return array( "desc" => "Expiration Date", "extdesc" => "Date after which this " .
			"record is considered to be expired (useful for temporary entries).", "sortable" => true );
		
		case "expirestate": return array( "desc" => "Expiration State", "extdesc" => "Settings for the " .
			"expiration system.", "sortable" => true );
		
		case "ip": return array( "desc" => "IP", "extdesc" => "Shows the machines IP address (and the MAC " .
			"address in the tooltip.", "sortable" => true );
		
		case "mac": return array( "desc" => "MAC Address", "extdesc" => "Shows the MAC address. You might " .
			"want to include the IP field instead which includes the MAC as a tooltip.",
			"sortable" => true );
		
		case "mfdata": return array( "desc" => "Monfiles Data", "extdesc" => "Shows the IPs and MACs that " .
			"machines itself claims to use, if the machine runs the monequip.sh script.", "sortable" =>
			true );
		
		case "room": return array( "desc" => "Room", "extdesc" => "Displays the location used for generation " .
			"of the room map.", "sortable" => true );
		
		case "notes": return array( "desc" => "Notes", "extdesc" => "Notes (free text).", "sortable" => true );
		
		case "sysinfo": return array( "desc" => "Sys Info", "extdesc" => "Multipurpose monitoring and " .
			"information system using icons (highly recommended!)", "sortable" => false );
		
		case "os": return array( "desc" => "OS information", "extdesc" => "OS. Consider using the Sys Info " .
			"field instead.", "sortable" => true );
		
		case "cpu": return array( "desc" => "CPU(s)", "extdesc" => "CPU information. Consider using the Sys " .
			"Info field instead.", "sortable" => true );
		
		case "mem": return array( "desc" => "Memory", "extdesc" => "Memory information. Consider using the " .
			"Sys Info field instead.", "sortable" => true );
		
		case "disk": return array( "desc" => "Disk(s)", "extdesc" => "Disk information. Consider using the " .
			"Sys Info field instead.", "sortable" => true );
		
		case "kernel": return array( "desc" => "Kernel", "extdesc" => "Kernel information. Consider using " .
			"the Sys Info field instead.", "sortable" => true );
		
		case "libc": return array( "desc" => "libc", "libc" => "libc information. Consider using the Sys " .
			"Info field instead.", "sortable" => true );
		
		case "compiler": return array( "desc" => "Compiler", "extdesc" => "Compiler information. Consider " .
			"using the Sys Info field instead.", "sortable" => true );
		
		case "rack": return array( "desc" => "Rack", "extdesc" => "Displays information about the type of " .
			"location where this system is hosted (rack/floor/virtual).", "sortable" => true );
		
		case "hostsystem": return array( "desc" => "Host System", "extdesc" => "Enter the host system here " .
			"for virtual machines. Not needed if the hostname matches [hostsystem]v[number].",
			"sortable" => true );
		
		case "mailtarget": return array( "desc" => "Mail Target", "extdesc" => "If set, mail information " .
			"concerning this equipment is mailed to the given address instead to the users set in the " .
			"used by field.", "sortable" => true );
		
		case "mailopts": return array( "desc" => "Mail Options", "extdesc" => "Specify when phpEquiMon " .
			"should send information mails about this machine.", "sortable" => false );
		
		case "edit": return array( "desc" => "Edit", "extdesc" => "Allows to edit the record.",
			"sortable" => false );
		
		case "clone": return array( "desc" => "Clone", "extdesc" => "Creates a new record starting with the " .
			"values of this entry.", "sortable" => false );
		
		case "delete": return array( "desc" => "Delete", "extdesc" => "Allows to delete the record.",
			"sortable" => false );
		
		case "pxe": return array( "desc" => "PXE Boot", "extdesc" => "Quicklink for setting the PXE settings " .
			"of this machine.", "sortable" => false );
		
		case "wakeonlan": return array( "desc" => "Wake On LAN", "extdesc" => "Upon clicked, generates a " .
			"special packet to wake up the machine.", "sortable" => false );
		
		case "groupedit": return array( "desc" => "Group Edit", "extdesc" => "Selection of records for " .
			"groupediting.", "sortable" => false );
		
		default: return false;
	}
}

/**
 * Reads a line from a file and cuts off any comments (starting with #).
 * @param string $f Handle to the file to read from.
 * @return string Read line.
 */
function helperReadLine( $f )
{
	$line = fgets( $f );	
	$chunks = explode( "#", $line, 2 );
	
	return trim( $chunks[ 0 ] );
}

/**
 * Encodes a string for use in HTML output.
 * @param string $s Input string.
 * @return string Output.
 */
function helperEncodeHTML( $s )
{
	global $backend;
	
	if ( $backend->readConfig( "enable_html" ) == "yes" )
		return $s;
	else
		return htmlspecialchars( $s );
}

/**
 * Checks whether a given string is a valid number (only digits).
 * @param string $s String to contain only digits.
 * @return boolean True if $s contains only digits.
 */
function helperIsDigit( $s )
{
	if ( function_exists( "ctype_digit" ) )
		return ctype_digit( $s );
	else
		return is_string( $s ) && preg_match( "/^[[:digit:]]*$/", $s );
}


/**
 * Checks whether a given string contains only alphanumerical characters.
 * @param string $s String to contain only alphanumerical characters.
 * @return boolean True if $s contains only alphanumerical characters.
 */
function helperIsAlnum( $s )
{
	if ( function_exists( "ctype_alnum" ) )
		return ctype_alnum( $s );
	else
		return is_string( $s ) && preg_match( "/^[[:alnum:]]*$/", $s );
}

/**
 * Checks whether a given string contains a valid IP address.
 * @param string $ip String to be a IP address.
 * @return boolean True if $ip is a IP address.
 */
function helperIsIP( $ip )
{
	return preg_match( "/^([[:digit:]]{1,3}.){3}[[:digit:]]{1,3}$/", $ip );
}

/**
 * Checks whether a given string contains a valid MAC address.
 * @param string $mac String to be a MAC address.
 * @return boolean True if $mac is a MAC address.
 */
function helperIsMAC( $mac )
{
	return preg_match( "/^([[:xdigit:]]{2}:){5}[[:xdigit:]]{2}$/", $mac );
}

/**
 * Checks whether a given string contains a valid date (YYYY-MM-DD).
 * @param string $date String to be a date.
 * @return boolean True if $date is a date.
 */
function helperIsDate( $date )
{
	return preg_match( "/^[[:digit:]]{4}(-[[:digit:]]{2}){2}$/", $date );
}

/**
 * Checks whether a given string contains a valid mail address.
 * @param string $mac String to be a MAC address.
 * @return boolean True if $mac is a MAc address.
 */
function helperIsMail( $mail )
{
	return preg_match( "/^[-+\\.0-9=a-z_]+@([-0-9a-z]+\\.)+([0-9a-z]){2,4}$/i", $mail );
}

/**
 * Checks whether a IP is in a given subnet.
 * @param string $ip IP
 * @param string $sn Subnet (ip/bits)
 * @return boolean Whether the IP is in the subnet.
 */
function helperCheckSubnet( $ip, $sn )
{
	// Split subnet string in IP and the number of important bits (like 24)
	$sns = explode( "/", $sn );
	
	// Sanity check
	if ( count( $sns ) != 2 || $sns[ 1 ] < 16 || $sns[ 1 ] > 31 )
		return false;
	
	// Convert IP into longs
	$ipl = ip2long( $ip );
	$snl = ip2long( $sns[ 0 ] );
	
	// Do the check
	$ipm = $ipl - ( $ipl % pow( 2, 32 - $sns[ 1 ] ) );
	return ( $ipm == $snl );
}

/**
 * Outputs a format size string, e.g. 7MB. (result is floor()ed)
 * @param integer $bytes Byte count
 * @param boolean If true, $bytes is specified in kilobyte
 * @return string Formatted size string
 */
function helperWriteSize( $bytes, $iskb = false )
{
	if ( $iskb )
		$bytes = $bytes * 1000;
	
	if ( $bytes > 2000000000 )
		return floor( $bytes / 1000000000 ) . "GB";
	else if ( $bytes > 2000000 )
		return floor( $bytes / 1000000 ) . "MB";
	else if ( $bytes > 2000 )
		return floor( $bytes / 1000 ) . "KB";
	else
		return $bytes . " bytes";
}

/**
 * Prints information about an user.
 * @param string $username Username.
 * @param string $contact Contact information string.
 */
function helperPrintUserInfo( $username, $contact )
{
	global $theme;
	
	$ca = unserialize( $contact );
	$ttarget = "#";
	
	$tooltip = "<b>User Details</b><br /><br />Username: " . helperEncodeHTML( $username );
	
	if ( isset( $ca[ "mail" ] ) )
	{
		$tooltip .= "<br />Mail: " . helperEncodeHTML( $ca[ "mail" ] );
		$ttarget = "mailto:" . helperEncodeHTML( $ca[ "mail" ] );
	}
	
	if ( isset( $ca[ "tel" ] ) )
		$tooltip .= "<br />Tel.: " . helperEncodeHTML( $ca[ "tel" ] );
	
	if ( isset( $ca[ "building" ] ) )
		$tooltip .= "<br />Building: " . helperEncodeHTML( $ca[ "building" ] );
	
	if ( isset( $ca[ "room" ] ) )
		$tooltip .= "<br />Room: " . helperEncodeHTML( $ca[ "room" ] );
	
	if ( !empty( $ca[ "realname" ] ) )
		$theme->printTooltip( $ttarget, helperEncodeHTML( $ca[ "realname" ] ), $tooltip );
	else
		$theme->printTooltip( $ttarget, helperEncodeHTML( $username ), $tooltip );
}

/**
 * Fetches a given URL with cURL from a remote web server. It disables a possibly set proxy and can get webpages over
 * https (SSL), even without a valid server certificate.
 * @param string $url URL of the page to fetch.
 * @param boolean $pxequery If true, this is a PXE query and includes our SSL certificate, if set.
 * @return array The fetched content in an array of lines.
 */
function helperFetchURL( $url, $postdata = false, $pxequery = false )
{
	global $backend;
	
	$curl = curl_init( $url );
	
	curl_setopt( $curl, CURLOPT_HEADER, false );
	curl_setopt( $curl, CURLOPT_PROXY, false );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, false );
	curl_setopt( $curl, CURLOPT_TIMEOUT, 15 );
	
	if ( $postdata )
	{
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $postdata );
	}
	
	if ( $pxequery )
	{
		$cert = $backend->readConfig( "pxe_cert" );
		$key = $backend->readConfig( "pxe_key" );
		$ca = $backend->readConfig( "pxe_ca" );
		
		if ( !empty( $cert ) && !empty( $key ) && !empty( $ca ) )
		{
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, true );
			curl_setopt( $curl, CURLOPT_SSLCERT, $cert );
			curl_setopt( $curl, CURLOPT_SSLKEY, $key );
			curl_setopt( $curl, CURLOPT_CAINFO, $ca );
		}
	}
	else
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
	
	$httpresp = curl_exec( $curl );
	
	if ( curl_errno( $curl ) )
	{
		echo "<p class=\"err\">cURL call failed: " . curl_error( $curl ) . "</p>\n";
		$content = array();
	}
	else
		$content = explode( "\n", $httpresp );
	
	curl_close( $curl );
	return $content;
}

/**
 * Performs a LDAP lookup for a given username and extracts information.
 * @param string $user Name to search for.
 * @return array Array with the fields "realname", "tel", "building" and "room" (if found). (false on LDAP error)
 */
function helperGetLDAPInfo( $user )
{
	global $backend;
	
	// LDAP enabled?
	$ldapserver = $backend->readConfig( "ldap_server" );
	$basedn = $backend->readConfig( "ldap_basedn" );
	if ( !$ldapserver || !$basedn )
		return array();
	
	// Connect to LDAP server
	$con = ldap_connect( $ldapserver );
	if ( !$con )
	{
		echo "<p class=\"err\">ldap_connect() failed.</p>\n";
		return false;
	}
	
	// Bind to LDAP directory
	if ( !ldap_bind( $con ) )
	{
		echo "<p class=\"err\">ldap_bind() failed.</p>\n";
		return false;
	}
	
	// Perform search
	$sr = ldap_search( $con, $basedn, "(uid=$user)" );
	if ( !$sr || ldap_count_entries( $con, $sr ) != 1 )
		return false;
	
	// Get search result
	$all = ldap_get_entries( $con, $sr );
	$e = $all[ 0 ];
	
	// Read values
	$reta = array();
	
	// User ID
	$reta[ "ldapuid" ] = $user;
	
	// Name
	if ( isset( $e[ "givenname" ] ) && isset( $e[ "sn" ] ) )
		$reta[ "realname" ] = $e[ "givenname" ][ 0 ] . " " . $e[ "sn" ][ 0 ];
	
	// Mail
	if ( isset( $e[ "mail" ] ) )
		$reta[ "mail" ] = $e[ "mail" ][ 0 ];
	
	// Telephone
	if ( isset( $e[ "telephonenumber" ] ) )
		$reta[ "tel" ] = $e[ "telephonenumber" ][ 0 ];
	
	// Building
	if ( isset( $e[ "houseidentifier" ] ) )
		$reta[ "building" ] = $e[ "houseidentifier" ][ 0 ];
	
	// Room
	if ( isset( $e[ "roomnumber" ] ) )
		$reta[ "room" ] = $e[ "roomnumber" ][ 0 ];
	
	return $reta;
}

/**
 * Searches for a file for a hostname generated by the monfiles script in the monfiles directory. The filename searched
 * for is [hostname]. When found, MACs and IPs are extracted and returned.
 * @param string $hostname Hostname to search for.
 * @return array Array with "ip" and "mac" field for every found network interface plus "arch" field.
 */
function helperReadMonfile( $hostname )
{
	global $backend, $CONF;
	
	// Read directory path from configuration
	$directory = $backend->readConfig( "monfiles_dir" );
	$maxage = $backend->readConfig( "monfiles_maxage" );
	if ( !$directory || !$maxage )
		return array();
	
	// Machine has mofnile?
	if ( file_exists( $directory . $hostname ) && ( time() - filemtime( $directory . $hostname ) < 3600 * 24 *
		$maxage ) )
	{
		// Read complete file into memory
		$content = file( $directory . $hostname );
		if ( !$content )
			return array();
		
		$ret = array();
		
		foreach ( $content as $line )
		{
			$line = trim( $line );
			
			// 32 Bit architecture?
			if ( preg_match( "/i[356]86 GNU\/Linux$/", $line ) )
				$ret[ "arch" ] = "i386";
			
			// 64 Bit architecture?
			else if ( preg_match( "/x86_64 GNU\/Linux$/", $line ) )
				$ret[ "arch" ] = "x86_64";
			
			// First piece of ifconfig output?
			else if ( preg_match( "/^[[:alnum:]]+[[:blank:]]+Link encap:Ethernet[[:blank:]]+HWaddr[[:blank:" .
				"]]+([[:xdigit:]]{2}:){5}[[:xdigit:]]{2}/", $line ) )
			{
				$interface = trim( substr( $line, 0, 6 ) );
				$mac = substr( $line, -17 );
			}
			
			// Second piece of ifconfig output?
			else if ( isset( $interface ) && preg_match( "/^[[:blank:]]*inet addr:([[:digit:]]{1,3}.){3}" .
				"[[:digit:]]{1,3}/", $line ) )
			{
				$tmp = explode( ":", $line, 3 );
				$tmp = explode( " ", $tmp[ 1 ], 2 );
				
				$ip = $tmp[ 0 ];
			}
			
			// Got all data for one interface?
			if ( isset( $interface ) && isset( $ip ) && isset( $mac ) )
			{
				// if ( helperIsIP( $ip ) && helperIsMAC( $mac ) )
					$ret[ "net_" . $interface ] = array( "ip" => $ip, "mac" => $mac );
				
				unset( $interface );
				unset( $ip );
				unset( $mac );
			}
		}
		
		return $ret;
	}
	else
		return array();
}

/**
 * Generates a new DHCP configuration file and returns it as a string but just writes the needed host lines, not the
 * general DHCP config which is usually written by the user. Included are all hosts with an working DNS entry (hostname
 * resolvs to IP) and an existing SOL file read by helperParseSOLFile() which are in the right subnet for the given DHCP
 * server ID.
 * @param string $netmask Netmask (ip/bits)
 * @return string Generated output for the DHCP server, in one string.
 */
function helperGenerateDHCPConfig( $netmask )
{
	global $backend, $CONF;
	
	$machines = $backend->getList( -1, "hostname", false );
	
	$lines = "#\n";
	$lines .= "# The following group is auto-generated by phpEquiMon and inserted\n";
	$lines .= "# by phpEquiMon's PXE helper script into this configuration file.\n";
	$lines .= "#\n\n";
	$lines .= "group {\n\n";
	
	foreach ( $machines as $id => $m )
	{
		if ( empty( $m[ "ip" ] ) || !helperIsIP( $m[ "ip" ] ) || !helperCheckSubnet( $m[ "ip" ], $netmask ) )
			continue;
		
		if ( empty( $m[ "mac" ] ) || !helperIsMAC( $m[ "mac" ] ) )
		{
			$lines .= "\t# Omitting " . $m[ "hostname" ] . ": No MAC address in database.\n\n";
			continue;
		}
		
		$lines .= "\thost " . $m[ "hostname" ] . " {  # Generated by phpEquiMon (machine ID = $id)\n";
		$lines .= "\t\thardware ethernet " . $m[ "mac" ] . ";\n";
		$lines .= "\t\tfixed-address " . $m[ "ip" ] . ";\n";
		
		if ( $m[ "arch" ] == "ia64" )
			$lines .= "\t\tfilename \"bootia64.efi\";\n";
		
		$lines .= "\t}\n\n";
	}
	
	$lines .= "}\n";
	
	return $lines;
}

/**
 * Sends out an mail.
 * @param string $recipient Target mail address.
 * @param string $subject Subject of the mail.
 * @param string $content Mail body.
 * @return boolean Whether the mail could be sent out.
 */
function helperSendMail( $recipient, $subject, $content )
{
	global $backend;
	
	$headers = "From: phpEquiMon\r\n";
	$headers .= "Reply-To: " . $backend->readConfig( "admin_mail" ) . "\r\n";
	
	$success = mail( $recipient, $subject,
		"This is a mail from phpEquiMon running on " . $backend->readConfig( "installation_url" ) . ".\r\n" .
		"You are getting this mail because phpEquiMon thinks that an event has occured that requires your " .
		"intervention.\r\n" .
		"If you want to change the way phpEquiMon informs you, check your settings.\r\n" .
		"**********\r\n\r\n" . $content, $headers );
	
	if ( $success )
		$backend->logEvent( 0, "Sent mail to $recipient." );
	else
		$backend->logEvent( 0, "Failed to send mail to $recipient." );
	
	return $success;
}

/**
 * Parses a WBEM string from the database. WBEM information is saved in the database in the format: key1=v1|key2=v2|...
 * @param string $string The string to parse.
 * @return array Array: Keys => Values
 */
function helperParseWBEMString( $string )
{
	if ( empty( $string ) )
		return array();
	
	$pieces = explode( "|", $string );
	$wbem = array();
	
	foreach( $pieces as $p )
	{
		$pieces2 = explode( "=", $p );
		
		if ( count( $pieces2 ) != 2 )
			continue;
		
		$wbem[ $pieces2[ 0 ] ] = $pieces2[ 1 ];
	}
	
	return $wbem;
}

/**
 * Performs one single query to a WBEM query. Should not be called by hand.
 * @param $target Hostname target
 * @param $path Path to read.
 * @return array Array of lines with the result from wbemcli.
 * @see helperQueryWBEM.
 */
function helperWbemCli( $target, $path )
{
	global $backend;
	
	$wbemcli = escapeshellarg( $backend->readConfig( "prog_wbemcli" ) );
	$wbemuser = escapeshellarg( $backend->readConfig( "wbem_user" ) );
	$wbempassword = escapeshellarg( $backend->readConfig( "wbem_password" ) );
	
	if ( empty( $wbemcli ) || empty( $wbemuser ) || empty( $wbempassword ) )
		return false;
	
	$cmd = "https_proxy= $wbemcli ei -nl -noverify 'https://$wbemuser:$wbempassword@" . escapeshellarg( $target ) .
		":5989/$path' 2> /dev/null";
	
	exec( $cmd, $output );
	
	return $output;
}

/**
 * Checks if a CIM/OpenPegasus server runs on $target and returns information about OS, mem, kernel and CPU(s). May hang
 * for some seconds.
 * @param string $target Hostname or IP of the machine to contact.
 * @return string WBEM information string.
 */
function helperQueryWBEM( $target )
{
	global $backend;
	
	$wbem = "";
	
	///////////////////////////
	/// CIM_OperatingSystem ///
	///////////////////////////
	
	$output = helperWbemCli( $target, "root/cimv2:CIM_OperatingSystem" );
	
	foreach ( $output as $line )
	{
		$parts = explode( "=", $line, 2 );
		
		if ( count( $parts ) != 2 )
			continue;
		
		switch( $parts[ 0 ] )
		{
		case "-ElementName":
			$wbem .= "os=" . trim( $parts[ 1 ], "\"" ) . "|";
			break;
		
		case "-TotalSwapSpaceSize":
			$swap = helperWriteSize( $parts[ 1 ], true );
			break;
		
		case "-TotalVisibleMemorySize":
			$wbem .= "mem=" . helperWriteSize( $parts[ 1 ], true );
			
			if ( $swap )
				$wbem .= " (" . $swap . " swap)";
			
			$wbem .= "|";
			
			break;
		}
	}
	
	/////////////////////
	/// CIM_Processor ///
	/////////////////////
	
	$output = helperWbemCli( $target, "root/cimv2:CIM_Processor" );
	
	foreach ( $output as $line )
	{
		$parts = explode( "=", $line, 2 );
		
		if ( count( $parts ) != 2 )
			continue;
		
		if ( $parts[ 0 ] == "-ElementName" )
		{
			if ( isset( $procs[ $parts[ 1 ] ] ) )
				++$procs[ $parts[ 1 ] ];
			else
				$procs[ $parts[ 1 ] ] = 1;
		}
	}
	
	if ( isset( $procs ) )
	{
		$wbem .= "cpu=";
		
		foreach ( $procs as $name => $count )
		{
			$name = str_replace( "&#32;", "", $name );
			$wbem .= $count . "x " . trim( trim( $name, "\"" ) ) . " ";
		}
	}
	else
		$wbem = substr( $wbem, 0, -1 );
	
	////////////////
	/// CIM_Card ///
	////////////////
	
	$output = helperWbemCli( $target, "root/cimv2:CIM_Card" );
	
	foreach ( $output as $line )
	{
		$parts = explode( "=", $line, 2 );
		
		if ( count( $parts ) != 2 )
			continue;
		
		switch( $parts[ 0 ] )
		{
			case "-SerialNumber":
				$wbem .= "assettag=" . trim( $parts[ 1 ], "\"" ) . "|";
				break;
		}
	}
	
	//////////////////////
	/// CIM_FileSystem ///
	//////////////////////
	
	$output = helperWbemCli( $target, "root/cimv2:CIM_FileSystem" );
	
	foreach ( $output as $line )
	{
		$parts = explode( "=", $line, 2 );
		
		if ( count( $parts ) != 2 )
			continue;
		
		switch ( $parts[ 0 ] )
		{
		case "-Name":
			if ( isset( $pname ) && isset( $psize ) && isset( $pfree ) && isset( $pperc ) )
				$wbem .= "$pname=$pperc% of $psize (free: $pfree)|";
			
			$pname = trim( $parts[ 1 ], "\"" );
			
			unset( $psize );
			unset( $pfree );
			unset( $pperc );
			
			break;
		
		case "-FileSystemSize":
			$psize = helperWriteSize( $parts[ 1 ] );
			break;
		
		case "-AvailableSpace":
			$pfree = helperWriteSize( $parts[ 1 ] );
			break;
		
		case "-PercentageSpaceUse":
			$pperc = $parts[ 1 ];
			break;
		}
	}
	
	if ( isset( $pname ) && isset( $psize ) && isset( $pfree ) && isset( $pperc ) )
		$wbem .= "$pname=$pperc% of $psize (free: $pfree)|";
	
	return $wbem;
}

?>
