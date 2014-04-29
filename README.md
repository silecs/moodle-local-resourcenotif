# Purpose

This Moodle plugin allows a teacher to notify course students by mail when a new resource/activity 
is created or modified into a course.

The notification is activated by an action performed by the teacher, available as a new entry 
in the "Edit" dropdown menu available for each resource or activity.
The notification message can be customized, but contains by default two links, to the resource
and to the course.
The message is sent to all users enrolled into the course and permitted to view the resource.


# Installation trick

This plugin contains the code for the plugin and must be installed under moodle/local/, 
but you need to slightly edit the core Moodle code to insert the Notification entry 
in the edit menu. The target file is course/lib.php
You can edit it by applying the patch file "course-lib.patch" with the patch command (unix) or by hand.
In the latter case, you simply need no add the lines which are prefixed by a '+' sign.


This plugin was sponsored by Université Paris 1 Panthéon-Sorbonne, France, as part of a heavily customised Moodle instance.
