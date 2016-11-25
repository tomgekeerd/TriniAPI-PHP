<?php

// Error handling
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);


// Requirements
require 'vendor/autoload.php';
include 'helpers/encryption.php';
include 'helpers/functions.php';

// Variables

$configuration = [
    'settings' => [
        'displayErrorDetails' => true,
    ],
];
$container = new \Slim\Container($configuration);
$app = new \Slim\App($container);

$v = "v1";

$db = "deb93253_triniapi";
$host = "localhost";
$username = "deb93253_trinitasUser";
$password = "Eq0xdIkA";

// Define app routes

// Login check
$app->post("/v1/login", function ($request, $response, $args) {

	// Get parameters
	$lln = $request->getParam('lln');
	$pass = $request->getParam('pass');
	$url = "";

	echo getDataOfURL($lln, $pass, $url, "numbers", "");
});

// Get schedule
$app->post("/v1/schedule", function ($request, $response, $args) {

	// Get parameters
	$lln = $request->getParam('lln');
	$pass = $request->getParam('pass');
	$sd = $request->getParam('sd');
	$url = "https://leerlingen.trinitascollege.nl/fs/SOMTools/Comps/Agenda.cfc?format=json&method=getLeerlingRooster&so_id=7227&startDate=" . $sd;

	// Return everything
	echo getDataOfURL($lln, $pass, $url, "schedule", $sd);
});

// Get exam numbers
$app->post("/v1/exam_numbers", function ($request, $response, $args) {

	// Get parameters
	$lln = $request->getParam('lln');
	$pass = $request->getParam('pass');
	$url = "https://leerlingen.trinitascollege.nl/Portaal/Persoonlijke_info/Examendossier?wis_ajax&ajax_object=7250&view=print";

	// Return everything
	echo getDataOfURL($lln, $pass, $url, "exam_numbers", "");
});

// Get numbers
$app->post("/v1/numbers", function ($request, $response, $args) {

	// Get parameters
	$lln = $request->getParam('lln');
	$pass = $request->getParam('pass');
	$url = "https://leerlingen.trinitascollege.nl/Portaal/Persoonlijke_info/Rapport_cijfers?wis_ajax&ajax_object=7249&view=print";

	// Return everything
	echo getDataOfURL($lln, $pass, $url, "numbers", "");
});

// Get mediatheek id
$app->post("/v1/m_id", function ($request, $response, $args) {

	// Params for db
	$db = "deb93253_triniapi";
	$host = "localhost";
	$username = "deb93253_trinitasUser";
	$password = "Eq0xdIkA";

	// Setup new connection
	$conn = new mysqli($host, $username, $password, $db);
	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	} 

	// Get input data
	$lln = $request->getParam('lln');
	$pass = encrypt_decrypt('encrypt', $request->getParam('pass'));

	$sql = "SELECT * FROM users WHERE username=$lln AND password='$pass'";
	$result = $conn->query($sql);

	// Check data from db
	if ($result->num_rows > 0) {
	    while($row = $result->fetch_assoc()) {
	    	$acc_id = $row['m_id'];

	  		$returnArray = array(
	  			'success' => true,
	  			'id' => "$acc_id");

	  		echo json_encode($returnArray);
	    }
	} else {
  		$returnArray = array(
  			'success' => false,
  			'msg' => "No records found.");

	  	echo json_encode($returnArray);
	 }

	$conn->close();
});

// Set parameters

$container['host'] = function ($c) { 
    return "localhost";
};

$container['db'] = function ($c) { 
    return "deb93253_triniapi";
};

$container['password'] = function ($c) { 
    return "Eq0xdIkA";
};

$container['username'] = function ($c) { 
    return "deb93253_trinitasUser";
};

// Run app


$app->run();



?>