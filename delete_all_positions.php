<?php
// short script to quickly delete all custom post's typed positions 
function delete_all_positions() {
  $deleted_qty = 0;
  $positions= array (
    'post_type' => 'Position',
    'nopaging' => true
  );
  $query = new WP_Query ($positions);
  while ($query->have_posts ()) {
    $query->the_post ();
    $id = get_the_ID ();
    wp_delete_post ($id, true);
    $deleted_qty++;
  }
  wp_reset_postdata ();
  update_option('import_switch','1');
  plugin_log('*****' . $deleted_qty . ' positions deleted ' . '*****');

}

function delete_cat($cat_id,$cat_name){
   $deleted_qty = 0;
  $positions= array (
    'post_type' => 'Position',
    'category_name'  => $cat_name,
    'nopaging' => true
  );
  $query = new WP_Query ($positions);
  while ($query->have_posts ()) {
    $query->the_post ();
    $id = get_the_ID ();
    wp_delete_post ($id, true);
    $deleted_qty++;
  }
  wp_reset_postdata ();
  plugin_log($deleted_qty . ' positions deleted from category ' . $cat_name );
  

}
