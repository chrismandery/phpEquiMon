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
 Helper function for cronRun.
 */
function machineMail( $entry, $text )
{
	global $backend;
	
	// Mailtarget field set?
	if ( !empty( $entry[ "mailtarget" ] ) && helperIsMail( $entry[ "mailtarget" ] ) )
		$recp = $entry[ "mailtarget" ];
	
	// Usedby 1
	else if ( $entry[ "usedby_id1" ] || $entry[ "usedby_id2" ] )
	{
		if ( $entry[ "usedby_id1" ] )
		{
			$username = "";
			$contact = $backend->getContact( $entry[ "usedby_id1" ], $username );
			
			if ( $contact )
			{
				$contact = unserialize( $contact );
				
				if ( isset( $contact[ "mail" ] ) && helperIsMail( $contact[ "mail" ] ) )
				{
					if ( helperIsAlnum( $username ) )
						$recp = $username . " <" . $contact[ "mail" ] . ">";
					else
						$recp = $contact[ "mail" ];
				}
			}
		}
		
		if ( $entry[ "usedby_id2" ] )
		{
			$username = "";
			$contact = $backend->getContact( $entry[ "usedby_id2" ], $username );
			
			if ( $contact )
			{
				$contact = unserialize( $contact );
				
				if ( isset( $contact[ "mail" ] ) && helperIsMail( $contact[ "mail" ] ) )
				{
					if ( isset( $recp ) && !empty( $recp ) )
					{
						if ( helperIsAlnum( $username ) )
							$recp .= ", $username <" . $contact[ "mail" ] . ">";
						else
							$recp .= ", " . $contact[ "mail" ];
					}
					else
					{
						if ( helperIsAlnum( $username ) )
							$recp = $username . " <" . $contact[ "mail" ] . ">";
						else
							$recp = $contact[ "mail" ];
					}
				}
			}
		}
	}
	
	// No destionation? Then use admin address or abort.
	if ( !isset( $recp ) || empty( $recp ) )
	{
		$recp = $backend->readConfig( "admin_mail" );
		
		if ( !helperIsMail( $recp ) )
			return;
	}
	
	if ( empty( $recp ) )
		return;
	
	helperSendMail( $recp, "phpEquiMon: " . $entry[ "hostname" ] . " needs attention", $text );
}

/**
 * Runs the script as a cronjob. Performs all kind of maintenance tasks and should be run every 1-10 minutes.
 */
