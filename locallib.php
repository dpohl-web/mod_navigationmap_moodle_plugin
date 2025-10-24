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
 * Private navigationmap module utility functions
 *
 * @package mod_navigationmap
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/navigationmap/lib.php");
// require_once("$CFG->dirroot/mod/resource/locallib.php");


/**
 * File browsing support class
 */
class navigationmap_content_file_info extends file_info_stored {
    public function get_parent() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }
    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }
}

function navigationmap_get_editor_options($context) {
    global $CFG;
    return array('subdirs'=>1, 'maxbytes'=>$CFG->maxbytes, 'maxfiles'=>-1, 'changeformat'=>1, 'context'=>$context, 'noclean'=>1, 'trusttext'=>0);
}

/**
 * Display embedded resource file.
 * @param object $resource
 * @param object $cm
 * @param object $course
 * @param stored_file $file main file
 * @return does not return
 */
function navigationmap_display_embed($navigationmap, $cm, $course, $file, $filearea, $repeatables = null) {
    global $CFG, $PAGE, $OUTPUT;

    $clicktoopen = navigationmap_get_clicktoopen($file, $navigationmap->revision);
    
    $context = context_module::instance($cm->id);
    $path = '/'.$context->id.'/mod_navigationmap/'. $filearea. '/'.$navigationmap->revision.$file->get_filepath().$file->get_filename();
    $fullurl = file_encode_url($CFG->wwwroot.'/pluginfile.php', $path, false);
    $moodleurl = new moodle_url('/pluginfile.php' . $path);

    $mimetype = $file->get_mimetype();
    $title    = $navigationmap->name;

    $extension = resourcelib_get_extension($file->get_filename());

    $mediamanager = core_media_manager::instance($PAGE);
    $embedoptions = array(
        core_media_manager::OPTION_TRUSTED => true,
        core_media_manager::OPTION_BLOCK => true,
    );

    if (file_mimetype_in_typegroup($mimetype, 'web_image')) {  // It's an image
    	$code = navigationmap_embed_image($fullurl, $title, $repeatables, $navigationmap->is_map);

    } else if ($mimetype === 'application/pdf') {
        // PDF document
        $code = resourcelib_embed_pdf($fullurl, $title, $clicktoopen);

    } else if ($mediamanager->can_embed_url($moodleurl, $embedoptions)) {
        // Media (audio/video) file.
        $code = $mediamanager->embed_url($moodleurl, $title, 0, 0, $embedoptions);

    } else {
        // We need a way to discover if we are loading remote docs inside an iframe.
        $moodleurl->param('embed', 1);

        // anything else - just try object tag enlarged as much as possible
        $code = resourcelib_embed_general($moodleurl, $title, $clicktoopen, $mimetype);
    }


    return $code;
}

/**
 * Display Title and info for hotspots tuning.
 * @return string The html string
 */
function navigationmap_get_hotspots_tuning_html() {
    ob_start();
?>
<div class="navigationmap__hotpot-info">
    <h3><?php echo(get_string('hotspot_drag_header', 'navigationmap'))?></h3>
    <p><?php echo(get_string('hotspot_drag_help', 'navigationmap'))?></p>
</div>
<?php
    $html = ob_get_clean();

    return $html;
}

/**
 * Display interaction buttons for the hotpot settings.
 * @return string The html string for the buttons
 */
function navigationmap_get_interaction_buttons_html() {
    ob_start();
?>
<div class="navigationmap__hotpot-interaction--wrapper">
    <button type="button" id="navigationmap__hotpot-button--reset" class="navigationmap__hotpot-button navigationmap__hotpot-button--reset btn btn-primary"><?php echo(get_string('reset_hotspots', 'navigationmap'))?></button>
</div>
<?php
    $html = ob_get_clean();

    return $html;
}

/**
 * Returns image embedding html.
 * @param string $fullurl
 * @param string $title
 * @return string html
 */
function navigationmap_embed_image($fullurl, $title, $repeatables, $is_map) {
	
	
	
	if (! $repeatables) {
		$code = '';
		$code .= '<div class="resourcecontent resourceimg">';
		$code .= "<img title=\"".s(strip_tags(format_string($title)))."\" class=\"navigationimage\" src=\"$fullurl\" alt=\"\" />";
		$code .= '</div>';
	} else {
		$code = '';
		$code .= '<div class="navigationmap__hotspotimage-wrapper">';
		$code .= "<img title=\"".s(strip_tags(format_string($title)))."\" class=\"navigationmap__hotspotimage\" src=\"$fullurl\" alt=\"\" />";
        $index = 0;
		foreach($repeatables as $value) {
			$x_value = property_exists($value, 'room_hotspot_xvalue') ? $value->room_hotspot_xvalue : $value->topic_hotspot_xvalue;
			$y_value = property_exists($value, 'room_hotspot_yvalue') ? $value->room_hotspot_yvalue : $value->topic_hotspot_yvalue;
			$number = property_exists($value, 'room_hotspot_number') ? $value->room_hotspot_number : $value->topic_hotspot_number;
			$code .= "<div data-ismap='". $is_map. "' data-hotspot-index='". $index. "' data-hotspot-xvalue='". $x_value. "' data-hotspot-yvalue='". $y_value. "' class='navigationmap__hotspot' style='left: $x_value%; top: $y_value%;'>$number</div>";
            $index++;
		}
		$code .= '</div>';
	}
	
	return $code;
}

/**
 * Internal function - create click to open text with link.
 */
function navigationmap_get_clicktoopen($file, $revision, $extra='') {
	global $CFG;
	
	$filename = $file->get_filename();
	$path = '/'.$file->get_contextid().'/mod_navigationmap/content/'.$revision.$file->get_filepath().$file->get_filename();
	$fullurl = file_encode_url($CFG->wwwroot.'/pluginfile.php', $path, false);
	
	$string = get_string('clicktoopen2', 'resource', "<a href=\"$fullurl\" $extra>$filename</a>");
	
	return $string;
}