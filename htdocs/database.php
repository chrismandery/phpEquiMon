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
 * Database query class. Represents a single SQL query.
 * @package phpequimon
 */
class DatabaseQuery
{
	private $query = false;
	private $pdo = false;
	
	/**
	 * Creates a new query.
	 * @param instance $backend Reference to the database backend.
	 * @param string $query SQL query to create.
	 */
	public function __construct( &$backend, $query )
	{
		$this->query = $query;
		
		$this->pdo = $backend->db->prepare( $this->query );
		if ( !$this->pdo )
		{
			echo "<p class=\"err\">Preparing statement \"" . helperEncodeHTML( $query ) .
				"\" failed.</p>\n";
		}
	}
	
	/**
	 * Binds a value to an parameter.
	 * @param string $param Parameter string to bind to.
	 * @param string $value Value to bind.
	 */
	public function bindValue( $param, $value )
	{
		if ( !$this->pdo )
			echo "<p class=\"err\">PDO object not ready. (" . helperEncodeHTML( $this->query ) . ")</p>\n";
		
		if ( !$this->pdo->bindValue( $param, $value ) )
			echo "<p class=\"err\">bindValue() failed.</p>\n";
	}
	
	/**
	 * Executes the query.
	 * @param array $params Array of parameters for the query.
	 */
	public function execute( $params = false )
	{
		global $backend;
		
		if ( !$this->pdo )
			echo "<p class=\"err\">PDO object not ready. (" . helperEncodeHTML( $this->query ) . ")</p>\n";
		
		if ( !$backend->acquire() )
		{
			echo "<p class=\"err\">Could not acquire database. (" . helperEncodeHTML( $this->query ) .
				")</p>\n";
		}
		
		if ( $params )
		{
			if ( $this->pdo->execute( $params ) )
				return;
		}
		else
		{
			if ( $this->pdo->execute() )
				return;
		}
		
		echo "<p class=\"err\">execute() failed. (" . helperEncodeHTML( $this->query ) . ")</p>\n";
	}
	
	/**
	 * Closes a query and makes the connection ready for the next one. Theoretically one should only need to call
	 * this method after SELECT queries but I demand that it is called after EVERY query (checked via $backend->
	 * release() to allow possible optimization using transactions or optimized locking later).
	 */
	public function finish()
	{
		global $backend;
		
		if ( !$this->pdo )
			echo "<p class=\"err\">PDO object not ready. (" . helperEncodeHTML( $this->query ) . ")</p>\n";
		
		$this->pdo->closeCursor();
		
		$backend->release();
	}
	
	/**
	 * Standard fetch for results.
	 * @return Database result.
	 */
	public function fetch()
	{
		if ( !$this->pdo )
			echo "<p class=\"err\">PDO object not ready. (" . helperEncodeHTML( $this->query ) . ")</p>\n";
		
		return $this->pdo->fetch( PDO::FETCH_ASSOC );
	}
	
	/**
	 * Fetches a single column.
	 * @return Database result.
	 */
	public function fetchColumn()
	{
		if ( !$this->pdo )
			echo "<p class=\"err\">PDO object not ready. (" . helperEncodeHTML( $this->query ) . ")</p>\n";
		
		return $this->pdo->fetchColumn();
	}
	
	/**
	 * Fetches the whole result table.
	 * @return Database result.
	 */
	public function fetchAll()
	{
		if ( !$this->pdo )
			echo "<p class=\"err\">PDO object not ready. (" . helperEncodeHTML( $this->query ) . ")</p>\n";
		
		return $this->pdo->fetchAll( PDO::FETCH_ASSOC );
	}
}

/**
 * Database backend class. The whole communication with the underlying SQLite database is done in the class for easy
 * adjustment.
 * @package phpequimon
 */
class DatabaseBackend
{
	public $db = 0;  // Public because damn PHP has no friend keyword :-/
	private $queryCount = 0;
	private $ready = true;
	
	/**
	 * Opens the SQLite database.
	 */
	public function __construct()
	{
		global $CONF;
		
		try
		{
			if ( isset( $CONF[ "pdouser" ] ) && isset( $CONF[ "pdopass" ] ) )
				$this->db = new PDO( $CONF[ "pdostring" ], $CONF[ "pdouser" ], $CONF[ "pdopass" ] );
			else
				$this->db = new PDO( $CONF[ "pdostring" ] );
			
			$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			$this->db->setAttribute( PDO::NULL_EMPTY_STRING, true );
		}
		catch ( PDOException $e )
		{
			echo "<p class=\"err\">Database connect failed: " . $e->getMessage() . "</p>";
		}
	}
	
	/**
	 * Checks that we do a clean exit.
	 */
	public function __destruct()
	{
		if ( !$this->ready )
			echo "<p class=\"err\">Unclean database shutdown!</p>\n";
	}
	
	/**
	 * Acquires the database (atm this does nothing instead of changing $ready).
	 * @return boolean Success or not.
	 */
	public function acquire()
	{
		if ( $this->ready )
		{
			++$this->queryCount;
			$this->ready = false;
			return true;
		}
		else
			return false;
	}
	
	/**
	 * Releases the database.
	 */
	public function release()
	{
		$this->ready = true;
	}
	
	/**
	 * Returns the number of database queries made until now.
	 * @return integer Query counter.
	 */
	public function getQueryCount()
	{
		return $this->queryCount;
	}
	
	/**
	 * Reads a config setting from the settings table (or its cache if possible).
	 * @param string $key Key of the row to fetch
	 * @return string $value Returned value (or false).
	 */
	public function readConfig( $key )
	{
		static $configcache = array();
		
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "SELECT value FROM settings WHERE keyname = ?;" );
		
