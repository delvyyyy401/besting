<?php
/**
 * Expivi Admin Order Manager
 *
 * @package Expivi/WooCommerce/Admin
 */

defined( 'ABSPATH' ) || exit;

require_once XPV_ABSPATH . 'classes/woocommerce/class-expivi-cart-process.php';

/**
 * Class to provide functionality to WC orders in admin panel.
 */
class Expivi_Admin_Order_Manager {

	/**
	 * Expivi_Admin_Order_Manager Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_admin_order_item_headers', array( $this, 'order_item_table_headers' ), 10, 1 );
		add_action( 'woocommerce_admin_order_item_values', array( $this, 'order_item_table_values' ), 10, 3 );
		add_filter( 'woocommerce_admin_order_item_thumbnail', array( $this, 'order_item_thumbnail' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
	}

	/**
	 * Render generated products for order item.
	 *
	 * @param int           $item_id Identifier of Order item.
	 * @param WC_Order_Item $item Item of order, usually WC_Order_Item_Product.
	 * @param WC_Order      $order Order.
	 *
	 * @return void
	 */
	public function order_item_sub_products( $item_id, $item, $order ) {
		if ( ! $item || ! ( $item instanceof WC_Order_Item_Product ) ) {
			return;
		}

		$product = $item->get_product();

		if ( ! $product || ! $product->get_meta( 'xpv_generated' ) ) {
			return;
		}

		$quantities           = $product->get_meta( 'xpv_grouped_products_quantities' );
		$grouped_products_ids = $product->get_meta( 'xpv_grouped_products_ids' );

		foreach ( $grouped_products_ids as $product_id ) {
			$grouped_product          = wc_get_product( $product_id );
			$grouped_product_quantity = $quantities[ $grouped_product->get_sku() ] * $item->get_quantity();

			if ( $grouped_product->managing_stock() && $grouped_product_quantity > $grouped_product->get_stock_quantity() ) {
				// translators: %1$s: Name of product.
				// translators: %2$s: Stock limit.
				$text = __( 'Sub-product %1$s stock limit (%2$s) reached!', 'expivi' );
				$html = '<div class="notice notice-warning"><p>' . $text . '</p></div>';
				echo sprintf( $html, $grouped_product->get_name(), $grouped_product->get_stock_quantity() );
			}
		}

		xpv_get_template(
			'admin/orders/order-item-generated-product.phtml',
			array(
				'item_id' => $item_id,
				'item'    => $item,
				'order'   => $order,
				'product' => $product,
			),
			false // Admin templates should not be overridable.
		);
	}

	/**
	 * Add headers to the order item table.
	 *
	 * @param WC_Order $order Order.
	 */
	public function order_item_table_headers( $order ) {
		if ( ! $order ) {
			return;
		}

		// Retrieve array of WC_Order_Item.
		$order_items = $order->get_items();

		// Check if order contains Expivi products.
		$contains_expivi_product = false;

		foreach ( $order_items as $order_item ) {
			if ( $order_item->meta_exists( 'xpv_configuration' ) || $order_item->meta_exists( 'configuration' ) ) {
				$contains_expivi_product = true;
				break;
			}
		}

		// Ignore when no Expivi product configurations are found.
		if ( ! $contains_expivi_product ) {
			return;
		}

		$this->load_scripts();

		// Render template when Order contains Expivi products.
		xpv_get_template( 'admin/orders/order_item_table_headers.phtml' );
	}


