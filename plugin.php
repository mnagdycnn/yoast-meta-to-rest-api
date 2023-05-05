<?php

add_action( 'plugins_loaded', 'WPAPIYoast_init' );

/**
 * Plugin Name: Yoast Meta to REST API
 * Description: Updates Yoast fields to page, post, category and any other custom taxonomy metadata to WP REST API responses
 * Author: Mohamed Nagdy inspired by Yasin Yaqoobi
 * Author URI: https://github.com/mnagdycnn/
 * Version: 2.0.0
 * Plugin URI: https://github.com/mnagdycnn/yoast-meta-to-rest-api/
 */
class YoastToRestApi {


	function __construct() {
		add_action( 'rest_api_init', array( $this, 'add_yoast_data' ) );
	}

	/**
	 * Registers a new field 'yoast_meta' for the APIs
	 */
	function add_yoast_data() {
		// Posts
		register_rest_field( 'post',
			'yoast_meta',
			array(
				'get_callback'    => array( $this, 'wp_api_encode_post_yoast' ),
				'update_callback' => array( $this, 'wp_api_update_post_yoast' ),
				'schema'          => null,
			)
		);

		// Pages
		register_rest_field( 'page',
			'yoast_meta',
			array(
				'get_callback'    => array( $this, 'wp_api_encode_post_yoast' ),
				'update_callback' => array( $this, 'wp_api_update_post_yoast' ),
				'schema'          => null,
			)
		);

		// Category
		register_rest_field( 'category',
			'yoast_meta',
			array(
				'get_callback'    => array( $this, 'wp_api_encode_taxonomy_yoast' ),
				'update_callback' => array( $this, 'wp_api_update_taxonomy_yoast' ),
				'schema'          => null,
			)
		);

		// Tag
		register_rest_field( 'tag',
			'yoast_meta',
			array(
				'get_callback'    => array( $this, 'wp_api_encode_taxonomy_yoast' ),
				'update_callback' => array( $this, 'wp_api_update_taxonomy_yoast' ),
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
					'get_callback'    => array( $this, 'wp_api_encode_post_yoast' ),
					'update_callback' => array( $this, 'wp_api_update_post_yoast' ),
					'schema'          => null,
				)
			);
		}
	}

	/**
	 * Updates post meta with values from post/put request.
	 *
	 * @param array $value
	 * @param object $data
	 * @param string $field_name
	 *
	 * @return array
	 */
	function wp_api_update_post_yoast( $value, $post, $field_name ) {
		foreach ( $value as $k => $v ) {

			if ( in_array( $k, $this->keys ) ) {
				! empty( $k ) ? update_post_meta( $post->ID, '_' . $k, $v ) : null;
				wp_update_post( $post->ID );
			}
		}

		return $this->wp_api_encode_post_yoast( $post->ID );
	}

	/**
	 * Updates taxonomy meta with values from post/put request.
	 *
	 * @param array $value
	 * @param object $term
	 * @param string $field_name
	 *
	 * @return array
	 */
	function wp_api_update_taxonomy_yoast( $value, $term, $field_name ) {

		foreach ( $value as $k => $v ) {

			if ( in_array( $k, $this->keys ) ) {
				! empty( $k ) ? update_post_meta( $term->term_id, '_' . $k, $v ) : null;
				wp_update_term( $term->term_id );
			}
		}

		return $this->wp_api_encode_taxonomy_yoast( $term->term_id );
	}

	
    /**
	 * Encode the post meta data for get requests
	 * 
	 * @param int $post_id
	 * @return array $yoast_meta
	 */
	function wp_api_encode_post_yoast( $post_id ) {
		return self::get_yoast_fields($post_id);
	}

	/**
	 * Encode the taxonomy meta data for get requests
	 * 
	 * @param int $term_id
	 * @return array $yoast_meta
	 */
	function wp_api_encode_taxonomy_yoast( $term_id ) {
		return self::get_yoast_fields($term_id, true);
	}

	/**
	 * Retrieves all the yoast_wpseo values from get_term_meta or get_post_meta
	 * array. Usually the first element of the retrived array from get_<>_meta is
	 * the wanted value
	 * 
	 * @param mixed $the_post_or_taxonomy post or term object
	 * @param bool $taxonomy to check if taxonomy needs to be fetched
	 * @return array $yoast_meta
	 */
	function get_yoast_fields($the_post_or_taxonomy, $taxonomy = false){
		$yoast_meta = [];

        foreach ($this->keys as $key) {
			if ($taxonomy) {
				$value = get_term_meta($the_post_or_taxonomy['id'], '_' . $key)[0];
			} else {
				$value = get_post_meta($the_post_or_taxonomy['id'], '_' . $key)[0];
			}
	
			if (!empty($value)) {
				$yoast_meta[$key] = $value;
			}
		}
		return (array) $yoast_meta;
    }

    protected $keys = array(
		'yoast_wpseo_focuskw',
		'yoast_wpseo_title',
		'yoast_wpseo_metadesc',
		'yoast_wpseo_linkdex',
		'yoast_wpseo_metakeywords',
		'yoast_wpseo_meta-robots-noindex',
		'yoast_wpseo_meta-robots-nofollow',
		'yoast_wpseo_meta-robots-adv',
		'yoast_wpseo_canonical',
		'yoast_wpseo_redirect',
		'yoast_wpseo_opengraph-title',
		'yoast_wpseo_opengraph-description',
		'yoast_wpseo_opengraph-image',
		'yoast_wpseo_twitter-title',
		'yoast_wpseo_twitter-description',
		'yoast_wpseo_twitter-image'
	);

}	


function WPAPIYoast_init() {
	$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
	if(in_array('wordpress-seo/wp-seo.php', $active_plugins) || in_array('wordpress-seo-premium/wp-seo-premium.php', $active_plugins)){ 
    $yoast_To_REST_API = new YoastToRestApi();
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
