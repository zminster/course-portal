Installation Notes
================== 
* Software dependencies: Node/NPM 12, mariadb-server, apache2, php, libapache2-mod-php, php-pear, php-dev, pecl install stats-2.0.3, Composer
* Apache will run the PHP backend. VHost needs to be configured with ./backend as the DocumentRoot, and only expose to localhost. Example in ./build/
* Add Apache user www-data, new user course-portal, and instructor <actual user> to new group, "instructor" or something similar. Create the following dirs owned by <actual user>:instructor (the rest are handled by the software)
	* /course/csp/rubric/
	* /course/csp/handin/
* Configure node backend to be run by course-portal (example in ./build). Node backend needs to be run with umask 0770 to create files/folders the backend (and instructor user) can access.
	* Install system script in the build/ folder, apply umask 0770 and remove umask directive from server.js
* Monit configuration: monitor apache, mysql, and the node systemd service (see init script, example monit script in ./build)
* Authbind configuration: add /etc/authbind/byport/443 /etc/authbind/byport/443
	* Change owner to www-data:instructor on both
	* Change perms to 544 on both
* Apache configuration: edit /etc/apache2/envvars and set APACHE_RUN_GROUP=instructor

