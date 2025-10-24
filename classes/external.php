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
 * Navigationmap external API
 *
 * @package mod_navigationmap
 * @category external
 * @copyright 2015 Juan Leyva <juan@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 3.0
 */
use core_course\external\helper_for_get_mods_by_courses;
defined( 'MOODLE_INTERNAL' ) || die();

require_once ("$CFG->libdir/externallib.php");

/**
 * Navigationmap external functions
 *
 * @package mod_navigationmap
 * @category external
 * @copyright 2015 Juan Leyva <juan@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 3.0
 */
class mod_navigationmap_external extends external_api {

	/**
	 * Returns description of method parameters
	 *
	 * @return external_function_parameters
	 * @since Moodle 3.0
	 */
	public static function view_navigationmap_parameters() {
		return new external_function_parameters( array (
				'navigationmapid' => new external_value( PARAM_INT, 'navigationmap instance id' )
		) );
	}

	/**
	 * Simulate the navigationmap/view.php web interface navigationmap: trigger events, completion, etc...
	 *
	 * @param int $navigationmapid
	 *        	the navigationmap instance id
	 * @return array of warnings and status result
	 * @since Moodle 3.0
	 * @throws moodle_exception
	 */
	public static function view_navigationmap($navigationmapid) {
		global $DB, $CFG;
		require_once ($CFG->dirroot . "/mod/navigationmap/lib.php");

		$params = self::validate_parameters( self::view_navigationmap_parameters(), array (
				'navigationmapid' => $navigationmapid
		) );
		$warnings = array ();

		// Request and permission validation.
		$navigationmap = $DB->get_record( 'navigationmap', array (
				'id' => $params['navigationmapid']
		), '*', MUST_EXIST );
		list ( $course, $cm ) = get_course_and_cm_from_instance( $navigationmap, 'navigationmap' );

		$context = context_module::instance( $cm->id );
		self::validate_context( $context );

		require_capability( 'mod/navigationmap:view', $context );

		// Call the navigationmap/lib API.
		navigationmap_view( $navigationmap, $course, $cm, $context );

		$result = array ();
		$result['status'] = true;
		$result['warnings'] = $warnings;
		return $result;
	}

	/**
	 * Returns description of method result value
	 *
	 * @return external_description
	 * @since Moodle 3.0
	 */
	public static function view_navigationmap_returns() {
		return new external_single_structure( array (
				'status' => new external_value( PARAM_BOOL, 'status: true if success' ),
				'warnings' => new external_warnings()
		) );
	}

	/**
	 * Describes the parameters for get_navigationmaps_by_courses.
	 *
	 * @return external_function_parameters
	 * @since Moodle 3.3
	 */
	public static function get_navigationmaps_by_courses_parameters() {
		return new external_function_parameters( array (
				'courseids' => new external_multiple_structure( new external_value( PARAM_INT, 'Course id' ), 'Array of course ids', VALUE_DEFAULT, array () )
		) );
	}

