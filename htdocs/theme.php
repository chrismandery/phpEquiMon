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

interface ITheme
{
	/**
	 * Prints the header of the page, i.e. everything from the DOCTYPE to the first headline.
	 * @param boolean $setfocus Whether to set the focus on the first input box (used for edit form).
	 */
	public function printHeader( $setfocus );
	
	/**
	 * Prints the footer of the page, i.e. everything after the content.
	 * @param float $exectime Time needed for execution of the script.
	 * @param integer $queries Performed database queries during execution of the script.
	 */
	public function printFooter( $exectime, $queries );
	
	/**
	 * Returns the code for including an icon as an img tag or prints text if icon is not included with the theme.
	 * @param string Name of the icon to display.
	 */
	public function printIcon( $name );
	
	/**
	 * Prints a tooltipped link.
	 * @param string $target Link target.
	 * @param string $title Link title.
	 * @param string $tooltip Tooltip text.
	 * @param string $name Value of the HTML name attribute (optional).
	 */
	public function printTooltip( $target, $title, $tooltip, $name = false );
	
	/**
	 * Generates a color out of a given offline time for a machine (green = online etc.)
	 * @param integer $time Time the machine has been offline, in seconds.
	 * @return string Color in HTML format (#RRGGBB)
	 */
	public function calcDowntimeColor( $time );
}

?>