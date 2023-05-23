<?php
/**
 * Expivi Checkout Manager
 *
 * @package Expivi/WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class to provide functionality to checkout process.
 */
class Expivi_Checkout_Manager {
	/**
	 * A var to store Cart Contents.
	 *
	 * @var array
	 */
	private $cart_items_keys = array();

	/**
	 * Expivi_Checkout_Manager Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_review_order_before_cart_contents', array( $this, 'woocommerce_review_order_before_cart_contents' ) );
		add_action( 'woocommerce_review_order_after_cart_contents', array( $this, 'woocommerce_review_order_after_cart_contents' ) );
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_configuration_to_order_item' ), 10, 4 );
		add_action( 'woocommerce_check_cart_items', array( $this, 'is_grouped_product_stock_enough' ) );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'decrease_grouped_product_stock' ), 10, 1 );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'increase_grouped_product_stock' ), 10, 1 );
	}

	/**
	 * Remove generated products from checkout before showing cart contents.
	 *
	 * @return void
	 */
	public function woocommerce_review_order_before_cart_contents() {
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

			if ( $_product->get_meta( 'xpv_generated' ) ) {
				WC()->cart->remove_cart_item( $cart_item_key );
			}

			$this->cart_items_keys[] = $cart_item_key;
		}
	}

	/**
	 * Restore generated products in checkout after showing cart contents using custom template.
	 *
	 * @return void
	 */
	public function woocommerce_review_order_after_cart_contents() {
		if ( empty( $this->cart_items_keys ) ) {
			return;
		}

		foreach ( $this->cart_items_keys as $cart_item_key ) {
			WC()->cart->restore_cart_item( $cart_item_key );
		}
		xpv_get_template( 'checkout/review-order.phtml' );
	}

	/**
	 * Add the configuration from the cart item to the order item.
	 *
	 * @param WC_Order_Item_Product $item Product of Order Item.
	 * @param string                $cart_item_key Key of Cart Item.
	 * @param array                 $values Values of Cart Item.
	 * @param WC_Order              $order Order.
	 */
	public function add_configuration_to_order_item( $item, $cart_item_key, $values, $order ) {
		$config = null;
		if ( array_key_exists( 'xpv_configuration', $values ) ) {
			$config = $values['xpv_configuration'];
		} elseif ( array_key_exists( 'configuration', $values ) ) { // Backwards compatibility.
			$config = $values['configuration'];
		}

		if ( is_null( $config ) || empty( $config ) ) {
			return;
		}

		$meta_data = xpv_array_get( $values, 'xpv_meta_data', array() );

		$item->add_meta_data( 'xpv_configuration', $config );
		$item->add_meta_data( 'xpv_meta_data', $meta_data );
	}

	/**
	 * Checks stock quantities before Processing Orders for Expivi generated grouped products.
	 *
	 * @return void
	 */
	public function is_grouped_product_stock_enough() {
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product                = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$stock_quantity_in_cart = $cart_item['quantity'];

			if ( ! $product->get_meta( 'xpv_generated' ) ) {
				continue;
			}

			$base_product_id             = $product->get_meta( 'xpv_base_simple_product' );
			$base_product                = wc_get_product( $base_product_id );
			$grouped_products_ids        = $product->get_meta( 'xpv_grouped_products_ids' );
			$grouped_products_quantities = $product->get_meta( 'xpv_grouped_products_quantities' );

			if ( ! $base_product->has_enough_stock( $stock_quantity_in_cart ) ) {
				// translators: %1$s: Name of product.
				// translators: %2$s: Stock quantity.
				$message = sprintf( __( 'Sorry, we do not have enough "%1$s" in stock to fulfill your order (%2$s available). We apologize for any inconvenience caused.' ), $product->get_name(), wc_format_stock_quantity_for_display( $base_product->get_stock_quantity(), $base_product ) );

				wc_add_notice( $message, 'error' );

				WC()->cart->set_quantity( $cart_item_key, $base_product->get_stock_quantity() );

				return;
			}

			foreach ( $grouped_products_ids as $grouped_product_id ) {
				$grouped_product                        = wc_get_product( $grouped_product_id );
				$grouped_product_stock_quantity         = $grouped_product->get_stock_quantity();
				$grouped_product_stock_quantity_in_cart = $grouped_products_quantities[ $grouped_product->get_sku() ] * $stock_quantity_in_cart;

				if ( ! $grouped_product->has_enough_stock( $grouped_product_stock_quantity_in_cart ) ) {
					// translators: %1$s: Name of product.
					// translators: %2$s: Stock quantity.
					$message             = sprintf( __( 'Sorry, we do not have enough "%1$s" in stock to fulfill your order (%2$s available). We apologize for any inconvenience caused.' ), $grouped_product->get_name(), wc_format_stock_quantity_for_display( $grouped_product->get_stock_quantity(), $grouped_product ) );
					$available_stock_qty = floor( ( $grouped_product_stock_quantity * $stock_quantity_in_cart ) / $grouped_product_stock_quantity_in_cart );

					wc_add_notice( $message, 'error' );

					WC()->cart->set_quantity( $cart_item_key, $available_stock_qty );
					return;
				}
			}
		}
	}

	/**
	 * Decrease stock quantities for Expivi generated grouped products.
	 *
	 * @param int $order_id Identifier of order.
	 *
	 * @return void
	 */
	public function decrease_grouped_product_stock( $order_id ) {
		$order       = wc_get_order( $order_id );
		$order_items = $order->get_items();

		foreach ( $order_items as $item ) {
			$product = $item->get_product();

			if ( ! $product->meta_exists( 'xpv_generated' ) ) {
				continue;
			}

			$base_product_id             = $product->get_meta( 'xpv_base_simple_product' );
			$base_product                = wc_get_product( $base_product_id );
			$grouped_products_ids        = $product->get_meta( 'xpv_grouped_products_ids' );
			$grouped_products_quantities = $product->get_meta( 'xpv_grouped_products_quantities' );

			if ( $base_product->managing_stock() ) {
				wc_update_product_stock( $base_product_id, $item['qty'], 'decrease' );
			}

			foreach ( $grouped_products_ids as $grouped_product_id ) {
				$grouped_product          = wc_get_product( $grouped_product_id );
				$grouped_product_quantity = $grouped_products_quantities[ $grouped_product->get_sku() ] * $item['qty'];

				if ( $grouped_product->managing_stock() ) {
					wc_update_product_stock( $grouped_product_id, $grouped_product_quantity, 'decrease' );
				}
			}
		}
	}

	/**
	 * Increase stock quantities for Expivi generated grouped products.
	 *
	 * @param int $order_id Identifier of order.
	 *
	 * @return void
	 */
	public function increase_grouped_product_stock( $order_id ) {
		$order       = wc_get_order( $order_id );
		$order_items = $order->get_items();

		foreach ( $order_items as $item ) {
			$product = $item->get_product();

			if ( ! $product->meta_exists( 'xpv_generated' ) ) {
				continue;
			}

			$base_product_id             = $product->get_meta( 'xpv_base_simple_product' );
			$base_product                = wc_get_product( $base_product_id );
			$grouped_products_ids        = $product->get_meta( 'xpv_grouped_products_ids' );
			$grouped_products_quantities = $product->get_meta( 'xpv_grouped_products_quantities' );

			if ( $base_product->managing_stock() ) {
				wc_update_product_stock( $base_product_id, $item['qty'], 'increase' );
			}

			foreach ( $grouped_products_ids as $grouped_product_id ) {
				$grouped_product          = wc_get_product( $grouped_product_id );
				$grouped_product_quantity = $grouped_products_quantities[ $grouped_product->get_sku() ] * $item['qty'];

				if ( $grouped_product->managing_stock() ) {
					wc_update_product_stock( $grouped_product_id, $grouped_product_quantity, 'increase' );
				}
			}
		}
	}
}
