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

define( "PHPEQUIMON", true );
require( "config.php" );

require( "helper.php" );
require( "pxecontroller.php" );
require( "theme.php" );
require( "session.php" );
require( "database.php" );

function run()
{
	global $backend, $CONF, $lastcronfailed, $session, $theme;
	
	$starttime = microtime();
	
	// Set exception handler
	set_exception_handler( "helperHandleException" );
	
	// Check for magic quoting "feature"/annoyance
	if ( get_magic_quotes_gpc() )
		die( "Please disable PHP Magic Quoting.\n" );
	
	// Check for complete config
	if ( !isset( $CONF[ "dbdir" ] ) || !isset( $CONF[ "pdostring" ] ) || !isset( $CONF[ "release" ] ) )
		die( "Please setup config.php.\n" );
	
	// Add slash to database dir if not existing
	$dbdir_lastchar = substr( $CONF[ "dbdir" ], -1 );
	if ( $dbdir_lastchar != "/" && $dbdir_lastchar != "\\" )
		$CONF[ "dbdir" ] .= "/";
	
	// Initialize database backends
	$backend = new DatabaseBackend();
	
	if ( $backend->readConfig( "dbversion" ) != $CONF[ "release" ] )
		echo "<p class=\"err\">Wrong database version. Expect unpredictable behavior.</p>";
	
	// Check whether the cronjob is working
	if ( file_exists( $CONF[ "dbdir" ] . "lastcron" ) )
	{
		$lastcronfailed = time() - filemtime( $CONF[ "dbdir" ] . "lastcron" ) > $backend->readConfig(
			"cron_interval" ) + 60;
	}
	else
		$lastcronfailed = true;
	
	if ( isset( $_SERVER[ "argc" ] ) && $_SERVER[ "argc" ] == 2 && isset( $_SERVER[ "argv" ] ) &&
		$_SERVER[ "argv" ][ 1 ] == "cron" )
	{
		// Run cron when invoked on the CLI with the cron option set
		require( "cron.php" );
		cronRun();
		
		exit;
	}
	
	// Create session
	$session = new Session();
	
	// User wants logout?
	if ( isset( $_GET[ "logout" ] ) && $_GET[ "logout" ] == "true" )
		$session->logout();  // Ends program execution
	
	// User wants login?
	else if ( isset( $_POST[ "login" ] ) && !empty( $_POST[ "username" ] ) && !empty( $_POST[ "password" ] ) )
	{
		if ( !$session->login( $_POST[ "username" ], $_POST[ "password" ] ) )
			$loginfailed = true;
	}
	
	// Auto login enabled and user has the right certificate?
	else if ( $backend->readConfig( "enable_autologin" ) == "yes" && !$session->getID() && isset( $_SERVER[
		"SSL_CLIENT_S_DN_CN" ] ) && $session->login( strtolower( $_SERVER[ "SSL_CLIENT_S_DN_CN" ] ), false ) )
		$autologindone = true;
	
	// Action code
	if ( isset( $_GET[ "a" ] ) )
		$ac = $_GET[ "a" ];
	else
		$ac = "i";
	
	// Select theme
	$themename = "default";
	if ( isset( $_GET[ "forcetheme" ] ) )
	{
		if ( !helperIsAlnum( $_GET[ "forcetheme" ] ) || !file_exists( "themes/" . $_GET[ "forcetheme" ] .
			"/theme.php" ) )
			$themefailed = $_GET[ "forcetheme" ];
		else
			$themename = $_GET[ "forcetheme" ];
	}
	else if ( isset( $session->_settings[ "theme" ] ) && helperIsAlnum( $session->_settings[ "theme" ] ) )
	{
		if ( !file_exists( "themes/" . $session->_settings[ "theme" ] . "/theme.php" ) )
			$themefailed = $session->_settings[ "theme" ];
		else
			$themename = $session->_settings[ "theme" ];
	}
	
	// Load theme
	require( "themes/$themename/theme.php" );
	
	if ( !( $theme instanceof ITheme ) )
		die( "Theme $themename is corrupt." );
	
	// Print page header
	if ( $ac == "e" )
		$theme->printHeader( true );
	else
		$theme->printHeader( false );
	
	// Show theme failed warning
	if ( isset( $themefailed ) )
		echo "<p class=\"err\">Could not find theme $themefailed. Using default.</p>\n";
	
	// Show message if logged on via auto login
	if ( isset( $autologindone ) )
	{
		echo "<p class=\"infobox\">Auto login: You have been logged in (Single Sign On) as " . $session->
			getUsername() . ".</p>\n";
	}
	
	// Show failed login warning
	if ( isset( $loginfailed ) )
		echo "<p class=\"err\">Login failed. Check credentials.</p>\n";
	else
	{
		// Update last use value for user
		$userid = $session->getID();
		
		if ( $userid )
			$backend->updateLastUse( $userid );
	}
	
	switch ( $ac )
	{
	case "a":
		require( "adminpanel.php" );
		pageAdminPanel();
		
		break;
	
	case "ak":
		require( "adminpanel.php" );
		require( "pxeadmin.php" );
		
		pageAdminPanel();
		pagePXEAdmin();
		
		break;
	
	case "am":
		require( "adminpanel.php" );
		require( "authman.php" );
		
		pageAdminPanel();
		pageAuthManagement();
		
		break;
	
	case "as":
		require( "adminpanel.php" );
		require( "syssettings.php" );
		
		pageAdminPanel();
		pageSystemSettings();
		
		break;
	
	case "c":
		require( "conscheck.php" );
		pageConsistencyCheck();
		
		break;
	
	case "e":
		require( "edit.php" );
		require( "list.php" );
		
		pageEdit();  // uses pageOutputList() internally
		
		break;
	
	case "h":
		require( "freehostnames.php" );
		require( "list.php" );
		
		pageFreeHostnames();  // uses pageOutputList() internally
		
		break;
	
	case "l":
		require( "eventlog.php" );
		pageEventLog();
		
		break;
	
	case "p":
		require( "list.php" );
		require( "pxe.php" );
		
		pagePXE();  // uses pageOutputList() internally
		
		break;
	
	case "s":
		require( "usersettings.php" );
		pageUserSettings();
		
		break;
	
	case "w":
		require( "list.php" );
		require( "wakeonlan.php" );
		
		pageWakeOnLAN();  // uses pageOutputList() internally
		
		break;
	
	case "x":
		require( "map.php" );
		pageShowMap();
		
		break;
	
	default:  // case "i":
		require( "list.php" );
		pageOutputList();
		
		break;
	}
	
	// Calculate execution time
	$endtime = microtime();
	$exectime = number_format( ( ( substr( $endtime, 0, 9 ) ) + ( substr( $endtime, -10 ) ) -
		( substr( $starttime, 0, 9 ) ) - ( substr( $starttime, -10 ) ) ), 4 );
	
	// Print page footer
	$theme->printFooter( $exectime, $backend->getQueryCount() );
	
	// Do safe cleanup and don't trust the PHP GC
	unset( $GLOBALS[ "theme" ] );
	unset( $GLOBALS[ "session" ] );
	unset( $GLOBALS[ "backend" ] );
}

run();

?>
