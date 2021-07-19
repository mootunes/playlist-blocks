<?php 

/**
 * Plugin Name: Simple Playlist Block
 * Description: WordPress plugin that adds a Gutenberg block for core playlist functionality
 * Version: 1.1
 * Author: MooTunes
 * Author URI: https://mootunes.com.au/
 * License: GPLv2
 * License URL: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * GitHub Plugin URI: mootunes/playlist-blocks
 */

use Carbon_Fields\Field;
use Carbon_Fields\Block;

add_action( 'after_setup_theme', 'moo_playlist_load_carbon_fields' );
add_action( 'carbon_fields_register_fields', 'moo_register_playlist_block');
add_action( 'admin_enqueue_scripts', 'moo_playlist_load_css' );

function moo_playlist_load_carbon_fields() {
    require_once( 'vendor/autoload.php' );
    \Carbon_Fields\Carbon_Fields::boot();
}

function moo_playlist_load_css() {
    wp_enqueue_style('admin-styles', plugin_dir_url( __FILE__ ) . '/includes/css/admin.css' );
}

function moo_register_playlist_block() {
    $fields = array(
        Field::make( 'html', 'title' )
            ->set_html( '<h3>' . __( 'Playlist', 'moo-playlist' ) . '</h3>' ),
    );

    if( function_exists( 'mootunes_get_previews' ) ) {
        $fields = array_merge( $fields, array(
            Field::make( 'select', 'type', __( 'Playlist Type', 'moo-playlist' ) )
                ->set_options( array(
                    'standard' => __( 'Standard', 'moo-playlist' ),
                    'preview' => __( 'Music Release Preview', 'moo-playlist' )
                ) )
                ->set_default_value( 'standard' ),
            Field::make( 'select', 'music_release', __( 'Music Release', 'moo-playlist' ) )
                ->set_options( 'moo_playlist_block_releases' )
                ->set_conditional_logic( array(
                    array(
                        'field' => 'type',
                        'value' => 'preview',
                        'compare' => '=',
                    )
                ) )
                ->set_required( true ),
            Field::make( 'media_gallery', 'tracks', __( 'Insert Media', 'moo-playlist' ) )
                ->set_type( array( 'audio', 'video' ) )
                ->set_conditional_logic( array(
                    array(
                        'field' => 'type',
                        'value' => 'standard',
                        'compare' => '=',
                    )
                ) ),
        ) );
    } else {
        $fields[] = Field::make( 'media_gallery', 'tracks',  __( 'Insert Media', 'moo-playlist' ) )
            ->set_type( array( 'audio', 'video' ) );
    }

    $fields = array_merge( $fields, array(
        Field::make( 'checkbox', 'rand', __( 'Randomize Order', 'moo-playlist' ) ),
        Field::make( 'radio', 'style', 'Style' )
                ->set_options( array (
                    'light' => __( 'Light', 'moo-playlist' ),
                    'dark' => __( 'Dark', 'moo-playlist' ),
                ) ),
        Field::make( 'set', 'hide', __( 'Hide', 'moo-playlist' ) )
            ->set_options( array (
                'tracklist' => __( 'Track List', 'moo-playlist' ),
                'tracknumbers' => __(  'Track Numbers', 'moo-playlist' ),
                'images' => __( 'Images', 'moo-playlist' ),
                'artists' => __( 'Artists', 'moo-playlist' )
            ) )
    ) );
    Block::make( 'playlist', __( 'Playlist', 'moo-playlist' ) )
        ->set_mode( 'edit' )
        ->set_icon( 'playlist-audio' )
        ->set_category( 'media' )
        ->add_fields( $fields )
        ->set_render_callback( 'moo_playlist_block_callback' );
}

function moo_playlist_block_callback( $fields ) {
    $args = array();
    if( isset( $fields['type'] ) && $fields['type'] === 'preview' && function_exists( 'mootunes_get_previews' ) ) {
        $args['ids'] = mootunes_get_previews( $fields['music_release'] );
    } elseif( $fields['tracks'] ) {
        $args['ids'] = $fields['tracks'];
        if( isset( $fields[ 'tracks' ][0] ) && strpos( get_post_mime_type( $fields[ 'tracks' ][0] ), 'video' ) !== false ) {
            $args['type'] = 'video';
        }
    }
    if ( $fields['rand'] ) {
        $args['orderby'] = 'rand';
    }
    if ( $fields['style'] ) {
        $args['style'] = $fields['style'];
    }
    if ($fields['hide']) { 
        foreach( $fields['hide'] as $hidden ) { 
            $args[$hidden] = false; 
        } 
    }
    echo wp_playlist_shortcode( $args );
}

function moo_playlist_block_releases( ) {
    $args = array(
        'post_type' => 'mootunes_album',
        'post_status' => 'publish',
        'numberposts' => -1
    );
    $releases = get_posts( $args );
    // $options = array();
    if ( $releases ) {
        foreach ( $releases as $release ) {
            $options[$release->ID] = get_the_title( $release->ID );
        }
    }
    return $options;
}