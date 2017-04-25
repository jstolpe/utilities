README for the following scripts:
	create_project.php

This script will create a sublime project file. 
Follow these steps to get the script running.
==========================================================================

Step 1: Create a defines.php file.

Step 2: Copy the following code into your defines.php file.

<?php
	/**
	 * Defines for use with create_project script.
	 *
	 * These defines are server level variables and 
	 * for this reason this file is .gitignored!
	 */

	// path to where your sublime projects are stored
	define( 'PATH_TO_SUBLIME_PROJECTS', 'C:\wamp\sublime_projects\\' );

	// path to your www/ directory in FORWARD slashes
	define( 'PATH_TO_WWW', '/C/wamp/www/' );
?>

Step 3: Set the define variables as needed to work on your server.

Step 4: Open command line.

Step 5: cd into utilities/sublime/create_project/

Step 6: Run "php create_project.php"

==========================================================================

              (           (        )   (               )   (     (         
              )\ )  *   ) )\ )  ( /(   )\ )  *   )  ( /(   )\ )  )\ )      
   (      (  (()/(` )  /((()/(  )\()) (()/(` )  /(  )\()) (()/( (()/( (    
   )\     )\  /(_))( )(_))/(_))((_)\   /(_))( )(_))((_)\   /(_)) /(_)))\   
  ((_) _ ((_)(_)) (_(_())(_))   _((_) (_)) (_(_())   ((_) (_))  (_)) ((_)  
 _ | || | | |/ __||_   _||_ _| | \| | / __||_   _|  / _ \ | |   | _ \| __| 
| || || |_| |\__ \  | |   | |  | .` | \__ \  | |   | (_) || |__ |  _/| _|  
 \__/  \___/ |___/  |_|  |___| |_|\_| |___/  |_|    \___/ |____||_|  |___|