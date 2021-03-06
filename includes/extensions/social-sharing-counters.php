<?php
/*
 * This file is a part of the RadiumFramework core.
 *
 * @category RadiumFramework
 * @package  NewsCore
 * @author   Franklin M Gitonga
 * @link     http://radiumthemes.com/
 */
 
/*
 * SOCIAL SHARE COUNTERS
 */
 
/**
 * radium_get_twitter_followers 
 * @param  [type] $twitter_user [description]
 * @param  [type] $cache_time   [description]
 * @return [type]               [description]
 */

function radium_get_twitter_followers($cache_time = 60) {

    $options = get_option( 'radium_tweets_settings' );
		
    // CHECK SETTINGS & DIE IF NOT SET
    if( empty($options['consumerkey']) || empty($options['consumersecret']) ){
         return 0;
    }

    // some variables
    $consumerKey = $options['consumerkey'];
    $consumerSecret = $options['consumersecret'];
    $token = get_option('cfTwitterToken');

    // get follower count from cache
    $followers = get_transient('radium_overall_twitter_followers');

    // cache version does not exist or expired
    if (false === $followers) {

        // getting new auth bearer only if we don't have one
        if(!$token) {
        
            // preparing credentials
            $credentials = $consumerKey . ':' . $consumerSecret;
            $toSend = base64_encode($credentials);

            // http post arguments
            $args = array(
                'method' => 'POST',
                'httpversion' => '1.1',
                'blocking' => true,
                'timeout'   => 30,
                'headers' => array(
                    'Authorization' => 'Basic ' . $toSend,
                    'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'
                ),
                'body' => array( 'grant_type' => 'client_credentials' )
            );

            add_filter('https_ssl_verify', '__return_false');
            $response = wp_remote_post('https://api.twitter.com/oauth2/token', $args);

            $keys = json_decode(wp_remote_retrieve_body($response));

            if($keys) {
                // saving token to wp_options table
                update_option('cfTwitterToken', $keys->access_token);
                $token = $keys->access_token;
            }
        }

        // we have bearer token wether we obtained it from API or from options
        $args = array(
            'httpversion' => '1.1',
            'blocking' => true,
            'timeout'   => 30,
            'headers' => array(
                'Authorization' => "Bearer $token"
            )
        );

        add_filter('https_ssl_verify', '__return_false');
        $api_url = "https://api.twitter.com/1.1/users/show.json?screen_name={$options['username']}";
        $response = wp_remote_get($api_url, $args);

        if (!is_wp_error($response)) {

            $followers = json_decode(wp_remote_retrieve_body($response));
            $followers = $followers->followers_count;

        } else {
        
        	return 0;
        	
        }

        // cache for an hour
        set_transient('radium_overall_twitter_followers', $followers, $cache_time * 60);
     
     } 

    return $followers;
}


/**
 * [radium_get_facebook_likes description]
 * @param  [type] $account [description]
 * @param  [type] $cache_time    [description]
 * @return [type]                [description]
 */
function radium_get_facebook_likes( $account, $cache_time = 60) {

    $transient_name = 'radium_overall_facebook_likes';
    $fbData = '';
	
	if ( $account == '' || $account == '#') return apply_filters(__FUNCTION__, 0);
 	
    if ($cache_time == 0) delete_transient($transient_name);
 
    if (false === ( $shares = get_transient($transient_name) )) {
        
        $json = wp_remote_get("http://graph.facebook.com/" . $account, array('timeout' => 30));

        if (is_wp_error($json)) {
        	
            return 0;
        
        } else {
                        
            $fbData = json_decode($json['body'], true);
 			
			$shares = isset( $fbData['likes'] ) ? $fbData['likes'] : false;
			
			if($shares)
				set_transient($transient_name, intval($shares), $cache_time * 60);
			        
			return $shares;
		}
        
    } else {
    
        return $shares;
    
    }

}


/**
 * [radium_gplus_count get googe plus circled count]
 * @param  [type] $gplus_username [description]
 * @param  [type] $gplus_api      [description]
 * @param  [type] $cache_time     [description]
 * @return [type]                 [description]
 */
