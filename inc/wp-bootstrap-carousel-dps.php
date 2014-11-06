<?php
/**
 * WP_Bootstrap_Carousel_DPS class
 *
 * This class is part of the WP Bootstrap Carousel plugin for WordPress. This addon
 * optionally transforms post listings generated by Bill Erickson's "Display Posts
 * Shortcode" plugin in a Bootstrap Carousel slideshow.
 *
 * To download the "Display Posts Shortcode" plugin, visit:
 * http://wordpress.org/extend/plugins/display-posts-shortcode/
 *
 * Make sure you download DPS version 2.2.1 or higher.
 *
 * For more info on how to customize the output of the shortcode, visit:
 * https://github.com/billerickson/display-posts-shortcode/wiki
 *
 * @class       WP_Bootstrap_Carousel_DPS
 * @version     0.4.0
 * @package     WP_Bootstrap_Carousel/Classes
 * @category    Class
 * @author      Peter J. Herrel <peterherrel@gmail.com>
 * @copyright   Copyright (c) 2012, Peter J. Herrel
 * @link        http://wordpress.org/extend/plugins/wp-bootstrap-carousel/
 * @link        https://github.com/diggy/wp-bootstrap-carousel/wiki/
 * @link        http://peterherrel.com/wordpress/plugins/wp-bootstrap-carousel
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 */

/*
 * LICENSE
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 3, as 
 * published by the Free Software Foundation.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
    exit;
}

if( ! class_exists( 'WP_Bootstrap_Carousel_DPS' ) ) {

/**
 * WP Bootstrap Carousel DPS class
 *
 * @class   WP_Bootstrap_Carousel_DPS
 * @version 0.4.0
 */
class WP_Bootstrap_Carousel_DPS
{
    /**
     * @var     integer
     * @since   0.4.0
     */
    protected static $found_posts = 0;

