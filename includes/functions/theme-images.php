<?php
/*
 * This file is a part of the RadiumFramework core.
 * Please be extremely cautious editing this file,
 *
 * @category RadiumFramework
 * @package  Carlton
 * @author   Franklin M Gitonga
 * @link     http://radiumthemes.com/
 */

/*  Custom thumbnail quality
/* ------------------------------------ */
function radium_thumbnail_quality( $quality ) {
    return 100;
}
add_filter( 'jpeg_quality', 'radium_thumbnail_quality' );
add_filter( 'wp_editor_set_quality', 'radium_thumbnail_quality' );

/*-----------------------------------------------------------------------------------*
* Radium Image Resize Based on Aqua Resizer https://github.com/sy4mil/Aqua-Resizer

* Title     : Aqua Resizer
* Description   : Resizes WordPress images on the fly
* Version   : 1.1.6
* Author    : Syamil MJ
* Author URI    : http://aquagraphite.com
* License   : WTFPL - http://sam.zoy.org/wtfpl/
* Documentation : https://github.com/sy4mil/Aqua-Resizer/
*
* @param string $url - (required) must be uploaded using wp media uploader
* @param int $width - (required)
* @param int $height - (optional)
* @param bool $crop - (optional) default to soft crop
* @param bool $single - (optional) returns an array if false
*
* @return str|array
/*-----------------------------------------------------------------------------------*/

if ( !function_exists( 'radium_resize' ) ) {

    function radium_resize( $url, $width, $height = null, $crop = null, $single = true, $quality = 100, $retina = false ) {

        if ( $retina ) {

            $width = ($width * 2);

            $height = isset( $height ) ?  ($height * 2) : null;

        }

        //validate inputs
        if(!$url OR !$width ) return false;

        //define upload path & dir
        $upload_info = wp_upload_dir();
        $upload_dir = $upload_info['basedir'];
        $upload_url = $upload_info['baseurl'];

        //check if $img_url is local
        if(strpos( $url, $upload_url ) === false) return false;

        //define path of image
        $rel_path = str_replace( $upload_url, '', $url);
        $img_path = $upload_dir . $rel_path;

        //check if img path exists, and is an image indeed
        if( !file_exists($img_path) OR !getimagesize($img_path) ) return false;

        //get image info
        $info = pathinfo($img_path);
        $ext = $info['extension'];
        list($orig_w,$orig_h) = getimagesize($img_path);

        //get image size after cropping
        $dims = image_resize_dimensions($orig_w, $orig_h, $width, $height, $crop);
        $dst_w = $dims[4];
        $dst_h = $dims[5];

        //use this to check if cropped image already exists, so we can return that instead
        $suffix = "{$dst_w}x{$dst_h}";
        $dst_rel_path = str_replace( '.'.$ext, '', $rel_path);
        $destfilename = "{$upload_dir}{$dst_rel_path}-{$suffix}.{$ext}";

        if(!$dst_h) {
            //can't resize, so return original url
            $img_url = $url;
            $dst_w = $orig_w;
            $dst_h = $orig_h;
        }
        //else check if cache exists
        elseif(file_exists($destfilename) && getimagesize($destfilename)) {
            $img_url = "{$upload_url}{$dst_rel_path}-{$suffix}.{$ext}";
        }
        //else, we resize the image and return the new resized image url
        else {

            $editor = wp_get_image_editor($img_path);

            if ( is_wp_error( $editor ) || is_wp_error( $editor->resize( $width, $height, $crop ) ) )
                return false;

            if ( $quality  )
            $editor->set_quality($quality);
            $resized_file = $editor->save();

            if(!is_wp_error($resized_file)) {
                $resized_rel_path = str_replace( $upload_dir, '', $resized_file['path']);
                $img_url = $upload_url . $resized_rel_path;
            } else {
                return false;
            }

        }

        //return the output
        if($single) {
            //str return
            $image = $img_url;
        } else {
            //array return
            $image = array (
                0 => $img_url,
                1 => $dst_w,
                2 => $dst_h
            );
        }

        // RETINA Support --------------------------------------------------------------->
        // Thanks to @wpexplorer
        // https://github.com/syamilmj/Aqua-Resizer/issues/36
        $retina_w = $dst_w*2;
        $retina_h = $dst_h*2;

        //get image size after cropping
        $dims_x2 = image_resize_dimensions($orig_w, $orig_h, $retina_w, $retina_h, $crop);
        $dst_x2_w = $dims_x2[4];
        $dst_x2_h = $dims_x2[5];

        // If possible lets make the @2x image
        if($dst_x2_h) {

            //@2x image url
            $destfilename = "{$upload_dir}{$dst_rel_path}-{$suffix}@2x.{$ext}";

            //check if retina image exists
            if(file_exists($destfilename) && getimagesize($destfilename)) {
                    // already exists, do nothing
            } else {
                // doesnt exist, lets create it
                $editor = wp_get_image_editor($img_path);

                if ( ! is_wp_error( $editor ) ) {
                    $editor->resize( $retina_w, $retina_h, $crop );
                    $editor->set_quality( 100 );
                    $filename = $editor->generate_filename( $dst_w . 'x' . $dst_h . '@2x'  );
                    $editor = $editor->save($filename);
                }

            }

        } //end retina

        return $image;
    }

}



/*
* Get First post image
*
* @param $fallback - set to true to show fallback image
*/

function get_radium_first_post_image( $fallback = '' ) {

	global $post, $posts;

	$framework = radium_framework();

	$first_img = null;

	$args = array(
		'numberposts' => 1,
		'order'=> 'ASC',
		'orderby' => 'menu_order',
		'post_mime_type' => 'image',
		'post_parent' => $post->ID,
		'post_status' => null,
		'post_type' => 'attachment'
	);

	$attachments = get_children( $args );

	if ( $attachments ) {

 		$count = count($attachments);
		$first_attachment = array_shift($attachments);

		if ( $first_attachment )
	   		$first_img = @wp_get_attachment_url( $first_attachment->ID, 'full' ); //get full URL to image (use "large" or "medium" if the image is too big)


	} else {

		ob_start();
		ob_end_clean();

		if ( preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches) ) {

			$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches);

			$first_img =  $matches[1][0];

		} else {

			$first_img = null;

		}

        $placeholder_url = apply_filters( 'radium_placeholder_url', $framework->theme_images_url . '/placeholder.gif' );

		if ( is_null($first_img) && $fallback ) //Defines a default image
			$first_img = $placeholder_url;

	}

	return $first_img;
}

/**
 * radium_placeholder_url
 *
 * @since  2.1.6
 *
 * @return $placeholder_url
 */
function radium_placeholder_url( ) {

	global $post;

    $framework = radium_framework();

    $post_id = is_object($post) ? get_the_ID() : 0;

    $placeholder_url = $framework->theme_images_url . '/placeholder.gif';

    if ( get_post_type($post_id) == 'post' ) {

        $placeholder_url = $framework->theme_images_url . '/placeholder-plain.gif';

    }

    return $placeholder_url;
}
add_filter( 'radium_placeholder_url', 'radium_placeholder_url' );

/**
 * radium_calculate_image_height
 *
 * @since  2.2.2
 *
 * @return $height
 */
function radium_calculate_image_height($srcWidth, $srcHeight, $maxWidth, $maxHeight) {

	$ratio1 = $maxWidth / $srcWidth;

	$ratio2 = $maxHeight / $srcHeight;

	$ratio = min($ratio1, $ratio2);

	$width = $srcWidth * $ratio;

	$height = $srcHeight * $ratio;

	return array($width, $height);

}