function radium_gplus_count( $account, $cache_time = 60 ) {
		 	    
	$shares = 0;
	
	if ( $account == '' || $account == '#') return apply_filters(__FUNCTION__, 0);

    $transient_name = 'radium_overall_google_pluses';
    $google_plus_count = '';

    if ($cache_time == 0) 
        delete_transient($transient_name);
    
    if ( false === ( $shares = get_transient($transient_name) ) ) {

        // Google Plus.
        $link = "https://plus.google.com/".$account;

        $gplus = array(
            'method'    => 'POST',
            'sslverify' => false,
            'timeout'   => 30,
            'headers'   => array( 'Content-Type' => 'application/json' ),
            'body'      => '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $link . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]'
        );

        $remote_data = wp_remote_get( 'https://clients6.google.com/rpc', $gplus );

        if (is_wp_error($remote_data)) {

            return "0";

        } else {

            $json_data = json_decode( $remote_data['body'], true );
            
            $shares = isset($json_data[0]['result']['metadata']['globalCounts']) ? $json_data[0]['result']['metadata']['globalCounts'] : false; 
            
            if ( $shares && is_array( $shares ) ) {
 
	            foreach($shares as $gcount){
	
	                $shares = $gcount;
	
	            }
             
            } else {
            
            	return "0";
            
            }

            if ( !$shares) {

                $link = "https://plus.google.com/".$account."/posts";

                $page = wp_remote_fopen($link);

                if (preg_match('/>([0-9,]+) people</i', $page, $matches))
                    $shares = str_replace(',', '', $matches[1]);

            }
						
			if($google_plus_count)
            	set_transient($transient_name, intval($shares), $cache_time * 60);
		
            return $shares;

        }

    } else {

        return $shares;

    }

}


//dribbble fans count
function radium_get_total_dribbble_followers ($account, $return = 'count', $cache_time = 60) {
	
	if ( $account == '' || $account == '#') return apply_filters(__FUNCTION__, 0);

	$count =  get_transient('radium_overall_dribbble_followers');
	$link =  get_transient('radium_overall_dribbble_page_url');
	if ($return == 'link') {
		if ($link !== false) return $link;
	} else {
		if ($count !== false) return $count;
	}
	
	$count = 0;
	$link = '';
	
	$args = array(
	    'timeout'     => 30,
	    'sslverify'   => true,
	);
	
	$data = wp_remote_get('http://api.dribbble.com/players/'.$account, $args);
	if (!is_wp_error($data)) {
		$json = json_decode( $data['body'], true );
		$count = intval($json['followers_count']);
		$link = $json['url'];
                set_transient('radium_overall_dribbble_followers', $count, $cache_time * 60);
                set_transient('radium_overall_dribbble_page_url', $link, $cache_time * 60);
    }
    if ($return == 'link') {
        return $link;
    } else {
        return $count;
    }
}

//youtube fans count
function radium_get_total_youtube_subscribers ($account, $return = 'count', $cache_time = 60) {
   
   	if ( $account == '' || $account == '#') return apply_filters(__FUNCTION__, 0);
   
	$count =  get_transient('radium_overall_youtube_followers');
	$link =  get_transient('radium_overall_youtube_page_url');
	
	if ($return == 'link') {
	
		if ($link !== false) return $link;
	
	} else {

		if ($count !== false) return $count;
	}
	 
	$count = 0;
	$link = '';

	$args = array(
	    'timeout'     => 30,
	    'sslverify'   => true,
	);
	
	$data = wp_remote_get('http://gdata.youtube.com/feeds/api/users/'.$account.'?alt=json', $args);
	if (!is_wp_error($data)) {
	
		$json = json_decode( $data['body'], true );
		$count = intval($json['entry']['yt$statistics']['subscriberCount']);
		$link = $json['entry']['link'][0]['href'];
        set_transient('radium_overall_youtube_followers', $count, $cache_time * 60);
        set_transient('radium_overall_youtube_page_url', $link, $cache_time * 60);
    
    }
    
    if ($return == 'link') {
    
        return $link;
        
    } else {
    
        return $count;
        
    }
    
}

//vimeo fans count
function radium_get_total_vimeo_followers ($account, $return = 'count', $cache_time = 60) {

	if ( $account == '' || $account == '#') return apply_filters(__FUNCTION__, 0);

	$count =  get_transient('radium_overall_vimeo_followers');
	$link = get_transient('radium_overall_vimeo_page_url');
	
	if ($return == 'link') {
	
		if ($link !== false) return $link;
	
	} else {
	
		if ($count !== false) return $count;
	
	}
	
	$count = 0;
	$link = '';
	
	$args = array(
	    'timeout'     => 30,
	    'sslverify'   => true,
	);
	
	$data = wp_remote_get('http://vimeo.com/api/v2/channel/'.$account.'/info.json', $args);
		
	if (!is_wp_error($data)) {
	
		$json = json_decode( $data['body'], true );
		
		$count = intval($json['total_subscribers']);
		
		$link = $json['url'];
		
	    set_transient('radium_overall_vimeo_followers', $count, $cache_time * 60);
	    set_transient('radium_overall_vimeo_page_url', $link, $cache_time * 60);
	
	} else {
	
		$count = 0;
	}
	
	return ($return == 'link') ? $link : $count;
}


