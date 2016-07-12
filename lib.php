<?php
/**
 * @package    local_resourcenotif
 * @copyright  2012-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function local_resourcenotif_extend_navigation_course() {
    global $OUTPUT, $PAGE;

    $html = '<li role="presentation">'
        . '<a class="editing_notifications menu-action cm-edit-action" data-action="notifications" role="menuitem" href="'
        . htmlspecialchars(new moodle_url('/local/resourcenotif/resourcenotif.php', array('id' => '123XYZ321')))
        . '" title="' . htmlspecialchars(get_string("notifications")) . '">'
        . $OUTPUT->pix_icon('t/email', get_string("notifications"), 'moodle', array('class' => 'iconsmall', 'title' => ''))
        . '<span class="menu-action-text">' . htmlspecialchars(get_string("notifications")) . '</span>'
        . '</a>'
        . '</li>';
    $enc = json_encode($html);

    $PAGE->requires->js_init_code(<<<EOJS
    var activities = document.querySelectorAll('.section-cm-edit-actions ul[role="menu"]');
    if (activities) {
        for (var i = 0; i < activities.length; i++) {
            var ul = activities[i];
            var owner = ul.parentNode.getAttribute('data-owner');
            if (owner) {
                var id = owner.replace(/^#module-/, '');
                ul.insertAdjacentHTML('beforeend', $enc.replace('123XYZ321', id));
            }
        }
    }
EOJS
    , true);
}