	/**
	 * Returns a list of navigationmaps in a provided list of courses.
	 * If no list is provided all navigationmaps that the user can view will be returned.
	 *
	 * @param array $courseids
	 *        	course ids
	 * @return array of warnings and navigationmaps
	 * @since Moodle 3.3
	 */
	public static function get_navigationmaps_by_courses($courseids = array()) {
		// error_log('get_navigationmaps_by_courses is used');
		// error_log($courseids);
		global $DB;
		$warnings = array ();
		$returnednavigationmaps = array ();

		$params = array (
				'courseids' => $courseids
		);
		$params = self::validate_parameters( self::get_navigationmaps_by_courses_parameters(), $params );

		$mycourses = array ();
		if (empty( $params['courseids'] )) {
			$mycourses = enrol_get_my_courses();
			$params['courseids'] = array_keys( $mycourses );
		}

		// Ensure there are courseids to loop through.
		if (! empty( $params['courseids'] )) {

			list ( $courses, $warnings ) = external_util::validate_courses( $params['courseids'], $mycourses );

			// Get the navigationmaps in this course, this function checks users visibility permissions.
			// We can avoid then additional validate_context calls.
			$navigationmaps = get_all_instances_in_courses( "navigationmap", $courses );
			foreach ( $navigationmaps as $navigationmap ) {
                helper_for_get_mods_by_courses::format_name_and_intro($navigationmap, 'mod_navigationmap');
				$context = context_module::instance( $navigationmap->coursemodule );
				// Entry to return.
				$navigationmap->name = external_format_string( $navigationmap->name, $context->id );

				// list($navigationmap->intro, $navigationmap->introformat) = external_format_text($navigationmap->intro,
				// $navigationmap->introformat, $context->id, 'mod_navigationmap', 'intro', null);
				// $navigationmap->introfiles = external_util::get_area_files($context->id, 'mod_navigationmap', 'intro', false, false);

				// $options = array (
				// 		'noclean' => true
				// );
				list ( $navigationmap->content, $navigationmap->contentformat ) = external_format_text( $navigationmap->content, $navigationmap->contentformat, $context->id, 'mod_navigationmap', 'content', $navigationmap->revision, ['noclean' => true] );
				$navigationmap->contentfiles = external_util::get_area_files( $context->id, 'mod_navigationmap', 'content' );

				$navigationmap->navigation_image = external_util::get_area_files( $context->id, 'mod_navigationmap', 'card_image', 0 );
				$navigationmap_id = $navigationmap->id;
				$course_id = $navigationmap->course;
				if ($navigationmap->is_map === '1') {

					$all_mapped_rooms_before_and_after_cron_sql = "SELECT nmrh.room_id AS repeat_id, nm.name, nm.short_description, nmrh.room_hotspot_xvalue AS x_value, nmrh.room_hotspot_yvalue AS y_value, nmrh.room_hotspot_number AS hotspot_number, cm.id AS room_course_module FROM {navigationmap_room_hotspots} AS nmrh
						INNER JOIN {navigationmap} AS nm
						ON nmrh.room_id = nm.id
			            INNER JOIN {course_modules} AS cm
			            ON nm.course = cm.course
			            AND nm.id = cm.instance
			            AND nm.module = cm.module
			            WHERE cm.deletioninprogress = 0
			            AND nmrh.navigationmap_id = $navigationmap_id
			            AND nm.course = $course_id";

					$all_mapped_rooms = $DB->get_records_sql( $all_mapped_rooms_before_and_after_cron_sql ); // Needs a chec
					foreach ( $all_mapped_rooms as $key => &$value ) {
						$value->repeat_navigation_image_file = array();
					}
					unset( $value );
					$navigationmap->all_repeats = $all_mapped_rooms;
				} else {
					$all_mapped_topics_before_and_after_cron_sql = "SELECT nmttr.topic_id AS repeat_id, nmttr.topic_shortdescription AS short_description, nmttr.topic_hotspot_xvalue AS x_value, nmttr.topic_hotspot_yvalue AS y_value, nmttr.topic_hotspot_number AS hotspot_number FROM {navigationmap_topics_to_room} AS nmttr
					INNER JOIN {navigationmap} AS nm
					ON nmttr.navigationmap_id = nm.id
		            INNER JOIN {course_modules} AS cm
		            ON nm.course = cm.course
		            AND nm.id = cm.instance
		            AND nm.module = cm.module
					INNER JOIN {course_sections} AS cs
					ON nmttr.topic_id = cs.id
		            WHERE cm.deletioninprogress = 0
		            AND nmttr.navigationmap_id = $navigationmap_id
		            AND nm.course = $course_id
					ORDER BY cs.section";

					$all_mapped_topics = $DB->get_records_sql( $all_mapped_topics_before_and_after_cron_sql ); // Needs a chec
					
					
					$repeat_index = 0;
					foreach ( $all_mapped_topics as $key => &$value ) {
						// change the topic id to the section id as repeat id. In the app we need the section id for the order
						$repeat_id = $value->repeat_id;
						$section_sort_id = $DB->get_record_sql( "SELECT section FROM {course_sections} WHERE course = $course_id AND id = $repeat_id");
						
						$topic_name = $DB->get_record_sql( "SELECT name FROM {course_sections} WHERE id = $repeat_id");
						$topic_navigation_image_file = external_util::get_area_files( $context->id, 'mod_navigationmap', 'topicimage_' . $repeat_index, 0, 'sortorder DESC, id ASC', false );
						$value->repeat_navigation_image_file = $topic_navigation_image_file;
						$value->name = $topic_name->name;
						$value->room_course_module = null;
						$value->repeat_id = $section_sort_id->section;
						$repeat_index ++;
					}
					unset( $value );
					$navigationmap->all_repeats = $all_mapped_topics;
				}

				$returnednavigationmaps[] = $navigationmap;
			}
		}

		$result = array (
				'navigationmaps' => $returnednavigationmaps,
				'warnings' => $warnings
		);
		return $result;
	}

