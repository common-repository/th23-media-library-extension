<?php
/*
th23 Media Library Extension
Admin area

Copyright 2014-2016, Thorsten Hartmann (th23)
http://th23.net/
*/

class th23_media_library_extension_admin extends th23_media_library_extension_pro {

	function __construct() {

		parent::__construct();

		// Setup basics (additions for backend)
		$this->plugin['support_url'] = 'https://wordpress.org/support/plugin/th23-media-library-extension';

		// Modify plugin overview page
		add_filter('plugin_row_meta', array(&$this, 'contact_link'), 10, 2);

		// Add link to edit posts/ pages overview directing to filtered attachments
		add_filter('post_row_actions', array(&$this, 'add_posts_pages_media_link'), 10, 2);
		add_filter('page_row_actions', array(&$this, 'add_posts_pages_media_link'), 10, 2);
		
		// Add filter to display only attachments of specified parent post/ page
		add_filter('posts_where', array(&$this, 'filter_apply'));
		add_action('restrict_manage_posts', array(&$this, 'filter_show'));
		
		// Replace standard "attached to" column
		add_filter('manage_media_columns', array(&$this, 'media_columns'));
		add_filter('manage_upload_sortable_columns', array(&$this, 'media_columns_sortable'));
		add_filter('request', array(&$this, 'media_columns_sortable_orderby'));
		add_action('manage_media_custom_column', array(&$this, 'media_columns_attached_to'), 10, 2);

	}

	// Ensure PHP <5 compatibility
	function th23_media_library_extension_admin() {
		self::__construct();
	}

	// Add supporting information (eg links and notices) to plugin row in plugin overview page
	// Note: Any CSS styling needs to be "hardcoded" here as plugin CSS might not be loaded (e.g. when plugin deactivated)
	function contact_link($links, $file) {
		if($this->plugin['basename'] == $file) {
			// Use internal version number
			$links[0] = sprintf(__('Version %s', 'th23-media-library-extension'), $this->plugin['version']);
			// Add support link
			if(!empty($this->plugin['support_url'])) {
				$links[] = '<a href="' . esc_url($this->plugin['support_url']) . '">' . __('Support', 'th23-media-library-extension') . '</a>';
			}
		}		
		return $links;
	}

	// Add link to edit posts/ pages overview directing to filtered attachments
	function add_posts_pages_media_link($actions, $post) {
		$actions[] = '<a href="upload.php?th23_post_parent=' . $post->ID . '">' . __('Show Media', 'th23-media-library-extension') . '</a>';
		return $actions;
	}

	// Add filter to display only attachments of specified parent post/ page (add where clause)
	function filter_apply($where) {
		global $pagenow;
		if($pagenow == 'upload.php') {
			if(isset($_REQUEST['th23_post_parent']) && (int) $_REQUEST['th23_post_parent'] >= 0) {
				global $wpdb;
				$where .= ' AND ' . $wpdb->posts . '.post_parent = ' . (int) $_REQUEST['th23_post_parent'];
			}
		}
		return $where;
	}

