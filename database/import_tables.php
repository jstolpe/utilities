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

	// select database to use
	$dbhRemote->query( 'USE ' . $remoteDatabaseName );

	// set path to the file so we know where to get it from
	$pathToDbFile = PATH_TO_DUMPS . $remoteDatabaseName . '.sql';

	// get tables
	$remoteDbTables = getDbTables( $dbhRemote );

	// show tables on command line
	showRemoteDbTablesOnCommandLine( $remoteDatabaseName, $remoteDbTables, $remoteUser );

	// promt user to pick tables
	$tablesSelected = pickTables( $remoteUser, $remoteDbTables, $remoteDatabaseName );

	$verifyRemoteTablesSelected = verifyRemoteTablesPick( $remoteDatabaseName, $remoteUser, $tablesSelected );
	
	if ( 'yes' == $verifyRemoteTablesSelected ) { // good to go!
		// create sql dump file of the database
		createSqlDump( $remoteUser, $remoteDatabaseName, $tablesSelected, $pathToDbFile );

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
	 * Create sql dump file of the database
	 *
	 * @param Array $user
	 * @param String $databaseName
	 * @param Array $tables
	 * @param String $pathToDbFile
	 *
	 * @return Object
	 */
	function createSqlDump( $user, $databaseName, $tables, $pathToDbFile ) {
		// command line stuff
		echo "\nCreating .sql file of " . $databaseName . " database tables from remote server" . $user['server'] . " on localhost...\n";

		$tablesString = '';

		foreach ( $tables as $table ) {
			$tablesString .= $table . " ";
		}
		
		// command to dump the database from the remote server onto our localhost
		exec( 'mysqldump -u' . $user['user'] . ' -p' . $user['password'] . ' -h ' . $user['host'] . ' ' . $databaseName . ' ' . $tablesString .  '> ' . $pathToDbFile );

		// .sql file created
		echo "\n..." . $databaseName . ".sql file of " . $databaseName . " database tables created on localhost\n";
	}

	/**
	 * Verify tables pick
	 *
	 * @param Array $user
	 * @param String $tables
	 *
	 * @return String
	 */
	function verifyRemoteTablesPick( $dbName, $user, $tables ) {
		echo "\nTables selected for import from database " . $dbName . " on remote server " . $user['server'] . " for user " . $user['user'] . ":\n";
		
		foreach ( $tables as $key => $table ) { // dislay dbs on the command line
			echo ' - ' . $table . "\n";
		}

		// verify import of db
		$verifyImportString = "\nAre you sure you want to import these tables from remote server " . $user['server'] . " to localhost (yes,no)? ";
		$dbTablesVerifyImport = getInput( $verifyImportString );

		while ( 'yes' != $dbTablesVerifyImport && 'no' != $dbTablesVerifyImport ) { // keep asking until yes or no
			$dbTablesVerifyImport = getInput( $verifyImportString );
		}

		// return verified
		return $dbTablesVerifyImport;
	}

	/**
	 * Pick tables to import
	 *
	 * @param Array $user
	 * @param Array $tables
	 * @param String $dbName
	 *
	 * @return Integer
	 */
	function pickTables( $user, $tables, $dbName ) {
		// what database do you want to import
		$whatTablesString = "\nEnter table [#]'s to import from database " . $dbName . " on remote server " . $user['server'] . " to localhost (comma seperate for multiple tables): ";
		$tableNumbersSelected = getInput( $whatTablesString );

		// check for valid input
		$areTableNumbersValid = areTableNumbersValid( $tableNumbersSelected, $tables );

		while ( !$areTableNumbersValid['status'] ) { // keep asking until they enter valid numbers
			$tableNumbersSelected = getInput( $whatTablesString );
			$areTableNumbersValid = areTableNumbersValid( $tableNumbersSelected, $tables );
		}

		// return tables selected
		return $areTableNumbersValid['tables'];
	}

	/**
	 * Pick tables to import
	 *
	 * @param String $tableNumbersSelected
	 * @param Array $tables
	 *
	 * @return boolean
	 */
	function areTableNumbersValid( $tableNumbersSelected, $tables ) {
		// split up comma separated strin
		$selectedTables = array();
		$tableNumbers = explode(',', trim( $tableNumbersSelected ) );
		$areTableNumbersValid = true;

		foreach ( $tableNumbers as $tableNumber ) { // loop over tables numbers
			if ( !array_key_exists( $tableNumber, $tables ) ) { // bad number input, bad user...
				$areTableNumbersValid = false;
				break;
			} else {
				// add table name to return array
				$selectedTables[] = $tables[$tableNumber];
			}
		}

		return array(
			'status' => $areTableNumbersValid,
			'tables' => $selectedTables
		);
	}

	/**
	 * Display dbs on command line
	 *
	 * @param Array $user
	 * @param Array $dbsFound
	 *
	 * @return void
	 */
	function showRemoteDbTablesOnCommandLine( $dbName, $tablesFound, $user ) {
		echo "\nTables found in database " . $dbName . " on remote server " . $user['server'] . " for user " . $user['user'] . ":\n";
		
		foreach ( $tablesFound as $key => $table ) { // dislay dbs on the command line
			echo ' [' . $key . '] - ' . $table . "\n";
		}
	}

	/**
	 * Get database tables on server
	 *
	 * @param Object $dbh
	 *
	 * @return Array dbs
	 */
	function getDbTables( $dbh ) {
		$tables = $dbh->query( 'SHOW TABLES' );

		// store tables found in an array
		$tablesFound = array();

		while( $table = $tables->fetch() ) { // put table in array
			$tablesFound[] = $table[0];
		}

		return $tablesFound;
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