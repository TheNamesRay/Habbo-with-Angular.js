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

class Template {
	public static function get($s, $p = array()) {
		return self::_render($s, $p);
	}

	private static function _render($s, $p) {
		try {
		    ob_start();
		    if(file_exists('views/' . $s)) {
		    	readfile('views/' . $s);
		    } else {
		    	throw new Exception('View was not found');
		    };
		    $output = ob_get_contents();
		    ob_clean();
			$output = self::_filter($output, $p);
			$output = self::_minify($output);
			return $output;
		} catch(Exception $e) {
			die('API Template Renderer: ' . $e->getMessage());
		};
	}

	private static function _filter($buffer, $p) {
	    preg_match_all("#import\((.*?)\)#s", $buffer, $matches);
	    foreach ($matches[1] as $template) {
	        $buffer = file_exists('views/' . $template) ?
	        str_replace("import($template)", 
	            file_get_contents('views/' . $template), $buffer) :
	        str_replace("import($template)", '', $buffer);
	    };
	    foreach ($p as $k => $v) {
	        $buffer = str_replace("%$k%", $v, $buffer);
	    };
	    return preg_replace("/(?<!\d)%([^%]*)%\s*/", '', $buffer);
	}

	private static function _minify($buffer) {
		$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
	    $buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  '), '', $buffer);
	    $buffer = str_replace('{ ', '{', $buffer);
	    $buffer = str_replace(' }', '}', $buffer);
	    $buffer = str_replace('; ', ';', $buffer);
	    $buffer = str_replace(' {', '{', $buffer);
	    $buffer = str_replace('} ', '}', $buffer);
	    $buffer = str_replace(': ', ':', $buffer);
	    $buffer = str_replace(' ,', ',', $buffer);
	    $buffer = str_replace(' ;', ';', $buffer);
	    return $buffer;
	}
}