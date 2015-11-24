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

class Auth {
	public static function attempt($s, $database) {
		try {
			$hash = self::hash($s['password']);
			$stmt = $database->connection->prepare(
				'SELECT id, credits, motto, look FROM users WHERE username = ? AND password = ? LIMIT 1'
			);
			$stmt->execute([ $s['username'], $hash ]);
			$result = $stmt->fetch();

			if($result) {
				self::create($result['id'], $s['username'], $hash);
				return array(
					'id' => $result['id'],
					'username' => $s['username']
				);
			} else {
				return false;
			};
		} catch(PDOException $e) {
			Error::write($e->getMessage());
		};
	}

	public static function get($s) {
		return isset($_SESSION['auth'][$s]) 
			? $_SESSION['auth'][$s] : -1;
	}
 
	public static function set($s, $v) {
		$_SESSION['auth'][$s] = $v;
	}

	public static function check($database) {
		if(self::get('userid') !== -1 && self::get('username') !== -1 && self::get('authcode') !== -1) {
			$stmt = $database->connection->prepare(
				'SELECT look, motto, credits, password FROM users WHERE username = ? AND id = ? LIMIT 1'
			);
			$stmt->execute([ self::get('username'), self::get('userid') ]);
			$result = $stmt->fetch();
			if($result) {
				$authcode = md5(
					$result['password'] . Request::ip() . getenv('HTTP_USER_AGENT')
				);
				self::set('usercredits', $result['credits']);
				self::set('userlook', $result['look']);
				self::set('usermotto', $result['motto']);
				return self::get('authcode') == $authcode;
			} else {
				return false;
			};
		} else {
			return false;
		};
	}

	public static function create($userid, $username, $hash) {
		self::set('authcode', md5(
			$hash . Request::ip() . getenv('HTTP_USER_AGENT')
		));
		self::set('userid', $userid);
		self::set('username', $username);
	}

	public static function session() {
		session_name('HabboAPI');
		session_start();
	}

	public static function destroy() {
		session_destroy();
		$_SESSION = array();
	}

	public static function hash($s) {
		return sha1($s);
	}
}