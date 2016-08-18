<?php
/**
 * @package    local_resourcenotif
 * @copyright  2012-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$messageproviders = array (
    /*
     * Resource notification.
     */
    'resourcenotif_notification' => array(
        'defaults' => array(
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF
        ),
    ),
);


