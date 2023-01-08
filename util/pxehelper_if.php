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

// Helper script for PXE/DHCP servers

require( "pxehelper_conf.php" );

// DO NOT CHANGE ANYTHING BEYOND THIS LINE

function generateFilename( $mac )
{
	global $CONF;
	
	return $CONF[ "pxe-cfg-dir" ] . "01-" . strtolower( str_replace( ":", "-", $mac ) );
}

function generateFilenameIA64( $ip )
{
	global $CONF;
	
	$ips = explode( ".", $ip );
	
	return $CONF[ "pxe-cfg-dir-ia64"] . sprintf( "%02X%02X%02X%02X", $ips[ 0 ], $ips[ 1 ], $ips[ 2 ], $ips[ 3 ] ) .
		".conf";
}

function readConfiguration()
{
	global $CONF;
	
	// Read DHCP information from the dhcpd.conf
	$dcf = fopen( $CONF[ "dhcp-cfg-file" ], "r" );
	if ( !$dcf )
		return false;
	
	while ( !feof( $dcf ) )
	{
		// Read a single line
		$line = fgets( $dcf );
		
		// Cut off comments
		$chunks = explode( "#", $line, 2 );
		$line = trim( $chunks[ 0 ] );
		
		// Start of a host definition?
		if ( !preg_match( "/^host [[:alnum:]]+ {/", $line ) )
			continue;
		
		// Extract hostname
		$line = substr( $line, 5 );
		$line = explode( " ", $line );
		
		$hostname = $line[ 0 ];
		
		// Now try to parse all information about this host from the config file
		$hasip = false;
		$hasmac = false;
		
		do
		{
			// Read a line until we have read all of this machine's entry
			$line = fgets( $dcf );
			
			// Cut off comments
			$chunks = explode( "#", $line, 2 );
			$line = trim( $chunks[ 0 ] );
			
			// IP address setting?
			if ( substr( $line, 0, 13 ) == "fixed-address" )
			{
				$line = str_replace( "fixed-address", "", $line );
				$line = str_replace( ";", "", $line );
				$hostip = trim( $line );
				$hasip = true;
			}
			
			// MAC address setting?
			else if ( substr( $line, 0, 17 ) == "hardware ethernet" )
			{
				$line = str_replace( "hardware ethernet", "", $line );
				$line = str_replace( ";", "", $line );
				$hostmac = strtoupper( trim( $line ) );
				$hasmac = true;
			}
		} while ( !feof( $dcf ) && $line != "}" );
		
		// Abort if the machine does not have a complete set of options
		if ( !$hasip || !$hasmac )
			continue;
		
		$kernel = "";
		$opts = "";
		$active = false;
		
		// Split IP into fields
		$ips = explode( ".", $hostip );
		
		// Generate PXE config filenames
		$cfgfilename = generateFilename( $hostmac );
		$cfgfilenameia64 = generateFilenameIA64( $hostip );
		
		$cfgfile = false;
		
		// Try to open file if it exists
		if ( file_exists( $cfgfilename ) )
		{
			$cfgfile = fopen( $cfgfilename, "r" );
			$active = true;
			$ia64 = false;
		}
		if ( file_exists( $cfgfilenameia64 ) )
		{
			$cfgfile = fopen( $cfgfilenameia64, "r" );
			$active = true;
			$ia64 = true;
		}
		else if ( file_exists( $cfgfilename . ".inactive" ) )
		{
			$cfgfile = fopen( $cfgfilename . ".inactive", "r" );
			$ia64 = false;
		}
		else if ( file_exists( $cfgfilenameia64 . ".inactive" ) )
		{
			$cfgfile = fopen( $cfgfilenameia64 . ".inactive", "r" );
			$ia64 = true;
		}
		
		// If config file found, read it!
		if ( $cfgfile )
		{
			while ( !feof( $cfgfile ) )
			{
				// Read single line
				$line = fgets( $cfgfile );
				$line = trim( $line );
				
				// Which file format? pxelinux.cfg or bootia64.efi style?
				if ( $ia64 )
				{
					// Kernel settings?
					if ( substr( $line, 0, 6 ) == "image=" && empty( $kernel ) )
					{
						$line = trim( substr( $line, 6 ), "\"" );
						
						// Remove kernel.-prefix from kernel name
						if ( substr( $line, 0, 7 ) == "kernel." )
							$kernel = substr( $line, 7 );
						else
							$kernel = $line;
					}
					
					// Kernel boot options
					else if ( substr( $line, 0, 7 ) == "append=" && !empty( $kernel ) && empty(
						$opts ) )
						$opts = trim( substr( $line, 8 ), "\"" );
				}
				else
				{
					// Kernel settings?
					if ( substr( $line, 0, 6 ) == "KERNEL" && empty( $kernel ) )
					{
						$line = substr( $line, 7 );
						
						// Remove kernel.-prefix from kernel name
						if ( substr( $line, 0, 7 ) == "kernel." )
							$kernel = substr( $line, 7 );
						else
							$kernel = $line;
					}
					
					// Kernel boot options
					else if ( substr( $line, 0, 6 ) == "APPEND" && !empty( $kernel ) && empty(
						$opts ) )
						$opts = substr( $line, 7 );
				}
			}
			
			// Close config file
			fclose( $cfgfile );
		}
		
		// Output data to phpEquiMon
		echo $hostname . "|";
		echo $hostip . "|";
		echo $hostmac . "|";
		echo $kernel . "|";
		echo $opts . "|";
		
		if ( $active )
			echo "active\n";
		else
			echo "inactive\n";
	}
	
	fclose( $dcf );
	
	return true;
}

