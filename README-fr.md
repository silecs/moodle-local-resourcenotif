# But

Ce plugin Moodle permet à un enseignant de notifier par mail tous les étudiants d'un cours
qu'une ressource/activité a été ajoutée ou mise à jour.

La notification est déclenchée par l'action volontaire d'un enseignant, sous la forme d'une
nouvelle entrée dans le menu déroulant *Modifier* disponible pour chaque cours ou activité.
Le message envoyé peut être personnalisé. Il contient par défaut les liens vers la ressource
cible et vers le cours.
Le message est envoyé à tous les utilisateurs inscrits au cours et autorisés à voir la ressource.
En particulier, le plugin respecte les restrictions d'accès comme les groupes internes aux cours.


# Installation

Ce plugin contient le code principal et doit être installé dans le répertoire `moodle/local`,
mais vous devez légèrement modifier le code original de Moodle pour intégrer l'entrée *Notification*
dans le menu d'édition de ressource. Le fichier cible est `moodle/course/lib.php`.
Vous pouvez le modifier en appliquant le patch `course-lib.patch` avec la commande patch (unix),
ou manuellement. Dans ce dernier cas, vous devez simplement ajouter au fichier `course/lib.php`
les lignes préfixées par le signe '+'.


# Crédits

Ce plugin a été développé par [Silecs](http://www.silecs.info) à la demande de 
l'[Université Paris 1 Panthéon-Sorbonne, France](https://www.univ-paris1.fr/).
Il est intégré à leur [instance Moodle principale](https://cours.univ-paris1.fr/),
fortement personnalisée.

