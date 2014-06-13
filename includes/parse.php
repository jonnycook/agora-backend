<?php

require_once(__DIR__.'/productTypes.php');

$bnfInput = file_get_contents(__DIR__.'/../grammar');

$lines = explode("\n", $bnfInput);

// var_dump($lines);

// define('DEBUG', true);

function debug() { 
	return defined('DEBUG') && DEBUG;
}

class Match {
	public function __construct($name, $value) {
		$this->name = $name;
		$this->value = $value;
	}
}

class Element {
	public function prntSuccess() {
		if (debug()) echo "=> Success!\n";
	}
	public function name() { return null; }

	public function wrapMatch($match) {
		return new Match($this->name(), $match);
	}
}



class InputStream {
	public function __construct($string, $begin = 0, $end = null) {
		$this->string = $string;
		$this->position = $begin;
		$this->begin = $begin;
		$this->end = $end === null ? strlen($string) : $end;

		$this->str = $this->string();

	}

	public function skipWhiteSpace() {
		while (!$this->atEnd() && $this->string[$this->position] == ' ') ++ $this->position;
	}

	public function nextWord() {
		$this->skipWhiteSpace();
		if ($this->atEnd()) return false;
		while (!$this->atEnd() && $this->string[$this->position] != ' ') ++ $this->position;
		if ($this->atEnd()) return false;
		$this->skipWhiteSpace();
		if ($this->atEnd()) return false;

	}

	public function atEnd() {
		return $this->position >= $this->end;
	}

	public function test($string) {
		$this->skipWhiteSpace();

		$subject = $this->string();

		if (($str = substr($subject, 0, strlen($string))) == $string) {
			$this->position += strlen($string);
			return $str;
		}
		else {
			return false;
		}
	}

	public function nextChar() {
		return $this->string[$this->position++];
	}

	public function peek() {
		return $this->string[$this->position];
	}

	public function prnt() {
		if (debug()) echo '::', substr($this->string, $this->position), "\n";
	}

	public function string() {
		if ($this->position == 0 && $this->end == strlen($this->string)) {
			return $this->string;
		}
		else {
			return substr($this->string, $this->position, $this->end - $this->position);
		}

	}
}



class Expression {
	public function __construct($elements, $source=null) {
		$this->elements = $elements;
		$this->source = $source;
	}

	public function name() { return null; }

	public function match($stream) {
		if (debug()) if ($this->source) echo "--$this->source\n";
		$matches = array();
		$pos = $stream->position;
		foreach ($this->elements as $el) {
			$match = $el->match($stream);
			if ($match === false) {
				if (debug()) echo "-failed-$this->source\n"; 
				$stream->position = $pos;
				return false;
			}
			else {
				$matches[] = $match;
			}
		}


		return count($matches) == 1 ? $matches[0] : $matches;
	}

	public function matchAnywhere($stream, &$matchRange = null) {
		$startPosition = $stream->position;
		while (true) {
			if ($stream->atEnd()) {
				$stream->position = $startPosition;
				return false;
			}
			$position = $stream->position;
			if (($match = $this->match($stream)) === false) {
				$stream->nextWord();
			}
			else {
				$endPosition = $stream->position;
				$matchRange = array($position, $endPosition);
				// echo substr($stream->string, $position, $endPosition - $position), "\n";
				// var_dump($match);
				return $match;
			}
		}
	}

	public static function parseExpression($stream) {
		$elements = array();
		$startPos = $stream->position;
		while (true) {
			$el = $stream->next();

			if ($el === null || $el == ')' || $el == ']' || $el == '}') {
				return new Expression($elements, substr($stream->string, $startPos, $stream->position - $startPos));
			}
			else if ($el == '|') {
				$right = Expression::parseExpression($stream);
				return new OrExpression(new Expression($elements), $right, substr($stream->string, $startPos, $stream->position - $startPos));
			}
			else if ($el == '(') {
				$elements[] = Expression::parseExpression($stream);
			}
			else if ($el == '[') {
				$elements[] = new Optional(Expression::parseExpression($stream));
			}
			else if ($el == '{') {
				$elements[] = new Repeating(Expression::parseExpression($stream));
			}
			else {
				$elements[] = $el;
			}
		}
	}

}