function writeConfiguration()
{
	global $CONF;
	
	// Check that argument is given
	if ( !isset( $_POST[ "dhcpcfg" ] ) || empty( $_POST[ "dhcpcfg" ] ) )
		return false;
	
	// Make backup of existing DHCP config file
	if ( !copy( $CONF[ "dhcp-cfg-file" ], $CONF[ "dhcp-cfg-file" ] . ".backup" ) )
		return false;
	
	// Read existing DHCP config file
	$oldcfg = file( $CONF[ "dhcp-cfg-file" ] );
	
	// Write new configuration file
	$newcfg = fopen( $CONF[ "dhcp-cfg-file" ], "w" );
	
	// Copy content until marker or EOF
	foreach ( $oldcfg as $oldline )
	{
		if ( $oldline == "### PHPEQUIMON GENERATED CONTENT\n" )
			break;
		
		fwrite( $newcfg, $oldline );
	}
	
	fwrite( $newcfg, "### PHPEQUIMON GENERATED CONTENT\n" );
	
	// Append new content
	$newlines = explode( "\n", $_POST[ "dhcpcfg" ] );
	foreach ( $newlines as $newline )
		fwrite( $newcfg, $newline . "\n" );
	
	fclose( $newcfg );
	
	// Restart DHCP server if configured
	if ( !empty( $CONF[ "dhcp-restart-cmd" ] ) )
		exec( $CONF[ "dhcp-restart-cmd" ] );
	
	return true;
}

