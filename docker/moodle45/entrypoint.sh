#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

cat > config.php <<PHP
<?php
unset(\$CFG);
global \$CFG;
\$CFG = new stdClass();

\$CFG->dbtype = 'mariadb';
\$CFG->dblibrary = 'native';
\$CFG->dbhost = '${MOODLE_DB_HOST}';
\$CFG->dbname = '${MOODLE_DB_NAME}';
\$CFG->dbuser = '${MOODLE_DB_USER}';
\$CFG->dbpass = '${MOODLE_DB_PASS}';
\$CFG->prefix = 'mdl_';
\$CFG->dboptions = [
    'dbpersist' => 0,
    'dbport' => '',
    'dbsocket' => '',
    'dbcollation' => 'utf8mb4_unicode_ci',
];

\$CFG->wwwroot = '${MOODLE_URL}';
\$CFG->dataroot = '/var/www/moodledata';
\$CFG->admin = 'admin';
\$CFG->directorypermissions = 0777;

require_once(__DIR__ . '/lib/setup.php');
PHP

until mysqladmin ping -h"${MOODLE_DB_HOST}" -u"${MOODLE_DB_USER}" -p"${MOODLE_DB_PASS}" --silent; do
    sleep 2
done

if [ ! -f /var/www/moodledata/.dutydesk-installed ]; then
    php admin/cli/install_database.php \
        --agree-license \
        --fullname="DutyDesk Moodle 4.5" \
        --shortname="DutyDesk" \
        --summary="Fresh Moodle 4.5 development site with local_dutydesk." \
        --adminuser="${MOODLE_ADMIN_USER}" \
        --adminpass="${MOODLE_ADMIN_PASS}" \
        --adminemail="${MOODLE_ADMIN_EMAIL}"

    touch /var/www/moodledata/.dutydesk-installed
else
    php admin/cli/upgrade.php --non-interactive --allow-unstable
fi

chown -R www-data:www-data /var/www/html /var/www/moodledata

exec "$@"
