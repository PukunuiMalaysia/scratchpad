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
 * Handler script
 *
 * @package mod_scratchpad
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @copyright  2021 Tengku Alauddin - din@pukunui.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

/**
 * Scratchpad conversion handler
 */
class moodle1_mod_scratchpad_handler extends moodle1_mod_handler {

    /**
     * Declare the paths in moodle.xml we are able to convert
     *
     * The method returns list of {@link convert_path} instances.
     * For each path returned, the corresponding conversion method must be
     * defined.
     *
     * @return array of {@link convert_path} instances
     */
    public function get_paths() {
        return array(
            new convert_path(
                'scratchpad', '/MOODLE_BACKUP/COURSE/MODULES/MOD/SCRATCHPAD',
                array(
                    'renamefields' => array(
                        'assessed' => 'grade'
                    )
                )
            ),
            new convert_path('entries', '/MOODLE_BACKUP/COURSE/MODULES/MOD/SCRATCHPAD/ENTRIES'),
            new convert_path('entry', '/MOODLE_BACKUP/COURSE/MODULES/MOD/SCRATCHPAD/ENTRIES/ENTRY'),
        );
    }

    public function process_scratchpad($data) {

        // Get the course module id and context id.
        $instanceid = $data['id'];
        $cminfo     = $this->get_cminfo($instanceid);
        $moduleid   = $cminfo['id'];
        $contextid  = $this->converter->get_contextid(CONTEXT_MODULE, $moduleid);

        // We now have all information needed to start writing into the file.
        $this->open_xml_writer("activities/scratchpad_{$moduleid}/scratchpad.xml");
        $this->xmlwriter->begin_tag('activity', array('id' => $instanceid, 'moduleid' => $moduleid,
            'modulename' => 'scratchpad', 'contextid' => $contextid));
        $this->xmlwriter->begin_tag('scratchpad', array('id' => $instanceid));

        unset($data['id']);
        foreach ($data as $field => $value) {
            $this->xmlwriter->full_tag($field, $value);
        }

        return $data;
    }

    /**
     * This is executed when the parser reaches the <ENTRIES> opening element
     */
    public function on_entries_start() {
        $this->xmlwriter->begin_tag('entries');
    }

    /**
     * This is executed every time we have one /MOODLE_BACKUP/COURSE/MODULES/MOD/SCRATCHPAD/ENTRIES/ENTRY
     * data available
     */
    public function process_entry($data) {
        $this->write_xml('entry', $data, array('/entry/id'));
    }

    /**
     * This is executed when the parser reaches the closing </ENTRIES> element
     */
    public function on_entries_end() {
        $this->xmlwriter->end_tag('entries');
    }

    /**
     * This is executed when we reach the closing </MOD> tag of our 'scratchpad' path
     */
    public function on_scratchpad_end() {

        $this->xmlwriter->end_tag('scratchpad');
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();
    }
}
