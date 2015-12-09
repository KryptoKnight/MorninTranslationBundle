Overview
========

This Symfony2 bundle allow to:

* import translation files content into the database and provide a GUI to edit translations.
* export translations from the database into files.
* have an overview to check translation domains are completely translated.
* add new translations in the database.

The idea is to:

* write your translations files (xliff, yml or php) as usual for at least one language (the default language of your website for example).
* load translations into the database by using a command line.
* freely edit/add translation through an edition page.

The bundle override the translator service and provide a DatabaseLoader.
Database translations content is loaded last so it override content from xliff, yml and php translations files.
You can also export translations from the database in to files in case of you need to get translations files with the same content as the database.

