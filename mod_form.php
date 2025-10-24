<?php

// module instance id in die navigationmap database und dann mit dreifachen fremdschlüssel navigation map mit course modules verknüpfen, um dann herauszufuinden, ob deletion in progress ist.

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
 * Navigationmap configuration form
 *
 * @package mod_navigationmap
 * @copyright 2009 Petr Skoda (http://skodak.org)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined( 'MOODLE_INTERNAL' ) || die();

require_once $CFG->dirroot . '/course/moodleform_mod.php';
require_once $CFG->dirroot . '/mod/navigationmap/locallib.php';
require_once $CFG->libdir . '/filelib.php';
class mod_navigationmap_mod_form extends moodleform_mod {
	protected $_topics;
	protected $_repeat_rooms;
	protected $all_mapped_rooms;

	function definition() {
		global $CFG, $DB, $PAGE, $data, $cm;
		$mform = $this->_form;
		$config = get_config( 'navigationmap' );
		
		// Generate header and name
		$mform->addElement( 'header', 'general', get_string( 'general', 'form' ) );
		$mform->addElement( 'text', 'name', get_string( 'name' ), array (
				'size' => '48'
		) );
		if (! empty( $CFG->formatstringstriptags )) {
			$mform->setType( 'name', PARAM_TEXT );
		} else {
			$mform->setType( 'name', PARAM_CLEANHTML );
		}
		$mform->addRule( 'name', null, 'required', null, 'client' );
		$mform->addRule( 'name', get_string( 'maximumchars', '', 255 ), 'maxlength', 255, 'client' );
		
		// Generate the selectbox for choosing map or room
		$is_map_options = array (
				1 => 'map',
				0 => 'room'
		);
		$mform->addElement( 'select', 'is_map', get_string( 'is_map', 'navigationmap' ), $is_map_options );
		if (! empty( $this->_instance )) {
			$mform->disabledIf( 'is_map', 'name', 'neq', '' );
		} else {
			$mform->addRule( 'is_map', null, 'required', null, 'client' );
		}
		$mform->addHelpButton('is_map', 'is_map', 'navigationmap');

		

		// Generate the text editor for the long description
		$mform->addElement( 'header', 'contentsection', get_string( 'contentheader', 'navigationmap' ) );
		
		// Generate the short description
		$mform->addElement( 'textarea', 'short_description', get_string( 'short_description', 'navigationmap' ), 'wrap="virtual" rows="10" cols="50"' );
		if (! empty( $CFG->formatstringstriptags )) {
			$mform->setType( 'short_description', PARAM_TEXT );
		} else {
			$mform->setType( 'short_description', PARAM_CLEANHTML );
		}
		$mform->addRule( 'short_description', null, 'required', null, 'client' );
		$mform->addRule( 'short_description', get_string( 'maximumchars', '', 255 ), 'maxlength', 255, 'client' );
		$mform->addHelpButton('short_description', 'short_description', 'navigationmap');
		
		$mform->addElement( 'editor', 'navigationmap', get_string( 'content', 'navigationmap' ), null, navigationmap_get_editor_options( $this->context ) );
		$mform->addRule( 'navigationmap', get_string( 'required' ), 'required', null, 'client' );
		$mform->addHelpButton('navigationmap', 'long_description', 'navigationmap');
	
		// Generate the filemanager for the image for then navigation
		$mform->addElement( 'filemanager', 'card_image', get_string( 'card_image', 'navigationmap' ), null, array (
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
		$mform->addRule( 'card_image', null, 'required', null, 'client' );
		$mform->addHelpButton('card_image', 'card_image', 'navigationmap');
		
		// If it is an update and room or map is known, we generate the repeats for rooms or topics (with hotspots, image)
		if (! empty( $this->_instance )) {
			$this->create_repeat_blocks( $this->current->is_map === '1', $mform );
		}




        // hotspots drag
        if (!empty( $this->_instance )) {
            $context = context_module::instance($this->_cm->id);
            $fs = get_file_storage();
            $files = $fs->get_area_files($context->id, 'mod_navigationmap', 'card_image', 0, 'sortorder DESC, id ASC', false); // TODO: this is not very efficient!!
            if (count($files) < 1) {
                // resource_print_filenotfound($navigationmap, $cm, $course);
                die;
            } else {
                $file = reset($files);
                unset($files);
            }

            $navigationmap = clone $data;
			$mform->addElement('html', navigationmap_get_hotspots_tuning_html());
            $mform->addElement('html', navigationmap_display_embed( $navigationmap, $cm, null, $file, 'card_image', $this->current->is_map === '1' ? $this->_repeat_rooms : $this->_topics));
            $mform->addElement('html', navigationmap_get_interaction_buttons_html());
		}
        // hotspots drag 

		$mform->addElement( 'header', 'appearancehdr', get_string( 'appearance' ) );
		if ($this->current->instance) {
			$options = resourcelib_get_displayoptions( explode( ',', $config->displayoptions ), $this->current->display );
		} else {
			$options = resourcelib_get_displayoptions( explode( ',', $config->displayoptions ) );
		}
		if (count( $options ) == 1) {
			$mform->addElement( 'hidden', 'display' );
			$mform->setType( 'display', PARAM_INT );
			reset( $options );
			$mform->setDefault( 'display', key( $options ) );
		} else {
			$mform->addElement( 'select', 'display', get_string( 'displayselect', 'navigationmap' ), $options );
			$mform->setDefault( 'display', $config->display );
		}

		if (array_key_exists( RESOURCELIB_DISPLAY_POPUP, $options )) {
			$mform->addElement( 'text', 'popupwidth', get_string( 'popupwidth', 'navigationmap' ), array (
					'size' => 3
			) );
			if (count( $options ) > 1) {
				$mform->hideIf('popupwidth', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
			}
			$mform->setType( 'popupwidth', PARAM_INT );
			$mform->setDefault( 'popupwidth', $config->popupwidth );

			$mform->addElement( 'text', 'popupheight', get_string( 'popupheight', 'navigationmap' ), array (
					'size' => 3
			) );
			if (count( $options ) > 1) {
				$mform->hideIf('popupheight', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
			}
			$mform->setType( 'popupheight', PARAM_INT );
			$mform->setDefault( 'popupheight', $config->popupheight );
		}

		$mform->addElement( 'advcheckbox', 'printintro', get_string( 'printintro', 'navigationmap' ) );
		$mform->setDefault( 'printintro', $config->printintro );

		// add legacy files flag only if used
		if (isset( $this->current->legacyfiles ) and $this->current->legacyfiles != RESOURCELIB_LEGACYFILES_NO) {
			$options = array (
					RESOURCELIB_LEGACYFILES_DONE => get_string( 'legacyfilesdone', 'navigationmap' ),
					RESOURCELIB_LEGACYFILES_ACTIVE => get_string( 'legacyfilesactive', 'navigationmap' )
			);
			$mform->addElement( 'select', 'legacyfiles', get_string( 'legacyfiles', 'navigationmap' ), $options );
			$mform->setAdvanced( 'legacyfiles', 1 );
		}

		// -------------------------------------------------------
		$this->standard_coursemodule_elements();

		// -------------------------------------------------------
		$this->add_action_buttons();

		// -------------------------------------------------------
		$mform->addElement( 'hidden', 'revision' );
		$mform->setType( 'revision', PARAM_INT );
		$mform->setDefault( 'revision', 1 );

        $PAGE->requires->js_call_amd('mod_navigationmap/main', 'init', array(true));

	}
	
	/**
	 * Depending of map or room, repeats for rooms or topics are created here
	 */
	protected function create_repeat_blocks($is_map, &$mform) {
		global $DB;
		if ($is_map === false) {

			$all_topics_in_this_course = $DB->get_records( 'course_sections', [ 
					'course' => $this->current->course
			]);
			$topic_name_options_array = array ();
			foreach ( $all_topics_in_this_course as $key => $value ) {
				
				if ($value->section !== '0') {
					if ($value->name) {
						$topic_name_options_array[$value->id] = $value->name;
					} else {
						$topic_name_options_array[$value->id] = get_string( 'topicname', 'navigationmap' ) . $value->section;
					}
				}
			}

			// Dynamicly map topics / sections to the room
			$repeatarray = array ();
			$repeatarray[] = $mform->createElement( 'header', 'topicinroomheader', get_string( 'topicinroomheader', 'navigationmap' ) );
			$repeatarray[] = $mform->createElement( 'select', 'topicid', get_string( 'topicname', 'navigationmap' ), $topic_name_options_array );
			$repeatarray[] = $mform->createElement( 'text', 'topicshortdescription', get_string( 'topic_short_description', 'navigationmap' ) );
			$repeatarray[] = $mform->createElement( 'filemanager', 'topicimage', get_string( 'topic_image', 'navigationmap' ), null, array (
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
			$repeatarray[] = $mform->createElement( 'text', 'topic_hotspot_xvalue', get_string( 'topic_hotspot_xvalue', 'navigationmap' ) );
			$repeatarray[] = $mform->createElement( 'text', 'topic_hotspot_yvalue', get_string( 'topic_hotspot_yvalue', 'navigationmap' ) );
			$repeatarray[] = $mform->createElement( 'text', 'topic_hotspot_number', get_string( 'topic_hotspot_number', 'navigationmap' ) );
			$repeatarray[] = $mform->createElement( 'advcheckbox', 'repeat_delete', get_string( 'remove_this_topic', 'navigationmap' ), '',  array (
					'group' => 1
			), array (
					0,
					1
			) );

			if (! empty( $this->_instance )) { // to check if its update or add
				$this->_topics = $DB->get_records( 'navigationmap_topics_to_room', array (
						'navigationmap_id' => $this->current->id
				) );
				$repeatno = count( $this->_topics ) > 0 ? count( $this->_topics ) : 1;
			} else {
				$this->_topics = array ();
				$repeatno = 1;
			}

			$repeateloptions = array ();
			$repeateloptions['topicid']['type'] = PARAM_INT;
			$repeateloptions['topicid']['rule'] = [[null, 'required', null, 'client']];
			$repeateloptions['topicid']['helpbutton'] = array('topicname', 'navigationmap');
			$repeateloptions['topicshortdescription']['type'] = PARAM_TEXT;
			$repeateloptions['topicshortdescription']['rule'] = [[null, 'required', null, 'client']];
			$repeateloptions['topicshortdescription']['helpbutton'] = ['topic_short_description', 'navigationmap'];
			$repeateloptions['topicimage']['rule'] = [[null, 'required', null, 'client'] ];
			$repeateloptions['topicimage']['helpbutton'] = ['topic_image', 'navigationmap'];
			$repeateloptions['topic_hotspot_xvalue']['type'] = PARAM_TEXT;
			$repeateloptions['topic_hotspot_xvalue']['rule'] = [
					[
							get_string( 'x_y_values_err', 'navigationmap' ),
							'regex',
							"/^[0-9]{1,2}(\.(?=[0-9]))?((?<=\.)[0-9])?$/",
							'server'
					],
					[
							null,
							'required',
							null,
							'client'
					],
					[ 
							null,
							'maxlength',
							4,
							'client'
					]
					
			]; // Only multi dimensional arrays are allowed
			$repeateloptions['topic_hotspot_xvalue']['helpbutton'] = ['topic_hotspot_xvalue', 'navigationmap'];
			$repeateloptions['topic_hotspot_yvalue']['type'] = PARAM_TEXT;
			$repeateloptions['topic_hotspot_yvalue']['rule'] = [ 
					[
							get_string( 'x_y_values_err', 'navigationmap' ),
							'regex',
							'/^[0-9]{1,2}(\.(?=[0-9]))?((?<=\.)[0-9])?$/',
							'server'
					],
					[
							null,
							'required',
							null,
							'client'
					],
					[ 
							null,
							'maxlength',
							4,
							'client'
					]
			];
			$repeateloptions['topic_hotspot_yvalue']['helpbutton'] = ['topic_hotspot_yvalue', 'navigationmap'];
			$repeateloptions['topic_hotspot_number']['type'] = PARAM_TEXT;
			$repeateloptions['topic_hotspot_number']['rule'] = [ 
					[ 
							null,
							'alphanumeric',
							null,
							'client'
					],
					[ 
							null,
							'maxlength',
							2,
							'client'
					]
			];
			$repeateloptions['topic_hotspot_number']['helpbutton'] = ['topic_hotspot_number', 'navigationmap'];
			$repeateloptions['repeat_delete']['helpbutton'] = ['remove_this_topic', 'navigationmap'];
		}

		if ($is_map === true) {
			$course_id = $this->current->course;
			$all_stored_rooms_in_this_course_sql = "SELECT nm.name, nm.id FROM {navigationmap} AS nm
            INNER JOIN {course_modules} AS cm
            ON nm.course = cm.course
            AND nm.id = cm.instance
            AND nm.module = cm.module
            WHERE cm.deletioninprogress = 0
            AND nm.is_map = 0
            AND nm.course = $course_id";

			$all_rooms_in_this_course = $DB->get_records_sql( $all_stored_rooms_in_this_course_sql ); // Needs a check if deletion is in progress

			$room_name_options_array = array ();
			foreach ( $all_rooms_in_this_course as $key => $value ) {

				if ($value->name) {
					$room_name_options_array[$value->id] = $value->name;
				}
			}

			// Dynamicly map topics / sections to the room
			$repeatarray = array ();
			$repeatarray[] = $mform->createElement( 'header', 'roominmapheader', get_string( 'room_to_map_header', 'navigationmap' ) );
			$repeatarray[] = $mform->createElement( 'select', 'room_id', get_string( 'room_id', 'navigationmap' ), $room_name_options_array );
			$repeatarray[] = $mform->createElement( 'text', 'room_hotspot_xvalue', get_string( 'room_hotspot_xvalue', 'navigationmap' ) );
			$repeatarray[] = $mform->createElement( 'text', 'room_hotspot_yvalue', get_string( 'room_hotspot_yvalue', 'navigationmap' ) );
			$repeatarray[] = $mform->createElement( 'text', 'room_hotspot_number', get_string( 'room_hotspot_number', 'navigationmap' ) );
			$repeatarray[] = $mform->createElement( 'advcheckbox', 'repeat_delete', get_string( 'remove_this_room', 'navigationmap' ), '', array (
					'group' => 1
			), array (
					0,
					1
			) );

			if (! empty( $this->_instance )) { // to check if its update or add
				$this->_repeat_rooms = $DB->get_records( 'navigationmap_room_hotspots', array (
						'navigationmap_id' => $this->current->id
				) );
				$repeatno = count( $this->_repeat_rooms ) > 0 ? count( $this->_repeat_rooms ) : 1;
			} else {
				$this->_repeat_rooms = array ();
				$repeatno = 1;
			}

			$repeateloptions = array ();
			$repeateloptions['room_id']['type'] = PARAM_INT;
			$repeateloptions['room_id']['rule'] = [[null, 'required', null, 'client' ]];
			$repeateloptions['room_id']['helpbutton'] = array('room_id', 'navigationmap');
			$repeateloptions['room_hotspot_xvalue']['type'] = PARAM_TEXT;
			$repeateloptions['room_hotspot_xvalue']['rule'] = [ 
					[
							get_string( 'x_y_values_err', 'navigationmap' ),
							'regex',
							'/^[0-9]{1,2}(\.(?=[0-9]))?((?<=\.)[0-9])?$/',
							'server'
					],
					[
							null,
							'required',
							null,
							'client'
					],
					[ 
							null,
							'maxlength',
							4,
							'client'
					]
			]; // Only multi dimensional arrays are allowed
			$repeateloptions['room_hotspot_xvalue']['helpbutton'] = array('room_hotspot_xvalue', 'navigationmap');
			$repeateloptions['room_hotspot_yvalue']['type'] = PARAM_TEXT;
			$repeateloptions['room_hotspot_yvalue']['rule'] = [ 
					[
							get_string( 'x_y_values_err', 'navigationmap' ),
							'regex',
							'/^[0-9]{1,2}(\.(?=[0-9]))?((?<=\.)[0-9])?$/',
							'server'
					],
					[
							null,
							'required',
							null,
							'client'
					],
					[ 
							null,
							'maxlength',
							4,
							'client'
					]
			];
			$repeateloptions['room_hotspot_yvalue']['helpbutton'] = array('room_hotspot_yvalue', 'navigationmap');
			$repeateloptions['room_hotspot_number']['type'] = PARAM_TEXT;
			$repeateloptions['room_hotspot_number']['rule'] = [
					[ 
							null,
							'alphanumeric',
							null,
							'client'
					],
					[ 
							null,
							'maxlength',
							2,
							'client'
					]
			];
			$repeateloptions['room_hotspot_number']['helpbutton'] = array('room_hotspot_number', 'navigationmap');
			$repeateloptions['repeat_delete']['helpbutton'] = array('remove_this_room', 'navigationmap');
		}
		
		$add_string = $is_map ? get_string( 'add_one_room_to_form', 'navigationmap' ) : get_string( 'add_one_topic_to_form', 'navigationmap' );
		$this->repeat_elements( $repeatarray, $repeatno, $repeateloptions, 'option_repeats', 'option_add_fields', 1, $add_string, false );
	}

	function data_preprocessing(&$default_values) {
		$default_values['completion'] = 0; // Always disabling the completion tracking
		if ($this->current->instance) {
			$draftitemid = file_get_submitted_draft_itemid( 'navigationmap' );
			$default_values['navigationmap']['format'] = $default_values['contentformat'];
			$default_values['navigationmap']['text'] = file_prepare_draft_area( $draftitemid, $this->context->id, 'mod_navigationmap', 'content', 0, navigationmap_get_editor_options( $this->context ), $default_values['content'] );
			$default_values['navigationmap']['itemid'] = $draftitemid;

			$card_image_draftitemid = file_get_submitted_draft_itemid( 'card_image' );
			file_prepare_draft_area( $card_image_draftitemid, $this->context->id, 'mod_navigationmap', 'card_image', 0, array (
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
			$default_values['card_image'] = $card_image_draftitemid;
		}

		// copy from choice
		if (! empty( $this->current->instance ) && ($this->current->is_map === '0') && isset( $this->_topics )) {
			$index = 0;
			foreach ( $this->_topics as $key => $value ) {

				$default_values['topicid[' . $index . ']'] = $value->topic_id;
				$default_values['topicshortdescription[' . $index . ']'] = $value->topic_shortdescription;
				$topic_image_draftitemid = file_get_submitted_draft_itemid( "topicimage_$index" );
				file_prepare_draft_area( $topic_image_draftitemid, $this->context->id, 'mod_navigationmap', "topicimage_$index", 0, // Itemid.,
				array (
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
				$default_values['topicimage[' . $index . ']'] = $topic_image_draftitemid;
				$default_values['topic_hotspot_xvalue[' . $index . ']'] = $value->topic_hotspot_xvalue;
				$default_values['topic_hotspot_yvalue[' . $index . ']'] = $value->topic_hotspot_yvalue;
				$default_values['topic_hotspot_number[' . $index . ']'] = $value->topic_hotspot_number;
				$index ++;
			}
		}

		// copy from choice
		if (! empty( $this->current->instance ) && ($this->current->is_map === '1') && isset( $this->_repeat_rooms )) {
			$index = 0;
			foreach ( $this->_repeat_rooms as $key => $value ) {

				$default_values['room_id[' . $index . ']'] = $value->room_id;
				$default_values['room_hotspot_xvalue[' . $index . ']'] = $value->room_hotspot_xvalue;
				$default_values['room_hotspot_yvalue[' . $index . ']'] = $value->room_hotspot_yvalue;
				$default_values['room_hotspot_number[' . $index . ']'] = $value->room_hotspot_number;
				$index ++;
			}
		}

		if (! empty( $default_values['displayoptions'] )) {
			$displayoptions = unserialize( $default_values['displayoptions'] );
			if (isset( $displayoptions['printintro'] )) {
				$default_values['printintro'] = $displayoptions['printintro'];
			}
			if (! empty( $displayoptions['popupwidth'] )) {
				$default_values['popupwidth'] = $displayoptions['popupwidth'];
			}
			if (! empty( $displayoptions['popupheight'] )) {
				$default_values['popupheight'] = $displayoptions['popupheight'];
			}
		}
	}

	/**
	 * Method to add a repeating group of elements to a form.
	 *
	 * @param array $elementobjs
	 *        	Array of elements or groups of elements that are to be repeated
	 * @param int $repeats
	 *        	no of times to repeat elements initially
	 * @param array $options
	 *        	a nested array. The first array key is the element name.
	 *        	the second array key is the type of option to set, and depend on that option,
	 *        	the value takes different forms.
	 *        	'default' - default value to set. Can include '{no}' which is replaced by the repeat number.
	 *        	'type' - PARAM_* type.
	 *        	'helpbutton' - array containing the helpbutton params.
	 *        	'disabledif' - array containing the disabledIf() arguments after the element name.
	 *        	'rule' - array containing the addRule arguments after the element name.
	 *        	'expanded' - whether this section of the form should be expanded by default. (Name be a header element.)
	 *        	'advanced' - whether this element is hidden by 'Show more ...'.
	 * @param string $repeathiddenname
	 *        	name for hidden element storing no of repeats in this form
	 * @param string $addfieldsname
	 *        	name for button to add more fields
	 * @param int $addfieldsno
	 *        	how many fields to add at a time
	 * @param string $addstring
	 *        	name of button, {no} is replaced by no of blanks that will be added.
	 * @param bool $addbuttoninside
	 *        	if true, don't call closeHeaderBefore($addfieldsname). Default false.
	 * @return int no of repeats of element in this page
	 */
	function repeat_elements($elementobjs, $repeats, $options, $repeathiddenname, $addfieldsname, $addfieldsno = 5, $addstring = null, $addbuttoninside = false, $withoutAddButton = false) {
		if ($addstring === null) {
			$addstring = get_string( 'addfields', 'form', $addfieldsno );
		} else {
			$addstring = str_ireplace( '{no}', $addfieldsno, $addstring );
		}
		$repeats = optional_param( $repeathiddenname, $repeats, PARAM_INT );
		$addfields = optional_param( $addfieldsname, '', PARAM_TEXT );
		if (! empty( $addfields )) {
			$repeats += $addfieldsno;
		}
		$mform = & $this->_form;
		$mform->registerNoSubmitButton( $addfieldsname );
		$mform->addElement( 'hidden', $repeathiddenname, $repeats );
		$mform->setType( $repeathiddenname, PARAM_INT );
		// value not to be overridden by submitted value
		$mform->setConstants( array (
				$repeathiddenname => $repeats
		) );
		$namecloned = array ();
		for($i = 0; $i < $repeats; $i ++) {
			foreach ( $elementobjs as $elementobj ) {
				$elementclone = fullclone( $elementobj );
				$this->repeat_elements_fix_clone( $i, $elementclone, $namecloned );

				if ($elementclone instanceof HTML_QuickForm_group && ! $elementclone->_appendName) {
					foreach ( $elementclone->getElements() as $el ) {
						$this->repeat_elements_fix_clone( $i, $el, $namecloned );
					}
					$elementclone->setLabel( str_replace( '{no}', $i + 1, $elementclone->getLabel() ) );
				}

				$mform->addElement( $elementclone );
			}
		}
		for($i = 0; $i < $repeats; $i ++) {
			foreach ( $options as $elementname => $elementoptions ) {
				$pos = strpos( $elementname, '[' );
				if ($pos !== FALSE) {
					$realelementname = substr( $elementname, 0, $pos ) . "[$i]";
					$realelementname .= substr( $elementname, $pos );
				} else {
					$realelementname = $elementname . "[$i]";
				}
				foreach ( $elementoptions as $option => $params ) {

					switch ($option) {
						case 'default' :
							if (is_array( $params )) {
								$defaultValue = $params[0][$i]->{$params[1]};
								$mform->setDefault( $realelementname, $defaultValue );
							} else {
								$mform->setDefault( $realelementname, str_replace( '{no}', $i + 1, $params ) );
							}

							break;
						case 'helpbutton' :
							$params = array_merge( array (
									$realelementname
							), $params );
							call_user_func_array( array (
									&$mform,
									'addHelpButton'
							), $params );
							break;
						case 'disabledif' :
							foreach ( $namecloned as $num => $name ) {
								if ($params[0] == $name) {
									$params[0] = $params[0] . "[$i]";
									break;
								}
							}
							$params = array_merge( array (
									$realelementname
							), $params );
							call_user_func_array( array (
									&$mform,
									'disabledIf'
							), $params );
							break;
						case 'rule' :
							if (is_array( $params )) {
								foreach ( $params as $key => $value ) {
									if (is_array( $value )) {
										$newparams = array_merge( array (
												$realelementname
										), $value );
										call_user_func_array( array (
												&$mform,
												'addRule'
										), $newparams );
									}
								}
							}
							break;
						case 'type' :
							$mform->setType( $realelementname, $params );
							break;

						case 'expanded' :
							$mform->setExpanded( $realelementname, $params );
							break;

						case 'advanced' :
							$mform->setAdvanced( $realelementname, $params );
							break;
					}
				}
			}
		}
		if (! $withoutAddButton) {
			$mform->addElement( 'submit', $addfieldsname, $addstring );
		}

		if (! $addbuttoninside) {
			$mform->closeHeaderBefore( $addfieldsname );
		}

		return $repeats;
	}

	function is_array($value) {
		return is_array( $value );
	}

	/**
	 * Allows module to modify the data returned by form get_data().
	 * This method is also called in the bulk activity completion form.
	 *
	 * Only available on moodleform_mod.
	 *
	 * @param stdClass $data
	 *        	the form data to be modified.
	 */
	public function data_postprocessing($data) {
		parent::data_postprocessing( $data );

		// We have to delete all iamges in this context to make it possible to delete one or more without having some images as orphans in the files table in db
		// We have to delte the topics or rooms in the corresponding array for the update method in update_instance in lib.php
		if ($this->current->instance) {
			$fs = get_file_storage();
			if ($data->is_map !== '1') {
				for($x = 0; $x < count( $data->topicid ); $x ++) {
					$topic_navigation_image_file = $fs->get_area_files( $this->context->id, 'mod_navigationmap', "topicimage_$x", 0, 'sortorder DESC, id ASC', false );
					if (count( $topic_navigation_image_file ) > 0) {
						$topic_image_file = reset( $topic_navigation_image_file );
						unset( $topic_navigation_image_file );
						if ($topic_image_file) {
							$topic_image_file->delete();
						}
					}
				}
				$topic_count = count( $data->topicid );
				for($x = 0; $x < $topic_count; $x ++) {
					if ($data->repeat_delete[$x] === '1') {
						unset( $data->topicid[$x] );
						unset( $data->topic_hotspot_xvalue[$x] );
						unset( $data->topic_hotspot_yvalue[$x] );
						unset( $data->topic_hotspot_number[$x] );
						unset( $data->topicimage[$x] );
						unset( $data->topicshortdescription[$x] );
					}
				}
				$data->topicid = array_values( $data->topicid );
				$data->topic_hotspot_xvalue = array_values( $data->topic_hotspot_xvalue );
				$data->topic_hotspot_yvalue = array_values( $data->topic_hotspot_yvalue );
				$data->topic_hotspot_number = array_values( $data->topic_hotspot_number );
				$data->topicimage = array_values( $data->topicimage );
				$data->topicshortdescription = array_values( $data->topicshortdescription );
			} else {
				$room_count = count( $data->room_id );
				for($x = 0; $x < $room_count; $x ++) {
					if ($data->repeat_delete[$x] === '1') {
						unset( $data->room_id[$x] );
						unset( $data->room_hotspot_xvalue[$x] );
						unset( $data->room_hotspot_yvalue[$x] );
						unset( $data->room_hotspot_number[$x] );
					}
				}
				$data->room_id = array_values( $data->room_id );
				$data->room_hotspot_xvalue = array_values( $data->room_hotspot_xvalue );
				$data->room_hotspot_yvalue = array_values( $data->room_hotspot_yvalue );
				$data->room_hotspot_number = array_values( $data->room_hotspot_number );
			}
		}
	}
}
