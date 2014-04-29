<?php
/**
 * @package    local
 * @subpackage resourcenotif
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//It must be included from a Moodle page

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->libdir.'/formslib.php');

class local_resourcenotif_resourcenotif_form extends moodleform {
    public function definition() {

        $mform =& $this->_form;

        // hidden elements
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'mod');
        $mform->setType('mod', PARAM_ALPHA);

        $mform->addElement('textarea', 'complement', null, array('rows' => 3,
            'cols' => 80));
        $mform->setType('complement',PARAM_RAW);

        $urlactivity = html_Writer::link($this->_customdata['urlactivite'], $this->_customdata['urlactivite']);

        $htmlinfo = html_Writer::empty_tag('br');
        $htmlinfo .= html_Writer::tag(
            'p',
            $this->_customdata['coursepath'] .  html_Writer::empty_tag('br') . $urlactivity,
            array('class' => 'notificationlabel')
        );

        $mform->addElement('html', $htmlinfo);

        //-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons(true,  get_string('submit', 'local_resourcenotif'));
    }
}
