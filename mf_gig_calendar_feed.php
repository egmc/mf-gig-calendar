<?php

/*

Copyright (C) 2012 Matthew Fries

MF Gig Calendar is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

MF Gig Calendar is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

function create_my_customfeed() {
	
	header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
	$more = 1;
	
	echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; 
	
	$mfgigcal_options = get_option('mfgigcal_settings');
	
	$language = get_bloginfo_rss('language');
	if ($language == "") {
		$language = "en-us";
	}
	
	?>
	
	<rss version="2.0"
		xmlns:content="http://purl.org/rss/1.0/modules/content/"
		xmlns:wfw="http://wellformedweb.org/CommentAPI/"
		xmlns:dc="http://purl.org/dc/elements/1.1/"
		xmlns:atom="http://www.w3.org/2005/Atom"
		xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
		xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
		xmlns:mfgigcal="http://wordpress.org/extend/plugins/mf-gig-calendar/"
		<?php do_action('rss2_ns'); ?>
	>
	
	<channel>
		<title><?php bloginfo_rss('name'); ?></title>
		<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
		<link><?php bloginfo_rss('url') ?></link>
		<description>MF Gig Cal Events</description>
		<lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></lastBuildDate>
		<language><?php echo $language; ?></language>
		<sy:updatePeriod><?php echo apply_filters( 'rss_update_period', 'hourly' ); ?></sy:updatePeriod>
		<sy:updateFrequency><?php echo apply_filters( 'rss_update_frequency', '1' ); ?></sy:updateFrequency>
		<?php do_action('rss2_head'); ?>
		
		
		<?php //get events from db
		
		global $wpdb; 
		$mfgigcal_table = $wpdb->prefix . "mfgigcal";
		$mfgigcal_settings = get_option('mfgigcal_settings');
		
		// get the dates
		$today = date("Y-m-d");
		
		$sql = "SELECT * FROM $mfgigcal_table WHERE end_date >= '$today' ORDER BY start_date";
		
		$mfgigcal_events = $wpdb->get_results($sql);
		
		if (!empty($mfgigcal_events)) {
			foreach ($mfgigcal_events as $mfgigcal_event) { 
			?>
			
			<item>
				<?php $event_url = add_query_arg('event_id', $mfgigcal_event->id, !empty($mfgigcal_settings['calendar_url']) ? $mfgigcal_settings['calendar_url'] : home_url('/')); ?>
				<guid isPermaLink="false"><?php echo esc_url($event_url); ?></guid>
				<title><![CDATA[<?=$mfgigcal_event->title?>]]></title>
				<link><?php echo esc_url($event_url); ?></link>
				<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', $mfgigcal_event->pub_date, false); ?></pubDate>
				<description><![CDATA[<p><?php echo mfgigcal_feed_FormatDate($mfgigcal_event->start_date, $mfgigcal_event->end_date); ?> <?=$mfgigcal_event->time?></p>
				<?php echo $mfgigcal_event->location;
				if (!empty($mfgigcal_settings['rss_details'])) { echo $mfgigcal_event->details; } 
				?>]]></description>
				<mfgigcal:event-date><?php echo mfgigcal_feed_FormatDate($mfgigcal_event->start_date, $mfgigcal_event->end_date); ?></mfgigcal:event-date>
				<mfgigcal:event-time><![CDATA[<?=$mfgigcal_event->time?>]]></mfgigcal:event-time>
				<mfgigcal:location><![CDATA[<?=$mfgigcal_event->location?>]]></mfgigcal:location>
				<mfgigcal:content><![CDATA[<?=$mfgigcal_event->details?>]]></mfgigcal:content>
			</item>
		
			<?php 
			} //end event loop 
		} //end check for events
		?>
		
	</channel>
	</rss>

	<?php
}
add_action('do_feed_mfgigcal', 'create_my_customfeed', 10, 1); // Make sure to have 'do_feed_customfeed'


function custom_feed_rewrite() {
	global $wp_rewrite;
	$feed_rules = array(
	'feed/mfgigcal' => 'index.php?feed=mfgigcal',
	'mfgigcal.xml' => 'index.php?feed=mfgigcal'
	);
	$wp_rewrite->rules = $feed_rules + $wp_rewrite->rules;
	return $wp_rewrite->rules;
}
add_filter('generate_rewrite_rules', 'custom_feed_rewrite');



function mfgigcal_feed_FormatDate($start_date, $end_date) {

	$mfgigcal_options = get_option('mfgigcal_settings');

	$startArray = explode("-", $start_date);
	$start_date = mktime(0,0,0,$startArray[1],$startArray[2],$startArray[0]);
	
	$endArray = explode("-", $end_date);
	$end_date = mktime(0,0,0,$endArray[1],$endArray[2],$endArray[0]);
	
	$mfgigcal_date = "";
	if (empty($mfgigcal_options['rss_date_format'])) $mfgigcal_options['rss_date_format'] = "mdy";
	
	if ($start_date == $end_date) {
		if ($startArray[2] == "00") {
			$start_date = mktime(0,0,0,$startArray[1],15,$startArray[0]);			
			$mfgigcal_date .= date_i18n("F, Y", $start_date);
			return $mfgigcal_date;
		}
		if ($mfgigcal_options['rss_date_format'] == "mdy") $mfgigcal_date .= date_i18n("M j, Y", $start_date);
		if ($mfgigcal_options['rss_date_format'] == "dmy") $mfgigcal_date .= date_i18n("j M, Y", $start_date);
		return $mfgigcal_date;
	}
	
	if ($startArray[0] == $endArray[0]) {
		if ($startArray[1] == $endArray[1]) {
			if ($mfgigcal_options['rss_date_format'] == "mdy") $mfgigcal_date .= date_i18n("M j", $start_date) . "-" . date_i18n("j, Y", $end_date);
			if ($mfgigcal_options['rss_date_format'] == "dmy") $mfgigcal_date .= date_i18n("j", $start_date) . "-" . date_i18n("j M, Y", $end_date);
			return $mfgigcal_date;
		}
		if ($mfgigcal_options['rss_date_format'] == "mdy") $mfgigcal_date .= date_i18n("M j", $start_date) . "-" . date_i18n("M j, Y", $end_date);
		if ($mfgigcal_options['rss_date_format'] == "dmy") $mfgigcal_date .= date_i18n("j M", $start_date) . "-" . date_i18n("j M, Y", $end_date);
		return $mfgigcal_date;
	
	}
	if ($mfgigcal_options['rss_date_format'] == "mdy") $mfgigcal_date .= date_i18n("M j, Y", $start_date) . "-" . date_i18n("M j, Y", $end_date);
	if ($mfgigcal_options['rss_date_format'] == "dmy") $mfgigcal_date .= date_i18n("j M, Y", $start_date) . "-" . date_i18n("j M, Y", $end_date);
	return $mfgigcal_date;
}




?>