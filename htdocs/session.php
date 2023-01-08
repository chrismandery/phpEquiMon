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

class Session
{
	public $_settings = false;
	
	/**
	 * Starts a PHP session
	 */
	public function __construct()
	{
		global $backend;
		
		session_start();
		
		$this->loadSettings();
	}
	
	/**
	 * Ends a PHP session (and calls session_write_close()).
	 */
	public function __destruct()
	{
		session_write_close();
	}
	
	/**
	 * Try to log in with the given credentials.
	 * @param string $username Username.
	 * @param string $password Password or false to disable password authentification.
	 * @return boolean Whether the login attempt was successful.
	 */
	public function login( $username, $password )
	{
		global $backend;
		
		if ( isset( $_SESSION[ "username" ] ) )
			return false;
		
		// Get user ID
		$id = $backend->getUserID( $username, $password );
		if ( !$id )
			return false;
		
		// Set session values
		$_SESSION[ "id" ] = $id;
		$_SESSION[ "username" ] = $username;
		
		$this->loadSettings();
		
		return true;
	}
	
	/**
	 * Perform a logout. Important note: This ends the program execution but sends a HTTP redirect request before.
	 */
	public function logout()
	{
		global $backend;
		
		header( "Location: " . $backend->readConfig( "installation_url" ) );
		session_destroy();
		
		exit;
	}
	
	/**
	 * Returns the username.
	 * @return string Username or false if not logged in.
	 */
	public function getUsername()
	{
		if ( isset( $_SESSION[ "username" ] ) )
			return $_SESSION[ "username" ];
		else
			return false;
	}
	
	/**
	 * Returns the user ID.
	 * @return string User ID or false if not logged in.
	 */
	public function getID()
	{
		if ( isset( $_SESSION[ "id" ] ) )
			return $_SESSION[ "id" ];
		else
			return false;
	}
	
	/**
	 * Loads the user settings array from the database. (used internally)
	 */
	private function loadSettings()
	{
		global $backend;
		
		if ( isset( $_SESSION[ "id" ] ) )
		{
			$serstr = $backend->loadUserSettings( $_SESSION[ "id" ] );
			
			if ( $serstr )
				$this->_settings = unserialize( $serstr );
			else
				$this->_settings = array();
		}
	}
	
	/**
	 * Writes the user settings array to the database.
	 */
	public function saveSettings()
	{
		global $backend;
		
		if ( isset( $_SESSION[ "id" ] ) )
		{
			$serstr = serialize( $this->_settings );
			$backend->saveUserSettings( $_SESSION[ "id" ], $serstr );
		}
	}
}

?>
