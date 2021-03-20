<?php
/**
 * @package    local_resourcenotif
 * @copyright  2012-2021 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_resourcenotif;

class notifstudents
{

    public $courseid;

    public function __construct(int $courseid)
    {
        $this->courseid = $courseid;
    }

    /**
     * renvoie les utilisateurs ayant le rôle 'rolename'
     * dans le cours $courseid
     *
     * @param int $courseid
     * @param string $rolename shortname du rôle
     * @return array [users...]
     */
    public function get_users_from_course($rolename) {
        global $DB;
        $coursecontext = \context_course::instance($this->courseid);
        $roletarget = $DB->get_record('role', ['shortname'=> $rolename]);
        $targetcontext = get_users_from_role_on_context($roletarget, $coursecontext);

        if (count($targetcontext) == 0) {
            return $targetcontext;
        }
        $ids = [];
        foreach ($targetcontext as $sc) {
            $ids[] = (int) $sc->userid;
        }
        $sql = "SELECT * FROM {user} WHERE id IN (" . join(",", $ids) . ")";
        $students = $DB->get_records_sql($sql);

        return $students;
    }

    /**
     * Renvoie tous les groupes d'un cours
     *
     * @return array groups
     */
    public function get_all_groups() {
        $groups = [];
        $allgroups = groups_get_all_groups($this->courseid);
        if (count($allgroups)) {
            foreach ($allgroups as $id => $group) {
                $groups[$id] = $group->name;
            }
        }
        return $groups;
    }

    /**
     * Retourne tous les groupements d'un cours
     *
     * @return array $groupings
     */
    public function get_all_groupings() {
        $groupings = [];
        $allgroupings = groups_get_all_groupings($this->courseid);
        if (count($allgroupings)) {
            foreach ($allgroupings as $id => $grouping) {
                $groupings[$id] = $grouping->name;
            }
        }
        return $groupings;
    }

    /**
     * Renvoie le tableau des étudiants inscrit à un cours
     *
     * @todo Should use `fullname()` instead of hardcoded format.
     *
     * @return array [id => studentname]
     **/
    public function get_list_students() {
        $listStudent = [];
        $students = $this->get_users_from_course('student');
        if (!empty($students)) {
            foreach ($students as $id => $student) {
                $listStudent[$id] = $student->firstname . ' ' . $student->lastname;
            }
        }
        return $listStudent;
    }

    /**
     * Renvoie le tableau des utilisateurs appartenant aux groupes $groups
     * ou aux groupements $groupings
     *
     * @param array $groups
     * @param array $groupings
     * @return array users
     */
    public static function get_users_recipients($groups, $groupings) {
        $users = [];
        if (count($groups)) {
            foreach ($groups as $groupid) {
                $userg = groups_get_members($groupid);
                foreach ($userg as $id => $u) {
                    if (!isset($users[$id])) {
                        $users[$id] = $u;
                    }
                }
            }
        }
        if (count($groupings)) {
            foreach ($groupings as $groupingid) {
                $usergp = groups_get_grouping_members($groupingid);
                foreach ($usergp as $id => $u) {
                    if (!isset($users[$id])) {
                        $users[$id] = $u;
                    }
                }
            }
        }
        return $users;
    }
}