<?php
/**
 * @package    local_resourcenotif
 * @copyright  2012-2021 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_resourcenotif;

class notification
{

    /**
     * construit l'objet $message contenant le sujet et le corps de message version texte et html
     *
     * @param string $subject
     * @param mixed $msgbodyinfo
     * @param string $complement
     * @return object $message
     */
    public static function get_notification_message($subject, $msgbodyinfo, $complement) {
        $message = new \stdClass();
        $message->subject = $subject;
        $message->from = $msgbodyinfo['shortnamesite'];
        $comhtml = '';
        $comtext = '';
        if (trim($complement)) {
            $comhtml .= format_text($complement, FORMAT_MOODLE);
            $comtext .= "\n\n" . $complement;
        }
        $message->bodyhtml = '<p>' . self::get_email_body($msgbodyinfo, 'html') . '</p>'
            . $comhtml;
        $message->bodytext = self::get_email_body($msgbodyinfo, 'text')
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
    public static function get_recipient_label($nbdest, $availability, $msgbodyinfo) {
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
     * Envoi une notification aux $users + copie à $USER
     *
     * @param array $idusers
     * @param object $msg
     * @param array $infolog informations pour le log pour les envois de mails
     * @return string message interface
     */
    public static function send_notification($users, $msg, $infolog) {
        global $USER;
        $nb = 0;
        foreach ($users as $user) {
            $res = self::send_email($user, $msg, $infolog['courseid']);
            if ($res) {
                ++$nb;
            }
        }
        self::send_email($USER, $msg, $infolog['courseid']);
        $infolog['nb'] = $nb;
        return self::get_result_action_notification($infolog);
    }

    /**
     * construit le message d'interface après l'envoi groupé de notification
     *
     * @param array $infolog informations pour le log pour les envois de mails
     * @return string message interface
     */
    private static function get_result_action_notification($infolog) {
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
    private static function send_email($user, $msg, $courseid) {
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
        $eventdata->contexturl = new \moodle_url('/course/view.php', ['id' => $eventdata->courseid]);
        return \message_send($eventdata);
    }

    /**
     * construit le sujet du mail envoyé
     *
     * @param string $courseshortname
     * @param string $activitename
     * @return string
     */
    public static function get_email_subject($courseshortname, $activitename) {
        $subject = get_string('notification', 'local_resourcenotif')
            . $courseshortname . ' - ' . $activitename;
        return $subject;
    }

    /**
     * @param array $msgbodyinfo
     * @param string $type
     * return string
     */
    public static function get_email_body($msgbodyinfo, $type) {
        $message_body = get_config('local_resourcenotif','message_body');
        $coursename = $msgbodyinfo['fullnamecourse'];
        $message_body = str_replace('[[sender]]', $msgbodyinfo['user'], $message_body);

        if ($type == 'html') {
            $linkactivity = \html_writer::link($msgbodyinfo['urlactivite'], $msgbodyinfo['nomactivite']);
            $linkcourse = \html_writer::link($msgbodyinfo['urlcourse'], $coursename);
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
    public static function get_pathcategories_course($categories, $course) {
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

}