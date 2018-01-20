<?php
	// include server defines
	include( 'defines.php' );

	// get local things
	$dbhLocal = dbConnect( $localUser );
	$localDbs = getDbs( $dbhLocal );

	// get remote things
	$remoteUser = getRemoteUser( $remoteUsers );
	$dbhRemote =  dbConnect( $remoteUser );
	$remoteDbs = getDbs( $dbhRemote );
	
	// display remote databases on command line
	showRemoteDbsOnCommandLine( $remoteUser, $remoteDbs );
	
	// promt user to pick database
	$remoteDatabaseName = pickRemoteDb( $remoteUser, $remoteDbs );

	// set path to the file so we know where to get it from
	$pathToDbFile = PATH_TO_DUMPS . $remoteDatabaseName . '.sql';

	// verify pick
	$verified = verifyRemoteDbPick( $remoteUser, $remoteDatabaseName );

	if ( 'yes' == $verified ) { // go ahead and import
		// create sql dump file of the database
		createSqlDump( $remoteUser, $remoteDatabaseName, $pathToDbFile );

		// display remote databases on command line
		showLocalDbsOnCommandLine( $localUser, $localDbs, $remoteDatabaseName );
		
		// promt for local database to import into
		$importIntoDatabaseName = getImportIntoDatabase( $localUser, $localDbs, $remoteDatabaseName );

		// verify pick
		$verifiedLocal = verifyLocalDbPick( $importIntoDatabaseName, $remoteDatabaseName );

		if ( 'yes' == $verifiedLocal ) { // verified local dabase import
			// command line stuff
			echo "\nImporting " . $remoteDatabaseName . ".sql file into database " . $importIntoDatabaseName . " on localhost...\n";

			try { // create the database if it does not exist
		        $dbhLocal->exec("CREATE DATABASE IF NOT EXISTS `$importIntoDatabaseName`") or die( print_r( $db->errorInfo(), true ) );
		    } catch (PDOException $e) { // we have failed
		        die("Error creating database: ". $e->getMessage());
		    }

			// command to import the db dump sql file into our local mysql
			exec( 'mysql -u' . $localUser['user'] . ' -h ' . $localUser['host'] . ' -D ' . $importIntoDatabaseName  . ' < ' . $pathToDbFile );

			// cleanup and remove sql dump file
			exec ( 'rm ' . $pathToDbFile );
		} else { // no import for you
			exitWithNoImport();
		}

		// gg
		echo "\n...gg!\n";
	} else { // no import for you
		exitWithNoImport();
	}

	displaySignature();

	/**
	 * Exit with no import
	 *
	 * @param void
	 *
	 * @return void
	 */
	function exitWithNoImport() {
		echo "\n...No import for you!\n";
	}

	/**
	 * Create sql dump file of the database
	 *
	 * @param Array $user
	 * @param String $databaseName
	 * @param String $pathToDbFile
	 *
	 * @return Object
	 */
	function createSqlDump( $user, $databaseName, $pathToDbFile ) {
		// command line stuff
		echo "\nCreating .sql file of " . $databaseName . " database from remote server" . $user['server'] . " on localhost...\n";

		// command to dump the database from the remote server onto our localhost
		exec( 'mysqldump --single-transaction -u' . $user['user'] . ' -p' . $user['password'] . ' -h ' . $user['host'] . ' ' . $databaseName . ' > ' . $pathToDbFile );

		// .sql file created
		echo "\n..." . $databaseName . ".sql file of " . $databaseName . " database created on localhost\n";
	}

	/**
	 * Make db connection
	 *
	 * @param Array $user
	 *
	 * @return Object
	 */
	function dbConnect( $user ) {
		try { // make connection to database
			$db = new PDO( "mysql:host=" . $user['host'], $user['user'], $user['password']  );
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch( PDOException $ex ) { // we have failed
			die( "Failed to connect to the database: " . $ex->getMessage() ); 
		}
		
		return $db;
	}

	/**
	 * Get remote user to import from
	 *
	 * @param Array $remoteUsers
	 *
	 * @return Integer
	 */
	function getRemoteUser( $remoteUsers ) {
		echo "\nSelect a remote server user to import from:\n";
	
		foreach ( $remoteUsers as $key => $remoteUser ) { // dislay dbs on the command line
			echo " [" . $key . "] - [server name: " . $remoteUser['server'] . "] [user: " . $remoteUser['user'] . "] [host: " . $remoteUser['host'] . "]\n";
		}

		// prompt user for input
		$whatRemoteUserString = "\nEnter the remote server user [#] you want to import from: ";
		$remoteUserNumberSelected = getInput( $whatRemoteUserString );

		while ( !array_key_exists( $remoteUserNumberSelected, $remoteUsers ) ) { // keep asking until valid number is given
			$remoteUserNumberSelected = getInput( $whatRemoteUserString );
		}

		// return remote user selected
		return $remoteUsers[$remoteUserNumberSelected];
	}

	/**
	 * Get databases on server
	 *
	 * @param Object $dbh
	 *
	 * @return Array dbs
	 */
	function getDbs( $dbh ) {
		// get databases on server
		$dbs = $dbh->query( 'SHOW DATABASES' );

		// store databases found in an array
		$dbsFound = array();

		while( ( $db = $dbs->fetchColumn( 0 ) ) !== false ) { // put dbs in array
			$dbsFound[] = $db;
		}

		return $dbsFound;
	}

	/**
	 * Display dbs on command line
	 *
	 * @param Array $user
	 * @param Array $dbsFound
	 *
	 * @return void
	 */
	function showRemoteDbsOnCommandLine( $user, $dbsFound ) {
		echo "\nDatabases found on remote server " . $user['server'] . " for user " . $user['user'] . ":\n";
		
		foreach ( $dbsFound as $key => $db ) { // dislay dbs on the command line
			echo ' [' . $key . '] - ' . $db . "\n";
		}
	}

	/**
	 * Display local dbs on command line
	 *
	 * @param Array $user
	 * @param Array $dbsFound
	 * @param String $remoteDatabaseName
	 *
	 * @return void
	 */
	function showLocalDbsOnCommandLine( $user, $dbsFound, $remoteDatabaseName ) {
		echo "\nDatabases found on " . $user['server'] . " for user " . $user['user'] . ":\n";
		echo " [-2] - SAME DATABASE AS REMOTE SERVER (" . $remoteDatabaseName . ")\n";
		echo " [-1] - CREATE NEW DATABASE\n";

		foreach ( $dbsFound as $key => $db ) { // dislay dbs on the command line
			echo ' [' . $key . '] - ' . $db . "\n";
		}
	}

	/**
	 * Pick database to import into on localhost
	 *
	 * @param Array $user
	 * @param Array $dbs
	 * @param String $databaseName
	 *
	 * @return Integer
	 */
	function getImportIntoDatabase( $user, $dbs, $databaseName ) {
		// what database do you want to import
		$whatDatabaseString = "\nEnter database [#] you want to import the " . $databaseName . ".sql file into on localhost: ";
		$dbNumberSelected = getInput( $whatDatabaseString );

		while ( '-2' != $dbNumberSelected && 
			    '-1' != $dbNumberSelected && 
			    !array_key_exists( $dbNumberSelected, $dbs ) 
		) { // keep asking until valid number is given
			$dbNumberSelected = getInput( $whatDatabaseString );
		}

		if ( '-2' == $dbNumberSelected ) { // same database name as remote server
			$dbName = $databaseName;
		} else if ( '-1' == $dbNumberSelected ) { // create new database
			$newDatabaseName = "\nEnter name of the new databse to be created on localhost: ";
			$dbName = getInput( $newDatabaseName );

			while ( !$dbName ) { // keep asking until name is given
				$dbName = getInput( $newDatabaseName );
			}
		} else {
			$dbName = $dbs[$dbNumberSelected];
		}

		// return db name
		return $dbName;
	}

	/**
	 * Pick a database
	 *
	 * @param Array $user
	 * @param Array $dbs
	 *
	 * @return Integer
	 */
	function pickRemoteDb( $user, $dbs ) {
		// what database do you want to import
		$whatDatabaseString = "\nEnter database [#] to import from remote server " . $user['server'] . " to localhost: ";
		$dbNumberSelected = getInput( $whatDatabaseString );

		while ( !array_key_exists( $dbNumberSelected, $dbs ) ) { // keep asking until valid number is given
			$dbNumberSelected = getInput( $whatDatabaseString );
		}

		// return db selected
		return $dbs[$dbNumberSelected];
	}

	/**
	 * Verify database pick
	 *
	 * @param Array $user
	 * @param String $databaseName
	 *
	 * @return String
	 */
	function verifyRemoteDbPick( $user, $databaseName ) {
		// verify import of db
		$verifyImportString = "\nAre you sure you want to import database " . $databaseName . " from remote server " . $user['server'] . " to localhost (yes,no)? ";
		$dbVerifyImport = getInput( $verifyImportString );

		while ( 'yes' != $dbVerifyImport && 'no' != $dbVerifyImport ) { // keep asking until yes or no
			$dbVerifyImport = getInput( $verifyImportString );
		}

		// return verified
		return $dbVerifyImport;
	}

	/**
	 * Verify database pick
	 *
	 * @param String $localDatabase
	 * @param String $remoteDatabaseName
	 *
	 * @return String
	 */
	function verifyLocalDbPick( $localDatabase, $remoteDatabaseName ) {
		// verify import of db
		$verifyImportString = "\nAre you sure you want to import " . $remoteDatabaseName . ".sql file into database " . $localDatabase . " on localhost (yes,no)? ";
		$dbVerifyImport = getInput( $verifyImportString );

		while ( 'yes' != $dbVerifyImport && 'no' != $dbVerifyImport ) { // keep asking until yes or no
			$dbVerifyImport = getInput( $verifyImportString );
		}

		// return verified
		return $dbVerifyImport;
	}

	/**
	 * Get users input
	 *
	 * @param string $prompt text to show the user
	 *
	 * @return user input
	 */
	function getInput( $prompt ) {
		say( $prompt );
		return trim( fgets( STDIN ) );
	}

	/**
	 * Display command line text
	 *
	 * @param string $str to display in the command line
	 *
	 * @return user input
	 */
	function say( $str ) {
		fwrite( STDOUT, $str );
	}

	/**
	 * Display signature
	 *
	 * @param void
	 *
	 * @return void
	 */
	function displaySignature() {
		echo "\n\n";
		echo getSignature();
		echo "\n\n";
	}

	/**
	 * Get signature
	 *
	 * @param void
	 *
	 * @return String
	 */
	function getSignature() {
		return "
              (           (        )   (               )   (     (         
              )\ )  *   ) )\ )  ( /(   )\ )  *   )  ( /(   )\ )  )\ )      
   (      (  (()/(` )  /((()/(  )\()) (()/(` )  /(  )\()) (()/( (()/( (    
   )\     )\  /(_))( )(_))/(_))((_)\   /(_))( )(_))((_)\   /(_)) /(_)))\   
  ((_) _ ((_)(_)) (_(_())(_))   _((_) (_)) (_(_())   ((_) (_))  (_)) ((_)  
 _ | || | | |/ __||_   _||_ _| | \| | / __||_   _|  / _ \ | |   | _ \| __| 
| || || |_| |\__ \  | |   | |  | .` | \__ \  | |   | (_) || |__ |  _/| _|  
 \__/  \___/ |___/  |_|  |___| |_|\_| |___/  |_|    \___/ |____||_|  |___|
		";
	}