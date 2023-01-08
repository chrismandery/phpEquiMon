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
 * Allows all admins to manipulate the database of available kernels for PXE booting including changing the default
 * parameter line(s) for them.
 */
function pagePXEAdmin()
{
	global $backend, $session;
	
	echo "<h2>PXE Kernel Management</h2>\n";
	
	// Kernel line mass edit?
	if ( isset( $_GET[ "massedit" ] ) && $_GET[ "massedit" ] == "true" )
	{
		if ( isset( $_POST[ "submit" ] ) )
		{
			$i = 1;
			while ( isset( $_POST[ "id$i" ] ) && isset( $_POST[ "pl$i" ] ) && helperIsDigit( $_POST[ "id$i"
				] ) )
			{
				if ( empty( $_POST[ "pl$i" ] ) )
					$backend->deleteKernelParameterLine( $_POST[ "id$i" ] );
				else
					$backend->editKernelParameterLine( $_POST[ "id$i" ], $_POST[ "pl$i" ] );
				
				++$i;
			}
			
			$backend->logEvent( $session->getID(), "Mass edit kernel parameter lines." );
		}
		
		echo "<p>You can change all saved kernel parameter lines here on one page. Clearing an input box " .
			"deletes the parameter line.</p>\n";
		echo "<p><a href=\"?a=ak\">Click here to return to kernel administration.</a></p>\n";
		
		$paramlines = $backend->getAllKernelParameterLines();
		
		echo "<form method=\"post\" action=\"?a=ak&massedit=true\">\n";
		
		echo "<table class=\"editlist\">\n";
		echo "<tr class=\"listrow-header\"><td>Kernel name</td><td>Parameter line</td></tr>\n";
		
		$i = 1;
		foreach ( $paramlines as $p )
		{
			if ( $i % 2 )
				echo "<tr class=\"editrow-light\">\n";
			else
				echo "<tr class=\"editrow-dark\">\n";
			
			echo "<td>" . helperEncodeHTML( $p[ "kernelname" ] ) . "</td>\n";
			
			echo "<td><input type=\"hidden\" name=\"id$i\" value=\"" . $p[ "id" ] . "\" /><input type=\"" .
				"text\" name=\"pl$i\" value=\"" . helperEncodeHTML( $p[ "paramline" ] ) .
				"\" size=\"100\" /></td>\n";
			
			echo "</tr>\n";
			
			++$i;
		}
		
		echo "<tr class=\"listrow-header\"><td colspan=\"2\"><input type=\"submit\" name=\"submit\" value=\"" .
			"Change all\" /></td></tr>\n";
		
		echo "</table>\n";
		echo "</form>\n";
	}
	
	// Normal operation mode
	else
	{
		// Perform changes on the database: ... add kernel
		if ( isset( $_POST[ "addkernel" ] ) && !empty( $_POST[ "name" ] ) )
		{
			$backend->addKernel( $_POST[ "name" ] );
			$backend->logEvent( $session->getID(), "Added kernel: " . $_POST[ "name" ] );
		}
		
		// ... edit kernel
		else if ( isset( $_POST[ "sedit" ] ) && isset( $_POST[ "id" ] ) && helperIsDigit( $_POST[ "id" ] ) &&
			isset( $_POST[ "type" ] ) && isset( $_POST[ "description" ] ) )
		{
			$backend->editKernel( $_POST[ "id" ], $_POST[ "type" ], $_POST[ "description" ] );
			$backend->logEvent( $session->getID(), "Edit kernel: id = " . $_POST[ "id" ] );
		}
		
		// ... delete kernel
		else if ( isset( $_GET[ "delete" ] ) && helperIsDigit( $_GET[ "delete" ] ) )
		{
			$backend->deleteKernel( $_GET[ "delete" ] );
			$backend->logEvent( $session->getID(), "Deleted kernel: id = " . $_GET[ "delete" ] );
		}
		
		// ... add parameter line
		else if ( isset( $_POST[ "saddpl" ] ) && isset( $_POST[ "id" ] ) && helperIsDigit( $_POST[ "id" ] ) &&
			isset( $_POST[ "paramline" ] ) )
		{
			$backend->addKernelParameterLine( $_POST[ "id" ], $_POST[ "paramline" ] );
			$backend->logEvent( $session->getID(), "Added parameter line for kernel: id = " . $_POST[
				"id" ] );
			
			$kid = $_POST[ "id" ];
		}
		
		// ... delete parameter line
		else if ( isset( $_GET[ "kernel" ] ) && helperIsDigit( $_GET[ "kernel" ] ) &&
			isset( $_GET[ "deletepl" ] ) && helperIsDigit( $_GET[ "deletepl" ] ) )
		{
			$backend->deleteKernelParameterLine( $_GET[ "deletepl" ] );
			$backend->logEvent( $session->getID(), "Removed parameter line: id = " . $_GET[ "deletepl" ] );
			
			$kid = $_GET[ "kernel" ];
		}
		
		// ... just in edit mode
		if ( isset( $_GET[ "edit" ] ) && helperIsDigit( $_GET[ "edit" ] ) )
			$kid = $_GET[ "edit" ];
		
		if ( isset( $kid ) )
		{
			$kernel = $backend->getKernelByID( $kid );
			
			echo "<form action=\"?a=ak&amp;e=" . $kid . "\" method=\"post\">\n";
			echo "<table class=\"editlist\" style=\"margin-bottom: 30px\">\n";
			
			echo "<tr class=\"editrow-dark\"><td>Filename</td><td>" .helperEncodeHTML( $kernel[ "filename"
				] ) . "<input type=\"hidden\" name=\"id\" value=\"" . $kid . "\" /></td></tr>\n";
			
			if ( $kernel[ "type" ] == 1 )
			{
				echo "<tr class=\"editrow-light\"><td>Type</td><td>";
				echo "<input type=\"radio\" name=\"type\" value=\"1\" checked=\"checked\" />SUSE-" .
					"like&nbsp;&nbsp;";
				echo "<input type=\"radio\" name=\"type\" value=\"2\" />Red Hat-like&nbsp;&nbsp;";
				echo "<input type=\"radio\" name=\"type\" value=\"0\" />Other</td></tr>\n";
			}
			else if ( $kernel[ "type" ] == 2 )
			{
				echo "<tr class=\"editrow-light\"><td>Type</td><td>";
				echo "<input type=\"radio\" name=\"type\" value=\"1\" />SUSE-Like&nbsp;&nbsp;";
				echo "<input type=\"radio\" name=\"type\" value=\"2\" checked=\"checked\" />Red Hat-" .
					"like&nbsp;&nbsp;";
				echo "<input type=\"radio\" name=\"type\" value=\"0\" />Other</td></tr>\n";
			}
			else
			{
				echo "<tr class=\"editrow-light\"><td>Type</td><td>";
				echo "<input type=\"radio\" name=\"type\" value=\"1\" />SUSE-like&nbsp;&nbsp;";
				echo "<input type=\"radio\" name=\"type\" value=\"2\" />Red Hat-like&nbsp;&nbsp;";
				echo "<input type=\"radio\" name=\"type\" value=\"0\" checked=\"checked\" />Other" .
					"</td></tr>\n";
			}
			
			echo "<tr class=\"editrow-dark\"><td>Description</td><td><input name=\"description\" type=\"" .
				"text\" value=\"" . helperEncodeHTML( $kernel[ "description" ] ) . "\" size=\"100\" " .
				"/></td></tr>\n";
			
			echo "<tr class=\"editrow-light\"><td>Parameter lines</td><td>\n";
			
			foreach( $kernel[ "paramlines" ] as $line )
			{
				echo helperEncodeHTML( $line[ "text" ] ) . " - <a href=\"?a=ak&amp;kernel=$kid&amp;" .
					"deletepl=" . $line[ "id" ] . "\">Delete</a><br />\n";
			}
			
			echo "Add new: <input type=\"text\" name=\"paramline\" size=\"100\" /> <input type=\"" .
				"submit\" name=\"saddpl\" value=\"Add new!\"  />\n";
			
			echo "</td></tr>\n";
			
			echo "<tr class=\"editrow-dark\"><td colspan=\"2\"><input type=\"submit\" name=\"sedit\" " .
				"value=\"Submit\" />&nbsp;<input type=\"reset\" name=\"reset\" value=\"Reset\" />" .
				"</tr>\n";
			
			echo "</table>\n";
			echo "</form>\n";
		}
		else
		{
			echo "<p><a href=\"?a=ak&massedit=true\">Click here to mass edit available parameter lines." .
				"</a></p>\n";
			
			echo "<form method=\"post\" action=\"?a=ak\">\n";
			echo "<p>Add new kernel:<input type=\"text\" name=\"name\" />\n";
			echo "<input type=\"submit\" name=\"addkernel\" value=\"Add Kernel\" /></p>\n";
			echo "</form>\n";
		}
		
		$kernels = $backend->getAllKernels();
		
		echo "<table class=\"editlist\">\n";
		echo "<tr class=\"listrow-header\"><td>Name in filesystem</td><td>Type</td><td>Description</td><td>" .
			"Saved default parameter lines</td><td>Edit</td><td>Delete</td></tr>\n";
		
		$highlight = true;
		foreach ( $kernels as $k )
		{
			if ( $highlight )
				echo "<tr class=\"editrow-light\">\n";
			else
				echo "<tr class=\"editrow-dark\">\n";
			
			$highlight = !$highlight;
			
			echo "<td>" . helperEncodeHTML( $k[ "filename" ] ) . "</td>\n";
			
			if ( $k[ "type" ] == 1 )
				echo "<td>SUSE-like</td>\n";
			else if ( $k[ "type" ] == 2 )
				echo "<td>Red Hat-like</td>\n";
			else
				echo "<td>Other</td>\n";
			
			echo "<td>" . helperEncodeHTML( $k[ "description" ] ) . "</td>\n";
			echo "<td>" . $k[ "pcount" ] . "</td>\n";
			echo "<td><a href=\"?a=ak&amp;edit=" . $k[ "id" ] . "\">Edit</a></td>\n";
			echo "<td><a href=\"?a=ak&amp;delete=" . $k[ "id" ] . "\">Delete</a></td>\n";
			
			echo "</tr>\n";
		}
		
		echo "</table>\n";
	}
}

?>
