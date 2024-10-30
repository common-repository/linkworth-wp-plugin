	<script type="text/javascript">
	//<![CDATA[
		function confirmationLWDelete()
		{
			var lwDeleteAnswer = confirm('Are You Sure You Want To Delete Your Settings?');

			if( lwDeleteAnswer )
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	//]]>
	</script>
<?php
	//CHECK FOR EDIT ABILITY
	$support_array = $LinkWorth->testtheme();

	//UPDATE DEALS
	if( isset( $_POST['lw_update_deal_list'] ) && wp_verify_nonce($_POST['update_general_settings_field'], 'update_general_settings_action') )
	{
		update_option( 'lw_cache_time', 0 );
		$LinkWorth->get_ads( $lw_update_ads = true );
		//UPDATE THE BILLBOARD ADS. THESE ARE USED WITH RE-WRITES, SO IT IS BEST TO HAVE IT ALL READY.
		$LinkWorth->billboards_update();
		//UPDATE THE OLD LINKS
		$LinkWorth->linkintxts_update();
	}

	//DELETE SETTINGS
	if( isset( $_POST['lw_delete_settings'] ) && wp_verify_nonce($_POST['update_advanced_options_field'], 'update_advanced_options_action') )
	{
		delete_option('lw_linkintxts');
		delete_option('lw_billboards');
		delete_option('lw_cache_time');
		delete_option('lw_cache');
		delete_option('lw_links');
		delete_option('lw_widget');
		delete_option('lw_linkcatid');
		delete_option('lw_options');

		$lw_options = get_option( 'lw_options' );
		$billboard_base = 'pages';
	}
	elseif( isset( $_POST['lw_update_settings'] ) && wp_verify_nonce($_POST['update_advanced_options_field'], 'update_advanced_options_action') )
	{
		//PROCESS POST BEFOR UPDATING
		$updated_lw_options = get_option( 'lw_options' );

		//SET DEFAULT VARIABLE ARRAY  -> OVERRIDE EXISTING WITH INPUT
		if( isset( $_POST['updating_advanced_options'] ) )
		{
			//CHANGING SCALES
			if( $_POST['lw_ops']['lw_linkscale'] != $updated_lw_options['lw_linkscale'] )
			{
				//SET SCALE TO DEFAULT
				switch( $_POST['lw_ops']['lw_linkscale'] )
				{
					case 'px':
						$updated_lw_options['lw_linksize'] = 12;
						break;
					case 'pt':
						$updated_lw_options['lw_linksize'] = 12;
						break;
					case 'em':
						$updated_lw_options['lw_linksize'] = 1;
						break;
				}
			}

			$updated_lw_options['loop_number'] = sanitize_text_field($_POST['lw_ops']['loop_number']);
			$updated_lw_options['nocontentads'] = sanitize_text_field($_POST['lw_ops']['nocontentads']);
			$updated_lw_options['debug'] = sanitize_text_field($_POST['lw_ops']['debug']);
			$updated_lw_options['disable_silent'] = sanitize_text_field($_POST['lw_ops']['disable_silent']);
			$updated_lw_options['lw_linkscale'] = sanitize_text_field($_POST['lw_ops']['lw_linkscale']);
		}
		else
		{
			$updated_lw_options['lw_sidebar'] = sanitize_text_field($_POST['lw_ops']['lw_sidebar']);
			$updated_lw_options['lw_sidebarwidget'] = sanitize_text_field($_POST['lw_ops']['lw_sidebarwidget']);
			$updated_lw_options['lw_cssmod'] = 0;
			$updated_lw_options['lw_linktype'] = sanitize_text_field($_POST['lw_ops']['lw_linktype']);
			$updated_lw_options['lw_linkcolor'] = sanitize_text_field($_POST['lw_ops']['lw_linkcolor']);
			$updated_lw_options['website_id'] = sanitize_text_field($_POST['lw_ops']['website_id']);
			$updated_lw_options['website_hash'] = sanitize_text_field($_POST['lw_ops']['website_hash']);
			$updated_lw_options['billboard_base'] = sanitize_text_field($_POST['lw_ops']['billboard_base']);
			$updated_lw_options['lw_linksize'] = sanitize_text_field($_POST['lw_ops']['lw_linksize']);

			if( isset( $_POST['lw_ops']['site_id'] ) && isset( $_POST['lw_ops']['site_hash'] ) )
			{
				$updated_lw_options['site_id'] = sanitize_text_field($_POST['lw_ops']['site_id']);
				$updated_lw_options['site_hash'] = sanitize_text_field($_POST['lw_ops']['site_hash']);
			}
		}

		//UPDATE PLUGIN VARIABLES
		if( is_array( $updated_lw_options ) )
		{
			//SIDEBAR DISABLED -> DELETE LINKS.
			if( $updated_lw_options['lw_sidebar'] == 0 )
			{
				$LinkWorth->delete_sidebar_links();
			}

			//UPDATED WEBSITE HASH OR WEBSITE ID
			if( $lw_options['website_hash'] != $updated_lw_options['website_hash'] || $lw_options['website_id'] != $updated_lw_options['website_id'] )
			{
				$LinkWorth->get_ads( $lw_update_ads = true );

				if( is_array( $lw_linkads ) )
				{
					//UPDATE THE BILLBOARD ADS
					$LinkWorth->billboards_update();
					//UPDATE THE OLD LINKS
					$LinkWorth->linkintxts_update();
				}
			}

			//CHANGED THE BILLBOARD PATH NAME
			if( $lw_options['billboard_base'] != $updated_lw_options['billboard_base'] )
			{
				$lw_options['billboard_base'] = $updated_lw_options['billboard_base'];
				//FLUSH THE REWRITE RULES.
				$wp_rewrite->flush_rules();
			}

			if( !$wp_rewrite->using_mod_rewrite_permalinks() )
			{
				$LinkWorth->save_mod_rewrite_rules();
			}

			//UPDATE MULTISITE OPTIONS
			if( function_exists('is_site_admin') && is_site_admin() && $updated_lw_options['applytoall'] == 1 )
			{
				$LinkWorth_MU->update_all_options($updated_lw_options);
			}

			$lw_options = $LinkWorth->update_options($updated_lw_options);
?>
		<div id="message" class="updated fade">
			<p style="font-weight:bold;">Settings Updated!</p>
		</div>
<?php
		}
	}

	//SET VARIABLES TO USE IN ADMIN
	if( isset( $lw_options ) && is_array( $lw_options ) )
	{
		extract( $lw_options, EXTR_OVERWRITE );
	}

	//OUTPUT ANY DEBUG INFORMATION
	if( isset( $_GET['debug'] ) )
	{
		//BUILD ARRAY OF DEBUG INFORMATION
		$lw_debug_information .= print_r($support_array,true);
		$debug_details = explode( '++', $GLOBALS['lw_debug_information']);
?>
	<div style="width:97%; margin:10px; padding:5px; background-color:#F1DCD7;">
		<pre><?php echo htmlentities( print_r( $debug_details, true ) ) ?></pre>
	</div>
<?php
	}

	//GENERAL SETTINGS
	if( $_GET['page'] == 'lw-settings' )
	{
		if( $support_array['can_get_ads'] == 0 )
		{
?>
		<div id="message" class="updated fade">
			<p><strong>Your host doesn't support <em>wp_remote_get</em> or <em>curl</em>. Please ask your host to enable one or the other.</strong></p>
		</div>
<?php
		}

		//NO FOOTER SUPPORT
		if( $support_array['footer'] == 0 )
		{
?>
		<div id="message" class="updated fade">
			<p><strong>Your theme does not have a footer action. </strong></p>
		</div>
<?php
		}
?>
	<div class="wrap" style="font-size:1em;">
		<fieldset class="options">
			<h2>LinkWorth Configuration</h2>
			<form method="post" action="<?php echo htmlentities( $_SERVER['REQUEST_URI'] ) ?>">
			<table cellspacing="0" cellpadding="0" class="widefat" style="display:inline-block; width:auto; vertical-align:top;">
				<thead>
					<tr>
						<th colspan="3" style="color:#21759B;">General Settings</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th colspan="3">&nbsp;</th>
					</tr>
				</tfoot>
<?php
				//OPTION FOR MULTISITES -> APPLY TO ALL
				if( function_exists( 'is_site_admin' ) && is_site_admin() )
				{
?>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;">Apply these options to ALL WPMU blogs?</td>
					<td colspan='2'><input type="checkbox" value="1" name="lw_ops[applytoall]" id="applytoall" /></td>
				</tr>
<?php
				}
?>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;">Does your theme have a sidebar?</td>
					<td><input type="radio" name="lw_ops[lw_sidebar]" id="lw_sidebar-no" value="0" <?php if(!isset($lw_sidebar) || $lw_sidebar == 0) { echo esc_attr("checked='checked' "); }?>/><label for="lw_sidebar-no"> No </label></td>
					<td><input type="radio" name="lw_ops[lw_sidebar]" id="lw_sidebar-yes" value="1" <?php if($lw_sidebar == 1) { echo esc_attr("checked='checked' "); }?>/><label for="lw_sidebar-yes"> Yes </label></td>
				</tr>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;">Display deals using the widget?</td>
					<td><input type="radio" name="lw_ops[lw_sidebarwidget]" id="lw_sidebarwidget-no" value="0" <?php if(!isset($lw_sidebarwidget) || $lw_sidebarwidget == 0) { echo esc_attr("checked='checked' "); }?>/><label for="lw_sidebar-no"> No </label></td>
					<td><input type="radio" name="lw_ops[lw_sidebarwidget]" id="lw_sidebarwidget-yes" value="1" <?php if($lw_sidebarwidget == 1) { echo esc_attr("checked='checked' "); }?>/><label for="lw_sidebar-yes"> Yes </label></td>
				</tr>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;">Display styled links?</td>
					<td><input type="radio" name="lw_ops[lw_linktype]" id="lw_regular" onchange="jQuery('#colors').fadeOut('slow');" value="0" <?php if (!isset($lw_linktype) || $lw_linktype == 0) { echo esc_attr("checked='checked' "); }?>/><label for="lw_regular"> No </label></td>
					<td><input type="radio" name="lw_ops[lw_linktype]" id="lw_cloud" onchange="jQuery('#colors').fadeIn('slow').css('display', 'inline-block');" value="1" <?php if ($lw_linktype == 1) { echo esc_attr("checked='checked' "); }?>/><label for="lw_cloud"> Yes </label></td>
				</tr>
<?php
			//OPTION FOR MULTISITES -> WEBSITE HASH/WEBSITE ID INPUTS
			if( function_exists( 'is_site_admin' ) )
			{
				//ADMIN GET TO SEE ALL INFORMATION
				if( is_site_admin() || is_super_admin() )
				{
?>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;"> Allow WPMU sub blogs manage their own id and hashes?</td>
					<td colspan="2">
						<input type="checkbox" value="1" name="lw_ops[user_managed]" id="user_managed" <?php if(isset($user_managed) && $user_managed == 1){echo esc_attr("checked='checked' ");}?>/>
					</td>
				</tr>
				<tr style="background-color:#F1DCD7;">
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;"> What is <span style="color:#FF0000;">MAIN</span> website's ID?</td>
					<td colspan="2">
						<input type="text" name="lw_ops[site_id]" size="10" id="site_id" value="<?php if(defined('LW_SITE_ID')){echo esc_html(LW_SITE_ID);} ?>"/>
					</td>
				</tr>
				<tr style="background-color:#F1DCD7;">
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;"> What is <span style="color:#FF0000;">MAIN</span> website's HASH ID?</td>
					<td colspan="2">
						<input type="text" name="lw_ops[site_hash]" size="35" id="site_hash" value="<?php if(defined('LW_SITE_HASH')){echo esc_html(LW_SITE_HASH);} ?>"/>
					</td>
				</tr>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;"> What is THIS website's ID?</td>
					<td colspan="2">
						<input type="text" name="lw_ops[website_id]" size="10" id="website_id" value="<?php if(defined('LW_WEBSITE_ID')) { echo esc_html(LW_WEBSITE_ID); } ?>"/>
					</td>
				</tr>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;"> What is THIS website's HASH ID?</td>
					<td colspan="2">
						<input type="text" name="lw_ops[website_hash]" size="35" id="website_hash" value="<?php if(defined('LW_HASH')) { echo esc_html(LW_HASH); } ?>"/>
					</td>
				</tr>
<?php
				}
				else
				{
					$disabled_option = ' disabled="disabled"';

					//IF THE USERS ARE ABLE TO MANAGE THEIR WEBSITE ID AND HASH
					if( $user_managed )
					{
						$disabled_option = '';
					}
?>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;"> What is THIS website's SITE ID?</td>
					<td colspan="2">
						<input type="text" name="lw_ops[website_id]" size="10" id="website_id" value="<?php if(defined('LW_WEBSITE_ID')) { echo esc_html(LW_WEBSITE_ID); } ?>"<?php echo esc_attr($disabled_option) ?> />
					</td>
				</tr>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;"> What is THIS website's HASH ID?</td>
					<td colspan="2">
						<input type="text" name="lw_ops[website_hash]" size="35" id="website_hash" value="<?php if(defined('LW_HASH')) { echo esc_html(LW_HASH); } ?>"<?php echo esc_attr($disabled_option) ?> />
					</td>
				</tr>
<?php
				}
			 }
			 else
			 {
?>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;"> What is your website's ID?</td>
					<td colspan="2">
						<input type="text" name="lw_ops[website_id]" size="10" id="website_id" value='<?php if(isset($website_id)) { echo esc_html($website_id); } ?>'/>
					</td>
				</tr>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;"> What is your website's HASH ID?</td>
					<td colspan="2">
						<input type="text" name="lw_ops[website_hash]" size="35" id="website_hash" value='<?php if(isset($website_hash)) { echo esc_html($website_hash); } ?>'/>
					</td>
				</tr>
<?php
			}
?>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;"> Billboard ad base URL?</td>
					<td colspan="2">
						<input type="text" name="lw_ops[billboard_base]" size="20" id="billboard_base" value='<?php if(isset($billboard_base)) { echo esc_html($billboard_base); } ?>'/>
					</td>
				</tr>
			</table>

			<table id="colors" class="widefat" style="display:none; width:auto; vertical-align:top;">
				<thead>
					<tr>
						<th colspan="2" style="color:#21759B;">Link Styles</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th colspan="2">&nbsp;</th>
					</tr>
				</tfoot>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA; vertical-align:middle;">Select a style:</td>
					<td>
<?php
				//GET COLOR(S) FOR LINKS
				for( $color_index = 0; $color_index < 7; $color_index++ )
				{
					$test_color[$color_index] = $LinkWorth->cloudcss( $lw_linkcolor );
				}

				$lw_color_array_selected = array('','','','','','','');

				if( !empty( $lw_linkcolor ) )
				{
					$lw_color_array_selected[$lw_linkcolor] = ' selected="selected"';
				}

				if( !isset( $lw_linksize ) || empty( $lw_linksize ) )
				{
					 $lw_linksize = 12;
				}

				if( !isset( $lw_linkscale ) || empty( $lw_linkscale ) )
				{
					$lw_linkscale = 'px';
				}
?>
						<select name="lw_ops[lw_linkcolor]" style="width:100px;">
							<option value="">Select One</option>
							<option value="1"<?php echo esc_attr($lw_color_array_selected[1]) ?>>AquaMarine</option>
							<option value="2"<?php echo esc_attr($lw_color_array_selected[2]) ?>>Forest</option>
							<option value="3"<?php echo esc_attr($lw_color_array_selected[3]) ?>>Winter</option>
							<option value="4"<?php echo esc_attr($lw_color_array_selected[4]) ?>>Summer</option>
							<option value="5"<?php echo esc_attr($lw_color_array_selected[5]) ?>>Fruity</option>
							<option value="6"<?php echo esc_attr($lw_color_array_selected[6]) ?>>Baby</option>
							<option value="7"<?php echo esc_attr($lw_color_array_selected[7]) ?>>Highway</option>
						</select>
					</td>
				</tr>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA; vertical-align:middle;">Font Size:</td>
					<td>
						<input name="lw_ops[lw_linksize]" value="<?php echo esc_html($lw_linksize) ?>" style="width:90px;" /> <span style="font-weight:bold"><?php echo esc_html($lw_linkscale) ?></span>
					</td>
				</tr>
				<tr>
					<td colspan='2' style="color:#333333; font-weight:bold; background-color:#EAF2FA;">The links will look similiar to the following:</td>
				</tr>
				<tr>
					<td colspan='2'>
						<a href="#" style="color:<?php echo esc_attr($test_color[0]);?>; font-size:<?php echo esc_attr($lw_linksize.$lw_linkscale);?>;">Link 1</a> &nbsp; &nbsp; &nbsp;
						<a href="#" style="color:<?php echo esc_attr($test_color[1]);?>; font-size:<?php echo esc_attr($lw_linksize.$lw_linkscale);?>;">Another Link</a> &nbsp; &nbsp; &nbsp;
						<a href="#" style="color:<?php echo esc_attr($test_color[2]);?>; font-size:<?php echo esc_attr($lw_linksize.$lw_linkscale);?>;">Anchor 4</a> &nbsp; &nbsp; &nbsp;
						<br />
						<a href="#" style="color:<?php echo esc_attr($test_color[3]);?>; font-size:<?php echo esc_attr($lw_linksize.$lw_linkscale);?>;">A Link</a> &nbsp; &nbsp; &nbsp;
						<a href="#" style="color:<?php echo esc_attr($test_color[4]);?>; font-size:<?php echo esc_attr($lw_linksize.$lw_linkscale);?>;">Click me</a> &nbsp; &nbsp; &nbsp;
						<a href="#" style="color:<?php echo esc_attr($test_color[5]);?>; font-size:<?php echo esc_attr($lw_linksize.$lw_linkscale);?>;">Another Anchor</a> &nbsp; &nbsp; &nbsp;
					</td>
				</tr>
			</table>

			<table cellspacing="0" cellpadding="0" class="submit">
				<tr>
					<td style="padding:0 15px; vertical-align:top;">
						<input type="submit" name="lw_update_settings" value="Update Settings &raquo;" />
					</td>
					<td style="padding:0 15px; vertical-align:top;">
						<input type="submit" name="lw_delete_settings" value="Delete Settings &raquo;" onclick="return confirmationLWDelete()" /><br />
						<span style="font-weight:bold; font-style:italic;">(Including Advanced Settings)</span>
					</td>
				</tr>
			</table>

<?php
		//HIDE STYLED LINKS ON LOAD
		if( ( isset( $lw_linktype ) && $lw_linktype == 1 ) )
		{
?>
			<script type="text/javascript">
			//<![CDATA[
				document.getElementById('colors').style.display = 'inline-block';
			//]]>
			</script>
<?php
		}

		// ---------------------------------------------------------------------------------------
		// ----------------- NO OPTIONS BELOW THIS POINT. JUST SOME INFORMATION. -----------------
		// ---------------------------------------------------------------------------------------
?>
		<h2 style="display:inline-block;">Ad Information</h2>
		<p style="display:inline-block; margin-top:0;"><small><em>(Overview of published adverts.)</em></small></p>
		<p>Information listed here has been generated based on the lists of approved ads in your LinkWorth account on our servers.</p>

		<table cellspacing="0" cellpadding="0" class="widefat">
			<thead>
				<tr>
					<th style="width:20%; color:#21759B;">Sample Link</th>
					<th style="width:30%; color:#21759B;">Link is Displayed on</th>
					<th style="width:50%; color:#21759B;">Description of Link</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th colspan="3">&nbsp;</th>
				</tr>
			</tfoot>
<?php
		// GOING THROUGH THE ADVERT ARRAY TO DISPLAY EXISTING ADS
		if( !isset( $lw_linkads ) || !is_array( $lw_linkads ) || empty( $lw_linkads ) )
		{
?>
			<tr>
				<td colspan="3">There are no advertisers approved in your LinkWorth account.</td>
			</tr>
<?php
		}
		else
		{
			//LINKADS
			if( isset( $lw_linkads['linkads'] ) && count( $lw_linkads['linkads'] ) > 0 )
			{
?>
			<tr>
				<td colspan="3" style="color:#333333; font-weight:bold; background-color:#EAF2FA;">LinkAds</td>
			</tr>
<?php
				if( !is_array( $lw_linkads['linkads']['ad'][0] ) )
				{
					$ad = $lw_linkads['linkads']['ad'];
					$lw_linkads['linkads']['ad'] = null;
					$lw_linkads['linkads']['ad'][0] = $ad;
				}

				foreach( $lw_linkads['linkads']['ad'] as $ad )
				{
					//SENDS A NUMBER, GETS HOME, SUB OR ALL PAGES IN RETURN.
					$lw_humanloc = $LinkWorth->human_location($ad['location']);
					$protocol = ($ad['use_https'])? 'https://' : 'http://';
?>
			<tr>
				<td><a href="<?php echo esc_url($protocol.$ad['url'])?>"><?php echo esc_html($ad['anchor'])?></a></td>
				<td><?php echo esc_html($lw_humanloc) ?></td>
				<td><?php echo ((!is_array($ad['description']) || !empty($ad['description'])) ? esc_html($ad['description']) : '');?></td>
			</tr>
<?php
				}
			}

			//LINKBB
			if( count($lw_linkads['linkbb']) > 0 )
			{
?>
			<tr>
				<td colspan="3" style="color:#333333; font-weight:bold; background-color:#EAF2FA;">LinkBB (Billboard Ads)</td>
			</tr>
<?php
				$lw_path = get_option('siteurl');

				if( !is_array( $lw_linkads['linkbb']['ad'][0] ) )
				{
					$ad = $lw_linkads['linkbb']['ad'];
					$lw_linkads['linkbb']['ad'] = null;
					$lw_linkads['linkbb']['ad'][0] = $ad;
				}

				foreach( $lw_linkads['linkbb']['ad'] as $ad )
				{
					//SENDS A NUMBER, GETS AHOME, SUB OR ALL PAGES IN RETURN.
					$lw_humanloc = $LinkWorth->human_location( $ad['location'] );

					if( get_option('permalink_structure') =='' )
					{
						$ad['url'] = $lw_path.'?linkworth='.$ad['pagename'];
					}
					else
					{
						$ad['url'] = $lw_path.'/'.$lw_options['billboard_base'].'/'.$ad['pagename'];
					}
?>
			<tr>
				<td><a href="<?php echo esc_url($ad['url']) ?>"><?php echo esc_html($ad['anchor']) ?></a></td>
				<td><?php echo esc_html($lw_humanloc) ?></td>
				<td><?php echo esc_html($ad['pagename']) ?> <?php echo esc_html($ad['description']) ?></td>
			</tr>
<?php
				}
			}

			//LINKMURA
			if( count( $lw_linkads['linkmura'] ) > 0 )
			{
				if( !is_array( $lw_linkads['linkmura']['ad'][0] ) )
				{
					$ad = $lw_linkads['linkmura']['ad'];
					$lw_linkads['linkmura']['ad'] = null;
					$lw_linkads['linkmura']['ad'][0] = $ad;
				}
?>
			<tr>
				<td colspan="3" style="color:#333333; font-weight:bold; background-color:#EAF2FA;">LinkMura</td>
			</tr>
<?php
				foreach( $lw_linkads['linkmura']['ad'] as $ad )
				{
					//SENDS A NUMBER, GETS AHOME, SUB OR ALL PAGES IN RETURN.
					$lw_humanloc = $LinkWorth->human_location($ad['location']);

					if(@is_array($ad['hyperlinks']['hyperlink'][0]))
					{
						$example_ad = $ad['hyperlinks']['hyperlink'][0];
					}
					else
					{
						$example_ad = $ad['hyperlinks']['hyperlink'];
					}

					$protocol = ($ad['use_https'])? 'https://' : 'http://';
?>
			<tr>
				<td style="vertical-align:top;"><a href="<?php echo esc_url($protocol.$example_ad['url']) ?>" title="<?php echo esc_attr($example_ad['description']) ?>"><?php echo esc_html($example_ad['anchor']) ?></a></td>
				<td style="vertical-align:top;"><?php echo esc_html($lw_humanloc) ?></td>
				<td>
<?php
					$current_count = 1;
					$hyperlink_count = count($ad['hyperlinks']['hyperlink']);

					foreach( $ad['hyperlinks']['hyperlink'] as $hyperlink )
					{
						$protocol = ($hyperlink['use_https'])? 'https://' : 'http://';
?>
					<a href="<?php echo esc_url($protocol.$hyperlink['url']) ?>" title="<?php echo esc_attr($hyperlink['description']) ?>"><?php echo esc_html($hyperlink['anchor']) ?></a><?php echo (($current_count < $hyperlink_count) ? ' ,' : '')?>
<?php
						$current_count++;
					}

					unset($current_count, $hyperlink_count);
?>
				</td>
			</tr>
<?php
				}
			}

			//LINKINTXT
			if( count( $lw_linkads['linkintxt'] ) > 0 )
			{
				if( !is_array( $lw_linkads['linkintxt']['ad'][0] ) )
				{
					$ad = $lw_linkads['linkintxt']['ad'];
					$lw_linkads['linkintxt']['ad'] = null;
					$lw_linkads['linkintxt']['ad'][0] = $ad;
				}
?>
			<tr>
				<td colspan="3" style="color:#333333; font-weight:bold; background-color:#EAF2FA">LinkInTxt</td>
			</tr>
<?php
				foreach( $lw_linkads['linkintxt']['ad'] as $ad )
				{
					$protocol = ($ad['use_https'])? 'https://' : 'http://';
?>
			<tr>
				<td><a href="<?php echo esc_url($protocol.$ad['url']) ?>"><?php echo esc_html($ad['anchor']) ?></a></td>
				<td><?php echo esc_html($ad['webpageurl']) ?></td>
				<td></td>
			</tr>
<?php
				}
			}
		}
?>
		</table>

			<table cellspacing="0" cellpadding="0" class="submit">
				<tr>
					<td style="padding:10px 15px 0 15px; vertical-align:top;">
						<input type="submit" name="lw_update_deal_list" value="Update Deal List &raquo;" />
					</td>
					<td style="padding:10px 15px 0 15px; vertical-align:top;">
						<a href="https://act.linkworth.com/account-login.php" class="button" style="display:inline-block;">LinkWorth Account &raquo;</a><br />
						<span style="font-weight:bold; font-style:italic;">(This Will Leave WordPress)</span>
					</td>
				</tr>
			</table>
			<?php wp_nonce_field( 'update_general_settings_action', 'update_general_settings_field' ); ?>
			</form>
		</fieldset>
		<h2>Theme Notes</h2>
		<p>If any notes about your theme are displayed below, please contact your theme author to have it updated to resolve the issue.</p>
<?php
		$home = get_option( 'home' );
		$siteurl = get_option( 'siteurl' );

		if ( $home != '' && $home != $siteurl )
		{
			$wp_path_rel_to_home = str_replace($home, '', $siteurl); /* $siteurl - $home */
			$script_filename = htmlspecialchars($_SERVER["SCRIPT_FILENAME"], ENT_QUOTES, 'UTF-8');
			$pos = strpos($script_filename, $wp_path_rel_to_home);
			$home_path = substr($script_filename, 0, $pos);
			$home_path = trailingslashit( $home_path );
		}
		else
		{
			$home_path = ABSPATH;
		}

		$htaccess_file = $home_path.'.htaccess';

		if(get_option('permalink_structure') == '' && ((!file_exists($htaccess_file) && !is_writable($home_path)) || !is_writable($htaccess_file)))
		{
?>
		<div>
			<p><strong>Your .htaccess file can not be edited by the plugin automatically. If your <code>.htaccess</code> file were <a href="http://codex.wordpress.org/Changing_File_Permissions">writable</a>, we could do this automatically, but it isn't so these are the mod_rewrite rules you should have in your <code>.htaccess</code> file. Click in the field and press <kbd>CTRL + A</kbd> to select all.</strong></p>
			<textarea style="width:100%; height:130px;" rows="7" cols="50"><?php echo htmlentities( $LinkWorth->AddRewriteRules() ) ?></textarea>
		</div>
<?php
		}
	}
	else
	{
		//ADVANCED PLUGIN SETTINGS
		$loop_option_disabled = '';
		$loop_count = $support_array['loop']['count'];

		if( $support_array['loop']['exists'] == 1 && $loop_count > 0 )
		{
			if( $loop_count == 1 )
			{
?>
		<div id="message" class="updated fade">
			<p style="font-weight:bold;">
				Your theme has <?php echo esc_html($loop_count) ?> loop.
				If you do not have a static page as your home page, your theme could be using more then one instance of 'The Loop.'
				The number of loops could be higher then <?php echo esc_html($loop_count) ?>.
			</p>
		</div>
<?php
			}
			else
			{
?>
		<div id="message" class="updated fade">
			<p style="font-weight:bold;">Your theme has <?php echo esc_html($loop_count) ?> loop(s).</p>
		</div>
<?php
			}
		}
		else
		{
			$loop_option_disabled = ' disabled="disabled"';
?>
		<div id="message" class="updated fade">
			<p style="font-weight:bold;">Your theme does not have a standard loop.</p>
		</div>
<?php
		}
?>
	<div class="wrap" style="font-size:1em;">
		<fieldset class="options">
			<h2>LinkWorth Advanced Configuration</h2>

			<form method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>">
			<table cellspacing="0" cellpadding="0" class="widefat" style="width:auto;">
				<thead>
					<tr>
						<th colspan="2" style="color:#21759B;">Advanced options</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th colspan="2">&nbsp;</th>
					</tr>
				</tfoot>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;"> Add rotating ads after what loop?</td>
					<td>
						<input type="text" name="lw_ops[loop_number]" size="5" id="loop_number" style="border:1px solid #406680; border-radius:0 0 0 0;" value='<?php if(isset($loop_number) && $loop_number > 0){ echo esc_html($loop_number); }?>'<?php echo esc_attr($loop_option_disabled) ?> />
					</td>
				</tr>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;"> Select alternative font scale?</td>
					<td>
<?php
				$lw_size_array_selected = array('','','');

				if( !empty( $lw_linkscale ) )
				{
					$lw_size_array_selected[$lw_linkscale] = ' selected="selected"';
				}
?>
						<select name="lw_ops[lw_linkscale]">
							<option value="px"<?php echo esc_attr($lw_size_array_selected['px']) ?>>Pixels</option>
							<option value="pt"<?php echo esc_attr($lw_size_array_selected['pt']) ?>>Points</option>
							<option value="em"<?php echo esc_attr($lw_size_array_selected['em']) ?>>Ems</option>
						</select>
					</td>
				</tr>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;"> Do not display ads after single page content?</td>
					<td>
						<input type="checkbox" value="1" name="lw_ops[nocontentads]" id="nocontentads" <?php if(isset($nocontentads) && $nocontentads == 1) { echo esc_attr("checked='checked' "); }?>/>
					</td>
				</tr>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;"> Place LinkInTxt as Tags on single pages?</td>
					<td>
						<input type="checkbox" value="1" name="lw_ops[display_tagged]" <?php if(isset($display_tagged) && $display_tagged == 1) { echo esc_attr("checked='checked' "); }?>/>
					</td>
				</tr>
				<tr>
					<td style="color:#333333; font-weight:bold; background-color:#EAF2FA;">Disable silent running?</td>
					<td>
						<input type="checkbox" value="1" name="lw_ops[disable_silent]" id="silent" <?php if(isset($disable_silent) && $disable_silent == 1) { echo esc_attr("checked='checked' "); }?>/>
					</td>
				</tr>
			</table>
			<p style="color:#FF0000; font-weight:bold;"><em>View readme.txt for more information</em></p>

			<table cellspacing="0" cellpadding="0" class="submit">
				<tr>
					<td style="padding:0 15px; vertical-align:top;">
						<input type="submit" name="lw_update_settings" value="Update Settings &raquo;" />
					</td>
					<td style="padding:0 15px; vertical-align:top;">
						<input type="submit" name="lw_delete_settings" value="Delete Settings &raquo;" onclick="return confirmationLWDelete()" /><br />
						<span style="font-weight:bold; font-style:italic;">(Including General Settings)</span>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td style="padding:10px 15px 0 15px; vertical-align:top;">
						<a href="https://act.linkworth.com/account-login.php" class="button" style="display:inline-block;">LinkWorth Account &raquo;</a><br />
						<span style="font-weight:bold; font-style:italic;">(This Will Leave WordPress)</span>
					</td>
				</tr>
			</table>

			<input type="hidden" name="updating_advanced_options" value="1" />
			<?php wp_nonce_field( 'update_advanced_options_action', 'update_advanced_options_field' ); ?>
			</form>
		</fieldset>
	</div>
<?php
	}
?>
	</div>