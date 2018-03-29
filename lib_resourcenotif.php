<?php
/**
 * @package    local_resourcenotif
 * @copyright  2012-2018 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * construit l'objet $message contenant le sujet et le corps de message version texte et html
 *
 * @param string $subject
 * @param mixed $msgbodyinfo
 * @param string $complement
 * @return object $message
 */
function resourcenotif_get_notification_message($subject, $msgbodyinfo, $complement) {
    $message = new stdClass();
    $message->subject = $subject;
    $message->from = $msgbodyinfo['shortnamesite'];
    $comhtml = '';
    $comtext = '';
    if (trim($complement)) {
        $comhtml .= format_text($complement, FORMAT_MOODLE);
        $comtext .= "\n\n" . $complement;
    }
    $message->bodyhtml = '<p>' . resourcenotif_get_email_body($msgbodyinfo, 'html') . '</p>'
        . $comhtml;
    $message->bodytext = resourcenotif_get_email_body($msgbodyinfo, 'text')
        . $comtext
        . "\n\n" . $msgbodyinfo['coursepath']
        . "\n" . $msgbodyinfo['nomactivite'] . "\n" . $msgbodyinfo['urlactivite'];
    return $message;
}

/**
 * construit le messsage d'interface du nombre et de la qualité des
 * destinataires du message
 *
 * @param int $nbdest
 * @param string $availability
 * @param array $msgbodyinfo
 * @return string
 */
function resourcenotif_get_recipient_label($nbdest, $availability, $msgbodyinfo) {
    if ($nbdest == 0) {
        return get_string('norecipient', 'local_resourcenotif');
    }
    if ($availability) {
        $a = new stdClass();
        $a->nbdest = $nbdest;
        $a->linkactivity = $msgbodyinfo['urlactivite'];
        $a->nameactivity = $msgbodyinfo['nomactivite'];
        $a->editactivity = $msgbodyinfo['editactivite'];
        return get_string('grouprecipient', 'local_resourcenotif', $a);
    } else {
        return get_string('allstudentrecipient', 'local_resourcenotif', $nbdest);
    }
}

/**
 * renvoie les utilisateurs ayant le rôle 'rolename'
 * dans le cours $courseid
 *
 * @param int $courseid
 * @param string $rolename shortname du rôle
 * @return array [users...]
 */
function resourcenotif_get_users_from_course($courseid, $rolename) {
    global $DB;
    $coursecontext = context_course::instance($courseid);
    $rolestudent = $DB->get_record('role', array('shortname'=> $rolename));
    $studentcontext = get_users_from_role_on_context($rolestudent, $coursecontext);

    if (count($studentcontext) == 0) {
        return $studentcontext;
    }
    $ids = [];
    foreach ($studentcontext as $sc) {
        $ids[] = (int) $sc->userid;
    }
    $sql = "SELECT * FROM {user} WHERE id IN (" . join(",", $ids) . ")";
    $students = $DB->get_records_sql($sql);

    return $students;
}

/**
 * Envoi une notification aux $users + copie à $USER
 *
 * @param array $idusers
 * @param object $msg
 * @param array $infolog informations pour le log pour les envois de mails
 * @return string message interface
 */
function resourcenotif_send_notification($users, $msg, $infolog) {
    global $USER;
    $nb = 0;
    foreach ($users as $user) {
        $res = resourcenotif_send_email($user, $msg, $infolog['courseid']);
        if ($res) {
            ++$nb;
        }
    }
    resourcenotif_send_email($USER, $msg, $infolog['courseid']);
    $infolog['nb'] = $nb;
    return resourcenotif_get_result_action_notification($infolog);
}

/**
 * construit le message d'interface après l'envoi groupé de notification
 *
 * @param array $infolog informations pour le log pour les envois de mails
 * @return string message interface
 */
function resourcenotif_get_result_action_notification($infolog) {
    if ($infolog['nb'] == 0) {
        return get_string('nomessagesend', 'local_resourcenotif');
    }
    $message = get_string('numbernotification', 'local_resourcenotif', $infolog['nb']);
    return $message;
}

/**
 * Envoie un email à l'adresse mail spécifiée
 *
 * @param string $email
 * @param object $msg
 * @param int $courseid
 * @return mixed false ou resultat de la fonction email_to_user()
 **/
