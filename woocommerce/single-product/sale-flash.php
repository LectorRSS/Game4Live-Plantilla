<?php
/**
 * Single Product Sale Flash
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $post, $product;
?>
<?php if ( $product->is_on_sale() ) : ?>

	 <div class="callout large">
            <div class="inner">
              <div class="inner-text"><?php echo apply_filters( 'woocommerce_sale_flash', '<span class="onsale">' . __( 'Sale!', 'radium' ) . '</span>', $post, $product ); ?></div>
            </div>
     </div><!-- end callout -->

<?php endif; ?>
