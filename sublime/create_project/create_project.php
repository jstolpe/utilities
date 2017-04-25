<?php
	/**
	 * This script creates a sublime project file
	 *
	 * @author     Justin Stolpe
	 * @license    The MIT License
	 * @version    1.0
	 */

	// include defines for this script
	include( 'defines.php' );

	// get website folder name to create project for
	$websiteFolderName = getWebsiteFolderName();

	// path to new sublime project file
	$pathToNewSublimeProjectFile = PATH_TO_SUBLIME_PROJECTS . $websiteFolderName . '.sublime-project';

	// replace XXXX in our template project with path to project
	$replaceXXXXWith = PATH_TO_WWW . $websiteFolderName;

	// display start message
	$startMessage = "\ncreating sublime project for " . $websiteFolderName . " ...";
	say( $startMessage );

	// command line sed FTW! copy template XXXX project and replace XXXX with website folder name
	exec( 'cat XXXX.sublime-project | sed "s@XXXX@' . $replaceXXXXWith . '@" >  ' . $pathToNewSublimeProjectFile );

	// tell user what happened message
	$tellUserWhatHappenedMessage = "\n\n\"" . $websiteFolderName . ".sublime-project\" created in \"" . PATH_TO_SUBLIME_PROJECTS . "\"";
	say( $tellUserWhatHappenedMessage );

	// gg
	$finishMessage = "\n\n...gg!\n";
	say( $finishMessage );

	displaySignature();

	/**
	 * Get website folder name
	 *
	 * @param void
	 *
	 * @return String website folder name
	 */
	function getWebsiteFolderName() {
		// prompt message
		$getWebsiteNameMessage = "\nEnter a website folders name from your \"www/\" directory: ";

		// get name of the website folder in www directory
		$siteName = getUserInput( $getWebsiteNameMessage );

		while( !$siteName ) { // try until the user enters something
			$siteName = getUserInput( $getWebsiteNameMessage );
		}

		return $siteName;
	}

	/**
	 * Get users input
	 *
	 * @param string $promptMessage text to show the user
	 *
	 * @return user input
	 */
	function getUserInput( $promptMessage ) {
		fwrite( STDOUT, $promptMessage );
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