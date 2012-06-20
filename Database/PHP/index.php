<?php
require 'lib/Airtimer.php';

$air = new AirTimer();

if(!isset($_GET['m'])){
	AirTimer::exception("Metodo 'm' deve ser informado.");
}

$method = $_GET['m'];

$air->$method($_GET);