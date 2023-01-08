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

class DefaultTheme implements ITheme
{
	function printHeader( $setfocus )
	{
		global $backend, $lastcronfailed, $session;
		
		$userid = $session->getID();
		$admin = false;
		
		if ( $userid )
		{
			$groups = $backend->listAuthGroupMemberships( $userid );
			
			foreach ( $groups as $g )
			{
				if ( $g[ "groupname" ] == "admin" )
					$admin = true;
			}
		}
		
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo helperEncodeHTML( $backend->readConfig( "installation_name" ) ); ?></title>
<link rel="stylesheet" href="themes/default/style.css" />
<link rel="stylesheet" media="screen" href="themes/default/screen.css" />
<link rel="stylesheet" media="print" href="themes/default/print.css" />
<link rel="shortcut icon" type="image/x-icon" href="themes/default/favicon.ico" />
</head>

<?php
		if ( $setfocus )
			echo "<body onload='setFocus();'>";
		else
			echo "<body>";
		
?>

<script type="text/javascript" src="themes/default/wz_tooltip.js"></script>
<script type="text/javascript" src="scripts.js"></script>

<noscript>
<p class="err">You have JavaScript disabled. Some features will not work properly.</p>
</noscript>

<?php
		if ( $lastcronfailed )
		{
			echo "<p class=\"err\">It seems as if your cronjob is not properly set up. If this is a new " .
				"installation, this message is normal. Just remember to add a cronjob that runs \"" .
				"php /path/phpequimon/index.php cron\" every few minutes and adjust the \"Cron " .
				"Interval\" in the Admin Panel here.</p>\n";
		}
		
		echo "<table class=\"navhead\">\n";
		
		if ( isset( $_GET[ "nav" ] ) )
		{
			if ( $_GET[ "nav" ] == "hide" )
				$_SESSION[ "hidenavbar" ] = true;
			else
				unset( $_SESSION[ "hidenavbar" ] );
		}
		
		if ( isset( $_SESSION[ "hidenavbar" ] ) )
		{
			echo "<tr><td>\n";
			echo "<a href=\"?nav=show\" style=\"font-weight: bold\">Show navigation bar</a>\n";
			echo "</td></tr>\n";
			
			echo "<tr><td>\n";
		}
		else
		{
			$cols = 0;
			
			echo "<tr>\n";
			
			++$cols;
			echo "<td><a href=\"?\"><img src=\"themes/default/icos/index.png\" alt=\"index\" /><br />" .
				"Index Page</a></td>\n";
			
			++$cols;
			echo "<td><a href=\"?a=e\"><img src=\"themes/default/icos/add.png\" alt=\"add\" /><br />Add " .
				"Entry</a></td>\n";
			
			if ( $backend->readConfig( "enable_map" ) == "yes" )
			{
				++$cols;
				echo "<td><a href=\"?a=x\"><img src=\"themes/default/icos/roommap.png\" alt=\"" .
					"roommap\" /><br />Room Map</a></td>\n";
			}
			
			++$cols;
			echo "<td><a href=\"?a=c\"><img src=\"themes/default/icos/conscheck.png\" alt=\"" .
				"conscheck\" /><br />Consistency check</a></td>\n";
			
			if ( $backend->readConfig( "fh_prefix" ) )
			{
				++$cols;
				echo "<td><a href=\"?a=h\"><img src=\"themes/default/icos/probehostnames.png\" " .
					"alt=\"probehostnames\" /><br />Probe for Free Hostnames</a></td>\n";
			}
			
			++$cols;
			echo "<td><a href=\"?a=l\"><img src=\"themes/default/icos/eventlog.png\" alt=\"eventlog\" />" .
				"<br />View event log</a></td>\n";
			
			if ( $admin )
			{
				++$cols;
				echo "<td><a href=\"?a=a\"><img src=\"themes/default/icos/adminp.png\" alt=\"" .
					"adminp\" /><br />Admin Panel</a></td>\n";
			}
			
			echo "</tr>\n";
			echo "<tr><td colspan=\"$cols\">\n";
			echo "<a href=\"?nav=hide\">Hide navigation bar</a>\n";
			echo "</td></tr>\n";
			echo "<tr><td colspan=\"$cols\">\n";
		}
		
		if ( $userid )
		{
			echo "Logged in as ";
			
			$username = "";
			$contact = $backend->getContact( $session->getID(), $username );
			
			helperPrintUserInfo( $username, $contact );
			
			if ( !empty( $groups ) )
			{
				echo "- Your group(s):\n";
				
				foreach ( $groups as $g )
					echo helperEncodeHTML( $g[ "groupname" ] ) . "\n";
			}
			else
				echo "- You are not member in any group.\n";
			
			echo "- <a href=\"?a=s\">Change your settings</a>\n";
			echo "- <a href=\"?logout=true\">Logout</a>\n";
		}
		else
		{
			echo "<form action=\"?\" method=\"post\"><p>\n";
			echo "Username: <input type=\"text\" name=\"username\" size=\"10\"/>&nbsp;&nbsp;\n";
			echo "Password: <input type=\"password\" name=\"password\" size=\"10\" />&nbsp;&nbsp;\n";
			echo "<input type=\"submit\" name=\"login\" value=\"Login\" />\n";
			echo "</p></form>\n";
		}
		
		echo "</td></tr>\n";
		echo "</table>\n";
		
		echo "<a href=\"?\"><h1>" . helperEncodeHTML( $backend->readConfig( "installation_name" ) ) .
			"</h1></a>\n";
	}
	
