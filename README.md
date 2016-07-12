# Resource Notification, a Moodle plugin

## Purpose

This Moodle plugin allows a teacher to notify course students by email when a new resource/activity 
is created or modified into a course.

The notification is activated by an action performed by the teacher.
This new action is shown in a new entry at the end of the *Edit* dropdown menu,
available for each resource or activity, on a course page.

The notification message contains two links by default,
one to the resource, and the other to the course.
The Moodle admin can modify this default setting.
The teacher can change the text to send.

The message is sent to all the users enrolled into the course and allowed to view the resource.
The notification conforms to resource access restrictions such as course groups.


## Requirements

* Moodle 3.0 is required

Moodle has changed the way it loads plugins with its 3.1 version.
The new API was introduced in Moodle 3.0, hence the requirements.


## Installation

* Unpack or `git clone` under `local/`.
* Install the plugin into Moodle with either by typing `php admin/cli/upgrade.php` (CLI)
  or by visiting '/admin/index.php' (web).


## Credits

This plugin was developped by [Silecs](http://www.silecs.info)
and initially sponsored by [Université Paris 1 Panthéon-Sorbonne, France](https://www.univ-paris1.fr/),
as part of their main, heavily customised, [Moodle instance](https://cours.univ-paris1.fr/).

Additional development and migration to Moodle 3.x was sponsored by
[Xi’an Jiaotong-Liverpool University (XJTLU)](http://www.xjtlu.edu.cn/en/academics/aec.html).

