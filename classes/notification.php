<?php
/**
 * @package    local_resourcenotif
 * @copyright  2012-2021 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_resourcenotif;

class notification
{
    /** @var stdClass $course course record */
    public $course; //
    /** @var stdClass $cm course_modules record */
    public $cm; // course module
    /** @var string $moduletype */
    public $moduletype;

    private $msgbodyinfo;
    private $nbsent = 0;
    private $message; //complete notification message

    public function __construct($course, $cm, $moduletype)
    {
        $this->course = $course;
        $this->cm = $cm;
        $this->moduletype = $moduletype;
    }

    public function set_message_body_info()
    {
        global $USER;
        $site = \get_site();

        $this->msgbodyinfo = [
            'user' => $USER->firstname . ' ' . $USER->lastname,
            'shortnamesite' => $site->shortname,
            'nameactivity' => format_string($this->cm->name),
            'urlactivity' => new \moodle_url('/mod/' . $this->moduletype . '/view.php', ['id' => $this->cm->id]),
            'urlcourse' => new \moodle_url('/course/view.php', ['id' => $this->course->id]),
            'shortnamecourse' => $this->course->shortname,
            'fullnamecourse' => $this->course->fullname,
        ];
    }

    /**
     * build the $message attribute (subject, text body and html body)
     *
     * @param string $complement
     * @return bool
     */
    public function set_notification_message($complement) {
        $message = new \stdClass();
        $message->subject = $this->get_message_subject();
        $message->from = $this->msgbodyinfo['shortnamesite'];
        $comhtml = '';
        $comtext = '';
        if (trim($complement)) {
            $comhtml .= format_text($complement, FORMAT_MOODLE);
            $comtext .= "\n\n" . $complement;
        }
        $message->bodyhtml = '<p>' . $this->get_message_body('html') . '</p>'
            . $comhtml;
        $message->bodytext = $this->get_message_body('text')
            . $comtext
            . "\n" . $this->msgbodyinfo['nameactivity'] . "\n" . $this->msgbodyinfo['urlactivity'];

        $this->message = $message;
        return true;
    }

    /**
     * prepare the customdata to pass to the main form
     * @param array $notifiablestudents
     * @return array
     */
    public function get_form_customdata($notifiablestudents)
    {
        if ( ! $notifiablestudents ) {
            $recipients = get_string('norecipient', 'local_resourcenotif');
        } else {
            $recipients = $this->get_recipients_label(count($notifiablestudents));
        }

        $infoform = [
            'urlactivity' => $this->msgbodyinfo['urlactivity'],
            'courseid' => $this->course->id,
            'recipients' => $recipients,
            'nbNotifiedStudents' => count($notifiablestudents),
            'emailsubject' => $this->get_message_subject(),
            'msgbodyinfo' => $this->msgbodyinfo,
            'siteshortname' => $this->msgbodyinfo['shortnamesite'],
            'formmsgbody' => $this->get_message_body('html'),
            'cm' => $this->cm,
            ];
        return $infoform;
    }


    /**
     * build the interface message : number and type of recipients
     *
     * @param int $nbdest
     * @return string
     */
    public function get_recipients_label($nbdest) {
        if ($nbdest == 0) {
            return get_string('norecipient', 'local_resourcenotif');
        }
        if ($this->cm->availability) {
            $a = new \stdClass();
            $a->nbdest = $nbdest;
            $a->linkactivity = $this->msgbodyinfo['urlactivity'];
            $a->nameactivity = $this->msgbodyinfo['nameactivity'];
            return get_string('grouprecipient', 'local_resourcenotif', $a);
        } else {
            return get_string('allstudentrecipient', 'local_resourcenotif', $nbdest);
        }
    }

    /**
     * Envoi une notification aux $users + copie à $USER
     *
     * @param array $users
     * @return string interface message
     */
    public function send_notifications($users) {
        global $USER;
        $nb = 0;
        foreach ($users as $user) {
            $res = $this->send_message($user);
            if ($res) {
                ++$nb;
            }
        }
        $this->send_message($USER);
        $this->nbsent = $nb;
        return $this->get_result_action_notification();
    }

    /**
     * construit le message d'interface après l'envoi groupé de notification
     *
     * @return string message interface
     */
    private function get_result_action_notification() {
        if ($this->nbsent == 0) {
            return get_string('nomessagesend', 'local_resourcenotif');
        }
        $message = get_string('numbernotification', 'local_resourcenotif', $this->nbsent);
        return $message;
    }

    /**
     * Envoie un message interne à l'utilisateur spécifié
     *
     * @param stdClass $user
     * @return mixed false ou resultat de la fonction message_send()
     **/
    private function send_message($user) {
        global $USER;

        if (!isset($user->email) && empty($user->email)) {
            return false;
        }
        $eventdata = new \core\message\message();
        $eventdata->courseid = (int)$this->course->id;
        $eventdata->component = 'local_resourcenotif';
        $eventdata->name = 'resourcenotif_notification';
        $eventdata->userfrom = $USER;
        $eventdata->userto = $user;
        $eventdata->subject = $this->message->subject;
        $eventdata->fullmessage = $this->message->bodytext;
        $eventdata->fullmessagehtml = $this->message->bodyhtml;
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
     * build the message subject
     *
     * @return string
     */
    private function get_message_subject() {
        $subject = sprintf('%s %s - %s',
            get_string('notification', 'local_resourcenotif'),
            $this->course->shortname,
            \format_string($this->cm->name));
        return $subject;
    }

    /**
     * interpolate variables in the message body
     * @param string $type 'hmtl' or 'txt'
     * return string
     */
    public function get_message_body($type) {
        $message_body = get_config('local_resourcenotif','message_body');
        $coursename = $this->msgbodyinfo['fullnamecourse'];
        $message_body = str_replace('[[sender]]', $this->msgbodyinfo['user'], $message_body);

        if ($type == 'html') {
            $linkactivity = \html_writer::link($this->msgbodyinfo['urlactivity'], $this->msgbodyinfo['nameactivity']);
            $linkcourse = \html_writer::link($this->msgbodyinfo['urlcourse'], $coursename);
        } else {
            $linkactivity = $this->msgbodyinfo['nameactivity'];
            $linkcourse = $coursename;
        }
        $message_body = str_replace('[[linkactivity]]', $linkactivity, $message_body);
        $message_body = str_replace('[[linkcourse]]', $linkcourse, $message_body);
        return $message_body;
    }

}