function writePXEConfiguration()
{
	global $CONF;
	
	// Get configuration string
	if ( !isset( $_POST[ "pxecfg" ] ) )
		return false;
	
	$cfgstr = $_POST[ "pxecfg" ];
	
	// Split configuration string
	$cfg = explode( "|", $cfgstr );
	if ( count( $cfg ) != 6 )
		return false;
	
	// i386/x86_64 or ia64?
	if ( $cfg[ 0 ] == "ia64" )
		$filename = generateFilenameIA64( $cfg[ 1 ] );
	else
		$filename = generateFilename( $cfg[ 1 ] );
	
	// Delete old configuration files
	if ( file_exists( $filename ) )
		unlink( $filename );
	
	if ( file_exists( $filename . ".inactive" ) )
		unlink( $filename . ".inactive" );
	
	// Start new configuration file
	if ( $cfg[ 2 ] == "enabled" )
		$cf = fopen( $filename, "w" );
	else
		$cf = fopen( $filename . ".inactive", "w" );
	
	if ( !$cf )
		return false;
	
	// Replace wildcards in PXE config
	$pxeopts = str_replace( array( "%netmask", "%gateway" ), array( $CONF[ "netmask" ], $CONF[ "gateway" ] ),
		$cfg[ 5 ] );
	
	// i386/x86_64 or ia64?
	if ( $cfg[ 0 ] == "ia64" )
	{
		// Write PXE config file for ia64 bootloader
		fwrite( $cf, "# Generated by phpEquiMon\n" );
		fwrite( $cf, "delay=20\n" );
		fwrite( $cf, "default=\"pxeboot\"\n" );
		fwrite( $cf, "image=\"kernel." . $cfg[ 4 ] . "\"\n" );
		fwrite( $cf, "label=\"pxeboot\"\n" );
		fwrite( $cf, "read-only\n" );
		fwrite( $cf, "relocatable\n" );
		
		if ( $pxeopts )
		{
			// Search for given initrd
			$optsexp = explode( " ", $pxeopts );
			
			foreach ( $optsexp as $curopt )
			{
				$optsplit = explode( "=", $curopt );
				
				if ( $optsplit[ 0 ] == "initrd" )
				{
					fwrite( $cf, "initrd=" . $optsplit[ 1 ] . "\n" );
					break;
				}
			}
			
			fwrite( $cf, "append=\"$pxeopts\"\n" );
		}
	}
	else
	{
		// Write PXE config file
		fwrite( $cf, "# Generated by phpEquiMon\n" );
		fwrite( $cf, "DEFAULT pxeboot\n" );
		fwrite( $cf, "TIMEOUT 100\n\n" );
		fwrite( $cf, "LABEL pxeboot\n" );
		fwrite( $cf, "KERNEL kernel." . $cfg[ 4 ] . "\n" );
		
		if ( $pxeopts )
		{
			fwrite( $cf, "APPEND $pxeopts\n" );
		}
	}
	
	fclose( $cf );
	
	// Initiate watchdog if desired
	if ( $cfg[ 3 ] == "wd" )
	{
		$cmd = "/usr/bin/php pxehelper_wa.php $filename > /dev/null 2>&1 &";
		system( $cmd );
	}
	
	return true;
}

// Check that action parameter is available
if ( !$_GET[ "pxeaction" ] )
	die( "acmiss\n" );

// Check origin of the request
if ( !empty( $CONF[ "phpequimon-addr" ] ) && ( $_SERVER[ "REMOTE_ADDR" ] != $CONF[ "phpequimon-addr" ] ) )
	die( "auth addr\n" );

// Check certiciate
if ( !empty( $CONF[ "phpequimon-certname" ] ) )
{
	if ( !isset( $_SERVER[ "SSL_CLIENT_S_DN_CN" ] ) )
		die( "certmiss\n" );
	
	if( $_SERVER[ "SSL_CLIENT_S_DN_CN" ] != $CONF[ "phpequimon-certname" ] )
		die( "certfail\n" );
}

switch ( $_GET[ "pxeaction" ] )
{
	case "status":
		echo "ready\n";
		echo "1\n";
		break;
	
	case "readconfig":
		if ( !readConfiguration() )
			echo "error\n";
		
		break;
	
	case "writeconfig":
		if ( writeConfiguration() )
			echo "ok\n";
		else
			echo "error\n";
		
		break;
	
	case "writepxeconfig":
		if ( writePXEConfiguration() )
			echo "ok\n";
		else
			echo "error\n";
		
		break;
	
	default:
		echo "error\n";
		break;
};

?>