	// Add filter to display only attachments of specified parent post/ page (add selection dropdown)
	function filter_show() {
		global $pagenow;
		if($pagenow != 'upload.php') {
			return;
		}

		// post_parent selection
		global $wpdb;
		
		$post_parents = $wpdb->get_results("SELECT a.ID, a.post_title, (SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_parent = a.ID) AS attachment_count 
			FROM $wpdb->posts a 
			WHERE (post_type = 'post' OR post_type = 'page') AND (post_status = 'publish' OR post_status = 'draft' OR post_status = 'pending') 
			ORDER BY a.post_title ASC");
		$post_parent_options = array(-1 => array(-1, __('Show all parents', 'th23-media-library-extension')), 0 => array(0, __('(Unattached)', 'th23-media-library-extension')));
		foreach ($post_parents as $post_parent) {
			if ($post_parent->attachment_count > 0) {
				$post_parent_options[$post_parent->ID] = array($post_parent->ID, $post_parent->post_title);
			}
		}
		unset($post_parents);

		$post_parent_id = (isset($_REQUEST['th23_post_parent']) && (int) $_REQUEST['th23_post_parent'] >= 0) ? (int) $_REQUEST['th23_post_parent'] : -1;

		if(!isset($post_parent_options[$post_parent_id])) {
			$post_parent_id = -1;
		}

		echo ' ' . $this->build_select('th23_post_parent', $post_parent_options, $post_parent_id, 'th23_post_parent');

	}

	// Build select drop down
	// @param $options needs to be an array('value', 'title', 'css_class'), title and css can be left empty
	function build_select($name, $options, $selected = '', $id = '', $class = '', $multiple = false) {

		$id_html = ($id) ? ' id="' . $id . '"' : '';
		$class_html = ($class) ? ' class="' . $class . '"' : '';
		if ($multiple) {
			$name .= '[]';
			$multiple_html = ' multiple="multiple"';
		} else {
			$multiple_html = '';
		}

		$html_select = '<select name="' . $name . '"' . $id_html . $class_html . $multiple_html . '>';

		if (is_array($options)) {
			foreach ($options as $option) {
				
				if (!is_array($option) || !isset($option[0])) {
					continue;
				}
				$value = (string) $option[0];

				if ($multiple && is_array($selected)) {
					$selected_html = (in_array($value, $selected)) ? ' selected="selected"' : '';
				} else {
					$selected_html = ($value == $selected) ? ' selected="selected"' : '';
				}

				$title = (isset($option[1])) ? (string) $option[1] : $value;
				$style_html = (isset($option[2])) ? ' class="' . (string) $option[2] . '"' : '';			
				$html_select .= '<option value="' . $value . '"' . $selected_html . $style_html . '>' . $title . '</option>';

			}
		}

		return $html_select . '</select>';

	}

	// Replace standard "attached to" column
	function media_columns($defaults) {
		$defaults_new = array();
		foreach($defaults as $default_key => $default_value) {
			if($default_key == 'parent') {
				$default_key = 'th23_media_library_attached_to';
				$default_value = __('Attached to', 'th23-media-library-extension');
			}
			$defaults_new[$default_key] = $default_value;
		}
		return $defaults_new;
	}
	function media_columns_sortable($columns) {
		$columns['th23_media_library_attached_to'] = 'th23_media_library_attached_to';
		return $columns;
	}
	function media_columns_sortable_orderby($vars) {
		if(isset($vars['orderby'] ) && $vars['orderby'] == 'th23_media_library_attached_to') {
			$vars['orderby'] = 'parent';
		} 
		return $vars;
	}
	function media_columns_attached_to($column_name, $id) {
		if ($column_name == 'th23_media_library_attached_to') {    
			$parent_id = (int) get_post_field('post_parent', (int) $id);
			if ( $parent_id > 0 ) {
				echo '<strong><a href="' . get_edit_post_link($parent_id) . '">' . _draft_or_post_title($parent_id) . '</a></strong>';
				echo '<p>' . get_the_time(__('Y/m/d'), $parent_id) . '</p>';
				echo '<div class="row-actions">';
				// add "re-attach" link
				echo '<a class="hide-if-no-js" onclick="findPosts.open(\'media[]\',\'' . $id . '\');return false;" href="#the-list">' . __('Re-Attach', 'th23-media-library-extension') . '</a>';
				// add filter link "only media attached to this item"
				echo ' | <a href="upload.php?th23_post_parent=' . $parent_id . '">' . __('Show All Media', 'th23-media-library-extension') . '</a>';
				echo '</div>';
			} else {
				echo __('(Unattached)', 'th23-media-library-extension');
				echo '<p>&nbsp;</p>';
				echo '<div class="row-actions"><a class="hide-if-no-js" onclick="findPosts.open(\'media[]\',\'' . $id . '\');return false;" href="#the-list">' . __('Attach', 'th23-media-library-extension') . '</a></div>';
			}	
		}
	}

}

?>