//soundcloud fans count
function radium_get_total_soundcloud_fans ($account, $return = 'count', $cache_time = 60) {
   	
   	if ( $account == '' || $account == '#') return apply_filters(__FUNCTION__, 0);
   	
	$count =  get_transient('radium_overall_soundcloud_followers');
	$link =  get_transient('radium_overall_soundcloud_page_url');
	if ($return == 'link') {
	if ($link !== false) return $link;
	} else {
	if ($count !== false) return $count;
	}
	$count = 0;
	$link = '';
	$client_id = radium_get_option('soundcloud_clientid');
	if ($client_id != '') {
		
		$args = array(
		    'timeout'     => 30,
		    'sslverify'   => true,
		);
		
		$data = wp_remote_get('http://api.soundcloud.com/users/'.$account.'.json?client_id='.$client_id, $args);
	    if (!is_wp_error($data)) {
			$json = json_decode( $data['body'], true );
			$count = intval($json['followers_count']);
			$link = $json['permalink_url'];
	                set_transient('radium_overall_soundcloud_followers', $count, $cache_time * 60);
	                set_transient('radium_overall_soundcloud_page_url', $link, $cache_time * 60);
	    }
	}
	return ($return == 'link') ? $link : $count;

}

//behance fans count
function radium_get_total_behance_followers ($account, $return = 'count', $cache_time = 60) {

	if ( $account == '' || $account == '#') return apply_filters(__FUNCTION__, 0);
   
	$count =  get_transient('radium_overall_behance_followers');
	$link =  get_transient('radium_overall_behance_page_url');
	if ($return == 'link') {
	if ($link !== false) return $link;
	} else {
	if ($count !== false) return $count;
	}
	$count = 0;
	$link = '';
	$api_key = radium_get_option('behance_api_key');
	if ($api_key != '') {
		$args = array(
		    'timeout'     => 30,
		    'sslverify'   => true,
		);
		$data = wp_remote_get('https://www.behance.net/v2/users/'.$account.'?api_key='.$api_key, $args);
	    if (!is_wp_error($data)) {
			$json = json_decode( $data['body'], true );
			$count = intval($json['user']['stats']['followers']);
			$link = $json['user']['url'];
	                set_transient('radium_overall_behance_followers', $count, $cache_time * 60);
	                set_transient('radium_overall_behance_page_url', $link, $cache_time * 60);
	    }
	}
    if ($return == 'link') {
        return $link;
    } else {
        return $count;
    }
}

//instagram fans count
function radium_get_total_instagram_followers ($account, $return = 'count', $cache_time = 60) {
   	
   	if ( $account == '' || $account == '#') return apply_filters(__FUNCTION__, 0);
   
	$count =  get_transient('radium_overall_instagram_followers');
	$link =  get_transient('radium_overall_instagram_page_url');
	
	if ($return == 'link') {
		if ($link !== false) return $link;
	} else {
		if ($count !== false) return $count;
	}
	
	$count = 0;
	$link = '';
	
	$access_token = radium_get_option('instagram_access_token');
	$instID = '';
	
	if ($access_token != '') {
	
		$args = array(
		    'timeout'     => 30,
		    'sslverify'   => true,
		);
		
		//instagram
		$instID_url = wp_remote_get('https://api.instagram.com/v1/users/search?q='.$account.'&access_token='.$access_token, $args);
	
		if (!is_wp_error($instID_url)) {
			$instID_json = json_decode( $instID_url['body'], true );
			$instID = $instID_json['data'][0]['id'];
		    
		}
		
		$response = wp_remote_get('https://api.instagram.com/v1/users/'.$instID.'/?access_token='.$access_token);
		
		$xml = $response['body'];
			
		if( is_wp_error( $xml ) )
			return "0";
			
		if (!is_wp_error($response)) {
		        $json = json_decode( $xml, true );
		        $count = intval($json['data']['counts']['followed_by']);
		        $link = 'http://instagram.com/'.$account;
		        set_transient('radium_overall_instagram_followers', $count, $cache_time * 60);
		        set_transient('radium_overall_instagram_page_url', $link, $cache_time * 60);
		    } 
		}
		
	    if ($return == 'link') {
	        return $link;
	    } else {
	        return $count;
	    }
	    
}
 
