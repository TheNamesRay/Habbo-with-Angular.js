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

var app = angular.module('Habbo', ['ngRoute', 'ngAnimate']);

app.config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
	$routeProvider.
		when('/', {
			templateUrl: 'partials/index.html',
			controller: 'IndexController',
			authRequirement: -1
		}).
		when('/registration', {
			templateUrl: 'partials/registration.html',
			controller: 'RegistrationController',
			authRequirement: -1
		}).
		when('/me', {
			templateUrl: 'partials/me.html',
			controller: 'MeController',
			authRequirement: 1
		}).
		when('/account', {
			templateUrl: 'partials/account.html',
			controller: 'AccountController',
			authRequirement: 1
		}).
		when('/logout', {
			controller: 'LogoutController',
			template: '',
			authRequirement: 1
		}).
		when('/client', {
			templateUrl: 'partials/client.html',
			controller: 'ClientController',
			authRequirement: 1
		}).
		when('/404', {
			templateUrl: 'partials/notfound.html',
			controller: 'NotfoundController',
			authRequirement: 0
		}).
		otherwise({
			redirectTo: '/404'
		});
	$locationProvider.html5Mode(true);
}]);