class Symbol extends Element {
	public function __construct($value) {
		$this->value = $value;
	}

	public function name() { return $this->value; }


	public function match($stream) {
		if (debug()) echo "/<$this->value>/\n";
		$stream->prnt();
		global $definitions;
		$definition = $definitions[$this->value];
		if ($definition) {
			$match = $definition->match($stream);
			if ($match !== false) {
				$this->prntSuccess();
				return $this->wrapMatch($match);
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}
}

class String extends Element {
	public function __construct($value) {
		$this->value = trim($value);
	}

	public function name() { return $this->value; }

	public function match($stream) {
		if (debug()) echo "/\"$this->value\"/\n";
		$stream->prnt();
		$match = $stream->test($this->value);
		if ($match) {
			$this->prntSuccess();
			return $this->wrapMatch($match);
		}
		else return false;
	}
}

class OrExpression extends Expression {
	public function __construct($left, $right, $source = null) {
		$this->left = $left;
		$this->right = $right;
		$this->source = null;
	}

	public function match($stream) {
		if (debug()) if ($this->source) echo "--$this->source\n";
		$match = $this->left->match($stream);
		if ($match !== false) return $match;
		else return $this->right->match($stream);
	}
}

class Optional extends Element {
	public function __construct($expression) {
		$this->expression = $expression;
	}

	public function match($stream) {
		$match = $this->expression->match($stream);
		if ($match === false) return array();
		else return $match;
	}
}

class Repeating extends Element {
	public function __construct($expression) {
		$this->expression = $expression;
	}

	public function match($stream) {
		$matches = array();
		while (true) {
			$match = $this->expression->match($stream);
			if ($match === false) return $matches;
			$matches[] = $match;
		}
	}
}

class BNFExpressionStream {
	public function __construct($string) {
		$this->string = $string;
		$this->position = 0;
	}

	private function nextChar() {
		if (strlen($this->string) <= $this->position) return null;
		return $this->string[$this->position++];
	}

	private function peek() {
		return $this->string[$this->position];
	}

	private function rewind() {
		$this->position--;
	}

	public function next() {
		while ($this->peek() == ' ') $this->nextChar();

		$char = $this->nextChar();
		if ($char === null) return null;

		if (in_array($char, array('(', ')', '|', '[', ']', '{', '}'))) return $char;

		else {
			$string = '';
			if ($char == '<') {
				$terminal = '>';
			}
			else if ($char == '"') {
				$terminal = $char;
			}
			else {
				$terminal = array('"', '<', '|', '(', ')', '[', ']', '{', '}');
				$string .= $char;
			}

			
			while (true) {
				$c = $this->nextChar();
				// var_dump($c);

				if ($c === null) {
					break;
				}
				else if ($c == '\\') {
					$c = $this->nextChar();
				}
				else if (is_array($terminal) && in_array($c, $terminal)) {
					$this->rewind();
					break;
				}
				else if ($c == $terminal) {
					break;
				}

				$string .= $c;
			}


			if ($char == '"' || $char == '\'') return new String($string);
			else if ($char == '<') return new Symbol($string);
			else return new String($string);
		}
	}
}


$definitions = array();

foreach ($lines as $line) {
	$line = trim($line);
	if ($line[0] == '#') continue;
	$assignPos = strpos($line, '::=');
	if ($assignPos !== false) {
		$assignSymbol = substr(trim(substr($line, 0, $assignPos)), 1, -1);
		$assignExpression = trim(substr($line, $assignPos + 3));

		$expression = Expression::parseExpression(new BNFExpressionStream($assignExpression));

		$definitions[$assignSymbol] = $expression;

		// echo "<$assignSymbol> ::= $assignExpression\n";
		// var_dump($expression);
	}
}

class NumberSymbol {
	public function match($stream) {
		if (debug()) echo "/\\d/\n";
		$stream->skipWhiteSpace();

		$stream->prnt();
		$char = $stream->peek();

		if (is_numeric($char)) {
			$number = '';
			while (true) {
				$number .= $stream->nextChar();
				if (!is_numeric($stream->peek())) return $number;
			}
		}
		else {
			return false;
		}
	}
}

$definitions['number'] = new NumberSymbol;

function parseProperties($propertiesStr) {
	if ($propertiesStr) {
		$propertyParts = explode('and', $propertiesStr);
		$properties = array();
		foreach ($propertyParts as $part) {
			$p = explode(',', $part);
			foreach ($p as $prop) {
				$properties[] = trim($prop);
			}
		}
		return $properties;
	}
	else {
		return array();
	}
}

function fuzzyParseProduct($stream) {
	$string = $stream->string();
	// var_dump($stream);
	// var_dump($string);

	if (preg_match('/that (is|are)/', $string, $matches)) {
		$pos = strpos($string, $matches[0]);
		$product = trim(substr($string, 0, $pos));
		$propertiesPart = trim(substr($string, $pos + strlen($matches[0])));

		$properties = parseProperties($propertiesPart);

		$products = products();

		foreach ($products as $productType) {
			$pos = strpos($product, $productType);
			if ($pos !== false) {
				$properties = array_merge(parseProperties(trim(substr($product, 0, $pos))), $properties);;
				$product = trim(substr($product, $pos));
				break;
			}
		}

		$data = array(
			'type' => $product,
		);

		if ($properties) {
			$data['properties'] = $properties;
		}
	}
	else {
		$products = products($singulars);
		$product = trim($string);
		foreach ($products as $productType) {
			$pos = strpos($product, $productType);
			if ($pos !== false) {
				$properties = parseProperties(trim(substr($product, 0, $pos)));

				// if ($pos + strlen($productType) == strlen($product)) {
				// 	$product = $singulars[$productType];
				// }
				// else {
					$product = trim(substr($product, $pos));	
				// }
				break;
			}
		}


		$data = array('type' => $product);
		if ($properties) $data['properties'] = $properties;
	}

	return $data;
}

function fuzzyParseProductInfo($stream) {
	$string = $stream->string();
	// var_dump($string);


	$forPos = strpos($string, ' for ');
	if ($forPos === false) {
		if (substr($string, 0, 4) == 'for ') {
			$forPos = 0;
			$forLen = 4;
		}
	}
	else {
		$forLen = 5;
	}


	if ($forPos !== false) {
		$match['product'] = fuzzyParseProduct(new InputStream($stream->string, $stream->position, $stream->position + $forPos));

		$start = $forPos + $forLen;
		$purpose = trim(substr($string, $start));

		$inPos = strpos($purpose, ' in ');

		if ($inPos !== false) {
			$match['purpose'] = /*array($stream->position + $start, */trim(substr($purpose, 0, $inPos))/*)*/;
			$match['context'] = /*array($stream->position + $start + $inPos + 4, */trim(substr($purpose, $inPos + 4))/*)*/;
		}
		else {
			$match['purpose'] = /*array($stream->position + $start, */$purpose/*)*/;
		}

		return $match;
	}
	else {
		return array('product' => fuzzyParseProduct($stream));
	}
}


function subStreams($stream, $matchedRanges) {
	if ($matchedRanges) {
		usort($matchedRanges, function($a, $b) { return $a[0] - $b[0]; });
		// var_dump($matchedRanges);
		$subStreams = array();
		for ($i = 0; $i < count($matchedRanges); ++ $i) {
			if ($i == 0) $start = 0;
			else {
				$start = $matchedRanges[$i - 1][1];
			}

			$end = $matchedRanges[$i][0];
			if ($start != $end)
				$subStreams[] = new InputStream($stream->string, $start, $end);
		}

		$last = $matchedRanges[count($matchedRanges) - 1];
		// var_dump($last);
		if ($last[1] < strlen($stream->string)) {
			$subStreams[] = new InputStream($stream->string, $last[1]);
		}

		return $subStreams;
	}
	else {
		return array($stream);
	}
}


function sex($relationship) {
	switch ($relationship) {
		case 'niece':
		case 'mother': case 'mom':
		case 'sister':
		case 'wife':
		case 'aunt':
		case 'daughter':
		case 'girlfriend':
		case 'grandmother':
		case 'her':
			return 'female';

		case 'nephew':
		case 'father': case 'dad':
		case 'brother':
		case 'husband':
		case 'uncle':
		case 'son':
		case 'boyfriend':
		case 'grandfather':
		case 'him':
			return 'male';
	}
}

function interpret($symbol, $match) {
	switch ($symbol) {
		case 'full occasion expression':

			return interpret('occasion expression', $match[1]->value);

		case 'for person':
			return array('person' => interpret('person expression', $match[1]->value));

		case 'full skill expression':
			return array('skill level' => $match[1]->value[0]->name);

		case 'occasion expression':
			// var_dump($match);
			if (is_array($match)) {
				if (is_array($match[1]->value)) {
					if ($match[1]->value[1]->value == 'birthday') {
						$age = $match[1]->value[0][0]->value;
					}
					$data['occasion'] = $match[1]->value[1]->value;
				}
				else {
					$data['occasion'] = $match[1]->value->value;
				}

				$data['person'] = interpret('person expression:possessive', $match[0]->value);
				if ($age && !$data['person']['age']) $data['person']['age'] = $age - 1;
			}
			else {
				if (is_array($match->value)) {
					if ($match->value[1]->value == 'birthday') {
						// $data['person']['age'] = $match->value[0][0]->value;
					}
					$data['occasion'] = $match->value[1]->value;
				}
				else {
					$data['occasion'] = $match->value->value;
				}

			}
			return $data;

		case 'full gift expression':
			// var_dump($match);
			if (is_array($match[0]->value) && $match[0]->value[2]->name == 'occasion expression') {
				return interpret('occasion expression', $match[0]->value[2]->value);
			}
			else {
				$data = array();
				// var_dump($match);
				if ($match[0]->name == 'gift expression') {
					$data += interpret('gift expression', $match[0]->value);
				}

				if ($match[1]->name == 'for person') {
					$data += interpret('for person', $match[1]->value);
				}
				return $data;
			}


		case 'gift expression':
			if (is_array($match)) {
				if ($match[0]->name == 'occasion expression') {
					return interpret('occasion expression', $match[0]->value);
				}
				else if ($match[2]->name == 'occasion expression') {
					return interpret('occasion expression', $match[2]->value);
				}
			}
			else {
				return interpret('occasion expression', $match->value);
			}


		case 'person expression:possessive':
			// var_dump($match);
		// case 'person expression':


			if ($match[1][0]->name == 'age') {
				$data = array();
				$data['age'] = $match[1][0]->value[0]->value;
				$data += interpret('sub person expression', $match[1][1]);
				return $data;
			}

			else {
				return interpret('sub person expression', $match[1][0]->value);
			}

			return interpret('sub person expression', $match[1][0]->value);
			// return array(
			// 	'relationship' => 
			// );
			return null;

		case 'person expression':
			if (is_array($match[1]) && $match[1][0]->name == 'age') {
				$data = array();
				$data['age'] = $match[1][0]->value[0]->value;
				$data += interpret('sub person expression', $match[1][1]);
				return $data;
			}

			else {
				return interpret('sub person expression', $match[1]->value);
			}

			// return array(
			// 	'relationship' => 
			// );
			return null;

		case 'sub person expression':
			return interpret($match->name, $match->value);

		case 'grandparent expression':
			$greats = array();
			foreach ($match[0] as $great) {
				$greats[] = $great->name;
			}
			$data = array('relationship' => trim(implode(' ', $greats) . ' ' . $match[1]->value->name));

			$data['sex'] = sex($match[1]->value->name);
			return $data;

		case 'relative':
			// var_dump($match);
			$data = interpret($match->name, $match->value);

			if (!$data) {
				if (is_string($match->value)) $data = array('relationship' => $match->name);
				else if (is_string($match->value->name)) $data = array('relationship' => $match->value->name);
			}

			return $data;

		case 'sibling expression':
			// var_dump($match);
			if (is_array($match)) {
				$data = array('relationship' => $match[0]->value->name . ' ' . $match[1]->value->name);
			$data['sex'] = sex($match[1]->value->name);

			}

			else { 
				$data = array('relationship' => $match->value->name);
			$data['sex'] = sex($match->value->name);

			}

			// var_dump($data);
			return $data;

		case 'person relationship':
			// var_dump($match);
			if (is_array($match)) {
				$inLaw = true;
				$data = interpret('relative', $match[0]->value);
			}
			else if (is_string($match->value)) $data = array('relationship' => $match->name);
			else if ($match->name == 'relative') $data = interpret('relative', $match->value);
			else $data = array('relationship' => $match->value->name);

			if (!$data['sex']) $data['sex'] = sex($data['relationship']);

			if ($inLaw) $data['relationship'] .= '-in-law';

			return $data;
	}
}


function parse($descriptor) {
	global $definitions;
	global $mysqli;
	$stream = new InputStream($descriptor);
	$data = array();
	foreach (array('full gift expression', 'full occasion expression', 'for person') as $symbol) {
		if (($match = $definitions[$symbol]->matchAnywhere($stream, $range)) !== false) {
		// var_dump($match);

			// echo substr($stream->string, $range[0], $range[1] - $range[0]), "\n";
			$data = interpret($symbol, $match);
			break;
		}
	}

	if ($match) {
		// var_dump($data);
		$matchedRanges[] = $range;
	}

	// if ($match) {
	// 	$stream = new InputStream($stream->string, 0, $range[0]);
	// 	// var_dump($stream);
	// }

	// var_dump(subStreams());

	foreach (subStreams($stream, $matchedRanges) as $subStream) {
		if ($match = $definitions['full skill expression']->matchAnywhere($subStream, $range)) {
			// var_dump($match);
			$data += interpret('full skill expression', $match);
			// echo substr($stream->string, $range[0], $range[1] - $range[0]), "\n";
			// $stream = new InputStream($stream->string, 0, $range[0]);
			$matchedRanges[] = $range;
		}
		else {
			// echo "no match\n";
		}
	}

	// var_dump(subStreams());

	foreach (subStreams($stream, $matchedRanges) as $subStream) {
		// var_dump($subStream);
		$subStream->test('best');
		$subStream->test('good');

		$data += fuzzyParseProductInfo($subStream);
	}

	if ($data['purpose'] && !$data['person']) {
		$nameRecord = mysqli_fetch_row(mysqli_query($mysqli, "SELECT sex FROM names WHERE name = '$data[purpose]'"));
		if ($nameRecord) {
			$data['person'] = array(
				'name' => ucwords($data['purpose']),
				'sex' => $nameRecord[0] == 'f' ? 'female' : 'male'
			);
			unset($data['purpose']);
		}
	}

	if ($data['occasion']) {
		$data['occasion'] = ucwords($data['occasion']);
	}


	return $data;
}

// $stream = new InputStream('best winter boots that are nice, sturdy and warm for hiking in Alaska for husband\'s birthday');
// $stream = new InputStream('best camera for black-and-white photography for my sisters birthday');
// $stream = new InputStream('PS3 games for nephew');
// $stream = new InputStream('reusable water bottles that are easy to clean for my brothers 27st birthday for beginner');

