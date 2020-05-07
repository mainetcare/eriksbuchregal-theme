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

	wp_enqueue_style( 'eriksbuchregal-theme-theme-css', get_stylesheet_directory_uri() . '/style.css', array( 'astra-theme-css' ), CHILD_THEME_ERIKSBUCHREGAL_THEME_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );

// Rendert die Produktkategorien zu einem Produkt als "Badges"
add_shortcode( 'mnc-badgemenu', function ( $atts ) {

	$atts = shortcode_atts( array(
		'menu' => 'main',
	), $atts, 'mnc-badgemenu' );
	$menu = $atts['menu'];

	return wp_nav_menu( [
		'menu'            => $menu, // (int|string|WP_Term) Desired menu. Accepts a menu ID, slug, name, or object.
		'menu_class'      => "mnc-badges", // (string) CSS class to use for the ul element which forms the menu. Default 'menu'.
		'container'       => "span", // (string) Whether to wrap the ul, and what to wrap it with. Default 'div'.
		'container_class' => "mnc-badges-menu-container", // (string) Class that is applied to the container. Default 'menu-{menu slug}-container'.
		'echo'            => false, // (bool) Whether to echo the menu or return it. Default true.
		'depth'           => "1", // (int) How many levels of the hierarchy are to be included. 0 means all. Default 0.
	] );

} );


/**
 * Liefert eine Liste mit definierten eigenen Produkt-Attributen
 * Jedes Attribut wird in einem DIV Container zurück gegeben
 * z.B. Autor, ISBN, Seitenzahl, usw.
 * Beispiel [mnc-product-atttributes atts="autor,isbn"]
 *
 */
add_shortcode( 'mnc-product-attributes', function ( $atts ) {
	global $post;
	$atts = shortcode_atts( array(
		'id'   => '0',
		'atts' => ''
	), $atts, 'mnc-product-attributes' );
	$id   = $atts['id'];
	$keys = explode( ',', $atts['atts'] );
	if ( count( $keys ) == 0 ) {
		return '';
	}
	if ( isset( $post ) && $post->post_type == 'product' ) {
		$id = $post->ID;
	}
	$_pf     = new WC_Product_Factory();
	$product = $_pf->get_product( $id );
	$html    = [];
	if ( $product ) {
		foreach ( $keys as $key ) {
			$key   = 'pa_' . $key;
			$value = $product->get_attribute( $key );
			if ( $value ) {
				$html[] = '<div class="' . $key . '">' . $product->get_attribute( $key ) . '</div>';
			}
		}
	}

	return implode( "\n", $html );
} );

// Stellt ein einzelnes Produkt für die Startseite dar:
add_shortcode( 'mnc-prod', function ( $atts ) {
	global $post;
	$atts = shortcode_atts( array(
		'id'   => '0',
		'atts' => '',
	), $atts, 'mnc-prod' );
	$id   = $atts['id'];

	$keys = explode( ',', $atts['atts'] );
	if ( count( $keys ) == 0 ) {
		return '';
	}
	if ( isset( $post ) && $post->post_type == 'product' ) {
		$id = $post->ID;
	}
	$_pf     = new WC_Product_Factory();
	$product = $_pf->get_product( $id );
	$html    = [];
	if ( $product ) {
		$alt_image = get_field( 'produkt-frontpage-image', $id );
		if ( $alt_image ) {
			$img = wp_get_attachment_image( $alt_image, 'full' );
		} else {
			$img = $product->get_image();
		}
		$title  = $product->get_title();
		$link   = $product->get_permalink();
		$autor  = $product->get_attribute( 'pa_autor' );
		$preis  = number_format_i18n( $product->get_price(), 2 ) . '&nbsp;€';
		$info   = get_field( 'produkt-frontpage-hinweis', $id );
		$html[] = '<div class="mnc-prod-list">';
		$html[] = sprintf( '<a href="%s">%s</a>', $link, $img );
		$html[] = sprintf( '<h4><a href="%s">%s</a></h4>', $link, $title );
		$html[] = '<div class="mnc-pl-autor">' . $autor . '</div>';
		$html[] = '<div class="mnc-pl-preis">' . $preis . '</div>';
		$html[] = '<div class="mnc-pl-info">' . $info . '</div>';
		$html[] = '</div>';
	}
	return implode( "\n", $html );
} );


// Don't show the excerpt in the single-product page:
function woocommerce_template_single_excerpt() {

}

// Show custom attributes on the single-products page immediately under the title:
add_action( 'woocommerce_single_product_summary', 'mi_show_attributes', 6 );
function mi_show_attributes() {
	global $product;
	$seiten = $product->get_attribute( 'pa_seitenzahl' );
	if ( is_string( $seiten ) && strpos( $seiten, 'Seiten' ) === false ) {
		$seiten .= ' Seiten';
	}
	$arrAtt[] = $product->get_attribute( 'pa_verlag' );
	if(strlen($seiten)) {
		$arrAtt[] = $product->get_attribute( 'pa_buchform' ) . ' / ' . $seiten;
	} else {
		$arrAtt[] = $product->get_attribute( 'pa_buchform' );
	}
	$arrAtt[] = $product->get_attribute( 'pa_auflage' );
	$isbn     = $product->get_attribute( 'pa_isbn' );
	if ( $isbn ) {
		$arrAtt[] = 'ISBN: ' . $product->get_attribute( 'pa_isbn' );
	}
	$atts = implode( '<br>', $arrAtt );
	echo( '<div class="mi-sp-attr">' . $atts . '</div>' );
}

//l


add_action( 'astra_woo_shop_title_after', function () {
	global $post;
	$_pf = new WC_Product_Factory();
	if ( $post && $post->post_type == 'product' ) {
		$product = $_pf->get_product( $post->ID );
		// $product->get_attributes();
		$author = $product->get_attribute( 'pa_autor' );
		echo( '<div class="mnc-author">' . $author . '</div>' );
	}
} );

//add_action('woocommerce_before_shop_loop_item_title', function() {
//	echo( 'ISE ISE ISE');
//});
//
//add_action('woocommerce_shop_loop_item_title', function() {
//	echo( '!Moin ick bin Stephan!');
//});

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
	woocommerce_related_products( [
		'posts_per_page' => 6,
		'columns'        => 4,
		'orderby'        => 'date'
	] );
}

add_filter( 'get_the_archive_title', function ( $title ) {
	if ( is_category() ) {
		$title = single_cat_title( '', false );
	} elseif ( is_tag() ) {
		$title = single_tag_title( '', false );
	} elseif ( is_author() ) {
		$title = '<span class="vcard">' . get_the_author() . '</span>';
	} elseif ( is_tax() ) { //for custom post types
		$title = sprintf( __( '%1$s' ), single_term_title( '', false ) );
	} elseif ( is_post_type_archive() ) {
		$title = post_type_archive_title( '', false );
	}

	return $title;
} );



