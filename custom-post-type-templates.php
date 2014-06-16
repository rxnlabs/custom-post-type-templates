<?php
/*
Plugin Name: Custom Post Type Templates
Plugin URI: https://github.com/rxnlabs/custom-post-type-templates
Description: Add template dropdown selector to custom post types. Useful for conditionally showing metaboxes using Advanced Custom Fields
Version: 1.0a
Author: De'Yonte W.
Author URI: http://rxnlabs.com
License:
Copyright 2014 De'Yonte W.
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

// File Security Check
if ( ! empty( $_SERVER['SCRIPT_FILENAME'] ) && basename( __FILE__ ) == basename( $_SERVER['SCRIPT_FILENAME'] ) ) {
  die ( 'You do not have sufficient permissions to access this page!' );
}

// Only create an instance of the plugin if it doesn't already exists in GLOBALS
if( ! array_key_exists( 'cptt', $GLOBALS ) ) {
  
  /**
  * @package WordPress\Plugins
  */
  class CustomPostTypeTemplates {
    
    /**
    * Plugin constructor.
    *
    * Set class properties used throughout the class and call necessary methods.
    *
    * @var void
    */
    public function __construct(){
      if( is_admin() ){
        $this->admin_hooks();
      }else{
        $this->front_hooks();
      }
    }

    /**
    * Plugin hooks to be used in WordPress admin section.
    *
    * Attach the class methods that are called when WordPress does these actions in dashboard.
    *
    * @return void
    */
    public function admin_hooks(){
      add_action( 'add_meta_boxes', array( &$this, 'register_meta_boxes' ) );
      add_action( 'save_post', array( &$this, 'save_meta_boxes' ) );
    }

    /**
    * Plugin hooks to be used in WordPress when not in WordPress dashboard.
    *
    * Attach the class methods that are called when WordPress does these actions when NOT in dashboard.
    *
    * @return void
    */
    public function front_hooks(){
      add_filter( 'template_include', array( &$this, 'change_template' ), 5 );
    }

    /**
    * Get the names of all post types registered.
    *
    * Get the name of all the public post types registered with WordPress.
    *
    * @return array An array of public post type names.
    */
    public function get_post_types(){
      $public_post_types = get_post_types(array('public'=>'true'),'names');

      // post types to not enqueue scripts for
      $remove_posts = array('attachment','page','post');

      // remove certain post types
      foreach ($public_post_types as $value) {
        foreach ($remove_posts as $remove) {
          if( array_key_exists($remove, $public_post_types))
            unset($public_post_types[$remove]);
        }
      }

      return $public_post_types;
    }

    /**
    * Get the label of a post type.
    *
    * @param string $post_type Name of a post type.
    * @return string An array of public post type names.
    */
    public function get_post_types_label($post_type = ''){
      if( !empty($post_type) ){
        $obj = get_post_type_object($post_type);
        $name = $obj->labels->name;
      }
      else{
        $objs = $this->get_post_types();
        $name = array();
        foreach( $objs as $obj ){
          $name[] = $obj->labels->name;
        }
      }

      $name = (empty($name)?false:$name);

      return $name;
    }

    /**
    * Show template dropdown on custom post types
    *
    * Only show the template dropdown on custom post types
    *
    * @return void.
    */
    public function register_meta_boxes(){

      $post_types =  $this->get_post_types();
      if( !empty(get_page_templates()) ){
        foreach( $post_types as $post_type ){
          add_meta_box( 'page_template', 'Template', array( &$this, 'template_meta_box' ), $post_type, 'side', 'default' );
        }
      }
    }

    /**
    * HTML for template dropdown
    *
    * @return void.
    */
    public function template_meta_box($post){
      $saved_template = get_post_meta( $post->ID, '_wp_page_template', true );
      ?>
      <label>Select Template</label>
      <select id="page_template" name="page_template">
        <option value="<?php _e('default');?>" <?php echo ($saved_template === 'default' OR empty($saved_template)?'select':'');?>><?php _e('Default');?></option>
        <?php
        $i = 0;

        foreach(  get_page_templates() as $key=>$template ){
          ?>
          <option value="<?php _e($template);?>" <?php echo ($template === $saved_template?'select':'');?>><?php _e($key);?></option>
          <?php
        }
        ?>
      </select>
      <?php
    }

    /**
    * Save the custom template used to render post
    *
    * @return void.
    */
    public function save_meta_boxes($post_id){
      if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return $post_id;
      }

      $save_template = sanitize_text_field( $_POST['page_template'] );
      update_post_meta( $post_id, '_wp_page_template',  $save_template );
    }

    /**
    * Output post content using selected template
    *
    * @return string A string representing a file path to custom template
    */
    public function change_template($original_template){
      global $post;
      $template = $original_template;
      if( in_array($post->post_type,$this->get_post_types()) ){

        $saved_template = get_post_meta( $post->ID, '_wp_page_template', true );
        if( $saved_template === 'default' OR empty($saved_template) )
          $template = $original_template;
        else
          $template = $saved_template;

        // get the full path to the current theme folder (assuming custom template is in that folder)
        if( !file_exists($template) AND $template != 'default' ){
          $template = get_stylesheet_directory().'/'.$template;
        }
      }

      return $template;
    }
  }
   
  // Store a reference to the plugin in GLOBALS so that our unit tests can access it
  $GLOBALS['cptt'] = new CustomPostTypeTemplates();
     
}