		if ( isset( $configcache[ $key ] ) )
			return $configcache[ $key ];
		
		$q->execute( array( $key ) );
		$value = $q->fetchColumn();
		$q->finish();
		
		$configcache[ $key ] = $value;
		
		return $value;
	}
	
	/**
	 * Updates a config setting in the settings table.
	 * @param string $key Key.
	 * @param string $value Value.
	 */
	public function updateConfig( $key, $value )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "UPDATE settings SET value = ? WHERE keyname = ?;" );
		
		$q->execute( array( $value, $key ) );
		$q->finish();
	}
	
	/**
	 * Returns a list of all available PXE servers and the IP range belonging to them.
	 * @return array Array with fields "id", "address" and "iprange".
	 */
	public function listAllPXE()
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "SELECT * FROM pxeserver;" );
		
		$q->execute();
		$list = $q->fetchAll();
		$q->finish();
		
		return $list;
	}
	
	/**
	 * Adds a PXE server to the database.
	 * @param string $address Address of the PXE server.
	 * @param string $iprange IP range serviced by the PXE server.
	 */
	public function addPXE( $address, $iprange )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "INSERT INTO pxeserver( address, iprange ) VALUES( ?, ? );" );
		
		$q->execute( array( $address, $iprange ) );
		$q->finish();
	}
	
	/**
	 * Deletes a PXE server from the database.
	 * @param integer $id ID.
	 */
	public function deletePXE( $id )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "DELETE FROM pxeserver WHERE id = ?;" );
		
		$q->execute( array( $id ) );
		$q->finish();
	}
	
	/**
	 * Checks whether the user is allowed to edit hosts in a certain group or perform other tasks. Group = 1 checks
	 * for admin privileges. We do not check for admin group here every call, the caller must handle admin users
	 * seperately (by calling checkAuth( 1 ) for example).
	 * @param string $groupid Group IP (admin is 1).
	 * @return boolean true is the operation is allowed, otherwise false.
	 */
	public function checkAuth( $groupid )
	{
		global $session;
		
		static $q = false;
		
		if ( !$q )
		{
			$q = new DatabaseQuery( $this, "SELECT COUNT(*) FROM authgroupmembers WHERE userid = ? AND " .
				"groupid = ?;" );
		}
		
		// No user id = not logged in (= deny everything)
		$userid = $session->getID();
		if ( !$userid )
			return false;
		
		$q->execute( array( $userid, $groupid ) );
		$res = $q->fetchColumn();
		$q->finish();
		
		if ( $res )
			return true;
		else
			return false;
	}
	
	/**
	 * Returns information about a given user ID.
	 * @param integer $id ID.
	 * @return array Array with fields "username" (more coming)
	 */
	public function getUserInfo( $id )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "SELECT username FROM authusers WHERE id = ?;" );
		
		$q->execute( array( $id ) );
		$userinfo = $q->fetch();
		$q->finish();
		
		return $userinfo;
	}
	
	/**
	 * Returns contact information for a given user.
	 * @param string $id User ID.
	 * @param string $username Write username to this variable (given by reference).
	 * @return string Contact string.
	 */
	public function getContact( $id, &$username = false )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "SELECT username, contact FROM authusers WHERE id = ?;" );
		
		$q->execute( array( $id ) );
		$ret = $q->fetch();
		$q->finish();
		
		if ( $ret )
		{
			$username = $ret[ "username" ];
			return $ret[ "contact" ];
		}
		else
		{
			$username = "Invalid";
			return "";
		}
	}
	
	/**
	 * Get the ID belonging to the username after checking the password.
	 * @param string $username Username to check.
	 * @param string $password Password for the user (false to disable password checking).
	 * @return integer The ID of the user or false if the password is wrong.
	 */
	public function getUserID( $username, $password )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "SELECT id, password FROM authusers WHERE username = ?;" );
		
		$q->execute( array( $username ) );
		$res = $q->fetch();
		$q->finish();
		
		if ( $res && ( !$password || ( crypt( $password, $res[ "password" ] ) == $res[ "password" ] ) ) )
			return $res[ "id" ];
		else
			return false;
	}
	
	/**
	 * Adds a new authentification user entry to the authusers database.
	 * @param string $username Username to add.
	 */
	public function addAuthUser( $username )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "INSERT INTO authusers( username ) VALUES( ? );" );
		
		$q->execute( array( $username ) );
		$q->finish();
	}
	
	/**
	 * Updates an authentification user entry in the authusers database.
	 * @param integer $id ID of the auth entry to update.
	 * @param string $password New password.
	 */
	public function updateAuthUserPassword( $id, $password )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "UPDATE authusers SET password = ? WHERE id = ?;" );
		
		$passwordc = crypt( $password );
		
		$q->execute( array( $passwordc, $id ) );
		$q->finish();
	}
	
	/**
	 * Updates the lastuse value for the given user ID.
	 * @param integer $id ID
	 */
	public function updateLastUse( $id )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "UPDATE authusers SET lastuse = ? WHERE id = ?;" );
		
		$q->execute( array( time(), $id ) );
		$q->finish();
	}
	
	/**
	 * Updates the contact data for the given user ID.
	 * @param integer $id ID
	 * @param string $contact Contact data.
	 */
	public function updateContact( $id, $contact )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "UPDATE authusers SET contact = ? WHERE id = ?;" );
		
		$q->execute( array( $contact, $id ) );
		$q->finish();
	}
	
	/**
	 * Deletes an authentification user entry from the authusers database.
	 * @param integer $id ID of the auth entry to delete.
	 */
	public function deleteAuthUser( $id )
	{
		static $q = false;
		static $q2 = false;
		static $q3 = false;
		static $q4 = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "DELETE FROM authusers WHERE id = ?;" );
		
		$q->execute( array( $id ) );
		$q->finish();
		
		if ( !$q2 )
			$q2 = new DatabaseQuery( $this, "DELETE FROM authgroupmembers WHERE userid = ?;" );
		
		$q2->execute( array( $id ) );
		$q2->finish();
		
		if ( !$q3 )
		{
			$q3 = new DatabaseQuery( $this, "UPDATE machines SET usedby_id1 = NULL WHERE usedby_id1 " .
				"= ?;" );
		}
		
		$q3->execute( array( $id ) );
		$q3->finish();
		
		if ( !$q4 )
		{
			$q4 = new DatabaseQuery( $this, "UPDATE machines SET usedby_id2 = NULL WHERE usedby_id2 " .
				"= ?;" );
		}
		
		$q4->execute( array( $id ) );
		$q4->finish();
	}
	
	/**
	 * Lists all auth users from the database.
	 * @return array Array with id, username, contact, lastuse
	 */
	public function listAuthUsers()
	{
		static $q = false;
		
		if ( !$q )
		{
			$q = new DatabaseQuery( $this, "SELECT id, username, password, contact, lastuse FROM " .
				"authusers ORDER BY username ASC;" );
		}
		
		$q->execute();
		$authlist = $q->fetchAll();
		$q->finish();
		
		return $authlist;
	}
	
	/**
	 * Reads the user settings field.
	 * @param integer $id ID of the user.
	 * @return string Content of the user settings field.
	 */
	public function loadUserSettings( $id )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "SELECT settings FROM authusers WHERE id = ?;" );
		
		$q->execute( array( $id ) );
		$settings = $q->fetchColumn();
		$q->finish();
		
		return $settings;
	}
	
	/**
	 * Saves the user settings field.
	 * @param integer $id ID of the user.
	 * @param string $settings New user settings.
	 */
	public function saveUserSettings( $id, $settings )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "UPDATE authusers SET settings = ? WHERE id = ?;" );
		
		$q->execute( array( $settings, $id ) );
		$q->finish();
	}
	
	/**
	 * Creates a new authentification group.
	 * @param string $groupname Name of the group to create.
	 */
	public function addAuthGroup( $groupname )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "INSERT INTO authgroups( groupname ) VALUES( ? );" );
		
		$q->execute( array( $groupname ) );
		$q->finish();
	}
	
	/**
	 * Deletes an authentification group from the database.
	 * @param integer $id ID of the group to delete.
	 */
	public function deleteAuthGroup( $id )
	{
		static $q = false;
		static $q2 = false;
		static $q3 = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "DELETE FROM authgroups WHERE id = ?;" );
		
		$q->execute( array( $id ) );
		$q->finish();
		
		if ( !$q2 )
			$q2 = new DatabaseQuery( $this, "DELETE FROM authgroupmembers WHERE groupid = ?;" );
		
		$q2->execute( array( $id ) );
		$q2->finish();
		
		if ( !$q3 )
			$q3 = new DatabaseQuery( $this, "UPDATE machines SET groupid = NULL WHERE groupid = ?;" );
		
		$q3->execute( array( $id ) );
		$q3->finish();
	}
	
	/**
	 * Returns an array with all available groups and their ID.
	 * @return array All groups.
	 */
	public function listAuthGroups()
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "SELECT * FROM authgroups ORDER BY groupname ASC;" );
		
		$q->execute();
		$authgroups = $q->fetchAll();
		$q->finish();
		
		return $authgroups;
	}
	
	/**
	 * Adds an authentification username to a group.
	 * @param string $userid ID of the user.
	 * @param string $groupid ID of the group.
	 */
	public function addAuthGroupMembership( $userid, $groupid )
	{
		static $q = false;
		
		if ( !$q )
		{
			$q = new DatabaseQuery( $this, "INSERT INTO authgroupmembers( userid, groupid ) VALUES( ?, " .
				"? );" );
		}
		
		$q->execute( array( $userid, $groupid ) );
		$q->finish();
	}
	
	/**
	 * Removes an authentification username from a group.
	 * @param string $userid ID of the user.
	 * @param string $groupid ID of the group.
	 */
	public function deleteAuthGroupMembership( $userid, $groupid )
	{
		static $q = false;
		
		if ( !$q )
		{
			$q = new DatabaseQuery( $this, "DELETE FROM authgroupmembers WHERE userid = ? AND groupid = " .
				"?;" );
		}
		
		$q->execute( array( $userid, $groupid ) );
		$q->finish();
	}
	
	/**
	 * List all group memberships for a given user id.
	 * @param integer $userid ID of the user.
	 * @return array ID and names of the groups in an array.
	 */
	public function listAuthGroupMemberships( $userid )
	{
		static $q = false;
		
		if ( !$q )
		{
			$q = new DatabaseQuery( $this, "SELECT authgroupmembers.groupid AS id, authgroups.groupname " .
				"AS groupname FROM authgroupmembers, authgroups WHERE authgroupmembers.groupid = " .
				"authgroups.id AND authgroupmembers.userid = ? ORDER BY groupname ASC" );
		}
		
		$q->execute( array( $userid ) );
		$groupmemberships = $q->fetchAll();
		$q->finish();
		
		return $groupmemberships;
	}
	
	/**
	 * Writes an entry to the event log.
	 * @param integer $userid ID of the user causing the event (0 = system).
	 * @param string $content Event description.
	 */
	public function logEvent( $userid, $content )
	{
		static $q = false;
		
		if ( !$q )
		{
			$q = new DatabaseQuery( $this, "INSERT INTO events( user, time, content ) VALUES( ?, " .
				time() . ", ? );" );
		}
		
		$q->execute( array( $userid, $content ) );
		$q->finish();
	}
	
	/**
	 * Returns the number of events logged.
	 * @return integer Number of events saved in database.
	 */
	public function countEvents()
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "SELECT COUNT(*) FROM events;" );
		
		$q->execute( array() );
		$num = $q->fetchColumn();
		$q->finish();
		
		return $num;
	}
	
	/**
	 * Returns part of the event log, ordered by time descending.
	 * @param integer $start Return 50 entries, starting with $start.
	 * @param integer $num Number of entries to return.
	 */
	public function viewEvents( $start, $num )
	{
		static $q = false;
		
		// Start and number not really included in PDO because MySQL cannot handle LIMIT within ''
		if ( !is_numeric( $start ) || !is_numeric( $num ) )
			return array();
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "SELECT * FROM events ORDER BY id DESC LIMIT $start, $num;" );
		
		$q->execute( array() );
		$ret = $q->fetchAll();
		$q->finish();
		
		return $ret;
	}
	
	/**
	 * Removes all events that are older that a given amount of days.
	 * @param integer $days Delete entries older than this age.
	 */
	public function cleanEvents( $days )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "DELETE FROM events WHERE time < ?;" );
		
		$purgetime = time() - $days * 3600 * 24;
		
		$q->execute( array( $purgetime ) );
		$q->finish();
	}
	
	/**
	 * Returns a list of machines from the database, sorted by $sort ascending- descending if $reverse = true.
	 * Returns all machines unless $search is set in which case all text fields are searched for the string.
	 * @param integer $id If set, return only machine with $id. -1 to disable.
	 * @param string $sort Sort field (or false). It is passed directly with the ORDER BY statement to SQLite.
	 * @param boolean $reverse Reverse sort order (descending instead of ascending) if true.
	 * @param string $search Search term to search for in all text fields
	 * @param integer $showuser Only display machines with this user ID in used by field.
	 * @param array $showvendor Vendor filtering for the extended filtering
	 * @param array $showarch Architecture filtering for the extended filtering
	 * @param string $showstate State filtering for the extended filtering
	 * @param array $showrack Rack filtering for the extended filtering
	 * @param array $roomsearch Room filtering for the extended filtering and the map (prefix)
	 * @param boolean $showequipment If false, hide entries with non-server architecture
	 * @return array Array id => array( "hostname", "groupid", "vendor", ... )
	 */
	public function getList( $id = -1, $sort = false, $reverse = false, $search = false, $showuser = false,
		$showvendor = false, $showarch = false, $showstate = false, $showrack = false, $showroom = false,
		$showequipment = true )
	{
		global $CONF;
		
		$sortfields = array( "id", "hostname", "groupid", "lastupdate", "updateby", "vendor", "model",
			"state", "arch", "assettag", "expiredate", "expirestate", "ip", "mac", "room", "os", "cpu",
			"mem", "disk", "kernel", "libc", "compiler", "rack", "notes", "hostsystem", "mailtarget",
			"sysinfo" );
		
		if ( $sort && !in_array( $sort, $sortfields ) )
			return array();
		
		if ( $sort == "sysinfo" )
			$sort = "lastping";
		
		$q = "SELECT * FROM machines ";
		
		if ( $id != -1 )
			$q .= "WHERE id = :id ";
		else
		{
			if ( $search )
			{
				$search = "%" . $search . "%";
				
				$q .= "WHERE ( hostname LIKE :searchterm OR " .
					"vendor LIKE :searchterm OR " .
					"model LIKE :searchterm OR " .
					"arch LIKE :searchterm OR " .
					"assettag LIKE :searchterm OR " .
					"room LIKE :searchterm OR " .
					"os LIKE :searchterm OR " .
					"cpu LIKE :searchterm OR " .
					"mem LIKE :searchterm OR " .
					"disk LIKE :searchterm OR " .
					"kernel LIKE :searchterm OR " .
					"libc LIKE :searchterm OR " .
					"compiler LIKE :searchterm OR " .
					"usedby LIKE :searchterm OR " .
					"notes LIKE :searchterm OR " .
					"hostsystem LIKE :searchterm OR " .
					"wbem_info LIKE :searchterm OR " .
					"usedby_id1 LIKE :searchterm OR " .
					"usedby_id2 LIKE :searchterm OR " .
					"usedby LIKE :searchterm OR " .
					"mailtarget LIKE :searchterm ) ";
			}
			else
				$q .= "WHERE 1 ";
			
			if ( $showuser )
				$q .= "AND ( usedby_id1 = $showuser OR usedby_id2 = $showuser ) ";
			
			if ( $showvendor && $showarch && $showstate && $showrack )
			{
				$q .= "AND ( ";
				$first = true;
				
				foreach ( $showvendor as $vendor => $active )
				{
					if ( $active )
					{
						if ( $first )
						{
							$q .= "vendor LIKE '" . strtolower( $vendor ) . "' ";
							$first = false;
						}
						else
							$q .= "OR vendor LIKE '" . strtolower( $vendor ) . "' ";
					}
				}
				
				if ( $first )
					return array();
				
				$q .= ") AND ( ";
				$first = true;
				
				foreach ( $showarch as $arch => $active )
				{
					if ( $active )
					{
						if ( $first )
						{
							$q .= "arch LIKE '$arch' ";
							$first = false;
						}
						else
							$q .= "OR arch LIKE '$arch' ";
					}
				}
				
				if ( $first )
					return array();
				
				if ( $showstate == "inuse" )
					$q .= ") AND state = 1 ";
				else if ( $showstate == "free" )
					$q .= ") AND state = 0 ";
				else  // $showstate == "all"
					$q .= ") ";
				
				$q .= "AND ( ";
				
				$rackset = false;
				
				if ( $showrack[ "rack" ] )
				{
					$q .= "rack = 1 ";
					$rackset = true;
				}
				
				if ( $showrack[ "floor" ] )
				{
					if ( $rackset )
						$q .= "OR rack = 0 ";
					else
					{
						$q .= "rack = 0 ";
						$rackset = true;
					}
				}
				
				if ( $showrack[ "virtual" ] )
				{
					if ( $rackset )
						$q .= "OR rack = 2 ";
					else
					{
						$q .= "rack = 2 ";
						$rackset = true;
					}
				}
				
				if ( !$rackset )
					return array();
				
				$q .= ") ";
			}
			
			if ( $showroom )
			{
				$q .= "AND ( ";
				$first = true;
				
				foreach ( $showroom as $room => $active )
				{
					if ( $active )
					{
						if ( $first )
						{
							$q .= "room LIKE '$room%' ";
							$first = false;
						}
						else
							$q .= "OR room LIKE '$room%' ";
					}
				}
				
				if ( $first )
					return array();
				
				$q .= ") ";
			}
			
			if ( !$showequipment )
				$q .= "AND ( arch IS NULL OR arch NOT LIKE 'non-server' ) ";
			
			if ( $sort )
			{
				$q .= "ORDER BY $sort ";
				
				if ( $reverse )
					$q .= "DESC;";
				else
					$q .= "ASC;";
			}
		}
		
		$res = new DatabaseQuery( $this, $q );
		
		if ( $id != -1 )
			$res->bindValue( ":id", $id );
		else
		{
			if ( $search )
				$res->bindValue( ":searchterm", $search );
		}
		
		$res->execute();
		
		if ( $id != -1 )
			$ret = $res->fetch();
		else
		{
			$ret = array();
			
			while ( $row = $res->fetch() )
			{
				foreach ( $row as $col => $val )
					$ret[ $row[ "id" ] ][ $col ] = $val;
			}
		}
		
		$res->finish();
		
		return $ret;
	}
	
	/**
	 * Checks whether the user is allowed to edit a machine.
	 * @param integer $id ID of the machine.
	 * @return boolean True if auth check passes, false if fails.
	 */
	public function checkEditAuth( $id )
	{
		static $q = false;
		
		// Check for admin
		if ( $this->checkAuth( 1 ) )
			return true;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "SELECT groupid FROM machines WHERE id = ?;" );
		
		$q->execute( array( $id ) );
		$groupid = $q->fetchColumn();
		$q->finish();
		
		if ( $groupid )
			return $this->checkAuth( $groupid );
		else
			return false;
	}
	
	/**
	 * Get the ID for a machine when only the hostname is known.
	 * @param string $hostname Hostname to search for.
	 * @return integer ID of the machine $hostname belongs to (-1 = error).
	 */
	public function queryIDFromHostname( $hostname )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "SELECT id FROM machines WHERE hostname = ?;" );
		
		$q->execute( array( $hostname ) );
		$id = $q->fetchColumn();
		$q->finish();
		
		return $id;
	}
	
	/**
	 * Adds a new machine to the database.
	 * @param array $data Array with all needed fields (hostname, vendor, ...)
	 * @return integer ID of the new entry.
	 */
	public function addEntry( $data )
	{
		global $session;
		
		static $q = false;
		
		if ( !$q )
		{
			$q = new DatabaseQuery( $this, "INSERT INTO machines( hostname, groupid, vendor, model, " .
				"arch, assettag, ip, mac, monfiles_data, lastping, room, os, cpu, mem, disk, " .
				"kernel, libc, compiler, state, usedby, usedby_id1, usedby_id2, expiredate, " .
				"expirestate, notes, rack, hostsystem, wbem_info, wbem_lastupdate, remoteadm, " .
				"lastupdate, updateby, mailtarget, mailopts ) " .
				"VALUES( :hostname, :groupid, :vendor, :model, :arch, :assettag, :ip, :mac, '', " .
				"0, :room, :os, :cpu, :mem, :disk, :kernel, :libc, :compiler, :state, :usedby, " .
				":usedby_id1, :usedby_id2, :expiredate, :expirestate, :notes, :rack, :hostsystem, " .
				"'', 0, 0, :curtime, :curuser, :mailtarget, :mailopts );" );
		}
		
		$q->bindValue( ":hostname", $data[ "hostname" ] );
		$q->bindValue( ":groupid", $data[ "groupid" ] );
		$q->bindValue( ":vendor", $data[ "vendor" ] );
		$q->bindValue( ":model", $data[ "model" ] );
		$q->bindValue( ":arch", $data[ "arch" ] );
		$q->bindValue( ":assettag", $data[ "assettag" ] );
		$q->bindValue( ":ip", $data[ "ip" ] );
		$q->bindValue( ":mac", $data[ "mac" ] );
		$q->bindValue( ":room", $data[ "room" ] );
		$q->bindValue( ":os", $data[ "os" ] );
		$q->bindValue( ":cpu", $data[ "cpu" ] );
		$q->bindValue( ":mem", $data[ "mem" ] );
		$q->bindValue( ":disk", $data[ "disk" ] );
		$q->bindValue( ":kernel", $data[ "kernel" ] );
		$q->bindValue( ":libc", $data[ "libc" ] );
		$q->bindValue( ":compiler", $data[ "compiler" ] );
		$q->bindValue( ":state", $data[ "state" ] );
		$q->bindValue( ":usedby", $data[ "usedby" ] );
		$q->bindValue( ":usedby_id1", $data[ "usedby_id1" ] );
		$q->bindValue( ":usedby_id2", $data[ "usedby_id2" ] );
		$q->bindValue( ":expiredate", $data[ "expiredate" ] );
		$q->bindValue( ":expirestate", $data[ "expirestate" ] );
		$q->bindValue( ":notes", $data[ "notes" ] );
		$q->bindValue( ":rack", $data[ "rack" ] );
		$q->bindValue( ":hostsystem", $data[ "hostsystem" ] );
		$q->bindValue( ":mailtarget", $data[ "mailtarget" ] );
		$q->bindValue( ":mailopts", $data[ "mailopts" ] );
		$q->bindValue( ":curtime", time() );
		$q->bindValue( ":curuser", $session->getID() );
		
		$q->execute();
		$q->finish();
		
		return $this->db->lastInsertID();
	}
	
	/**
	 * Edits an entry in the database.
	 * @param integer $id ID of the entry to edit.
	 * @param array $data Array with all needed fields (hostname, vendor, ...)
	 */
	public function editEntry( $id, $data )
	{
		global $session;
		
		$qe = "UPDATE machines SET";
		
		if ( array_key_exists( "hostname", $data ) )
			$qe .= " hostname = :hostname,";
		
		if ( array_key_exists( "groupid", $data ) )
			$qe .= " groupid = :groupid,";
		
		if ( array_key_exists( "vendor", $data ) )
			$qe .= " vendor = :vendor,";
		
		if ( array_key_exists( "model", $data ) )
			$qe .= " model = :model,";
		
		if ( array_key_exists( "arch", $data ) )
			$qe .= " arch = :arch,";
		
		if ( array_key_exists( "assettag", $data ) )
			$qe .= " assettag = :assettag,";
		
		if ( array_key_exists( "ip", $data ) )
			$qe .= " ip = :ip,";
		
		if ( array_key_exists( "mac", $data ) )
			$qe .= " mac = :mac,";
		
		if ( array_key_exists( "room", $data ) )
			$qe .= " room = :room,";
		
		if ( array_key_exists( "os", $data ) )
			$qe .= " os = :os,";
		
		if ( array_key_exists( "cpu", $data ) )
			$qe .= " cpu = :cpu,";
		
		if ( array_key_exists( "mem", $data ) )
			$qe .= " mem = :mem,";
		
		if ( array_key_exists( "disk", $data ) )
			$qe .= " disk = :disk,";
		
		if ( array_key_exists( "kernel", $data ) )
			$qe .= " kernel = :kernel,";
		
		if ( array_key_exists( "libc", $data ) )
			$qe .= " libc = :libc,";
		
		if ( array_key_exists( "compiler", $data ) )
			$qe .= " compiler = :compiler,";
		
		if ( array_key_exists( "state", $data ) )
			$qe .= " state = :state,";
		
		if ( array_key_exists( "usedby", $data ) )
			$qe .= " usedby = :usedby,";
		
		if ( array_key_exists( "usedby_id1", $data ) )
			$qe .= " usedby_id1 = :usedby_id1,";
		
		if ( array_key_exists( "usedby_id2", $data ) )
			$qe .= " usedby_id2 = :usedby_id2,";
		
		if ( array_key_exists( "expiredate", $data ) )
			$qe .= " expiredate = :expiredate,";
		
		if ( array_key_exists( "expirestate", $data ) )
			$qe .= " expirestate = :expirestate,";
		
		if ( array_key_exists( "notes", $data ) )
			$qe .= " notes = :notes,";
		
		if ( array_key_exists( "rack", $data ) )
			$qe .= " rack = :rack,";
		
		if ( array_key_exists( "hostsystem", $data ) )
			$qe .= " hostsystem = :hostsystem,";
		
		if ( array_key_exists( "mailtarget", $data ) )
			$qe .= " mailtarget = :mailtarget,";
		
		if ( array_key_exists( "mailopts", $data ) )
			$qe .= " mailopts = :mailopts,";
		
		$qe .= " lastping = 0, wbem_lastupdate = 0, lastupdate = :curtime, updateby = :curuser WHERE " .
			"id = :id;";
		
		$q = new DatabaseQuery( $this, $qe );
		
		if ( array_key_exists( "hostname", $data ) )
			$q->bindValue( ":hostname", $data[ "hostname" ] );
		
		if ( array_key_exists( "groupid", $data ) )
			$q->bindValue( ":groupid", $data[ "groupid" ] );
		
		if ( array_key_exists( "vendor", $data ) )
			$q->bindValue( ":vendor", $data[ "vendor" ] );
		
		if ( array_key_exists( "model", $data ) )
			$q->bindValue( ":model", $data[ "model" ] );
		
		if ( array_key_exists( "arch", $data ) )
			$q->bindValue( ":arch", $data[ "arch" ] );
		
		if ( array_key_exists( "assettag", $data ) )
			$q->bindValue( ":assettag", $data[ "assettag" ] );
		
		if ( array_key_exists( "ip", $data ) )
			$q->bindValue( ":ip", $data[ "ip" ] );
		
		if ( array_key_exists( "mac", $data ) )
			$q->bindValue( ":mac", $data[ "mac" ] );
		
		if ( array_key_exists( "room", $data ) )
			$q->bindValue( ":room", $data[ "room" ] );
		
		if ( array_key_exists( "os", $data ) )
			$q->bindValue( ":os", $data[ "os" ] );
		
		if ( array_key_exists( "cpu", $data ) )
			$q->bindValue( ":cpu", $data[ "cpu" ] );
		
		if ( array_key_exists( "mem", $data ) )
			$q->bindValue( ":mem", $data[ "mem" ] );
		
		if ( array_key_exists( "disk", $data ) )
			$q->bindValue( ":disk", $data[ "disk" ] );
		
		if ( array_key_exists( "kernel", $data ) )
			$q->bindValue( ":kernel", $data[ "kernel" ] );
		
		if ( array_key_exists( "libc", $data ) )
			$q->bindValue( ":libc", $data[ "libc" ] );
		
		if ( array_key_exists( "compiler", $data ) )
			$q->bindValue( ":compiler", $data[ "compiler" ] );
		
		if ( array_key_exists( "state", $data ) )
			$q->bindValue( ":state", $data[ "state" ] );
		
		if ( array_key_exists( "usedby", $data ) )
			$q->bindValue( ":usedby", $data[ "usedby" ] );
		
		if ( array_key_exists( "usedby_id1", $data ) )
			$q->bindValue( ":usedby_id1", $data[ "usedby_id1" ] );
		
		if ( array_key_exists( "usedby_id2", $data ) )
			$q->bindValue( ":usedby_id2", $data[ "usedby_id2" ] );
		
		if ( array_key_exists( "expiredate", $data ) )
			$q->bindValue( ":expiredate", $data[ "expiredate" ] );
		
		if ( array_key_exists( "expirestate", $data ) )
			$q->bindValue( ":expirestate", $data[ "expirestate" ] );
		
		if ( array_key_exists( "notes", $data ) )
			$q->bindValue( ":notes", $data[ "notes" ] );
		
		if ( array_key_exists( "rack", $data ) )
			$q->bindValue( ":rack", $data[ "rack" ] );
		
		if ( array_key_exists( "hostsystem", $data ) )
			$q->bindValue( ":hostsystem", $data[ "hostsystem" ] );
		
		if ( array_key_exists( "mailtarget", $data ) )
			$q->bindValue( ":mailtarget", $data[ "mailtarget" ] );
		
		if ( array_key_exists( "mailopts", $data ) )
			$q->bindValue( ":mailopts", $data[ "mailopts" ] );
		
		$q->bindValue( ":curtime", time() );
		
		if ( $session->getID() )
			$q->bindValue( ":curuser", $session->getID() );
		else
			$q->bindValue( ":curuser", 0 );
		
		$q->bindValue( ":id", $id );
		
		$q->execute();
		$q->finish();
	}
	
	/**
	 * Deletes an entry from the database.
	 * @param integer $id ID of the entry to delete.
	 */
	public function deleteEntry( $id )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "DELETE FROM machines WHERE id = ?;" );
		
		$q->execute( array( $id ) );
		$q->finish();
	}
	
	/**
	 * Updates the lastping field for a machine and sets it to the current UNIX time.
	 * @param integer $id ID ID of the machine to update.
	 * @param integer $time Time to write, usually time() or 1 for "never online" machines.
	 * @see CronRun()
	 */
	public function setLastPing( $id, $time )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "UPDATE machines SET lastping = ? WHERE id = ?;" );
		
		$q->execute( array( $time, $id ) );
		$q->finish();
	}
	
	/**
	 * Sets whether remote administration is available for a machine.
	 * @param integer $id ID ID of the machine to update.
	 * @param boolean $avail Whether remote admin is available.
	 * @see CronRun()
	 */
	public function setRemoteAvail( $id, $avail )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "UPDATE machines SET remoteadm = ? WHERE id = ?;" );
		
		$q->execute( array( $avail, $id ) );
		$q->finish();
	}
	/**
	 * Updates the expiration state for a machine.
	 * @param integer $id ID of the machine to update.
	 * @param integer $expirestate New expiration state
	 */
	public function setExpirationState( $id, $expirestate )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "UPDATE machines SET expirestate = ? WHERE id = ?;" );
		
		$q->execute( array( $expirestate, $id ) );
		$q->finish();
	}
	
	/**
	 * Updates the WBEM information for a machine.
	 * @param integer $id ID of the machine to update.
	 * @param string $wbem WBEM information to write.
	 */
	public function setWBEMInformation( $id, $wbem )
	{
		static $q = false;
		
		if ( !$q )
		{
			$q = new DatabaseQuery( $this, "UPDATE machines SET wbem_info = ?, wbem_lastupdate = ? WHERE " .
				"id = ?;" );
		}
		
		$q->execute( array( $wbem, time(), $id ) );
		$q->finish();
	}
	
	/**
	 * Updates the monfiles_data field for a machine.
	 * @param integer $id ID of the machine to update.
	 * @param string $data New value for monfiles_data.
	 */
	public function setMonfilesData( $id, $data )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "UPDATE machines SET monfiles_data = ? WHERE id = ?;" );
		
		$q->execute( array( $data, $id ) );
		$q->finish();
	}
	
	/**
	 * Tries to find out the vendor for the given start of a physical address (AA:BB:CC).
	 * @param string $macstart First three fields of the MAC address, :-seperated
	 * @return string Vendor, if found. Otherwise false.
	 */
	public function lookupVendor( $macstart )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "SELECT vendor FROM nicvendors WHERE macstart = ?;" );
		
		$q->execute( array( $macstart ) );
		$vendor = $q->fetchColumn();
		$q->finish();
		
		return $vendor;
	}
	
	/**
	 * Returns a list with all kernels in the database and the count of their default parameter lines.
	 * @return array Array with the following fields set: id, filename, description, type, pcount
	 */
	function getAllKernels()
	{
		static $q = false;
		static $q2 = false;
		
		if ( !$q )
		{
			$q = new DatabaseQuery( $this, "SELECT id, filename, type, description FROM kernels ORDER BY " .
				"filename ASC;" );
		}
		
		if ( !$q2 )
		{
			$q2 = new DatabaseQuery( $this, "SELECT COUNT(*) AS pcount FROM kernelparameters WHERE " .
				"kernel = ?;" );
		}
		
		$q->execute();
		$kernel = $q->fetchAll();
		$q->finish();
		
		$ret = array();
		
		foreach ( $kernel as $row )
		{
			$q2->execute( array( $row[ "id" ] ) );
			$pcount = $q2->fetchColumn();
			$q2->finish();
			
			$ret[ $row[ "filename" ] ] = array( "id" => $row[ "id" ], "filename" => $row[ "filename" ],
				"description" => $row[ "description" ], "type" => $row[ "type" ], "pcount" => $pcount );
		}
		
		$q->finish();
		
		return $ret;
	}
	
	/**
	 * Returns information about a single kernel identified by its ID.
	 * @param integer $id ID of the kernel to return.
	 * @return array Array with the fields: filename, type, description, paramlines [<= array of strings]
	 */
	function getKernelByID( $id )
	{
		static $q = false;
		static $q2 = false;
		
		if ( !$q )
		{
			$q = new DatabaseQuery( $this, "SELECT filename, type, description FROM kernels WHERE id = ?;"
				);
		}
		
		if ( !$q2 )
		{
			$q2 = new DatabaseQuery( $this, "SELECT id, paramline AS text FROM kernelparameters WHERE " .
				"kernel = ?;" );
		}
		
		$q->execute( array( $id ) );
		
		$ret = $q->fetch();
		$ret[ "paramlines" ] = array();
		
		$q->finish();
		
		$q2->execute( array( $id ) );
		
		while ( $paramline = $q2->fetch() )
			array_push( $ret[ "paramlines" ], $paramline );
		
		$q2->finish();
		
		return $ret;
	}
	
	/**
	 * Returns information about a single kernel identified by its name.
	 * @param string $name Name of the kernel to return.
	 * @return array Array with the fields: filename, type, description, paramlines [<= array of strings]
	*/
	function getKernelByName( $name )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "SELECT id FROM kernels WHERE filename = ?;" );
		
		$q->execute( array( $name ) );
		$id = $q->fetchColumn();
		$q->finish();
		
		if ( $id )
			return $this->getKernelByID( $id );
		else
			return false;
	}
	
	/**
	 * Adds a new kernel in the database. Every field except the name is filled with default values.
	 * @param string $name Name of the kernel.
	 */
	function addKernel( $name )
	{
		static $q = false;
		
		if ( !$q )
		{
			$q = new DatabaseQuery( $this, "INSERT INTO kernels( filename, type, description ) VALUES( " .
				"?, 0, 'Default config. You can now create parameter lines for this kernel.' );" );
		}
		
		$q->execute( array( $name ) );
		$q->finish();
	}
	
	/**
	 * Changes type and description for the kernel identified by $id.
	 * @param integer $id ID of the kernel to edit.
	 * @param integer $type New kernel type.
	 * @param string $description New kernel description.
	 */
	function editKernel( $id, $type, $description )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "UPDATE kernels SET type = ?, description = ? WHERE id = ?;" );
		
		$q->execute( array( $type, $description, $id ) );
		$q->finish();
	}
	
	/**
	 * Deletes a kernel from the database.
	 * @param integer $id Identifies the kernel to delete.
	 */
	function deleteKernel( $id )
	{
		static $q = false;
		static $q2 = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "DELETE FROM kernels WHERE id = ?;" );
		
		if ( !$q2 )
			$q2 = new DatabaseQuery( $this, "DELETE FROM kernelparameters WHERE kernel = ?;" );
		
		$q->execute( array( $id ) );
		$q->finish();
		
		$q2->execute( array( $id ) );
		$q2->finish();
	}
	
	/**
	 * Returns an array with all available kernel parameter lines in it.
	 * @return array Array with the fields: id, kernelid, kernelname, paramline
	 */
	function getAllKernelParameterLines()
	{
		static $q = false;
		
		if ( !$q )
		{
			$q = new DatabaseQuery( $this, "SELECT kernelparameters.id, kernel AS kernelid, filename AS " .
				"kernelname, paramline FROM kernelparameters, kernels WHERE kernel = kernels.id;" );
		}
		
		$q->execute();
		$ret = $q->fetchAll();
		$q->finish();
		
		return $ret;
	}
	
	/**
	 * Fetches a kernel parameter line from the database
	 * @param integer $id ID of the parameter line to return.
	 * @return string The parameter line.
	 */
	function getKernelParameterLine( $id )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "SELECT paramline FROM kernelparameters WHERE id = ?;" );
		
		$q->execute( array( $id ) );
		$paramline = $q->fetchColumn();
		$q->finish();
		
		return $paramline;
	}
	
	/**
	 * Adds a new kernel parameter line to the database.
	 * @param integer $kernel ID of the kernel to which the parameter line belongs to.
	 * @param string $paramline The parameter line.
	 */
	function addKernelParameterLine( $kernel, $paramline )
	{
		static $q = false;
		
		if ( !$q )
		{
			$q = new DatabaseQuery( $this, "INSERT INTO kernelparameters( kernel, paramline ) VALUES( " .
				"?, ? );" );
		}
		
		$q->execute( array( $kernel, $paramline ) );
		$q->finish();
	}
	
	/**
	 * Edits a parameter line. Only changing the parameter line itself is supported, not the kernel it belongs to.
	 * @param integer $id ID of the parameter line to change.
	 * @param string $paramline New parameter line.
	 */
	function editKernelParameterLine( $id, $paramline )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "UPDATE kernelparameters SET paramline = ? WHERE id = ?;" );
		
		$q->execute( array( $paramline, $id ) );
		$q->finish();
	}
	
	/**
	 * Deletes a parameter line from the database.
	 * @param interger $id ID of the parameter line to delete.
	 */
	function deleteKernelParameterLine( $id )
	{
		static $q = false;
		
		if ( !$q )
			$q = new DatabaseQuery( $this, "DELETE FROM kernelparameters WHERE id = ?;" );
		
		$q->execute( array( $id ) );
		$q->finish();
	}
}

?>
