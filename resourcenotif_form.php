<?php
/**
 * @package    local_resourcenotif
 * @copyright  2012-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->libdir.'/formslib.php');

class local_resourcenotif_resourcenotif_form extends moodleform {
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $customdata = $this->_customdata;

        // hidden elements
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'mod');
        $mform->setType('mod', PARAM_ALPHA);
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        //recipients
        $sendok = true;
        $mform->addElement('header', 'recipient', get_string('recipients', 'local_resourcenotif'));
        $optionsSendAll = [];
        if ($customdata['nbNotifiedStudents'] == 0) {
            $optionsSendAll = ['disabled' => 'disabled'];
            $sendok = false;
        }
        $mform->addElement('radio', 'send', '', '<span class="fake-fitemtitle">' . $customdata['recipicents'] . '</span>', 'all', $optionsSendAll);
        if($sendok) {
            $mform->setDefault('send', 'all');
        }

        $allgroups = resourcenotif_get_all_groups($customdata['courseid']);
        $allgroupings = resourcenotif_get_all_groupings($customdata['courseid']);
        $selected = [];

        if (count($allgroups)) {
            $selectgroup = $mform->CreateElement('select','groups', 'Groupes', $allgroups);
            $selectgroup->setMultiple(true);
            $selected[] = $selectgroup;
        }
        if (count($allgroupings)) {
            $selectgrouping = $mform->CreateElement('select','groupings', 'Groupements', $allgroupings);
            $selectgrouping->setMultiple(true);
            $selected[] = $selectgrouping;
        }

        if (count($selected)) {
            $mform->addElement(
                'radio',
                'send',
                '',
                '<span class="fake-fitemtitle">' . get_string('selectedmembers', 'local_resourcenotif') . '</span>',
                'selection'
            );
            $mform->addGroup($selected, 'myselected', "", array('&nbsp;&nbsp;&nbsp;'), false);
            $mform->disabledIf('groups[]', 'send', 'neq', 'selection');
            $mform->disabledIf('groupings[]', 'send', 'neq', 'selection');
            if ($sendok == false) {
                $mform->setDefault('send', 'selection');
                $sendok = true;
            }
        } else {
            $mform->addElement('radio', 'send', '', '<span class="fake-fitemtitle">'
                . get_string('groupsgroupingsnone', 'local_resourcenotif') . '</span>', 'selection', ['disabled' => 'disabled']);
        }

        //message
        $mform->addElement('header', 'message', get_string('content', 'local_resourcenotif'));
        $msghtml = '';
        $senderlabel = html_writer::tag('span', get_string('sender', 'local_resourcenotif'), array('class' => 'notificationgras'));
        $sender = $customdata['siteshortname'] . ' &#60;'. $CFG->noreplyaddress . '&#62;';
        $msghtml .= html_writer::tag('p', $senderlabel . $sender, array('class' => 'notificationlabel'));
        $msghtml .= html_writer::tag('p', get_string('subject', 'local_resourcenotif') . $customdata['mailsubject'], array('class' => 'notificationlabel'));
        $msgbody = resourcenotif_get_email_body($customdata['msgbodyinfo'], 'html');
        $msghtml .= html_writer::tag('p', get_string('body', 'local_resourcenotif'), array('class' => 'notificationlabel notificationgras'));
        $msghtml .= html_writer::tag('p', $msgbody, array('class' => 'notificationlabel'));
        $msghtml .= html_writer::tag('div', get_string('complement', 'local_resourcenotif'), array('class' => 'notificationlabel'));

        $mform->addElement('html', $msghtml);

        $mform->addElement('textarea', 'complement', null, ['rows' => 3, 'cols' => 80]);
        $mform->setType('complement',PARAM_RAW);

        $urlactivity = html_writer::link($this->_customdata['urlactivite'], $this->_customdata['urlactivite']);

        $htmlinfo = html_writer::empty_tag('br');
        $htmlinfo .= html_writer::tag(
            'p',
            $this->_customdata['coursepath'] .  html_writer::empty_tag('br') . $urlactivity,
            array('class' => 'notificationlabel')
        );

        $mform->addElement('html', $htmlinfo);

        //-------------------------------------------------------------------------------
        // buttons
        if ($sendok) {
            $this->add_action_buttons(true,  get_string('submit', 'local_resourcenotif'));
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($errors)) {
            $this->validation_recipicent($data, $errors);
        }
        return $errors;
    }

    private function validation_recipicent($data, &$errors) {
        if (isset($data['send']) == false) {
            $errors['send'] = get_string('errorselectrecipicent', 'local_resourcenotif');
        } else {
            if ($data['send'] == 'selection' && isset($data['groups']) == false && isset($data['groupings']) == false) {
                 $errors['myselected'] = get_string('errorselectgroup', 'local_resourcenotif');
            }
        }
        return $errors;
    }
}
