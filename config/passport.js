// config/passport.js

// load all the things we need
var LocalStrategy   = require('passport-local').Strategy;

// load up the user model
var bcrypt = require('bcrypt-nodejs');
var connection = require('../app/database_ops.js').connection;
// expose this function to our app using module.exports
module.exports = function(passport) {

    // =========================================================================
    // passport session setup ==================================================
    // =========================================================================
    // required for persistent login sessions
    // passport needs ability to serialize and unserialize users out of session

    // used to serialize the user for the session
    passport.serializeUser(function(user, done) {
        done(null, user.uid);
    });

    // used to deserialize the user
    // we want full set of information, so we do a JOIN query
    passport.deserializeUser(function(uid, done) {
        connection.query("SELECT user.uid, class_pd, username, password, change_flag, name, year, email FROM user INNER JOIN membership\
         ON user.uid = membership.uid INNER JOIN user_meta ON user.uid = user_meta.uid WHERE user.uid = ?",[uid], function(err, rows){
            done(err, rows[0]);
        });
    });

    // =========================================================================
    // LOCAL LOGIN =============================================================
    // =========================================================================

    passport.use(
        new LocalStrategy({
            passReqToCallback : true // allows us to pass back the entire request to the callback
        },
        function(req, username, password, done) { // callback with email and password from our form
            connection.query("SELECT * FROM user WHERE username = ?",[username], function(err, rows){
                if (err)
                    return done(err);
                else if (!rows.length)
                    return done(null, false, req.flash('loginMessage', 'Username does not exist.')); // req.flash is the way to set flashdata using connect-flash
                else if (!bcrypt.compareSync(password, rows[0].password))
                    return done(null, false, req.flash('loginMessage', 'Whoops! Wrong password.')); // create the loginMessage and save it to session as flashdata
                else
                    return done(null, rows[0]);
            });
        })
    );
};
