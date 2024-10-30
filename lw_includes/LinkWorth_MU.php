<?php
class LinkWorth_MU
{
	function init()
	{
		$temp_site_id = LW_SITE_ID;
		$temp_site_hash = LW_SITE_HASH;

		if(!empty($temp_site_id) && !empty($temp_site_hash))
		{
			$this->add_website_ids();
			$this->update_blogs();

			// IF THE OPTIONS DOESN'T EXIST, THIS IS THE FIRST RUN.
			if(!get_site_option('lw_wpmu_blogs_lastupdate'))
			{
				$this->install();
			}

			//BILLBOARDS
			add_filter('query_vars', array($this,'addQueryVar'));
			//BILLBOARDS
			add_action('parse_query', array($this,'Query'));
		}
	}

	//INSTALL THE PLUGIN.
	function install()
	{
		global $wp_rewrite;

		//FLUSH THE REWRITE RULES.
		$wp_rewrite->flush_rules();
		get_site_option('lw_wpmu_blogs_lastupdate',1);
	}

	//ADD THE MOD_REWRITE RULES TO WORDPRESS. SO WE DON'T HAVE TO CREATE A FILE.
	function rewrite($wp_rewrite)
	{
		global $lw_options;

		$feed_rules = array('linkworth_mu/(.+)?' => 'index.php?linkworth_mu='. $wp_rewrite->preg_index(1));

		$wp_rewrite->rules = $feed_rules + $wp_rewrite->rules;
	}

	function addQueryVar($wpvar_array)
	{
		global $lw_options;

		$wpvar_array[] = 'linkworth_mu';

		return($wpvar_array);
	}

	//BEFORE WE DO EANYTHING ELSE STOP AND CHECK IF THIS IS A REQUEST FOR THE BLOG LIST
	function Query()
	{
		global $wp_rewrite, $lw_options;

		if (LW_SITE_HASH && get_query_var('linkworth_mu') == LW_SITE_HASH)
		{
			$this->blog_list();
		}
	}

	function update_blogs()
	{
		$last = get_site_option('lw_wpmu_blogs_lastblogsupdate');
		$count = get_site_option('lw_wpmu_blogs_lastcount');
		$waiting = get_site_option('lw_wpmu_blogs_waiting');

		if((time() - $last) > 60*60*5 && $waiting != 1 && $count != get_blog_count())
		{
			@ini_set('default_socket_timeout', 10);

			$url = 'http://www.linkworth.com/act/partner/code/plugin_feed.php?ping=1&web_id=' . LW_SITE_ID . '&hash=' . LW_SITE_HASH;

            $lw_blog_string = false;
            $response = wp_remote_get($url);
            $http_code = wp_remote_retrieve_response_code($response);

            if ($http_code == '200') {

                $lw_blog_string = wp_remote_retrieve_body($response);

                if ($lw_blog_string) {

                    update_site_option('lw_wpmu_blogs_waiting',1);

                } else {

                    update_site_option('lw_wpmu_blogs_lastblogsupdate',time()-600); //Delay the update for 10 minutes incase of server issues.
                }

            } else {

                update_site_option('lw_wpmu_blogs_lastblogsupdate',time()-600); //Delay the update for 10 minutes incase of server issues.
            }
		}
	}