function resourcenotif_send_email($user, $msg, $courseid) {
    global $USER;

    if (!isset($user->email) && empty($user->email)) {
        return false;
    }
    $eventdata = new \core\message\message();
    $eventdata->courseid = (int)$courseid;
    $eventdata->component = 'local_resourcenotif';
    $eventdata->name = 'resourcenotif_notification';
    $eventdata->userfrom = $USER;
    $eventdata->userto = $user;
    $eventdata->subject = $msg->subject;
    $eventdata->fullmessage = $msg->bodytext;
    $eventdata->fullmessagehtml = $msg->bodyhtml;
    // With FORMAT_HTML, most outputs will use fullmessagehtml, and convert it to plain text if necessary.
    // but some output plugins will behave differently (airnotifier only uses fullmessage)
    $eventdata->fullmessageformat = FORMAT_HTML;
    // If smallmessage is not empty,
    // it will have priority over the 2 other fields, with a hard coded FORMAT_PLAIN.
    // But some output plugins may need it, as jabber currently does.
    $eventdata->smallmessage = "";
    return message_send($eventdata);
}

/**
 * construit le sujet du mail envoyé
 *
 * @param string $courseshortname
 * @param string $activitename
 * @return string
 */
function resourcenotif_get_email_subject($courseshortname, $activitename) {
    $subject = get_string('notification', 'local_resourcenotif')
        . $courseshortname . ' - ' . $activitename;
    return $subject;
}

/**
 * @param array $msgbodyinfo
 * @param string $type
 * return string
 */
function resourcenotif_get_email_body($msgbodyinfo, $type) {
    $message_body = get_config('local_resourcenotif','message_body');
    $coursename = $msgbodyinfo['fullnamecourse'];
    $message_body = str_replace('[[sender]]', $msgbodyinfo['user'], $message_body);

    if ($type == 'html') {
        $linkactivity = html_writer::link($msgbodyinfo['urlactivite'], $msgbodyinfo['nomactivite']);
        $linkcourse = html_writer::link($msgbodyinfo['urlcourse'], $coursename);
    } else {
        $linkactivity = $msgbodyinfo['nomactivite'];
        $linkcourse = $coursename;
    }
    $message_body = str_replace('[[linkactivity]]', $linkactivity, $message_body);
    $message_body = str_replace('[[linkcourse]]', $linkcourse, $message_body);
    return $message_body;
}

/**
 * Construit le chemin categories > cours
 *
 * @param array $categories tableau de tableaux
 * @param object $course
 * @return string path
 */
function resourcenotif_get_pathcategories_course($categories, $course) {
    $path ='';
    $tabcat = array();
    if (count($categories)) {
        foreach ($categories as $category) {
            $tabcat[$category->depth] = $category->name;
        }
        ksort($tabcat);
        foreach ($tabcat as $cat) {
            $path .= $cat . ' > ';
        }
    }
    $path .= $course->shortname;
    return $path;
}

/**
 * Renvoie tous les groupes d'un cours
 *
 * @param int $courseid
 * @return array groups
 */
function resourcenotif_get_all_groups($courseid) {
    $groups = [];
    $allgroups = groups_get_all_groups($courseid);
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
 * @param int $courseid
 * @return array $groupings
 */
function resourcenotif_get_all_groupings($courseid) {
    $groupings = [];
    $allgroupings = groups_get_all_groupings($courseid);
    if (count($allgroupings)) {
        foreach ($allgroupings as $id => $grouping) {
            $groupings[$id] = $grouping->name;
        }
    }
    return $groupings;
}

/**
 * Renvoie le tableau des utilisateurs appartenant aux groupes $groups
 * ou aux groupements $groupings
 *
 * @param array $groups
 * @param array $groupings
 * @return array users
 */
function resourcenotif_get_users_recipicents($groups, $groupings) {
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

/**
 * Renvoie le tableau des étudiants inscrit à un cours
 *
 * @todo Should use `fullname()` instead of hardcoded format.
 *
 * @param int $courseid
 * @return array [id => studentname]
 **/
function resourcenotif_get_list_students($courseid) {
    $listStudent = [];
    $students = resourcenotif_get_users_from_course($courseid, 'student');
    if (!empty($students)) {
        foreach ($students as $id => $student) {
            $listStudent[$id] = $student->firstname . ' ' . $student->lastname;
        }
    }
    return $listStudent;
}
