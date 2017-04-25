<?php
	// include server defines
	include( 'defines.php' );

	// get local things
	$dbhLocal = dbConnect( $localUser );
	$localDbs = getDbs( $dbhLocal );
	$dumpFiles = getDumpFiles();

	if ( empty( $dumpFiles ) ) { // no files to import
		echo "\nNo dump files found in \"" . PATH_TO_DUMPS . "\"\n";
		exitWithNoImport();
	} else { // go ahead with import
		showDumpsOnCommandLine( $dumpFiles );

		// prompt user to select file
		$fileSelected = pickDumpFile( $dumpFiles );

		// verify selection
		$verified = verifyDumpFilePick( $fileSelected );

		if ( 'yes' == $verified ) { // go ahead and import
			// set path to the file so we know where to get it from
			$pathToDbFile = PATH_TO_DUMPS . $fileSelected;

			// display remote databases on command line
			showLocalDbsOnCommandLine( $localUser, $localDbs );

			// promt for local database to import into
			$importIntoDatabaseName = getImportIntoDatabase( $localUser, $localDbs, $fileSelected );

			// verify pick
			$verifiedLocal = verifyLocalDbPick( $importIntoDatabaseName, $fileSelected );

			if ( 'yes' == $verifiedLocal ) { // verified local dabase import
				// command line stuff
				echo "\nImporting " . $fileSelected . ".sql dump file into database " . $importIntoDatabaseName . " on localhost...\n";

				try { // create the database if it does not exist
			        $dbhLocal->exec("CREATE DATABASE IF NOT EXISTS `$importIntoDatabaseName`") or die( print_r( $db->errorInfo(), true ) );
			    } catch (PDOException $e) { // we have failed
			        die("Error creating database: ". $e->getMessage());
			    }

				// command to import the db dump sql file into our local mysql
				exec( 'mysql -u' . $localUser['user'] . ' -h ' . $localUser['host'] . ' -D ' . $importIntoDatabaseName  . ' < ' . $pathToDbFile );
			} else { // no import for you
				exitWithNoImport();
			}

			// gg
			echo "\n...gg!\n";
		} else { // no import for you
			exitWithNoImport();
		}
	}

	displaySignature();

	/**
	 * Verify database pick
	 *
	 * @param String $localDatabase
	 * @param String $fileSelected
	 *
	 * @return String
	 */
	function verifyLocalDbPick( $localDatabase, $fileSelected ) {
		// verify import of db
		$verifyImportString = "\nAre you sure you want to import " . $fileSelected . ".sql dump file into database " . $localDatabase . " on localhost (yes,no)? ";
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
	 * @param String $fileSelected
	 *
	 * @return Integer
	 */
	function getImportIntoDatabase( $user, $dbs, $fileSelected ) {
		// what database do you want to import
		$whatDatabaseString = "\nEnter database [#] you want to import the " . $fileSelected . " dump file into on localhost: ";
		$dbNumberSelected = getInput( $whatDatabaseString );

		while ( '-1' != $dbNumberSelected && !array_key_exists( $dbNumberSelected, $dbs ) 
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
	function showLocalDbsOnCommandLine( $user, $dbsFound ) {
		echo "\nDatabases found on " . $user['server'] . " for user " . $user['user'] . ":\n";
		echo " [-1] - CREATE NEW DATABASE\n";

		foreach ( $dbsFound as $key => $db ) { // dislay dbs on the command line
			echo ' [' . $key . '] - ' . $db . "\n";
		}
	}

	/**
	 * Verify dump file pick
	 *
	 * @param String $fileName
	 *
	 * @return String
	 */
	function verifyDumpFilePick( $fileName ) {
		// verify import of db
		$verifyImportString = "\nAre you sure you want to import dump file " . $fileName . " (yes,no)? ";
		$dumpFileVerifyImport = getInput( $verifyImportString );

		while ( 'yes' != $dumpFileVerifyImport && 'no' != $dumpFileVerifyImport ) { // keep asking until yes or no
			$dumpFileVerifyImport = getInput( $verifyImportString );
		}

		// return verified
		return $dumpFileVerifyImport;
	}

	/**
	 * Pick a dump file
	 *
	 * @param Array $dumpFiles
	 *
	 * @return Integer
	 */
	function pickDumpFile( $dumpFiles ) {
		// what dump file do you want to import
		$whatFile = "\nEnter dump file [#] you want to import: ";
		$fileNumberSelected = getInput( $whatFile );

		while ( !array_key_exists( $fileNumberSelected, $dumpFiles ) ) { // keep asking until valid number is given
			$fileNumberSelected = getInput( $whatFile );
		}

		// return db selected
		return $dumpFiles[$fileNumberSelected];
	}

	/**
	 * Get files in dumps folder
	 *
	 * @param void
	 *
	 * @return Array
	 */
	function getDumpFiles() {
		$dumpFiles = scandir( 'dumps' );

		$validFiles = array();

		foreach ( $dumpFiles as $file ) { // dislay dbs on the command line
			if ( !in_array( trim( $file ), ['.', '..'] ) ) {
				$filePieces = explode( '.', $file );

				if ( isset( $filePieces[1] ) && 'sql' == $filePieces[1] ) { // only display .sql files
		        	$validFiles[] = $file;
		        }
		    }
		}

		return $validFiles;
	}

	/**
	 * Display dump files on command line
	 *
	 * @param Array $user
	 * @param Array $dbsFound
	 *
	 * @return void
	 */
	function showDumpsOnCommandLine( $dumpFiles ) {
		echo "\nDump files found in \"" . PATH_TO_DUMPS . "\":\n";
		
		foreach ( $dumpFiles as $key => $file ) { // dislay dbs on the command line
			echo ' [' . $key . '] - ' . $file . "\n";
		}
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