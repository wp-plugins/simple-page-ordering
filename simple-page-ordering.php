<?php
/**
 Plugin Name: Simple Page Ordering
 Plugin URI: http://www.get10up.com/plugins/simple-page-ordering-wordpress/
 Description: Order your pages and hierarchical post types using drag and drop on the built in page list. Also adds a filter for items to show per page. For further instructions, open the "Help" tab on the Pages screen. 
 Version: 0.9.5
 Author: Jake Goldman (10up)
 Author URI: http://www.get10up.com

    Plugin: Copyright 2011 10up  (email : jake@get10up.com)

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

class simple_page_ordering
{
	function simple_page_ordering()
	{
		add_filter( 'edit_posts_per_page', array( $this, 'edit_posts_per_page' ), 10, 2 );
		add_action( 'wp_ajax_simple_page_ordering', array( $this, 'ajax_simple_page_ordering' ) );
	}
	
	function edit_posts_per_page( $per_page, $post_type )
	{		
		if ( !current_user_can('edit_others_pages') )								// check permission
			return $per_page;
		
		$post_type_object = get_post_type_object( $post_type );
		if ( !$post_type_object->hierarchical )										// only hierarchical post types apply
			return $per_page;
			
		add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );			// posts per page drop down UI
		wp_enqueue_script( 'simple-page-ordering', plugin_dir_url( __FILE__ ) . '/simple-page-ordering.js', array('jquery-ui-sortable'), '0.9.5', true );
		add_filter( 'contextual_help', array( $this, 'contextual_help' ) );
		
		if ( isset( $_GET['spo'] ) && is_numeric( $_GET['spo'] ) && ( $_GET['spo'] == -1 || ($_GET['spo']%10) == 0 ) ) :
		
			global $edit_per_page, $user_ID;
			
			$per_page = $_GET['spo'];
			
			if ( $per_page == -1 )
				$per_page = 99999;
			
			update_user_option( $user_ID, $edit_per_page, $per_page );
		
		endif;
		
		return $per_page;
	}
	
	function restrict_manage_posts()
	{
		global $per_page;
				
		$spo = isset($_GET['spo']) ? (int)$_GET['spo'] : $per_page;
	?>
		<select name="spo" style="width: 110px;">
			<option<?php selected( $spo, -1 ); ?> value="-1"><?php _e('Show all'); ?></option>
			<?php for( $i=10;$i<=100;$i+=10 ) : ?>
			<option<?php selected( $spo, $i ); ?> value="<?php echo $i; ?>"><?php echo $i; ?> <?php _e('per page'); ?></option>
			<?php endfor; ?>		
		</select>
	<?php
	}
	
	function contextual_help( $help )
	{
		return $help . '
			<p><strong>'. __( 'Simple Page Ordering' ) . '</strong></p>
			<p><a href="http://www.get10up.com/plugins/simple-page-ordering-wordpress/" target="_blank">Simple Page Ordering</a> is a plug-in by <a href="http://www.get10up.com" target="_blank">Jake Goldman (10up)</a>) that  allows you to order pages and other hierarchical post types with drag and drop.</p>
			<p>To reposition an item, simply drag and drop the row by "clicking and holding" it anywhere (outside of the links and form controls) and moving it to its new position.</p>
			<p>If you have a large number of pages, it may be helpful to adjust the new "items per page" filter located above the table and before the filter button.</p>
			<p>To keep things relatively simple, the current version only allows you to reposition items within their current tree / hierarchy (next to pages with the same parent). If you want to move an item into or out of a different part of the page tree, use the "quick edit" feature to change the parent.</p>  
		';
	}
	
	function ajax_simple_page_ordering()
	{
		// check permissions again and make sure we have what we need
		if ( !current_user_can('edit_others_pages') || empty($_POST['id']) || ( !isset($_POST['previd']) && !isset($_POST['nextid']) ) )
			die(-1);	
		
		// real post?
		if ( !$post = get_post( $_POST['id'] ) )
			die(-1);
		
		$previd = isset($_POST['previd']) ? $_POST['previd'] : false;
		$nextid = isset($_POST['nextid']) ? $_POST['nextid'] : false;
		$new_pos = array(); // store new positions for ajax
		
		if ( $previd ) 
		{	
			$siblings = get_posts(array( 'depth' => 1, 'numberposts' => -1, 'post_type' => $post->post_type, 'post_status' => 'publish,pending,draft,future,private', 'post_parent' => $post->post_parent, 'orderby' => 'menu_order', 'order' => 'ASC', 'exclude' => $post->ID )); // fetch all the siblings (relative ordering)
			
			foreach( $siblings as $sibling ) :
			
				// start updating menu orders
				if ( $sibling->ID == $previd ) {
					$menu_order = $sibling->menu_order + 1;
					wp_update_post(array( 'ID' => $post->ID, 'menu_order' => $menu_order ));  // update the actual moved post to 1 after prev
					$new_pos[$post->ID] = $menu_order;
					continue;
				}
				
				// nothing left to do - numbers already sufficiently padded!
				if ( isset($menu_order) && $menu_order < $sibling->menu_order )
					break; 
			
				// need to update this sibling's menu order too
				if ( isset($menu_order) ) {
					$menu_order++;
					wp_update_post(array( 'ID' => $sibling->ID, 'menu_order' => $menu_order ));  // update the actual moved post to 1 after prev	
					$new_pos[$sibling->ID] = $menu_order;
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
					$new_pos[$post->ID] = $menu_order;
					continue;
				}
				
				// nothing left to do - numbers already sufficiently padded!
				if ( isset($menu_order) && $menu_order > $sibling->menu_order )
					break; 
				
				// need to update this sibling's menu order too
				if ( isset($menu_order) ) {
					$menu_order--;
					wp_update_post(array( 'ID' => $sibling->ID, 'menu_order' => $menu_order ));  // update the actual moved post to 1 after prev	
					$new_pos[$sibling->ID] = $menu_order;
				}		
			
			endforeach;
			
		}
		
		// if the moved post has children, we need to refresh the page
		$children = get_posts(array( 'depth' => 1, 'numberposts' => 1, 'post_type' => $post->post_type, 'post_status' => 'publish,pending,draft,future,private', 'post_parent' => $post->ID )); // fetch all the siblings (relative ordering)
		if ( !empty($children) )
			die('children');
		
		die( json_encode($new_pos) );
	}
}

if ( is_admin() )
	$simple_page_ordering = new simple_page_ordering;