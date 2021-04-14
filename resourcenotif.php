<?php
/**
 * @package    local_resourcenotif
 * @copyright  2012-2021 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \local_resourcenotif\notification;
use \local_resourcenotif\notifstudents;
use \local_resourcenotif\resourcenotif_form;

require_once("../../config.php");

$id = required_param('id', PARAM_INT);

if (! $cm = get_coursemodule_from_id('', $id)) {
    print_error('invalidcoursemodule');
}

if (! $moduletype = $DB->get_field('modules', 'name', ['id' => $cm->module], MUST_EXIST)) {
    print_error('invalidmodule');
}

if (! $course = $DB->get_record('course', ['id' => $cm->course])) {
    print_error('coursemisconf');
}

if (! $module = $DB->get_record($moduletype, ['id' => $cm->instance])) {
    print_error('invalidcoursemodule');
}

require_login($course, false, $cm);
$modcontext = context_module::instance($cm->id);
require_capability('moodle/course:manageactivities', $modcontext);

$url = new moodle_url('/local/resourcenotif/resourcenotif.php', ['id' => $id]);
$PAGE->set_url($url);

$msgresult = '';

$urlcourse = $CFG->wwwroot . '/course/view.php?id=' . $course->id;

$notificationprocess = new notification($PAGE->categories, $course, $cm, $moduletype);
$notificationprocess->set_message_body_info();

$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($module->name));
$PAGE->requires->css(new moodle_url('/local/resourcenotif/resourcenotif.css'));

$notifrecipients = new notifstudents($course->id, $cm);
$notifiablestudents = $notifrecipients->get_users_from_course('student');

$formcustomdata = $notificationprocess->get_form_customdata($notifiablestudents);
$mform = new resourcenotif_form(null, $formcustomdata);

$newformdata = ['id' => $id, 'mod' => $moduletype, 'courseid' => $course->id];
$mform->set_data($newformdata);
$formdata = $mform->get_data();

if ($mform->is_cancelled()) {
    redirect($urlcourse);
}

if ($formdata) {
    $notificationprocess->set_notification_message($formdata->complement);

    if ($formdata->send == 'all') {
        if (count($notifiablestudents)) {
            $msgresult = $notificationprocess->send_notifications($notifiablestudents);
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
        $grpNotifiedStudents = notifstudents::get_users_recipients($groups, $groupings);
        if (count($grpNotifiedStudents)) {
            $msgresult = $notificationprocess->send_notifications($grpNotifiedStudents);
        }
    } elseif ($formdata->send == 'selectionstudents') {
        $listidstudents = $formdata->students;
        if (count($listidstudents)) {
            $notifiedS = [];
            foreach ($listidstudents as $id) {
                $notifiedS[$id] = $notifiablestudents[$id];
            }
            $msgresult = $notificationprocess->send_notifications($notifiedS);
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
