<?php

/**
 * Plugin Name: Simple Playlist Blocks
 * Description: WordPress plugin that adds a Gutenberg block for core playlist functionality.
 * Version: 1.0
 * Author: MooTunes
 * Author URI: https://mootunes.com.au/
 * License: GPLv2
 * License URL: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

//Set up Carbon Fields
use Carbon_Fields\Container;
use Carbon_Fields\Field;
use Carbon_Fields\Block;

add_action( 'after_setup_theme', 'crb_load' );
function crb_load() {
    require_once( 'vendor/autoload.php' );
    \Carbon_Fields\Carbon_Fields::boot();
}

//Audio Playlist Block
add_action( 'carbon_fields_register_fields', 'mootunes_audio_playlist_block' );
function mootunes_audio_playlist_block() {
  Block::make( __( 'Audio Playlist' ) )
      ->set_preview_mode( false )
      ->set_icon('playlist-audio')
      ->add_fields( array(
        Field::make( 'html', 'crb_information_text' )
          ->set_html( '<h3>Audio Playlist</h3>' ),
        Field::make( 'media_gallery', 'mootunes_audio_playlist_block_tracks', 'Tracks' )
          ->set_type( 'audio' ),
        Field::make( 'radio', 'mootunes_audio_playlist_order', 'Order' )
          ->set_options( array (
            'asc' => 'ASC',
            'desc' => 'DESC',
            'rand' => 'RANDOM'
          ) ),
        Field::make( 'radio', 'mootunes_audio_playlist_style', 'Style' )
          ->set_options( array (
            'light' => 'Light',
            'dark' => 'Dark',
          ) ),
        Field::make( 'set', 'mootunes_audio_playlist_hide', 'Hide' )
          ->set_options( array (
            'tracklist' => 'Track List',
            'tracknumbers' => 'Track Numbers',
            'images' => 'Images',
            'artists' => 'Artists'
          ) )
      ) )
      ->set_render_callback( function ( $fields, $attributes, $inner_blocks ) {
        //Set Track List
        $args = array();
        if ( $fields['mootunes_audio_playlist_block_tracks'] ) $args['ids'] = $fields['mootunes_audio_playlist_block_tracks']; // Set Tracks
        if ( $fields['mootunes_audio_playlist_order'] == 'rand' ) { $args['orderby'] = 'rand'; } //Set Order to Random
        elseif ( $fields['mootunes_audio_playlist_order'] ) { $args['order'] = $fields['mootunes_audio_playlist_order']; } //Otherwise set the order
        if ( $fields['mootunes_audio_playlist_style'] ) $args['style'] = $fields['mootunes_audio_playlist_style'];
        if ($fields['mootunes_audio_playlist_hide']) { foreach( $fields['mootunes_audio_playlist_hide'] as $hidden ) { $args[$hidden] = false; } }
        echo wp_playlist_shortcode( $args );
      } );
}

//Video Playlist Block
add_action( 'carbon_fields_register_fields', 'mootunes_video_playlist_block' );
function mootunes_video_playlist_block() {
  Block::make( __( 'Video Playlist' ) )
      ->set_preview_mode( false )
      ->set_icon('playlist-video')
      ->add_fields( array(
        Field::make( 'html', 'crb_information_text' )
          ->set_html( '<h3>Video Playlist</h3>' ),
        Field::make( 'media_gallery', 'mootunes_video_playlist_block_tracks', 'Tracks' )
          ->set_type( 'video' ),
        Field::make( 'radio', 'mootunes_video_playlist_order', 'Order' )
          ->set_options( array (
            'asc' => 'ASC',
            'desc' => 'DESC',
            'rand' => 'RANDOM'
          ) ),
        Field::make( 'radio', 'mootunes_video_playlist_style', 'Style' )
          ->set_options( array (
            'light' => 'Light',
            'dark' => 'Dark',
          ) ),
        Field::make( 'set', 'mootunes_video_playlist_hide', 'Hide' )
          ->set_options( array (
            'tracklist' => 'Track List',
            'tracknumbers' => 'Track Numbers',
          ) )
      ) )
      ->set_render_callback( function ( $fields, $attributes, $inner_blocks ) {
        $args = array( 'type' => 'video' );
        if ( $fields['mootunes_video_playlist_block_tracks'] ) $args['ids'] = $fields['mootunes_video_playlist_block_tracks']; // Set Tracks
        if ( $fields['mootunes_video_playlist_order'] == 'rand' ) { $args['orderby'] = 'rand'; } //Set Order to Random
        elseif ( $fields['mootunes_video_playlist_order'] ) { $args['order'] = $fields['mootunes_video_playlist_order']; } //Otherwise set the order
        if ( $fields['mootunes_video_playlist_style'] ) $args['style'] = $fields['mootunes_video_playlist_style'];
        if ($fields['mootunes_video_playlist_hide']) { foreach( $fields['mootunes_video_playlist_hide'] as $hidden ) { $args[$hidden] = false; } }
        echo wp_playlist_shortcode( $args );
      } );
}
