<?php
/**
 * eriksbuchregal-theme Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package eriksbuchregal-theme
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_ERIKSBUCHREGAL_THEME_VERSION', '1.0.0' );

/**
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'eriksbuchregal-theme-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ERIKSBUCHREGAL_THEME_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );


add_shortcode( 'mi_show_product_attributes', function ( $atts ) {

	$atts          = shortcode_atts( array(
		'id' => '0',
	), $atts, 'mi_show_product_attributes' );
	$id            = $atts['id'];
	$_pf           = new WC_Product_Factory();
	$_product      = $_pf->get_product( $id );
	$arrAttributes = $_product->get_attributes();

	return '<p>hello</p>';

} );


// Don't show the excerpt in the single-product page:
function woocommerce_template_single_excerpt() {

}

// Show custom attributes on the single-products page immediately under the title:
add_action( 'woocommerce_single_product_summary', 'mi_show_attributes', 6 );
function mi_show_attributes() {
	global $product;
	$seiten = $product->get_attribute( 'pa_seitenzahl' );
	if (is_string($seiten) && strpos($seiten, 'Seiten') === false) {
		$seiten .= ' Seiten';
	}
	$arrAtt[] = $product->get_attribute( 'pa_verlag' );
	$arrAtt[] = $product->get_attribute( 'pa_buchform' ) . ' / ' . $seiten;
	$arrAtt[] = $product->get_attribute( 'pa_auflage' );
	$isbn     = $product->get_attribute( 'pa_isbn' );
	if ( $isbn ) {
		$arrAtt[] = 'ISBN: ' . $product->get_attribute( 'pa_isbn' );
	}
	$atts = implode( '<br>', $arrAtt );
	echo( '<div class="mi-sp-attr">' . $atts . '</div>' );
}

// Custom TAB below the summary:

add_filter( 'woocommerce_product_tabs', 'woo_remove_product_tabs', 98 );
function woo_remove_product_tabs( $tabs ) {
	// unset( $tabs['description'] );      	// Remove the description tab
	unset( $tabs['reviews'] );            // Remove the reviews tab
	unset( $tabs['additional_information'] );    // Remove the additional information tab

	return $tabs;
}

// Add product tab Rezensionen on single-product template:
add_filter( 'woocommerce_product_tabs', 'woo_new_product_tab' );
function woo_new_product_tab( $tabs ) {
	$tabs['rezensionen'] = array(
		'title'    => __( 'Rezensionen', 'woocommerce' ),
		'priority' => 50,
		'callback' => 'woo_new_product_tab_content'
	);

	return $tabs;
}

function woo_new_product_tab_content() {
	// The new tab content
	// echo '<h2>New Product Tab</h2>';
	global $product;
	echo( get_field( 'rezensionen' ) );
}

// Remove the Heading from the description tab:
add_filter( 'woocommerce_product_description_heading', function () {
	return '';
} );


function woocommerce_template_single_meta() {
	echo( '' );
}

// enable Shortcodes in Widgets:
add_filter( 'widget_text', 'do_shortcode' );

function woocommerce_output_related_products() {
	woocommerce_related_products([
		'posts_per_page' => 6,
		'columns'        => 4,
		'orderby'        => 'date'
	]);
}

add_filter( 'get_the_archive_title', function ($title) {
	if ( is_category() ) {
		$title = single_cat_title( '', false );
	} elseif ( is_tag() ) {
		$title = single_tag_title( '', false );
	} elseif ( is_author() ) {
		$title = '<span class="vcard">' . get_the_author() . '</span>' ;
	} elseif ( is_tax() ) { //for custom post types
		$title = sprintf( __( '%1$s' ), single_term_title( '', false ) );
	} elseif (is_post_type_archive()) {
		$title = post_type_archive_title( '', false );
	}
	return $title;
});

