<?php

/**
 * Edit handler for topics
 *
 * @package radium framework
 * @subpackage Theme
 */

get_header();

$sidebar = radium_sidebar_loader(radium_get_option('bbpress_layout', false, 'right') );

get_template_part( 'includes/content/content', 'header' ); 

?>
<div class="row">

    <main class="content <?php echo $sidebar['content_class']; ?>"  role="main" itemprop="mainContentOfPage" itemscope="itemscope" itemtype="http://schema.org/Article">

    	<?php while ( have_posts() ) : the_post(); ?>

    		<div id="bbp-edit-page" class="bbp-edit-page">
    			<div class="entry-content">

    				<?php bbp_get_template_part( 'form', 'topic' ); ?>

    			</div>
    		</div><!-- #bbp-edit-page -->

    	<?php endwhile; ?>

    </main><!-- #main -->

    <?php if( $sidebar['sidebar_active'] ) { ?>

    <aside class="<?php echo $sidebar['sidebar_class']; ?>" role="complementary" itemscope="itemscope" itemtype="http://schema.org/WPSideBar">
		<div id="sidebar-main" class="sidebar">
			<?php get_sidebar('Internal Sidebar'); ?>
		</div><!--sidebar-main-->
	</aside>

    <?php } ?>

</div><!-- END .row -->

<?php get_footer(); ?>