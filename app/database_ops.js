// universal DB options from .env
var options = {
	host: 		process.env.DB_HOST,
	port: 		process.env.DB_PORT,
	user:		process.env.DB_USER,
	password: 	process.env.DB_PASS,
	database: 	process.env.DB_NAME,
};

// general connection mgmt
var mysql 			= require("mysql");
var connection 		= mysql.createConnection(options);
//connection.query('USE ' + process.env.DB_NAME);

// session store mgmt
var session 		= require('express-session');
var mysqlStore		= require('express-mysql-session')(session);
var sessionStore 	= new mysqlStore({
	clearExpired: true,
	checkExpirationInterval: 10800000,
	expiration: 604800000,
	createDatabaseTable: true,
	schema: {
		tableName: 'user_session',
		columnNames: {
			session_id: 'id',
			expires: 'expires',
			data: 'data',
		}
	}}, connection);

// exports connection and session/sessionStore instances
module.exports = {
	connection: connection,
	session: session,
	sessionStore: sessionStore
};