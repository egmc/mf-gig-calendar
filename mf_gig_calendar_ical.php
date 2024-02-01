<?php


function generate_ical_data() {
    $events = mfgigcal_get_evens_for_ical();

    $ical_header = "BEGIN:VCALENDAR\r\n";
    $ical_header .= "VERSION:2.0\r\n";
    $ical_header .= "METHOD:PUBLISH\r\n";
    $ical_header .= "PRODID:-//test//My Calendar//EN\r\n";

    $ical_footer = "END:VCALENDAR\r\n";

    $ical_events = "";
    foreach ($events as $event) {
        $ical_events .= mfgigcal_format_vevent($event);
    }
    // echo $ical_events;
    $ical_data = $ical_header . $ical_events . $ical_footer;

    return $ical_data;
}

function mfgigcal_escape_ical_content($content) {
    $escape_characters = [
        '\\' => '\\\\',
        ';' => '\\;',
        ',' => '\\,',
        "\n" => '\\n',
    ];
    $content = str_replace(array_keys($escape_characters), array_values($escape_characters), $content);

    return $content;
}

function mfgigcal_format_vevent($event) {

    $now = (new DateTime())->setTimezone(new DateTimeZone('UTC'));
    $now_dtstamp =  $now->format('Ymd') . "T" . $now->format('His') . "Z";

    $summary = mfgigcal_escape_ical_content(strip_tags($event->title));
    $description = mfgigcal_escape_ical_content(strip_tags($event->details));
    $location = mfgigcal_escape_ical_content(strip_tags($event->location));

    $summary = str_replace(["\r"], '',$summary);
    $description = str_replace(["\r"], '',$description);
    $location = str_replace(["\r"], '',$location);


    $ret = "";
    $ret .= "BEGIN:VEVENT\r\n";
    $ret .= "UID:testcal-{$event->id}\r\n";
    $ret .= "DTSTAMP:{$now_dtstamp}\r\n";
    $ret .= "DTSTART;VALUE=DATE:{$event->start_date_ymd}\r\n";
    $ret .= "DTEND;VALUE=DATE:{$event->end_date_ymd}\r\n";
    $ret .= "SUMMARY:{$summary}\r\n";
    $ret .= "DESCRIPTION:{$description}\r\n";
    $ret .= "LOCATION:{$location}\r\n";
    $ret .= "END:VEVENT\r\n";
    return $ret;

}

function mfgigcal_get_evens_for_ical() {
    global $wpdb; 

    $start_date = (new DateTime())->sub(new DateInterval('P1Y'))->format('Y-m-d');

    $mfgigcal_table = $wpdb->prefix . "mfgigcal";

    $sql = $wpdb->prepare(
        "select
             *,
             date_format(start_date, '%%Y%%m%%d') as start_date_ymd,
             date_format(date_add(end_date, interval 1 day), '%%Y%%m%%d') as end_date_ymd
        from %i
        where
            start_date >= %s
        ",$mfgigcal_table,$start_date
    );
    return $wpdb->get_results($sql);

}

function mfgigcal_ical_template_redirect() {
    if (get_query_var('mfgigcal_ical')) {
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="mfgigcal.ics"');
        echo generate_ical_data();
        exit;
    }
}
function mfgigcal_custom_ical_rewrite() {
	add_rewrite_rule('mfgigcal-calender','index.php?mfgigcal_ical=1', 'top');
}

// add_filter('generate_rewrite_rules', 'mfgigcal_custom_ical_rewrite');
function mfgigcal_custom_ical_query_vars($vars) {
    $vars[] = 'mfgigcal_ical';
    return $vars;
}
add_filter('query_vars', 'mfgigcal_custom_ical_query_vars');


add_action('init', 'mfgigcal_custom_ical_rewrite');
add_action('template_redirect', 'mfgigcal_ical_template_redirect');