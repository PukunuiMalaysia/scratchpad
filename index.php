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
 * This page lists all the instances of scratchpad in a particular course
 *
 * @package mod_scratchpad
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/


require_once(__DIR__ . "/../../config.php");
require_once("lib.php");


$id = required_param('id', PARAM_INT);   // Course.

if (! $course = $DB->get_record("course", array("id" => $id))) {
    print_error("Course ID is incorrect");
}

require_course_login($course);


// Header.
$strscratchpads = get_string("modulenameplural", "scratchpad");
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/scratchpad/index.php', array('id' => $id));
$PAGE->navbar->add($strscratchpads);
$PAGE->set_title($strscratchpads);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($strscratchpads);

if (! $scratchpads = get_all_instances_in_course("scratchpad", $course)) {
    notice(get_string('thereareno', 'moodle', get_string("modulenameplural", "scratchpad")), "../../course/view.php?id=$course->id");
    die;
}

// Sections.
$usesections = course_format_uses_sections($course->format);
if ($usesections) {
    $modinfo = get_fast_modinfo($course);
    $sections = $modinfo->get_section_info_all();
}

$timenow = time();


// Table data.
$table = new html_table();

$table->head = array();
$table->align = array();
if ($usesections) {
    $table->head[] = get_string('sectionname', 'format_'.$course->format);
    $table->align[] = 'center';
}

$table->head[] = get_string('name');
$table->align[] = 'left';
$table->head[] = get_string('description');
$table->align[] = 'left';

$currentsection = '';
$i = 0;
foreach ($scratchpads as $scratchpad) {

    $context = context_module::instance($scratchpad->coursemodule);
    $entriesmanager = has_capability('mod/scratchpad:manageentries', $context);

    // Section.
    $printsection = '';
    if ($scratchpad->section !== $currentsection) {
        if ($scratchpad->section) {
            $printsection = get_section_name($course, $sections[$scratchpad->section]);
        }
        if ($currentsection !== '') {
            $table->data[$i] = 'hr';
            $i++;
        }
        $currentsection = $scratchpad->section;
    }
    if ($usesections) {
        $table->data[$i][] = $printsection;
    }

    // Link.
    $scratchpadname = format_string($scratchpad->name, true, array('context' => $context));
    if (!$scratchpad->visible) {
        // Show dimmed if the mod is hidden.
        $table->data[$i][] = "<a class=\"dimmed\" href=\"view.php?id=$scratchpad->coursemodule\">".$scratchpadname."</a>";
    } else {
        // Show normal if the mod is visible.
        $table->data[$i][] = "<a href=\"view.php?id=$scratchpad->coursemodule\">".$scratchpadname."</a>";
    }

    // Description.
    $table->data[$i][] = format_text($scratchpad->intro,  $scratchpad->introformat, array('context' => $context));

    // Entries info.
    if ($entriesmanager) {

        // Display the report.php col only if is a entries manager in some CONTEXT_MODULE.
        if (empty($managersomewhere)) {
            $table->head[] = get_string('viewentries', 'scratchpad');
            $table->align[] = 'left';
            $managersomewhere = true;

            // Fill the previous col cells.
            $manageentriescell = count($table->head) - 1;
            for ($j = 0; $j < $i; $j++) {
                if (is_array($table->data[$j])) {
                    $table->data[$j][$manageentriescell] = '';
                }
            }
        }

        $entrycount = scratchpad_count_entries($scratchpad, groups_get_all_groups($course->id, $USER->id));
        $table->data[$i][] = "<a href=\"report.php?id=$scratchpad->coursemodule\">".
            get_string("viewallentries", "scratchpad", $entrycount)."</a>";
    } else if (!empty($managersomewhere)) {
        $table->data[$i][] = "";
    }

    $i++;
}

echo "<br />";

echo html_writer::table($table);

// Trigger course module instance list event.
$params = array(
    'context' => context_course::instance($course->id)
);
$event = \mod_scratchpad\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

echo $OUTPUT->footer();
