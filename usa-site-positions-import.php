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
 */

include 'plugin_log.php';
include 'delete_all_positions.php';
include 'wp_log.php';

// when the plugin is activated this will schedule the import function to occur automatically 
register_activation_hook(__FILE__, 'my_activation');

function my_activation() {
    if (! wp_next_scheduled ( 'import_hourly_event' )) {
	wp_schedule_event(time(), 'hourly', 'import_hourly_event');
    }
}

add_action('import_hourly_event', 'import_positions');
register_deactivation_hook(__FILE__, 'my_deactivation');

function my_deactivation() {
	wp_clear_scheduled_hook('import_hourly_event');
}

// the import function
function import_positions()
{	
 plugin_log('import job positions begun');
 $total_imp_qty = 0;

	//The US site created multiple feeds to separate position categories
	$feeds = array(
	'aviation-projects'				   	    	=> 'https://ethnos360.org/feeds/jobs-aviation',
	'business-and-administration'		    	=> 'https://ethnos360.org/feeds/jobs-business-and-administration',
	'communications'					        => 'https://ethnos360.org/feeds/jobs-communications',
	'construction' 					            => 'https://ethnos360.org/feeds/jobs-construction-and-maintenance',
	'teaching-elementary'				        => 'https://ethnos360.org/feeds/jobs-education-elementary-teaching',
	'teaching-secondary' 				        => 'https://ethnos360.org/feeds/jobs-education-secondary-teaching',
	'education-administration-and-staff'  		=> 'https://ethnos360.org/feeds/jobs-education-administration-and-staff',
	'other-education'					        => 'https://ethnos360.org/feeds/jobs-education-other',
	'evangelism-and-church-planting'       	    => 'https://ethnos360.org/feeds/jobs-evangelism-and-church-planting',
	'food-service'                         	    => 'https://ethnos360.org/feeds/jobs-food-service',
	'health-care' 					     	    => 'https://ethnos360.org/feeds/jobs-health-care',
	'hospitality' 					            => 'https://ethnos360.org/feeds/jobs-hospitality',
	'law' 							            => 'https://ethnos360.org/feeds/jobs-law',
	'technology'			                    => 'https://ethnos360.org/feeds/jobs-technology',
	'training' 				   	                => 'https://ethnos360.org/feeds/jobs-training',
	);
	
// start looping through each feed 
	foreach ($feeds as $feed_key => $feed_value) {	
		$feed = fetch_feed($feed_value);
		$post_cat = get_category_by_slug($feed_key);
        
		if( !is_wp_error($feed) && !empty($feed) ){
			delete_cat($post_cat,$feed_key);
			plugin_log('================================================== ' . $feed_key . ' =======================================================');
			$items = $feed->get_items();
			$cat_qty=0;		    
		
				foreach ( $items as $item ){
					
					//create loop variables 
					$item_date = $item->get_date('Y-m-d H:i:s');      
					
					$xmlns = 'http://sitestacker.com/Job';
				
					//Strip html tags and regex's 
					$strip_tags = array('&nbsp;', '<br/>', '</span>', '<span>', '<ul>', '</ul>', '<li>', '</li>', '<p>', '</p>', '<li style="list-style-type: none">'); 
					
					$purpose = $item->get_item_tags($xmlns, 'purpose')[0]['data']; 
					if ( ! empty($purpose)) {
						$striped_purpose =  str_replace($strip_tags, '', $purpose);
						}
					else {$striped_purpose = 'not listed'; }
				
					$content = $item->get_content(); 
					if ( !empty($content)) {
						$striped_content = str_replace($strip_tags, '', $content); 
						}
					else {$striped_content = 'not listed'; }	

					$degree = $item->get_item_tags($xmlns,'degree')[0]['data'];
					if ( !empty($degree)) {
						$striped_degree = str_replace('Ethnos360 Training', 'Ethnos Training', $degree); 
						}
					else {$striped_degree = ''; }

								
					$post = array(
						'post_content'  => $content, 
						'post_date'     => $item_date, 
						'post_title'    => $item->get_title(), 
						'post_status'   => 'publish', 
						'post_type'     => 'Position', 
						'post_category'  => array($post_cat->term_id),
						); 
					
				$meta = array(
					'position_region'           => $item->get_item_tags($xmlns,'region')[0]['data'], 
					'position_country'          => $item->get_item_tags($xmlns,'country')[0]['data'], 
					'position_location'         => $item->get_item_tags($xmlns,'location')[0]['data'], 
					'position_purpose'          => $striped_purpose, 
					'position_paid_position'    => '0', 
					'position_minimum_duration' => $item->get_item_tags($xmlns,'minimum_duration')[0]['data'], 
					'position_maximum_duration' => $item->get_item_tags($xmlns,'maximum_duration')[0]['data'], 
					'position_people_needed'    => $item->get_item_tags($xmlns,'people_needed')[0]['data'], 
					'position_person_needed'    => $item->get_item_tags($xmlns,'person_needed')[0]['data'], 
					'position_priority'         => $item->get_item_tags($xmlns,'priority')[0]['data'], 
					'position_date_needed'      => $item->get_item_tags($xmlns,'date_needed')[0]['data'], 
					'position_responsibilities' => $striped_content, 
					'position_degree'           => $striped_degree, 
					'position_skills'           => $item->get_item_tags($xmlns,'skill')[0]['data'], 
					'position_experience'       => $item->get_item_tags($xmlns,'experience')[0]['data'], 
					'position_comments'         => $item->get_item_tags($xmlns,'comment')[0]['data'],
					); 	
			
				$post_id = wp_insert_post($post); 

				foreach ($meta as $key => $value) {
					add_post_meta($post_id, $key, $meta[$key], true); 	
					}
				//log import progress and increment counters 
				plugin_log($feed_key . '# ' . $cat_qty . " " . $item->get_title() );	
				$total_imp_qty++;	
				$cat_qty++;		  			   			   			
				}
			}
			//Log error if cant get feed item
        else{
             $error_string = $result->get_error_message();
             plugin_log("error with feed value " . $feed. "wp error message - " . $error_string );
        }
	}
	plugin_log('Import finished. total positions imported - ' . $total_imp_qty );
	}
