<?php
/**
 Plugin Name: Simple Page Ordering
 Plugin URI: http://www.cmurrayconsulting.com/software/wordpress-page-order-plugin/
 Description: Order your pages and other hierarchical post types with drag and drop. Also adds a filter for items to show per page.
 Version: 0.8.4
 Author: Jacob M Goldman (C. Murray Consulting)
 Author URI: http://www.cmurrayconsulting.com

    Plugin: Copyright 2009 C. Murray Consulting  (email : jake@cmurrayconsulting.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * code to add posts per page filter
 */
 
add_filter( 'edit_posts_per_page', 'spo_edit_posts_per_page', 10, 2 );

function spo_edit_posts_per_page( $per_page, $post_type )
{		
	if ( !current_user_can('edit_others_pages') )								// check permission
		return;
	
	$post_type_object = get_post_type_object( $post_type );
	if ( !$post_type_object->hierarchical )										// only hierarchical post types apply
		return;
		
	add_action( 'restrict_manage_posts', 'spo_posts_per_page_filter' );			// posts per page drop down UI
	add_action( 'admin_print_styles', 'spo_admin_styles' );						// special styles (move cursor), spinner positioning
	wp_enqueue_script( 'spo-ordering', plugin_dir_url( __FILE__ ) . '/spo-ordering.js', array('jquery-ui-sortable'), '0.8.4', true );
	add_filter( 'contextual_help', 'spo_contextual_help' );
	
	if ( isset( $_GET['spo'] ) && is_numeric( $_GET['spo'] ) && ( $_GET['spo'] == -1 || ($_GET['spo']%10) == 0 ) ) :
	
		global $edit_per_page, $user_ID;
		
		$per_page = $_GET['spo'];
		
		if ( $per_page == -1 )
			$per_page = 99999;
		
		update_user_option( $user_ID, $edit_per_page, $per_page );
	
	endif;
	
	return $per_page;
}

function spo_posts_per_page_filter()
{
	global $per_page;
			
	$spo = isset($_GET['spo']) ? (int)$_GET['spo'] : $per_page;
?>
	<select name="spo" style="width: 100px;">
		<option<?php selected( $spo, -1 ); ?> value="-1"><?php _e('Show all'); ?></option>
		<?php for( $i=10;$i<=100;$i+=10 ) : ?>
		<option<?php selected( $spo, $i ); ?> value="<?php echo $i ?>"><?php echo $i; ?> <?php _e('per page'); ?></option>
		<?php endfor; ?>		
	</select>
<?php
} 

/** 
 * styling and help
 */

function spo_admin_styles() {
	echo '<style type="text/css">table.widefat tbody th, table.widefat tbody td { cursor: move; }</style>';
}

function spo_contextual_help( $help )
{
	return $help . '
		<p><strong>Simple Page Ordering</strong></p>
		
		<p><a href="http://www.cmurrayconsulting.com/software/wordpress-page-order-plugin/" target="_blank">Simple Page Ordering</a> is a plug-in by <a href="http://www.jakegoldman.net" target="_blank">Jake Goldman</a> (<a href="http://www.cmurrayconsulting.com/software/wordpress-page-order-plugin/" target="_blank">C. Murray Consulting</a>) that  allows you to order pages and other hierarchical post types with drag and drop.</p>
		
		<p>To reposition an item, simply drag and drop the row by "clicking and holding" it anywhere (outside of the links and form controls) and moving it to its new position.</p>
		
		<p>If you have a large number of pages, it may be helpful to adjust the new "items per page" filter located above the table and before the filter button.</p>
		
		<p>To keep things relatively simple, the current version only allows you to reposition items within their current tree / hierarchy (next to pages with the same parent). If you want to move an item into or out of a different part of the page tree, use the "quick edit" feature to change the parent.</p>  
	';
}

/**
 * actual ajax request for sorting pages
 */

add_action( 'wp_ajax_simple_page_ordering', 'spo_do_page_ordering' );

function spo_do_page_ordering()
{
	// check permissions again and make sure we have what we need
	if ( !current_user_can('edit_others_pages') || !isset($_POST['id']) || empty($_POST['id']) || ( !isset($_POST['previd']) && !isset($_POST['nextid']) ) )
		die(-1);	
	
	// real post?
	if ( !$post = get_post( $_POST['id'] ) )
		die(-1);
	
	$previd = isset($_POST['previd']) ? $_POST['previd'] : false;
	$nextid = isset($_POST['nextid']) ? $_POST['nextid'] : false;
	
	if ( $previd ) {
		
		$siblings = get_posts(array( 'depth' => 1, 'numberposts' => -1, 'post_type' => $post->post_type, 'post_status' => 'publish,pending,draft,future,private', 'post_parent' => $post->post_parent, 'orderby' => 'menu_order', 'order' => 'ASC', 'exclude' => $post->ID )); // fetch all the siblings (relative ordering)
		
		foreach( $siblings as $sibling ) :
		
			// start updating menu orders
			if ( $sibling->ID == $previd ) {
				$menu_order = $sibling->menu_order + 1;
				wp_update_post(array( 'ID' => $post->ID, 'menu_order' => $menu_order ));  // update the actual moved post to 1 after prev
				continue;
			}
			
			// nothing left to do - numbers already sufficiently padded!
			if ( isset($menu_order) && $menu_order < $sibling->menu_order )
				break; 
		
			// need to update this sibling's menu order too
			if ( isset($menu_order) ) {
				$menu_order++;
				wp_update_post(array( 'ID' => $sibling->ID, 'menu_order' => $menu_order ));  // update the actual moved post to 1 after prev	
			}		
		
		endforeach;
			
	}
	
	if ( !isset($menu_order) && $nextid ) {
		
		$siblings = get_posts(array( 'depth' => 1, 'numberposts' => -1, 'post_type' => $post->post_type, 'post_status' => 'publish,pending,draft,future,private', 'post_parent' => $post->post_parent, 'orderby' => 'menu_order', 'order' => 'DESC', 'exclude' => $post->ID )); // fetch all the siblings (relative ordering)
		
		foreach( $siblings as $sibling ) :
			
			// start updating menu orders
			if ( $sibling->ID == $nextid ) {
				$menu_order = $sibling->menu_order - 1;
				wp_update_post(array( 'ID' => $post->ID, 'menu_order' => $menu_order ));  // update the actual moved post to 1 after prev
				continue;
			}
			
			// nothing left to do - numbers already sufficiently padded!
			if ( isset($menu_order) && $menu_order > $sibling->menu_order )
				break; 
			
			// need to update this sibling's menu order too
			if ( isset($menu_order) ) {
				$menu_order--;
				wp_update_post(array( 'ID' => $sibling->ID, 'menu_order' => $menu_order ));  // update the actual moved post to 1 after prev	
			}		
		
		endforeach;
		
	}
	
	// if the moved post has children, we need to refresh the page
	$children = get_posts(array( 'depth' => 1, 'numberposts' => 1, 'post_type' => $post->post_type, 'post_status' => 'publish,pending,draft,future,private', 'post_parent' => $post->ID )); // fetch all the siblings (relative ordering)
	if ( !empty($children) )
		die('children');
	
	die();
}