	/**
	 * Describes the get_navigationmaps_by_courses return value.
	 *
	 * @return external_single_structure
	 * @since Moodle 3.3
	 */
	public static function get_navigationmaps_by_courses_returns() {
		return new external_single_structure(
            array (
				'navigationmaps' => new external_multiple_structure(
                    new external_single_structure( array_merge (
                        helper_for_get_mods_by_courses::standard_coursemodule_elements_returns(),
                        [
                            'id' => new external_value( PARAM_INT, 'Module id' ),
                            'coursemodule' => new external_value( PARAM_INT, 'Course module id' ),
                            'course' => new external_value( PARAM_INT, 'Course id' ),
                            'is_map' => new external_value( PARAM_INT, 'Is it a map or a room' ),
                            'name' => new external_value( PARAM_RAW, 'Navigationmap name' ),
                            'short_description' => new external_value( PARAM_RAW, 'Navigationmap short description' ),
                            'content' => new external_value( PARAM_RAW, 'Navigationmap content' ),
                            'contentformat' => new external_format_value( 'content', 'Content format' ),
                            'contentfiles' => new external_files( 'Files in the content' ),
                            'legacyfiles' => new external_value( PARAM_INT, 'Legacy files flag' ),
                            'legacyfileslast' => new external_value( PARAM_INT, 'Legacy files last control flag' ),
                            'display' => new external_value( PARAM_INT, 'How to display the navigationmap' ),
                            'displayoptions' => new external_value( PARAM_RAW, 'Display options (width, height)' ),
                            'revision' => new external_value( PARAM_INT, 'Incremented when after each file changes, to avoid cache' ),
                            'timemodified' => new external_value( PARAM_INT, 'Last time the navigationmap was modified' ),
                            'section' => new external_value( PARAM_INT, 'Course section id' ),
                            'visible' => new external_value( PARAM_INT, 'Module visibility' ),
                            'groupmode' => new external_value( PARAM_INT, 'Group mode' ),
                            'groupingid' => new external_value( PARAM_INT, 'Grouping id' ),
                            'navigation_image' => new external_files( 'File for navigation' ),
                            'all_repeats' => new external_multiple_structure( new external_single_structure( array (
                                    'repeat_id' => new external_value( PARAM_INT, 'Id from room or section' ),
                                    'room_course_module' => new external_value( PARAM_INT, 'Course Module Id of the mapped room' ),
                                    'x_value' => new external_value( PARAM_RAW, 'horizontal percent value' ),
                                    'y_value' => new external_value( PARAM_RAW, 'vertical percent value' ),
                                    'hotspot_number' => new external_value( PARAM_RAW, 'The string in the hotspot' ),
                                    'name' => new external_value( PARAM_RAW, 'The name of the room or topic' ),
                                    'short_description' => new external_value( PARAM_RAW, 'The short description of the room or topic' ),
                                    'repeat_navigation_image_file' => new external_files( 'Navigation image for the topic' ),
                            )))
                        ]
				) ) ),
				'warnings' => new external_warnings()
		) );
	}
	
