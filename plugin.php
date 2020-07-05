<?php

add_action( 'plugins_loaded', 'WPAPIYoast_init' );

/**
 * Plugin Name: Yoast Meta to REST API
 * Description: Adds Yoast fields to page and post metadata to WP REST API responses
 * Author: Yasin Yaqoobi
 * Author URI: https://github.com/rotexhawk/
 * Version: 1.0.0
 * Plugin URI: https://github.com/rotexhawk/yoast-meta-to-rest-api
 */
class Yoast_To_REST_API {


	function __construct() {
		add_action( 'rest_api_init', array( $this, 'add_yoast_data' ) );
	}

	function add_yoast_data() {
		// Posts
		register_rest_field( 'post',
			'yoast_meta',
			array(
				'get_callback'    => array( $this, 'wp_api_encode_yoast' ),
				'update_callback' => null,
				'schema'          => null,
			)
		);

		// Pages
		register_rest_field( 'page',
			'yoast_meta',
			array(
				'get_callback'    => array( $this, 'wp_api_encode_yoast' ),
				'update_callback' => null,
				'schema'          => null,
			)
		);

		// Category
		register_rest_field( 'category',
			'yoast_meta',
			array(
				'get_callback'    => array( $this, 'wp_api_encode_taxonomy' ),
				'update_callback' => null,
				'schema'          => null,
			)
		);

		// Tag
		register_rest_field( 'tag',
			'yoast_meta',
			array(
				'get_callback'    => array( $this, 'wp_api_encode_taxonomy' ),
				'update_callback' => null,
				'schema'          => null,
			)
		);

		// Public custom post types
		$types = get_post_types( array(
			'public'   => true,
			'_builtin' => false
		) );

		foreach ( $types as $key => $type ) {
			register_rest_field( $type,
				'yoast_meta',
				array(
					'get_callback'    => array( $this, 'wp_api_encode_yoast' ),
					'update_callback' => null,
					'schema'          => null,
				)
			);
		}
	}

    
    function wp_api_encode_yoast( $post, $field_name, $request ) {
		$yoastFields = YoastSEO()->meta->for_post( $post_id ); 
        return self::get_yoast_fields($yoastFields);
	}



	function wp_api_encode_taxonomy($post) {
		$yoastFields = YoastSEO()->meta->for_term( $post['id'] ); 
        return self::get_yoast_fields($yoastFields);
    }
	

    function get_yoast_fields($yoastFields){
		
        $og_image =  $yoastFields->open_graph_images;  
		$twitter_image = $yoastFields->twitter_image; 

        $yoast_meta = array(
            'site_title'               =>  get_bloginfo( 'name' ),
            'page_title'               =>  $yoastFields->open_graph_title,
            'description'              =>  $yoastFields->description,  
			'metadesc'                 =>  $yoastFields->meta_description,
            'canonical'                =>  $yoastFields->canonical,
            'robots'                   =>  $yoastFields->robots,
            'modified_time'             =>  $yoastFields->open_graph_article_modified_time,
            'publish_time'             =>  $yoastFields->open_graph_article_published_time,
            'og_site_name'             =>  $yoastFields->open_graph_site_name, 
            'og_title'                 =>  $yoastFields->open_graph_title,
            'og_description'           =>  $yoastFields->open_graph_description,
            'og_image'                 =>  json_encode($og_image), 
            'og_type'                  =>  $yoastFields->open_graph_type,
            'og_locale'                =>  $yoastFields->open_graph_locale,
            'og_url'                   =>  $yoastFields->open_graph_url, 
            'og_publisher'             =>  $yoastFields->open_graph_article_publisher, 
            'og_author'                => $yoastFields->open_graph_article_author,

            'twitter_card'              => $yoastFields->twitter_card,
            'twitter_creator'           => $yoastFields->twitter_creator,
            'twitter_site'              => $yoastFields->twitter_site,

            'twitter_description'       => $yoastFields->twitter_description,
            'twitter_title'             => $yoastFields->twitter_title,
            'twitter_image'             => json_encode($twitter_image),

            'json_lds'                  => WPSEO_Utils::format_json_encode( $yoastFields->schema),  
            'breadcrumbs'               => $yoastFields->breadcrumbs,
		);

		return (array) $yoast_meta;
    }

}	


function WPAPIYoast_init() {
	$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
	if(in_array('wordpress-seo/wp-seo.php', $active_plugins) || in_array('wordpress-seo-premium/wp-seo-premium.php', $active_plugins)){ 
    $yoast_To_REST_API = new Yoast_To_REST_API();
	} else {
		add_action( 'admin_notices', 'wpseo_not_loaded' );
	}
}

function wpseo_not_loaded() {
	printf(
		'<div class="error"><p>%s</p></div>',
		__( '<b>Yoast to REST API</b> plugin not working because <b>Yoast SEO</b> plugin is not active.' )
	);
}

