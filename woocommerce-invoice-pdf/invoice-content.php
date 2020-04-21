<?php
/**
 * Template for invoice content
 *
 * Override this template by copying it to yourtheme/woocommerce-invoice-pdf/invoice-content.php
 *
 * @version     0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} 

//////////////////////////////////////////////////
// init
//////////////////////////////////////////////////

$order	= $args[ 'order' ];
$test	= false;

$wc_mails = WC_Emails::instance(); // load all actions

if ( $order != 'test' ) {
	$order_number			= $order->get_order_number();
	$order_date				= $order->order_date;
	$billing_address		= $order->get_formatted_billing_address();
	$items					= $order->get_items();
	$first_name				= $order->billing_first_name;
	$last_name				= $order->billing_last_name;
	$shipping_address 		= $order->get_formatted_shipping_address();
} else { // test pdf
	$test 					= true;
	$order_number			= rand( 1000, 99999 );
	$order_date				= date( 'Y-m-d' );
	$billing_address		= __( 'John', 'woocommerce-german-market' ) . ' ' . __( 'Doe', 'woocommerce-german-market' ) . '<br/>' . __( '42 Example Avenue', 'woocommerce-german-market' ) . '<br/>' . __( 'Springfield, IL 61109', 'woocommerce-german-market' );
	$shipping_address		= __( 'Marry', 'woocommerce-german-market' ) . ' ' . __( 'Doe', 'woocommerce-german-market' ) . '<br/>' . __( '71 Example Street', 'woocommerce-german-market' ) . '<br/>' . __( 'Denver, IL 61109', 'woocommerce-german-market' );
	$first_name				= __( 'John', 'woocommerce-german-market' );
	$last_name				= __( 'Doe', 'woocommerce-german-market' );
	// we need an existing order to run the actions ( e.g. woocommerce_email_after_order_table )
	$example_order 			= get_option( 'wp_wc_invoice_pdf_example_order', '' );
	$need_new_example_order	= true;
	if ( $example_order != '' ) {
		$example_order_array	= explode( '_', $example_order );
		if ( $example_order_array[ 1 ] > time() - 60*60*24 ) { // after 1 day, look for another example order
			$order_id		= $example_order_array[ 0 ];
			$order 			= wc_get_order( $order_id );
			$order_status	= str_replace( 'wc-', '', $order->post_status );
			if ( in_array( $order_status, array( 'completed', 'processing', 'pending', 'on-hold' ) ) ) {
				$items = $order->get_items();
				$need_new_example_order = false;	
				$there_is_an_example = true;
			}
		}
	}
	if ( $need_new_example_order ) {		
		$args = array(
			'post_type'			=> 'shop_order',
			'post_status' 		=> array( 'wc-completed', 'wc-processing', 'wc-pending', 'wc-on-hold' ),
			'posts_per_page'	=> 10,
			'orderby'			=> 'post_date',
			'order'				=> 'DESC'
		);
		$orders = get_posts( $args );
		foreach ( $orders as $post_example ) {
			// do not use orders with no item (created manualle)
			$order_example = wc_get_order( $post_example );
			if ( count( $order_example->get_items() ) > 0 ) {
				$order = wc_get_order( $order_example );
				$there_is_an_example = true;
				$items = $order->get_items();
				update_option( 'wp_wc_invoice_pdf_example_order', $order->id . '_' . time() );
				break;
			}
		}	
	}
}

$can_use_order = ( ! $test ) || ( $test && $there_is_an_example );
if ( $can_use_order ) {
	$order_date_formated	= sprintf( '<time datetime="%s">%s</time>', date_i18n( 'c', strtotime( $order_date ) ), date_i18n( wc_date_format(), strtotime( $order_date ) ) );
} else {
	$order_date_formated	= __( 'Ex Date', 'woocommerce-german-market' );
}
$content			= '';
$cell_padding		= get_option( 'wp_wc_invoice_pdf_table_cell_padding', 5 );
$show_sku			= get_option( 'wp_wc_invoice_pdf_show_sku_in_invoice', true );
$show_purchase_note	= get_option( 'wp_wc_invoice_pdf_show_purchase_note_in_invoice', false );
$show_short_desc	= get_option( 'wp_wc_invoice_pdf_show_short_description_in_invoice', false );

//////////////////////////////////////////////////
// billing address
//////////////////////////////////////////////////
$additoinal_notation = get_option( 'wp_wc_invoice_pdf_billing_address_additional_notation', get_bloginfo( 'name' ) );
?>
<div class="helper-billing-address">
	<table class="billing-address" cellspacing="0" border="0">
	   <?php if ( trim( $additoinal_notation ) != '{{blank}}' ) { ?>
		<tr>
			<?php $additoinal_notation = strip_tags( $additoinal_notation, '<i><strong><u><b>' ); ?>
            <td class="additional-notation"><?php echo nl2br( $additoinal_notation ); ?></td>                    
		</tr>
		<?php } ?>
		<tr>    
			<td class="address">
				<?php echo $billing_address; ?>
			</td>
		</tr>
	</table>
</div>
<?php

//////////////////////////////////////////////////
// subject
//////////////////////////////////////////////////
$subject = get_option( 'wp_wc_invoice_pdf_invoice_start_subject', __( 'Invoice for order {{order-number}} ({{order-date}})', 'woocommerce-german-market' ) );
$subject_placeholders = apply_filters( 'wp_wc_invoice_pdf_placeholders', array( 'order-number' => __( 'Order Number', 'woocommerce-german-market' ), 'order-date' => __( 'Order Date', 'woocommerce-german-market' ) ) );
$search = array();
$replace = array();
foreach( $subject_placeholders as $placeholder_key => $placeholder_value ) {
	$search[] = '{{' . $placeholder_key . '}}';
	if ( $placeholder_key == 'order-number' ) {
		$replace[] = $order_number;	
	} else if ( $placeholder_key == 'order-date' ) {
		$replace[] = $order_date_formated;	
	} else {
		$replace[] = apply_filters( 'wp_wc_invoice_pdf_placeholder_' . $placeholder_key, $placeholder_value, $placeholder_key, $order );
	}
}
$subject = str_replace( $search, $replace , $subject );
?>
<table class="subject" cellspacing="0" cellpadding="0" border="0">
	<tr>
        <?php
			$invoice_date = apply_filters( 'wp_wc_invoice_pdf_invoice_date', '', $order );

			if ( $invoice_date == '' ) {	?>
   				<td><?php echo apply_filters( 'wp_wc_invoice_pdf_subject', $subject, $order ); ?></td>    
            <?php } else { ?>
	            <td class="subject"><?php echo apply_filters( 'wp_wc_invoice_pdf_subject', $subject, $order ); ?></td>
		        <td class="invoice-date"><?php echo nl2br( $invoice_date ); ?></td>
			<?php } ?>
	</tr>
</table>
<?php

//////////////////////////////////////////////////
// welcome text
//////////////////////////////////////////////////
$welcome_text	= get_option( 'wp_wc_invoice_pdf_invoice_start_welcome_text', '' );
if ( trim ( $welcome_text != '' ) ) {
	$welcome_text 	= str_replace( array( '{{first-name}}', '{{last-name}}', '{{order-number}}', '{{order-date}}' ), array( $first_name, $last_name, $order_number, $order_date_formated ) , $welcome_text );
	?>
	<table class="welcome-text" cellspacing="0" cellpadding="0" border="0">
		<tr>
            <?php $welcome_text = strip_tags ( $welcome_text, '<br><br/><p><h1><h2><h3><h4><h5><h6><em><ul><li><strong><u><i><b><ol><span>' ); ?>
			<td><?php echo nl2br( $welcome_text ); ?></td>
		</tr>
	</table>
	<?php
}

//////////////////////////////////////////////////
// before order table
//////////////////////////////////////////////////
if ( $can_use_order ) {
	ob_start();
	do_action( 'woocommerce_email_before_order_table', $order, false, false );
	$before_order_table = ob_get_clean();
} else {
	$before_order_table = '';	
}

if ( trim ( $before_order_table != '' ) ) {
	?>
	<table class="before-order-table" cellspacing="0" cellpadding="0" border="0">
		<tr>
			<td><?php echo ( trim( $before_order_table ) ); ?></td>
		</tr>
	</table>
	<?php
}

//////////////////////////////////////////////////
// items table
//////////////////////////////////////////////////
$sku_th	= '<th class="header_suk header_sku" scope="col">' . __( 'SKU', 'woocommerce-german-market' ) . '</th>';
?>
<table cellspacing="0" cellpadding="<?php echo $cell_padding;?>" class="invoice-table items-table">
    <thead>
		<tr>
            <?php echo ( $show_sku ) ? $sku_th : ''; ?>
            <th class="header_product" scope="col"><?php echo __( 'Product', 'woocommerce-german-market' ); ?></th>
            <th class="header_quantity" scope="col"><?php echo __( 'Quantity', 'woocommerce-german-market' ); ?></th>
            <th class="header_price <?php echo get_option( 'wp_wc_invoice_pdf_net_prices_product' ) == 'on' ? 'header_net_prices' : ''; ?>" scope="col"><?php echo __( 'Price', 'woocommerce-german-market' ); ?></th>
		</tr>
    </thead>
	<tbody>
		<?php
		if ( ! $can_use_order ) {
			$items = array( 1 => array( 
									'sku'		=> rand( 100, 999999 ),
									'name'		=> __( 'Ex product', 'woocommerce-german-market' ),
									'qty'		=> rand( 1, 20 ),
									'price'		=> __( 'Ex price', 'woocommerce-german-market' )
								)
						);
		}
		$item_i = 0;
		foreach ( $items as $item_id => $item ){
			$item_i++;
			if ( $test && $item_i > 2 ) {
				break;
			}
			if ( $can_use_order ) {
				$_product     = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
				$item_meta    = new WC_Order_Item_Meta( $item['item_meta'], $_product );
			}
			?>
			<tr><?php
				// sku					
				if ( $show_sku ) {
					if ( $can_use_order && is_object( $_product ) && $_product->get_sku() ) {
						$sku = $_product->get_sku();
					} else {
						$sku = apply_filters( 'wp_wc_invoice_inoice_no_sku', '-', $item, $order );	
					}
				?><td class="sku"><?php echo ( $can_use_order ) ? $sku : $item[ 'sku' ]; ?></td>
				<?php
				}
				
				// product name	
				?><td class="product-name"><?php echo nl2br( ( $can_use_order ) ? apply_filters( 'woocommerce_order_item_name', $item['name'], $item ) : $item[ 'name' ] ); 
				
				// item meta
				if ( $can_use_order ) {
					
					?><br/><?php 

					if ( is_object( $_product ) ) {
						$item_meta_obj                = new WC_Order_Item_Meta( $item, $_product );
						$item_meta_display            = $item_meta_obj->get_formatted();
						$meta_array = array();
						foreach ( $item_meta_display as $single_meta_array ) {
							$output = apply_filters( 'wp_wc_invoice_modify_item_meta_string', $single_meta_array[ 'label' ] . ': ' . $single_meta_array[ 'value' ], $single_meta_array );
							array_push( $meta_array, $output );
						}
						$meta_string = apply_filters( 'wp_wc_invoice_modify_item_meta', implode( ', ', $meta_array ), $meta_array );

						?><span class="smaller"><?php echo nl2br( $meta_string ); ?></span><?php
					
					}

				}
				
				// short description
				if ( $can_use_order && $show_short_desc && is_object( $_product ) && trim( $_product->post->post_excerpt ) !== '' ) {
					?><br /><span class="short_description smaller"><?php echo $_product->post->post_excerpt; ?></span><?php
				}

				// purchas note
				if ( $can_use_order && $show_purchase_note && is_object( $_product ) && $purchase_note = get_post_meta( $_product->id, '_purchase_note', true ) ) {				
					?><br/><span class="purchase-note"><?php echo do_shortcode( $purchase_note ); ?></span><?php
				}
				?>
				</td>
				<?php
				// quantity	?>
				<td class="quantity"><?php echo $item['qty']; ?> </td><?php
				
				// subtotal
				if ( get_option( 'wp_wc_invoice_pdf_net_prices_product' ) == 'on' ) {
					
					?><td class="net_prices"><?php
						
						if ( $can_use_order ) {

							// only if there is a tax
							if ( $order->get_line_tax( $item ) > 0.0 ) {
								
								$tax_array =  unserialize( $item[ 'item_meta' ][ '_line_tax_data' ][ 0 ] );
								$first_element = array_shift( $tax_array );
								reset( $first_element );
								$rate_id = key( $first_element );

								$rate_label = WC_Tax::get_rate_label( $rate_id );
								$rate_percent = WC_Tax::get_rate_percent( $rate_id );

								$net_price_product = sprintf( __( '<small>Net: %s<br />+ %s %s: %s<br /></small>= Gross: %s', 'woocommerce-german-market' ),
																wc_price( $order->get_line_subtotal( $item, false ) ),
																$rate_percent,
																$rate_label,
																wc_price( $order->get_line_tax( $item ) ),
																wc_price( $order->get_line_subtotal( $item, true ) ) 
													);

								$net_price_product = apply_filters( 'wp_wc_invoice_net_price_product', $net_price_product, $item );
								echo $net_price_product;

							} else {

								// there is no line tax, make default output
								echo nl2br( $can_use_order ? $order->get_formatted_line_subtotal( $item, get_option( 'woocommerce_tax_display_cart' ) ) : $item[ 'price' ] );

							}

						} else {

							$net_price_product = sprintf( __( '<small>Net: %s<br />+ %s %s: %s<br /></small>= Gross: %s', 'woocommerce-german-market' ),
															wc_price( $item[ 'price' ] ),
															'19%',
															__( 'Ex. VAT', 'woocommerce-german-market' ),
															wc_price( $item[ 'price' ] * 0.19 ),
															wc_price( $item[ 'price' ] * 1.19 ) 
												);

							$net_price_product = apply_filters( 'wp_wc_invoice_net_price_product', $net_price_product, $item );
							echo $net_price_product;

						}						
					
					?></td><?php

				} else {
					?><td class="subtotal"><?php echo nl2br( $can_use_order ? $order->get_formatted_line_subtotal( $item, get_option( 'woocommerce_tax_display_cart' ) ) : $item[ 'price' ] ); ?></td><?php
				}
                
   				// action after item
				if ( $can_use_order ) {
					do_action( 'wp_wc_invoice_pdf_after_item', $item, $_product ); 
				}
				?>
			</tr>
			<?php
		} // enf foreach ?>
	</tbody>
</table>

<?php
// we take another table because <thead> should not be repeated when page is breaking in one of the following lines	
// rendering is not working correctly using <tfoot> (border-bottom of last row is missing when page breaking)
if ( $can_use_order ) {
	$totals = $order->get_order_item_totals( get_option( 'woocommerce_tax_display_cart' ) );
} else {
	$totals = array(
				array(	'label' => __( 'Cart Subtotal', 'woocommerce-german-market' ),		'value'	=> __( 'Ex price', 'woocommerce-german-market' ) ),
				array(	'label' => __( 'Shipping', 'woocommerce-german-market' ),				'value'	=> __( 'Ex price', 'woocommerce-german-market' ) ),
				array(	'label' => __( 'Order Total', 'woocommerce-german-market' ),			'value'	=> __( 'Ex price', 'woocommerce-german-market' ) ),
			);
}

?>
<table cellspacing="0" cellpadding="<?php echo $cell_padding;?>" class="invoice-table totals-table">
	<tbody>
		<?php
		if ( $totals ) {
			$i = 0;
			foreach ( $totals as $total_key => $total ) {
				$i++;
				$border_class = ( $i == 1 ) ? ' extra-border' : '';
				$colspan = ( $show_sku ) ? 3 : 2;

				if ( get_option( 'wp_wc_invoice_pdf_net_prices_total' ) == 'on' ) {

					if ( $total_key == 'order_total' ) {
						
						?>
						<tr>
							<th scope="row" colspan="<?php echo $colspan; ?>" class="totals<?php echo $border_class; ?>"><?php echo apply_filters( 'wp_wc_invoice_pdf_total_net_label', __( 'Total Net:', 'woocommerce-german-market' ) ); ?></th>
							<td class="totals<?php echo $border_class; ?>">
							<?php 
	                    		if ( $can_use_order ) {
		                    		
		                    		$complete_taxes = $order->get_total_tax();
		                    		$fees = $order->get_fees();
		                    		$fee_taxes = 0.0;
		                    		foreach ( $fees as $fee ) {
		                    			$fee_taxes += floatval( $fee[ 'item_meta' ][ '_line_tax' ][ 0 ] );
		                    		}
		                    		$complete_taxes += $fee_taxes;
		                    		$total_exl_tax = $order->get_total() - $complete_taxes;
		                    		echo wc_price( $total_exl_tax );

		                    	} else {

		                    		echo "Ex. Net Price";

		                    	}
		                    ?>   	
	                    	</td>
						</tr>

						<?php
	                	$i++;
						$border_class = ( $i == 1 ) ? ' extra-border' : '';
						$colspan = ( $show_sku ) ? 3 : 2;

					}
				}

				?>
                <tr>
                    <th scope="row" colspan="<?php echo $colspan; ?>" class="totals<?php echo $border_class; ?>"><?php echo nl2br( $total['label'] ); ?></th>
                    <td class="totals<?php echo $border_class; ?>"><?php echo nl2br( $total['value'] ); ?></td>
                </tr>
				<?php
			}
		}
		?>
		</tbody>
</table>
<?php

//////////////////////////////////////////////////
// Small Trading Exemption
//////////////////////////////////////////////////
if ( get_option( WGM_Helper::get_wgm_option( 'woocommerce_de_kleinunternehmerregelung' ) ) == 'on' ) {
	?>
	<table class="after-order-table" cellspacing="0" cellpadding="0" border="0">
		<tr>
			<td>
				<?php echo WGM_Template::get_ste_string_invoice(); ?>
			</td>
				
		</tr>
	</table>
	<?php
}

//////////////////////////////////////////////////
// shipping address
//////////////////////////////////////////////////
if ( ( $can_use_order && ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() && $shipping_address ) || ( ! $can_use_order && $shipping_address ) ) {
	?>
	<table class="shipping-address" cellspacing="0" cellpadding="0" border="0">
		<tr>
			<td>
				<h3 class="title"><?php echo __( 'Shipping address', 'woocommerce-german-market' ); ?>:</h3>
				<?php echo $shipping_address; ?>
			</td>
		</tr>
	</table>
	<?php
}

//////////////////////////////////////////////////
// after_order_table
//////////////////////////////////////////////////
if ( $can_use_order ) {
	ob_start();
	do_action( 'woocommerce_email_after_order_table', $order, false, false );
	$after_order_table = ob_get_clean();
} else {
	$after_order_table = '';
}
if ( trim( $after_order_table ) != '' ) {
	?>
	<table class="after-order-table" cellspacing="0" cellpadding="0" border="0">
		<tr>
			<td><?php echo $after_order_table; ?></td>
		</tr>
	</table>
	<?php
}

//////////////////////////////////////////////////
// order_meta
//////////////////////////////////////////////////
if ( $can_use_order ) {
	ob_start();
	do_action( 'woocommerce_email_order_meta', $order, false, false );
	$order_meta = ob_get_clean();
} else {
	$order_meta = '';	
}
if ( trim( $order_meta ) != '' ) {
	?>
	<table class="order-meta" cellspacing="0" cellpadding="0" border="0">
		<tr>
			<td><?php echo $order_meta; ?></td>
		</tr>
	</table>
	<?php
}

//////////////////////////////////////////////////
// customer note
//////////////////////////////////////////////////
$show_customer_note = get_option( 'wp_wc_invoice_pdf_show_customers_note' );
if ( $show_customer_note == 'on' && $can_use_order && $order->customer_note != '' ) {
	?>
	<table class="after-content-text note customer-note" cellspacing="0" cellpadding="0" border="0">
		<tr>
			<td>
				<strong><?php echo __( 'Customer Note:', 'woocommerce-german-market'); ?></strong>
			</td>
		</tr>
		<tr>
            <?php $customer_note = strip_tags ( $order->customer_note ); ?>
			<td><?php echo nl2br( $customer_note); ?></td>
		</tr>
	</table>
	<?php
}

//////////////////////////////////////////////////
// order notes
//////////////////////////////////////////////////
if ( $can_use_order ) {
	$show_order_notes = get_option( 'wp_wc_invoice_pdf_show_order_notes' );
	$customer_notes = $order->get_customer_order_notes();
	if ( $show_order_notes == 'on' && $can_use_order && ! empty( $customer_notes ) ) {
		
		?>
		<table class="after-content-text note customer-note" cellspacing="0" cellpadding="0" border="0">
			<tr>
				<td>
					<strong><?php echo __( 'Order Notes:', 'woocommerce-german-market'); ?></strong>
				</td>
			</tr>

			<?php foreach ( $order->get_customer_order_notes() as $note ) { ?>
				
				<tr>
					<td>
						<?php 
							echo nl2br( strip_tags( $note->comment_content ) ); 
							$comment_date =  date_i18n( get_option( 'date_format' ), strtotime( $note->comment_date ) ) . ' ' . date_i18n( get_option( 'time_format' ), strtotime( $note->comment_date ) );
							$comment_date = ' (' . $comment_date . ')';
							$comment_date = apply_filters( 'wp_wc_invoice_pdf_comment_date', $comment_date, $note->comment_date );
							echo $comment_date;
						?>
					</td>
				</tr>

			<?php } ?>
			
		</table>
		<?php
	}
}

//////////////////////////////////////////////////
// after content text
//////////////////////////////////////////////////
$after_content_text = get_option( 'wp_wc_invoice_pdf_text_after_content' );
if ( $after_content_text != '' ) {
	?>
	<table class="after-content-text" cellspacing="0" cellpadding="0" border="0">
		<tr>
            <?php $after_content_text = strip_tags ( $after_content_text, '<br><br/><p><h1><h2><h3><h4><h5><h6><em><ul><li><strong><u><i><ol><span>' ); ?>
			<td><?php echo nl2br( $after_content_text ); ?></td>
		</tr>
	</table>
	<?php
}

//////////////////////////////////////////////////
// fine print
//////////////////////////////////////////////////
$fine_print = '';
$show_fine_print = get_option( 'wp_wc_invoice_pdf_show_fine_print', 'no' );
if ( $show_fine_print != 'no' ) {
	if ( $show_fine_print == 'default' ) {
		ob_start();
		echo @wpautop( wp_kses_post( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) ); // don't show notices of third party plugins
		$fine_print_content = ( ob_get_clean() );
	} else {
		$fine_print_content = get_option( 'wp_wc_invoice_pdf_fine_print_custom_content', '' );
	}
	$fine_print_content = str_replace ( array( '<<', '>>', ), array( '<', '>', ), $fine_print_content );		// handle bugs of third party plguins
	$fine_print_content = str_replace( '<p></p>', '', $fine_print_content );									// handle bugs of third party plguins
	$fine_print_content = strip_tags ( $fine_print_content, '<br><br/><p><h1><h2><h3><h4><h5><h6><em><ul><li><strong><u><i><ol><span>' );
	if ( get_option( 'wp_wc_invoice_pdf_fine_print_new_page', true ) ) { 
		?><div style="page-break-after: always;"></div><?php
	}
	?><div class="fine_print"><?php echo $fine_print_content; ?></div><?php
}
