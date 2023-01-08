#!/usr/bin/php

<?php

define( "PHPEQUIMON", true );
require "config.php";

try
{
	if ( isset( $CONF[ "pdouser" ] ) && isset( $CONF[ "pdopass" ] ) )
		$db = new PDO( $CONF[ "pdostring" ], $CONF[ "pdouser" ], $CONF[ "pdopass" ] );
	else
		$db = new PDO( $CONF[ "pdostring" ] );
}
catch ( PDOException $e )
{
	die( "Database connect failed: " . $e->getMessage() );
}

$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

$data = file( "nicdb.txt" );
if ( !$data )
	die( "Reading nicdb failed." );

$q = "INSERT INTO nicvendors( macstart, vendor ) VALUES( ?, ? );";
$st = $db->prepare( $q );
if ( !$st )
	die( "Preparing statement failed." );

$i = 0;
foreach ( $data as $line )
{
	++$i;
	$datae = explode( "(hex)", $line );
	$datae[ 0 ] = preg_replace( "/-/", ":", trim( $datae[ 0 ] ) );
	$datae[ 1 ] = trim( $datae[ 1 ] );
	$st->execute( array( $datae[ 0 ], $datae[ 1 ] ) );
	$st->closeCursor();
}

echo "Processed $i lines.\n";

?>

