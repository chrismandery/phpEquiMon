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

$CONF[ "dhcp-cfg-file" ] = "/home/chris/equimon-db/dhcpd.conf";
$CONF[ "dhcp-restart-cmd" ] = "";
$CONF[ "gateway" ] = "10.20.88.1";
$CONF[ "logfile" ] = "/var/log/messages";
$CONF[ "maxwait" ] = 86400;
$CONF[ "netmask" ] = "255.255.252.0";
$CONF[ "phpequimon-addr" ] = "10.20.68.117";
$CONF[ "phpequimon-certname" ] = "ls3514";
$CONF[ "pxe-cfg-dir" ] = "/home/chris/equimon-db/tftp/";
$CONF[ "pxe-cfg-dir-ia64" ] = "/tftpboot/";
$CONF[ "renamedelay" ] = 30;
$CONF[ "sleeptime" ] = 10;

?>
