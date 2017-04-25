README for the following scripts:
	export_database.php
	import_database.php
	import_dump_file.php
	import_tables.php

These scripts are for exporting/importing databases.
Follow these steps to get the scripts running.
==========================================================================

Step 1: Create a folder and name it "dumps".

Step 2: Create a defines.php file.

Step 3: Copy the following code into your defines.php file.

<?php
	/**
	 * Defines for use with the following scripts:
	 *		export_database.php
	 * 		import_database.php
	 * 		import_dump_file.php
	 *		import_tables.php
	 *
	 * These defines are server level variables and 
	 * for this reason this file and the dumps folder
	 * are .gitignored!
	 */

	// path to your dumps folder
	define( 'PATH_TO_DUMPS', 'dumps/' );

	/**
	 * Localhost creds
	 */
	$localUser = array(
		'server' => 'localhost',
		'host' => '127.0.0.1',
		'user' => 'user',
		'password' => 'password'
	);

	/**
	 * Array of remote server users and their creds
	 */
	$remoteUsers = array(
		array(
			'server' => 'remoteServer1Name',
			'host' => 'remoteServer1IPAddress',
			'user' => 'remoteServer1User',
			'password' => 'remoteServer1Password'
		),
		array(
			'server' => 'remoteServer2Name',
			'host' => 'remoteServer2IPAddress',
			'user' => 'remoteServer2User',
			'password' => 'remoteServer2Password'
		),
	);
?>

Step 4: Set the define variables as needed to work on your server.

Step 5: Open command line.

Step 6: cd into utilities/database/

Step 7: Run one of the scripts "php import_database.php"

==========================================================================

              (           (        )   (               )   (     (         
              )\ )  *   ) )\ )  ( /(   )\ )  *   )  ( /(   )\ )  )\ )      
   (      (  (()/(` )  /((()/(  )\()) (()/(` )  /(  )\()) (()/( (()/( (    
   )\     )\  /(_))( )(_))/(_))((_)\   /(_))( )(_))((_)\   /(_)) /(_)))\   
  ((_) _ ((_)(_)) (_(_())(_))   _((_) (_)) (_(_())   ((_) (_))  (_)) ((_)  
 _ | || | | |/ __||_   _||_ _| | \| | / __||_   _|  / _ \ | |   | _ \| __| 
| || || |_| |\__ \  | |   | |  | .` | \__ \  | |   | (_) || |__ |  _/| _|  
 \__/  \___/ |___/  |_|  |___| |_|\_| |___/  |_|    \___/ |____||_|  |___|