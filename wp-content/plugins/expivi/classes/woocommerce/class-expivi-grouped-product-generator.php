<?php
/**
 * Expivi Grouped Product Generator
 *
 * @package Expivi/WooCommerce
 */

defined( 'ABSPATH' ) || exit;

require_once XPV_ABSPATH . 'classes/helpers/class-expivi-configuration-helper.php';

/**
 * Class to generate grouped products.
 */
class Expivi_Grouped_Product_Generator {
	use Expivi_Configuration_Helper;

	/**
	 * Returns a grouped product.
	 *
	 * @param WC_Product $wc_product A WC product.
	 * @param mixed      $configured_product A single configured product.
	 * @param int        $calculated_price A price calculated by expivi.
	 * @param int        $selected_quantity Quantity.
	 *
	 * @return WC_Product|bool
	 */
	public function get_grouped_product( $wc_product, $configured_product, $calculated_price, $selected_quantity ) {
		if ( ! $wc_product || ! $wc_product->meta_exists( 'expivi_id' ) ) {
			return false;
		}

		$hash = $this->get_config_hash(
			$configured_product,
			$calculated_price,
			array(
				$wc_product->get_weight(),
			)
		);

		$wc_sku              = $wc_product->get_sku();
		$grouped_product_sku = sprintf( '%s-%s', null !== $wc_sku ? $wc_sku : '', $hash );
		$grouped_product_id  = wc_get_product_id_by_sku( $grouped_product_sku );
		$grouped_product     = false;

		if ( 0 === $grouped_product_id ) {
			$articles                  = $configured_product['articles'];
			$grouped_products_sku_list = array();

			foreach ( $articles as $article ) {
				if ( isset( $article['price_sku'] ) ) {
					$grouped_products_sku_list[] = $article['price_sku'];
				}
			}

			if ( empty( $grouped_products_sku_list ) ) {
				XPV()->log( 'Expivi Grouped Product Generator: Could not generate the product, no SKU\'s found in the product configuration.', Expivi::WARNING );
				return false;
			}

			$grouped_products_sku_qty = array_count_values( $grouped_products_sku_list );

			$grouped_product = $this->create( $wc_product, $grouped_products_sku_qty, $configured_product, $calculated_price, $selected_quantity );
		} elseif ( $wc_product->has_enough_stock( $selected_quantity ) ) {
			$grouped_product = wc_get_product( $grouped_product_id );

			$grouped_product->set_manage_stock( $wc_product->get_manage_stock() );
			$grouped_product->set_stock_quantity( $wc_product->get_stock_quantity() );
			$grouped_product->set_backorders( $wc_product->get_backorders() );
			$grouped_product->set_stock_status();
			$grouped_product->save();

			$grouped_products_sku_qty = $grouped_product->get_meta( 'xpv_grouped_products_quantities' );

			if ( false === $this->get_product_ids( $grouped_products_sku_qty, $selected_quantity ) ) {
				return false;
			}
		} else {
			XPV()->log( 'Expivi Grouped Product Generator: Could not retrieve grouped product, the product with the SKU ' . $grouped_product_sku . ' or one of its sub products is out of stock.', Expivi::INFO );
			return false;
		}

		return $grouped_product;
	}

	/**
	 * Creates a Grouped Product
	 *
	 * @param WC_Product $wc_product A WC product.
	 * @param mixed      $grouped_products_sku_qty An object that holds quantity information to each SKU.
	 * @param mixed      $configured_product A single configured product.
	 * @param int        $calculated_price A price calculated by expivi.
	 * @param int        $selected_quantity Quantity.
	 *
	 * @return WC_Product|bool
	 */
	private function create( $wc_product, $grouped_products_sku_qty, $configured_product, $calculated_price, $selected_quantity ) {
		$grouped_products_ids = $this->get_product_ids( $grouped_products_sku_qty, $selected_quantity );
		if ( false === $grouped_products_ids ) {
			return false;
		}

		$duplicate_product = new WC_Admin_Duplicate_Product();
		$product           = $duplicate_product->product_duplicate( $wc_product );
		$hash              = $this->get_config_hash(
			$configured_product,
			$calculated_price,
			array(
				$wc_product->get_weight(),
			)
		);

		$wc_product_name = $wc_product->get_name();
		$wc_product_sku  = $wc_product->get_sku();

		$total_weight = $wc_product->has_weight() ? $wc_product->get_weight() : 0;

		// Combine weight of all sub-products as they're not part of the cart.
		foreach ( $grouped_products_sku_qty as $sku => $quantity ) {
			$child_product_id = wc_get_product_id_by_sku( $sku );
			$child_product    = wc_get_product( $child_product_id );

			if ( $child_product->has_weight() ) {
				$total_weight += $child_product->get_weight();
			}
		}

		$product->set_name( null !== $wc_product_name ? $wc_product_name : '' );
		$product->set_price( $calculated_price );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'hidden' );
		$product->set_sku( sprintf( '%s-%s', null !== $wc_product_sku ? $wc_product_sku : '', $hash ) );
		$product->add_meta_data( 'xpv_grouped_products_ids', array_filter( wp_parse_id_list( (array) $grouped_products_ids ) ) );
		$product->add_meta_data( 'xpv_base_simple_product', $wc_product->get_id() );
		$product->add_meta_data( 'xpv_generated', true );
		$product->add_meta_data( 'xpv_grouped_products_quantities', $grouped_products_sku_qty );
		$product->set_weight( $total_weight );

		$grouped_product_id = $product->save();

		return wc_get_product( $grouped_product_id );
	}

	/**
	 * Returns products ids after validating SKU's between Expivi and Woocommerce.
	 *
	 * @param mixed $grouped_products_sku_qty An object that holds quantity information to each SKU.
	 * @param int   $selected_quantity Quantity.
	 *
	 * @return array|bool
	 */
	private function get_product_ids( $grouped_products_sku_qty, $selected_quantity ) {
		$products_ids = array();
		foreach ( $grouped_products_sku_qty as $sku => $quantity ) {
			$product_id = wc_get_product_id_by_sku( $sku );
			$product    = wc_get_product( $product_id );
			$quantity  *= $selected_quantity;

			if ( 0 === $product_id ) {
				XPV()->log( 'Expivi Grouped Product Generator: Could not generate grouped product, the product with the SKU ' . $sku . ' was not found.', Expivi::WARNING );
				return false;
			} elseif ( ! $product->has_enough_stock( $quantity ) ) {
				XPV()->log( 'Expivi Grouped Product Generator: Could not generate grouped product, the product with the SKU ' . $sku . ' is out of stock.', Expivi::INFO );
				return false;
			}

			$products_ids[] = $product_id;
		}
		return $products_ids;
	}
}
