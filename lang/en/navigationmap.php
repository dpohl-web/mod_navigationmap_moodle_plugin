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
 * Strings for component 'navigationmap', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   mod_navigationmap
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['configdisplayoptions'] = 'Select all options that should be available, existing settings are not modified. Hold CTRL key to select multiple fields.';
$string['content'] = 'Long Description';
$string['contentheader'] = 'Content';
$string['mapsettings'] = 'Maps';
$string['is_map'] = 'Is this a map or a room?';
$string['towhichmapbelongstheroom'] = 'Which map does the room belong to?';
$string['createnavigationmap'] = 'Create a new navigationmap resource';
$string['displayoptions'] = 'Available display options';
$string['displayselect'] = 'Display';
$string['displayselectexplain'] = 'Select display type.';
$string['indicator:cognitivedepth'] = 'Navigationmap cognitive';
$string['indicator:cognitivedepth_help'] = 'This indicator is based on the cognitive depth reached by the student in a Navigationmap resource.';
$string['indicator:cognitivedepthdef'] = 'Navigationmap cognitive';
$string['indicator:cognitivedepthdef_help'] = 'The participant has reached this percentage of the cognitive engagement offered by the Navigationmap resources during this analysis interval (Levels = No view, View)';
$string['indicator:cognitivedepthdef_link'] = 'Learning_analytics_indicators#Cognitive_depth';
$string['indicator:socialbreadth'] = 'Navigationmap social';
$string['indicator:socialbreadth_help'] = 'This indicator is based on the social breadth reached by the student in a Navigationmap resource.';
$string['indicator:socialbreadthdef'] = 'Navigationmap social';
$string['indicator:socialbreadthdef_help'] = 'The participant has reached this percentage of the social engagement offered by the Navigationmap resources during this analysis interval (Levels = No participation, Participant alone)';
$string['indicator:socialbreadthdef_link'] = 'Learning_analytics_indicators#Social_breadth';
$string['legacyfiles'] = 'Migration of old course file';
$string['legacyfilesactive'] = 'Active';
$string['legacyfilesdone'] = 'Finished';
$string['modulename'] = 'Navigationmap';
$string['modulename_help'] = 'This module creates the navigation for the badges app.

You can either create rooms in maps or only rooms. To create a functional navigation for the badges app please follow these steps:

1. Create all Topics
2. Create the rooms
3. If you want you can create maps

