<?php

/**
 * This is a simple parser for PO files. It also provides statistics about how many strings
 * have been translated, how many are fuzzy, etc.
 *
 * @author Laurent Cozic
 */

class PoParser {

	protected function parseMsgId($line, &$output) {
		$success = @ereg('msgid "(.*)"', $line, $result);
		if ($success === false) return false;
		$output = $result[1];
		return true;
	}


	protected function parseMsgStr($line, &$output) {
		$success = @ereg('msgstr "(.*)"', $line, $result);
		if ($success === false) return false;
		$output = $result[1];
		return true;
	}


	protected function parseString($line, &$output) {
		$success = @ereg('^"(.*)"', $line, $result);
		if ($success === false) return false;
		$output = $result[1];
		return true;
	}


	protected function parseFuzzy($line) {
		$success = @ereg('#, fuzzy', $line, $result);
		if ($success === false) return false;
		return true;
	}


	protected function parseObsolete($line) {
		return substr($line, 0, 2) == "#~";
	}


	protected function getLine($file) {
		return trim(fgets($file));
	}


	protected function parsePoFile($path) {
		$file = @fopen($path, 'r');
		if ($file === false) {
			throw new Exception("Cannot open ".$path);
			return;
		}

		$expect = "msgid";
		$parsingString = false;
		$currentIsFuzzy = false;

		$lineIndex = 0;

		$output = array();

		while (!feof($file)) {
			$line = $this->getLine($file);
			$lineIndex++;

			if ($line == "") continue;

			$isFuzzy = $this->parseFuzzy($line);
			if ($isFuzzy === true) {
				$currentIsFuzzy = true;
				continue;
			}

			if ($this->parseObsolete($line)) {
				$currentIsFuzzy = false;
				continue;
			}

			if (substr($line, 0, 1) == "#") continue;



			if ($expect == "msgid") {
				$success = $this->parseMsgId($line, $result);

				if (!$success) {
					throw new Exception("Error at line ".$lineIndex.": expecting msgid");
					return;
				}

				if ($result === false) $result = "";

				$currentObject = array(
					"id" => $result,
					"string" => "",
					"fuzzy" => $currentIsFuzzy
				);

				$currentIsFuzzy = false;

				while (!feof($file)) {
					$line = $this->getLine($file);
					$lineIndex++;

					$success = $this->parseString($line, $result);
					if ($success) {
						$currentObject["id"] = $currentObject["id"].$result;
						continue;
					} else {
						break;
					}
				}

				while (!feof($file)) {
					if ($line == "" || substr($line, 0, 1) == "#") {
						$line = $this->getLine($file);
						$lineIndex++;
					} else {
						break;
					}
				}

				$success = $this->parseMsgStr($line, $result);

				if (!$success) {
					throw new Exception("Error at line	".$lineIndex.": expecting msgstr");
					return;
				}

				$currentObject["string"] = $result;

				while (!feof($file)) {
					$line = $this->getLine($file);
					$lineIndex++;

					$success = $this->parseString($line, $result);
					if ($success) {
						$currentObject["string"] = $currentObject["string"].$result;
						continue;
					} else {
						break;
					}
				}

				$expect = "msgid";

				array_push($output, $currentObject);

				continue;
			}

		}

		fclose($file);

		return $output;
	}


	public function gettextStatus($poDoc) {
		$fuzzyCount = 0;
		$todoCount = 0;
		$totalCount = count($poDoc);

		for ($i = 0; $i < count($poDoc); $i++) {
			$o = $poDoc[$i];
			if ($o["id"] == "") continue;

			if ($o["fuzzy"]) {
				$fuzzyCount++;
			} else if ($o["string"] == "") {
				$todoCount++;
			}
		}

		return array(
			"fuzzy" => $fuzzyCount,
			"todo" => $todoCount,
			"total" => $totalCount);
	}


	public function getPercentageDone($poDoc) {
		$result = $this->gettextStatus($poDoc);
		$totalDone = $result["total"] - $result["fuzzy"] - $result["todo"];
		return $totalDone / $result["total"];
	}

}
