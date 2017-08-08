<?php
// short script to quickly delete all custom post's typed positions 
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
  plugin_log('positions deleted');

}