// pinterest
function radium_get_total_pinterest_followers ($account, $cache_time = 60) {
   	
   	if ( $account == '' || $account == '#') return apply_filters(__FUNCTION__, 0);
        
    $count =  get_transient('radium_overall_pinterest_followers');
    
    if ($count !== false) 
    	return $count;
        
	$pin_metas = @get_meta_tags($account);
		
	if (isset($pin_metas['pinterestapp:followers'])) {
		$count = $pin_metas['pinterestapp:followers'];
	} else {
		$count = $pin_metas['followers'];
	}
    
    set_transient('radium_overall_pinterest_followers', $count, $cache_time * 60);
     
    return $count;        

}

/////////////// SOCIAL COUNTER FOR SINGLE POSTS ////////////
/* single Article Social Counters */
function radium_single_get_tweets( $url = null, $cache_time = 60 ) {
	
	$shares = 0;
	
	$post_id = get_the_ID();
	
	$transient_name = 'radium_single_get_tweets_'. $post_id;
	
    $url = $url ? $url : get_permalink();
    
	$url = apply_filters(__FUNCTION__.'_url', $url);
    
    if ($cache_time == 0)
        delete_transient($transient_name);
    
    if ($cache_time == '')
        $cache_time = 60;
    
    if ( false === ( $shares = get_transient($transient_name) ) ) {
    
	    $args = array(
	        'timeout'     => 30,
	        'sslverify'   => true,
	    ); 
	
	    $response = wp_remote_get('http://cdn.api.twitter.com/1/urls/count.json?url=' . $url, $args);
	    
	    if( is_wp_error( $response ) )
	     	return "0";
	    
		$xml = $response['body'];
			
		if( is_wp_error( $xml ) )
			return "0";
	
		$json = json_decode( $xml, true );
	    
 	    $shares = isset( $json['count'] ) ?  $json['count']  : false;
	    
	    if($shares)
	    	set_transient($transient_name, $shares, $cache_time * 60);
	            
	    return $shares;
	    
    } else {
        
    	return $shares;
    
    }
    
}

/**
 * [radium_single_get_shares description]
 * @param  [type] $url [description]
 * @return [type]      [description]
 */
function radium_single_get_linkedin_shares( $url = null, $cache_time = 60 ) {

 	$shares = 0;

    $post_id = get_the_ID();
    
    $transient_name = 'radium_single_get_linkedin_'. $post_id;
     
    $url = $url ? $url : get_permalink();
    
	$url = apply_filters(__FUNCTION__.'_url', $url);
    
	if ($cache_time == 0)
	    delete_transient($transient_name);
	
	if ($cache_time == '')
	    $cache_time = 60;
	
	if ( false === ( $shares = get_transient($transient_name) ) ) {
		
		$args = array(
		    'timeout'     => 30,
		    'sslverify'   => true,
		);
		
	    $response = wp_remote_get("http://www.linkedin.com/countserv/count/share?url=$url&format=json", $args);
	    
	    if( is_wp_error( $response ) )
	    	return "0";
	        
		$xml = $response['body'];
	
		if( is_wp_error( $xml ) )
			return;
	
		$json = json_decode( $xml, true );
		
		$shares = isset( $json['count'] ) ?  $json['count']  : false;
		
		if($shares)
	    	set_transient($transient_name, $shares, $cache_time * 60);
	            
        return $shares;
        
    } else {
        
    	return $shares;
    
    }
    
}


/**
 * [radium_single_get_likes description]
 * @param  [type] $url [description]
 * @return [type]      [description]
 */
function radium_single_get_facebook_likes( $url = null, $cache_time = 60 ) {
 	
 	$shares = 0;
	
	$post_id = get_the_ID();
	
	$transient_name = 'radium_single_get_facebook_'. $post_id;
		
	$url = $url ? $url : get_permalink();
	
	$url = apply_filters(__FUNCTION__.'_url', $url);
	
	if ($cache_time == 0)
	    delete_transient($transient_name);
	
	if ($cache_time == '')
	    $cache_time = 60;
	    	    	
	if ( false === ( $shares = get_transient($transient_name) ) ) {
		
		$args = array(
		    'timeout'     => 30,
		    'sslverify'   => true,
		);
		
    	$response = wp_remote_get('http://graph.facebook.com/?ids=' . $url, $args);
    	
    	if( is_wp_error( $response ) )
    		return "0";
    	        
		$xml = $response['body'];
	
		if( is_wp_error( $xml ) )
			return "0";
	
		$json = json_decode( $xml, true );
    	
    	$shares = isset( $json[$url]['shares'] ) ? intval( $json[$url]['shares'] ) : false;
    	
    	if ( $shares ) {
    	
    		set_transient($transient_name, $shares, $cache_time * 60);
    		return $shares;
    		
    	} else {
    	
    		return 0;
    		
    	}
    	
    } else {
    
    	return $shares;
    
    }
}

