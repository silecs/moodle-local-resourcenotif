<?php
/**
 * @package    local
 * @subpackage resourcenotif
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_once('lib_resourcenotif.php');
require_once('resourcenotif_form.php');

$id = required_param('id', PARAM_INT);
$moduletype = required_param('mod', PARAM_ALPHA);

if (! $cm = get_coursemodule_from_id($moduletype, $id)) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
    print_error('coursemisconf');
}

if (! $module = $DB->get_record($moduletype, array("id"=>$cm->instance))) {
    print_error('invalidcoursemodule');
}

require_login($course, true, $cm);

$url = new moodle_url('/local/resourcenotif/resourcenotif.php', array('mod' => $moduletype, 'id'=>$id));
$PAGE->set_url($url);

$site = get_site();

$msgresult = '';
$infolog = array();
$infolog['courseid'] = $cm->course;
$infolog['cmid'] = $cm->id;
$infolog['cmurl'] = $url;
$infolog['userid'] = $USER->id;

$urlcourse = $CFG->wwwroot . '/course/view.php?id='.$course->id;
$urlactivite = $CFG->wwwroot . '/mod/' . $moduletype . '/view.php?id=' . $cm->id;

$coursepath = resourcenotif_get_pathcategories_course($PAGE->categories, $course);

$mailsubject = resourcenotif_get_email_subject($site->shortname, $course->shortname, format_string($cm->name));

$msgbodyinfo = array();
$msgbodyinfo['user'] = $USER->firstname . ' ' . $USER->lastname;
$msgbodyinfo['shortnamesite'] = $site->shortname;
$msgbodyinfo['nomactivite'] = format_string($cm->name);
$msgbodyinfo['urlactivite'] = $urlactivite;
$msgbodyinfo['urlcourse'] = $urlcourse;
$msgbodyinfo['shortnamecourse'] = $course->shortname;
$msgbodyinfo['fullnamecourse'] = $course->fullname;
$msgbodyinfo['coursepath'] = $coursepath;

$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($module->name));
$PAGE->requires->css(new moodle_url('/local/resourcenotif/resourcenotif.css'));

$recipicents = '';
$students = array();

// le groupmode
$groupmode = groups_get_activity_groupmode($cm);

if ($groupmode == 0) {
    // pas de groupe, envoyé à tous les étudiants
    $students = resourcenotif_get_users_from_course($course, 'student');
    $recipicents = resourcenotif_get_recipient_label(count($students), $cm->groupingid, $msgbodyinfo);
} elseif ($cm->groupingid != 0) {
    //envoyé au groupe
    $students = groups_get_grouping_members($cm->groupingid);
    $recipicents = resourcenotif_get_recipient_label(count($students), $cm->groupingid, $msgbodyinfo);
} else {
    $recipicents = get_string('norecipient', 'local_resourcenotif');
}

$mform = new local_resourcenotif_resourcenotif_form(null,
    array('urlactivite' => $urlactivite, 'coursepath' => $coursepath));

$newformdata = array('id'=>$id, 'mod' => $moduletype);
$mform->set_data($newformdata);
$formdata = $mform->get_data();


if ($mform->is_cancelled()) {
    redirect($urlcourse);
}

if ($formdata) {
    $msg = resourcenotif_get_notification_message($mailsubject, $msgbodyinfo, $formdata->complement);
    if (count($students)) {
        $msgresult = resourcenotif_send_notification($students, $msg, $infolog);
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('sendnotification', 'local_resourcenotif'));

if ($msgresult != '') {
    echo $OUTPUT->box_start('info');
    echo $msgresult;
    echo html_Writer::tag('p', html_Writer::link($urlcourse, get_string('returncourse', 'local_resourcenotif')));
    echo $OUTPUT->box_end();
} else {
    echo html_Writer::tag('p', $recipicents, array('class' => 'notificationlabel'));

    $senderlabel = html_Writer::tag('span', get_string('sender', 'local_resourcenotif'), array('class' => 'notificationgras'));
    $sender = $site->shortname . ' &#60;'. $CFG->noreplyaddress . '&#62;';
    echo html_Writer::tag('p', $senderlabel . $sender, array('class' => 'notificationlabel'));

    echo html_Writer::tag('p', get_string('subject', 'local_resourcenotif') . $mailsubject, array('class' => 'notificationlabel'));

    $msgbody = resourcenotif_get_email_body($msgbodyinfo, 'html');
    echo html_Writer::tag('p', get_string('body', 'local_resourcenotif'), array('class' => 'notificationlabel notificationgras'));
    echo html_Writer::tag('p', $msgbody, array('class' => 'notificationlabel'));
    echo html_Writer::tag('div', get_string('complement', 'local_resourcenotif'), array('class' => 'notificationlabel'));

    $mform->display();
}

echo $OUTPUT->footer();
?>
