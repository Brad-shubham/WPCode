<?php

session_start();

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       www.rubicotech.com
 * @since      1.0.0
 *
 * @package    Spire_Page_Management
 * @subpackage Spire_Page_Management/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Spire_Page_Management
 * @subpackage Spire_Page_Management/admin
 * @author     RubicoTech
 */
class Spire_Page_Settings_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}


	public function setup_plugin_options_menu()
	{

		$menu = add_menu_page(
			__('Spire Pages', $this->plugin_name),
			__('Pages', $this->plugin_name),
			'edit_pages',
			'spire_page_settings',
			array($this, 'render_settings_page_content'),
			'dashicons-admin-page',
			20
		);
		add_submenu_page(
			'spire_page_settings',
			__('All Pages', $this->plugin_name),
			__('All Pages', $this->plugin_name),
			'edit_pages',
			'spire_page_settings',
		);
		add_submenu_page(
			'spire_page_settings',
			__('Add New', $this->plugin_name),
			__('Add New', $this->plugin_name),
			'edit_pages',
			'admin.php?page=spire_page_settings&action=add_new',
		);
		add_submenu_page(
			'spire_page_settings',
			__('Default Pages', $this->plugin_name),
			__('Default Pages', $this->plugin_name),
			'edit_pages',
			'edit.php?post_type=page',
		);
		remove_menu_page('edit.php?post_type=page');

		add_action('admin_print_styles-' . $menu, array($this, 'admin_custom_css'), 10, 2);
		add_action('admin_print_scripts-' . $menu, array($this, 'admin_custom_js'), 10, 2);
	}

	public function admin_custom_css()
	{
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/spire-page-management-admin.css', array(), $this->version, 'all');
		wp_enqueue_style($this->plugin_name . '-uikit', 'https://cdn.jsdelivr.net/npm/uikit@3.11.1/dist/css/uikit.min.css', array(), $this->version, 'all');
		wp_enqueue_style($this->plugin_name . '-select', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), $this->version, 'all');
	}

	public function admin_custom_js()
	{

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/spire-page-management-admin.js', array('jquery'), $this->version, false);
		wp_localize_script($this->plugin_name, 'admin_js_obj', array('ajax_url' => admin_url('admin-ajax.php')));
		wp_enqueue_script($this->plugin_name . '-uikit', 'https://cdn.jsdelivr.net/npm/uikit@3.11.1/dist/js/uikit.min.js', array('jquery'), $this->version, false);
		wp_enqueue_script($this->plugin_name . '-uiicon', 'https://cdn.jsdelivr.net/npm/uikit@3.11.1/dist/js/uikit-icons.min.js', array('jquery'), $this->version, false);
		wp_enqueue_script($this->plugin_name . '-iconify', 'https://code.iconify.design/2/2.1.2/iconify.min.js', array('jquery'), $this->version, false);
		wp_enqueue_script($this->plugin_name . '-select', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), $this->version, false);
		wp_enqueue_script($this->plugin_name . '-validation', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.min.js', array('jquery'), $this->version, false);
	}

	public function render_settings_page_content(){?>
		<div class="wrap">
			<div id="app">
				<main class="main" role="main">
					<header>
						<h1><?php _e('Pages', $this->plugin_name); ?></h1>
						<p class="subtitle"><?php _e('Create, edit, and organize the pages on your site.', $this->plugin_name); ?> <a href="#"><?php _e('Learn more.',$this->plugin_name);?></a></p>
						<a id="trash_page_link" href="/wp-admin/edit.php?post_status=trash&post_type=page" target="_blank"><?php _e('View Trash',$this->plugin_name);?></a>
					</header>
					<div id="pages-row">
						<?php
						require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/spire-page-management-admin-menus.php';   // calling the menu part of the page
						require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/spire-page-management-admin-page-lists.php'; // calling the page list part of the page
						?>
					</div>
				</main>
			</div>
		</div>
		<?php
	}
	/** 
	 * 
	 * function to create menus on plugin initialization
	 * 
	 */
	public function menus_creation()
	{
		$menus = wp_get_nav_menus();
		// condition to check if menus are already created
		if(count($menus) > 0){
			foreach ($menus as $menu) {
				if ($menu->name != "Main Navigation") {
					wp_create_nav_menu('Main Navigation');
				}
				if ($menu->name != "Utility Navigation") {
					wp_create_nav_menu('Utility Navigation');
				}
				if ($menu->name != "Other Pages") {
					wp_create_nav_menu('Other Pages');
				}
			}
		}
		else{
			wp_create_nav_menu('Main Navigation');
			wp_create_nav_menu('Utility Navigation');
			wp_create_nav_menu('Other Pages');
		}
		
	}
	
	public function buildTreeMenu( $elements, $parentId = 0 ){
		$branch = array();
		foreach ( $elements as $element ){
			$is_header = get_post_meta($element->ID, '_menu_item_header', true);
			$is_trashed = get_post_status($element->object_id);
			$is_hide = get_post_meta($element->ID, '_menu_item_hide', true);
			if ( $element->menu_item_parent == $parentId ){
				$children = $this->buildTreeMenu( $elements, $element->ID );
				$element->has_submenu = false;
				$element->is_header = $is_header;
				$element->is_trashed = $is_trashed;
				$element->is_hide = $is_hide;
				$element->type = $element->object;
				$element->child = array();
				if ( $children ){
					$element->child = $children;
					$element->has_submenu = true;
				}
				$branch[$element->ID] = $element;
				unset( $element );
			}
		}
		return $branch;
	}

	public function wp_get_menu_array($current_menu) {
		$menu_items = wp_get_nav_menu_items( $current_menu );
		$menus = $this->buildTreeMenu($menu_items);
		return $menus;
	}

	/** 
	 * 
	 * function to list custom post types
	 * 
	 */
	public function spire_pm_list_custom_posts()
	{
		extract($_POST);
		$args_stories = array(
			'post_type' => $post_type,
		);
		$loop_stories = new WP_Query($args_stories);
		$options .= "";
		while ($loop_stories->have_posts()) : $loop_stories->the_post();
			$title = get_the_title();
			$post_id = get_the_ID();
			$options .= "<option value='$post_id'>" . $title . "</option>";
		endwhile;
		wp_send_json_success($options);
	}

	/**
	 * 
	 * function to save pages and links
	 */
	public function spire_pm_save_page()
	{
		if (current_user_can('manage_options')) {
			extract($_POST);
			$menu_header_hide_check = get_post_meta($item_id, '_menu_item_hide', true);
			$menu_header_object_id = get_post_meta($item_id, '_menu_item_object_id', true);
			if ($select_parent_menu != NULL) {
				$menu_id = $select_parent_menu;
			}
			// setting error message if required
			$msg_error = new WP_Error();
			if (!$page_title || $page_title == '') {
				$msg_error->add('empty', __("Page Title is missing!"));
			}
			if ($msg_error->get_error_message()) {
				wp_send_json_error($msg_error->get_error_message());
			}
			// if page type link is selected
			if ($link_url) {
				$link_args = array(
					'menu-item-title' => $page_title,
					'menu-item-url' => $link_url,
					'menu-item-status' => 'publish',
					'menu-item-type' => 'custom',
				);
				if ($menu_id_to_update) {
					$menu_item_id = wp_update_nav_menu_item($menu_id, $menu_id_to_update, $link_args);
				} else {
					$menu_item_id = wp_update_nav_menu_item($menu_id, 0, $link_args);
				}
				update_post_meta($menu_item_id, '_menu_item_header', $menu_header);
				if($menu_header_hide_check == 1) {
					update_post_meta($menu_item_id, '_menu_item_hide', true);
				}
				if($item_id){
					wp_update_post(
						array(
							'ID' => $menu_item_id, 
							'post_parent' => $menu_header_object_id
						)
					);
				}
			}
			// if page type list is selected
			else if ($custom_post_link) {
				$custom_post_link_args = array(
					'menu-item-title' => $page_title,
					'menu-item-url' => $custom_post_link,
					'menu-item-status' => 'publish',
					'menu-item-type' => 'custom',
				);
				if ($menu_id_to_update) {
					$menu_item_id = wp_update_nav_menu_item($menu_id, $menu_id_to_update, $custom_post_link_args);
				} else {
					$menu_item_id = wp_update_nav_menu_item($menu_id, 0, $custom_post_link_args);
				}
				update_post_meta($menu_item_id, '_menu_item_header', $menu_header);
				update_post_meta($menu_item_id, '_menu_item_post_type', $custom_post_label);
				if($menu_header_hide_check == 1) {
					update_post_meta($menu_item_id, '_menu_item_hide', true);
				}
				if($item_id){
					wp_update_post(
						array(
							'ID' => $menu_item_id, 
							'post_parent' => $menu_header_object_id
						)
					);
				}
			}

			// if page type page is selected
			else {
				$new_page_args = array(
					'post_title'    => $page_title,
					'post_status'   => 'draft',
					'post_type'		=> 'page',
					'post_parent' => $menu_header_object_id,
				);
				$new_page_id = wp_insert_post($new_page_args);
				$menu_item_id = wp_update_nav_menu_item($menu_id, 0, array(
					'menu-item-title' => $page_title,
					'menu-item-object-id' => $new_page_id,
					'menu-item-object' => 'page',
					'menu-item-status' => 'publish',
					'menu-item-type' => 'post_type',
				));
				update_post_meta($menu_item_id, '_menu_item_header', $menu_header);
				if($menu_header_hide_check == 1) {
					update_post_meta($menu_item_id, '_menu_item_hide', true);
				}
			}
			if (!empty($item_id)) {
				update_post_meta($menu_item_id, '_menu_item_menu_item_parent', $item_id);
				if($menu_header_hide_check == 1) {
					update_post_meta($menu_item_id, '_menu_item_hide', true);
				}
			}
			$sitemap_menu_header = '';
			if($activeItemId == "undefined"){
				$activeItemId = "";
			}
            if($menu_header == 1) {
                ob_start();
                echo $this->folder_list_sitemap($activeItemId, $menu_id);
                $sitemap_menu_header = ob_get_contents();
                ob_end_clean();
            }
            ob_start();
            echo $this->admin_menu_lists();
            wp_reset_postdata();
            $menu_lists = ob_get_contents();
            ob_end_clean();
            wp_send_json_success(array('data' => 'Page successfully saved!', 'header_check' => $menu_header, 'menu_lists' => $menu_lists, 'sitemap' => $sitemap_menu_header));
		} else {
			wp_send_json_error(__("You don't have persmission to make any changes!", $this->plugin_name));
		}
	}
	/**
	 * 
	 * function to send the data to the pop up
	 * 
	 */
	public function spire_pm_pop_up_to_edit_link()
	{
		extract($_POST);
		$return = array();
		$link_title = html_entity_decode(get_the_title($object_id));
		$link_url = get_post_meta($object_id, '_menu_item_url', true);
		$is_header = get_post_meta($object_id, '_menu_item_header', true);
		$args_for_menu_children = array(
			'post_type' => 'nav_menu_item',
			'meta_query' => array(
				array(
					'key'     => '_menu_item_menu_item_parent',
					'value'   => array($object_id),
					'compare' => 'IN',
				),
			),
		);
		$query_for_menu_children = new WP_Query($args_for_menu_children);
		$pages_under_menu_item = $query_for_menu_children->posts;
		$is_parent = count($pages_under_menu_item);
		$return = array("link_title" => $link_title, "link_url" => $link_url, "menu_header" => $is_header, "menu_parent" => $is_parent);
		wp_send_json_success(__($return, $this->plugin_name));  //Return as array.
	}
	

	/**
	 * 
	 * function for duplicate functionality
	 * 
	 */
	public function spire_pm_duplicate_option()
	{
		extract($_POST);
		$menu_id = $active_menu_id;
		$page_title = get_the_title($page_id_to_duplicate);
		$page_content = get_the_content('', '', $page_id_to_duplicate);
		$page_parent = wp_get_post_parent_id($page_id_to_duplicate);
		$menu_header = get_post_meta($page_id_to_duplicate, '_menu_item_header');
		$page_args = array(
			'post_title'    => $page_title . "-copy",
			'post_content' => $page_content,
			'post_status'   => 'draft',
			'post_type'		=> 'page',
			'post_parent' => $page_parent,
		);
		$duplicated_page_id = wp_insert_post($page_args);
		$menu_args = array(
			'menu-item-title' => $page_title . "-copy",
			'menu-item-object-id' => $duplicated_page_id,
			'menu-item-object' => 'page',
			'menu-item-status' => 'publish',
			'menu-item-type' => 'post_type',
		);
		if ($active_menu_item_id) {
			$menu_args['menu-item-parent-id'] = $active_menu_item_id;
		}
		$duplicated_menu_item_id = wp_update_nav_menu_item($menu_id, 0, $menu_args);
		if ($menu_header) {
			update_post_meta($duplicated_menu_item_id, '_menu_item_header', $menu_header);
		}
		$return = array("Page" => $duplicated_page_id, "Menu" => $duplicated_menu_item_id);
		ob_start();
		echo $this->admin_menu_lists();
		$admin_menu_lists = ob_get_contents();
		ob_end_clean();
		wp_reset_postdata();
		wp_send_json_success(array('menu_lists'=>$admin_menu_lists));
	}

	/**
	 *
	 * 
	 * function to hide the menu
	 *  
	 */
	public function spire_pm_hide_page()
	{
		if (current_user_can('manage_options')) {
			extract($_POST);
			ob_start();
			$main_navigation_item = wp_get_nav_menu_items($nav_id);
			$object_id_of_hide_page = get_post_meta($id_for_hide_page, '_menu_item_object_id', true);
			$childrens = $this->get_nav_menu_item_children($object_id_of_hide_page, $main_navigation_item);
			if($page_hide_unhide_action == "hide"){
				if(count($childrens) > 0){
					foreach($childrens as $children){
						update_post_meta($children->ID, '_menu_item_hide', true);
					}
				}
				update_post_meta($id_for_hide_page, '_menu_item_hide', true);
			}
			else{
				if(count($childrens) > 0){
					foreach($childrens as $children){
						update_post_meta($children->ID, '_menu_item_hide', false);
					}
				}
				update_post_meta($id_for_hide_page, '_menu_item_hide', false);
			}
			$icon = '<span class="iconify" data-icon="dashicons:hidden"></span>';
			echo $this->admin_menu_lists();
			$admin_menu_lists = ob_get_contents();
			ob_end_clean();
			wp_reset_postdata();
			wp_send_json_success(array('data' => $admin_menu_lists, 'icon' => $icon, 'hide_unhide_type' => $page_hide_unhide_action));
		} else {
			wp_send_json_error(__("You don't have persmission to make any changes!", $this->plugin_name));
		}
	}


	/******** Function to trash page *********/

	public function spire_pm_trash_page()
	{
		if (current_user_can('manage_options')) {
			extract($_POST);
			ob_start();
			$main_navigation_item = wp_get_nav_menu_items($active_menu_id);
			// print_r($main_navigation_item);die();
			$childrens = $this->get_nav_menu_item_children($page_obj_id, $main_navigation_item);
			// print_r($childrens);die();
			if(count($childrens) > 0){
				foreach($childrens as $children){
					wp_delete_post($children->object_id);
				}
			}
			wp_delete_post($page_obj_id);
			echo $this->folder_list_sitemap($item_id, $active_menu_id);
			$new_menu_item_sitemap = ob_get_contents();
			ob_end_clean();
			wp_reset_postdata();
			wp_send_json_success(array('data'=>$new_menu_item_sitemap));
		} else {
			wp_send_json_error(__("You don't have persmission to make any changes!", $this->plugin_name));
		}
	}

	/******** Function to untrash page *********/

	public function spire_pm_untrash_page($post_id)
	{
		global $wpdb;
		$results = $wpdb->get_results("select post_id from $wpdb->postmeta where meta_value = '".$post_id."'", ARRAY_A );
		$menu_name_for_restore_page = get_the_terms($results[0]['post_id'], 'nav_menu');
		$main_navigation_item = wp_get_nav_menu_items($menu_name_for_restore_page[0]->term_id);
		$childrens = $this->get_nav_menu_item_children($post_id, $main_navigation_item);
		// print_r($childrens);die();
		foreach($main_navigation_item as $item){
			$item_object_id = get_post_meta($item->ID, '_menu_item_object_id', true);
			if($item_object_id == $post_id){
				$parent_of_item = get_post_meta($item->ID, '_menu_item_menu_item_parent', true);
				$paren_object_id_of_item = get_post_meta($parent_of_item, '_menu_item_object_id', true);
				$parent_menu_id = $item->ID;
			}
		}
		if(count($childrens) == 0){
			if((get_post_status($paren_object_id_of_item) == 'trash')){
				update_post_meta($parent_menu_id, '_menu_item_menu_item_parent', '0');
				wp_update_post(array(
					'ID'    =>  $parent_menu_id,
					'post_parent'   => 0,
				));
				wp_update_post(array(
					'ID'    =>  $post_id,
					'post_parent'   => 0,
				));
			}
		}
		else{
			if((get_post_status($paren_object_id_of_item) == 'trash')){
				update_post_meta($parent_menu_id, '_menu_item_menu_item_parent', '0');
				wp_update_post(array(
					'ID'    =>  $parent_menu_id,
					'post_parent'   => 0,
				));
				wp_update_post(array(
					'ID'    =>  $post_id,
					'post_parent'   => 0,
				));
			}
		}
		foreach($childrens as $children){
			if((get_post_status($paren_object_id_of_item) == 'trash')){
				update_post_meta($parent_menu_id, '_menu_item_menu_item_parent', '0');
			}
			wp_update_post(array(
				'ID'    =>  $children->object_id,
				'post_status'   =>  'draft'
			));
		}
	}


	/******** Function to drag/drop page to make the menu header *********/

	public function spire_pm_drag_drop_menu_header(){
		if (current_user_can('manage_options')){
			extract($_POST);
			ob_start();
			$dragged_page_object_id = get_post_meta($dragged_page_id, '_menu_item_object_id', true);
			$dragged_menu_object_id = get_post_meta($dragged_menu_id, '_menu_item_object_id', true);
			$main_navigation_item = wp_get_nav_menu_items($dragged_main_menu_id);
			$childrens = $this->get_nav_menu_item_children($dragged_page_object_id, $main_navigation_item);
			$count_for_header = 0;
			if(get_post_meta($dragged_page_id, '_menu_item_header', true) == 1){
				$count_for_header = 1;
			}
			foreach($childrens as $children){
				if(get_post_meta($children->ID, '_menu_item_header', true) == 1){
					$count_for_header++;
				}
			}
			if($menu_level == 3 && $count_for_header > 0){
				$response_msg = "Can not add menu header at this level";
				wp_send_json_success(array('status'=>$response_msg));
			}
			elseif($menu_level == 2 && $count_for_header > 1){
				$response_msg = "Can not add menu header at this level";
				wp_send_json_success(array('status'=>$response_msg));
			}
			else{
				if($menu_id_for_dragged_page != $dragged_menu_id && $dragged_menu_id != $dragged_page_id){
					if($dragged_menu_id != $dragged_main_menu_id){
						update_post_meta($dragged_page_id, '_menu_item_menu_item_parent', $dragged_menu_id);
						if(get_post_meta($dragged_menu_id, '_menu_item_hide', true)){
							update_post_meta($dragged_page_id, '_menu_item_hide', true);
						}
					}
					else{
						update_post_meta($dragged_page_id, '_menu_item_menu_item_parent', '0');
						if(get_post_meta($dragged_menu_id, '_menu_item_hide', true)){
							update_post_meta($dragged_page_id, '_menu_item_hide', true);
						}
					}
					wp_update_post(array(
						'ID'    =>  $dragged_page_object_id,
						'post_parent'   => $dragged_menu_object_id,
					));
					wp_update_post(array(
						'ID'    =>  $dragged_page_id,
						'post_parent'   => $dragged_menu_object_id,
					));
				}

				if($menu_header){
					ob_start();
					echo $this->folder_list_sitemap($item_id, $dragged_main_menu_id);
					$new_menu_item_sitemap = ob_get_contents();
					ob_end_clean();
				} 
				ob_start();
				echo $this->admin_menu_lists();
				$admin_menu_lists = ob_get_contents();
				ob_end_clean();
				wp_reset_postdata();
				wp_send_json_success(array('sitemap'=>$new_menu_item_sitemap, 'menu_lists'=>$admin_menu_lists));
			}
		}
		else {
			wp_send_json_error(__("You don't have persmission to make any changes!", $this->plugin_name));
		}
	}

    //  Function to fetch the children menu

	public function get_nav_menu_item_children( $parent_id, $nav_menu_items, $depth = true ) {
		$nav_menu_item_list = array();
		foreach ( (array) $nav_menu_items as $nav_menu_item ) {
			if ( $nav_menu_item->post_parent == $parent_id ) {
				$nav_menu_item_list[] = $nav_menu_item;
				if ( $depth ) {
					if ( $children = $this->get_nav_menu_item_children( $nav_menu_item->object_id, $nav_menu_items ) )
					$nav_menu_item_list = array_merge( $nav_menu_item_list, $children );
				}
			}
		}
		return $nav_menu_item_list;
	}

	public function spire_pm_change_menu_order(){
        extract($_POST);
        $menu_orders = $_POST['data_ordering'];
        foreach ($menu_orders as $menu_id => $menu_order) {
            $args = array(
                'ID'           => $menu_id,
                'menu_order'   => $menu_order,
            );
            wp_update_post($args);
        }
        ob_start();
        echo $this->folder_list_sitemap($item_id, $nav_id);
        $sitemap_menu_header = ob_get_contents();
        ob_end_clean();
        wp_send_json_success(array('data' => 'Menus sorted', 'sitemap' => $sitemap_menu_header));
    }

	public function folder_list_sitemap($item_id = null, $nav_id = null){
        $menus = wp_get_nav_menus();
        $x = 1;
        foreach($menus as $menu_name) {
            $count_menus = $menu_name->count; ?>
            <div class="folder-group">
                <a href="#" title="<?php echo $menu_name->name; ?>" id="<?php echo $menu_name->term_id; ?>" data-type="menu" class="<?php 
				if($x == 1 && $item_id == null && $nav_id == null){
					echo 'active';
				}
				else{
					if($nav_id == $menu_name->term_id && $item_id == null){
						echo 'active';
					}
				}
				?> main-menu" menu-level="1" data-nav-id="<?php echo $menu_name->term_id; ?>" data-perma="<?php echo $menu_name->name;?>"><span class="folder<?php if( $x == 1){ echo '-open'; } ?>"></span><?php echo $menu_name->name; ?></a>
                <?php
                    $submenus = $this->wp_get_menu_array($menu_name->name);
                    //first level menus
                    foreach($submenus as $submenu_items) {

                        if(($submenu_items->is_header == true || $submenu_items->is_header == 'on') && $submenu_items->is_trashed != "trash"):?>
                            <a href="#" title="<?php echo $submenu_items->title; ?>" id="<?php echo $submenu_items->ID; ?>" data-type="items" menu-level="2" data-nav-id="<?php echo $menu_name->term_id; ?>" data-item-id="<?php echo $submenu_items->ID; ?>" class="<?php if($submenu_items->is_hide){echo "not_on_menu";} echo ($item_id == $submenu_items->ID) ? ' active' : '';?>" data-perma="<?php echo $menu_name->name.' / '.$submenu_items->title;?>">
                                <span class="line-l"></span>
                                <span class="folder"></span>
                                <?php echo $submenu_items->title; ?>
                            </a>
                        <?php endif;?>
                        <?php 
                        if(count($submenu_items->child) >= 1){
                            //second level menus
                            $sub_sub_count = 0;
                            foreach($submenu_items->child as $sub_sub_menu){
                                if(($sub_sub_menu->is_header == true || $sub_sub_menu->is_header == 'on') && $sub_sub_menu->is_trashed != "trash"){ 
                                    $sub_sub_count++;
                                    $sublineClass = "";
                                    if(count($submenu_items->child) >= 1){
                                        if($sub_sub_count == count($submenu_items->child)){
                                            $sublineClass = "line-l";
                                        } else {
                                            $sublineClass = "line-t";
                                        }
                                    }?>
                                    <a href="#" title="<?php echo $sub_sub_menu->title; ?>" id="<?php echo $sub_sub_menu->ID; ?>" menu-level="3" data-type="items" class="<?php if($sub_sub_menu->is_hide){echo "not_on_menu";} echo ($item_id == $sub_sub_menu->ID) ? 'active' : '';?>" data-nav-name = "<?php echo $menu_name->name;?>" data-nav-id="<?php echo $menu_name->term_id; ?>" data-item-id="<?php echo $sub_sub_menu->ID; ?>" data-perma="<?php echo $menu_name->name.' / '.$submenu_items->title.' / '.$sub_sub_menu->title;?>" data-item-id-last-level="true">
                                        <span class="<?php echo $sublineClass;?> level-2"></span>
                                        <span class="folder"></span>
                                        <?php echo $sub_sub_menu->title; ?>
                                    </a>
                                <?php 
                                }
                                //third level menus
                                if(count($sub_sub_menu->child) >= 1){
                                    $sub_sub_sub_count = 0;
                                    foreach($sub_sub_menu->child as $sub_sub_sub_menu){
                                        if(($sub_sub_sub_menu->is_header == true || $sub_sub_sub_menu->is_header == 'on') && $sub_sub_sub_menu->is_trashed != "trash"){
                                            $sub_sub_sub_count++;
                                            $subsublineClass = "";
                                            if(count($submenu_items->child) >= 1){
                                                if($sub_sub_sub_count == count($sub_sub_menu->child)){
                                                    $subsublineClass = "line-l";
                                                } else {
                                                    $subsublineClass = "line-t";
                                                }
                                            }?>
                                            <a href="#" title="<?php echo $sub_sub_sub_menu->title; ?>" id="<?php echo $sub_sub_sub_menu->ID; ?>" class="<?php if($sub_sub_sub_menu->is_hide){echo "not_on_menu";} echo ($item_id == $sub_sub_sub_menu->ID) ? 'active' : '';?>" data-nav-name = "<?php echo $menu_name->name;?>" data-type="items" data-nav-id="<?php echo $menu_name->term_id; ?>" data-item-id="<?php echo $sub_sub_sub_menu->ID; ?>" data-perma="<?php echo $menu_name->name.'/'.$submenu_items->title.' / '.$sub_sub_menu->title.' / '.$sub_sub_sub_menu->title;?>">
                                                <span class="<?php echo $subsublineClass;?> level-3"></span>
                                                <span class="folder"></span>
                                                <?php echo $sub_sub_sub_menu->title; ?>
                                            </a>    
                                    <?php }
                                    }
                                }
                            }
                        }
                    }
                ?>
            </div>
            <?php
            $x++;
        }
    }
	
	public function admin_menu_lists(){
        $menus = wp_get_nav_menus();?>
        <div uk-sortable="handle: .drag-handle" id="active-submenu" class="uk-sortable uk-list uk-list-space" data-uk-sortable="{handleClass:'uk-panel'}">
            <?php
            foreach($menus as $menu):
            $pages_inside_menus = wp_get_nav_menu_items($menu->name);
            foreach ($pages_inside_menus as $page) {
                $object_id = get_post_meta($page->ID, '_menu_item_object_id', true);
                $menu_item_post_type = get_post_meta($page->ID, '_menu_item_post_type', true);
                $edit_link = get_edit_post_link($object_id);
                $menu_order = $page->menu_order;
                $link_view_url = get_post_meta($object_id, '_menu_item_url', true);
                $page_status = get_post_status($object_id);
                $parent_id = $page->menu_item_parent;
                if ($page_status != 'trash') {
                    $is_hide = get_post_meta($page->ID, '_menu_item_hide', true);
                    $is_header = get_post_meta($page->ID, '_menu_item_header', true); ?>
                    <div draggable="true" id="<?php echo $page->ID ?>" class="element-to-drag card page <?php echo ($menu->term_id) ? 'nav_'.$menu->term_id : '';?> <?php echo ($parent_id) ? 'parent_'.$parent_id : 'parent_0';?> <?php if ($is_header) {
                                                echo 'menu_header ';
                                            }
                                            if ($is_hide) {
                                                echo 'off-menu ';
                                            }
                                            echo strtolower($page->type_label); ?>" data-menu-item-id="<?php echo $page->ID ?>" data-menu-order="<?php echo $menu_order;?>">
                        <div draggable class="drag-handle"></div>
						<div class="card-padding">
                            <div class="main">
                                <input type="hidden" data-menu-order="<?php echo $menu_order; ?>" id="menu-order" />
                                <a href="<?php echo $page->url; ?>" class="title"><?php echo $page->title; ?></a>
                                <div class="card-info">
                                    <div class="time-status" title="<?php echo $page->post_modified ?>">
                                        <?php
                                        $date = date('F d ', strtotime($page->post_modified));
                                        $time = date('h:i A', strtotime($page->post_modified));
                                        $dateTime = $date . ', at ' . $time;
                                        ?>
                                        <a class="link" href="#">
                                            <span class="time"><span class="iconify" data-icon="dashicons:clock" data-width="12" data-height="12"></span><time class="time-text" datetime="<?php echo $page->post_modified ?>"><?php echo $dateTime; ?></time></span>
                                        </a>
                                    </div>
                                    <?php if ($page->type_label == 'Custom Link') : 
                                        if($menu_item_post_type) : ?>
                                        <span class="badge">
                                            <span class="iconify" data-icon="dashicons-admin-post" data-width="12" data-height="12"></span>
                                            <span class="badge-text"><?php echo $menu_item_post_type; ?></span>
                                        </span>
                                        <?php else : ?>
                                            <span class="badge">
                                            <span class="iconify" data-icon="dashicons-admin-links" data-width="12" data-height="12"></span>
                                            <span class="badge-text">Link</span>
                                        </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php $is_header = get_post_meta($page->ID, '_menu_item_header', true);
                                    if ($is_header) : ?>
                                        <span class="badge">
                                            <span class="iconify" data-icon="dashicons:open-folder" data-width="12" data-height="12"></span>
                                            <span class="badge-text">Menu Header</span>
                                        </span>
                                    <?php endif; ?>
                                    <?php $is_draft = get_post_status($object_id);
                                    if ($is_draft == "draft") { ?>
                                        <span class="badge">
                                            <span class="iconify" data-icon="dashicons-post-status" data-width="12" data-height="12"></span>
                                            <span class="badge-text">Draft</span>
                                        </span>
                                    <?php } else { ?>
                                        <span class="badge">
                                            <span class="iconify" data-icon="dashicons-yes" data-width="12" data-height="12"></span>
                                            <span class="badge-text">Published</span>
                                        </span>
                                    <?php }
                                    ?>
                                    <?php if ($is_hide) : ?>
                                        <span class="badge not_on_menu">
                                            <span class="iconify" data-icon="dashicons:hidden" data-width="12" data-height="12"></span>
                                            <span class="badge-text">Not on Menu</span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="ellipsis-menu">
                                <button title="Toggle menu" type="button" class="button rotateH"><span class="iconify" data-icon="gridicons:ellipsis" data-width="24" data-height="24"></span></button>
                                <div class="page-menu uk-close" uk-dropdown="mode: click; offset: 10">
                                    <ul class="uk-nav uk-dropdown-nav">
                                        <?php if ($page->type_label != 'Custom Link') : ?>
                                            <li><a id="edit-page" data-id="<?php echo $object_id; ?>" href="<?php echo $edit_link; ?>"><span class="iconify" data-icon="dashicons:edit-large"></span> Edit page details</a></li>
                                        <?php else : ?>
											<?php if($menu_item_post_type) : ?>
                                            	<li><a href="" class="edit-link" data-menu_type="<?php echo $menu_item_post_type; ?>" data-id="<?php echo $object_id; ?>" uk-toggle><span class="iconify" data-icon="dashicons:edit-large"></span> Edit list details</a></li>
											<?php else : ?>
												<li><a href="" class="edit-link" data-menu_type="<?php echo $menu_item_post_type; ?>" data-id="<?php echo $object_id; ?>" uk-toggle><span class="iconify" data-icon="dashicons:edit-large"></span> Edit link details</a></li>
											<?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($page->type_label != 'Custom Link') : ?>
                                            <li><a href="<?php echo 'customize.php?url=' . urlencode($page->url) . '&autofocus[section]=builder' ?>"><span class="iconify" data-icon="dashicons:external"></span> Launch builder</a></li>
                                        <?php endif; ?>
                                        <?php $is_draft = get_post_status($object_id);
                                        if ($is_draft == "draft") { ?>
                                            <li><a href="<?php echo $page->url . '&preview=true' ?>"><span class="iconify" data-icon="dashicons:search"></span> Preview</a></li>
                                            <?php } else {
                                            if ($page->type_label != 'Custom Link') { ?>
                                                <li><a href="<?php echo $page->url; ?>"><span class="iconify" data-icon="dashicons:search"></span> View</a></li>
                                            <?php } else { ?>
                                                <li><a target="_blank" href="<?php echo $link_view_url; ?>"><span class="iconify" data-icon="dashicons:search"></span> View</a></li>
                                        <?php }
                                        } ?>
                                        <li>
                                        <?php if (!$is_header && $page->type_label != 'Custom Link') { ?>
                                            <a id="duplicate" data-pageid="<?php echo $object_id; ?>" data-menuid="<?php echo $page->ID; ?>" href="#"><span class="iconify" data-icon="dashicons:admin-page"></span> Duplicate</a></li>
                                        <?php } ?>
                                        <?php if ($is_hide) : ?>
                                            <li id="hide_unhide"><a href="#" class="hide_unhide_page" data-type="unhide" data-page-id="<?php echo $page->ID ?>"><span class="iconify" data-icon="dashicons:hidden"></span> Unhide from menu</a></li>
                                        <?php else : ?>
                                            <li id="hide_unhide"><a href="#" class="hide_unhide_page" data-type="hide" data-page-id="<?php echo $page->ID ?>"><span class="iconify" data-icon="dashicons:hidden"></span> Hide from menu</a></li>
                                        <?php endif; ?>
										<?php if ($page->type_label != 'Custom Link') : ?>
                                        <li><a href="#" class="trash_page" data-page-id="<?php echo $page->ID ?>" data-obj-page-id="<?php echo $object_id; ?>" ><span class="iconify" data-icon="dashicons:trash"></span> Trash</a></li>
										<?php else : ?>
											<li><a href="#" class="trash_page" data-page-id="<?php echo $page->ID ?>" data-obj-page-id="<?php echo $object_id; ?>" ><span class="iconify" data-icon="dashicons:trash"></span> Remove</a></li>
										<?php endif; ?>
									</ul>
                                </div>
                            </span>
                        </div>
                    </div>
                <?php
                }
            }
            endforeach;?>
        </div>
    <?php 
    }

}