/**
 * [radium_single_get_plusones description]
 * @param  [type] $url [description]
 * @return [type]      [description]
 */
function radium_single_get_plusones( $url = null, $cache_time = 60 ) {

 	$shares = 0;
	
	$post_id = get_the_ID();
	
	$transient_name = 'radium_single_get_googleplus_'. $post_id;

    $url = $url ? $url : get_permalink();
    
	$url = apply_filters(__FUNCTION__.'_url', $url);
	
	if ($cache_time == 0)
	    delete_transient($transient_name);
	
	if ($cache_time == '')
	    $cache_time = 60;
	
	if ( false === ( $shares = get_transient($transient_name) ) ) {

	     // Google Plus.
        $args = array(
            'method'    => 'POST',
            'sslverify' => false,
            'timeout'   => 30,
            'headers'   => array( 'Content-Type' => 'application/json' ),
            'body'      => '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $url . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]'
        );

        $response = wp_remote_get( 'https://clients6.google.com/rpc', $args );
		
		if( is_wp_error( $response ) )
		    return "0";
		        
		$xml = $response['body'];
		
		if( is_wp_error( $xml ) )
			return "0";
 		
		$json = json_decode( $xml, true );
		
		$shares = isset( $json[0]['result']['metadata']['globalCounts']['count'] ) ? intval( $json[0]['result']['metadata']['globalCounts']['count'] ) : false;
	    
	    if ( $shares )
	    	set_transient($transient_name, $shares, $cache_time * 60);
		
	    return intval( $shares );
	    
    } else {
        
    	return $shares;
    
    }
}


/**
 * [radium_single_get_pins description]
 * @param  [type] $url [description]
 * @return [type]      [description]
 */
function radium_single_get_pins( $url = null, $cache_time = 60 ) {

 	$shares = 0;
	
	$post_id = get_the_ID();
	
	$transient_name = 'radium_single_get_pins_'. $post_id;

    $url = $url ? $url : get_permalink();
    
	$url = apply_filters(__FUNCTION__.'_url', $url);
	
	if ($cache_time == 0)
	    delete_transient($transient_name);
	
	if ($cache_time == '')
	    $cache_time = 60;
	
	if ( false === ( $shares = get_transient($transient_name) ) ) {
	
	     // Pininterest Pins.
        $args = array(
            'method'    => 'POST',
            'sslverify' => false,
            'timeout'   => 30,
            'headers'   => array( 'Content-Type' => 'application/json' ),
        );

        $response = wp_remote_get( 'http://api.pinterest.com/v1/urls/count.json?callback=receiveCount&url='.$url, $args );
		
		if( is_wp_error( $response ) )
		    return "0";
		        
		$xml = $response['body'];
		
		if( is_wp_error( $xml ) )
			return "0";

 		$xml = preg_replace('/^receiveCount\((.*)\)$/', "\\1", $xml);
 		
		$json = json_decode( $xml, true );
		
		$shares = isset( $json['count'] ) ? intval( $json['count'] ) : false;
				     
        if ( $shares )
        	set_transient($transient_name, $shares, $cache_time * 60);
    	
        return intval( $shares );
        
    } else {
        
    	return $shares;
    
    }
}

function radium_single_get_stumble( $url = null, $cache_time = 60 ) {
	
	$shares = 0;
	
	$post_id = get_the_ID();
	
	$transient_name = 'radium_single_get_stumble_'. $post_id;

    $url = $url ? $url : get_permalink();
    
	$url = apply_filters(__FUNCTION__.'_url', $url);
	
	if ($cache_time == 0)
	    delete_transient($transient_name);
	
	if ($cache_time == '')
	    $cache_time = 60;
	
	if ( false === ( $shares = get_transient($transient_name) ) ) {
	
	     // Pininterest Pins.
        $args = array(
            'sslverify' => false,
            'timeout'   => 30,
        );

		$response = wp_remote_get('http://www.stumbleupon.com/services/1.01/badge.getinfo?url='.$url, $args);
		
		if( is_wp_error( $response ) )
		    return "0";
		        
		$xml = $response['body'];
		
		if( is_wp_error( $xml ) )
			return "0";
 		
		$json = json_decode( $xml, true );
		
		$shares = isset($json['result']['views'])?intval($json['result']['views']): false;
 				     
        if ( $shares )
        	set_transient($transient_name, $shares, $cache_time * 60);
    	
        return intval( $shares );
        
    } else {

    	return $shares;
    
    }
}
