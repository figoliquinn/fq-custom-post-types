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





/*
	
	Example Simple Use:
	$type = new FQ_Custom_Post_Type( 'post_type' );
	$type->register();


	$type->add_category('fart',array('show_admin_column'=>false));
	$type->add_tag('turd');
	$type->add_category('poop');
	$type->delete_all(1);
	$type->add_custom_fields();
	$type->custom_fields = array();

*/


if( !class_exists('FQ_Custom_Post_Type') ) {


	class FQ_Custom_Post_Type {

		public $meta_box_context = 'normal'; // normal, side, advanced

		public $meta_box_priority = 'high'; // default, low, high

		public $post_type = 'item';

		// Defaults are set in the cleanup_args() function
		public $args = array();

		// Defaults are set in the cleanup_taxonomy_args() function
		public $taxonomy_args = array();

		public $admin_header_title = '';

		public $admin_header_html = '';

		public $custom_fields_title = 'Custom Fields';
		
		public $custom_fields = array(
			'sample_date'=>array(
				'type'=>'date',
				'label'=>'Sample Date',
				#'show_in_admin_list_callback'=>'display_custom_date_in_admin',
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




		function register() {

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
			add_action('manage_'.$this->post_type.'_posts_custom_column',array($this,'custom_column_content'),10,2);
			
			// Custom Header Content
			add_filter('in_admin_header', array($this,'custom_admin_header_content') );

			// Install/Uninstall functions
			add_action( 'after_switch_theme' , array($this,'install') );
			add_action( 'switch_theme' , array($this,'uninstall') );

		
		} // end register









		function custom_post_type_messages() {
		
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

				if ( $this->args['publicly_queryable'] ) {

					$permalink = get_permalink( $post->ID );

					$view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), 'View '.$this->args['labels']['singular_name'] );
					$messages[ $this->type ][1] .= $view_link;
					$messages[ $this->type ][6] .= $view_link;
					$messages[ $this->type ][9] .= $view_link;

					$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
					$preview_link = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), 'Preview '.$this->args['labels']['singular_name'] );
					$messages[ $this->type ][8]  .= $preview_link;
					$messages[ $this->type ][10] .= $preview_link;
				}
		
				return $messages;
		
		} // end custom_post_type_messages



		
		
		

		function set_custom_sort( $query = 'title' , $order = 'ASC', $show_per_page = 10 ) {
		
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


		function pluralize($word) {
		
			if(substr($word,-1)=='s')
			{
				return $name.'es';
			}
			elseif(substr($name,-1)=='y')
			{
				return substr($word,0,-1).'ies';
			}
			else
			{
				return $word.'s';
			}
		}

		function custom_admin_header_content() {

			$screen = get_current_screen();

			if( $screen->post_type == $this->post_type && $this->admin_header_title && $this->admin_header_html ) {

				echo '<div class="postbox" style="margin: 0 20px 0 0; padding: 0 20px 10px 20px;">';
				echo '<h3>'.$this->admin_header_title.'</h3>';
				echo wpautop($this->admin_header_html);
				echo '</div>';
			}
		}


		function custom_next_post_link( $link_text='Next' , $link_format='%link' , $title_format='%title &raquo;', $post_object = null , $direction = 'next' ) { 
		
			if( is_object($post_object) && $post_object->post_type == $this->type ){
			
				$show = $this->show_per_page;
				$this->show_per_page = 99999;
				$post_object = ( $direction=='next' ? $this->get_next_post() : $this->get_previous_post() );
				$this->show_per_page = $show;
			}
		
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


		function custom_previous_post_link( $link_text='Previous' , $link_format='%link' , $title_format='%title', $post_object = null ) { 
		
			return $this->custom_next_post_link( $link_text , $link_format , $title_format , $post_object , 'previous' ); 	

		}




		function custom_column_headers( $defaults ) {

			if( $this->custom_fields ) {
			
				foreach($this->custom_fields as $name => $custom_field){
					if($custom_field['show_in_admin_list_callback']){
	
						$defaults[$name] = $custom_field['label'];
					}
				}
			}
			return $defaults;
		}

		function custom_column_content($column_name,$post_id) {

			if( $this->custom_fields[$column_name]['show_in_admin_list_callback'] ){
		
				// Call user function to generate content if exists.
				if(function_exists($this->custom_fields[$column_name]['show_in_admin_list_callback'])){
			
					call_user_func($this->custom_fields[$column_name]['show_in_admin_list_callback'],$column_name,$post_id);

				} else {

					echo get_post_meta($post_id,$column_name,true);
				}
		
			}
			return; 
		}



		function get_all_posts() {
		
			static $called = false;
			static $new_posts = array();

			if(!$called) {
			
				$posts = new WP_Query(array('post_type' => $this->type , 'orderby' => $this->orderby , 'meta_key' => $this->meta_key , 'posts_per_page' => $this->show_per_page ));
		
				$new_posts = array();
		
				if(!$posts->posts) {
				
					return $new_posts;
				}
		
				foreach($posts->posts as $k => $post) {
	
					$new_posts[$post->ID] = $post;
				}
			}
	
			return $new_posts;
		
		} // end get_all_posts
		
		

		function get_next_post($current_id=null,$previous=false) {
	
			if(!$current_id) { $current_id = get_the_ID(); }
		
			$posts = $this->get_all_posts();
	
			if($previous) { $posts = array_reverse($posts,true); }
	
			$next_post = array();
			$grab = false;
			$grabbed = false;
			foreach($posts as $id => $post) {

				if(!$grabbed) {

					if($grab) { $next_post = $post; $grabbed = true; }
					if($id == $current_id) { $grab = true; }
				}
			}
			return $next_post;
		
		} // end get_next_post



		function get_previous_post($current_id=null) {

			return $this->get_next_post($current_id,true);
		
		} // end get_previous_post




		function add_taxonomy( $taxonomy , $taxonomy_args = array() ) {
			
			$this->taxonomy_args = $this->cleanup_taxonomy_args($taxonomy);
			$this->taxonomy_args = array_merge($this->taxonomy_args,$taxonomy_args);

			if( !taxonomy_exists( $taxonomy ) ) {

				register_taxonomy( $taxonomy , $this->post_type , $this->taxonomy_args );
			}
			return $this->taxonomy_args;

		} // end add_taxonomy()
		
		function add_category( $taxonomy , $taxonomy_args = array() ) {

			$this->add_taxonomy( $taxonomy , $taxonomy_args );
		}
		
		function add_tag( $taxonomy , $taxonomy_args = array() ) {
			
			$taxonomy_args['hierarchical'] = false;
			$this->add_taxonomy( $taxonomy , $taxonomy_args );
		}






		function add_custom_fields($custom_fields=array(),$title='') {
			
			$this->custom_fields = $custom_fields;			
			if($title) $this->custom_fields_title = $title;
			add_action( 'add_meta_boxes', array( $this, 'cpt_add_meta_box' ) );
			add_action( 'save_post', array( $this, 'save_meta_box' ) );
		}
		
		
		function cpt_add_meta_box() {
			
			if(!$this->custom_fields) return;
			
			add_meta_box(
				$this->post_type.'_id' ,
				$this->custom_fields_title,
				array($this,'display_meta_box'),
				$this->post_type,
				$this->meta_box_context,
				$this->meta_box_priority
			);

		}
		
		
		function display_meta_box( $post ) {

			if(!$this->custom_fields) return;

			# wp_nonce_field( $this->nonce_value , $this->nonce_field );

			foreach( $this->custom_fields as $name => $custom_field ) {
			
				$value = get_post_meta( $post->ID, $name , true );

				echo '<br><div>';
				switch ($custom_field['type']) {

					case "select":
						echo '<label for="'.$name.'"><b>'.$custom_field['label'].'</b></label><br/>';
						echo '<select id="'.$name.'" name="'.$name.'">';
						foreach($custom_field['options'] as $val => $label){
							echo '<option '.($value==$val?'selected':'').' value="'.$val.'">'.$label.'</option>';
						}
						echo '</select>';
					break;

					case "radio":
					case "checkbox":
						if($custom_field['type']=="checkbox") { $value = (array)$value; }
						echo '<label><b>'.$custom_field['label'].'</b></label><br/>';
						$count=0;
						foreach($custom_field['options'] as $val => $label){ $count++;
							if($custom_field['type']=="checkbox") {
								$checked = in_array($val,$value) ? ' checked="checked" ' : '';
							} else {
								$checked = ($val==$value) ? ' checked="checked" ' : '';
							}
							echo '<label for="'.$name.'_'.$count.'">';
							echo '<input name="'.$name.($custom_field['type']=="checkbox"?"[]":"").'" id="'.$name.'_'.$count.'" 
								type="'.$custom_field['type'].'" '.$checked.' value="'.$val.'" />';
							echo $label;
							echo '</label>';
							echo $custom_field['inline'] ? '&nbsp;&nbsp;&nbsp;' : '<br>';
						}
					break;

					case "time":
					case "date":
					case "text":
					case "input":
						if( $value && $custom_field['type']=='date' ) { $value = date(get_option('date_format'),$value); }
						if( $value && $custom_field['type']=='time' ) { $value = date('g:ia',$value); }
						echo '<label for="'.$name.'"><b>'.$custom_field['label'].'</b></label><br/>';
						echo '<input type="text" id="'.$name.'" name="'.$name.'" value="'.$value.'" style="width:100%;" />';
					break;

					case "paragraph":
					case "textarea":
						echo '<label for="'.$name.'"><b>'.$custom_field['label'].'</b></label><br/>';
						echo '<textarea id="'.$name.'" name="'.$name.'" style="width:100%;" rows="5">'.$value.'</textarea>';
					break;

					case "html":
						echo $custom_field['content'];
					break;

					case 'wysiwyg':
						echo '<label for="'.$name.'"><b>'.$custom_field['label'].'</b></label><br/>';
						echo wp_editor($value, $name);
					break;

					default:
					break;

				}
				echo "</div>";
				echo "<br><hr>";
			
			}

		} // end display_options_meta_box()
	

		function save_meta_box( $post_id )
		{

			// Check if our nonce is set.
			#if ( ! isset( $_POST[ $this->nonce_field ] ) ) { return; }
	
			// Verify that the nonce is valid.
			#if ( ! wp_verify_nonce( $_POST[ $this->nonce_field ], $this->nonce_value ) ) { return; }
	
			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
	
			/* OK, its safe for us to save the data now. */
		
			foreach($this->custom_fields as $name => $custom_field) {

				if( isset($name) && isset($_POST[$name]) ) {

					$value = $_POST[$name];

					switch($custom_field['type']){
						case "date":
						case "time":
							$value = strtotime($value);
						break;
					}
					update_post_meta( $post_id , $name , $value );
				}
			}

		}

		function delete_all($execute=false){
		
			if($execute) {
				$posts = get_posts( array( 'post_type' => $this->post_type, 'posts_per_page' => -1) );
				foreach( $posts as $post ) {
					wp_delete_post( $post->ID, true);
				}
			}

		}

		function save_post( $post = array() ) {

			$post_array = array_merge(array(
				'post_type' 	=> $this->post_type,
				'post_name' 	=> '',
				'post_title' 	=> '',
				'post_content' 	=> '',
				'post_status' 	=> 'pending',
				'post_date' 	=> date('Y-m-d H:i:s'),
			),$post);
			wp_insert_post( $post_array );		
		}


		function cleanup_args() {
			
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
				#'has_archive'          => false,	// default: false		
				#'exclude_from_search'	=> true,	// default: opposite of public setting
				#'publicly_queryable'	=> false,	// default: same as public setting
				#'show_ui'				=> false,	// default: same as public setting
				#'show_in_nav_menus'	=> false,	// default: same as public setting
				#'show_in_menu'			=> false,	// default: same as show_ui setting
				#'show_in_admin_bar'	=> false,	// default: same as show_in_menu setting
				#'menu_position'		=> null,
				#'menu_icon'			=> null,
				#'query_var'            => false,	// default: true
				'capability_type'       => 'page', 	// default: post
				'hierarchical'          => true,	// default: false
				'supports'              => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'trackbacks', 'revisions', 'custom-fields', 'page-attributes', 'post-formats', ),
				'supports'              => array( 'title', 'editor', 'thumbnail'),
				#'rewrite'               => false,	// default: true
				#'can_export'            => false,	// default: true
			);
			return $auto_args;
			
		}

		function cleanup_taxonomy_args( $taxonomy ) {
		
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


		function plugin_admin_notices() {
			
			if(!$this->notices) return;
			foreach($this->notices as $n => $notice) {			
				echo '
					<div class="notice '.$notice[1].' '.($notice[2]?'is-dismissible':'').'">
						<p>'.$notice[0].'</p>
					</div>
				';	
			}

		}




	} // end class



	// example of custom columns display in the admin list	
	function display_custom_date_in_admin($name,$id){
	
		$value = get_post_meta( $id , $name , true );
		if($value) echo date("m-d-y",$value);
	}





} // end if class exists







