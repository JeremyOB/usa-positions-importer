<?php
function plugin_log($log){

$log_file = WP_PLUGIN_DIR."/usa-positions-import/plugin_debug.log"; 

if (! file_exists($log_file)) {//check to see if the log file exists in the plugin dir
$myfile = fopen(PLUGIN_DIR."plugin_debug.log", "w");// creates log file in plugin dir
wp_log('plugin_debug.log created');
 }

// Open the file to get existing content
$current = file_get_contents($log_file);
$date =  date('[d-M-Y h:i:s] ');

// Append a new person to the file
$current .= $date.$log."\n";

// Write the contents back to the file
file_put_contents($log_file, $current);
 wp_log('log made');
}