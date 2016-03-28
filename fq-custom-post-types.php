<?php
/*
Plugin Name: FQ Custom Post Types
Plugin URI: http://figoliquinn.github.io/fq-custom-post-types/
Description: Easy way to add a custom post types
Version: 1.0.0
Author: Figoli Quinn
Author URI: http://www.figoliquinn.com/
License: GPL
Copyright: Figoli Quinn
*/
defined( 'ABSPATH' ) or die( 'No access!' );


// enqueue JS files
add_action( 'admin_enqueue_scripts', 'fq_custom_post_types_enqueue' );
function fq_custom_post_types_enqueue($hook)
{
	if( $hook != 'edit.php' && $hook != 'post.php' && $hook != 'post-new.php' ) 
		return;
	
	wp_enqueue_style( 'fq-image-uploader-style', plugin_dir_url(__FILE__ ) . 'css/fq-image-uploader.css', false, '1.0.0' );
    wp_enqueue_script( 'fq-image-uploader', plugins_url( '/js/fq-image-uploader.js', __FILE__ ), array( 'jquery' ) );
}



if( !class_exists('FQ_Custom_Post_Type') ) {


	class FQ_Custom_Post_Type {

		public $meta_box_context = 'normal'; // normal, side, advanced
		public $meta_box_priority = 'high';  // default, low, high
		public $post_type = 'item';
		public $args = array();              // Defaults are set in the cleanup_args() function
		public $taxonomy_args = array();     // Defaults are set in the cleanup_taxonomy_args() function
		public $admin_header_title = '';
		public $admin_header_html = '';
		public $custom_fields_title = 'Custom Fields';
		
		public $custom_fields = array(
			'sample_date'=>array(
				'type'=>'date',
				'label'=>'Sample Date',
			),
			'sample_text'=>array(
				'type'=>'text',
				'label'=>'Sample Text',
				'show_in_admin_list_callback'=>false,
			),
			'sample_textarea'=>array(
				'type'=>'textarea',
				'label'=>'Sample Textarea',
				'show_in_admin_list_callback'=>false,
			),
			'sample_select'=>array(
				'type'=>'select',
				'label'=>'Sample Select',
				'options'=>array('Yes','No','Maybe'),
				'show_in_admin_list_callback'=>false,
			),
			'sample_radio'=>array(
				'type'=>'radio',
				'label'=>'Sample Radio',
				'options'=>array('Yes','No','Maybe'),
				'show_in_admin_list_callback'=>false,
			),
			'sample_checkbox'=>array(
				'type'=>'checkbox',
				'label'=>'Sample Checkbox',
				'options'=>array('Yes','No','Maybe'),
				'show_in_admin_list_callback'=>false,
			),
			'sample_wysiwyg'=>array(
				'type'=>'wysiwyg',
				'label'=>'Sample Wysiwyg',
				'options'=>array('Yes','No','Maybe'),
				'show_in_admin_list_callback'=>false,
			),
		);

		public $notices = array();
		/* For example:
		$notices = array(
			array('This is an error!','notice-error',true),
			array('This is a warning!','notice-warning',true),
			array('This is a success!','notice-success',true),
		); // message, class, dismissable?
		*/



		function __construct($post_type,$args=array()) {
			$this->post_type = $post_type;
			$this->args = $this->cleanup_args();
			$this->args = array_merge($this->args,$args);
			
			add_action( 'admin_notices', array($this,'plugin_admin_notices') );

		} // end __construct



		/**
		 * Registers the new custom post type
		 * Also sets up the hooks for uninstalling the post type
		 *
		 * @return NULL
		 */
		public function register() {

			// Check that this post type doesn't already exist before trying to register it
			if( !post_type_exists($this->post_type) ) {
				register_post_type( $this->post_type , $this->args );
			}

			// Nicer Custom Post Messages
			add_filter( 'post_updated_messages', array( $this , 'custom_post_type_messages' ) );

			// Customize the next/previous liinks
			add_filter( 'next_post_link' , array($this,'custom_next_post_link') , 99 , 10 );
			add_filter( 'previous_post_link' , array($this,'custom_previous_post_link') , 99 , 10 );

			// Custom column headers
			add_filter('manage_'.$this->post_type.'_posts_columns', array($this,'custom_column_headers'),10,2);
			add_action('manage_'.$this->post_type.'_posts_custom_column', array($this,'custom_column_content'),10,2);
			
			// Custom Header Content
			add_filter('in_admin_header', array($this,'custom_admin_header_content') );

			// Install/Uninstall functions
			add_action( 'after_switch_theme' , array($this,'install') );
			add_action( 'switch_theme' , array($this,'uninstall') );

		
		} // end register



		/**
		 * Creates friendlier messages related to custom post types based on the post type name
		 * Called from $this->register
		 * 
		 * @return array
		 */
		public function custom_post_type_messages() {
			global $post;

			$messages[ $this->post_type ] = array(
				0  => '', // Unused. Messages start at index 1.
				1  => $this->args['labels']['singular_name'].' updated.',
				2  => 'Custom field updated.',
				3  => 'Custom field deleted.',
				4  => $this->args['labels']['singular_name'].' updated.',
				/* translators: %s: date and time of the revision */
				5  => isset( $_GET['revision'] ) ? sprintf( $this->labels['singular_name'].' restored to revision from %s' , wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6  => $this->args['labels']['singular_name'].' published.',
				7  => $this->args['labels']['singular_name'].' saved.',
				8  => $this->args['labels']['singular_name'].' submitted.',
				9  => sprintf(
					$this->args['labels']['singular_name'].' scheduled for: <strong>%1$s</strong>.' ,
					date_i18n('M j, Y @ G:i') , strtotime( $post->post_date )
				),
				10  => $this->args['labels']['singular_name'].' draft updated.',
			);
			
			// If it's something that's publicly accessible, let's set up some nicer view & preview links
			if ( $this->args['publicly_queryable'] ) {
				// Get the link for the post
				$permalink = get_permalink( $post->ID );

				// Create a nicer view link
				$view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), 'View '.$this->args['labels']['singular_name'] );
				$messages[ $this->type ][1] .= $view_link;
				$messages[ $this->type ][6] .= $view_link;
				$messages[ $this->type ][9] .= $view_link;

				// Create a nicer preview link
				$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
				$preview_link = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), 'Preview '.$this->args['labels']['singular_name'] );
				$messages[ $this->type ][8]  .= $preview_link;
				$messages[ $this->type ][10] .= $preview_link;
			}
	
			return $messages;
		
		} // end custom_post_type_messages



		
		
		
		/**
		 * Set the default sort for queries on this post type
		 * Basically what's happening here is this is being used in two ways. The user can use it to set the cusotm post type, so really only pass in a string
		 * This is also being used by wordpress for modifying the query which is there the object part of $query comes in
		 * Should probably be two separate functions at some point
		 *
		 * @param string $query (the name of the column to sort by)
		 * @param string $order ('ASC' or 'DESC')
		 * @param int $show_per_page (how many to show per page)
		 *
		 * @return wp_query object
		 */
		function set_custom_sort( $query = 'title' , $order = 'ASC', $show_per_page = 10 ) {
		
			// If a query column is passed in, sort by that
			if( is_object($query) ) {
			
				if( !is_admin() && isset($query->query_vars['post_type']) && $query->query_vars['post_type'] == $this->type ) {

					$query->set( 'posts_per_page', $this->show_per_page );
					$query->set( 'order', $this->order );

					if( in_array($this->orderby,array('ID','menu_order','title','name','type','date','modifed','parent','author','rand')) ) {
					
						$query->set( 'orderby', $this->orderby );
					}
					else {

						if($this->orderby) {

							$query->set( 'meta_key', $this->orderby ); 
							$query->set( 'orderby', 'meta_value' ); 
						}
					}
				}


			} else {
			
				$this->orderby = $query;
				$this->order = $order;
				$this->show_per_page = $show_per_page;
				add_filter( 'pre_get_posts', array( $this, 'set_custom_sort' ) );

			}
		
			return $query;
	
		} // end custom_sorting()


		/**
		 * Correctly makes a word plural, taking into account it's ending
		 *
		 * @param string $word
		 *
		 * @return string
		 */
		private function pluralize($word) {
		
			switch (substr($word, -1))
			{
				case 's': // If it ends in 's'
					$word = $word .= 's';
					break;
					
				case 'y': // If it ends in 'y'
					$word = substr($word,0,-1).'ies';
					break;
					
				default: 
					$word = $word . 's';
			}
			
			return $word;

		}


		/**
		 * Modifies the admin header with custom text better suited for the custom post type
		 *
		 * @return html
		 */
		public function custom_admin_header_content() {

			$screen = get_current_screen();

			// Only do this if  it's the correct post type
			if( $screen->post_type == $this->post_type && $this->admin_header_title && $this->admin_header_html ) {

				echo '<div class="postbox" style="margin: 0 20px 0 0; padding: 0 20px 10px 20px;">';
				echo '<h3>'.$this->admin_header_title.'</h3>';
				echo wpautop($this->admin_header_html);
				echo '</div>';
			}
		}


		/**
		 * Customize the next post link for the custom post type
		 *
		 * @param string $link_text (the text shown for the link
		 * @param string $link_format
		 * @param string $title_format
		 * @param object $post_object (the post to do this for)
		 * @param string $direction
		 *
		 * @return HTML or NULL
		 */
		public function custom_next_post_link( $link_text='Next' , $link_format='%link' , $title_format='%title &raquo;', $post_object = null , $direction = 'next' ) { 
		
			// If this is the custom post type, set the pagination
			if( is_object($post_object) && $post_object->post_type == $this->type ){
			
				$show = $this->show_per_page;
				$this->show_per_page = 99999;
				$post_object = ( $direction=='next' ? $this->get_next_post() : $this->get_previous_post() );
				$this->show_per_page = $show;
			}
		
			// In general as long as it's an object create the link
			if( is_object($post_object) ) {
			
				$title = apply_filters( 'the_title', $post_object->post_title , $post_object->ID );
				$string = '<a href="' . get_permalink( $post_object->ID ) . '" rel="'.$direction.'">';
				$inlink = str_replace( '%title', $title, $title_format );
				$inlink = str_replace( '%date', $date, $inlink );
				$inlink = $string . $inlink . '</a>';
				$output = str_replace( '%link', $inlink, $link_format );
				
				return $output;
			}
		
		}

		
		/**
		 * Customize the previous post link for the custom post type. Really just uses the next function but reverses the direction
		 *
		 * @param string $link_text (the text shown for the link
		 * @param string $link_format
		 * @param string $title_format
		 * @param object $post_object (the post to do this for)
		 *
		 * @return HTML or NULL
		 */
		public function custom_previous_post_link( $link_text='Previous' , $link_format='%link' , $title_format='%title', $post_object = null ) { 
			
			// Just call the next post link function but reverse the direction
			return $this->custom_next_post_link( $link_text , $link_format , $title_format , $post_object , 'previous' ); 	
		}



		/**
		 * Create custom columns in the admin list screen for the fields with the 'show in admin list callback' setting set
		 *
		 * @param array $defaults
		 * 
		 * @return array
		 */
		public function custom_column_headers( $defaults ) {
			// Only do this if there are custom fields
			if( $this->custom_fields ) {
			
				// Loop throug heach custom field and check if any need to show up in the admin section
				foreach($this->custom_fields as $name => $custom_field){
					
					if($custom_field['show_in_admin_list_callback']){
						$defaults[$name] = $custom_field['label'];
					}
				}
			}
			
			return $defaults;
		}


		/**
		 * Populate the custom columns in the admin section
		 *
		 * @param string $column_name
		 * @param int $post_id
		 *
		 * @return NULL;
		 */
		public function custom_column_content($column_name,$post_id) {

			// Only do this if there's a custom field with this name that needs to be shown in the admin view
			if( $this->custom_fields[$column_name]['show_in_admin_list_callback'] ){
		
				// Call user function to generate content if exists.
				if(function_exists($this->custom_fields[$column_name]['show_in_admin_list_callback'])){
			
					call_user_func($this->custom_fields[$column_name]['show_in_admin_list_callback'],$column_name,$post_id);

				} else {
					
					switch ($this->custom_fields[$column_name]['type']) {
						case 'relationship':
							// Get the ID it's related to
							$post_id = get_post_meta($post_id,$column_name,true);
							
							// Get the post so we can print the title of the post — much friendlier than the ID
							$post = get_post($post_id);
							echo $post->post_title;
							break;
							
						default:
							echo get_post_meta($post_id,$column_name,true);
						
					}
				}
		
			}
			
			return NULL; 
		}


		/**
		 * Returns all of the posts for this custom post type
		 *
		 * @return array
		 */
		function get_all_posts() {
		
			$called = false;
			$new_posts = array();

			// DOESN'T THIS JUST ALWAYS GET CALLED? NEED TO LOOK INTO THIS MORE
			if(!$called) {
				
				// Fetch the wp_query of all posts for this content type
				$posts = new WP_Query(array('post_type' => $this->type , 'orderby' => $this->orderby , 'meta_key' => $this->meta_key , 'posts_per_page' => $this->show_per_page ));
		
				// If nothing came back, just return the original empty array
				if(!$posts->posts) {
				
					return $new_posts;
				}
		
				// Loop through and add these to our array
				foreach($posts->posts as $k => $post) {
	
					$new_posts[$post->ID] = $post;
				}
			}
	
			return $new_posts;
		
		} // end get_all_posts
		
		
		
		/**
		 * Fetches the next post based on the default sorting 
		 * 
		 * @param int $current_id (id of the 'current' post. Leave null if you just want to grab whatever the current post is)
		 * @param boolean $previous (if TRUE, reverses the list so you're actually grabbing the previous one)
		 *
		 * @return WP_POST Object
		 */
		public function get_next_post($current_id=null,$previous=false) {
			
			// If an ID wasn't passed in, get the current ID from the page
			if(!$current_id) { $current_id = get_the_ID(); }
		
			// Get all posts for this content type
			$posts = $this->get_all_posts();
	
			// Reverse it if we're actually trying to find the previous one
			if($previous) { $posts = array_reverse($posts,true); }
	
			$next_post = new stdClass();
			$grab = false;
			$grabbed = false;
			
			// Get the numeric index of our current post
			$currentIndex = array_search($current_id, array_keys($posts));
			
			// Get the next item after that numeric index (if it's not the last one)
			if ($currentIndex < count($posts) + 1) {
				return array_keys($posts)[$currentIndex + 1];
			}
			else {
				return array();
			}
		
		} // end get_next_post


		
		/**
		 * Gets the previous post by calling the next post function but just reverses it
		 *
		 * @param int $current_id (id of the 'current' post. Leave null if you just want to grab whatever the current post is)
		 *
		 * @return WP_POST Object
		 */
		public function get_previous_post($current_id=null) {
			// Call the next post function, just reverse it
			return $this->get_next_post($current_id,true);
		
		} // end get_previous_post



		/**
		 * Adds any custom taxonomy we need related to this custom post type
		 *
		 * @param string $taxonomy
		 * @param array $taxonomy_args
		 *
		 * @return array
		 */
		private function add_taxonomy( $taxonomy , $taxonomy_args = array() ) {
			$this->taxonomy_args = $this->cleanup_taxonomy_args($taxonomy);
			$this->taxonomy_args = array_merge($this->taxonomy_args,$taxonomy_args);

			// Only do this if the taxonomy doesn't already exist
			if( !taxonomy_exists( $taxonomy ) ) {
				register_taxonomy( $taxonomy , $this->post_type , $this->taxonomy_args );
			}
			
			return $this->taxonomy_args;

		} // end add_taxonomy()
		
		
		/**
		 * Adds a custom category based on this custom post type
		 * 
		 * @param string $taxonomy
		 * @param array $taxonomy_args
		 *
		 * @return NULL
		 */
		public function add_category( $taxonomy , $taxonomy_args = array() ) {
			$this->add_taxonomy( $taxonomy , $taxonomy_args );
		}
		
		
		/**
		 * Add a custom tag
		 *
		 * @param string $taxonomy
		 * @param array $taxonomy_args
		 *
		 * @return NULL
		 */
		function add_tag( $taxonomy , $taxonomy_args = array() ) {
			$taxonomy_args['hierarchical'] = false;
			$this->add_taxonomy( $taxonomy , $taxonomy_args );
		}





		/**
		 * Adds custom fields to the custom post type
		 * 
		 * @param array $custom_fields 
		 * @param string $title (sets the title of the meta box holding these custom fields)
		 *
		 * @return NULL
		 */
		function add_custom_fields($custom_fields=array(),$title='') {
			$this->custom_fields = $custom_fields;	
			
			// Set the title of the meta box for these if it's passed in
			if($title) {
				$this->custom_fields_title = $title;
			}
			
			// Creation of the meta boxes
			add_action( 'add_meta_boxes', array( $this, 'cpt_add_meta_box' ) );
			add_action( 'save_post', array( $this, 'save_meta_box' ) );
		}
		
		
		/**
		 * Actually creates the meta box for the custom fields
		 */
		public function cpt_add_meta_box() {
			// Only do this if there are custom fields for the post type
			if ($this->custom_fields) {
				
				add_meta_box(
					$this->post_type.'_id' ,
					$this->custom_fields_title,
					array($this,'display_meta_box'),
					$this->post_type,
					$this->meta_box_context,
					$this->meta_box_priority
				);
			}

		}
		
		
		/**
		 * Handles displaying the custom meta box for the custom fields in the admin section
		 *
		 * @param obj $post
		 */
		public function display_meta_box( $post ) {

			// Only do this if there are custom fields
			if ($this->custom_fields) {
				
				// Loop through each of our custom fields 
				foreach( $this->custom_fields as $name => $custom_field ) {
					
					// Grab the value
					$value = get_post_meta( $post->ID, $name , true );
					
					// Start rendering the HTML
					echo '<br><div class="meta-field">';
					
					switch ($custom_field['type']) {
	
						case "select":
							include('templates/meta_box/select.php');
							break;
	
						case "radio":
						case "checkbox":
							if($custom_field['type']=="checkbox") { 
								$value = (array)$value; 
							}
							
							include('templates/meta_box/checkbox.php');
							break;
	
						case "time":
						case "date":
						case "text":
						case "input":
							if( $value && $custom_field['type']=='date' ) { $value = date(get_option('date_format'),$value); }
							if( $value && $custom_field['type']=='time' ) { $value = date('g:ia',$value); }

							include('templates/meta_box/text.php');
							break;
	
						case "paragraph":
						case "textarea":
							include('templates/meta_box/textarea.php');
							break;
	
						case "html":
							echo $custom_field['content'];
							break;
	
						case 'wysiwyg':
							include('templates/meta_box/wysiwyg.php');
							break;
							
						case 'image':
							// Since we're only storing the image object id, we need to fetch the object
							$image = wp_get_attachment_image_url($value, 'thumbnail');
							include('templates/meta_box/image.php');
							break;
							
						case 'gallery':
							// Break apart the field into the specific images
							$images = FQ_Custom_Post_Type::galleryToImages($value, 'thumbnail');
							include('templates/meta_box/gallery.php');
							break;
							
						case 'relationship':
							// Get all items of that post type
							$query = new WP_Query(array('post_type' => $custom_field['post_type'], 'post_per_page' => -1));
							$relatedPosts = $query->get_posts();
							include('templates/meta_box/relationship.php');
							break;
	
						default:
						break;
	
					}
					
										
					echo "</div>";
					echo "<br><hr>";
					
				}
			
			}

		} // end display_options_meta_box()
	

		/**
		 * Saves the data from our meta box for custom fields
		 *
		 * @param int $post_id (the id of the WP Post)
		 *
		 * @return NULL
		 */
		public function save_meta_box( $post_id )
		{
			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
			
			/* OK, its safe for us to save the data now. */
			foreach($this->custom_fields as $name => $custom_field) {

				if( isset($name) && isset($_POST[$name]) ) {
					$value = $_POST[$name];
					
					// If it's a date or time field, format that value
					switch($custom_field['type']){
						case "date":
						case "time":
							$value = strtotime($value);
						break;
						
					}
					
					// Save that field
					update_post_meta( $post_id , $name , $value );
				}
			}
		}


		/**
		 * Deletes all posts of this custom post type
		 *
		 * @param boolean $execute
		 *
		 * @return NULL
		 */
		public function delete_all($execute=false){
		
			if($execute) {
				// Get all of the posts
				$posts = get_posts( array( 'post_type' => $this->post_type, 'posts_per_page' => -1) );
				
				// Loop through them one by one and delete them
				foreach( $posts as $post ) {
					wp_delete_post( $post->ID, true);
				}
			}

		}


		/**
		 * Saves a post of this type
		 * 
		 * @param array $post
		 * 
		 * @return NULL
		 */
		public function save_post( $post = array() ) {
			$post_array = array_merge(array(
				'post_type' 	=> $this->post_type,
				'post_name' 	=> '',
				'post_title' 	=> '',
				'post_content' 	=> '',
				'post_status' 	=> 'pending',
				'post_date' 	=> date('Y-m-d H:i:s'),
			), $post);
			
			wp_insert_post( $post_array );		
		}

		
		/**
		 * Used for general cleanup of the arguments for the custom post type, setting all the stuff we don't want to
		 *
		 * @return array
		 */
		private function cleanup_args() {
			$plural = ucwords($this->pluralize($this->post_type));
			$singular = ucwords($this->post_type);
			$auto_args = array(
				'label'                 => $singular,
				'labels'                => array(
					'name'                  => $plural,
					'singular_name'         => $singular,
					'menu_name'             => $plural,
					'name_admin_bar'        => $singular,
					'archives'              => $singular.' Archives',
					'parent_item_colon'     => 'Parent '.$singular.': ',
					'all_items'             => 'All '.$plural,
					'add_new_item'          => 'Add New '.$singular,
					'add_new'               => 'Add New',
					'new_item'              => 'New '.$singular,
					'edit_item'             => 'Edit '.$singular,
					'update_item'           => 'Update '.$singular,
					'view_item'             => 'View '.$singular,
					'search_items'          => 'Search '.$singular,
					'not_found'             => 'Not found',
					'not_found_in_trash'    => 'Not found in Trash',
					'featured_image'        => 'Featured Image',
					'set_featured_image'    => 'Set featured image',
					'remove_featured_image' => 'Remove featured image',
					'use_featured_image'    => 'Use as featured image',
					'insert_into_item'      => 'Insert into '.$singular,
					'uploaded_to_this_item' => 'Uploaded to this '.$singular,
					'items_list'            => $plural.' list',
					'items_list_navigation' => $plural.' list navigation',
					'filter_items_list'     => 'Filter '.$plural.' list',
				),
				'description'          	=> 'This is a custom post type.',
				'public'                => true,	// default: false
				'capability_type'       => 'page', 	// default: post
				'hierarchical'          => true,	// default: false
				'supports'              => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'trackbacks', 'revisions', 'custom-fields', 'page-attributes', 'post-formats', ),
				'supports'              => array( 'title', 'editor', 'thumbnail'),
			);
			
			return $auto_args;
		}


		/**
		 * Helper function for formatting the taxonomy correctly
		 *
		 * @param string $taxonomy
		 * 
		 * @return array
		 */
		private function cleanup_taxonomy_args( $taxonomy ) {
		
			$plural = ucwords($this->pluralize($taxonomy));
			$singular = ucwords($taxonomy);
			
			$auto_taxonomy_args = array(
				'labels'                     => array(
					'name'                       => $plural,
					'singular_name'              => $singular,
					'menu_name'                  => $plural,
					'all_items'                  => 'All '.$plural,
					'parent_item'                => 'Parent '.$singular,
					'parent_item_colon'          => 'Parent '.$singular.':',
					'new_item_name'              => 'New '.$singular.' Name',
					'add_new_item'               => 'Add New '.$singular,
					'edit_item'                  => 'Edit '.$singular,
					'update_item'                => 'Update '.$singular,
					'view_item'                  => 'View '.$singular,
					'separate_items_with_commas' => 'Separate '.$plural.' with commas',
					'add_or_remove_items'        => 'Add or remove '.$plural,
					'choose_from_most_used'      => 'Choose from the most used',
					'popular_items'              => 'Popular '.$plural,
					'search_items'               => 'Search '.$plural,
					'not_found'                  => 'Not Found',
					'no_terms'                   => 'No '.$plural,
					'items_list'                 => $plural.' list',
					'items_list_navigation'      => $plural.' list navigation',
				),
				'hierarchical'               => true,
				'public'                     => true,
				'show_ui'                    => true,
				'show_admin_column'          => true,
				'show_in_nav_menus'          => true,
				'show_tagcloud'              => true,
			);
			
			return $auto_taxonomy_args;
		}

		
		
		/**
		 * Create admin notices using our internal custom notices
		 *
		 * @return HTML
		 */
		public function plugin_admin_notices() {
			
			// Only do this if there are actually custom notices
			if ($this->notices) {
				
				foreach($this->notices as $n => $notice) {			
					echo '
						<div class="notice '.$notice[1].' '.($notice[2]?'is-dismissible':'').'">
							<p>'.$notice[0].'</p>
						</div>
					';	
				}
			}

		}
		
		
		/**
		 * Convert our stringified list of image ids for a gallery into an array of image urls
		 *
		 * @param string $gallery
		 * @param string $name (name of the image)
		 * 
		 * @return array
		 */
		public static function galleryToImages($gallery, $name = 'original')
		{
			$images = array();
			$ids = explode(',', $gallery);
			
			foreach ($ids as $id)
			{
				$images[$id] = wp_get_attachment_image_url($id, $name);
			}
			
			return $images;
		}


		function install() {
		}

		function uninstall() {
		}



	} // end class



	// example of custom columns display in the admin list
	// IS THE ACTUALLY USED?
	function display_custom_date_in_admin($name,$id){
		$value = get_post_meta( $id , $name , true );
		if($value) echo date("m-d-y",$value);
	}





} // end if class exists







