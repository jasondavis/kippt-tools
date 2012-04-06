<?php
/**
 */


class kipptBackup {
	var $userName;
	var $apiToken;

	var $dbName;
	var $dbUser;
	var $dbPass;

	public function setUsername($userName) {
		$this->userName = $userName;
	}

	public function setApiToken($apiToken) {
		$this->apiToken = $apiToken;
	}

	public function setDatabaseCredentials($dbName, $dbUser, $dbPass) {
		$this->dbName = $dbName;
		$this->dbUser = $dbUser;
		$this->dbPass = $dbPass;
	}

	public function createBackup() {
		$this->connectToDatabase();

		echo 'Trying with kipp.com User "' . $this->userName . '" and API-Token "' . $this->apiToken . '"';
		echo "\n\n";


		$ch = curl_init('https://kippt.com/api/clips/');

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				'X-Kippt-Username: ' . $this->userName,
				'X-Kippt-API-Token: ' . $this->apiToken
			)
		);

		$apiResult = curl_exec($ch);
		curl_close($ch);

		print_r($apiResult);

		// decode the JSON data
		$apiData = json_decode($apiResult);
		print_r($apiData);

		foreach ($apiData->objects as $clip) {
			$this->compareOrImportClip($clip);
		}


	}

	private function compareOrImportClip($clip) {
		echo 'checking clip with ID=' . $clip->id;

		$dbResult = mysql_query('SELECT id,updated FROM clips WHERE id=' . $clip->id);
		if (mysql_num_rows($dbResult) == 1) {
			echo "\n" . 'Clip exists already';

			$row = mysql_fetch_assoc($dbResult);
			if ($row['updated'] < $clip->updated) {
				echo "\n" . 'Clip exists, but seems to have changed, trying to update...';
				$this->updateExistingClip($clip);
			}

		} else {
			echo "\n" . 'New clip, inserting';
			$this->insertNewClip($clip);
		}

		echo "\n\n";

	}

	private function updateExistingClip($clip) {
		mysql_query('DELETE from clips WHERE id=' . $clip->id . ' LIMIT 1;');

		$this->insertNewClip($clip);
	}

	private function insertNewClip($clip) {
		$importResult = mysql_query('INSERT into clips SET id="' . $clip->id .
				'", title="' . $clip->title .'"' .
				', url="' . $clip->url .'"' .
				', list="' . $clip->list .'"' .
				', notes="' . $clip->notes .'"' .
				', is_starred="' . (int)$clip->is_starred .'"' .
				', url_domain="' . $clip->url_domain .'"' .
				', created="' . $clip->created .'"' .
				', updated="' . $clip->updated .'"' .
				', resource_uri="' . $clip->resource_uri .'"'
		);

		if (!$importResult) {
			echo 'INSERT error: ' . mysql_error() . "\n\n";
		}
	}

	private function connectToDatabase() {
		mysql_connect(
			'localhost',
			$this->dbUser,
			$this->dbPass
		);
		mysql_select_db($this->dbName);
		mysql_query('SET NAMES utf8;');
	}
}


$backup = new kipptBackup();

// modify to suit your needs
$backup->setUsername('johndoe');
$backup->setApiToken('123456abcdef');
$backup->setDatabaseCredentials(
	'name of your local database',
	'database username',
	'database password'
);

$backup->createBackup();


?>