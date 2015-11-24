<?php  

/**
 * Habbo API
 *
 * Based upon original code by:
 *
 * Copyright (c) 2014 Kedi Agbogre (me@kediagbogre.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

define('IN_API', true);

chdir(dirname(__DIR__));

require('autoload.php');

$config = require('configuration.php');

$api = '/api/v' . $config->api['version'][0];

$database = new Database($config);

Auth::session();

/* *
 *  {$api}/info
 *
 *  Displays generic info about this particular API.
 */

Router::get("{$api}/info", function() use($config) {
	echo Template::get('api_info.html', array(
		'version' => $config->api['version'],
		'date' => date('d-m-Y h:i:s', $config->api['build']),
		'users' => 1200
	));
});

/* *
 *  {$api}/auth/login
 *
 *  Attempts to authenticate the user.
 */

Router::post("{$api}/auth/login", function() use($database) {
	try {
		$s = Post::populate(array(
			'username' => -1,
			'password' => -1
		));

		if($s['username'] == -1 || $s['password'] == -1) 
			throw new Exception('Bad Request');

		if($s['username'] == "" || $s['password'] == "")
			throw new Exception('Please fill in all fields');

		$authAttempt = Auth::attempt($s, $database);

		if(!$authAttempt) 
			throw new Exception('Incorrect email or password');

		echo Response::json(array(
			'response' => true,
			'userdata' => array(
				'id' => $authAttempt['id'],
				'username' => $authAttempt['username']
			)
		));
	} catch(Exception $e) {
		Auth::destroy();

		Response::status(401);

		echo Response::json(array(
			'response' => false,
			'error' => $e->getMessage()
		));
	};
});

/* *
 *  {$api}/auth/check
 *
 *  Checks if the user is authenticated.
 */

Router::get("{$api}/auth/check", function() use($database) {
	try {
		if(!Auth::check($database))
			throw new Exception('User is not logged in');

		echo Response::json(array(
			'authstate' => true,
			'userdata' => array(
				'username' => Auth::get('username'),
				'id' => Auth::get('userid'),
				'motto' => Auth::get('usermotto'),
				'credits' => Auth::get('usercredits'),
				'look' => Auth::get('userlook')
			) 
		));
	} catch(Exception $e) {
		echo Response::json(array('authstate' => false));
	};
});

/* *
 *  {$api}/auth/user/create
 *
 *  Creates a brand new user.
 */

Router::post("{$api}/user/create", function() use($database) {
	try {
		$s = Post::populate(array(
			'username' => -1,
			'password' => -1,
			'mail' => -1
		));

		if($s['username'] == -1 || $s['password'] == -1 || $s['mail'] == -1)
			throw new Exception('What are you trying to do?');

		if($s['username'] == '' || $s['password'] == '' || $s['mail'] == '')
			throw new Exception('Please fill in all fields');

		if(!filter_var($s['mail'], FILTER_VALIDATE_EMAIL)) 
		    throw new Exception('That email address is not valid.');

		if(!preg_match('/^(?=.*[a-zA-Z]{1,})(?=.*[\d]{0,})[a-zA-Z0-9]{3,15}$/', $s['username']))
			throw new Exception('That username is not valid.');

		if(strlen($s['password']) < 6)
			throw new Exception('Choose a password of at least 6 characters');

		$stmt = $database->connection->prepare(
			'SELECT null FROM users WHERE mail = ?'
		);
		$stmt->execute([ $s['mail'] ]);

		$mailCheck = $stmt->fetchAll();		

		if($mailCheck)
			throw new Exception('That email address is currently in use');

		$stmt = $database->connection->prepare(
			'SELECT null FROM users WHERE username = ?'
		);
		$stmt->execute([ $s['username'] ]);

		$usernameCheck = $stmt->fetchAll();

		if($usernameCheck)
			throw new Exception('That username is currently in use');

		$hash = Auth::hash($s['password']);

		$stmt = $database->connection->prepare(
			'INSERT INTO users (username, mail, password) VALUES (?, ?, ?)'
		);
		$stmt->execute([ $s['username'], $s['mail'], $hash ]);

		Auth::create(
			$database->connection->lastInsertId(), $s['username'], $hash
		);

		echo Response::json(array(
			'response' => true
		));
	} catch(Exception $e) {
		Response::status(401);
		echo Response::json(array(
			'response' => false,
			'error' => $e->getMessage()
		));
	};
});

/* *
 *  {$api}/auth/logout
 *
 *  Creates a brand new user.
 */

Router::get("{$api}/auth/logout", function() {
	Auth::destroy();
});

/* *
 *  {$api}/locale
 *
 *  Gets all locale variables.
 */

Router::get("{$api}/locale", function() use($config) {
	echo Response::json(require('locale/' . $config->site['locale'] . '.php'));
});

/* *
 *  {$api}/user/edit/motto
 *
 *  Edits the current user's motto.
 */

Router::post("{$api}/user/edit/motto", function() use($database) {
	try {
		$s = Post::populate(array(
			'motto' => -1
		));
		
		if(!Auth::check($database))
			throw new Exception('You are not logged in!');

		if($s['motto'] == -1)
			throw new Exception('Unknown error ocurred');

		if(strlen($s['motto']) > 40) 
			throw new Exception('Motto is too long');

		if($s['motto'] == '') 
			$s['motto'] = 'I love Habbo Hotel';

		if(strip_tags($s['motto']) !== $s['motto'])
			throw new Exception('Please use permitted characters');

		$stmt = $database->connection->prepare(
			'UPDATE users SET motto = ? WHERE id = ?'
		);
		$stmt->execute([ $s['motto'], Auth::get('userid') ]);
		echo Response::json(array('response' => true, 'newmotto' => $s['motto']));

	} catch(Exception $e) {
		Response::status(400);
		echo Response::json(array('response' => false, 'error' => $e->getMessage()));
	};
});

/* *
 *  {$api}/articles
 *
 *  Fetches the article beans.
 */

Router::get("{$api}/articles", function() use($database) {
	$stmt = $database->connection->prepare(
		'SELECT * FROM cms_news LIMIT 5'
	);
	$stmt->execute();
	echo Response::json($stmt->fetchAll());
});

/* *
 *  Global Route
 *
 *  Displays the default AngularJS view.
 */

Router::get("*", function() use($api) {
	echo Template::get('index.html', [ 'api' => $api ]);
});

Router::execute(Request::param('uri'));

