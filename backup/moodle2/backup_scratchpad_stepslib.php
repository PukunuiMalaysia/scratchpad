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
 * Backup function
 *
 * @package mod_scratchpad
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @copyright  2021 Tengku Alauddin - din@pukunui.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

class backup_scratchpad_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        $scratchpad = new backup_nested_element('scratchpad', array('id'), array(
            'name', 'intro', 'introformat', 'days', 'grade', 'timemodified', 'preventry', 'mode', 'completionanswer'));

        $entries = new backup_nested_element('entries');

        $entry = new backup_nested_element('entry', array('id'), array(
            'userid', 'modified', 'text', 'format', 'rating',
            'entrycomment', 'teacher', 'timemarked', 'mailed'));

        // Scratchpad -> entries -> entry.
        $scratchpad->add_child($entries);
        $entries->add_child($entry);

        // Sources.
        $scratchpad->set_source_table('scratchpad', array('id' => backup::VAR_ACTIVITYID));

        if ($this->get_setting_value('userinfo')) {
            $entry->set_source_table('scratchpad_entries', array('scratchpad' => backup::VAR_PARENTID));
        }

        // Define id annotations.
        $entry->annotate_ids('user', 'userid');
        $entry->annotate_ids('user', 'teacher');

        // Define file annotations.
        $scratchpad->annotate_files('mod_scratchpad', 'intro', null); // This file areas haven't itemid.
        $entry->annotate_files('mod_scratchpad_entries', 'text', null); // This file areas haven't itemid.
        $entry->annotate_files('mod_scratchpad_entries', 'entrycomment', null); // This file areas haven't itemid.

        return $this->prepare_activity_structure($scratchpad);
    }
}
