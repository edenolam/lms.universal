#################  CRON SCRIPTS  every-night-at-midnight ###################

0 0 * * * php /var/www/lms.universalmedica.com/bin/console lms:mail_formation_open7 >> /var/www/lms.universalmedica.com/var/log/mail_formation_open7.log

0 0 * * * php /var/www/lms.universalmedica.com/bin/console lms:mail_formation_open >> /var/www/lms.universalmedica.com/var/log/mail_formation_open.log

0 0 * * * php /var/www/lms.universalmedica.com/bin/console lms:mail_formation_relance >> /var/www/lms.universalmedica.com/var/log/mail_formation_relance.log


0 0 * * * php /var/www/lms.universalmedica.com/bin/console lms:stat_question >> /var/www/lms.universalmedica.com/var/log/stat_question.log
