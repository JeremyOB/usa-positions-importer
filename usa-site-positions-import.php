<?php
/*
 
 * @package     usa-positions-import
 * @author      Jeremy O'Byrne
 * @copyright   2017 Jeremy O'Byrne
 * @license     GPL-2.0+
 
 * @wordpress-plugin
 * Plugin Name: USA positions import
 * Plugin URI:  https://example.com/plugin-name
 * Description: Imports job posts from the us ntm website rss feed https://ethnos360.org/rss/jobs
 * Version:     1.0.0
 * Author:      Jeremy O'Byrne
 * Author URI:  https://jeremyobyrne.com
 * Text Domain: usa-positions-import
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

 define( 'PLUGIN_DIR', plugin_dir_path( __FILE__ ));

function import_feed_items()
{
// 	include( plugin_dir_path( __FILE__ ) . 'debug/');
// 	include( plugin_dir_path( __FILE__ ) . 'include/delet_all_positions');
	
 write_log('import_feed_items called');
 $import_switch = get_option('import_switch');
	
	//check that we arnt running this function more than once 
	if ( $import_switch == '1'){
       update_option('import_switch','0');
	
       //The US site created multiple feeds to seperate position categories
	 $feeds = array(
	  'aviation-projects'				   => fetch_feed('https://ethnos360.org/feeds/jobs-aviation'),
	  'business-and-administration'		   => fetch_feed('https://ethnos360.org/feeds/jobs-business-and-administration'),
	  'communications'					   => fetch_feed('https://ethnos360.org/feeds/jobs-communications'),
	  'construction' 					   => fetch_feed('https://ethnos360.org/feeds/jobs-construction-and-maintenance'),
	  'teaching-elementary'				   => fetch_feed('https://ethnos360.org/feeds/jobs-education-elementary-teaching'),
	  'teaching-secondary' 				   => fetch_feed('https://ethnos360.org/feeds/jobs-education-secondary-teaching'),
	  'education-administration-and-staff' => fetch_feed('https://ethnos360.org/feeds/jobs-education-administration-and-staff'),
	  'other-education'					   => fetch_feed('https://ethnos360.org/feeds/jobs-education-other'),
	  'evangelism-and-church-planting'     => fetch_feed('https://ethnos360.org/feeds/jobs-evangelism-and-church-planting'),
	  'food-service'                       => fetch_feed('https://ethnos360.org/feeds/jobs-food-service'),
	  'health-care' 					   => fetch_feed('https://ethnos360.org/feeds/jobs-health-care'),
	  'hospitality' 					   => fetch_feed('https://ethnos360.org/feeds/jobs-hospitality'),
	  'law' 							   => fetch_feed('https://ethnos360.org/feeds/jobs-law'),
	  'technology'					       => fetch_feed('https://ethnos360.org/feeds/jobs-technology'),
	  'training' 						   => fetch_feed('https://ethnos360.org/feeds/jobs-training'),
	  );
	 write_log('first->feeds fetched');
 // start looping through each feed 
		foreach ($feeds as $feed_key => $feed_value) {	
		   if( !is_wp_error($feed_value) ){
		    $items = $feed_value->get_items();		    
		      foreach ( $items as $item ){
		       
		        	//create loop variables 
		         $item_date = $item->get_date('Y-m-d H:i:s');      
		         $post_cat = get_category_by_slug( $feed_key );
		         $xmlns = 'http://sitestacker.com/Job';
		        
		         //Strip html tags and regex's 
		         $strip_tags = array('&nbsp;','<br />','</span>','<span>','<ul>','</ul>','<li>','</li>','<p>','</p>','<li style="list-style-type: none">');
		         $purpose =  $item->get_item_tags($xmlns,'purpose')[0]['data']; 
		         if (!empty($purpose)) {$striped_purpose = $new_str = str_replace($strip_tags,'', $purpose);}
		         else {$striped_purpose = 'not listed';}
		         $content = $item->get_content();
		         if (!empty($content)) {$striped_content = $new_str = str_replace($strip_tags, '', $content);}
		         else {$striped_content = 'not listed';}		         
			        $post = array(
			          'post_content'   => $content,
			          'post_date'      => $item_date,
			          'post_title'     => $item->get_title(),
			          'post_status'    => 'publish',
			          'post_type'      => 'Position',
			          'post_category'  => array($post_cat->term_id),
		            );
			        
		        $meta = array(
		          	'position_region'			 => $item->get_item_tags($xmlns,'region')[0]['data'], 
		         	'position_country'			 => $item->get_item_tags($xmlns,'country')[0]['data'],
		         	'position_location' 		 => $item->get_item_tags($xmlns,'location')[0]['data'], 
		            'position_purpose'			 => $striped_purpose,
		            'position_paid_position'	 => '0', 
		            'position_minimum_duration'  => $item->get_item_tags($xmlns,'minimum_duration')[0]['data'] , 
		            'position_maximum_duration'  => $item->get_item_tags($xmlns,'maximum_duration')[0]['data'], 
		            'position_people_needed'	 => $item->get_item_tags($xmlns,'people_needed')[0]['data'], 
		            'position_person_needed' 	 => $item->get_item_tags($xmlns,'person_needed')[0]['data'], 
		            'position_priority'			 => $item->get_item_tags($xmlns,'priority')[0]['data'], 
		            'position_date_needed' 		 => $item->get_item_tags($xmlns,'date_needed')[0]['data'], 
		            'position_responsibilities'  => $striped_content,
		            'position_degree' 			 => $item->get_item_tags($xmlns,'degree')[0]['data'], 
		            'position_skills' 			 => $item->get_item_tags($xmlns,'skill')[0]['data'], 
		            'position_experience'		 => $item->get_item_tags($xmlns,'experience')[0]['data'], 
		            'position_comments'			 => $item->get_item_tags($xmlns,'comment')[0]['data'],      
		            );	
		        $post_id = wp_insert_post($post);

			    foreach ($meta as $key => $value) {
			     add_post_meta($post_id, $key, $meta[$key], true);
			   }  			  			   			   			
    		 }
          }
          
	  }

	  write_log('BOOM');
	}
	else {write_log('Import feed already run');}
	  
	}


//----------------------------------------------------------------------------------------------------------------------------------------------

// short script to quickly delet all custom post's typed positions 
function delet_all_positions () {
  $args = array (
    'post_type' => 'Position',
    'nopaging' => true
  );
  $query = new WP_Query ($args);
  while ($query->have_posts ()) {
    $query->the_post ();
    $id = get_the_ID ();
    wp_delete_post ($id, true);
  }
  wp_reset_postdata ();
  update_option('import_switch','1');
  write_log('positions deleted');

}
//------------------------------------------------------------------------------------------------------------------------------------------------------

//
if ( ! function_exists('write_log')) {
   function write_log ( $log )  {
      if ( is_array( $log ) || is_object( $log ) ) {
         error_log( print_r( $log, true ) );
      } else {
         error_log( $log );
      }
   }
}


//------------------------------------------------------------------------------------------------------------------------------------------------------
function write_to_log(){

$log = WP_PLUGIN_DIR."/usa-positions-import/log.txt"; 

if (! file_exists($log)) {//check to see if the log file exists in the plugin dir
$myfile = fopen(PLUGIN_DIR."log.txt", "w");// creates log file in plugin dir
write_log('log.txt created');
 }

// Open the file to get existing content
$current = file_get_contents($log);

// Append a new person to the file
$current .= date('l jS \of F Y h:i:s A')."\n";

// Write the contents back to the file
file_put_contents($log, $current);
 write_log('log made');
}




// add_action('init', 'delet_all_positions',10);
// add_action('init', 'import_feed_items', 10);
add_action('init', 'write_to_log', 10);
// add_action('init', 'script_finished', 20);
