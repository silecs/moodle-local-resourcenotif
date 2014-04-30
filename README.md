# Purpose

This Moodle plugin allows a teacher to notify course students by mail when a new resource/activity 
is created or modified into a course.

The notification is activated by an action performed by the teacher, available as a new entry 
in the *Edit* dropdown menu available for each resource or activity.
The notification message can be customized, but contains by default two links, one to the resource
and the other to the course.
The message is sent to all users enrolled into the course and permitted to view the resource.
Thus, the notification conforms to resource access restrictions as course groups.


# Installation trick

This plugin contains the main notification code and must be installed under `moodle/local/`, 
but you need to slightly edit the core Moodle code to insert the *Notification* entry 
in the Edit menu. The target file is `course/lib.php`.
You can edit it by applying the patch file `course-lib.patch` with the patch command (unix) or by hand.
In the latter case, you simply need no add the lines which are prefixed by a '+' sign.


# Credits

This plugin was developped by [Silecs](http://www.silecs.info) and sponsored by 
[Université Paris 1 Panthéon-Sorbonne, France](https://www.univ-paris1.fr/),
as part of their main, heavily customised, [Moodle instance](https://cours.univ-paris1.fr/).
