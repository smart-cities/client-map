<?php
/**
 * String utilities class
 * version 1.0
 * @author Switch Systems Ltd
 * $id$
 *
 */
class SW_String
{
	/**
	 *
	 * Replaces instances of $search (single character) with $replacmenets.
	 *
	 * Taken from http://uk2.php.net/manual/en/function.str-replace.php#73305
	 * Credit: rlee0001 at sbcglobal dot net
	 */
	public static function str_replace_many ($search, Array $replacements, $subject) {
			$index = strlen($subject);
			$replacements = array_reverse($replacements);

			if (count($replacements) != substr_count($subject, $search)) {
					return FALSE;
			}

			foreach ($replacements as $replacement) {
					$index = strrpos(substr($subject, 0, $index), $search);
					$prefix = substr($subject, 0, $index);
					$suffix = substr($subject, $index + 1);
					$subject = $prefix . $replacement . $suffix;
			}

			return $subject;
	}

}

class StringInvalidException extends Exception { }

?>