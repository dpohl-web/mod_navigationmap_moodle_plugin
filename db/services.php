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
 * Navigationmap external functions and service definitions.
 *
 * @package    mod_navigationmap
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die;

$functions = array(

    'mod_navigationmap_view_navigationmap' => array(
        'classname'     => 'mod_navigationmap_external',
        'methodname'    => 'view_navigationmap',
        'description'   => 'Simulate the view.php web interface navigationmap: trigger events, completion, etc...',
        'type'          => 'write',
        'capabilities'  => 'mod/navigationmap:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),

    'mod_navigationmap_get_navigationmaps_by_courses' => array(
        'classname'     => 'mod_navigationmap_external',
        'methodname'    => 'get_navigationmaps_by_courses',
        'description'   => 'Returns a list of navigationmaps in a provided list of courses, if no list is provided all navigationmaps that the user
                            can view will be returned.',
        'type'          => 'read',
        'capabilities'  => 'mod/navigationmap:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    
    'mod_navigationmap_is_navigationmap_course' => array(
        'classname'     => 'mod_navigationmap_external',
        'methodname'    => 'is_navigationmap_course',
        'description'   => 'Returns true if the course with the provided course id has at least one navigationmap instance in the first section, false otherwise',
        'type'          => 'read',
        'capabilities'  => 'mod/navigationmap:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    
    'mod_navigationmap_filter_navigationmap_courseids' => array(
        'classname'     => 'mod_navigationmap_external',
        'methodname'    => 'filter_navigationmap_courseids',
        'description'   => 'Filters the provided course ids and returns only the ids of those course the user is enrolled in and that have at least one navigationmap instance in the first section',
        'type'          => 'read',
        'capabilities'  => 'mod/navigationmap:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
);
