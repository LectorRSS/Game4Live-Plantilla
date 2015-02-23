<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive.
 *
 * Override this template by copying it to yourtheme/woocommerce/archive-product.php
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

get_header('shop');

$framework = radium_framework();

$options = $framework->options;

$sidebar = radium_sidebar_loader(radium_get_option('woocommerce_archive_layout', false, 'left') );

$header_title = get_post_meta( get_the_ID(), '_radium_title', true );

$header_subtitle = get_post_meta( get_the_ID(), '_radium_subtitle', true );

if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>

    <header id="page-header">
        <div class="row">
            <div class="large-6 columns">
                <?php if ( $header_title !== "hide" ) { ?><h1 class="header effect-content"><?php woocommerce_page_title(); ?></h1><?php } ?>
            </div>
            <div class="large-6 columns">
                <?php do_action('radium_header_breadcrumb'); ?>
            </div>
        </div>
    </header>

<?php endif; ?>

<?php do_action('radium_before_page'); ?>

<div class="row shop-content">

    <main id="main" class="<?php echo $sidebar['content_class']; ?>" role="main">

        <div class="woocommerce-before-content" itemscope>
            <?php
                /**
                 * woocommerce_before_main_content hook
                 *
                 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
                 * @hooked woocommerce_breadcrumb - 20
                 */
                do_action('woocommerce_before_main_content');
            ?>

            <?php do_action( 'woocommerce_archive_description' ); ?>

		</div>

		<div class="woocommerce-content">

		<?php if ( have_posts() ) : ?>

			<?php
				/**
				 * woocommerce_before_shop_loop hook
				 *
				 * @hooked woocommerce_result_count - 20
				 * @hooked woocommerce_catalog_ordering - 30
				 */
				do_action( 'woocommerce_before_shop_loop' );
			?>

			<?php woocommerce_product_loop_start(); ?>

				<?php woocommerce_product_subcategories(); ?>

				<?php while ( have_posts() ) : the_post(); ?>

					<?php wc_get_template_part( 'content', 'product' ); ?>

				<?php endwhile; // end of the loop. ?>

			<?php woocommerce_product_loop_end(); ?>

			<?php
				/**
				 * woocommerce_after_shop_loop hook
				 *
				 * @hooked woocommerce_pagination - 10
				 */
				do_action( 'woocommerce_after_shop_loop' );
			?>

			<?php elseif ( ! woocommerce_product_subcategories( array( 'before' => woocommerce_product_loop_start( false ), 'after' => woocommerce_product_loop_end( false ) ) ) ) : ?>
	
				<?php wc_get_template( 'loop/no-products-found.php' ); ?>
	
			<?php endif; ?>
		
		</div>

		<?php
			/**
			 * woocommerce_after_main_content hook
			 *
			 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
			 */
			do_action( 'woocommerce_after_main_content' );
		?>

    </main>

    <?php if( $sidebar['sidebar_active'] ) { ?>

        <aside id="sidebar" class="<?php echo $sidebar['sidebar_class']; ?>">
            <div id="sidebar-main" class="sidebar">
                <?php
                    dynamic_sidebar('Woocommerce Sidebar'); // DISPLAY THE SIDEBAR
                    /**
                     * woocommerce_sidebar hook
                     *
                     * @hooked woocommerce_get_sidebar - 10
                     */
                    do_action('woocommerce_sidebar');
                ?>
            </div><!--sidebar-main-->
        </aside><!--sidebar-->

    <?php } ?>

</div><!--.row-->

<?php

do_action('radium_after_page');

get_footer('shop');

?>
