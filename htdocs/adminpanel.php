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
 * Shows the administative panel.
 */
function pageAdminPanel()
{
	global $backend, $theme;
	
	$admin = $backend->checkAuth( 1 );
	if ( !$admin )
	{
		echo "<p class=\"err\">Admin privileges needed.</p>\n";
		return;
	}

?>

<h2>Admin Panel</h2>

<p>This is the place to perform all administrative tasks regarding this installation.</p>

<table class="navhead" style="margin-bottom: 30px">

<tr><td><a href="?a=as"><?php echo $theme->printIcon( "adminp" ); ?><br />
<span style="font-weight: bold">System settings</span><br />
Customize this installation to your system<br />(path to system tools, PXE servers etc.)</a></td>

<td><a href="?a=am"><?php echo $theme->printIcon( "authman" ); ?><br />
<span style="font-weight: bold">Authentification management.</span><br />
Change existing groups, users and group memberships.</a></td>

<td><a href="?a=ak"><?php echo $theme->printIcon( "pxekernel" ); ?><br />
<span style="font-weight: bold">PXE kernel management.</span><br />
Configure available kernels for PXE booting and default parameters.</a></td>

<td><?php echo $theme->printIcon( "styles" ); ?><br />
<span style="font-weight: bold">Styles</span><br />
Manage installed styles.<br />Under construction.</td></tr>

</table>

<?php

}

?>