	/**
	 * Show options on each product line if necessary.
	 *
	 * @param WC_Product                    $product Product.
	 * @param WC_Order_Item|WC_Order_Refund $item Order Item.
	 * @param int                           $item_id Identifier of Order Item.
	 */
	public function order_item_table_values( $product, $item, $item_id ) {
		// false when it is a sub-product.
		if ( false === $item ) {
			return;
		}

		$options  = array();
		$articles = array();

		$configuration = null;
		if ( $item->meta_exists( 'xpv_configuration' ) ) {
			$configuration = $item->get_meta( 'xpv_configuration' );
		} elseif ( $item->meta_exists( 'configuration' ) ) { // Backwards compatibility.
			$configuration = $item->get_meta( 'configuration' );
		}

		// Check if order contains Expivi products.
		if ( is_null( $configuration ) ) {
			$order_items = array();

			try {
				// Note: Checking function for existance will not work as function
				// is available but marked as deprecated (including throwing exception).
				if ( is_a( $item, 'WC_Order_Refund' ) ) {
					$order = $item;
				} else {
					$order = $item->get_order();
				}

				// Retrieve array of WC_Order_Item.
				$order_items = $order->get_items();
			} catch ( Exception $ex ) {
				XPV()->log_exception( $ex );
			}

			// Check if order contains Expivi products.
			$contains_expivi_product = false;

			foreach ( $order_items as $order_item ) {
				if ( $order_item->meta_exists( 'xpv_configuration' ) || $order_item->meta_exists( 'configuration' ) ) {
					$contains_expivi_product = true;
					break;
				}
			}

			// Ignore when no Expivi product configurations are found.
			if ( ! $contains_expivi_product ) {
				return;
			}
		}

		if ( ! is_null( $configuration ) ) {
			foreach ( $configuration['attributes'] as $option ) {
				$options[] = array(
					'name'  => $option['attribute_name'],
					'value' => $option['attribute_value_name'],
				);
			}
			foreach ( $configuration['articles'] as $article ) {
				if ( ! empty( $article['price_sku'] ) ) {
					$articles[] = $article['price_sku'];
				}
			}
		}

		add_action( 'woocommerce_order_item_' . $item->get_type() . '_html', array( $this, 'order_item_sub_products' ), 10, 3 );

		$meta_data   = $item->meta_exists( 'xpv_meta_data' ) ? $item->get_meta( 'xpv_meta_data' ) : null;
		$dynamic_sku = xpv_array_get( $meta_data, 'dynamic_sku' );

		// Render template when Order contains Expivi products.
		xpv_get_template(
			'admin/orders/order_item_table_values.phtml',
			array(
				'options'     => $options,
				'articles'    => $articles,
				'dynamic_sku' => $dynamic_sku,
			)
		);
	}

	/**
	 * Replace order thumbnail with thumbnail from expivi configuration
	 *
	 * @param string                $product_get_image_thumbnail_array_title_false Html for displaying image.
	 * @param int                   $item_id Identifier of order item product.
	 * @param WC_Order_Item_Product $item Product of Order Item.
	 *
	 * @return string
	 */
	public function order_item_thumbnail( $product_get_image_thumbnail_array_title_false, $item_id, $item ) {
		$configuration = null;
		if ( $item->meta_exists( 'xpv_configuration' ) ) {
			$configuration = $item->get_meta( 'xpv_configuration' );
		} elseif ( $item->meta_exists( 'configuration' ) ) { // Backwards compatibility.
			$configuration = $item->get_meta( 'configuration' );
		}

		if ( ! is_null( $configuration ) ) {
			if ( array_key_exists( 'thumbnail', $configuration ) && ! empty( $configuration['thumbnail'] ) ) {
				$thumb_base64 = $configuration['thumbnail'];
			} else {
				$thumb_base64 = XPV()->plugin_url() . '/public/img/image-not-found.jpg';
			}

			if ( array_key_exists( 'thumbnail_url', $configuration ) && ! empty( $configuration['thumbnail_url'] ) ) {
				$thumb_url = $configuration['thumbnail_url'];
			}

			if ( isset( $thumb_url ) ) {
				$product_get_image_thumbnail_array_title_false =
					'<a target="_blank" href="' . htmlentities( trim( $thumb_url ) ) . '"><img src="' . htmlentities( trim( $thumb_base64 ) ) . '" width="128" height="128" class="expivi-order-item-thumbnail" /></a>';
			} else {
				$product_get_image_thumbnail_array_title_false = '<img src="' . htmlentities( trim( $thumb_base64 ) ) . '" width="128" height="128" class="expivi-order-item-thumbnail" />';
			}
		}
		return $product_get_image_thumbnail_array_title_false;
	}


	/**
	 * Register scripts/styles for admin orders.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function register_scripts( string $hook_suffix ) {
		// Block all requests except when we are on admin page Expivi settings.
		if ( ! is_admin() || ! ( 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix ) ) {
			return;
		}

		// Use plugin version for cache control, except when in debug mode.
		$plugin_version = defined( 'WP_DEBUG' ) && true === WP_DEBUG ? gmdate( 'h:i:s' ) : XPV_VERSION;

		if ( ! wp_style_is( 'expivi-plugin-style', 'registered' ) ) {
			wp_register_style( 'expivi-plugin-style', XPV()->plugin_url() . '/public/css/plugin.css', array(), $plugin_version, false );
		}
	}

	/**
	 * Load scripts/styles for admin orders.
	 */
	public function load_scripts() {
		if ( wp_style_is( 'expivi-plugin-style', 'registered' ) && ! wp_style_is( 'expivi-plugin-style', 'enqueued' ) ) {
			wp_enqueue_style( 'expivi-plugin-style' );
		}
	}
}