	function printFooter( $exectime, $queries )
	{
		global $backend, $CONF;
		
		echo "<p id=\"footer\">$queries database queries executed.<br />\n";
		
		if ( file_exists( $CONF[ "dbdir" ] . "lastcron" ) )
		{
			echo "Last cronjob run: " . date( "D M j G:i:s T Y", filemtime( $CONF[ "dbdir" ] . "lastcron" )
				) . "<br />\n";
		}
		else
		{
			echo "No successful cronjob run ever. If this is a fresh installation, remember to setup " .
				"cronjob to enable all phpEquiMon features!<br />\n";
		}
		
		echo "Execution took $exectime seconds.<br /><br />\n";
		echo "<span style=\"font-weight: bold\">phpEquiMon 0.1 &nbsp;&nbsp;&copy; 2007 Christian Mandery" .
			"</span> (icons by KDE CrystalSVG, slightly modified by me; tooltip script from Walter Zorn" .
			")<br />\n";
		echo "This program comes with ABSOLUTELY NO WARRANTY, read the COPYING file for details.<br />\n";
		echo "This is free software, and you are welcome to redistribute it under certain conditions; read " .
			"COPYING for details.</p>\n"
?>

<!-- phpEquiMon is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.<br />
phpEquiMon is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.<br />
You should have received a copy of the GNU General Public License
along with this program.  If not, see http://www.gnu.org/licenses/. -->
<?php
		echo "</body>\n";
		echo "</html>\n";
	}
	
	public function printIcon( $name )
	{
		if ( file_exists( "themes/default/icos/$name.png" ) )
			return "<img src=\"themes/default/icos/$name.png\" alt=\"$name\" />";
		else
			return $name;
	}
	
	public function printTooltip( $target, $title, $tooltip, $name = false )
	{
		$tooltip = str_replace( array( "<", ">" ), array( "&lt;", "&gt;" ), $tooltip );
		
		echo "<a href=\"$target\"";
		
		if ( $name )
			echo " id=\"$name\"";
		
		echo " onmouseover=\"Tip( '$tooltip' );\">$title</a>\n";
	}
	
	public function calcDowntimeColor( $time )
	{
		global $backend;
		
		if ( $time == -1 )
			return "#FF8888";
		else if ( $time < $backend->readConfig( "cron_interval" ) + 60 )
			return "#88FF88";  // Green
		else if ( $time < 3600 * 6 )
		{
			$cdiff = floor( 128 + $time / 170 );
			return sprintf( "#%02XFF88", $cdiff );  // Green -> Yellow transition
		}
		else
		{
			$cdiff = floor( 255 - ( $time - 3600 * 6 ) / 3600 );
			if ( $cdiff < 128 )
				$cdiff = 128;
			
			return sprintf( "#FF%02X88", $cdiff );  // Yellow -> Red transistion
		}
	}
}

$theme = new DefaultTheme();

?>
