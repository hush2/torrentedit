<?php
/**
 * Bencoding encoder/decoder
 *
 * @version 1.0
 * @author Ruslan V. Us <unclerus at gmail.com>
 * @package BEncoder
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace BEncoder;

class BEncoderException extends \Exception {}

/**
 * Class for Bencoding encode/decode
 * Bencoding is a way to specify and organize data in a terse format.
 * It supports the following types: byte strings, integers,
 * lists, and dictionaries.
 */
class BEncoder
{
	/**
	 * Decoded data
	 * @var mixed
	 */
	public $source;
	/**
	 * Encoded data
	 * @var string
	 */
	public $encoded;

	private $_offset = 0;
	private $_len = 0;

	/**
	 * Bencode format decoding. Result of the decoding will be saved in the $source field.
	 * Some examples:
	 * <code>
	 * $bencoder = new bencoder_t;
	 * $bencoder->decode ('i12345e');
	 * print_r ($bencoder->source);
	 * </code>
	 * Or
	 * <code>
	 * $bencoder = new bencoder_t;
	 * $bencoder->encode (array ('fruit' => 'Banana', 'quantity' => 100));
	 * echo $bencoder->encoded;
	 * $bencoder->decode ();
	 * print_r ($bencoder->source);
	 * </code>
	 * @param string $str Encoded string. If omitted, contents of the $encoded field will be used
	 */
	public function decode ($str = null)
	{
		$this->source = array ();
		if (!is_null ($str)) $this->encoded = $str;

		$this->_offset = 0;
		$this->_len = strlen ($this->encoded);
		$this->source = $this->_decode ();
		return $this->source;
	}

	/**
	 * Encoding to Bencode format. Result of the encoding will be saved in the $encoded field.
	 * Some examples:
	 * <code>
	 * $bencoder = new bencoder_t;
	 * $bencoder->encode (array ('fruit' => 'Banana', 'quantity' => 100));
	 * echo $bencoder->encoded;
	 * </code>
	 * or
	 * <code>
	 * $bencoder = new bencoder_t;
	 * $bencoder->encode (array ('fruit' => 'Banana', 'quantity' => 100));
	 * $bencoder->source ['color'] = 'yellow';
	 * $bencoder->encode ();
	 * echo $bencoder->encoded;
	 * </code>
	 * @param mixed $str Data to encode. If omitted, contents of the $source field will be used
	 */
	public function encode ($value = null)
	{
		$this->encoded = '';
		if (!is_null ($value)) $this->source = $value;

		$this->encoded = $this->_encode ($this->source);
		return $this->encoded;
	}

	private function _decodeInt ()
	{
		$end = strpos ($this->encoded, 'e', $this->_offset);
		if ($end === false) throw new BEncoderException ('Decoding error at: ' . $this->_offset);
		$result = (double) substr ($this->encoded, $this->_offset + 1, $end - $this->_offset - 1);
		$this->_offset = $end + 1;
		return $result;
	}

	private function _decodeString ()
	{
		$divider = strpos ($this->encoded, ':', $this->_offset);
		if ($divider === false) throw new BEncoderException ('Decoding error at: ' . $this->_offset);
		$len = (int) substr ($this->encoded, $this->_offset, $divider - $this->_offset);
		$result = substr ($this->encoded, $divider + 1, $len);
		$this->_offset = $divider + $len + 1;
		return $result;
	}

	private function _decodeDictionary ()
	{
		$result = array ();
		$this->_offset ++;
		while ($this->encoded [$this->_offset] != 'e' && $this->_offset < $this->_len)
			$result [$this->_decodeString ()] = $this->_decode ();
		$this->_offset ++;
		return $result;
	}

	private function _decodeList ()
	{
		$result = array ();
		$this->_offset ++;
		while ($this->encoded [$this->_offset] != 'e' && $this->_offset < $this->_len)
			$result [] = $this->_decode ();
		$this->_offset ++;
		return $result;
	}

	private function _decode ()
	{
		switch ($this->encoded [$this->_offset])
		{
			case 'd':
				return $this->_decodeDictionary ();
			case 'i':
				return $this->_decodeInt ();
			case 'l':
				return $this->_decodeList ();
		}
		if (is_numeric ($this->encoded [$this->_offset])) return $this->_decodeString ();
		throw new BEncoderException ('Undefined data type at: ' . $this->_offset . ' while decoding');
	}

	private function _encodeString ($value)
	{
		return strlen ($value) . ':' . $value;
	}

	private function _encodeArray ($value)
	{
		$count = count ($value);
		$list = true;
		for ($i = 0; $i < $count; $i ++)
			if (!isset ($value [$i]))
			{
				$list = false;
				break;
			}
		//ksort ($value);
		return $list ? $this->_encodeList ($value) : $this->_encodeDictionary ($value);
	}

	private function _encodeList ($value)
	{
		$result = 'l';
		foreach ($value as $item)
			$result .= $this->_encode ($item);
		return $result . 'e';
	}

	private function _encodeDictionary ($value)
	{
		$result = 'd';
		foreach ($value as $key => $item)
			$result .= $this->_encodeString ($key) . $this->_encode ($item);
		return $result . 'e';
	}

	private function _encodeInt ($value)
	{
		return 'i' . ($value < 0 ? ceil ($value) : floor ($value)) . 'e';
	}

	private function _encode ($value)
	{
		switch (gettype ($value))
		{
			case 'NULL':
				return '0:';
			case 'string':
				return $this->_encodeString ($value);
			case 'array':
				return $this->_encodeArray ($value);
			case 'object':
				return $this->_encodeArray (get_object_vars ($value));
			case 'boolean':
				return $this->_encodeInt ((int) $value);
			case 'integer':
			case 'double':
			case 'float':
				return $this->_encodeInt ($value);
			default:
				throw new BEncoderException ('Unknown datatype while encoding');
		}
	}
}
?>
