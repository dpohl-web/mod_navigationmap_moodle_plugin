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
 * @category backup
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined( 'MOODLE_INTERNAL' ) || die();

require_once ($CFG->dirroot . '/mod/navigationmap/backup/moodle2/restore_navigationmap_stepslib.php'); // Because it exists (must)

/**
 * navigationmap restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_navigationmap_activity_task extends restore_activity_task {

	/**
	 * Define (add) particular settings this activity can have
	 */
	protected function define_my_settings() {
		// No particular settings for this activity
	}

	/**
	 * Define (add) particular steps this activity can have
	 */
	protected function define_my_steps() {
		// label only has one structure step
		$this->add_step( new restore_navigationmap_activity_structure_step( 'navigationmap_structure', 'navigationmap.xml' ) );
	}

	/**
	 * Define the contents in the activity that must be
	 * processed by the link decoder
	 */
	static public function define_decode_contents() {
		$contents = array ();

		// $contents[] = new restore_decode_content('navigationmap', array('content, card_image'), 'navigationmap');
		$contents[] = new restore_decode_content( 'navigationmap', array (
				'content'
		), 'navigationmap' );

		return $contents;
	}

	/**
	 * Define the decoding rules for links belonging
	 * to the activity to be executed by the link decoder
	 */
	static public function define_decode_rules() {
		$rules = array ();

		$rules[] = new restore_decode_rule( 'NAVIGATIONMAPVIEWBYID', '/mod/navigationmap/view.php?id=$1', 'course_module' );
		$rules[] = new restore_decode_rule( 'NAVIGATIONMAPINDEX', '/mod/navigationmap/index.php?id=$1', 'course' );

		return $rules;
	}

	/**
	 * Define the restore log rules that will be applied
	 * by the {@link restore_logs_processor} when restoring
	 * navigationmap logs.
	 * It must return one array
	 * of {@link restore_log_rule} objects
	 */
	static public function define_restore_log_rules() {
		$rules = array ();

		$rules[] = new restore_log_rule( 'navigationmap', 'add', 'view.php?id={course_module}', '{navigationmap}' );
		$rules[] = new restore_log_rule( 'navigationmap', 'update', 'view.php?id={course_module}', '{navigationmap}' );
		$rules[] = new restore_log_rule( 'navigationmap', 'view', 'view.php?id={course_module}', '{navigationmap}' );

		return $rules;
	}

	/**
	 * Define the restore log rules that will be applied
	 * by the {@link restore_logs_processor} when restoring
	 * course logs.
	 * It must return one array
	 * of {@link restore_log_rule} objects
	 *
	 * Note this rules are applied when restoring course logs
	 * by the restore final task, but are defined here at
	 * activity level. All them are rules not linked to any module instance (cmid = 0)
	 */
	static public function define_restore_log_rules_for_course() {
		$rules = array ();

		$rules[] = new restore_log_rule( 'navigationmap', 'view all', 'index.php?id={course}', null );

		return $rules;
	}

	/**
	 * Re-map the room_id frm the mapped rooms to the navigation map
	 */
	public function after_restore() {
		global $DB;

		$navigationmap = $DB->get_record( 'navigationmap', array (
				'id' => $this->get_activityid()
		), 'id, is_map' );

		if ($navigationmap->is_map === '1') {
			$mapped_rooms = $DB->get_records( 'navigationmap_room_hotspots', array (
					'navigationmap_id' => $navigationmap->id
			) );
			
			foreach ($mapped_rooms as &$mapped_room) {
				// get new id of the room
				$newitem = restore_dbops::get_backup_ids_record( $this->get_restoreid(), 'navigationmap', $mapped_room->room_id );
				
				$new_room_id = $newitem->newitemid;
				$mapped_room->room_id = $new_room_id;
				
				// replace the old room_id with the new one
				$DB->update_record('navigationmap_room_hotspots', $mapped_room);
			}
			unset($mapped_room);
		}
	}
}
