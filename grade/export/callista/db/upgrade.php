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

function xmldb_gradeexport_callista_upgrade($oldversion=0) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2015050400) {
        // Define field to be added to mark_override_manual.
        $table = new xmldb_table('callista_exp_marks');
        $field = new xmldb_field('mark_override_manual', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'outcome_load_message');
        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field to be added to grade_override_manual.
        $table = new xmldb_table('callista_exp_marks');
        $field = new xmldb_field('grade_override_manual', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'mark_override_manual');
        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2015050400, 'gradeexport', 'callista');
    }

}
