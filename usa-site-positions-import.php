<?php
/*
 * @package     usa-positions-import
 * @author      Jeremy O'Byrne
 * @copyright   2017 Jeremy O'Byrne
 * @license     GPL-2.0+
 * @wordpress-plugin
 * Plugin Name: USA positions import
 * Plugin URI:  https://example.com/plugin-name * Description:Imports job posts from the us ntm website rss feed https://ethnos360.org/rss/jobs * Version:1 . 0 . 0 * Author:Jeremy O'Byrne
 * Author URI:  https://jeremyobyrne.com
 * Text Domain: usa-positions-import
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

include 'plugin_log . php';
include 'delete_all_positions . php';
include 'wp_log . php';
define( 'PLUGIN_DIR', plugin_dir_path( __FILE__ ));

// 	include( plugin_dir_path( __FILE__ ) . 'include/delete_all_positions');

function import_feed_items()
{	
 plugin_log('import_feed_items called');

//check that we arnt running this function more than once 
 $import_switch = get_option('import_switch');
if ( $import_switch == '1'){
	 update_option('import_switch','0');

	//The US site created multiple feeds to separate position categories
	$feeds = array(
	'aviation - projects'				   	    => fetch_feed('https://ethnos360.org/feeds/jobs-aviation'),
	'business - and - administration'		    => fetch_feed('https://ethnos360.org/feeds/jobs-business-and-administration'),
	'communications'					        => fetch_feed('https://ethnos360.org/feeds/jobs-communications'),
	'construction' 					            => fetch_feed('https://ethnos360.org/feeds/jobs-construction-and-maintenance'),
	'teaching - elementary'				        => fetch_feed('https://ethnos360.org/feeds/jobs-education-elementary-teaching'),
	'teaching - secondary' 				        => fetch_feed('https://ethnos360.org/feeds/jobs-education-secondary-teaching'),
	'education - administration - and - staff'  => fetch_feed('https://ethnos360.org/feeds/jobs-education-administration-and-staff'),
	'other - education'					        => fetch_feed('https://ethnos360.org/feeds/jobs-education-other'),
	'evangelism - and - church - planting'      => fetch_feed('https://ethnos360.org/feeds/jobs-evangelism-and-church-planting'),
	'food - service'                            => fetch_feed('https://ethnos360.org/feeds/jobs-food-service'),
	'health - care' 					        => fetch_feed('https://ethnos360.org/feeds/jobs-health-care'),
	'hospitality' 					            => fetch_feed('https://ethnos360.org/feeds/jobs-hospitality'),
	'law' 							            => fetch_feed('https://ethnos360.org/feeds/jobs-law'),
	'technology'			                    => fetch_feed('https://ethnos360.org/feeds/jobs-technology'),
	'training' 				   	                => fetch_feed('https://ethnos360.org/feeds/jobs-training'),
	);
	
// start looping through each feed 
	foreach ($feeds as $feed_key => $feed_value) {	
		if( !is_wp_error($feed_value) ){
		$items = $feed_value->get_items();		    
			foreach ( $items as $item ){
			
				//create loop variables 
				$item_date = $item->get_date('Y - m - d H:i:s');      
				$post_cat = get_category_by_slug( $feed_key );
				$xmlns = 'http://sitestacker.com/Job';
			
				//Strip html tags and regex's 
				$strip_tags = array('&nbsp;', '<br />', '</span>', '<span>', '<ul>', '</ul>', '<li>', '</li>', '<p>', '</p>', '<li style="list-style-type: none">'); 
				$purpose = $item - > get_item_tags($xmlns, 'purpose')[0]['data']; 
				if ( ! empty($purpose)) {$striped_purpose = $new_str = str_replace($strip_tags, '', $purpose); }
				else {$striped_purpose = 'not listed'; }
				$content = $item - > get_content(); 
				if ( ! empty($content)) {$striped_content = $new_str = str_replace($strip_tags, '', $content); }
				else {$striped_content = 'not listed'; }	

				$post = array(
					'post_content'  => $content, 
					'post_date'     => $item_date, 
					'post_title'    => $item - > get_title(), 
					'post_status'   => 'publish', 
					'post_type'     => 'Position', 
					'post_category' => array($post_cat - > term_id), ); 
				
			$meta = array(
				'position_region'           => $item - > get_item_tags($xmlns, 'region')[0]['data'], 
				'position_country'          => $item - > get_item_tags($xmlns, 'country')[0]['data'], 
				'position_location'         => $item - > get_item_tags($xmlns, 'location')[0]['data'], 
				'position_purpose'          => $striped_purpose, 
				'position_paid_position'    => '0', 
				'position_minimum_duration' => $item - > get_item_tags($xmlns, 'minimum_duration')[0]['data'], 
				'position_maximum_duration' => $item - > get_item_tags($xmlns, 'maximum_duration')[0]['data'], 
				'position_people_needed'    => $item - > get_item_tags($xmlns, 'people_needed')[0]['data'], 
				'position_person_needed'    => $item - > get_item_tags($xmlns, 'person_needed')[0]['data'], 
				'position_priority'         => $item - > get_item_tags($xmlns, 'priority')[0]['data'], 
				'position_date_needed'      => $item - > get_item_tags($xmlns, 'date_needed')[0]['data'], 
				'position_responsibilities' => $striped_content, 
				'position_degree'           => $item - > get_item_tags($xmlns, 'degree')[0]['data'], 
				'position_skills'           => $item - > get_item_tags($xmlns, 'skill')[0]['data'], 
				'position_experience'       => $item - > get_item_tags($xmlns, 'experience')[0]['data'], 
				'position_comments'         => $item - > get_item_tags($xmlns, 'comment')[0]['data'], ); 	
			$post_id = wp_insert_post($post); 

			foreach ($meta as $key => $value) {
				add_post_meta($post_id, $key, $meta[$key], true); 
			}			  			   			   			
			}
		}
	}

}
else {plugin_log('Import feed already run'); }
}


// add_action('init', 'delet_all_positions',10);
// add_action('init', 'import_feed_items', 10);

// register_activation_hook(__FILE__, 'my_activation');

// function my_activation() {
//     if (! wp_next_scheduled ( 'my_hourly_event' )) {
// 	wp_schedule_event(time(), 'hourly', 'my_hourly_event');
//     }
// }

// add_action('my_hourly_event', 'do_this_hourly');

// function do_this_hourly() {
// 	// do something every hour
// }