	// LOOPS THROUGH THE WEBSITE IDS AND ADD THEM FOR EACH BLOG.
	function add_website_ids()
	{
		global $lw_debug_information;

		$last = get_site_option('lw_wpmu_lastupdate');

		if((time() - $last) > 36000 || isset($_GET['lw_update_now']))
		{
			@ini_set('default_socket_timeout', 10);

            $lw_blog_string = false;

			//PREVENT FUNCTION CALL IF CONSTANTS ARE NOT SET
			if (defined('LW_SITE_ID') && defined('LW_SITE_HASH')) {

                $url = 'http://www.linkworth.com/act/partner/code/plugin_feed.php?web_id=' . LW_SITE_ID . '&hash=' . LW_SITE_HASH . '&format=xml&wpmu=1';

				$$lw_debug_information .= $url . '++';

                $response = wp_remote_get($url);
                $http_code = wp_remote_retrieve_response_code($response);

                if ($http_code == '200') {

                    $lw_blog_string = wp_remote_retrieve_body($response);

                    if ($lw_blog_string) {

                        update_site_option('lw_wpmu_blogs_waiting',1);
                    }
                }

			} else {

				$lw_debug_information .= 'Constant not set++';
			}

			if(isset($lw_blog_string))
			{
				include_once('LinkWorth_parser.php');

				$xml_parser = new LW_XML();
				$blogs_list = $xml_parser->parse($lw_blog_string);

				update_option('lw_cache_time',time());

				//ONE SITE RETURNS ONE LESS DIMENSION IN ARRAY
				if(array_key_exists('id',$blogs_list['linkworth']['sites']['site']))
				{
					$blogs = $blogs_list['linkworth']['sites'];
				}
				else
				{
					$blogs = $blogs_list['linkworth']['sites']['site'];
				}

				$lw_debug_information .= print_r(get_blog_details(1),true).'++';

				if(count($blogs) > 0)
				{
					foreach($blogs as $blog)
					{
						//GET SUBDOMAIN OR DIRECTORY NAME
						$blog_url = str_replace(array('http://','www.'),'',$blog['url']);
						$blog_url_array = explode('.', $blog_url);
						$url = $blog_url_array[0];
						//GET BLOG ID.
						$blog_id = get_id_from_blogname($url);

						$lw_debug_information .= print_r($blog_url_array,true).'++';
						$lw_debug_information .= $blog['url']."\n\r".$url.'++';

						//CHECK FOR SUBBLOG FIRST - PREVENTS MAIN BLOG FROM GETTING A SUB-BLOG'S ID AND HASH
						if($blog_id)
						{
							$lw_op = get_blog_option($blog_id, 'lw_options');
							$lw_op['site_id'] = LW_SITE_ID;
							$lw_op['site_hash'] = LW_SITE_HASH;
							$lw_op['website_id'] = $blog['id'];
							$lw_op['website_hash'] = $blog['hash'];

							update_blog_option($blog_id, 'lw_options', $lw_op);
						}
						//CHECK FOR MAIN BLOG - WILL NOT BE SET IF CONSTANTS EQUAL A SUB-BLOG'S ID AND HASH
						elseif($blog['id'] == LW_SITE_ID)
						{
							$lw_op = get_blog_option(1, 'lw_options');
							$lw_op['site_id'] = LW_SITE_ID;
							$lw_op['site_hash'] = LW_SITE_HASH;
							$lw_op['website_id'] = $blog['id'];
							$lw_op['website_hash'] = $blog['hash'];

							update_blog_option(1, 'lw_options', $lw_op);
						}
					}
				}

				//DELAY THE UPDATE FOR 10 MINUTES INCASE OF SERVER ISSUES.
				update_site_option('lw_wpmu_lastupdate',time());
			}
			else
			{
				//DELAY THE UPDATE FOR 10 MINUTES INCASE OF SERVER ISSUES.
				update_site_option('lw_wpmu_lastupdate',time()-33000);
			}
		}
	}

	function blog_list()
	{
		global $wpdb;

		update_site_option('lw_wpmu_blogs_waiting',0);
		update_site_option('lw_wpmu_blogs_lastblogsupdate',time());
		update_site_option('lw_wpmu_blogs_lastcount',get_blog_count());

		$blogs = $wpdb->get_results( $wpdb->prepare("SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = %d AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' ORDER BY registered DESC", $wpdb->siteid), ARRAY_A );

		foreach($blogs as $blog)
		{
			$the_blogs[] = $blog['domain'].$blog['path'];
		}

		if(is_array($the_blogs))
		{
			echo implode("\n",$the_blogs);
		}

		exit;
	}

	function update_all_options($ops)
	{
		$blogs = get_blog_list();

		unset($ops['applytoall'], $ops['site_id'], $ops['site_hash'],$ops['website_id'], $ops['website_hash']);

		foreach($blogs as $blog)
		{
			update_blog_option( $blog['blog_id'], 'lw_options', $ops);
		}

		$this->add_website_ids(1);
	}
}

$LinkWorth_MU = new LinkWorth_MU;
// INITIATE LW. PASSED BY REFERENCE FOR PHP4
add_action('init', array(&$LinkWorth_MU, 'init'),11);
//LW REQUESTS
add_filter('generate_rewrite_rules', array($LinkWorth_MU,'rewrite'));
?>