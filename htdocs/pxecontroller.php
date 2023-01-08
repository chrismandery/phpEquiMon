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
 * Represents one PXE server running the pxehelper.php script.
 * @package phpequimon
 */
class PXEController
{
	private $address = false;
	private $version = false;
	
	public $config = false;
	
	/**
	 * Creates a new instance of the PXEController class.
	 * @param string $address Adress of the PXE server.
	 */
	public function __construct( $address )
	{
		$this->address = $address;
		
		// Check status
		$this->checkStatus();
		
		// If ready, query configuration
		if ( $this->version > 0 )
			$this->queryConfiguration();
	}
	
	/**
	 * Returns the protocol version.
	 * @return $integer Version of the procotol or false if PXE server does not work.
	 */
	public function getVersion()
	{
		return $this->version;
	}
	
	/**
	 * Writes a new DHCP configuration on the server.
	 * @param string $config DHCP configuration.
	 * @param boolean Successful?
	 */
	public function writeConfiguration( $dhcpconfig )
	{
		global $backend;
		
		// Only proceed if a valid connection is established
		if ( !( $this->version > 0 ) )
			return;
		
		// Send new configuration to server
		$resp = helperFetchURL( $this->address . "/pxehelper_if.php?pxeaction=writeconfig", "dhcpcfg=" .
			utf8_encode( $dhcpconfig ), true );
		
		if ( $resp[ 0 ] == "ok" )
		{
			$backend->logEvent( 0, "PXE Controller for " . $this->address . ": DCHP configuration " .
				"refreshed." );
			return true;
		}
		else
			return false;
	}
	
	/**
	 * Changes PXE settings for a machine.
	 * @param string $mac MAC address.
	 * @param boolean $enabled Whether the machine is enabled.
	 * @param boolean $watchdog Enable watchdog?
	 * @param string $kernel Kernel filename.
	 * @param string $opts Kernel options.
	 * @param boolean Successful?
	 */
	public function writePXEConfiguration( $arch, $mac, $enabled, $watchdog, $kernel, $opts )
	{
		global $backend;
		
		$rqstr = $arch;
		
		$rqstr .= "|" . $mac;
		
		if ( $enabled )
			$rqstr .= "|enabled";
		else
			$rqstr .= "|disabled";
		
		if ( $watchdog )
			$rqstr .= "|wd";
		else
			$rqstr .= "|nowd";
		
		$rqstr .= "|" . $kernel;
		$rqstr .= "|" . $opts;
		
		$resp = helperFetchURL( $this->address . "/pxehelper_if.php?pxeaction=writepxeconfig", "pxecfg=" .
			utf8_encode( $rqstr ), true );
		
		if ( $resp[ 0 ] == "ok" )
		{
			$backend->logEvent( 0, "PXE Controller for " . $this->address . ": Dispatched PXE settings." );
			return true;
		}
		else
			return false;
	}
	
	/**
	 * Internal: Checks the server's status.
	 */
	private function checkStatus()
	{
		// Contact PXE server
		$content = helperFetchURL( $this->address . "/pxehelper_if.php?pxeaction=status", false, true );
		
		// Correct response:
		// ready\n
		// [version]\n
		if ( count( $content ) != 3  )  // Three lines
			$this->version = false;
		else if ( $content[ 0 ] != "ready" )  // Ready?
			$this->version = -$content[ 1 ];
		else
			$this->version = $content[ 1 ];
	}
	
	/**
	 * Internal: Read information about known machines and their configuration.
	 */
	private function queryConfiguration()
	{
		// Start out with an empty array
		$this->config = array();
		
		// Only proceed if the PXE selftest was successful
		if ( !( $this->version > 0 ) )
			return;
		
		// Contact PXE server
		$content = helperFetchURL( $this->address . "/pxehelper_if.php?pxeaction=readconfig", false, true );
		
		// Parse response
		foreach ( $content as $line )
		{
			// Skip empty lines
			if ( empty( $line ) )
				continue;
			
			// Split lines (format: ..... )
			$parts = explode( "|", $line );
			
			// Response:
			// hostname|ip|mac|kernel|opts|active
			if ( count( $parts ) == 6 )
			{
				$this->config[ $parts[ 0 ] ] = array( "ip" => $parts[ 1 ], "mac" => $parts[ 2 ],
					"kernel" => $parts[ 3 ], "options" => $parts[ 4 ], "active" => ( $parts[ 5 ] ==
					"active" ) );
			}
			else
			{
				$this->version = -$this->version;
				return;
			}
		}
	}
}

?>
