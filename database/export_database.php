<?php
	// include server defines
	include( 'defines.php' );

	// combine local and remote users array
	$users = $remoteUsers;
	array_unshift($users, $localUser);

	// get server user to exporting from
	$user = getServerUser( $users );

	// db connect
	$dbh = dbConnect( $user );

	// get dbs
	$dbs = getDbs( $dbh );

	// display databases on command line
	showDbsOnCommandLine( $user, $dbs );

	// promt user to pick database
	$databaseName = pickDb( $user, $dbs );

	// set path to the file so we know where to get it from
	$pathToDbFile = PATH_TO_DUMPS . $databaseName . '.sql';

	// verify pick
	$verified = verifyDbPick( $user, $databaseName );

	if ( 'yes' == $verified ) { // go ahead and import
		// create sql dump file of the database
		createSqlDump( $user, $databaseName, $pathToDbFile );	

		// gg
		echo "\n...gg!\n";
	} else { // no import for you
		exitWithNoExport();
	}

	displaySignature();

	/**
	 * Exit with no export
	 *
	 * @param void
	 *
	 * @return void
	 */
	function exitWithNoExport() {
		echo "\n...No export for you!\n";
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
		echo "\nCreating .sql file of " . $databaseName . " database from server" . $user['server'] . " on localhost...\n";

		// command to dump the database from the remote server onto our localhost
		exec( 'mysqldump -u' . $user['user'] . ' -p' . $user['password'] . ' -h ' . $user['host'] . ' ' . $databaseName . ' > ' . $pathToDbFile );

		// .sql file created
		echo "\n..." . $databaseName . ".sql file of " . $databaseName . " database created on localhost\n";
	}

	/**
	 * Verify database pick
	 *
	 * @param Array $user
	 * @param String $databaseName
	 *
	 * @return String
	 */
	function verifyDbPick( $user, $databaseName ) {
		// verify import of db
		$verifyImportString = "\nAre you sure you want to export database " . $databaseName . " from server " . $user['server'] . " (yes,no)? ";
		$dbVerifyImport = getInput( $verifyImportString );

		while ( 'yes' != $dbVerifyImport && 'no' != $dbVerifyImport ) { // keep asking until yes or no
			$dbVerifyImport = getInput( $verifyImportString );
		}

		// return verified
		return $dbVerifyImport;
	}

	/**
	 * Pick a database
	 *
	 * @param Array $user
	 * @param Array $dbs
	 *
	 * @return Integer
	 */
	function pickDb( $user, $dbs ) {
		// what database do you want to import
		$whatDatabaseString = "\nEnter database [#] to export from server " . $user['server'] . ": ";
		$dbNumberSelected = getInput( $whatDatabaseString );

		while ( !array_key_exists( $dbNumberSelected, $dbs ) ) { // keep asking until valid number is given
			$dbNumberSelected = getInput( $whatDatabaseString );
		}

		// return db selected
		return $dbs[$dbNumberSelected];
	}

	/**
	 * Display dbs on command line
	 *
	 * @param Array $user
	 * @param Array $dbsFound
	 *
	 * @return void
	 */
	function showDbsOnCommandLine( $user, $dbsFound ) {
		echo "\nDatabases found on server " . $user['server'] . " for user " . $user['user'] . ":\n";
		
		foreach ( $dbsFound as $key => $db ) { // dislay dbs on the command line
			echo ' [' . $key . '] - ' . $db . "\n";
		}
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
	 * Get user to export from
	 *
	 * @param Array $remoteUsers
	 *
	 * @return Integer
	 */
	function getServerUser( $users ) {
		echo "\nSelect a server user to export from:\n";
	
		foreach ( $users as $key => $user ) { // dislay server users on the command line
			echo " [" . $key . "] - [server name: " . $user['server'] . "] [user: " . $user['user'] . "] [host: " . $user['host'] . "]\n";
		}

		// prompt user for input
		$whatUserString = "\nEnter the server user [#] you want to export from: ";
		$userNumberSelected = getInput( $whatUserString );

		while ( !array_key_exists( $userNumberSelected, $users ) ) { // keep asking until valid number is given
			$userNumberSelected = getInput( $whatUserString );
		}

		// return remote user selected
		return $users[$userNumberSelected];
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