    /**
     * Constructor
     *
     * @since   0.1.1
     * @access  public
     * @return  WP_Bootstrap_Carousel_DPS
     */
    public function __construct()
    {
        if( is_admin() || defined( 'DOING_AJAX' ) ) {
            return;
        }

        add_action( 'init', array( $this, 'init' ) );
    }
    /**
     * Hook filters into init.
     *
     * @since   0.1.1
     * @access  public
     * @return  void
     */
    public function init()
    {
        add_filter( 'shortcode_atts_display-posts',             array( $this, 'shortcode_atts_display_posts' ),             10, 3 );
        add_filter( 'display_posts_shortcode_args',             array( $this, 'display_posts_shortcode_args' ),             10, 2 );
        add_filter( 'display_posts_shortcode_wrapper_open',     array( $this, 'display_posts_shortcode_wrapper_open' ),     11, 2 );
        add_filter( 'display_posts_shortcode_output',           array( $this, 'display_posts_shortcode_output' ),           12, 9 );
        add_filter( 'display_posts_shortcode_wrapper_close',    array( $this, 'display_posts_shortcode_wrapper_close' ),    13, 2 );
    }
    /**
     * Modify DPS shortcode attributes.
     *
     * @since   0.4.0
     * @access  public
     * @param   array   $out    The output array of DPS shortcode attributes.
     * @param   array   $pairs  The supported DPS shortcode attributes and their defaults.
     * @param   array   $atts   The user defined DPS shortcode attributes.
     * @return  array           Modified output array of DPS shortcode attributes.
     */
    public function shortcode_atts_display_posts( $out, $pairs, $atts )
    {
        // deal with DPS title attr
        if( isset( $atts['title'] ) && '' != $atts['title'] )
        {
            $out['title'] = '';
        }

        return $out;
    }
    /**
     * Modify DPS shortcode query args.
     *
     * @since   0.1.1
     * @access  public
     * @uses    WP_Bootstrap_Carousel_DPS::$found_posts
     * @uses    WP_Bootstrap_Carousel_DPS::is_bootstrap()
     * @uses    WP_Bootstrap_Carousel_DPS::is_attachment_query()
     * @uses    WP_Bootstrap_Carousel_DPS::is_meta_query()
     * @param   array   $args           The output array of the DPS shortcode query args.
     * @param   array   $original_atts  Original attributes passed to the shortcode.
     * @return  array                   Modified output array of the DPS shortcode query args.
     */
    public function display_posts_shortcode_args( $args, $original_atts )
    {
        // check bootstrap attribute
        if( ! $this->is_bootstrap( $original_atts ) ) {
            return $args;
        }

        // add 'inherit' post status if attachments are queried
        if( $this->is_attachment_query( $original_atts ) ) {
            $args['post_status'] = array_unique( array_merge( array( 'inherit' ), $args['post_status'] ) );
        }

        // make sure posts have a thumbnail, but do not interfere with existing meta query
        if( ! $this->is_attachment_query( $original_atts ) && ! $this->is_meta_query( $original_atts ) ) {
            $args['meta_key'] = '_thumbnail_id';
        }

        // get number of found posts
        $listing = new WP_Query( $args );

        if( $listing->have_posts() ) {
            self::$found_posts = $listing->found_posts;
        }

        // reset query
        wp_reset_postdata();

        // return query args
        return $args;
    }
    /**
     * Modify DPS shortcode output.
     *
     * @since   0.1.1
     * @access  public
     * @uses    WP_Bootstrap_Carousel_DPS::is_bootstrap()
     * @uses    wp_bc_bool()
     * @param   string $output        The DPS shortcode's HTML output.
     * @param   array  $original_atts Original attributes passed to the DPS shortcode.
     * @param   string $image         HTML markup for the post's featured image element.
     * @param   string $title         HTML markup for the post's title element.
     * @param   string $date          HTML markup for the post's date element.
     * @param   string $excerpt       HTML markup for the post's excerpt element.
     * @param   string $inner_wrapper Type of container to use for the post's inner wrapper element.
     * @param   string $content       The post's content.
     * @param   string $class         Space-separated list of post classes to supply to the $inner_wrapper element.
     * @return  string                The modified DPS shortcode's HTML output.
     */
    public function display_posts_shortcode_output( $output, $original_atts, $image, $title, $date, $excerpt, $inner_wrapper, $content, $class )
    {
        // check bootstrap attribute
        if( ! $this->is_bootstrap( $original_atts ) ) {
            return $output;
        }

        // handle feed
        if( is_feed() ) {
            return '';
        }

        // post object variables
        $post_id    = $GLOBALS['post']->ID;
        $post_type  = $GLOBALS['post']->post_type;

        // check post type
        if( ! ( post_type_supports( $post_type, 'thumbnail' ) || 'attachment' == $post_type ) ) {
            return '';
        }

        // image variables
        $image_size     = isset( $original_atts['image_size'] ) && $original_atts['image_size'] ? sanitize_text_field( $original_atts['image_size'] ) : 'large';
        $cropped        = ( 'attachment' == $post_type ) ? wp_get_attachment_image_src( $post_id, $image_size ) : wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), $image_size );
        $full           = ( 'attachment' == $post_type ) ? wp_get_attachment_image_src( $post_id, 'full' )      : wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );

        // content variables
        /**
         * Filter the excerpt
         *
         * @since   0.2.1
         * @param   excerpt  $excerpt   The post excerpt
         */
        $showexcerpt = $excerpt ? apply_filters( 'wp_bootstrap_carousel_dps_the_excerpt', wpautop( wptexturize( $excerpt ) ) ) : '';
        /**
         * Filter the content
         *
         * @since   0.2.1
         * @param   excerpt  $content   The post content
         */
        $showcontent = $content ? apply_filters( 'wp_bootstrap_carousel_dps_the_content', $content ) : '';

        // misc variables
        $thickbox       = isset( $original_atts['thickbox'] ) && $original_atts['thickbox'] ? wp_bc_bool( $original_atts['thickbox'] ) : 0;
        $unwrap         = isset( $original_atts['unwrap'] ) && $original_atts['unwrap']     ? wp_bc_bool( $original_atts['unwrap'] )   : 0;
                        /** This filter is documented in wp-includes/link-template.php */
        $href           = ( $thickbox ) ? $full[0] : apply_filters( 'the_permalink', get_permalink( $post_id ) );

        // output fragments
        $inner_wrapper  = 'div';
        $image          = '<a class="image' . ( ( $thickbox ) ? " thickbox" : "" ) . '" href="' . $href . '"><img src="' . $cropped[0] . '" alt=""' . ( ( $unwrap ) ? " data-wpbc_unwrap='1'" : "" ) . ' /></a>';
        $title          = '<a class="title' . ( ( $thickbox ) ? " thickbox" : "" ) . '" href="' . $href . '">' . get_the_title() . '</a>';

        // build output
        $output = '';

        $output .= '<' . $inner_wrapper . ' class="' . implode( ' ', $class ) . ' item active">';

        $output .= $image;

        /**
         * Filter the carousel caption
         *
         * @since   0.2.1
         * @param   string  $html           Default caption html
         * @param   array   $original_atts  Original attributes passed to the DPS shortcode.
         * @param   string  $title          The title
         * @param   string  $date           The date
         * @param   string  $excerpt        The excerpt
         * @param   string  $content        The content
         * @param   object  $post           The post object
         */
        $output .= apply_filters(
            'wp_bootstrap_carousel_dps_caption',
            '<div class="carousel-caption"><h3 class="carousel-post-title">' . $title . '</h3>' . $showexcerpt . $showcontent . '</div><!-- .carousel-caption -->',
            $original_atts, $title, $date, $excerpt, $content, $GLOBALS['post']
        );

        $output .= '</' . $inner_wrapper . '><!-- .item -->';

        // return output
        return $output;
    }
    /**
     * Filter the DPS shortcode output's opening outer wrapper element.
     *
     * @since   0.1.1
     * @access  public
     * @uses    WP_Bootstrap_Carousel::enqueue()
     * @uses    WP_Bootstrap_Carousel_DPS::$found_posts
     * @uses    WP_Bootstrap_Carousel_DPS::is_bootstrap()
     * @uses    wp_bc_bool()
     * @param   string  $output         HTML markup for the opening outer wrapper element.
     * @param   array   $original_atts  Original attributes passed to the shortcode.
     * @return  string                  Modified HTML markup for the opening outer wrapper element.
     */
    public function display_posts_shortcode_wrapper_open( $output, $original_atts )
    {
        // check bootstrap attribute
        if( ! $this->is_bootstrap( $original_atts ) ) {
            return $output;
        }

        // static iterator
        static $it = 1;
        $it++;

        // handle feed
        if( is_feed() ) {
            /**
             * Determine what is displayed in RSS feeds
             *
             * @param   string  $html   The default HTML
             */
            return apply_filters( 
                'wp_bootstrap_carousel_dps_feed',
                /** This filter is documented in wp-includes/link-template.php */
                '<p><a href="' . apply_filters( 'the_permalink', get_permalink( $GLOBALS['post']->ID ) ) . '#wp-bootstrap-carousel-dps-' . $it . '">' . __( 'Click here to view the embedded slideshow.', 'wp_bootstrap_carousel' ) . '</a></p>'
            );
        }

        // sanitize variables
        $max_width  = isset( $original_atts['width'] )      ? intval( str_replace( array( '%', 'px' ), '', trim( $original_atts['width'] ) ) ) : false;
        $max_width  = ( ! empty( $max_width ) )             ? "max-width:{$max_width}px;" : '';
        $controls   = isset( $original_atts['controls'] )   ? wp_bc_bool( $original_atts['controls'] ) : 1;
        $slide      = isset( $original_atts['slide'] )      ? wp_bc_bool( $original_atts['slide'] ) : 1;
        $interval   = isset( $original_atts['interval'] )   ? intval( $original_atts['interval'] ) : 5000;
        $pause      = isset( $original_atts['pause'] )      ? sanitize_text_field( $original_atts['pause'] ) : 'hover';
        $wrap       = isset( $original_atts['wrap'] )       ? wp_bc_bool( $original_atts['wrap'] ) : 1;
        $thickbox   = isset( $original_atts['thickbox'] )   ? wp_bc_bool( $original_atts['thickbox'] ) : 0;
        $title      = isset( $original_atts['title'] )      ? sanitize_text_field( $original_atts['title'] ) : 0;

        // enqueue scripts
        WP_Bootstrap_Carousel::enqueue( $thickbox );

        // start building output
        $output = '';

        // carousel title
        if( $title )
        {
            /**
             * Filter the title tag element.
             *
             * @since   0.4.0
             * @param   string  $tag            Type of element to use for the output title tag. Default 'h2'.
             * @param   array   $original_atts  Original attributes passed to the DPS shortcode.
             */
            $title_tag = apply_filters( 'wp_bootstrap_carousel_dps_title', 'h2', $original_atts );

            $output .= '<' . $title_tag . ' class="display-posts-title wpbc-dps-title">' . $title . '</' . $title_tag . '>' . "\n";
        }

        // open carousel outer div
        $output .= '<div style="width:100%;' . $max_width . '" id="wp-bootstrap-carousel-dps-' . $it . '" class="carousel carousel-dps' . ( ( $slide ) ? " slide" : "" ) . '" data-interval="' . $interval . '" data-pause="' . $pause . '" data-wrap="' . $wrap . '">';

        // carousel indicators
        if( $controls )
        {
            $output .= '<ol class="carousel-indicators">';

            for( $i = 0; $i < self::$found_posts; $i++ ) {
                $output .= '<li data-target="#wp-bootstrap-carousel-dps-' . $it . '" data-slide-to="' . $i . '" class="' . ( ( $i == 0 ) ? "active" : "" ) . '"></li>';
            }

            $output .= '</ol>';
        }

        // open carousel inner div
        $output .= '<div class="carousel-inner carousel-inner-dps">';

        // return output
        return $output;
    }
    /**
     * Filter the DPS shortcode output's closing outer wrapper element.
     *
     * @since   0.1.1
     * @access  public
     * @uses    WP_Bootstrap_Carousel_DPS::is_bootstrap()
     * @uses    wp_bc_bool()
     * @param   string  $output         HTML markup for the closing outer wrapper element.
     * @param   array   $original_atts  Original attributes passed to the DPS shortcode.
     * @return  string                  Modified HTML markup for the closing outer wrapper element.
     */
    public function display_posts_shortcode_wrapper_close( $output, $original_atts )
    {
        // check bootstrap attribute
        if( ! $this->is_bootstrap( $original_atts ) ) {
            return $output;
        }

        // handle feed
        if( is_feed() ) {
            return '';
        }

        // sanitize variables
        $controls = isset( $original_atts['controls'] ) && $original_atts['controls'] ? wp_bc_bool( $original_atts['controls'] ) : 1;

        // static iterator
        static $it = 1;
        $it++;

        // start building output
        $output = '';

        // close carousel inner div
        $output .= '</div><!-- .carousel-inner -->';

        // carousel controls
        if( $controls ) {
            $output .= '<a class="carousel-control carousel-control-dps left" role="button" data-slide="prev" href="#wp-bootstrap-carousel-dps-' . $it . '"><span class="icon-prev"></span></a>
            <a class="carousel-control carousel-control-dps right" role="button" data-slide="next" href="#wp-bootstrap-carousel-dps-' . $it . '"><span class="icon-next"></span></a>';
        }

        // close carousel outer div
        $output .= '</div><!-- .carousel -->';

        // return output
        return $output;
    }
    /**
     * Check bootstrap shortcode parameter.
     *
     * @since   0.2.1
     * @access  private
     * @uses    wp_bc_bool()
     * @param   array   $original_atts  Original attributes passed to the DPS shortcode.
     * @return  bool                    True if bootstrap parameter is set and evaluates to true, otherwise false.
     */
    private function is_bootstrap( $original_atts = array() )
    {
        return isset( $original_atts['bootstrap'] ) && ( false !== wp_bc_bool( $original_atts['bootstrap'] ) ) ? true : false;
    }
    /**
     * Check meta_key shortcode parameter.
     *
     * @since   0.4.0
     * @access  private
     * @param   array   $original_atts  Original attributes passed to the DPS shortcode.
     * @return  bool                    True if meta_key parameter is provided, otherwise false.
     */
    private function is_meta_query( $original_atts = array() )
    {
        return isset( $original_atts['meta_key'] ) && $original_atts['meta_key'] ? true : false;
    }
    /**
     * Check post_type shortcode parameter.
     *
     * @since   0.4.0
     * @access  private
     * @param   array   $original_atts  Original attributes passed to the DPS shortcode.
     * @return  bool                    True if attachment is queried, otherwise or false.
     */
    private function is_attachment_query( $original_atts = array() )
    {
        if( isset( $original_atts['post_type'] ) && $original_atts['post_type'] )
        {
            $post_types = explode( ',', $original_atts['post_type'] );

            if( in_array( 'attachment', $post_types ) ) {
                return true;
            }
        }

        return false;
    }

} // end class

} // end class_exists check
