<?php
/*
Plugin Name: Short Links Plugin
Description: Plugin for create shorts link
Version: 1.0
Author: Denis
Text Domain: shortlink
*/

namespace App;

use Patterns\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Protection from direct access.
}

class ShortLinksPlugin extends Singleton {

  
    protected function __construct() {
       
        add_action( 'init', [ $this, 'register_post_type' ] );
        
        add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
       
        add_action( 'save_post', [ $this, 'save_meta_box_data' ] );
        
        add_filter( 'manage_short_links_posts_columns', [ $this, 'add_custom_columns' ] );

        add_action( 'manage_short_links_posts_custom_column', [ $this, 'fill_custom_columns' ], 10, 2 );

        add_filter( 'manage_edit-short_links_sortable_columns', [ $this, 'make_custom_columns_sortable' ] );

        add_action( 'template_redirect', [ $this, 'redirect_post_to_short_link' ] );       
        
        add_action('plugins_loaded', [ $this, 'add_textdomain_for_plugin' ] );
    
    }
   
    //Regiter post type 
    public function register_post_type() {
        $labels = array(
            'name'               => 'Short Links',
            'singular_name'      => 'Short Link',
            'menu_name'          => 'Short Links',
            'name_admin_bar'     => 'Short Link',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Short Link',
            'new_item'           => 'New Short Link',
            'edit_item'          => 'Edit Short Link',
            'view_item'          => 'View Short Link',
            'all_items'          => 'All Short Links',
            'search_items'       => 'Search Short Links',
            'not_found'          => 'No Short Links found.',
            'not_found_in_trash' => 'No Short Links found in Trash.'
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'short-links' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 20,
            'supports'           => array( 'title' ),
        );

        register_post_type( 'short_links', $args );
    }

    //Add meta box field
    public function add_meta_box() {
        add_meta_box(
            'original_link_meta_box',
            'Original Link',
            [ $this, 'render_meta_box' ],
            'short_links',
            'normal',
            'high'
        );
    }

    //Render meta box for plugin
    public function render_meta_box( $post ) {
    
        $original_link = get_post_meta( $post->ID, '_original_link', true );       

        $go_to_link = get_post_meta( $post->ID, '_click_count', true );    

        $go_time_link = get_post_meta( $post->ID, '_unique_click_count', true );    

        $label = __('Original link', 'shortlink');
        $input_id = 'original_link';
        $input_name = 'original_link';
        $input_value = esc_attr( $original_link );
        $input_size = 'width:100%';

        echo <<<METABOX
            <label for="$input_id">$label:</label>        
            METABOX;

        echo sprintf(
            '<input type="url" id="%s" name="%s" value="%s" style="%s" />',
            $input_id,
            $input_name,
            $input_value,
            $input_size
        );
        //Total number of transitions - Общее количество переходов
        $all_value = __('Total number of transitions', 'shortlink');
        //Number of transitions without unnecessary clicks - Количество переходов без лишних кликов
        $filter_value = __('Number of transitions without unnecessary clicks', 'shortlink');
        
        echo <<<METAGOTOLINK
            <br />
            <span>$all_value:  $go_to_link</span>        
            <br />
            <span>$filter_value:  $go_time_link</span>   
            METAGOTOLINK;

           
        wp_nonce_field( 'save_original_link', 'original_link_nonce' );
    }
    //Save meta box data for plugin
    public function save_meta_box_data( $post_id ) {
   
        if ( ! isset( $_POST['original_link_nonce'] ) || ! wp_verify_nonce( $_POST['original_link_nonce'], 'save_original_link' ) ) {
            return;
        }
     
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
       
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! isset( $_POST['original_link'] ) ) {
            return;
        }

       $original_link = sanitize_text_field( $_POST['original_link'] );
        update_post_meta( $post_id, '_original_link', $original_link );
    }

    //Adding columns in the desired order
    public function add_custom_columns( $columns ) {
         $columns = array_merge(
            ['cb' => $columns['cb']],
            ['title' => 'Title'], 
            ['post_link' => 'Page url'], 
            ['original_link' => 'Full Link'], 
            ['date' => $columns['date']] 
        );
        
        return $columns;
    }

    //Display a link or message about the absence
    public function fill_custom_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'original_link':
 
                $original_link = get_post_meta( $post_id, '_original_link', true );
                if ( $original_link ) {
                    echo '<a href="' . esc_url( $original_link ) . '">' . esc_html( $original_link ) . '</a>';
                } else {
                    echo __('No original link', 'shortlink');
                }
                break;

            case 'post_link':
              
                $post_link = get_permalink( $post_id );
                echo '<a href="' . esc_url( $post_link ) . '">' . esc_html( $post_link ) . '</a>';
                break;
        }
    }

    //Make custom columns sortable
    public function make_custom_columns_sortable( $columns ) {
        $columns['original_link'] = 'original_link';
        return $columns;
    }

    //Redirect to short link
    public function redirect_post_to_short_link() {
        if ( is_singular( 'post' ) ) {
            $post_id = get_the_ID();
            $post_permalink = get_permalink( $post_id ); 

            $args = array(
                'post_type' => 'short_links',
                'meta_query' => array(
                    array(
                        'key' => '_original_link',
                        'value' => $post_permalink,
                        'compare' => '='
                    )
                )
            );
            $query = new \WP_Query( $args );

            if ( $query->have_posts() ) {
             
                $short_link_post = $query->posts[0]; 
                $short_link_post_id = $short_link_post->ID;

                
                $clicks = get_post_meta( $short_link_post_id, '_click_count', true );
                $clicks = $clicks ? (int)$clicks : 0; 
                
                $clicks++;
                update_post_meta( $short_link_post_id, '_click_count', $clicks );

                $transient_key = 'last_click_' . $short_link_post_id;
                $last_click_time = get_transient( $transient_key );
                if ( false === $last_click_time ) {
                    $last_click_time = 0;
                }

                $current_time = time(); 

            
                if ( ( $current_time - $last_click_time ) > ( 2 * MINUTE_IN_SECONDS ) ) {
                    $unique_clicks = get_post_meta( $short_link_post_id, '_unique_click_count', true );
                    $unique_clicks = $unique_clicks ? (int) $unique_clicks : 0;
                    $unique_clicks++;
                    update_post_meta( $short_link_post_id, '_unique_click_count', $unique_clicks );

                  
                    set_transient( $transient_key, $current_time, 2 * MINUTE_IN_SECONDS );
                }


               
                $short_link_url = get_permalink( $short_link_post_id );
                wp_redirect( $short_link_url, 301 ); 
               
            }else{             
                status_header( 404 );    
                nocache_headers();
                include( get_query_template( '404' ) );
            }
        }
    }

    //add texdomain for plugin
    public function add_textdomain_for_plugin() {    
       
        $mo_file_path = __DIR__ . '/languages/plugin-shortlink-'. determine_locale() . '.mo';

        load_textdomain( 'shortlink', $mo_file_path );        
    }




}


ShortLinksPlugin::get_instance();
