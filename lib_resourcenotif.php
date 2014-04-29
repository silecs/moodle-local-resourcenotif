<?php
/**
 * @package    local
 * @subpackage resourcenotif
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * construit l'objet $message contenant le sujet et le corps de message version texte et html
 * @param string $subject
 * @return object $message
 */
function resourcenotif_get_notification_message($subject, $msgbodyinfo, $complement) {
    $message = new object();
    $message->subject = $subject;
    $message->from = $msgbodyinfo['shortnamesite'];
    $comhtml = '';
    $comtext = '';
    if (trim($complement) !='') {
        $comhtml .= '<p>' . $complement . '</p>';
        $comtext .= "\n\n" . $complement;
    }
    $message->bodyhtml = '<p>' . resourcenotif_get_email_body($msgbodyinfo, 'html') . '</p>' . $comhtml;
    $message->bodytext = resourcenotif_get_email_body($msgbodyinfo, 'text') . $comtext;

    $message->bodyhtml .= '<p>' . $msgbodyinfo['coursepath']
        . '<br/><a href="' . $msgbodyinfo['urlactivite']
        . '">' . $msgbodyinfo['urlactivite'] . '</a></p>';
    $message->bodytext .= "\n\n" . $msgbodyinfo['coursepath']
        . "\n" . $msgbodyinfo['urlactivite'];
    return $message;
}

/**
 * construit le messsage d'interface du nombre et de la qualité des
 * destinataires du message
 * @param int $nbdest
 * @param int $groupingid
 * @param array $msgbodyinfo
 * @return string
 */
function resourcenotif_get_recipient_label($nbdest, $groupingid, $msgbodyinfo) {
    if ($nbdest == 0) {
        return get_string('norecipient', 'local_resourcenotif');
    }
    if ($groupingid == 0) {
        return get_string('allstudentrecipient', 'local_resourcenotif', $nbdest);
    } else {
        $a = new stdClass();
        $a->nbdest = $nbdest;
        $a->linkactivity = $msgbodyinfo['urlactivite'];
        $a->nameactivity = $msgbodyinfo['nomactivite'];
        return get_string('grouprecipient', 'local_resourcenotif', $a);
    }
}

/**
 * renvoie les utilisateurs ayant le rôle 'rolename'
 * dans le cours $course
 * @param object $course $course
 * @param string $rolename shortname du rôle
 * @return array de $user
 */
function resourcenotif_get_users_from_course($course, $rolename) {
    global $DB;
    $coursecontext = context_course::instance($course->id);
    $rolestudent = $DB->get_record('role', array('shortname'=> $rolename));
    $studentcontext = get_users_from_role_on_context($rolestudent, $coursecontext);

    if (count($studentcontext) == 0) {
        return $studentcontext;
    }
    $ids = '';
    foreach ($studentcontext as $sc) {
        $ids .= $sc->userid . ',';
    }
    $ids = substr($ids, 0, -1);
    $sql = "SELECT * FROM {user} WHERE id IN ({$ids})";
    $students = $DB->get_records_sql($sql);

    return $students;
}

/**
 * Envoi une notification aux $users + copie à $USER
 * @param array $idusers
 * @param object $msg
 * @param array $infolog informations pour le log pour les envois de mails
 * @return string : message interface
 */
function resourcenotif_send_notification($users, $msg, $infolog) {
    global $USER;
    $nb = 0;
    foreach ($users as $user) {
        $res = resourcenotif_send_email($user, $msg);
        if ($res) {
            ++$nb;
        }
    }
    resourcenotif_send_email($USER, $msg);
    $infolog['nb'] = $nb;
    return resourcenotif_get_result_action_notification($infolog);
}

/**
 * construit le message d'interface après l'envoi groupé de notification
 * @param array $infolog informations pour le log pour les envois de mails
 * @return string message interface
 */
function resourcenotif_get_result_action_notification($infolog) {
    if ($infolog['nb'] == 0) {
        return get_string('nomessagesend', 'local_resourcenotif');
    }
    $message = get_string('numbernotification', 'local_resourcenotif', $infolog['nb']);
    //log
    add_to_log($infolog['courseid'], 'resourcenotif', 'send notification_course',
        $infolog['cmurl'], $message , $infolog['cmid'], $infolog['userid']);
    return $message;
}

/**
 * Envoie un email à l'adresse mail spécifiée
 * @param string $email
 * @param object $msg
 * @return false ou resultat de la fonction email_to_user()
 **/
function resourcenotif_send_email($user, $msg) {
    if (!isset($user->email) && empty($user->email)) {
        return false;
    }
    $emailform = $msg->from;
    return email_to_user($user, $emailform, $msg->subject, $msg->bodytext, $msg->bodyhtml);
}

/**
 * construit le sujet du mail envoyé
 * @param string $siteshortname
 * @param string $courseshortname
 * @param string $activitename
 * @return string
 */
function resourcenotif_get_email_subject($siteshortname, $courseshortname, $activitename) {
    $subject = '';
    $subject .='['. $siteshortname . '] '. get_string('notification', 'local_resourcenotif')
        . $courseshortname . ' - ' . $activitename;
    return $subject;
}

/**
 * construit le
 * @param array $msgbodyinfo
 * @param string $type
 * return string
 */
function resourcenotif_get_email_body($msgbodyinfo, $type) {
    $res = '';
    $coursename = $msgbodyinfo['shortnamecourse'] . ' - ' . $msgbodyinfo['fullnamecourse'];
    $a = new stdClass();
    $a->sender = $msgbodyinfo['user'];
    $a->linkactivity = $msgbodyinfo['nomactivite'];
    $a->linkcourse = $msgbodyinfo['shortnamecourse'] . ' - ' . $msgbodyinfo['fullnamecourse'];
    if ($type == 'html') {
        $a->linkactivity = html_Writer::link($msgbodyinfo['urlactivite'], $msgbodyinfo['nomactivite']);
        $a->linkcourse = html_Writer::link($msgbodyinfo['urlcourse'], $coursename);
    }
    $res .= get_string('msgsender', 'local_resourcenotif', $a);
    return $res;
}

/**
 * Construit le chemin categories > cours
 * @param array $categories tableau de tableaux
 * @param object $course
 * @return string $path
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
?>