Topics and rooms are mandatory. Maps are  optional.';
$string['modulename_link'] = 'mod/navigationmap/view';
$string['modulenameplural'] = 'Navigationmaps';
$string['optionsheader'] = 'Display options';
$string['navigationmap-mod-navigationmap-x'] = 'Any navigationmap module navigationmap';
$string['navigationmap:addinstance'] = 'Add a new navigationmap resource';
$string['navigationmap:view'] = 'View navigationmap content';
$string['pluginadministration'] = 'Navigationmap module administration';
$string['pluginname'] = 'Navigationmap';
$string['popupheight'] = 'Pop-up height (in pixels)';
$string['popupheightexplain'] = 'Specifies default height of popup windows.';
$string['popupwidth'] = 'Pop-up width (in pixels)';
$string['popupwidthexplain'] = 'Specifies default width of popup windows.';
$string['printintro'] = 'Display navigationmap description';
$string['printintroexplain'] = 'Display navigationmap description above content?';
$string['privacy:metadata'] = 'The Navigationmap resource plugin does not store any personal data.';
$string['search:activity'] = 'Navigationmap';
$string['topicinroomheader'] = 'Topic';
$string['topicname'] = 'Topic';
$string['topic_short_description'] = 'Topic short description';
$string['topic_image'] = 'Topic image';
$string['short_description'] = 'Short description (max 255 characters)';
$string['long_description'] = 'Long description';
$string['card_image'] = 'Image for the navigation';
$string['topic_hotspot_xvalue'] = 'Horizontal hotspot coordinates';
$string['topic_hotspot_yvalue'] = 'Vertical hotspot coordinates';
$string['topic_hotspot_number'] = 'Hotspot value (max 2 characters)';
$string['noMap'] = 'No map';
$string['room_to_map_header'] = 'Mapped room';
$string['room_map_name'] = 'Room name';
$string['room_id'] = 'Room name';
$string['room_hotspot_xvalue'] = 'Horizontal hotspot coordinates';
$string['room_hotspot_yvalue'] = 'Vertical hotspot coordinates';
$string['room_hotspot_number'] = 'Hotspot value (max 2 characters)';
$string['remove_this_room'] = 'Unlink this room';
$string['remove_this_room_help'] = 'After saving this form, this room will no longer be associated with this map. It will then no longer appear in this form, nor will it be displayed in the app in this map.';
$string['remove_this_topic'] = 'Unlink this topic';
$string['remove_this_topic_help'] = 'After saving this form, this topic will no longer be associated with this room. It will then no longer appear in this form, nor will it be displayed in the app in this room.';
$string['short_description_help'] = 'The short description will be shown as a short info in the navigation';
$string['long_description_help'] = 'The long description will be shown in a new window';
$string['card_image_help'] = 'In this image the hotspots for the navigation are shown';
$string['is_map_help'] = 'Before saving for the first time, you can decide whether it is a map or a room. After saving, this value can no longer be changed. If you select "Map" here, you can go back to these settings after saving and select the rooms matching this map.
If you select "Room", you can go back to these settings after saving and select the topics that match this room.';
$string['room_hotspot_xvalue_help'] = 'The horizontal coordinates (in percent from the left) of the hotspot for this room shown on the image of this map. You can use without dot (e.g. 22) or max one decimal after a dot (e.g. 33.3).';
$string['room_hotspot_yvalue_help'] = 'The vertical coordinates  (in percent from top) of the hotspot for this room shown on the image of this map. You can use without dot (e.g. 22) or max one decimal after a dot (e.g. 33.3).';
$string['room_hotspot_number_help'] = 'Value which is shown in the hotspot';
$string['topic_hotspot_xvalue_help'] = 'The horizontal coordinates (in percent from the left) of the hotspot for this topic shown on the image of this room. You can use without dot (e.g. 22) or max one decimal after a dot (e.g. 33.3).';
$string['topic_hotspot_yvalue_help'] = 'The vertical coordinates  (in percent from top) of the hotspot for this topic shown on the image of this room. You can use without dot (e.g. 22) or max one decimal after a dot (e.g. 33.3).';
$string['topic_hotspot_number_help'] = 'Hotspot value (max 2 characters)';
$string['topic_short_description_help'] = 'Short description shown in the navigation';
$string['topic_image_help'] = 'Topic image shown in the navigation';
$string['topicname_help'] = 'Please choose an already created topic from this course. It will be shown as a hotspot in the image of this room for navigation in the app.';
$string['room_id_help'] = 'Please choose an already created room from this course. It will be shown as a hotspot in the image of this map for navigation in the app';
$string['add_one_room_to_form'] = 'Add 1 room to form';
$string['add_one_topic_to_form'] = 'Add 1 topic to form';
$string['x_y_values_err'] = 'max 4 characters. You can use without dot (e.g. 22) or max one decimal after a dot (e.g. 33.3), Dot must not be the last character';
$string['reset_hotspots'] = 'Reset hotspots';
$string['save_hotspots'] = 'Save hotspots';
$string['hotspot_drag_header'] = 'Hotspots Tuning';
$string['hotspot_drag_help'] = 'Here you can adjust the respective values in the input fields for the respective hotspots via drag and drop.<br />
After adding a new hotspot, you first have to fill the form and save it. Then go back to here and the hotspot is visible in this image.<br />
With the Reset button you can reset the values.<br />
As before, the form must still be saved after the adjustment.';
// Deprecated since 4.0.
$string['printheading'] = 'Display page name';
$string['printheadingexplain'] = 'Display page name above content?';
