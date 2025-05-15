SESSION HANDLING INSTRUCTIONS

To avoid session warnings, follow these guidelines:

1. Always include 'session_config.php' at the very beginning of your PHP files:
   require_once('session_config.php');  // or '../session_config.php' for files in subdirectories

2. Do NOT call session_start() directly in your files - it's already handled in session_config.php

3. Do NOT try to modify session parameters after including session_config.php

4. The config.php file no longer handles sessions - it only contains database and site configuration

Example usage:
<?php
require_once('session_config.php');  // Always first!
require_once('db_connect.php');
require_once('config.php');

// Your code here...
?>