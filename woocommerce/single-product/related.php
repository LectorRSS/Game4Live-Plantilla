<?php
/**
 * Related Products
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $product, $woocommerce_loop;

$related = $product->get_related( $posts_per_page );

if ( sizeof( $related ) == 0 ) return;

$args = apply_filters( 'woocommerce_related_products_args', array(
	'post_type'				=> 'product',
	'ignore_sticky_posts'	=> 1,
	'no_found_rows' 		=> 1,
	'posts_per_page' 		=> $posts_per_page,
	'orderby' 				=> $orderby,
	'post__in' 				=> $related,
	'post__not_in'			=> array( $product->id )
) );

$products = new WP_Query( $args );

$woocommerce_loop['columns'] = $columns;

if ( $products->have_posts() ) : ?>

	<section class="related products even">

		<div class="widget">

			<div class="row">
				<div class="large-12 columns">
					<h2 class="widget-title"><span><?php _e( 'Related Products', 'radium' ); ?></span></h2>
				</div>
			</div>

            <div class="radium-product-carousel">

                <div class="horizontal-carousel-container">

                        <div class="control prev"></div>

                        <div class="horizontal-carousel">

                            <ul class="products" data-columns="5">

                				<?php while ( $products->have_posts() ) : $products->the_post(); ?>

                					<?php wc_get_template_part( 'content', 'product' ); ?>

                				<?php endwhile; // end of the loop. ?>

                			</ul>

                        </div>

                        <div class="control next"></div>

                    </div>

                </div><!-- end .radium-carousel -->

		  </div>

	</section>

<?php endif;

wp_reset_postdata();
