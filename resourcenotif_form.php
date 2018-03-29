<?php
/**
 * @package    local_resourcenotif
 * @copyright  2012-2018 Silecs {@link http://www.silecs.info/societe}
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

        $liststudents = resourcenotif_get_list_students($customdata['courseid']);
        if (count($liststudents)) {
            $mform->addElement('radio', 'send', '', '<span class="fake-fitemtitle">' .
                get_string('selectstudents', 'local_resourcenotif') . '</span>', 'selectionstudents', $optionsSendAll);

            $mform->addElement('select', 'students', '', $liststudents)->setMultiple(true);
            $mform->disabledIf('students', 'send', 'neq', 'selectionstudents');
        }

        //message
        $mform->addElement('header', 'message', get_string('content', 'local_resourcenotif'));
        $subjectlabel = html_writer::tag('span', get_string('subject', 'local_resourcenotif'), array('class' => 'notificationgras'));
        $msgbody = resourcenotif_get_email_body($customdata['msgbodyinfo'], 'html');
        $msghtml = html_writer::tag('p', $subjectlabel . $customdata['mailsubject'], array('class' => 'notificationlabel'))
            . html_writer::tag('p', get_string('body', 'local_resourcenotif'), array('class' => 'notificationlabel notificationgras'))
            . html_writer::tag('p', $msgbody, array('class' => 'notificationlabel'));

        $mform->addElement('html', $msghtml);
        $mform->setExpanded('message');

        $mform->addElement('header', 'complementheader', get_string('complement', 'local_resourcenotif') );
        $mform->setExpanded('complementheader');
        $mform->addElement('textarea', 'complement', null, ['rows' => 5, 'class' => 'complement', 'style' => 'resize:both;']);
        $mform->setType('complement',PARAM_RAW);

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
        if (isset($data['send'])) {
            if ($data['send'] == 'selection' && !isset($data['groups']) && !isset($data['groupings'])) {
                 $errors['myselected'] = get_string('errorselectgroup', 'local_resourcenotif');
            }
            if ($data['send'] == 'selectionstudents' && !isset($data['students'])) {
                 $errors['students'] = get_string('errorselectstudent', 'local_resourcenotif');
            }
        } else {
            $errors['send'] = get_string('errorselectrecipicent', 'local_resourcenotif');
        }
        return $errors;
    }
}
