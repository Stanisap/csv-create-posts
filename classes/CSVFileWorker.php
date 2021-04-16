<?php

/**
* A helper class for get data from CSV files.
*/
class CSVFileWorker {

	const DELIMITER = ",";


	/**
	 * @param string $filename
	 * @param string $mode
	 *
	 * @return false|resource
	 */
	public function fopen($filename,  $mode='r'){
		return fopen($filename, $mode);
	}

	/**
	 * @param resource $handle
	 * @param int|null $length
	 *
	 * @return array|false
	 */
	public function fgetcsv($handle, $length = 0) {
		return fgetcsv($handle, $length, self::DELIMITER);
	}

	/**
	 * @param resource $fp
	 *
	 * @return bool
	 */
	public function fclose($fp) {
		return fclose($fp);
	}

	/**
	 * @param  $obj
	 * @param array $array
	 * @param string $key
	 *
	 * @return false|mixed
	 */
	public function get_data($obj, &$array, $key) {
		if (!isset($obj->column_indexes) || !is_array($array) || count($array) == 0)
			return false;

		if (isset($obj->column_indexes[$key])) {
			$index = $obj->column_indexes[$key];
			if (isset($array[$index]) && !empty($array[$index])) {
				$value = $array[$index];
				unset($array[$index]);
				return $value;
			} elseif (isset($array[$index])) {
				unset($array[$index]);
			}
		}

		return false;
	}
}