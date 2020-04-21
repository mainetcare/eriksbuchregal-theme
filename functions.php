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

add_shortcode( 'mi_list_all_books', function ( $atts ) {
//	$atts = shortcode_atts( array(
//		'id' => 'false',
//	), $atts );

	$args     = array(
		'post_type'      => 'product',
		'posts_per_page' => - 1,
		'meta_query'     => array(
			'relation' => 'OR',
			array(
				'key'     => 'no_booklisting',
				'compare' => 'NOT EXISTS', // works!
				'value'   => '' // This is ignored, but is necessary...
			),
			array(
				'key'   => 'no_booklisting',
				'value' => '0'
			)
		),
	);
	$loop     = new WP_Query( $args );
	$arrBooks = [];
	if ( $loop->have_posts() ) {
		while ( $loop->have_posts() ) : $loop->the_post();
			ob_start();
			get_template_part( 'mi', 'product' );
			$arrBooks[] = ob_get_contents();
			ob_end_clean();
		endwhile;
		wp_reset_postdata();
		$li_part = implode( "\n", $arrBooks );

		return '<div class="mi-products">' . $li_part . '</div>';
	} else {
		wp_reset_postdata();

		return ( '' );
	}
} );

add_shortcode( 'mi_akz', function ( $atts ) {
	$atts   = shortcode_atts( array(
		'format' => 'inline',
	), $atts );
	$format = $atts['format'];
	$a[]    = 'Verlag Eriks Buchregal';
	// $a[]    = '';
	$a[]    = 'Schützenstraße 4';
	$a[]    = 'D-25548 Kellinghusen';
	$a[]    = '<a href="tel:+494882295697">Tel:&nbsp;+49&nbsp;(0)&nbsp;4822.3631030</a>';
	$a[]    = '<a href="tel:+491725612780">Mobil:&nbsp;+49&nbsp;(0)&nbsp;172.5612780</a>';
	$a[]    = '<a href="mailto:erik.eggers@gmx.de">E-Mail:&nbsp;erik.eggers@gmx.de</a>';
	if ( $format == 'inline' ) {
		return implode( ', ', $a );
	} elseif ( $format == 'block' ) {
		return implode( '<br>', $a );
	} else {
		return implode( ' ', $a );
	}
} );

add_shortcode( 'mi_bv', function ( $atts ) {
	$atts   = shortcode_atts( array(
		'format' => 'block',
	), $atts );
	$format = $atts['format'];
	$a[]    = 'Verlag Eriks Buchregal';
	$a[]    = 'Volksbank Wrist eG';
	$a[]    = 'IBAN DE64 2229 0031 0034 2607 14';
	$a[]    = 'BIC GENODEF1VIT';
	if ( $format == 'inline' ) {
		return implode( ', ', $a );
	} elseif ( $format == 'block' ) {
		return implode( '<br>', $a );
	} else {
		return implode( ' ', $a );
	}
} );


//add_filter( 'woocommerce_related_products_args', function ( $args ) {
//	return array();
//}, 10 );
//


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