function cronRun()
{
	global $backend, $CONF;
	
	// Abort if another cron run is running in phase 1
	if ( file_exists( $CONF[ "dbdir" ] . "cronlock-1" ) )
	{
		$backend->logEvent( 0, "Cron locked: Skipping phase 1." );
		return;
	}
	
	// Lock phase 1 (this is not really save but since cronjobs are invoked with some minutes in between, it is
	// no problem here of course)
	touch( $CONF[ "dbdir" ] . "cronlock-1" );
	touch( $CONF[ "dbdir" ] . "lastcron" );
	
	// Get list of all machines from database
	$machines = $backend->getList();
	
	// Ping all machines with fping
	if ( !empty( $machines ) )
	{
		$cmd = $backend->readConfig( "prog_fping" ) . " -a";
		
		foreach( $machines as $m )
		{
			$cmd .= " " . escapeshellarg( $m[ "hostname" ] . "r" ) . " " . escapeshellarg( $m[
				"hostname" ] );
		}
		
		$cmd .= " 2> /dev/null";
		
		exec( $cmd, $output );
		
		// Update connectivity status of all machines
		foreach ( $machines as $id => $m )
		{
			// Machine itself
			if ( in_array( $m[ "hostname" ], $output ) )
				$backend->setLastPing( $id, time() );
			else if ( time() - $m[ "lastping" ] < $backend->readConfig( "cron_interval" ) + 60 )
			{
				$backend->logEvent( 0, $m[ "hostname" ] . " (id = $id) went OFFLINE!" );
				
				// Connectivity mail information enabled for this machine?
				if ( $m[ "mailopts" ] )
				machineMail( $m, $m[ "hostname" ] . " went offline.\r\n" );
			}
			else if ( !$m[ "lastping" ] )
				$backend->setLastPing( $id, 1 );
			
			// Remote access card
			if ( in_array( $m[ "hostname" ] . "r", $output ) )
				$backend->setRemoteAvail( $id, true );
			else
				$backend->setRemoteAvail( $id, false );
		}
	}
	
	// Completed phase 1, remove lock
	unlink( $CONF[ "dbdir" ] . "cronlock-1" );
	
	// Try to enter phase 2
	if ( file_exists( $CONF[ "dbdir" ] . "cronlock-2" ) )
	{
		$backend->logEvent( 0, "Cron locked: Skipping phase 2." );
		return;
	}
	
	// Phase 2 entered
	touch( $CONF[ "dbdir" ] . "cronlock-2" );
	
	// Get list of all users
	$users = $backend->listAuthUsers();
	
	// Update contact information for all users
	foreach ( $users as $user )
	{
		// Does the user have LDAP updates disabled?
		$serstr = $backend->loadUserSettings( $user[ "id" ] );
		if ( $serstr )
		{
			$settings = unserialize( $serstr );
			
			if ( isset( $settings[ "forbidldap" ] ) )
				continue;
		}
		
		// Fetch LDAP info
		$contacts = helperGetLDAPInfo( $user[ "username" ] );
		
		if ( $contacts )
		{
			// Save result in database
			$contact = serialize( $contacts );
			$backend->updateContact( $user[ "id" ], $contact );
		}
	}
	
	// Loop over all machines
	foreach ( $machines as $id => $m )
	{
		// Check for file in the monfiles dir if monfiles are enabled
		$monfiles_data = helperReadMonfile( $m[ "hostname" ] );
		$backend->setMonfilesData( $id, serialize( $monfiles_data ) );
		
		// Do WBEM query for all machines that are up and are not updated recently
		if ( ( time() - $m[ "lastping" ] < 3600 ) && (
			( !empty( $m[ "wbem_info" ] ) && ( time() - $m[ "wbem_lastupdate" ] > $backend->readConfig(
			"cron_interval" ) * ( 2 + rand( 0, 2 ) ) ) ) ||
			( empty( $m[ "wbem_info" ] ) && ( time() - $m[ "wbem_lastupdate" ] > $backend->readConfig(
			"cron_interval" ) * ( 100 + rand( 0, 10 ) ) ) ) ) )
		{
			$wbem = helperQueryWBEM( $m[ "hostname" ] );
			
			if ( $wbem )
				$backend->setWBEMInformation( $id, $wbem );
			else
				$backend->setWBEMInformation( $id, NULL );
		}
		
		// Abort loop for this machine if machine is not set to expire
		if ( !$m[ "expiredate" ] || !$m[ "expirestate" ] || !helperIsDate( $m[ "expiredate" ] ) )
			continue;
		
		$expirefields = explode( "-", $m[ "expiredate" ] );
		$expirediff = mktime( 0, 0, 0, $expirefields[ 1 ], $expirefields[ 2 ], $expirefields[ 0 ] ) - mktime();
		
		$domail = false;
		
		// Send mail if machine is near expiration
		if ( $m[ "expirestate" ] > 0 && $expirediff < $m[ "expirestate" ] * 3600 * 24 )
		{
			$backend->setExpirationState( $id, -1 );
			
			machineMail( $m, "The lease of the machine " . $m[ "hostname" ] . " expires in " .
				$m[ "expirestate" ] . " days on " . $m[ "expiredate" ] . ".\r\n" );
		}
		
		// Send mail if machine has expired
		if ( $expirediff < 0 )
		{
			$backend->setExpirationState( $id, 0 );
			
			machineMail( $m, "The lease of the machine " . $m[ "hostname" ] . " has just expired.\r\n" );
		}
	}
	
	// Clean old log entries
	$backend->cleanEvents( 30 );
	
	// Perform daily database backup dump, if not already existing and VACUUM the database if using SQLite
	$backupname = $CONF[ "dbdir" ] . "backups/backupdump-" . date( "Ymd" ) . ".gz";
	$sqliteprog = $backend->readConfig( "prog_sqlite" );
	if ( !file_exists( $backupname ) && !empty( $sqliteprog ) )
	{
		$cmd = $backend->readConfig( "prog_sqlite" )  . " " . $CONF[ "dbdir" ] . "database" .
			" .dump | gzip > $backupname";
		exec( $cmd );
		
		$q = new DatabaseQuery( $backend, "VACUUM;" );
		$q->execute();
		$q->finish();
	}
	
	// Finished phase 2
	unlink( $CONF[ "dbdir" ] . "cronlock-2" );
}

?>