	/**
	 * Returns description of method parameters for navigationmap course check
	 *
	 * @return external_function_parameters
	 * @since Moodle 3.0
	 */
	public static function is_navigationmap_course_parameters() {
	    return new external_function_parameters( 
	       array(
	           'courseid' => new external_value( PARAM_INT, 'course id')  
	       )
	    );
	}
	
    /**
     * returns true if the course with the provided $courseid has at least one navigationmap instance in the first section, false otherwise
     * @param int $courseid the id of the course to check
     * @return boolean[]
     */
	public static function is_navigationmap_course($courseid) {
	    
	    $params = self::validate_parameters( self::is_navigationmap_course_parameters(), array( 'courseid' => $courseid ));
	    
	    $result = false;
	    
	    $mycourses = enrol_get_my_courses();
	    
	    $coursetocheck = null;
	    
	    foreach ( $mycourses as $mycourse ) {
	        
	        if($courseid == $mycourse->id) {
	            $coursetocheck = $mycourse;
	            break;
	        }
	    }
	    
	    if( $coursetocheck != null) {
    	    $navigationmaps = get_all_instances_in_course('navigationmap', $coursetocheck);

    	    if(!empty($navigationmaps)){
    	        $result = true;
    	    }
	    }
	    
	    return array(
	        'status' => $result
	    );
	}
	
	/**
	 * Describes the is_navigationmap_course return value.
	 *
	 * @return external_single_structure
	 * @since Moodle 3.4
	 */
	public static function is_navigationmap_course_returns() {
	    return new external_function_parameters( 
	        array(
                'status' => new external_value( PARAM_BOOL, 'True if the course has at least one navigationmap instance in the first section, false otherwise')
	       )
	    );
	}
	
	/**
	 * Describes the filter_navigationmap_courseids parameters.
	 *
	 * @return external_single_structure
	 * @since Moodle 3.4
	 */
	public static function filter_navigationmap_courseids_parameters() {
	    return new external_function_parameters( array (
	        'courseids' => new external_multiple_structure( new external_value( PARAM_INT, 'Course id' ), 'Array of course ids', VALUE_DEFAULT, array () )
	    ) );
	    
	}
	
	/**
	 * filters the provided $courseids returns only the ids of those courses the user is enrolled to and which have at least one navigationmap
	 * instance in the first section.
	 * 
	 * @param array $courseids the courseids to filter
	 * @return array of courseids 
	 */
	public static function filter_navigationmap_courseids($courseids) {
	    
	    $params = array (
	        'courseids' => $courseids 
	    );
	    
	    $params = self::validate_parameters(self::filter_navigationmap_courseids_parameters(), $params);
	    
	    $mycourses = enrol_get_my_courses();
	    
	    $navigationmaps = get_all_instances_in_courses('navigationmap', $mycourses);
	    
	    $navigationmapcourseids = array();
	    
	    foreach ( $navigationmaps as $navigationmap) {
	        
	        if($navigationmap->section == 0 && !in_array($navigationmap->course, $navigationmapcourseids) && 
	            in_array($navigationmap->course, $courseids)) {
	                
	                $navigationmapcourseids[] = $navigationmap->course;
	            
	        }
	    }
	    
	    return array(
	        'courseids' => $navigationmapcourseids
	    );
	}
	
	/**
	 * Describes the filter_navigationmap_courseids return value.
	 *
	 * @return external_single_structure
	 * @since Moodle 3.4
	 */
	public static function filter_navigationmap_courseids_returns() {
	    return new external_function_parameters( array (
	        'courseids' => new external_multiple_structure( new external_value( PARAM_INT, 'Course id' ), 'Array of course ids', VALUE_DEFAULT, array () )
	    ) );
	    
	}
}
