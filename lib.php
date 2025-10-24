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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package mod_navigationmap
 * @copyright 2009 Petr Skoda (http://skodak.org)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined( 'MOODLE_INTERNAL' ) || die();

/**
 * List of features supported in Navigationmap module
 *
 * @param string $feature
 *        	FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function navigationmap_supports($feature) {
	switch ($feature) {
		case FEATURE_MOD_ARCHETYPE :
			return MOD_ARCHETYPE_RESOURCE;
		case FEATURE_GROUPS :
			return false;
		case FEATURE_GROUPINGS :
			return false;
		case FEATURE_MOD_INTRO :
			return false;
		case FEATURE_COMPLETION_TRACKS_VIEWS :
			return true;
		case FEATURE_GRADE_HAS_GRADE :
			return false;
		case FEATURE_GRADE_OUTCOMES :
			return false;
		case FEATURE_BACKUP_MOODLE2 :
			return true;
		case FEATURE_SHOW_DESCRIPTION :
			return true;
		case FEATURE_MOD_PURPOSE:
			return MOD_PURPOSE_CONTENT;

		default :
			return null;
	}
}

/**
 * Returns all other caps used in module
 *
 * @return array
 */
function navigationmap_get_extra_capabilities() {
	return array (
			'moodle/site:accessallgroups'
	);
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param $data the
 *        	data submitted from the reset course.
 * @return array status array
 */
function navigationmap_reset_userdata($data) {

	// Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
	// See MDL-9367.
	return array ();
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 * crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 * be considered as view action.
 *
 * @return array
 */
function navigationmap_get_view_actions() {
	return array (
			'view',
			'view all'
	);
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 * crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 * will be considered as post action.
 *
 * @return array
 */
function navigationmap_get_post_actions() {
	return array (
			'update',
			'add'
	);
}

/**
 * Add navigationmap instance.
 *
 * @param stdClass $data
 * @param mod_navigationmap_mod_form $mform
 * @return int new navigationmap instance id
 */
function navigationmap_add_instance($data, $mform = null) {
	global $CFG, $DB;
	require_once ("$CFG->libdir/resourcelib.php");

	$cmid = $data->coursemodule;

	$data->timemodified = time();
	$displayoptions = array ();
	if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
		$displayoptions['popupwidth'] = $data->popupwidth;
		$displayoptions['popupheight'] = $data->popupheight;
	}
	$displayoptions['printintro'] = $data->printintro;
	$data->displayoptions = serialize( $displayoptions );

	if ($mform) {
		$data->content = $data->navigationmap['text'];
		$data->contentformat = $data->navigationmap['format'];
	}

	$context = context_module::instance( $cmid );

	$topics = new stdClass();
	// if ($data->is_map !== '1') {
	// $topics->topicid = $data->topicid;
	// $topics->topicshortdescription = $data->topicshortdescription;
	// $topics->topicimage = $data->topicimage;
	// $topics->topic_hotspot_xvalue = $data->topic_hotspot_xvalue;
	// $topics->topic_hotspot_yvalue = $data->topic_hotspot_yvalue;
	// $topics->topic_hotspot_number = $data->topic_hotspot_number;

	// unset($data->topicid, $data->topicshortdescription, $data->topicimage);
	// }

	// $data->towhichmapbelongstheroom = $data->is_map !== '1' ? $data->towhichmapbelongstheroom : 0;

	$topics->navigationmap_id = $DB->insert_record( 'navigationmap', $data );
	$data->id = $topics->navigationmap_id;

	// if ($data->is_map !== '1') {
	// topics_after_add_or_update($topics, $context, $cmid, $mform, $data->is_map);
	// }

	// Insert Rooms belongs to map for hotspots
	// $all_mapped_rooms = new stdClass();
	// $all_mapped_rooms->navigationmap_id = $data->id;
	// $all_mapped_rooms->room_id = $data->room_to_map_room_id;
	// $all_mapped_rooms->room_hotspot_xvalue = $data->room_to_map_room_hotspot_xvalue;
	// $all_mapped_rooms->room_hotspot_yvalue = $data->room_to_map_room_hotspot_yvalue;
	// $all_mapped_rooms->room_hotspot_number = $data->room_to_map_room_hotspot_number;

	// unset($data->room_id, $data->room_hotspot_xvalue, $data->room_hotspot_yvalue, $data->room_hotspot_number);

	// if (($data->is_map === '1') && (count($all_mapped_rooms->room_id) > 0)) {
	// rooms_hotspots_after_add_or_update($all_mapped_rooms, $mform);
	// }

	if ($mform and ! empty( $data->navigationmap['itemid'] )) {
		$draftitemid = $data->navigationmap['itemid'];
		$data->content = file_save_draft_area_files( $draftitemid, $context->id, 'mod_navigationmap', 'content', 0, navigationmap_get_editor_options( $context ), $data->content );
		$DB->update_record( 'navigationmap', $data );
	}

	if ($data->card_image) {
		file_save_draft_area_files( $data->card_image, $context->id, 'mod_navigationmap', 'card_image', 0, array (
				'subdirs' => 0,
				'maxbytes' => 2485760,
				'areamaxbytes' => 2485760,
				'maxfiles' => 1,
				'accepted_types' => array (
						'jpg',
						'jpeg',
						'png',
						'gif'
				)
		) );
	}

	$completiontimeexpected = ! empty( $data->completionexpected ) ? $data->completionexpected : null;
	\core_completion\api::update_completion_date_event( $cmid, 'navigationmap', $data->id, $completiontimeexpected );

	return $data->id;
}

/**
 * Update navigationmap instance.
 *
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function navigationmap_update_instance($data, $mform) {
	global $CFG, $DB;
	require_once ("$CFG->libdir/resourcelib.php");

	$cmid = $data->coursemodule;
	$draftitemid = $data->navigationmap['itemid'];

	$data->timemodified = time();
	$data->id = $data->instance;
	$data->revision ++;

// 	$dbInstance = $DB->get_record( 'navigationmap', [ 
// 			'id' => $data->id
// 	] );

	$displayoptions = array ();
	if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
		$displayoptions['popupwidth'] = $data->popupwidth;
		$displayoptions['popupheight'] = $data->popupheight;
	}
	$displayoptions['printintro'] = $data->printintro;
	$data->displayoptions = serialize( $displayoptions );

	$data->content = $data->navigationmap['text'];
	$data->contentformat = $data->navigationmap['format'];
	
	$context = context_module::instance( $cmid );
	if ($data->is_map !== '1') {
		$topics = new stdClass();
		$topics->topicid = $data->topicid;
		$topics->topicshortdescription = $data->topicshortdescription;
		$topics->topicimage = $data->topicimage;
		$topics->navigationmap_id = $data->id;
		// if (isset($data->towhichmapbelongstheroom) && $data->towhichmapbelongstheroom) {
		// // $topics->topic_hotspot_xvalue = $data->topic_hotspot_xvalue ? $data->topic_hotspot_xvalue : 0;
		// // $topics->topic_hotspot_yvalue = $data->topic_hotspot_yvalue ? $data->topic_hotspot_yvalue : 0;
		// // $topics->topic_hotspot_number = $data->topic_hotspot_number ? $data->topic_hotspot_number : '0';
		$topics->topic_hotspot_xvalue = isset( $data->topic_hotspot_xvalue ) ? $data->topic_hotspot_xvalue : 0;
		$topics->topic_hotspot_yvalue = isset( $data->topic_hotspot_yvalue ) ? $data->topic_hotspot_yvalue : 0;
		$topics->topic_hotspot_number = isset( $data->topic_hotspot_number ) ? $data->topic_hotspot_number : 0;
// 		$topics->order_number = array(2,1,3);
		// }

		unset( $data->topicid, $data->topicshortdescription, $data->topicimage );
	} else {
		$mapped_rooms = new stdClass();
		$mapped_rooms->room_id = $data->room_id;
		$mapped_rooms->navigationmap_id = $data->id;
		$mapped_rooms->room_hotspot_xvalue = isset( $data->room_hotspot_xvalue ) ? $data->room_hotspot_xvalue : 0;
		$mapped_rooms->room_hotspot_yvalue = isset( $data->room_hotspot_yvalue ) ? $data->room_hotspot_yvalue : 0;
		$mapped_rooms->room_hotspot_number = isset( $data->room_hotspot_number ) ? $data->room_hotspot_number : 0;
		unset( $data->room_id );
	}

	// // Insert Rooms belongs to map for hotspots
	// if (isset( $data->room_id )) {
	// $all_mapped_rooms = new stdClass();
	// $all_mapped_rooms->navigationmap_id = $data->id;
	// $all_mapped_rooms->room_id = $data->room_id;
	// $all_mapped_rooms->room_hotspot_xvalue = $data->room_hotspot_xvalue;
	// $all_mapped_rooms->room_hotspot_yvalue = $data->room_hotspot_yvalue;
	// $all_mapped_rooms->room_hotspot_number = $data->room_hotspot_number;

	// if (($data->is_map === '1') && (count( $all_mapped_rooms->room_id ) > 0)) {
	// rooms_hotspots_after_add_or_update( $all_mapped_rooms, $mform );
	// }

	// unset( $data->room_id, $data->room_hotspot_xvalue, $data->room_hotspot_yvalue, $data->room_hotspot_number, $data->room_map_name );
	// }

	if ($data->is_map !== '1') {
		topics_after_add_or_update( $topics, $context, $cmid, $mform );
	} else {
		rooms_after_add_or_update($mapped_rooms, $context, $cmid, $mform);
	}

	// if (($data->is_map === '1') && $data->room_id && (count($all_mapped_rooms->room_id) > 0)) {
	// rooms_hotspots_after_add_or_update($all_mapped_rooms, $mform);
	// }

	// $data->towhichmapbelongstheroom = $dbInstance->is_map !== '1' ? $data->towhichmapbelongstheroom : 0;
	$DB->update_record( 'navigationmap', $data );

	if ($draftitemid) {
		$data->content = file_save_draft_area_files( $draftitemid, $context->id, 'mod_navigationmap', 'content', 0, navigationmap_get_editor_options( $context ), $data->content );
		$DB->update_record( 'navigationmap', $data );
	}

	if ($data->card_image) {
		file_save_draft_area_files( $data->card_image, $context->id, 'mod_navigationmap', 'card_image', 0, array (
				'subdirs' => 0,
				'maxbytes' => 2485760,
				'areamaxbytes' => 2485760,
				'maxfiles' => 1,
				'accepted_types' => array (
						'jpg',
						'jpeg',
						'png',
						'gif'
				)
		) );
	}

	$completiontimeexpected = ! empty( $data->completionexpected ) ? $data->completionexpected : null;
	\core_completion\api::update_completion_date_event( $cmid, 'navigationmap', $data->id, $completiontimeexpected );

	return true;
}

/**
 * Delete navigationmap instance.
 *
 * @param int $id
 * @return bool true
 */
function navigationmap_delete_instance($id) {
	global $DB;

	if (! $navigationmap = $DB->get_record( 'navigationmap', array (
			'id' => $id
	) )) {
		return false;
	}

	$cm = get_coursemodule_from_instance( 'navigationmap', $id );
	\core_completion\api::update_completion_date_event( $cm->id, 'navigationmap', $id, null );
// 	if ($navigationmap->is_map === '1') {
// 		$DB->set_field( 'navigationmap', 'towhichmapbelongstheroom', 0, array (
// 				'towhichmapbelongstheroom' => $navigationmap->id
// 		) );
// 	}
	// note: all context files are deleted automatically

	$DB->delete_records( 'navigationmap', array (
			'id' => $navigationmap->id
	) );
	$DB->delete_records( 'navigationmap_topics_to_room', array (
			'navigationmap_id' => $navigationmap->id
	) );
	$DB->delete_records( 'navigationmap_room_hotspots', array (
			'navigationmap_id' => $navigationmap->id
	) );

	return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link course_modinfo::get_array_of_activities()}
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info Info to customise main navigationmap display
 */
function navigationmap_get_coursemodule_info($coursemodule) {
	global $CFG, $DB;
	require_once ("$CFG->libdir/resourcelib.php");

	if (! $navigationmap = $DB->get_record( 'navigationmap', array (
			'id' => $coursemodule->instance
	), 'id, name, display, displayoptions' )) {
		return NULL;
	}

	$info = new cached_cm_info();
	$info->name = $navigationmap->name;

	if ($coursemodule->showdescription) {
		// Convert intro to html. Do not filter cached version, filters run at display time.
		$info->content = format_module_intro( 'navigationmap', $navigationmap, $coursemodule->id, false );
	}

	if ($navigationmap->display != RESOURCELIB_DISPLAY_POPUP) {
		return $info;
	}

	$fullurl = "$CFG->wwwroot/mod/navigationmap/view.php?id=$coursemodule->id&amp;inpopup=1";
	$options = empty($navigationmap->displayoptions) ? [] : (array) unserialize_array($navigationmap->displayoptions);
	$width = empty( $options['popupwidth'] ) ? 620 : $options['popupwidth'];
	$height = empty( $options['popupheight'] ) ? 450 : $options['popupheight'];
	$wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
	$info->onclick = "window.open('$fullurl', '', '$wh'); return false;";

	return $info;
}

/**
 * Lists all browsable file areas
 *
 * @package mod_navigationmap
 * @category files
 * @param stdClass $course
 *        	course object
 * @param stdClass $cm
 *        	course module object
 * @param stdClass $context
 *        	context object
 * @return array
 */
function navigationmap_get_file_areas($course, $cm, $context) {
	$areas = array ();
	$areas['content'] = get_string( 'content', 'navigationmap' );
	return $areas;
}

/**
 * File browsing support for navigationmap module content area.
 *
 * @package mod_navigationmap
 * @category files
 * @param stdClass $browser
 *        	file browser instance
 * @param stdClass $areas
 *        	file areas
 * @param stdClass $course
 *        	course object
 * @param stdClass $cm
 *        	course module object
 * @param stdClass $context
 *        	context object
 * @param string $filearea
 *        	file area
 * @param int $itemid
 *        	item ID
 * @param string $filepath
 *        	file path
 * @param string $filename
 *        	file name
 * @return file_info instance or null if not found
 */
function navigationmap_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
	global $CFG;

	if (! has_capability( 'moodle/course:managefiles', $context )) {
		// students can not peak here!
		return null;
	}

	$fs = get_file_storage();

	if ($filearea === 'content') {
		$filepath = is_null( $filepath ) ? '/' : $filepath;
		$filename = is_null( $filename ) ? '.' : $filename;

		$urlbase = $CFG->wwwroot . '/pluginfile.php';
		if (! $storedfile = $fs->get_file( $context->id, 'mod_navigationmap', 'content', 0, $filepath, $filename )) {
			if ($filepath === '/' and $filename === '.') {
				$storedfile = new virtual_root_file( $context->id, 'mod_navigationmap', 'content', 0 );
			} else {
				// not found
				return null;
			}
		}
		require_once ("$CFG->dirroot/mod/navigationmap/locallib.php");
		return new navigationmap_content_file_info( $browser, $context, $storedfile, $urlbase, $areas[$filearea], true, true, true, false );
	}

	// note: navigationmap_intro handled in file_browser automatically

	return null;
}

function get_navigationmap_file($course_module_id, $filearea) {
	$context = context_module::instance($course_module_id);
	$fs = get_file_storage();
	$files = $fs->get_area_files($context->id, 'mod_navigationmap', $filearea, 0, $sort = false, $includedirs = false);
	if (!count($files)) return false;
	return array_shift($files);
} // function get_rainmaker_file


function get_navigationmap_doc_url() {
	global $id; // the course_module id
	if (! $file = get_navigationmap_file($id)) return false;
	return moodle_url::make_pluginfile_url(
			$file->get_contextid(),
			$file->get_component(),
			$file->get_filearea(),
			$file->get_itemid(),
			$file->get_filepath(),
			$file->get_filename(),
			$forcedownload = false);
} // function get_rainmaker_doc_url


/**
 * Serves the navigationmap files.
 *
 * @package mod_navigationmap
 * @category files
 * @param stdClass $course
 *        	course object
 * @param stdClass $cm
 *        	course module object
 * @param stdClass $context
 *        	context object
 * @param string $filearea
 *        	file area
 * @param array $args
 *        	extra arguments
 * @param bool $forcedownload
 *        	whether or not force download
 * @param array $options
 *        	additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function navigationmap_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
// 	if ($filearea === 'topicimage') {
// 		error_log('##########################################################'. $filearea);
// 		error_log(print_r(debug_backtrace ()), true);
// 	}
	$is_topic_image = preg_match('/topicimage_(.)*/', $filearea, $matches, PREG_OFFSET_CAPTURE);
	global $CFG, $DB;
	require_once ("$CFG->libdir/resourcelib.php");

	if ($context->contextlevel != CONTEXT_MODULE) {
		return false;
	}

	require_course_login( $course, true, $cm );
	if (! has_capability( 'mod/navigationmap:view', $context )) {
		return false;
	}

	if ($filearea === 'content') {
		// $arg could be revision number or index.html
		$arg = array_shift( $args );
		if ($arg == 'index.html' || $arg == 'index.htm') {
			// serve navigationmap content
			$filename = $arg;
			
			if (! $navigationmap = $DB->get_record( 'navigationmap', array (
					'id' => $cm->instance
			), '*', MUST_EXIST )) {
				return false;
			}
			
			// We need to rewrite the pluginfile URLs so the media filters can work.
			$content = file_rewrite_pluginfile_urls( $navigationmap->content, 'webservice/pluginfile.php', $context->id, 'mod_navigationmap', 'content', $navigationmap->revision );
			$formatoptions = new stdClass();
			$formatoptions->noclean = true;
			$formatoptions->overflowdiv = true;
			$formatoptions->context = $context;
			$content = format_text( $content, $navigationmap->contentformat, $formatoptions );
			
			// Remove @@PLUGINFILE@@/.
			$options = array (
					'reverse' => true
			);
			$content = file_rewrite_pluginfile_urls( $content, 'webservice/pluginfile.php', $context->id, 'mod_navigationmap', 'content', $navigationmap->revision, $options );
			$content = str_replace( '@@PLUGINFILE@@/', '', $content );
			
			send_file( $content, $filename, 0, 0, true, true );
		} else {
			$fs = get_file_storage();
			$relativepath = implode( '/', $args );
			$fullpath = "/$context->id/mod_navigationmap/$filearea/0/$relativepath";
			if (! $file = $fs->get_file_by_hash( sha1( $fullpath ) ) or $file->is_directory()) {
				$navigationmap = $DB->get_record( 'navigationmap', array (
						'id' => $cm->instance
				), 'id, legacyfiles', MUST_EXIST );
				if ($navigationmap->legacyfiles != RESOURCELIB_LEGACYFILES_ACTIVE) {
					return false;
				}
				if (! $file = resourcelib_try_file_migration( '/' . $relativepath, $cm->id, $cm->course, 'mod_navigationmap', 'content', 0 )) {
					return false;
				}
				// file migrate - update flag
				$navigationmap->legacyfileslast = time();
				$DB->update_record( 'navigationmap', $navigationmap );
			}
			
			// finally send the file
			send_stored_file( $file, null, 0, $forcedownload, $options );
		}
// 	} elseif($filearea === 'card_image' || $filearea === 'topicimage') {
	} elseif($filearea === 'card_image') {
		array_shift($args); // ignore revision - designed to prevent caching problems only
		
		$fs = get_file_storage();
		$relativepath = implode('/', $args);
		$fullpath = rtrim("/$context->id/mod_navigationmap/$filearea/0/$relativepath", '/');
		do {
			if (!$file = $fs->get_file_by_hash(sha1($fullpath))) {
				if ($fs->get_file_by_hash(sha1("$fullpath/."))) {
					if ($file = $fs->get_file_by_hash(sha1("$fullpath/index.htm"))) {
						break;
					}
					if ($file = $fs->get_file_by_hash(sha1("$fullpath/index.html"))) {
						break;
					}
					if ($file = $fs->get_file_by_hash(sha1("$fullpath/Default.htm"))) {
						break;
					}
				}
				$navigationmap = $DB->get_record('navigationmap', array('id'=>$cm->instance), 'id, legacyfiles', MUST_EXIST);
				if ($navigationmap->legacyfiles != RESOURCELIB_LEGACYFILES_ACTIVE) {
					return false;
				}
				if (!$file = resourcelib_try_file_migration('/'.$relativepath, $cm->id, $cm->course, 'mod_navigationmap', $filearea, 0)) {
					return false;
				}
				// file migrate - update flag
				$navigationmap->legacyfileslast = time();
				$DB->update_record('resource', $navigationmap);
			}
		} while (false);
		
		// finally send the file
		send_stored_file($file, null, 0, $forcedownload, $options);
		
	} elseif($is_topic_image === 1) {
		if (! $file = get_navigationmap_file($cm->id, $filearea)) return false;
		send_stored_file($file);
	}

	
}

/**
 * Return a list of navigationmap types
 *
 * @param string $navigationmaptype
 *        	current navigationmap type
 * @param stdClass $parentcontext
 *        	Block's parent context
 * @param stdClass $currentcontext
 *        	Current context of block
 */
function navigationmap_navigationmap_type_list($navigationmaptype, $parentcontext, $currentcontext) {
	$module_navigationmaptype = array (
			'mod-navigationmap-*' => get_string( 'navigationmap-mod-navigationmap-x', 'navigationmap' )
	);
	return $module_navigationmaptype;
}

/**
 * Export navigationmap resource contents
 *
 * @return array of file content
 */
function navigationmap_export_contents($cm, $baseurl) {
	global $CFG, $DB;
	$contents = array ();
	$context = context_module::instance( $cm->id );

	$navigationmap = $DB->get_record( 'navigationmap', array (
			'id' => $cm->instance
	), '*', MUST_EXIST );

	// navigationmap contents
	$fs = get_file_storage();
// 	$files = $fs->get_area_files( $context->id, 'mod_navigationmap', 'content', 0, 'sortorder DESC, id ASC', false );
// 	foreach ( $files as $fileinfo ) {
// 		$file = array ();
// 		$file['type'] = 'file';
// 		$file['filename'] = $fileinfo->get_filename();
// 		$file['filepath'] = $fileinfo->get_filepath();
// 		$file['filesize'] = $fileinfo->get_filesize();
// 		$file['fileurl'] = file_encode_url( "$CFG->wwwroot/" . $baseurl, '/' . $context->id . '/mod_navigationmap/content/' . $navigationmap->revision . $fileinfo->get_filepath() . $fileinfo->get_filename(), true );
// 		$file['timecreated'] = $fileinfo->get_timecreated();
// 		$file['timemodified'] = $fileinfo->get_timemodified();
// 		$file['sortorder'] = $fileinfo->get_sortorder();
// 		$file['userid'] = $fileinfo->get_userid();
// 		$file['author'] = $fileinfo->get_author();
// 		$file['license'] = $fileinfo->get_license();
// 		$file['mimetype'] = $fileinfo->get_mimetype();
// 		$file['isexternalfile'] = $fileinfo->is_external_file();
// 		if ($file['isexternalfile']) {
// 			$file['repositorytype'] = $fileinfo->get_repository_type();
// 		}
// 		$contents[] = $file;
// 	}
	
	$files = $fs->get_area_files($context->id, 'mod_navigationmap', 'card_image', 0, 'sortorder DESC, id ASC', false);
	
	foreach ( $files as $fileinfo ) {
		$file = array ();
		$file['type'] = 'file';
		$file['filename'] = $fileinfo->get_filename();
		$file['filepath'] = $fileinfo->get_filepath();
		$file['filesize'] = $fileinfo->get_filesize();
		$file['fileurl'] = file_encode_url( "$CFG->wwwroot/" . $baseurl, '/' . $context->id . '/mod_navigationmap/card_image/' . $navigationmap->revision . $fileinfo->get_filepath() . $fileinfo->get_filename(), true );
		$file['timecreated'] = $fileinfo->get_timecreated();
		$file['timemodified'] = $fileinfo->get_timemodified();
		$file['sortorder'] = $fileinfo->get_sortorder();
		$file['userid'] = $fileinfo->get_userid();
		$file['author'] = $fileinfo->get_author();
		$file['license'] = $fileinfo->get_license();
		$file['mimetype'] = $fileinfo->get_mimetype();
		$file['isexternalfile'] = $fileinfo->is_external_file();
		if ($file['isexternalfile']) {
			$file['repositorytype'] = $fileinfo->get_repository_type();
		}
		$contents[] = $file;
	}
	
	if ($navigationmap->is_map !== '1') {
		
		$params = array('course_id' => $navigationmap->course, 'navigationmap_id' => $navigationmap->id);
		$get_all_topics_sql = "SELECT *, nttr.id AS nttr_id FROM {course_sections} AS cs INNER JOIN {navigationmap_topics_to_room} AS nttr ON cs.id = nttr.topic_id WHERE course = :course_id AND nttr.navigationmap_id = :navigationmap_id";
		$repeatables = $DB->get_records_sql($get_all_topics_sql, $params);
		
		for ($x = 0; $x < count($repeatables); $x++) {
			$files = $fs->get_area_files($context->id, 'mod_navigationmap', "topicimage_$x", 0, 'sortorder DESC, id ASC', false);
			
			foreach ( $files as $fileinfo ) {
				$file = array ();
				$file['type'] = 'file';
				$file['filename'] = $fileinfo->get_filename();
				$file['filepath'] = $fileinfo->get_filepath();
				$file['filesize'] = $fileinfo->get_filesize();
				$file['fileurl'] = file_encode_url( "$CFG->wwwroot/" . $baseurl, '/' . $context->id . "/mod_navigationmap/topicimage_$x/" . $navigationmap->revision . $fileinfo->get_filepath() . $fileinfo->get_filename(), true );
				$file['timecreated'] = $fileinfo->get_timecreated();
				$file['timemodified'] = $fileinfo->get_timemodified();
				$file['sortorder'] = $fileinfo->get_sortorder();
				$file['userid'] = $fileinfo->get_userid();
				$file['author'] = $fileinfo->get_author();
				$file['license'] = $fileinfo->get_license();
				$file['mimetype'] = $fileinfo->get_mimetype();
				$file['isexternalfile'] = $fileinfo->is_external_file();
				if ($file['isexternalfile']) {
					$file['repositorytype'] = $fileinfo->get_repository_type();
				}
				$contents[] = $file;
			}
		}
	}

// 	// navigationmap html conent
// 	$filename = 'index.html';
// 	$navigationmapfile = array ();
// 	$navigationmapfile['type'] = 'file';
// 	$navigationmapfile['filename'] = $filename;
// 	$navigationmapfile['filepath'] = '/';
// 	$navigationmapfile['filesize'] = 0;
// 	$navigationmapfile['fileurl'] = file_encode_url( "$CFG->wwwroot/" . $baseurl, '/' . $context->id . '/mod_navigationmap/content/' . $filename, true );
// 	$navigationmapfile['timecreated'] = null;
// 	$navigationmapfile['timemodified'] = $navigationmap->timemodified;
// 	// make this file as main file
// 	$navigationmapfile['sortorder'] = 1;
// 	$navigationmapfile['userid'] = null;
// 	$navigationmapfile['author'] = null;
// 	$navigationmapfile['license'] = null;
// 	$contents[] = $navigationmapfile;

	return $contents;
}

/**
 * Register the ability to handle drag and drop file uploads
 *
 * @return array containing details of the files / types the mod can handle
 */
function navigationmap_dndupload_register() {
	return array (
			'types' => array (
					array (
							'identifier' => 'text/html',
							'message' => get_string( 'createnavigationmap', 'navigationmap' )
					),
					array (
							'identifier' => 'text',
							'message' => get_string( 'createnavigationmap', 'navigationmap' )
					)
			)
	);
}

/**
 * Handle a file that has been uploaded
 *
 * @param object $uploadinfo
 *        	details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function navigationmap_dndupload_handle($uploadinfo) {
	// Gather the required info.
	$data = new stdClass();
	$data->course = $uploadinfo->course->id;
	$data->name = $uploadinfo->displayname;
	$data->intro = '<p>' . $uploadinfo->displayname . '</p>';
	$data->introformat = FORMAT_HTML;
	if ($uploadinfo->type == 'text/html') {
		$data->contentformat = FORMAT_HTML;
		$data->content = clean_param( $uploadinfo->content, PARAM_CLEANHTML );
	} else {
		$data->contentformat = FORMAT_PLAIN;
		$data->content = clean_param( $uploadinfo->content, PARAM_TEXT );
	}
	$data->coursemodule = $uploadinfo->coursemodule;

	// Set the display options to the site defaults.
	$config = get_config( 'navigationmap' );
	$data->display = $config->display;
	$data->popupheight = $config->popupheight;
	$data->popupwidth = $config->popupwidth;
	$data->printintro = $config->printintro;

	return navigationmap_add_instance( $data, null );
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param stdClass $navigationmap
 *        	navigationmap object
 * @param stdClass $course
 *        	course object
 * @param stdClass $cm
 *        	course module object
 * @param stdClass $context
 *        	context object
 * @since Moodle 3.0
 */
function navigationmap_view($navigationmap, $course, $cm, $context) {

	// Trigger course_module_viewed event.
	$params = array (
			'context' => $context,
			'objectid' => $navigationmap->id
	);

	$event = \mod_navigationmap\event\course_module_viewed::create( $params );
	$event->add_record_snapshot( 'course_modules', $cm );
	$event->add_record_snapshot( 'course', $course );
	$event->add_record_snapshot( 'navigationmap', $navigationmap );
	$event->trigger();

	// Completion.
	$completion = new completion_info( $course );
	$completion->set_module_viewed( $cm );
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param cm_info $cm
 *        	course module data
 * @param int $from
 *        	the time to check updates from
 * @param array $filter
 *        	if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function navigationmap_check_updates_since(cm_info $cm, $from, $filter = array()) {
	$updates = course_check_module_updates_since( $cm, $from, array (
			'content'
	), $filter );
	return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_navigationmap_core_calendar_provide_event_action(calendar_event $event, \core_calendar\action_factory $factory) {
	$cm = get_fast_modinfo( $event->courseid )->instances['navigationmap'][$event->instance];

	$completion = new \completion_info( $cm->get_course() );

	$completiondata = $completion->get_data( $cm, false );

	if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
		return null;
	}

	return $factory->create_instance( get_string( 'view' ), new \moodle_url( '/mod/navigationmap/view.php', [ 
			'id' => $cm->id
	] ), 1, true );
}

function generateToken() {
	// $date = new DateTime();
	$token = "";
	$characterArray = [ 
			"0123456789",
			"abcdefghijklmnopqrstuvwxyz"
	];
	$i = 0;

	for($i; $i < 10; $i ++) {
		$char = substr( $characterArray[0], mt_rand( 0, strlen( $characterArray[0] ) - 1 ), 1 );
		$token .= $char;
	}
	$i = 0;
	for($i; $i < 15; $i ++) {
		$char = substr( $characterArray[1], mt_rand( 0, strlen( $characterArray[1] ) - 1 ), 1 );
		$token .= $char;
	}
	$shuffledToken = str_shuffle( $token );
	$token = milliseconds() . '_' . $shuffledToken;
	return $token;
}

function milliseconds() {
	$mt = explode( ' ', microtime() );
	return (( int ) $mt[1]) * 1000 + (( int ) round( $mt[0] * 1000 ));
}

/**
 * This function is called at the end of quiz_add_instance
 * and quiz_update_instance, to do the common processing.
 *
 * @param object $quiz
 *        	the quiz object.
 */
function topics_after_add_or_update($data, $context, $cmid, $mform) {
	global $DB;
	$DB->delete_records( 'navigationmap_topics_to_room', array (
			'navigationmap_id' => $data->navigationmap_id
	) );
		// insert topics
		if (count( $data->topicid ) > 0) {
			for($x = 0; $x < count( $data->topicid ); $x ++) {
				$topic = new stdClass();
				$topic->navigationmap_id = $data->navigationmap_id;
				$topic->topic_id = $data->topicid[$x];
				$topic->topic_shortdescription = $data->topicshortdescription[$x];
				$topic->topic_hotspot_xvalue = $data->topic_hotspot_xvalue[$x];
				$topic->topic_hotspot_yvalue = $data->topic_hotspot_yvalue[$x];
				$topic->topic_hotspot_number = $data->topic_hotspot_number[$x];

				$id = $DB->insert_record( 'navigationmap_topics_to_room', $topic );

				if ($data->topicimage[$x]) {
					$topic_image_draft_id = $data->topicimage[$x];
					file_save_draft_area_files( $data->topicimage[$x], $context->id, 'mod_navigationmap', "topicimage_$x", 0, array (
							'subdirs' => 0,
							'maxbytes' => 2485760,
							'areamaxbytes' => 2485760,
							'maxfiles' => 1,
							'accepted_types' => array (
									'jpg',
									'jpeg',
									'png',
									'gif'
							)
					) );
				}
			}
		}
}

/**
 * This function is called at the end of quiz_add_instance
 * and quiz_update_instance, to do the common processing.
 *
 * @param object $quiz
 *        	the quiz object.
 */
function rooms_after_add_or_update($data, $context, $cmid, $mform ) {
	global $DB;
	$DB->delete_records( 'navigationmap_room_hotspots', array (
			'navigationmap_id' => $data->navigationmap_id
	) );
	// insert rooms
	if (count( $data->room_id ) > 0) {
		for($x = 0; $x < count( $data->room_id ); $x ++) {
			$topic = new stdClass();
			$topic->navigationmap_id = $data->navigationmap_id;
			$topic->room_id = $data->room_id[$x];
			$topic->room_hotspot_xvalue = $data->room_hotspot_xvalue[$x];
			$topic->room_hotspot_yvalue = $data->room_hotspot_yvalue[$x];
			$topic->room_hotspot_number = $data->room_hotspot_number[$x];

			$id = $DB->insert_record( 'navigationmap_room_hotspots', $topic );
		}
	}
}

/**
 * Given an array with a file path, it returns the itemid and the filepath for the defined filearea.
 *
 * @param  string $filearea The filearea.
 * @param  array  $args The path (the part after the filearea and before the filename).
 * @return array The itemid and the filepath inside the $args path, for the defined filearea.
 */
function mod_navigationmap_get_path_from_pluginfile(string $filearea, array $args) : array {
    // Page never has an itemid (the number represents the revision but it's not stored in database).
    array_shift($args);
    
    // Get the filepath.
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }
    
    return [
        'itemid' => 0,
        'filepath' => $filepath,
    ];
}