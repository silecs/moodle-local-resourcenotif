<?php
/**
 * @package    local_resourcenotif
 * @copyright  2012-2018 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once('lib_resourcenotif.php');
require_once('resourcenotif_form.php');

$id = required_param('id', PARAM_INT);

if (! $cm = get_coursemodule_from_id('', $id)) {
    print_error('invalidcoursemodule');
}

if (! $moduletype = $DB->get_field('modules', 'name', array('id'=>$cm->module), MUST_EXIST)) {
    print_error('invalidmodule');
}

if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
    print_error('coursemisconf');
}

if (! $module = $DB->get_record($moduletype, array("id"=>$cm->instance))) {
    print_error('invalidcoursemodule');
}

require_login($course, false, $cm);
$modcontext = context_module::instance($cm->id);
require_capability('moodle/course:manageactivities', $modcontext);

$url = new moodle_url('/local/resourcenotif/resourcenotif.php', array('id'=>$id));
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
$urleditactivite = $CFG->wwwroot . '/course/modedit.php?update=' . $cm->id;

$coursepath = resourcenotif_get_pathcategories_course($PAGE->categories, $course);

$mailsubject = resourcenotif_get_email_subject($course->shortname, format_string($cm->name));

$msgbodyinfo = array();
$msgbodyinfo['user'] = $USER->firstname . ' ' . $USER->lastname;
$msgbodyinfo['shortnamesite'] = $site->shortname;
$msgbodyinfo['nomactivite'] = format_string($cm->name);
$msgbodyinfo['urlactivite'] = $urlactivite;
$msgbodyinfo['editactivite'] = $urleditactivite;
$msgbodyinfo['urlcourse'] = $urlcourse;
$msgbodyinfo['shortnamecourse'] = $course->shortname;
$msgbodyinfo['fullnamecourse'] = $course->fullname;
$msgbodyinfo['coursepath'] = $coursepath;

$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($module->name));
$PAGE->requires->css(new moodle_url('/local/resourcenotif/resourcenotif.css'));

$recipicents = '';
$students = resourcenotif_get_users_from_course($course->id, 'student');
$notifiedStudents = [];

$modinfo = get_fast_modinfo($course)->get_cm($cm->id);
$info = new \core_availability\info_module($modinfo);
$notifiedStudents = $info->filter_user_list($students);
$nbNotifiedStudents = count($notifiedStudents);

if ($nbNotifiedStudents == 0) {
    $recipicents = get_string('norecipient', 'local_resourcenotif');
} else {
    $recipicents = resourcenotif_get_recipient_label($nbNotifiedStudents, $cm->availability, $msgbodyinfo);
}

$infoform = ['urlactivite' => $urlactivite, 'coursepath' => $coursepath,
    'courseid' => $course->id, 'recipicents' => $recipicents, 'nbNotifiedStudents' => $nbNotifiedStudents];
$infoform['mailsubject'] = $mailsubject;
$infoform['msgbodyinfo'] = $msgbodyinfo;
$infoform['siteshortname'] = $site->shortname;
$mform = new local_resourcenotif_resourcenotif_form(null, $infoform);

$newformdata = ['id'=>$id, 'mod' => $moduletype, 'courseid' => $course->id];
$mform->set_data($newformdata);
$formdata = $mform->get_data();

if ($mform->is_cancelled()) {
    redirect($urlcourse);
}

if ($formdata) {
    $msg = resourcenotif_get_notification_message($mailsubject, $msgbodyinfo, $formdata->complement);
    if ($formdata->send == 'all') {
        if (count($notifiedStudents)) {
            $msgresult = resourcenotif_send_notification($notifiedStudents, $msg, $infolog);
        }
    } elseif ($formdata->send == 'selection') {
        $groups = [];
        if (isset($formdata->groups) && count($formdata->groups)) {
           $groups =  $formdata->groups;
        }
        $groupings = [];
        if (isset($formdata->groupings) && count($formdata->groupings)) {
           $groupings =  $formdata->groupings;
        }
        $grpNotifiedStudents = resourcenotif_get_users_recipicents($groups, $groupings);
        if (count($grpNotifiedStudents)) {
            $msgresult = resourcenotif_send_notification($grpNotifiedStudents, $msg, $infolog);
        }
    } elseif ($formdata->send == 'selectionstudents') {
        $listidstudents = $formdata->students;
        if (count($listidstudents)) {
            $notifiedS = [];
            foreach ($listidstudents as $id) {
                $notifiedS[$id] = $students[$id];
            }
            $msgresult = resourcenotif_send_notification($notifiedS, $msg, $infolog);
        }
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('sendnotification', 'local_resourcenotif'));

if ($msgresult != '') {
    echo $OUTPUT->box_start('info');
    echo $msgresult;
    echo html_writer::tag('p', html_writer::link($urlcourse, get_string('returncourse', 'local_resourcenotif')));
    echo $OUTPUT->box_end();
} else {
    $mform->display();
}

echo $OUTPUT->footer();
