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

/**
 * Define all the restore steps that will be used by the restore_navigationmap_activity_task
 */

/**
 * Structure step to restore one navigationmap activity
 */
class restore_navigationmap_activity_structure_step extends restore_activity_structure_step {

    protected $topicimage_filearea_number = 0;
    
    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('navigationmap', '/activity/navigationmap');
        
        $paths[] = new restore_path_element('room', '/activity/navigationmap/rooms/room');

        $paths[] = new restore_path_element('topic', '/activity/navigationmap/topics/topic');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_navigationmap($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.

        // insert the navigationmap record
        $newitemid = $DB->insert_record('navigationmap', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }
    
    protected function process_room($data) {
    	global $DB;
    	
    	$data = (object)$data;
    	$oldid = $data->id;
    	
    	$data->navigationmap_id = $this->get_new_parentid('navigationmap');
    	
    	$newitemid = $DB->insert_record('navigationmap_room_hotspots', $data);
    	$this->set_mapping('room', $oldid, $newitemid);

    }
    
    protected function process_topic($data) {
    	global $DB;
        $this->topicimage_filearea_number++;
        
        
        $data = (object)$data;
        $oldid = $data->id;
        
        $data->navigationmap_id = $this->get_new_parentid('navigationmap');
        
        $newitemid = $DB->insert_record('navigationmap_topics_to_room', $data);
        $this->set_mapping('topic', $oldid, $newitemid);
        
    }

    protected function after_execute() {
    	global $DB;
        // Add navigationmap related files, no need to match by itemname (just internally handled context)

        $this->add_related_files('mod_navigationmap', 'content', null);
        $this->add_related_files('mod_navigationmap', 'card_image', null);
        
        for ($x = 0; $x < $this->topicimage_filearea_number; $x++){
            $this->add_related_files('mod_navigationmap', "topicimage_$x", null);
        }
        
        
        // update coursemodule id foreign key in navigationmap table
        $course_id = $this->get_courseid();
        $module_id = $DB->get_field('modules', 'id', array('name'=>'navigationmap'));
        $cms = $DB->get_records('course_modules', array('course'=>$course_id, 'module'=>$module_id));
        
        foreach ($cms as $cm) {
        	$navigationmap = $DB->get_record('navigationmap', array('id'=>$cm->instance));
            $navigationmap->coursemodule = $cm->id;
            $navigationmap->module = $module_id;
        	$DB->update_record('navigationmap', $navigationmap);
        }
    }
    
    /**
     * Re-map the old topic_id from the mapped topics to the new section ids
     */
    public function after_restore() {
    	global $DB;
    	
    	$navigationmap = $DB->get_record( 'navigationmap', array (
    			'id' => $this->task->get_activityid()
    	), 'id, is_map' );
    	
    	if ($navigationmap->is_map === '0') {
    	
	    	$mapped_topics = $DB->get_records( 'navigationmap_topics_to_room', array (
	    			'navigationmap_id' => $navigationmap->id
	    	) );
	    	
	    	foreach ($mapped_topics as &$mapped_topic) {
	    		$new_topic_id = $this->get_mappingid('course_section', $mapped_topic->topic_id);
	    		$mapped_topic->topic_id = $new_topic_id;
	    		
	    		// replace the topic room_id with the new one
	    		$DB->update_record('navigationmap_topics_to_room', $mapped_topic);
	    	}
	    	unset($mapped_topic);
    	}
    }
    
    
}
