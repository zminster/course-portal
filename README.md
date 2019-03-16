Installation Notes
================== 
* Package dependencies: Node/NPM LTS Latest, PHP, MySQL, authbind, php-pear, php5-dev, pecl install stats
* Apache will run the PHP backend. VHost needs to be configured with ./backend as the DocumentRoot, and only expose to localhost. Access to the backend is possible only through VPN to server running system. NOTE: this is not a super-secure setup, since the frontend can send POST requests to the backend if compromised. In the future, a better solution should be developed.
* Add user www-data and instructor user to new group, "instructor" or something similar. Create the following dirs owned by www-data:instructor (the rest are handled by the software)
	* /course/csp/rubric/
	* /course/csp/handin/
* Configure node backend to be run by www-data:www-data as well. Node backend needs to be run with umask 0770 to create files/folders the backend (and instructor user) can access.
	* TODO: Debian init script in the build/ folder, apply umask 0770 and remove umask directive from server.js
* Monit needs to monitor apache, mysql, and the node service (see init script)
* Authbind configuration: add /etc/authbind/byport/443 /etc/authbind/byport/443
	* Change owner to www-data:instructor on both
	* Change perms to 544 on both

