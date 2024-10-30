<?php
/*
Plugin Name: LinkWorth
Plugin URI: http://www.linkworth.com
Description: LinkWorth Easy Link Syndication for WordPress and WPMU.  <a href="https://www.linkworth.com/wp_plug_in/">Check for latest version</a>.
Author: LinkWorth
Version: 3.3.5
Author URI: http://www.linkworth.com/
License: GPLv2 or later
*/

/* Copyright 2008 LinkWorth (support@linkworth.com).
This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as
published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the
Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

//PLUGINS ARE NOT INITIALIZED IN GLOBAL SPACE, SO WE MUST DEFINE GLOBALS AS SUCH.
global $lw_linkads, $lw_debug_information;
$lw_linkads = array();
$lw_debug_information = 'Debug Information:++';

class LinkWorth
{
	//INTITIALIZE THE PLUGIN. DEFINE VARIABLES, ADD ACTIONS, OPTIONALLY INSTALL THE PLUGIN.
	function init()
	{
		global $lw_linkads, $wp_rewrite, $lw_options, $lw_vercurrent, $wp_query, $lw_debug_information;
		$lw_vercurrent = '319';

		add_action( 'admin_menu', array( &$this, 'admin_menu_item' ) );
		add_action( 'admin_menu', array( $this, 'admin_init' ) );
		//GET PLUGIN OPTIONS
		$lw_options = get_option( 'lw_options' );
		//IF THE OPTIONS ARRAY DOESN'T EXIST, THIS IS THE FIRST RUN.
		if( !is_array( $lw_options ) || empty( $lw_options ) )
		{
			$this->install();
		}
		else
		{
			$this->upgrade();
		}

		$this->set_constants();
		$this->get_ads( $lw_update_ads = false );

		//SET BILLBOARD BASE
		if( empty( $lw_options ) || !isset( $lw_options['billboard_base'] ) )
		{
			$lw_options['billboard_base'] = 'pages';
		}

		//DISABLE SILENT
		if( isset( $_GET['silent'] ) || isset( $_GET['debug'] ) )
		{
			$lw_options['disable_silent'] = 1;
		}

		//ADD IDENTIFICATION DIV
		add_filter( 'get_footer', array( &$this, 'lw_identification' ) );

		//SET UP DEALS IF AVALIABLE
		if( is_array( $lw_linkads ) && !empty( $lw_linkads ) )
		{
			$this->setup_filters_and_actions();
		}
		else
		{
			//DELETE ANY OLD ADS
			$this->lw_linkintxt_dbcleanup();
			$lw_linkads = array();

			$lw_debug_information .= 'No LinkWorth Deals Found.++';
		}

		if( isset( $_POST['lw_update_deal_list'] ) && is_array( $lw_linkads ) )
		{
			//UPDATE THE BILLBOARD ADS
			$this->billboards_update();
			//UPDATE THE LINKINTXT
			$this->linkintxts_update();
		}

		//SAVING CHANGES
		add_filter( 'edit_post', array( $this, 'update_post_options' ) );
		add_filter( 'publish_post', array( $this, 'update_post_options' ) );
		add_filter( 'save_post', array( $this, 'update_post_options' ) );
	}

	// ---------------------------------------------------------------------------------------
	// ------------------------------- FUNCTIONS CALLED BY INT() -----------------------------
	// ---------------------------------------------------------------------------------------

	//ADDS LW MENU ITEM TO WP DASHBOARD
	function admin_menu_item()
	{
		add_menu_page( 'LinkWorth Ads Syndication', 'LinkWorth', 8, 'lw-settings', 'lw_config_admin_page' );
		add_submenu_page( 'lw-settings', 'LinkWorth Ads Syndication', 'General', 8, 'lw-settings', 'lw_config_admin_page' );
		add_submenu_page( 'lw-settings', 'LinkWorth Ads Syndication', 'Advanced', 8, 'lw-advanced-settings', 'lw_config_admin_page' );
	}

	function admin_init()
	{
		//add_meta_box( 'linkworth_enable', 'Linkworth Options', array( $this, 'post_options' ), 'post', 'side' );
		//add_meta_box( 'linkworth_enable', 'Linkworth Options', array( $this, 'post_options' ), 'page', 'side' );
	}

	//INSTALL THE PLUGIN.
	function install()
	{
		global $wp_rewrite, $lw_vercurrent;

		//STORE AN ARRAY OF VALUES INTO THE DATABASE.
		$options['lw_timeout'] = 5;
		$options['lw_linkcolor'] = 1;
		$options['lw_version'] = $lw_vercurrent;

		//FLUSH THE REWRITE RULES.
		$wp_rewrite->flush_rules();
		add_option( 'lw_options', $options);
		add_option( 'lw_widget' );
		add_option( 'lw_cache', '');
		add_option( 'lw_cache_time', '');
	}

	//UPGRADE THE PLUGIN
	function upgrade()
	{
		global $lw_vercurrent, $wp_rewrite;

		$lw_version = get_option( 'linkworth_version' );

		if( $lw_version == $lw_vercurrent )
		{
			return;
		}

		if( $lw_version < 300 )
		{
			if( !$wp_rewrite->using_mod_rewrite_permalinks() )
			{
				$this->save_mod_rewrite_rules();
			}
		}

		if( $lw_version < 310 )
		{
			$this->delete_sidebar_links();
		}

		update_option( 'linkworth_version', $lw_vercurrent );
	}

	//DEFINE THE CONSTANTS & OPTIONS IF THEY ARE NOT ALREADY SET.
	function set_constants()
	{
		global $lw_options;

		//IF THE CONSTANTS ARE NOT DEFINED, DEFINE THEM.
		if( !defined( 'LW_SITE_ID' ) || !defined(' LW_SITE_HASH' ) )
		{
			if( $lw_options['site_id'] && $lw_options['site_hash'] )
			{
				define( 'LW_SITE_ID', $lw_options['site_id'] );
				define( 'LW_SITE_HASH', $lw_options['site_hash'] );;
			}
			else
			{
				define( 'LW_SITE_ID', '' );
				define( 'LW_SITE_HASH', '' );
			}
		}
		else
		{
			//IF THE OPTIONS ARE NOT DEFINED, DEFINE THEM.
			if( !$lw_options['site_id'] || !$lw_options['site_hash'] )
			{
				$lw_options['site_id'] = LW_SITE_ID;
				$lw_options['site_hash'] = LW_SITE_HASH;
			}
		}

		//IF THE CONSTANTS ARE NOT DEFINED, DEFINE THEM.
		if( !defined('LW_WEBSITE_ID' ) || !defined( 'LW_HASH' ) )
		{
			if( $lw_options['website_id'] && $lw_options['website_hash'] )
			{
				define( 'LW_WEBSITE_ID', $lw_options['website_id'] );
				define( 'LW_HASH', $lw_options['website_hash'] );
			}
			else
			{
				define( 'LW_WEBSITE_ID', '' );
				define( 'LW_HASH', '' );
			}
		}
		else
		{
			//IF THE OPTIONS ARE NOT DEFINED, DEFINE THEM.
			if( !$lw_options['website_id'] || !$lw_options['website_hash'] )
			{
				$lw_options['website_id'] = LW_WEBSITE_ID;
				$lw_options['website_hash'] = LW_HASH;
			}
		}
	}

	//GET LIST OF DEALS
	function get_ads( $lw_update_ads )
	{
		global $lw_options, $lw_linkads, $lw_debug_information;

		$lw_debug_information .= 'get_ads() running++';

		$lw_linkads = get_option( 'lw_cache' );
		$last = get_option( 'lw_cache_time' );

		if( !is_array( $lw_linkads ) || empty( $lw_linkads ) || (time() - $last) > 3600 || $lw_update_ads || isset( $_GET['debug'] ) )
		{
			ini_set( 'default_socket_timeout', 10 );

			//PREVENT FUNCTION CALL IF CONSTANTS ARE NOT SET
			if( defined( 'LW_WEBSITE_ID' ) && defined( 'LW_HASH' ) )
			{
				$lw_advstring = $this->get_contents( 'http://www.linkworth.com/act/partner/code/plugin_feed.php?web_id=' . LW_WEBSITE_ID . '&hash=' . LW_HASH . '&format=xml' );
			}

			if( isset( $lw_advstring ) && !empty( $lw_advstring ) )
			{
				$lw_advstring = str_replace( '&amp;', '&', $lw_advstring);
				$lw_advstring = str_replace( '&amp;', '&', $lw_advstring);
				$lw_advstring = str_replace( '&amp;', '&', $lw_advstring);
				$lw_advstring = str_replace( '&', '&amp;', $lw_advstring);

				include_once( 'lw_includes/LinkWorth_parser.php' );

				$xml_parser = new LW_XML();
				$lw_linkads = $xml_parser->parse($lw_advstring);

				//DOES SITE ALLOW DESCRIPTIONS
				$lw_link_description = $lw_linkads['linkworth']['linkdesctiption'];
				//RESYNC ARRAY TO START WITH THE AD BLOCKS;
				$lw_linkads = $lw_linkads['linkworth']['ads'];
				$lw_options['linkdesctiption'] = $lw_link_description;

				update_option( 'lw_options', $lw_options );
				update_option( 'lw_cache', $lw_linkads );
				update_option( 'lw_cache_time', time() );
			}
			else
			{
				//DELAY THE UPDATE FOR 10 MINUTES INCASE OF SERVER ISSUES.
				update_option('lw_cache_time',time()-3000);
			}
		}
	}

	//JUST ADD THE FILTERS AND ACTIONS.
	function setup_filters_and_actions()
	{
		global $wp_rewrite, $lw_options;

		if( ( !is_home() || !is_front_page() ) && is_single() && ( isset( $lw_options['display_tagged'] ) && $lw_options['display_tagged'] == 1 ) )
		{
			//LINKINTXT IN TAGS
			add_filter( 'the_tags', array( &$this, 'lw_posttags') );
		}
		else
		{
			//LINKINTXT THROUGH CONTENT
			add_filter( 'the_content', array( &$this, 'lw_contentreplace' ) );
		}
		//BILLBOARDS
		add_filter( 'query_vars', array( $this, 'addQueryVar' ) );
		add_action( 'parse_query', array( $this, 'Query' ) );
		//NOT USING THE WIDGET
		if( !isset( $lw_options['lw_sidebarwidget'] ) || $lw_options['lw_sidebarwidget'] != 1 )
		{
			if( isset( $lw_options['loop_number'] ) && $lw_options['loop_number'] > 0 )
			{
				//ADD DEALS AFTER LOOP NUMBER
				add_filter( 'the_content', array( &$this, 'lw_publish_deals' ) );
			}
			else
			{
				//ADD DEALS AFTER CONTENT
				add_filter( 'get_footer', array( &$this, 'lw_publish_deals' ) );
			}
		}
	}

	// ---------------------------------------------------------------------------------------
	// ---------------------------------------------------------------------------------------
	// ---------------------------------------------------------------------------------------

	function clean_description( $description )
	{
		$description = trim( $description );
		$description = htmlspecialchars( $description );

		return $description;
	}

    function get_contents($url)
    {
        global $lw_debug_information;

        $string = '';
        $wp_remote_get_error = '';

        $lw_debug_information .= 'get_contents() running - '.$url.'++';

        if (!empty($url)) {

            $response = wp_remote_get($url);
            $http_code = wp_remote_retrieve_response_code($response);

            if ($http_code == '200') {

                $string = wp_remote_retrieve_body($response);

            } else {

                $wp_remote_get_error = 'get_contents() error: ' . wp_remote_retrieve_header($response, 'status');
            }
        }

        if (isset($_GET['debug'])) {

            if (!empty($wp_remote_get_error)) {

                $lw_debug_information .= $wp_remote_get_error.'++';
            }

            if (empty($string)) {

                $lw_debug_information .= 'get_contents() empty++';
            }
        }

        return $string;
    }

	// ---------------------------------------------------------------------------------------
	// ---------------------------------- BILLBOARD FUNCTIONS --------------------------------
	// ---------------------------------------------------------------------------------------

	function detectBillboard( $posts )
	{
		global $wp, $wp_query;

		$billboard = trim( get_query_var( 'linkworth' ), '/' );
		//GET THE BILLBOARDS ARRAY.
		$lw_billboards = get_option( 'lw_billboards' );

		//CHECK IF THE REQUESTED BILLBOARD EXISTS.
		if( $billboard && $lw_billboards[$billboard] )
		{
			if( $lw_billboards[$billboard]['in_theme'] != 1 )
			{
				$this->full_page_billboards();
			}

			//ADD THE FAKE POST
			$posts=NULL;
			$posts[]=$this->createPost();

			//TRICK WP_QUERY INTO THINKING THIS IS A PAGE (NECESSARY FOR WP_TITLE() AT LEAST)
			$wp_query->is_page = true;
			//NOT SURE IF THIS ONE IS NECESSARY BUT MIGHT AS WELL SET IT LIKE A TRUE PAGE
			$wp_query->is_singular = true;
			$wp_query->is_home = false;
			$wp_query->is_archive = false;
			$wp_query->is_category = false;
			//LONGER PERMALINK STRUCTURES MAY NOT MATCH THE FAKE POST SLUG AND CAUSE A 404 ERROR SO WE CATCH THE ERROR HERE
			unset($wp_query->query['error']);
			$wp_query->query_vars['error'] = '';
			$wp_query->is_404=false;
		}

		return $posts;
	}

	function createPost()
	{
		/**
		 * What we are going to do here, is create a fake post.  A post
		 * that doesn't actually exist. We're gonna fill it up with
		 * whatever values you want.  The content of the post will be
		 * the output from your plugin.
		 */

		//CREATE A FAKE POST.
		$post = new stdClass;
		$post->post_author = 1;
		$post->post_title = '';
		$post->post_content = $this->get_billboard();
		$post->ID = -1;
		$post->post_status = 'static';
		$post->comment_status = 'closed';
		$post->ping_status = 'closed';
		$post->comment_count = 0;
		$post->post_date = '13 Dec 1991 20:45:54 GMT';
		$post->post_date_gmt = '13 Dec 1991 20:45:54 GMT';

		return( $post );
	}

	//IF THIS IS A BILLBOARD PAGE, FUNCTION OUTPUTS THE BILLBOARD PAGE AND STOPS THE REST OF WP.
	function full_page_billboards()
	{
		$billboard = trim( get_query_var( 'linkworth' ), '/' );
		//GET THE BILLBOARDS ARRAY.
		$lw_billboards = get_option( 'lw_billboards' );

		//CHECK IF THE REQUESTED BILLBOARD EXISTS.
		if( $billboard && $lw_billboards[$billboard] )
		{
			echo $this->get_contents('http://www.linkworth.com/act/partner/code/linkbb.php?bb_id='.$lw_billboards[$billboard]['url'].'&web_id='.LW_WEBSITE_ID."&hash=".LW_HASH);
			die();
		}
	}

	function get_billboard()
	{
		$billboard = trim( get_query_var( 'linkworth' ), '/' );
		//GET THE BILLBOARDS ARRAY.
		$lw_billboards = get_option( 'lw_billboards' );

		$content = $this->get_contents('http://www.linkworth.com/act/partner/code/linkbb.php?bb_id='.$lw_billboards[$billboard]['url'].'&web_id='.LW_WEBSITE_ID."&hash=".LW_HASH."&in_page=1");

		$content = substr($content, strpos($content,'<body>')+6, strpos($content,'</body>')-strpos($content,'<body>'));

		return $content;
	}

	//ADD THE MOD_REWRITE RULES TO WORDPRESS. - SO WE DON'T HAVE TO CREATE A FILE.
	function rewrite( $wp_rewrite )
	{
		global $lw_options;

		$feed_rules = array($lw_options['billboard_base'].'/(.+)?' => 'index.php?linkworth='. $wp_rewrite->preg_index(1));

		$wp_rewrite->rules = $feed_rules + $wp_rewrite->rules;
	}

	function addQueryVar($wpvar_array)
	{
		global $lw_options;

		$wpvar_array[] = 'linkworth';

		return($wpvar_array);
	}

	function AddRewriteRules()
	{
		global $lw_options;

		$home_root = parse_url( get_option( 'home' ) );
		if( isset( $home_root['path'] ) )
		{
			$home_root = trailingslashit( $home_root['path'] );
		}
		else
		{
			$home_root = '/';
		}

		$insert = <<<block

	<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteBase $home_root
	RewriteRule ^$lw_options[billboard_base]/(.+)$ index.php?linkworth=$1
	</IfModule>
block;

		return $insert;
	}

	function do_pretty_links()
	{
		global $wp_rewrite;

		if( $wp_rewrite->using_mod_rewrite_permalinks() )
		{
			return 1;
		}

		if( get_option('linkworth_rules_added') )
		{
			return 1;
		}

		return 0;
	}

	function save_mod_rewrite_rules()
	{
		update_option( 'linkworth_rules_added', 0 );
		$home = get_option( 'home' );
		$siteurl = get_option( 'siteurl' );

		if( $home != '' && $home != $siteurl )
		{
			$wp_path_rel_to_home = str_replace( $home, '', $siteurl ); /* $siteurl - $home */
			$pos = strpos( $_SERVER['SCRIPT_FILENAME'], $wp_path_rel_to_home );
			$home_path = substr( $_SERVER['SCRIPT_FILENAME'], 0, $pos );
			$home_path = trailingslashit( $home_path );
		}
		else
		{
			$home_path = ABSPATH;
		}

		$htaccess_file = $home_path.'.htaccess';

		//IF THE FILE DOESN'T ALREADY EXISTS CHECK FOR WRITE ACCESS TO THE DIRECTORY AND WHETHER OF NOT WE HAVE SOME RULES.
		//ELSE CHECK FOR WRITE ACCESS TO THE FILE.
		if( ( !file_exists( $htaccess_file ) && is_writable( $home_path ) && $this->AddRewriteRules() ) || is_writable( $htaccess_file ) )
		{
			if( apache_mod_loaded( 'mod_rewrite', true ) )
			{
				$rules = explode( "\n", $this->AddRewriteRules() );
				include_once( $home_path.'wp-admin/includes/misc.php' );
				update_option( 'linkworth_rules_added', 1 );
				return insert_with_markers( $htaccess_file, 'LinkWorth', $rules );
			}
		}

		return false;
	}

	//BEFORE WE DO EANYTHING ELSE, STOP AND CHECK IF THIS IS A "BILLBOARD"
	function Query( $query )
	{
		global $wp_rewrite;

		if( get_query_var( 'linkworth' ) )
		{
			add_filter( 'the_posts', array( &$this, 'detectBillboard' ) );
		}
	}

	//LOOP THROUGH THE ADS AND UPDATE THE BILLBOARD OPTION WITH ANY NEW ONES.
	function billboards_update()
	{
		global $lw_linkads;
		$lw_billboards = array();

		if( !is_array( $lw_linkads ) || !is_array( $lw_linkads['linkbb'] ) || !is_array( $lw_linkads['linkbb']['ad'] ) )
		{
			update_option( 'lw_billboards', array() );
			return;
		}

		$billboards = $lw_linkads['linkbb']['ad'];

		if( !is_array( $billboards[0] ) )
		{
			$an_ad = $billboards;
			$billboards = null;
			$billboards[0] = $an_ad;
		}

		if( is_array( $billboards ) )
		{
			foreach( $billboards as $billboard )
			{
				$lw_billboards[$billboard['pagename']] = array( 'url' => $billboard['bbid'], 'in_theme' => $billboard['in_theme'] );
			} //WHILE BILLBOARD CREATION LOOP ENDS
		}

		update_option( 'lw_billboards', $lw_billboards );
	}

	//LOOP THROUGH THE ADS AND CHECK FOR NEW LINKINTXTS. IF THERE ARE NEW LINKS, ADD THEM INTO AN ARAY.
	function linkintxts_update()
	{
		global $lw_linkads, $lw_options;
		//NEW LINKS
		$linkintxts = array();

		if( !is_array( $lw_linkads['linkintxt'] ) || !is_array( $lw_linkads['linkintxt']['ad'] ) )
		{
			update_option( 'lw_linkintxts', array() );
			return;
		}

		$links = $lw_linkads['linkintxt'];

		if( is_array( $lw_linkads ) )
		{
			reset( $lw_linkads );
		}

		if( count( $links ) > 0 )
		{
			foreach( $links as $link )
			{
				$anchor_text = $link['anchor'];
				$protocol = ($link['use_https']) ? 'https://' : 'http://';

				if( isset( $link['link_title'] ) && !empty( $link['link_title'] ) )
				{
					$lw_title = ' title="' . $this->clean_description( $link['link_title'] ) . '" ';
				}

				$replace_word = '<a href="' . $protocol.$link['url'] . '"' . $lw_title . '>' .$link['anchor'] . '</a>';
				$disp_url = $link['webpageurl'];

				if( !empty( $anchor_text ) )
				{
					$linkintxts[] = array( 'find_word' => "$anchor_text", 'replace_word' => "$replace_word", 'disp_url' => "$disp_url" );
				}

				unset( $anchor_text, $replace_word, $disp_url );
			}
		}

		update_option('lw_linkintxts', $linkintxts);
	}

	// ---------------------------------------------------------------------------------------
	// ---------------------------------- VARIOUS UTILITY FUNCTIONS --------------------------
	// ---------------------------------------------------------------------------------------

	//PERFORMS VARIOUS TESTS ON THE THEME.
	function testtheme()
	{
		static $site_html;

		$support_array = array('can_get_ads' => 0, 'footer' => 0, 'loop' => array( 'exists' => 0, 'count' => 0 ) );

		//LOAD THE WEBSITE ONLY ONCE PER TEST RUN.
		if( !isset( $site_html ) || empty( $site_html ) || isset( $_GET['debug'] ) )
		{
			$site_url = get_option( 'siteurl' ) . '/?silent';
			$site_html = $this->get_contents( $site_url );
		}

		if( function_exists( 'wp_remote_get' ) )
		{
			$support_array['can_get_ads'] = 1;
		}

		if( !empty( $site_html ) )
		{
			if( preg_match( '/(?:\<\!\-\-\ lw\-loop\-count\:)(\d+)(?:\ \-\-\>)/i', stripslashes($site_html), $matches ) )
			{
				$support_array['loop']['exists'] = 1;
				$support_array['loop']['count'] = intval( $matches[1] );
			}
		}

		return $support_array;
	}

	function cloudcss( $lw_linkcolor )
	{
		if( !isset( $lw_linkcolor ) || $lw_linkcolor < 1 || $lw_linkcolor > 7 )
		{
			$lw_linkcolor = 8;
		}

		$lw_font_key = rand( 1, 3 );

		$lw_color_array = array(
			1 => array( 1 => "#00AEFF", 2 => "#594C9B", 3 => "#008C2E" ),
			2 => array( 1 => "#798975", 2 => "#81FF60", 3 => "#2C8B14" ),
			3 => array( 1 => "#737B87", 2 => "#77AFFF", 3 => "#14438B" ),
			4 => array( 1 => "#877673", 2 => "#FF7160", 3 => "#8B2014" ),
			5 => array( 1 => "#912DAE", 2 => "#53A244", 3 => "#DF723F" ),
			6 => array( 1 => "#FFFF40", 2 => "#C0C0FF", 3 => "#FF80FF" ),
			7 => array( 1 => "#606060", 2 => "#A8A8A8", 3 => "#E2E2E2" ),
			8 => array( 1 => "#FF0000", 2 => "#FF0000", 3 => "#FF0000" )
		);

		$css_color = $lw_color_array[$lw_linkcolor][$lw_font_key];

		return $css_color;
	}

	function post_options()
	{

	}

	function update_post_options()
	{
		if( $_POST['action'] == 'autosave' )
		{
			return;
		}

		$id = $_REQUEST['post_ID'];

		delete_post_meta( $id, '_disable_linkworth' );

		if( $_POST['disable_linkworth'] )
		{
			add_post_meta( $id, '_disable_linkworth', $_POST['disable_linkworth'] );
		}
	}

	//UPDATES ADMIN PANEL OPTIONS FROM POSTED DATA
	function update_options( $postdata )
	{
		while( list( $name, $value ) = each( $postdata ) )
		{
			if( get_magic_quotes_gpc() )
			{
				$value = stripslashes( $value );
			}

			$options[$name] = $value;
		}

		update_option( 'lw_options', $options );

		return $options;
	}

	// ---------------------------------------------------------------------------------------
	// ------------------------------ FUNCTION TO DISPLAY DEALS ------------------------------
	// ---------------------------------------------------------------------------------------

	function lw_publish_deals()
	{
		global $lw_linkads, $lw_options, $wp_query;

		//GET CURRENT LOOP NUMBER
		if( in_the_loop() )
		{
			$current_loop_number = $wp_query->current_post + 1;
		}

		//IGNORE DEALS ON POSTS
		if( is_single() && $lw_options['nocontentads'] == 1 )
		{
			return;
		}
		//NOT THE CORRECT LOOP NUMBER
		elseif( ( is_home() || is_front_page() ) && isset( $lw_options['loop_number'] ) && $lw_options['loop_number'] > 0 && $lw_options['loop_number'] != $current_loop_number )
		{
			return;
		}

		//LOOP THE DIFFERENT AD TYPES
		foreach( $lw_linkads as $deal_type => $ads )
		{
			if( $deal_type == 'linkads' || $deal_type == 'linkbb' || $deal_type == 'linkmura' )
			{
				if( !is_array( $ads['ad'][0] ) )
				{
					$an_ad = $ads['ad'];
					$ads['ad'] = null;
					$ads['ad'][0] = $an_ad;
				}

				//LOOP THROUGH ALL THE ADS OF THE SAME TYPE
				foreach( $ads['ad'] as $ad )
				{
					//HOME PAGE CHECK
					if( ( !is_home() || !is_front_page() ) && $ad['location'] == 1 )
					{
						continue;
					}
					//SECOND PAGE CHECK
					if( ( is_home() || is_front_page() ) && ( preg_match( '/\/page\//i', $page_uri ) || ( isset( $_GET['paged'] ) && $_GET['paged'] != 1 ) || ( isset( $_GET['p'] ) && $_GET['p'] != 1 ) ) )
					{
						continue;
					}
					//SUBPAGE CHECK
					if( ( is_home() || is_front_page() ) && $ad['location'] == 2 )
					{
						continue;
					}

					//RUN THE DISPLAY FUNCTION FOR THIS $AD_TYPE (FUNCTIONS ARE NAMED AFTER THE XML NAMES.)
					$ad_html .= $this->$deal_type( $ad, $count );
					$count++;
				}
			}
			else
			{
				continue;
			}
		}

		echo '<div id="lw-plugin-output" style="padding:10px 0; clear:both;">' . $ad_html . '</div>';
	}

	function lw_links_sidebar()
	{
		global $lw_linkads, $lw_options;

		$lw_path = get_option( 'siteurl' );

		if( isset( $lw_linkads ) )
		{
			reset( $lw_linkads );
		}

        $num_linkads = (isset($lw_linkads['linkads']) && is_array($lw_linkads['linkads'])) ? count($lw_linkads['linkads']) : 0;
        $num_linkbb = (isset($lw_linkads['linkbb']) && is_array($lw_linkads['linkbb'])) ? count($lw_linkads['linkbb']) : 0;
        $num_linkmura = (isset($lw_linkads['linkmura']) && is_array($lw_linkads['linkmura'])) ? count($lw_linkads['linkmura']) : 0;

		if (($num_linkads + $num_linkbb + $num_linkmura) > 0 )
		{
			foreach( $lw_linkads as $deal_type => $ads )
			{
				if( $deal_type == 'linkads' || $deal_type == 'linkbb' || $deal_type == 'linkmura' )
				{
					if( !is_array( $ads['ad'][0] ) )
					{
						$ad = $ads['ad'];
						$ads['ad'] = null;
						$ads['ad'][0] = $ad;
					}

					//LOOP THROUGH ALL THE ADS OF THE SAME TYPE.
					foreach( $ads['ad'] as $ad )
					{
						if( is_numeric( $ad['location'] ) )
						{
							$page_uri = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8');

							if( is_home() || is_front_page() )
							{
								$location_home = true;
							}
							else
							{
								$location_home = false;
							}

							//HOME PAGE CHECK
							if( $ad['location'] == 1 && ( !$location_home || ( ( $location_home ) && ( preg_match( '/\/page\//i', $page_uri ) || ( isset( $_GET['paged'] ) && $_GET['paged'] != 1 ) || ( isset( $_GET['p'] ) && $_GET['p'] != 1 ) ) ) ) )
							{
								continue;
							}
							//SUBPAGE CHECK
							if( $ad['location'] == 2 && $location_home )
							{
								continue;
							}

							$ad_html .= '<li>' . preg_replace( '/style="[^"]*"/', '', $this->$deal_type( $ad, 0 ) ) . '</li>';
							$count++;
						}
						else
						{
							continue;
						}
					}
				}
				else
				{
					continue;
				}
			}
		}

		return $ad_html;
	}

	//STYLES THE SAME WAY AS A WIDGET WOULD.
	function fake_widget( $args )
	{
		global $lw_linkads, $lw_options;

		$options = get_option( 'lw_widget' );
		$title_li = isset($options['title']) ? $options['title'] : '';

		$links = $this->lw_links_sidebar();

		extract( $args, EXTR_SKIP );

		$content .= $before_widget;
		$content .= "$before_title$title_li$after_title\n\t<ul class='xoxo blogroll'>\n";
		$content .= $links;
		$content .= '</ul>'.$after_widget;

		return $content;
	}

	// ---------------------------------------------------------------------------------------
	// ---------------------------------- AD DISPLAY FUNCTIONS -------------------------------
	// ---------------------------------------------------------------------------------------

	function linkbb( $ad, $count = 0 )
	{
		if( !is_array( $ad ) || empty( $ad ) )
		{
			return;
		}

		global $id, $lw_options;

		$lw_path = get_option( 'siteurl' );
		if( isset( $ad['link_title'] ) && !empty( $ad['link_title'] ) )
		{
			$lw_title = ' title="' . $this->clean_description( $ad['link_title'] ) . '" ';
		}
		$this->link_options( $count, $ad );

		if( !$this->do_pretty_links() )
		{
			$url = $lw_path . '?' . $lw_options['billboard_base'] . '=' . $ad['pagename'];
		}
		else
		{
			$url = $lw_path . '/' . $lw_options['billboard_base'] . '/' . $ad['pagename'];
		}

		$ads =  $lw_options['lw_seperator'] . '<a href="' . $url . '"' . $lw_options['lw_style'] . $lw_options['rel'] . $lw_title . '>' . $ad['anchor'] . '</a>';

		if( $ad['link_text'] != '' )
		{
			$ads = sprintf( $ad['link_text'], $ads );
		}

		return $ads;
	}

	function linkads( $ad, $count = 0 )
	{
		if( !is_array( $ad ) || empty( $ad ) )
		{
			return;
		}

		global $lw_options, $lw_debug_information;

		$this->link_options( $count, $ad );

		//SET UP THE LINK PARTS
		$protocol = ( $ad['use_https'] ) ? 'https://' : 'http://';
		$lw_seperator = $lw_options['lw_seperator'];
		$lw_ad_url = $ad['url'];
		$lw_style = $lw_options['lw_style'];
		$lw_rel = $lw_options['rel'];
		$lw_anchor = html_entity_decode($ad['anchor']);

		if( isset( $ad['link_title'] ) && !empty( $ad['link_title'] ) )
		{
			$lw_title = ' title="' . $this->clean_description( $ad['link_title'] ) . '" ';
		}

		if( $lw_options['linkdesctiption'] == 1 )
		{
			if( isset( $ad['description'] ) && !empty( $ad['description'] ) )
			{
				$lw_description = ' - ' . $this->clean_description( $ad['description'] ) . ' ';
			}
		}

		$ads = $lw_seperator . '<a href="' . $protocol . $lw_ad_url . '"' . $lw_style . $lw_rel . $lw_title . '>' . $lw_anchor . '</a>' . $lw_description;
		unset( $lw_title, $lw_description );

		if( !empty( $ad['link_text'] ) )
		{
			$ads = sprintf( $ad['link_text'], $ads );
		}

		return $ads;
	}

	function linkmura( $ad, $count = 0 )
	{
		if( !is_array( $ad ) || empty( $ad ) )
		{
			return;
		}

		global $lw_options;

		//NUMBER OF ANCHOR TEXTS
		$lw_arraysize = count( $ad['hyperlinks']['hyperlink'] );
		//PICK A RANDOM ANCHOR TEXT
		$lw_mura_rand = rand(0,$lw_arraysize-1);
		$ad = $ad['hyperlinks']['hyperlink'][$lw_mura_rand];

		$this->link_options( $count, $ad );

		$protocol = ( $ad['use_https'] ) ? 'https://' : 'http://';
		$lw_seperator = $lw_options['lw_seperator'];
		$lw_style = $lw_options['lw_style'];
		$lw_rel = $lw_options['rel'];

		if( isset( $ad['link_title'] ) && !empty( $ad['link_title'] ) )
		{
			$lw_title = ' title="' . $this->clean_description( $ad['link_title'] ) . '" ';
		}

		if( $lw_options['linkdesctiption'] == 1 )
		{
			if( isset( $ad['description'] ) && !empty( $ad['description'] ) )
			{
				$lw_description = ' - ' . $this->clean_description( $ad['description'] );
			}
		}

		$linkmura_link =  $lw_seperator . '<a href="' . $protocol . $ad['url'] . '"' . $lw_style . $lw_options['rel'] . $lw_title . '>' . $ad['anchor'] . '</a>' . $lw_description;
		unset( $lw_title, $lw_description );

		return $linkmura_link;
	}

	//SETS STYLE, REL AND SEPERATOR FOR EACH LINK.
	function link_options( $count, $ad )
	{
		global $lw_options;

		$lw_options['rel'] = '';
		$lw_options['lw_style'] = '';
		$lw_options['lw_seperator'] = '';

		if( isset( $lw_options['lw_linkcolor'] ) && !empty( $lw_options['lw_linkcolor'] ) && is_numeric( $lw_options['lw_linkcolor'] ) )
		{
			$css_color = $this->cloudcss( $lw_options['lw_linkcolor'] );
			$lw_options['lw_style'] = ' style="color:' . $css_color . ';';

			if( isset( $lw_options['lw_linksize'] ) && !empty( $lw_options['lw_linksize'] ) )
			{
				if( isset( $lw_options['lw_linkscale'] ) && !empty( $lw_options['lw_linkscale'] ) )
				{
					$lw_options['lw_style'] .= ' font-size: ' . $lw_options['lw_linksize'] . $lw_options['lw_linkscale'] . ';"';
				}
				else
				{
					$lw_options['lw_style'] .= ' font-size: ' . $lw_options['lw_linksize'] . 'px;"';
				}
			}
			else
			{
				$lw_options['lw_style'] .= ' font-size: 12px;"';
			}
		}

		if( isset( $ad['nofollow'] ) && $ad['nofollow'] == 1 )
		{
			$lw_options['rel'] = ' rel="nofollow"';
		}

		//KIND OF LINK SEPARATOR
		if( $count > 0 )
		{
			if( empty( $lw_options['lw_style'] ) )
			{
				$lw_options['lw_seperator'] = ' - ';
			}
			else
			{
				$lw_options['lw_seperator'] = '&nbsp;&nbsp;&nbsp;';
			}
		}
	}

	// ---------------------------------------------------------------------------------------
	// ---------------------------------- LINKINTEXT FUNCTIONS -------------------------------
	// ---------------------------------------------------------------------------------------

	function lw_linkintxt_dbcleanup()
	{
		//REMOVE OLD LINKINTXT ADVERTS
		global $table_prefix, $wpdb, $user_level;
		$table_name = $table_prefix . "linkintxt";

		$lw_cleandb = "DROP TABLE IF EXISTS ".$table_name;
		@$wpdb->query($lw_cleandb);
	}

	function replace_ok( $fwords, $rwords, $content )
	{
		$rcontent = "";
		$ncon = preg_split( "/\b" . $fwords[0] . "\b/i", $content, 2 );

		$f = array_shift( $fwords );
		$r_lw = array_shift( $rwords );

		$lw_oneword = 0;

		for( $lw_i = 0; $lw_i < count( $ncon ); $lw_i++ )
		{
			$con = $ncon[$lw_i];

			if( count( $fwords ) )
			{
				$con = $this->replace_ok( $fwords, $rwords, $con );
			}

			if( $lw_i < count( $ncon ) - 1 )
			{
				$rcontent .= $con . $r_lw;
			}
			else
			{
				$rcontent .= $con;
			}
		}

		return $rcontent;
	}

	//REPLACEONE TAKES THE INPUT WORD AND REPLACES IT WITH THE OUTPUT.
	function ReplaceOne( $in, $out, $content )
	{
		if( strpos( $content, $in ) !== false )
		{
			$pos = strpos( $content, $in );
			return substr( $content, 0, $pos ) . $out . substr( $content, $pos + strlen( $in ) );
		}
		else
		{
			return $content;
		}
	}

	function lw_posttags( $term_links )
	{
		$finds = get_option( 'lw_linkintxts' );
		$return_string = '';

		//BUILD WORDS TO FIND IN CONTENT
		if(is_array($finds))
		{
			foreach( $finds as $index => $find )
			{
				//CHECK IF ON THE CORRECT PAGE
				if( $find['disp_url'] == $current_url )
				{
					$term_links .= ', <a href="http://'.$find['disp_url'].'">'.$find['find_word'].'</a>';
				}
			}
		}

		return $term_links;
	}

	function lw_contentreplace( $content )
	{
		global $user_level, $post, $id;

		//OUR LINKINTXT ARRAY
		$finds = get_option( 'lw_linkintxts' );
		$lw_meta_data = get_post_meta( $id, '_disable_linkworth', true );

		//MUST HAVE WORDS AND NOT DISABLED ON POST
		if( ( empty( $lw_meta_data ) || $lw_meta_data != 1 ) && count( $finds ) > 0 )
		{
			//SET DEFAULT VARIABLES
			$current_url = htmlspecialchars($_SERVER['SERVER_NAME'], ENT_QUOTES, 'UTF-8') . htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8');
			$links = array();
			$tags = array();

			//BUILD WORDS TO FIND IN CONTENT
			if(is_array($finds))
			{
				foreach( $finds as $find )
				{
					//CHECK IF ON THE CORRECT PAGE
					if( $find['disp_url'] == $current_url )
					{
						//ADD WORD TO FIND AND REPLACE
						$find_words[] = $find['find_word'];
						$repl_words[] = $find['replace_word'];
					}
				}
			}

			//MUST HAVE WORDS ON THIS PAGE
			if( isset( $find_words ) && count( $find_words ) > 0 )
			{
				//STRIP ALL THE EXISTING LINKS OUT FIRST .
				$pattern = '/(<(?:[^<>]+(?:"[^"]*"|\\\'[^\']*\\\')?)+>)/';
				$html_array = preg_split ( $pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
				$content = "";

				//LOOK AT EACH PIECE OF CONTENT
				for( $lw_i = 0; $lw_i < count( $html_array ); $lw_i++ )
				{
					$chunk = $html_array[$lw_i];

					//CHECK FOR A LINK
					if( ( stripos( $chunk, '<a' ) == 0 && stripos( $chunk, '<a' ) !== false ) )
					{
						$links[] = $chunk . $html_array[++$lw_i] . $html_array[++$lw_i];
						$content .= "!@#$%^&*()";
					}
					//CHECK FOR A TAG
					elseif( ( strpos( $chunk, '<' ) == 0 && strpos( $chunk, '<' ) !== false ) )
					{
						$tags[] = $chunk;
						$content .= "!@#T%^&*()";
					}
					else
					{
						$content .= $chunk;
					}
				}

				$content = $this->replace_ok( $find_words, $repl_words, $content );

				//ADD TAGS BACK INTO CONTENT
				foreach( $tags as $tag )
				{
					$content = $this->ReplaceOne( "!@#T%^&*()", $tag, $content );
				}

				//ADD LINKS BACK INTO CONTENT
				foreach( $links as $link )
				{
					$content = $this->ReplaceOne( "!@#$%^&*()", $link, $content );
				}
			}
		}

		return $content;
	}

	//DELETES SIDEBAR LINKS. ONLY USED FOR UPGRADS FROM VERSION < 309
	function delete_sidebar_links( $only_links = 0 )
	{
		global $wpdb;
		delete_option( 'lw_links', array() );

		//GET THE STORED LINK CATEGORY ID
		$lw_linkcatid = get_option( 'lw_linkcatid' );

		if( !function_exists( 'wp_delete_link' ) )
		{
			@include_once( ABSPATH . '/wp-admin/includes/bookmark.php' );
		}

		if( !$lw_linkcatid )
		{
			return;
		}

		//GET_BOOKMARKS ACCEPTS 1 ARRAY AS AN ARGUMENT.  WE ONLY NEED THE LINKS FOR THE DEFAULT LW CATEGORY SO WE CAN DELETE THEM.
		$args = array();
		//SET UP THE ARRAY ARGUMENTS.
		$args['category'] = $lw_linkcatid;
		$args['hide_invisible'] = 0;
		//CACHE BUSTER
		$args['limit'] = rand(999,9999);
		$links = get_bookmarks( $args );

		if( !function_exists( 'wp_delete_link' ) )
		{
			@include_once( ABSPATH . '/wp-admin/includes/bookmark.php' );
		}

		if( count( $links ) > 0 )
		{
			foreach( $links as $link )
			{
				$link_id = $link->link_id;

				do_action( 'delete_link', $link_id );
				wp_delete_object_term_relationships( $link_id, 'link_category' );

				$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->links WHERE link_id = %d", $link_id ) );

				do_action( 'deleted_link', $link_id );
				clean_bookmark_cache( $link_id );
			}
		}

		if( !$only_links )
		{
			$args = array();
			//SET UP THE ARRAY ARGUMENTS
			$args['category'] = $lw_linkcatid;
			//CACHE BUSTER
			$args['limit'] = rand(999,9999);
			$links = get_bookmarks( $args );
			if( !count( $links ) )
			{
				delete_option( 'lw_linkcatid' );
				//DELETE THE LW CATEGORY.
				wp_delete_term( $lw_linkcatid, 'link_category' );
			}
			else
			{
				$this->delete_sidebar_links();
			}
		}
	}

	//DEACTIVATE THE PLUGIN. DELETES THE OLD LINKS ETC.
	function deactivate_hook()
	{
		$this->delete_sidebar_links();
	}

	//RETURNS A "NORMAL" DESCRIPTION OF THE AD_LOCATION VALUE.
	function human_location( $loc )
	{
		if( $loc == 1 )
		{
			$lw_humanloc = 'Home Page';
		}
		elseif( $loc == 2 )
		{
			$lw_humanloc = 'Sub Pages';
		}
		elseif( $loc == 3 )
		{
			$lw_humanloc = 'All Pages';
		}
		else
		{
			$lw_humanloc =$ad['location'];
		}

		return $lw_humanloc;
	}

	function plugin_get_number()
	{
		if ( ! function_exists( 'get_plugins' ) )
		{
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
		$plugin_file = basename( ( __FILE__ ) );

		return $plugin_folder[$plugin_file]['Version'];
	}


	//PLUGIN IDENTIFICATION
	function lw_identification()
	{
		global $wp_query, $lw_options, $lw_debug_information;

		$hash_salt = 'l1nkw0rth_Id3nt1f1cati0n';

		//CHECK TO MAKE SURE THEY HAVE THEIR WEBSITE ID AND HASH - NEEDED TO USE THE PLUGIN
		if( !empty( $lw_options['website_id'] ) && !empty( $lw_options['website_hash'] ) )
		{
			$hash_word = 'true' . date( 'Ymd' );
			$hash = md5( $hash_word . $hash_salt );
		}
		else
		{
			$hash_word = 'false' . date( 'Ymd' );
			$hash = md5( $hash_word . $hash_salt );
		}

		$plugin_number = $this->plugin_get_number();
		$clean_plugin_number = str_replace( '.', '', $plugin_number );

		$html = '<div id="plugin-identification" style="display:none;">' . $hash . '-' . $clean_plugin_number . '</div><!-- /plugin-identification -->';

		if( isset( $lw_options['disable_silent'] ) && $lw_options['disable_silent'] == 1 )
		{
			$html .= '<!-- lw-loop-count: ' . $wp_query->post_count . ' -->';
		}

		echo $html;
	}
}//CLASS END

	// -------------------------------------------------------------------------------
	// ------------------------- Initialize the LW CLass -----------------------------
	// -------------------------------------------------------------------------------

$lw_options = get_option( 'lw_options' );

//IF THIS IS MU SITE
if( function_exists( 'is_site_admin' ) && function_exists( 'get_blog_count' ) && !$lw_options['user_managed'] )
{
	include( 'lw_includes/LinkWorth_MU.php' );
}

$LinkWorth = new LinkWorth;
// INITIATE LW: PASSED BY REFERENCE FOR PHP4
add_action( 'init', array( &$LinkWorth, 'init' ) );
register_deactivation_hook( __file__, array( $LinkWorth, 'deactivate_hook' ) );
//BILLBOARDS
add_filter( 'generate_rewrite_rules', array( &$LinkWorth, 'rewrite' ) );

if( $lw_options['lw_sidebarwidget'] == 1 )
{
	add_action( 'init', 'widget_linkw_init' );
}

	// ----------------------------------------------------------------------------
	// ---------------------------------- LW WIDGET -------------------------------
	// ----------------------------------------------------------------------------

function widget_linkw_init()
{
	if( !function_exists( 'register_sidebar_widget' ) )
	{
		return;
	}

	function widget_linkw( $args )
	{
		global $LinkWorth;

		echo $LinkWorth->fake_widget( $args );
	}

	function widget_linkw_control()
	{
		$options = get_option( 'lw_widget' );

		if( !is_array( $options ) )
		{
			$options = array( 'title' => 'Friends' );
			add_option( 'lw_widget', $options );
		}

		if( isset( $_POST['linkw-submit'] ) )
		{
			$options['title'] = sanitize_text_field($_POST['linkw-title']);
			update_option( 'lw_widget', $options );
		}

		if( !empty( $options['title'] ) )
		{
			$title = $options['title'];
		}
		else
		{
			$title = '';
		}
		//HERE IS OUR LITTLE FORM SEGMENT. NOTICE THAT WE DON'T NEED A
		//COMPLETE FORM. THIS WILL BE EMBEDDED INTO THE EXISTING FORM.
?>
		<p style="text-align:right; white-space:nowrap;">
			<label for="linkw-title">Title: <input style="width: 200px;" id="linkw-title" name="linkw-title" type="text" value="<?php echo esc_html($title) ?>" /></label>
		</p>
		<input type="hidden" id="linkw-submit" name="linkw-submit" value="1" />
<?php
	}

	//REGISTER THE WIDGET
	wp_register_sidebar_widget( 'lw_widget', 'Links Widget', 'widget_linkw', array( 'description' => 'LinkWorth widget for publishing deals.' ) );
	//THIS REGISTERS WIDGET CONTROL FORM
	wp_register_widget_control( 'lw_widget', 'Links Widget', 'widget_linkw_control' );
}

	// ---------------------------------------------------------------------------------
	// ---------------------------------- LW ADMIN Panel -------------------------------
	// ---------------------------------------------------------------------------------

//CALLED BY THE ADMIN PANEL FUNCTION TO DISPLAY ADVERTISEMENT INFORMATION. IN ITS OWN FUNCTION TO "KEEP IT CLEAN"
function lw_config_admin_page()
{
	global $lw_linkads, $LinkWorth, $lw_options, $wp_rewrite, $LinkWorth_MU, $lw_debug_information;

	include_once( 'lw_includes/LinkWorth_admin.php' );
}
?>
