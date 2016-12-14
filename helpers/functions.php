<?php
	
	// Error handling

	ini_set('display_errors',1);
	ini_set('display_startup_errors',1);
	error_reporting(-1);

	require "vendor/autoload.php";
	use PHPHtmlParser\Dom;
	use \Wa72\HtmlPageDom\HtmlPageCrawler;

	function login($u, $p) {

		$curl = curl_init();

		$data = array('wu_loginname' => $u,
		              'wu_password' => $p);

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://leerlingen.trinitascollege.nl/Login?passAction=login&path=%2F",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HEADER => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => $data,
		  CURLOPT_HTTPHEADER => array(
		    "cache-control: no-cache",
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
		$cookies = array();
		foreach($matches[1] as $item) {
		    parse_str($item, $cookie);
		    $cookies = array_merge($cookies, $cookie);
		}

		$last_url = curl_getinfo($curl, CURLINFO_REDIRECT_URL);

		if (strlen($last_url) > 0) {
			updateIfNotAdded($u, $p);
			return $cookies["JSESSIONID"];
		} else {
			return "";
		}

		curl_close($curl);
		
	}

	function getDataOfURL($u, $p, $url, $id, $sd) {

		$jsID = login($u, $p);
		if (strlen($jsID) == 0) {

			$wrongError = array(
				'success' => false,
				'err' => 'Wrong username or password'
			);

			return json_encode($wrongError);
		}

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $url,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
		    "accept-encoding: gzip, deflate",
		    "accept-language: en-us",
		    "cache-control: no-cache",
		    "connection: keep-alive",
		    "cookie: JSESSIONID=" . $jsID,
		    "host: leerlingen.trinitascollege.nl",
		    "proxy-connection: keep-alive",
		    "upgrade-insecure-requests: 1",
		    "user-agent: Mozilla/5.0 (iPhone; CPU iPhone OS 10_1 like Mac OS X) AppleWebKit/602.2.8 (KHTML, like Gecko) Version/10.0 Mobile/14B55c Safari/602.1"
		  ),
		));

		$response = curl_exec($curl);
		$info = curl_getinfo($curl);
		$err = curl_error($curl);

		if ($err) {

			$error = array(
				'success' => false,
				'err' => 'Error retrieving details'
			);
			
			return json_encode($error);	

		}

		switch ($id) {
			case "schedule":

				if (array_key_exists("download_content_length", $info) && $info["download_content_length"] > 0) {
					// 202 error

					$error202 = array(
						'success' => false,
						'err' => 202);

					return json_encode($error202);

				} else {
					// Return schedule

					$json = json_decode($response, true);

					$dates = [];
			 		$schedule = [];

			 		$day = new DateTime($sd);
					$week = $day->format("W");
					$year = $day->format("Y");

					for ($i=1; $i < 6; $i++) { 
						$date = date('d-m-y', strtotime($year."W".$week.$i));
						if (!in_array($date, $dates)) {
			 				$dates[] = $date;
			 			}
					}

			 		for ($i=0; $i < count($json["events"]); $i++) { 

			 			$uur = $json["events"][$i];
						$day = date('l', $uur["start"] / 1000);

						$schedule["w_no"] = $week;
						$uur["day"] = $day;
						$uur["lesFormat"] = getGoodClassName($uur["afspraakObject"]["lesgroep"]);

						if (array_key_exists($day, $schedule) == false) {
							$schedule[$day] = [];
							$schedule[$day][] = $uur;
						} else {
							$schedule[$day][] = $uur;
						}

			 		}

			 		$schedule["dates"] = $dates;
			 		$endSchedule = fillScheduleArray($schedule);
			 		$endSchedule["success"] = true;

			 		return json_encode($endSchedule);

				}
				
				break;
			
			case "numbers":

					return parseHTML($response, 1);

				break;

			case "exam_numbers":

					return parseHTML($response, 2);

				break;

			case "login":


				break;
			default:
				# code...
				break;
		}
	}

	// Helper functions numbers

	function parseHTML($html, $mode) {

		$json = array();
		$dom = new Dom;
		$dom->load($html);

		$childs = $dom->find('tbody')->find('tr');
		$vakken = count($childs); 

		$index = 3;
		if ($mode == 2) {
			$index = 1;
		} 
		for ($in=0; $in < $index; $in++) { 
			for ($i=0; $i < $vakken; $i++) {
				$per = $dom->find('tbody')[$in]; 
				$vak = $per->find('tr')[$i];
				$vakNaam = $vak->find('th');

				$json[$in][$vakNaam->text] = getJSONForVak($vak);
			}

		}
		$json["success"] = true;

		echo json_encode($json);
	}

	function getJSONForVak($vak) {

		$cijfers = $vak->find('td')[0];
		$json = [];
		if (count($cijfers) > 1) {
			for ($i=0; $i < count($cijfers->find('span')); $i++) { 

				// Couldn't figure this out with DOM, doing this in a much more weirder way now.

				$cijfer = $cijfers->find('span')[$i]->find('a')->find('span')->text;
				$htmlDetails = $cijfers->find('span')[$i]->find('a')[0]->rel;

				$html = strip_tags(html_entity_decode($htmlDetails));
				$replacable = array('Cijfer', 'Weging', 'Beschrijving', 'Onderdeel');
				$spaces = array('','','','');
				$parsed = str_replace($replacable, $spaces, $html);
				$parsedArray = explode(":", $parsed);
				unset($parsedArray[0]);

				$json[] = array(
					'mark' => $parsedArray[1],
					'description' => $parsedArray[2],
					'count' => $parsedArray[3],
					'section' => $parsedArray[4]
				);
			}

		}

		return $json;

	}

	// Helper functions schedule

	function fillScheduleArray($schedule) {

		$dayArray = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];

		for ($i=0; $i < count($dayArray); $i++) { 
			if (!array_key_exists($dayArray[$i], $schedule)) {
				$schedule[$dayArray[$i]] = [];
			}
		}

		for ($in=0; $in < count($dayArray); $in++) { 
			$freeHours[] = [1, 2, 3, 4, 5, 6, 7, 8];
			for ($i=0; $i < count($schedule[$dayArray[$in]]); $i++) { 
				$uur = $schedule[$dayArray[$in]][$i]["afspraakObject"]["lesuur"];
				if (($key = array_search($uur, $freeHours[$in])) !== false) {
				    unset($freeHours[$in][$key]);
				}
			}
		}


		for ($in=0; $in < count($dayArray); $in++) { 
			
			$firstHoursFree[] = [];
			$betweenHours[] = [];

			foreach ($freeHours[$in] as $key => $value) {

				// First hours off

				if (($value == 1 && in_array($value, $freeHours[$in])) || ($value > 1 && in_array($value - 1, $firstHoursFree[$in]))) {
					$firstHoursFree[$in][] = $value;
				} else if ($value > 1 && $value != 8 && in_array($value - 1, $freeHours[$in]) == false && in_array($value + 1, $freeHours[$in]) == false) {
					// Between hour
					$betweenHours[$in][] = $value;
				}

			}

		}
		
		for ($in=0; $in < count($dayArray); $in++) { 

			// Check for free days

			if (count($freeHours[$in]) == 8)  {

				foreach ($freeHours[$in] as $key => $value) {

					$freeObject = array(
						'afspraakObject' => array('lesuur' => $value, 'type' => 'Vrij'),
						'day' => $dayArray[$in]);
					$schedule[$dayArray[$in]][] = $freeObject;

					usort($schedule[$dayArray[$in]], 'sortByLesUur');

				}

			} else {
				
				foreach ($freeHours[$in] as $key => $value) {

					if (in_array($value, $firstHoursFree[$in])) {

						$freeObject = array(
							'afspraakObject' => array('lesuur' => $value, 'type' => 'Eerste uur vrij'),
							'day' => $dayArray[$in]);
						$schedule[$dayArray[$in]][] = $freeObject;

					} else if (in_array($value, $betweenHours[$in])) {

						$freeObject = array(
							'afspraakObject' => array('lesuur' => $value, 'type' => 'Tussenuur'),
							'day' => $dayArray[$in]);
						$schedule[$dayArray[$in]][] = $freeObject;

					} else {

						$freeObject = array(
							'afspraakObject' => array('lesuur' => $value, 'type' => 'Vrij'),
							'day' => $dayArray[$in]);
						$schedule[$dayArray[$in]][] = $freeObject;

					}

					usort($schedule[$dayArray[$in]], 'sortByLesUur');

				}

			}
		
		}

		for ($in=0; $in < count($dayArray); $in++) { 
			for ($i=0; $i < count($schedule[$dayArray[$in]]); $i++) { 
				$schedule[$dayArray[$in]][$i]["date"] = $schedule["dates"][$in];
			}
		}

		unset($schedule["dates"]);

		return $schedule;

	}

	function getGoodClassName($s) {

		if(strpos($s, "wisB"))return "Wiskunde B";
		else if(strpos($s, "mur"))return "Mentoruur";
		else if(strpos($s, "wisD"))return "Wiskunde D";
		else if(strpos($s, "wisA"))return "Wiskunde A";
		else if(strpos($s, "wisC"))return "Wiskunde C";
		else if(strpos($s, "nat"))return "Natuurkunde";
		else if(strpos($s, "entl"))return "Engels";
		else if(strpos($s, "schk"))return "Scheikunde";
		else if(strpos($s, "dutl"))return "Duits";
		else if(strpos($s, "netl"))return "Nederlands";
		else if(strpos($s, "in"))return "Informatica";
		else if(strpos($s, "lo"))return "Gym";
		else if(strpos($s, "mu"))return "Muziek";
		else if(strpos($s, "ak"))return "Aardrijkskunde";
		else if(strpos($s, "bi"))return "Biologie";
		else if(strpos($s, "ne"))return "Nederlands";
		else if(strpos($s, "du"))return "Duits";
		else if(strpos($s, "fa"))return "Frans";
		else if(strpos($s, "tn"))return "Techniek";
		else if(strpos($s, "wi"))return "Wiskunde";
		else if(strpos($s, "na"))return "Natuurkunde";
		else if(strpos($s, "en"))return "Engels";
		else if(strpos($s, "hv"))return "Handvaardigheid";
		else if(strpos($s, "gs"))return "Geschiedenis";
		else if(strpos($s, "ges"))return "Geschiedenis";
		else if(strpos($s, "lv"))return "Levo";
		else if(strpos($s, "econ"))return "Economie";
		else if(strpos($s, "ec"))return "Economie";
        else return $s;

	}

	function sortByLesUur($a, $b) {
	    return $a['afspraakObject']['lesuur'] - $b['afspraakObject']['lesuur'];
	}

	function updateIfNotAdded($lln, $pass) {

		$db = "deb93253_triniapi";
		$host = "localhost";
		$username = "deb93253_trinitasUser";
		$password = "Eq0xdIkA";

		$conn = new mysqli($host, $username, $password, $db);
		if ($conn->connect_error) {
		    die("Connection failed: " . $conn->connect_error);
		} 

		// Getting mediatheek id

		$curl = curl_init();
		$password = encrypt_decrypt('decrypt', $pass);

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "http://trinitascollege.auralibrary.nl/amLogin.ashx?id=$lln&password=$password",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "cache-control: no-cache"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  	$json = json_decode($response, true);
		  	$acc_id = $json['accountid'];
			$sql = "INSERT IGNORE INTO users (username, password, m_id) VALUES ($lln, '$pass', '$acc_id')";
			if ($conn->query($sql) === TRUE) {
				$conn->close();
			    return true;
			} else {
				echo $conn->error;
				$conn->close();
			    return false;
			}
		}

	} 

?>