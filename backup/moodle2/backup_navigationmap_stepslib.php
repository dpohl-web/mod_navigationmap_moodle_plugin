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
 * @package   mod_navigationmap
 * @category  backup
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define all the backup steps that will be used by the backup_navigationmap_activity_task
 */

/**
 * Define the complete navigationmap structure for backup, with file and id annotations
 */
class backup_navigationmap_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
    	global $DB;
    	
    	$task = $this->task;
    	$coursemodule = $task->get_moduleid();
//     	$context_id = $task->contextid;
    	
    	$navigationmap_table = $DB->get_record( 'navigationmap', array ('coursemodule' => $coursemodule) );
    	
    	// To know if we are including userinfo
    	$userinfo = $this->get_setting_value('userinfo');
    	
    	// Define each element separated
    	$navigationmap = new backup_nested_element('navigationmap', array('id'), array(
    			'course', 'module', 'coursemodule', 'name', 'short_description',
    			'content', 'contentformat', 'legacyfiles', 'legacyfileslast',
    			'display', 'displayoptions', 'revision', 'timemodified', 'is_map'));
    	
    	$rooms = new backup_nested_element('rooms');
    	
    	$room = new backup_nested_element('room', array('id'), array(
    			'room_id', 'room_hotspot_xvalue', 'room_hotspot_yvalue', 'room_hotspot_number'));
    	
    	$topics = new backup_nested_element('topics');
    	
    	$topic = new backup_nested_element('topic', array('id'), array(
    			'topic_id', 'topic_shortdescription', 'topic_hotspot_xvalue', 'topic_hotspot_yvalue', 'topic_hotspot_number'));
    	
    	
        
        // Build the tree
    	$navigationmap->add_child($rooms);
    	$rooms->add_child($room);
    	
    	$navigationmap->add_child($topics);
    	$topics->add_child($topic);
    	

        // Define sources
        $navigationmap->set_source_table('navigationmap', array('id' => backup::VAR_ACTIVITYID));
        
        $room->set_source_table('navigationmap_room_hotspots', array('navigationmap_id' => backup::VAR_PARENTID), 'id ASC');
        
        $topic->set_source_table('navigationmap_topics_to_room', array('navigationmap_id' => backup::VAR_PARENTID), 'id ASC');

        // Define id annotations
        // (none)

        // Define file annotations
        $navigationmap->annotate_files('mod_navigationmap', 'content', null); // This file areas haven't itemid
        
        $navigationmap->annotate_files('mod_navigationmap', 'card_image', null); // This file areas haven't itemid
        
        
        if ($navigationmap_table && ($navigationmap_table->is_map === '0')) {
        	$topic_images_sql_params = array('navigationmap_id' => $navigationmap_table->id);
        	$topic_images_sql = "SELECT nmtr.id AS navigationmap_topic_id FROM {navigationmap_topics_to_room} AS nmtr INNER JOIN {navigationmap} AS nm ON nmtr.navigationmap_id = nm.id WHERE nm.id = :navigationmap_id";
        	$topic_images = $DB->get_records_sql($topic_images_sql, $topic_images_sql_params);
        	
        	for ($x = 0; $x < count($topic_images); $x++) {
        		$navigationmap->annotate_files('mod_navigationmap', "topicimage_$x", null); // This file areas haven't itemid
        	}
        	
        }
        
        

        // Return the root element (navigationmap), wrapped into standard activity structure
        return $this->prepare_activity_structure($navigationmap);
    }
}
