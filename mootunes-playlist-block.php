<?php 

/**
 * Plugin Name: Simple Playlist Block
 * Description: WordPress plugin that adds a Gutenberg block for core playlist functionality
 * Version: 1.3
 * Author: MooTunes
 * Author URI: https://mootunes.com.au/
 * License: GPLv2
 * License URL: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * GitHub Plugin URI: mootunes/playlist-blocks
 */

use Carbon_Fields\Field;
use Carbon_Fields\Block;

class MooTunesPlayListBlock {

    public function __construct() {
        add_action( 'after_setup_theme', array( $this, 'load_carbon_fields' ) );
        add_action( 'carbon_fields_register_fields', array( $this, 'register_block' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'load_css' ) );
    }

    /**
     * Loads Carbon Fields
     */
    public function load_carbon_fields() {
        require_once( 'vendor/autoload.php' );
        \Carbon_Fields\Carbon_Fields::boot();
    }

     /**
     * Loads CSS
     */
    public function load_css() {
        wp_enqueue_style('admin-styles', plugin_dir_url( __FILE__ ) . '/includes/css/admin.css' );
    }

    /**
     * Registers the fields
     */
    public function register_fields() {
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
                    ->set_options( array( $this, 'get_releases' ) )
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
    
        return array_merge( $fields, array(
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
    }

    /**
     * Returns an array of music releases for users to select
     * 
     * @return array    An array of Music Releases, in the format 'Post ID' => 'Post Title'
     */
    public function get_releases() {
        $args = array(
            'post_type' => 'mootunes_album',
            'post_status' => 'publish',
            'numberposts' => -1
        );
        $releases = get_posts( $args );
        $options = array();
        if ( $releases ) {
            foreach ( $releases as $release ) {
                $options[$release->ID] = get_the_title( $release->ID );
            }
        }
        return $options;
    }

    /**
     * Registers the block
     */
    public function register_block() {
        global $pagenow;
        if( $pagenow == 'widgets.php' ) return;
        $fields = $this->register_fields();
        Block::make( 'playlist', __( 'Playlist', 'moo-playlist' ) )
            ->set_mode( 'edit' )
            ->set_icon( 'playlist-audio' )
            ->set_category( 'media' )
            ->add_fields( $fields )
            ->set_render_callback( array( $this, 'callback' ) );
    }

    /**
     * Playlist block callback function
     * Echoes a wp_playlist_shortcode based on fields entered
     * 
     * @param array     $fields     The fields, as outputted by Carbon Fields. Accepts the following keys:
     *                                  - type: if set to 'preview', this will get a music release preview playlist
     *                                  - music-release: the music release to use
     *                                  - tracks: an array of Attachment IDs, in the order of playback
     *                                  - rand: bool - when true, the order will be randomized
     *                                  - style: light or dark
     *                                  - hide: an array of elements to hide: accepts tracklist, tracknumbers, images and artists
     */
    public function callback( array $fields ) {
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
}

$MooTunesPlaylistBlock = new MooTunesPlayListBlock();