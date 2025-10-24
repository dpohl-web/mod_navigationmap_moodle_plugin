<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Navigationmap module version information
 *
 * @package mod_navigationmap
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/navigationmap/lib.php');
require_once($CFG->dirroot.'/mod/navigationmap/locallib.php');
require_once($CFG->dirroot.'/mod/resource/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
$p       = optional_param('p', 0, PARAM_INT);  // Navigationmap instance ID
$inpopup = optional_param('inpopup', 0, PARAM_BOOL);
$redirect = optional_param('redirect', 0, PARAM_BOOL);
$forceview = optional_param('forceview', 0, PARAM_BOOL);

if ($p) {
    if (!$navigationmap = $DB->get_record('navigationmap', array('id'=>$p))) {
        print_error('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('navigationmap', $navigationmap->id, $navigationmap->course, false, MUST_EXIST);

} else {
    if (!$cm = get_coursemodule_from_id('navigationmap', $id)) {
        print_error('invalidcoursemodule');
    }
    $navigationmap = $DB->get_record('navigationmap', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/navigationmap:view', $context);

// Completion and trigger events.
navigationmap_view($navigationmap, $course, $cm, $context);

$PAGE->set_url('/mod/navigationmap/view.php', array('id' => $cm->id));

$options = empty($navigationmap->displayoptions) ? [] : (array) unserialize_array($navigationmap->displayoptions);

$activityheader = ['hidecompletion' => false];
if (empty($options['printintro']) || !trim(strip_tags($navigationmap->intro))) {
    $activityheader['description'] = '';
}

if ($inpopup and $navigationmap->display == RESOURCELIB_DISPLAY_POPUP) {
    $PAGE->set_navigationmaplayout('popup');
    $PAGE->set_title($course->shortname.': '.$navigationmap->name);
    $PAGE->set_heading($course->fullname);
} else {
	$PAGE->add_body_class('limitedwidth');
    $PAGE->set_title($course->shortname.': '.$navigationmap->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($navigationmap);
	if (!$PAGE->activityheader->is_title_allowed()) {
        $activityheader['title'] = "";
    }
}

$PAGE->activityheader->set_attrs($activityheader);
echo $OUTPUT->header();

$short_description = $navigationmap->short_description;

echo "<h3>". get_string( 'short_description', 'navigationmap' ). "</h3>";
echo "<div class='navigationmap__short_descritpion'>$short_description</div>";

echo "<h3>". get_string( 'long_description', 'navigationmap' ). "</h3>";
$content = file_rewrite_pluginfile_urls($navigationmap->content, 'pluginfile.php', $context->id, 'mod_navigationmap', 'content', $navigationmap->revision);
$formatoptions = new stdClass;
$formatoptions->noclean = true;
$formatoptions->overflowdiv = true;
$formatoptions->context = $context;
$content = format_text($content, $navigationmap->contentformat, $formatoptions);
echo $OUTPUT->box($content, "generalbox center clearfix");

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_navigationmap', 'card_image', 0, 'sortorder DESC, id ASC', false); // TODO: this is not very efficient!!
if (count($files) < 1) {
	resource_print_filenotfound($navigationmap, $cm, $course);
	die;
} else {
	$file = reset($files);
	unset($files);
}

if ($navigationmap->is_map === '1') {
	$repeatables_sql_params = array('navigationmap_id' => $navigationmap->id);
	$repeatables_sql = "SELECT nrh.id, nrh.navigationmap_id, nrh.room_id, nrh.room_hotspot_xvalue, nrh.room_hotspot_yvalue, nrh.room_hotspot_number, nm.coursemodule FROM {navigationmap} AS nm INNER JOIN {navigationmap_room_hotspots} AS nrh ON nm.id = nrh.room_id WHERE nrh.navigationmap_id = :navigationmap_id";
	$repeatables = $DB->get_records_sql($repeatables_sql, $repeatables_sql_params);
	
	// Order of the rooms as dragged in the frontend
	$map_sequence_object = $DB->get_record('course_sections', array('course' => $course->id, 'section' => 0 ));
	$map_sequence = explode(',', $map_sequence_object->sequence);
	$ordered_repeatables = array();
	
	for ($x = 0; $x < count($map_sequence); $x++) {
		foreach ($repeatables as $value) {
			if ($map_sequence[$x] === $value->coursemodule) {
				$ordered_repeatables[] = $value;
			}
		}
		
	}
	$repeatables = $ordered_repeatables;
	
	
	
} else {
	$params = array('course_id' => $navigationmap->course, 'navigationmap_id' => $navigationmap->id);
	$get_all_topics_sql = "SELECT *, nttr.id AS nttr_id FROM {course_sections} AS cs INNER JOIN {navigationmap_topics_to_room} AS nttr ON cs.id = nttr.topic_id WHERE course = :course_id AND nttr.navigationmap_id = :navigationmap_id";
	$repeatables = $DB->get_records_sql($get_all_topics_sql, $params);
}

$navigationmap->mainfile = $file->get_filename();
echo "<h3>". get_string( 'card_image', 'navigationmap' ). "</h3>";
echo(navigationmap_display_embed( $navigationmap, $cm, $course, $file, 'card_image', $repeatables));

$repeatable_index = 1;
foreach ($repeatables as $key => $value) {
	if ($navigationmap->is_map === '1') {
		$room = $DB->get_record('navigationmap', array('id' => $value->room_id));
		echo "<h3>". get_string( 'room_to_map_header', 'navigationmap' ). " ". $repeatable_index . "</h3>";
		echo "<h4>$room->name</h3>";
		echo "<p>$room->short_description</p>";
		$cm_room = get_coursemodule_from_instance('navigationmap', $value->room_id, $navigationmap->course, false, MUST_EXIST);
		$context_room = context_module::instance($cm_room->id);
		$room_files = $fs->get_area_files($context_room->id, 'mod_navigationmap', 'card_image', 0, 'sortorder DESC, id ASC', false); // TODO: this is not very efficient!!
		if (count($room_files)  > 0) {
			$room_file = reset($room_files);
			unset($room_files);
			echo(navigationmap_display_embed( $navigationmap, $cm_room, $course, $room_file, 'card_image'));
		}
		
		
	} else {
		$topic_navigation_image_file = $fs->get_area_files($context->id, 'mod_navigationmap', 'topicimage_'. ($repeatable_index - 1), 0, 'sortorder DESC, id ASC', false);
		echo "<h3>". get_string( 'topicinroomheader', 'navigationmap' ). " ". $value->section . "</h3>";
		echo "<h4>$value->name</h3>";
		echo "<p>$value->topic_shortdescription</p>";
		if (count($topic_navigation_image_file) > 0) {
			$topic_image_file = reset($topic_navigation_image_file);
			unset($topic_navigation_image_file);
			echo(navigationmap_display_embed( $navigationmap, $cm, $course, $topic_image_file, 'topicimage_'. ($repeatable_index - 1)));
		} else {
			echo  "<img src='". $OUTPUT->image_url('placeholder', 'navigationmap'). "' alt='missing image' style='width: 100%; max-width: 300px'/>";
		}
		
	}
	
	$repeatable_index++;
}

$strlastmodified = get_string("lastmodified");
echo "<div class=\"modified\">$strlastmodified: ".userdate($navigationmap->timemodified)."</div>";

echo $OUTPUT->footer();
