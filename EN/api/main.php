<?php

date_default_timezone_set("Europe/Belgrade");

require "avail.php";
require 'fixes.php';

// Login and Database

function connectToDB()
{
  $server = "localhost";
  $serverUser = "cuwpvuip_korisnik";
  $serverPassword = "davincijevkod966";
  $database = "cuwpvuip_aplikacija";
  /*
  $server = "localhost";
  $serverUser = "otasyncm_korisnikU";
  $serverPassword = "CT*$,ULOqgb=";
  $database = "aplikacijaBeta";
  */

  $konekcija = new mysqli($server, $serverUser, $serverPassword, $database);
  if ($konekcija->connect_error) {
    http_response_code(503);
      die("Failed to connect to database.");
  }
  mysqli_set_charset($konekcija , "utf8mb4");
  return $konekcija;
}
function getSession($key, $account, $konekcija)
{
  $sql = "SELECT id FROM all_sessions WHERE pkey = '$key' LIMIT 1";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat->num_rows > 0){
    $cur_time = time();
    $sql = "UPDATE all_sessions SET last_action = $cur_time WHERE pkey = '$key'";
    mysqli_query($konekcija, $sql);
  }
  else {
    fatal_error("Session key invalid", 200);
  }
  $id = mysqli_fetch_assoc($rezultat);
  $id = $id["id"];
  $sql = "SELECT * FROM all_users WHERE id = $id";
  $rezultat = mysqli_query($konekcija, $sql);
  $user = mysqli_fetch_assoc($rezultat);
  return $user;
}
function getAction()
{
  if($_GET["url"] == "index.php" || $_GET["url"] == "")
    return "all";
  else
    return $_GET["url"];
}
function fatal_error($msg, $code)
{
  if(isset($GLOBALS['konekcija']))
    $GLOBALS['konekcija']->close();
  if(isset($GLOBALS['userToken']))
    makeReleaseRequest("release_token", array($GLOBALS['userToken']));
  $ret_val = [];
  $ret_val["status"] = $msg;
  if($code == 200)
    echo json_encode($ret_val);
  else {
    http_response_code($code);
    echo "$code: $msg";
  }
  die();
}

// Wubook Requests
function makeRequest($fName, $fArgs)
{
  $request = xmlrpc_encode_request($fName, $fArgs);

  $context = stream_context_create(array('http' => array(
      'method' => "POST",
      'header' => "Content-Type: text/xml",
      'content' => $request
  )));
  $file = file_get_contents("https://wired.wubook.net/xrws/", false, $context);
  $response = xmlrpc_decode($file);
  if ($response && xmlrpc_is_fault($response)) {
    fatal_error("Wubook request failed", 500);
  }
  else {
      if($response[0] !== 0)
        fatal_error("Wubook: " . $response[1] . " ($fName)" , 200);
      return $response[1];
  }
}
function makeUncheckedRequest($fName, $fArgs)
{
  $request = xmlrpc_encode_request($fName, $fArgs);
  $context = stream_context_create(array('http' => array(
      'method' => "POST",
      'header' => "Content-Type: text/xml",
      'content' => $request
  )));
  $file = file_get_contents("https://wired.wubook.net/xrws/", false, $context);
  $response = xmlrpc_decode($file);
  if($response && xmlrpc_is_fault($response))
    return array(-1, "Error");
  else
    return $response;
}
function makeReleaseRequest($fArgs)
{
  $request = xmlrpc_encode_request("release_token", array($fArgs));
  $context = stream_context_create(array('http' => array(
      'method' => "POST",
      'header' => "Content-Type: text/xml",
      'content' => $request
  )));
  file_get_contents("https://wired.wubook.net/xrws/", false, $context);
}

// Date / Time
function dateDiff($d1, $d2)
{
  $date1=date_create($d1);
  $date2=date_create($d2);
  $diff=date_diff($date1, $date2);
  return $diff->format("%a");
}
function dmyToYmd($d)
{
  $d = explode("/", $d);
  $d = "$d[2]-$d[1]-$d[0]";
  return $d;
}
function ymdToDmy($d)
{
  $d = explode("-", $d);
  $d = "$d[2]/$d[1]/$d[0]";
  return $d;
}
function cmpDates($date1, $date2) // Prvi manji vraca -1
{
  $date1_ar = explode("-", $date1);
  $d1 = intval($date1_ar[2]);
  $m1 = intval($date1_ar[1]);
  $y1 = intval($date1_ar[0]);
  $date2_ar = explode("-", $date2);
  $d2 = intval($date2_ar[2]);
  $m2 = intval($date2_ar[1]);
  $y2 = intval($date2_ar[0]);
  if($y1 < $y2)
    return -1;
  else if($y1 > $y2)
    return 1;
  else if($m1 < $m2)
    return -1;
  else if($m1 > $m2)
    return 1;
  else if($d1 < $d2)
    return -1;
  else if($d1 > $d2)
    return 1;
  else
    return 0;
}

// Post Data
if($_SERVER["CONTENT_TYPE"] == "application/json")
  $post_vars = json_decode(file_get_contents('php://input'));
function checkPost($param)
{
  if($_SERVER["CONTENT_TYPE"] == "application/json"){
    if(isset($GLOBALS['post_vars']->$param))
      return $GLOBALS['post_vars']->$param;
    else
      fatal_error("Missing $param", 400);
  }
  if(isset($_POST[$param]))
    return $_POST[$param];
  else
    fatal_error("Missing $param", 400);
}
function checkPostExists($param)
{
  if($_SERVER["CONTENT_TYPE"] == "application/json"){
    if(isset($GLOBALS['post_vars']->$param))
      return true;
    else
      return false;
  }
  if(isset($_POST[$param]))
    return true;
  else
    false;
}

// Misc
function to_array($obj) // Depth of 2, used by insert price for some reason?
{
  $ret = [];
  foreach($obj as $key => $value){
    $val = $value;
    if(is_object($value)){
      $val = [];
      foreach($value as $subkey => $subvalue){
        $val[$subkey] = $subvalue;
      }
    }
    $ret[$key] = $val;
  }
  return $ret;
}
function ancillary_recursive($data, $depth)
{
  if(is_array($data)){
    $ret = "";
    foreach($data as $key => $value){
      $ret .=  "<br>" . str_repeat("  ", $depth) . $key . ": " . ancillary_recursive($value, $depth + 1);
    }
  }
  else
    $ret = $data;
  return $ret;
}
function repairGuestData($id, $lcode, $konekcija)
{
  $sql = "SELECT SUM(nights) as total_nights, SUM(total_price) as total_paid, COUNT(*) as total_arrivals FROM reservations_$lcode WHERE status = 1 AND guest_ids = '$id' OR guest_ids LIKE '%,$id,%' OR guest_ids LIKE '%,$id' OR guest_ids LIKE '$id,%'";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $total_nights = $red["total_nights"];
  $total_paid = $red["total_paid"];
  $total_arrivals = $red["total_arrivals"];
  if($total_nights == NULL)
    $total_nights = 0;
  if($total_paid == NULL)
    $total_paid = 0;
  $sql = "UPDATE guests_$lcode SET total_nights = $total_nights, total_paid = $total_paid, total_arrivals = $total_arrivals WHERE id = $id";
  mysqli_query($konekcija, $sql);
}
function saveImage($post, $name, $location)
{
  $location = "/images/";
  try {
    if (empty($_FILES[$post])) {
        throw new Exception('Image file is missing');
    }
    $image = $_FILES[$post];
    // check INI error
    if ($image['error'] !== 0) {
        if ($image['error'] === 1)
            throw new Exception('Max upload size exceeded');

        throw new Exception('Image uploading error: INI Error');
    }
    // check if the file exists
    if (!file_exists($image['tmp_name']))
        throw new Exception('Image file is missing in the server');
    $maxFileSize = 2 * 10e6; // in bytes
    if ($image['size'] > $maxFileSize)
        throw new Exception('Max size limit exceeded');
    // check if uploaded file is an image
    $imageData = getimagesize($image['tmp_name']);
    if (!$imageData)
        throw new Exception('Invalid image');
    $mimeType = $imageData['mime'];
    // validate mime type
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mimeType, $allowedMimeTypes))
        throw new Exception('Only JPEG, PNG and GIFs are allowed');

    // nice! it's a valid image
    // get file extension (ex: jpg, png) not (.jpg)
    $fileExtention = strtolower(pathinfo($image['name'] ,PATHINFO_EXTENSION));
    // create random name for your image
    $fileName = $name . '.' . $fileExtention; // anyfilename.jpg
    // Create the path starting from DOCUMENT ROOT of your website
    $path = $location . $fileName;
    // file path in the computer - where to save it
    $destination = $_SERVER['DOCUMENT_ROOT'] . $path;
    if (!move_uploaded_file($image['tmp_name'], $destination))
        throw new Exception('Error in moving the uploaded file');

    // create the url
    $protocol = 'https://';
    $domain = $protocol . $_SERVER['SERVER_NAME'];
    $url = $domain . "/" . $path;
    return $url;
  }
  catch (Exception $e) {
    fatal_error("Failed to upload image", 200);
  }
}
function insertWubookGuest($lcode, $reservation, $konekcija)
{
  $name = mysqli_real_escape_string($konekcija, $reservation["customer_name"]);
  $surname = mysqli_real_escape_string($konekcija, $reservation["customer_surname"]);
  $email = mysqli_real_escape_string($konekcija, $reservation["customer_mail"]);
  $phone = mysqli_real_escape_string($konekcija, $reservation["customer_phone"]);
  $address = mysqli_real_escape_string($konekcija, $reservation["customer_address"]);
  $zip = mysqli_real_escape_string($konekcija, $reservation["customer_zip"]);
  $city = mysqli_real_escape_string($konekcija, $reservation["customer_city"]);
  $country = mysqli_real_escape_string($konekcija, $reservation["customer_country"]);
  $arrivals = 1;
  $amount = $reservation["amount"];
  $nights = dateDiff(dmyToYmd($reservation["date_arrival"]), dmyToYmd($reservation["date_departure"]));

  // Check if guest exists
  $sql = "SELECT * FROM guests_$lcode WHERE name = '$name' AND surname = '$surname' AND email = '$email'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat->num_rows > 0){ // Update old guest data
    $red = mysqli_fetch_assoc($rezultat);
    $customer_id = $red["id"];
    if($reservation["status"] == 1){
      $total_arrivals = (int)$red['total_arrivals'] + 1;
      $total_nights = (int)$red['total_nights'] + (int)$nights;
      $total_paid = (float)$red['total_paid'] + (float)$amount;
    }
    else {
      $total_arrivals = (int)$red['total_arrivals'] - 1;
      $total_nights = (int)$red['total_nights'] - (int)$nights;
      $total_paid = (float)$red['total_paid'] - (float)$amount;
    }
    $sql = "UPDATE guests_$lcode SET
    total_arrivals = $total_arrivals,
    total_nights = $total_nights,
    total_paid = $total_paid
    WHERE id = $customer_id";
    mysqli_query($konekcija, $sql);
  }
  else {
    if($reservation["status"] == 5){
      $arrivals = 0;
      $nights = 0;
      $amount = 0;
    }
    $sql = "INSERT INTO guests_$lcode (name, surname, email, phone, country_of_residence, place_of_residence, address, zip, country_of_birth, date_of_birth, gender, host_again, note, total_arrivals, total_nights, total_paid, registration_data, created_by) VALUES (
      '$name',
      '$surname',
      '$email',
      '$phone',
      '$country',
      '$city',
      '$address',
      '$zip',
      '$country',
      '0001-01-01',
      'M',
      1,
      '',
      $arrivals,
      $nights,
      $amount,
      '',
      'Wubook')";
    $rezultat = mysqli_query($konekcija, $sql);
    if(!$rezultat){
        $name = utf8_encode($name);
        $surname = utf8_encode($surname);
        $city = utf8_encode($city);
        $address = utf8_encode($address);
        $sql = "INSERT INTO guests_$lcode (name, surname, email, phone, country_of_residence, place_of_residence, address, zip, country_of_birth, date_of_birth, gender, host_again, note, total_arrivals, total_nights, total_paid, registration_data, created_by) VALUES (
      '$name',
      '$surname',
      '$email',
      '$phone',
      '$country',
      '$city',
      '$address',
      '$zip',
      '$country',
      '0001-01-01',
      'M',
      1,
      '',
      $arrivals,
      $nights,
      $amount,
      '',
      'Wubook')";
    $rezultat = mysqli_query($konekcija, $sql);
    if(!$rezultat){
      fatal_error("Database failed", 505);
    }
    }
    $customer_id = mysqli_insert_id($konekcija);
  }
  return $customer_id;
}
function insertWubookReservation($lcode, $account, $res)
{
  $new_konekcija = connectToDB();
  $rcode = $res["reservation_code"];
  $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$rcode'";
  $rezultat = mysqli_query($new_konekcija, $sql);
  $old_reservation = mysqli_fetch_assoc($rezultat);
  if($old_reservation && $old_reservation["status"] ==  1 && $res["status"] == 5){ // Deleted reservation
     $reservation_code = $rcode;
     $status = $res["status"];
     $modified_reservations = $res["modified_reservations"][0];
     $was_modified = $res["was_modified"];
     $date_canceled = explode(" ", $res['deleted_at_time']);
     $date_canceled = dmyToYmd($date_canceled[0]);
     $deleted_advance = isset($res['deleted_advance']) ? $res['deleted_advance'] : 0;

     $sql = "UPDATE reservations_$lcode SET
      status = $status,
      modified_reservation = '$modified_reservations',
      was_modified = $was_modified,
      deleted_advance = $deleted_advance,
      date_canceled = '$date_canceled'
      WHERE reservation_code = '$reservation_code'";
      mysqli_query($new_konekcija, $sql);
      $guests = explode(",", $old_reservation["guest_ids"]);
      for($i=0;$i<sizeof($guests);$i++){
        repairGuestData($guests[$i], $lcode);
      }

      // Updating avail in database and on WB (increase values on actual reseravation dates)
      $real_rooms_list = explode(",", $old_reservation["real_rooms"]);
      $date_arrival = $old_reservation["date_arrival"];
      $date_departure = $old_reservation["date_departure"];
      $avail_rooms = [];
      $avail_values = [];
      for($i=0;$i<sizeof($real_rooms_list);$i++){
        $room = $real_rooms_list[$i];
        if(in_array($room, $avail_rooms)){ // Add one to value if room already added
          $avail_values[$room] += 1;
        }
        else { // Push to array of rooms and set to one if room isn't already added
          array_push($avail_rooms, $room);
          $avail_values[$room] = 1;
        }
      }
      $date_obj = date_create($date_departure); // Reduce the departure date by 1
      date_add($date_obj, date_interval_create_from_date_string("-1 day"));
      $avail_date_departure = date_format($date_obj, "Y-m-d");
      plansAvailUpdate($lcode, $date_arrival, $avail_date_departure, $avail_values, $avail_rooms, 1, $new_konekcija);

      if(($res["rooms"] != $old_reservation["rooms"]) || ( $old_reservation["date_arrival"] != dmyToYmd($res["date_arrival"])) || ( $old_reservation["date_departure"] != dmyToYmd($res["date_departure"]))  ){ // Only do the wubook update if rooms were changed or dates
        wubookAvailUpdate($lcode, $account, $date_arrival, $avail_date_departure, $avail_rooms, $new_konekcija);

        // Updating avail WB (decrease values on wubook reservation dates)
        $rooms = explode(",", $res["rooms"]);
        $real_rooms = [];
        for($i=0;$i<sizeof($rooms);$i++){
          $room = $rooms[$i];
          $sql = "SELECT id, parent_room FROM rooms_$lcode WHERE id = '$room'";
          $rezultat = mysqli_query($new_konekcija, $sql);
          $room = mysqli_fetch_assoc($rezultat);
          if($room["parent_room"] == 0)
            array_push($real_rooms, $room["id"]);
          else
            array_push($real_rooms, $room["parent_room"]);
        }
        $real_rooms_list = $real_rooms;
        $date_arrival = dmyToYmd($res["date_arrival"]);
        $date_departure = dmyToYmd($res["date_departure"]);
        $avail_rooms = [];
        $avail_values = [];
        for($i=0;$i<sizeof($real_rooms_list);$i++){
          $room = $real_rooms_list[$i];
          if(in_array($room, $avail_rooms)){ // Add one to value if room already added
            $avail_values[$room] += 1;
          }
          else { // Push to array of rooms and set to one if room isn't already added
            array_push($avail_rooms, $room);
            $avail_values[$room] = 1;
          }
        }
        $date_obj = date_create($date_departure); // Reduce the departure date by 1
        date_add($date_obj, date_interval_create_from_date_string("-1 day"));
        $avail_date_departure = date_format($date_obj, "Y-m-d");
        wubookAvailUpdate($lcode, $account, $date_arrival, $avail_date_departure, $avail_rooms, $new_konekcija);
      }
  }
  else if($old_reservation == false){
      // Res Data
      $reservation = $res;
      $reservation_code = $reservation["reservation_code"];
      $status = $reservation["status"];
      $was_modified = $reservation["was_modified"];
      $modified_reservations = "";
      if(sizeof($reservation["modified_reservations"]))
        $modified_reservations = $reservation["modified_reservations"][0];

      $date_received_time = explode(" ", $reservation["date_received_time"]);
      $date_received = dmyToYmd($date_received_time[0]);
      $time_received = $date_received_time[1];
      $date_arrival = dmyToYmd($reservation["date_arrival"]);
      $date_departure = dmyToYmd($reservation["date_departure"]);
      $nights = dateDiff($date_arrival, $date_departure);

      // Rooms data
      $dayprices = $reservation["dayprices"];
      $rooms = $reservation["rooms"];
      $room_data = [];
      $real_rooms = [];

      $rooms_map = []; // Init map of used rooms
      $sql = "SELECT name, shortname, room_numbers, id, parent_room FROM rooms_$lcode WHERE id IN ($rooms)";
      $rezultat = mysqli_query($new_konekcija, $sql);
      while($red = mysqli_fetch_assoc($rezultat)){
        $rooms_map[$red["id"]] = [];
        $rooms_map[$red["id"]]["id"] = $red["id"];
        $rooms_map[$red["id"]]["name"] = $red["name"];
        $rooms_map[$red["id"]]["shortname"] = $red["shortname"];
        $rooms_map[$red["id"]]["count"] = 0;
        $rooms_map[$red["id"]]["parent_id"] = $red["id"];
        if($red["parent_room"] != '0'){
          $rooms_map[$red["id"]]["parent_id"] = $red["parent_room"];
        }
        $rooms_map[$red["id"]]["room_numbers"] = [];
      }
      $rooms = explode(",", $rooms);
      for($j=0;$j<sizeof($rooms);$j++){
        $room = $rooms[$j];
        array_push($real_rooms, $rooms_map[$room]["parent_id"]);
        $rooms_map[$room]["count"] += 1;
        $rooms_map[$room]["price"] = array_sum($dayprices[$room]) / sizeof($dayprices[$room]);
      }


      // Get room numbers
      $dfrom = $date_arrival;
      $dto = $date_departure;
      $occupied_rooms = []; // Init occupied rooms struct
      for($j=0;$j<sizeof($real_rooms);$j++){
        $occupied_rooms[$real_rooms[$i]] = [];
      }
      for($j=0;$j<sizeof($real_rooms);$j++){
        $occupied_rooms[$real_rooms[$j]] = []; // It's a map,, use isset to check if it's occupied
      }
      $sql = "SELECT real_rooms, room_numbers FROM reservations_$lcode WHERE date_arrival < '$dto' AND date_departure > '$dfrom' AND status = 1";
      $rezultat = mysqli_query($new_konekcija, $sql);
      while($red = mysqli_fetch_assoc($rezultat)){
        $res_rooms = explode(",", $red["real_rooms"]);
        $res_room_numbers = explode(",", $red["room_numbers"]);
        for($j=0;$j<sizeof($res_rooms);$j++){
          $occupied_rooms[$res_rooms[$j]][$res_room_numbers[$j]] = 1;
        }
      } // Occupied rooms done

      $room_numbers = [];
      for($j=0;$j<sizeof($real_rooms);$j++){ // Getting available rooms
        $room_id = $real_rooms[$j];
        $n=0;
        while(1){
          if(isset($occupied_rooms[$room_id][$n])){ // Room is occupied
            $n += 1;
          }
          else {
            array_push($room_numbers, $n);
            $occupied_rooms[$room_id][$n] = 1;
            array_push($rooms_map[$room_id]["room_numbers"], $n); // Remember room number used
            break;
          }
        }
      }
      foreach($rooms_map as $key => $values){
        array_push($room_data, $values);
      }
      $rooms = implode(",", $rooms);
      $real_rooms = implode(",", $real_rooms);
      $room_numbers = implode(",", $room_numbers);
      $room_data = json_encode($room_data);

      $men = $reservation["men"];
      $children = $reservation["children"];

      $guest_ids = insertWubookGuest($lcode, $reservation, $new_konekcija);
      $customer_name = mysqli_real_escape_string($new_konekcija, $reservation["customer_name"]);
      $customer_surname = mysqli_real_escape_string($new_konekcija, $reservation["customer_surname"]);
      $customer_mail = mysqli_real_escape_string($new_konekcija, $reservation["customer_mail"]);
      $customer_phone = mysqli_real_escape_string($new_konekcija, $reservation["customer_phone"]);
      $customer_country = mysqli_real_escape_string($new_konekcija, $reservation["customer_country"]);
      $customer_address = mysqli_real_escape_string($new_konekcija, $reservation["customer_address"]);
      $customer_zip = mysqli_real_escape_string($new_konekcija, $reservation["customer_zip"]);
      $note = mysqli_real_escape_string($new_konekcija, $reservation["customer_notes"]);

      $avans = $reservation['payment_gateway_fee'] != "" ? $reservation['payment_gateway_fee'] : 0;
      $payment_gateway_fee = [];
      $payment_gateway_fee["type"] = "fixed";
      $payment_gateway_fee["value"] = $avans;
      $payment_gateway_fee = json_encode($payment_gateway_fee);

      $reservation_price = $reservation["amount"];
      $services = "[]";
      $services_price = 0;
      $total_price = $reservation_price;
      $pricing_plan = "";
      $discount = [];
      $discount["type"] = "fixed";
      $discount["value"] = 0;
      $discount = json_encode($discount);
      $invoices = "[]";
      $cc_info = $reservation['cc_info'];
      $no_show = 0;
      $deleted_advance = isset($reservation['deleted_advance']) ? $reservation['deleted_advance'] : 0;
      $date_canceled = "0001-01-01";
      if(isset($reservation['deleted_at_time'])) {
        $date_canceled = explode(" ", $reservation['deleted_at_time']);
        $date_canceled = dmyToYmd($date_canceled[0]);
      }
      $addons_list = json_encode($reservation["addons_list"]);
      $id_woodoo = $reservation["id_woodoo"];
      $channel_reservation_code = $reservation["channel_reservation_code"];
      $additional_data = "{}";
      if(isset($reservation["ancillary"]))
        $additional_data = mysqli_real_escape_string($new_konekcija, json_encode($reservation["ancillary"]));
      $created_by = "Wubook";


      // Insert
      $sql = "INSERT INTO reservations_$lcode VALUES (
        '$reservation_code',
        $status,
        $was_modified,
        '$modified_reservations',
        '',
        '$date_received',
        '$time_received',
        '$date_arrival',
        '$date_departure',
        $nights,
        '$rooms',
        '$room_data',
        '$real_rooms',
        '$room_numbers',
        $men,
        $children,
        '$guest_ids',
        '$customer_name',
        '$customer_surname',
        '$customer_mail',
        '$customer_phone',
        '$customer_country',
        '$customer_address',
        '$customer_zip',
        '$note',
        '$payment_gateway_fee',
        $reservation_price,
        '$services',
        $services_price,
        $total_price,
        '$pricing_plan',
        '$discount',
        '$invoices',
        $cc_info,
        'waiting_arrival',
        '$date_canceled',
        $deleted_advance,
        '$addons_list',
        '$id_woodoo',
        '$channel_reservation_code',
        '$additional_data',
        'Wubook'
      )";
      $rezultat = mysqli_query($new_konekcija, $sql);
     if(!$rezultat){
         // Insert
     $customer_name = utf8_encode($customer_name);
     $customer_surname = utf8_encode($customer_surname);
     $customer_address = utf8_encode($customer_address);
     $note = utf8_encode($note);
     $sql = "INSERT INTO reservations_$lcode VALUES (
       '$reservation_code',
       $status,
       $was_modified,
       '$modified_reservations',
       '',
       '$date_received',
       '$time_received',
       '$date_arrival',
       '$date_departure',
       $nights,
       '$rooms',
       '$room_data',
       '$real_rooms',
       '$room_numbers',
       $men,
       $children,
       '$guest_ids',
       '$customer_name',
       '$customer_surname',
       '$customer_mail',
       '$customer_phone',
       '$customer_country',
       '$customer_address',
       '$customer_zip',
       '$note',
       '$payment_gateway_fee',
       $reservation_price,
       '$services',
       $services_price,
       $total_price,
       '$pricing_plan',
       '$discount',
       '$invoices',
       $cc_info,
       'waiting_arrival',
       '$date_canceled',
       $deleted_advance,
       '$addons_list',
       '$id_woodoo',
       '$channel_reservation_code',
       '$additional_data',
       'Wubook'
     )";
     mysqli_query($new_konekcija, $sql);
     }

      repairGuestData($guest_ids, $lcode);

      // Avail update for reservation rooms
      $avail_rooms = [];
      $avail_values = [];
      $real_rooms_list = explode(",", $real_rooms);
      for($i=0;$i<sizeof($real_rooms_list);$i++){
        $room = $real_rooms_list[$i];
        if(in_array($room, $avail_rooms)){
          $avail_values[$room] += 1;
        }
        else {
          array_push($avail_rooms, $room);
          $avail_values[$room] = 1;
        }
      }
      $date_obj =date_create($date_departure);
      date_add($date_obj, date_interval_create_from_date_string("-1 day"));
      $avail_date_departure = date_format($date_obj, "Y-m-d");
      plansAvailUpdate($lcode, $date_arrival, $avail_date_departure, $avail_values, $avail_rooms, -1, $new_konekcija);
  }
}
function sendReservationConfirmation($reservation_id, $lcode, $account, $konekcija)
{

  $sql = "SELECT notify_new_reservations, email FROM all_users WHERE account = '$account' AND status = 1";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  if($red["notify_new_reservations"] != "1")
    return;
  $sql = "SELECT email FROM all_properties WHERE lcode = '$lcode'";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $to_email = $red["email"];


  $iso_countries = json_decode("{\"--\":\"Ostale\",\"AF\":\"Afghanistan\",\"AX\":\"Aland Islands\",\"AL\":\"Albania\",\"DZ\":\"Algeria\",\"AS\":\"American Samoa\",\"AD\":\"Andorra\",\"AO\":\"Angola\",\"AI\":\"Anguilla\",\"AQ\":\"Antarctica\",\"AG\":\"Antigua And Barbuda\",\"AR\":\"Argentina\",\"AM\":\"Armenia\",\"AW\":\"Aruba\",\"AU\":\"Australia\",\"AT\":\"Austria\",\"AZ\":\"Azerbaijan\",\"BS\":\"Bahamas\",\"BH\":\"Bahrain\",\"BD\":\"Bangladesh\",\"BB\":\"Barbados\",\"BY\":\"Belarus\",\"BE\":\"Belgium\",\"BZ\":\"Belize\",\"BJ\":\"Benin\",\"BM\":\"Bermuda\",\"BT\":\"Bhutan\",\"BO\":\"Bolivia\",\"BA\":\"Bosnia And Herzegovina\",\"BW\":\"Botswana\",\"BV\":\"Bouvet Island\",\"BR\":\"Brazil\",\"IO\":\"British Indian Ocean Territory\",\"BN\":\"Brunei Darussalam\",\"BG\":\"Bulgaria\",\"BF\":\"Burkina Faso\",\"BI\":\"Burundi\",\"KH\":\"Cambodia\",\"CM\":\"Cameroon\",\"CA\":\"Canada\",\"CV\":\"Cape Verde\",\"KY\":\"Cayman Islands\",\"CF\":\"Central African Republic\",\"TD\":\"Chad\",\"CL\":\"Chile\",\"CN\":\"China\",\"CX\":\"Christmas Island\",\"CC\":\"Cocos (Keeling) Islands\",\"CO\":\"Colombia\",\"KM\":\"Comoros\",\"CG\":\"Congo\",\"CD\":\"Congo, Democratic Republic\",\"CK\":\"Cook Islands\",\"CR\":\"Costa Rica\",\"CI\":\"Cote D'Ivoire\",\"HR\":\"Croatia\",\"CU\":\"Cuba\",\"CY\":\"Cyprus\",\"CZ\":\"Czech Republic\",\"DK\":\"Denmark\",\"DJ\":\"Djibouti\",\"DM\":\"Dominica\",\"DO\":\"Dominican Republic\",\"EC\":\"Ecuador\",\"EG\":\"Egypt\",\"SV\":\"El Salvador\",\"GQ\":\"Equatorial Guinea\",\"ER\":\"Eritrea\",\"EE\":\"Estonia\",\"ET\":\"Ethiopia\",\"FK\":\"Falkland Islands (Malvinas)\",\"FO\":\"Faroe Islands\",\"FJ\":\"Fiji\",\"FI\":\"Finland\",\"FR\":\"France\",\"GF\":\"French Guiana\",\"PF\":\"French Polynesia\",\"TF\":\"French Southern Territories\",\"GA\":\"Gabon\",\"GM\":\"Gambia\",\"GE\":\"Georgia\",\"DE\":\"Germany\",\"GH\":\"Ghana\",\"GI\":\"Gibraltar\",\"GR\":\"Greece\",\"GL\":\"Greenland\",\"GD\":\"Grenada\",\"GP\":\"Guadeloupe\",\"GU\":\"Guam\",\"GT\":\"Guatemala\",\"GG\":\"Guernsey\",\"GN\":\"Guinea\",\"GW\":\"Guinea-Bissau\",\"GY\":\"Guyana\",\"HT\":\"Haiti\",\"HM\":\"Heard Island & Mcdonald Islands\",\"VA\":\"Holy See (Vatican City State)\",\"HN\":\"Honduras\",\"HK\":\"Hong Kong\",\"HU\":\"Hungary\",\"IS\":\"Iceland\",\"IN\":\"India\",\"ID\":\"Indonesia\",\"IR\":\"Iran, Islamic Republic Of\",\"IQ\":\"Iraq\",\"IE\":\"Ireland\",\"IM\":\"Isle Of Man\",\"IL\":\"Israel\",\"IT\":\"Italy\",\"JM\":\"Jamaica\",\"JP\":\"Japan\",\"JE\":\"Jersey\",\"JO\":\"Jordan\",\"KZ\":\"Kazakhstan\",\"KE\":\"Kenya\",\"KI\":\"Kiribati\",\"KR\":\"Korea\",\"KW\":\"Kuwait\",\"KG\":\"Kyrgyzstan\",\"LA\":\"Lao People's Democratic Republic\",\"LV\":\"Latvia\",\"LB\":\"Lebanon\",\"LS\":\"Lesotho\",\"LR\":\"Liberia\",\"LY\":\"Libyan Arab Jamahiriya\",\"LI\":\"Liechtenstein\",\"LT\":\"Lithuania\",\"LU\":\"Luxembourg\",\"MO\":\"Macao\",\"MK\":\"Macedonia\",\"MG\":\"Madagascar\",\"MW\":\"Malawi\",\"MY\":\"Malaysia\",\"MV\":\"Maldives\",\"ML\":\"Mali\",\"MT\":\"Malta\",\"MH\":\"Marshall Islands\",\"MQ\":\"Martinique\",\"MR\":\"Mauritania\",\"MU\":\"Mauritius\",\"YT\":\"Mayotte\",\"MX\":\"Mexico\",\"FM\":\"Micronesia, Federated States Of\",\"MD\":\"Moldova\",\"MC\":\"Monaco\",\"MN\":\"Mongolia\",\"ME\":\"Montenegro\",\"MS\":\"Montserrat\",\"MA\":\"Morocco\",\"MZ\":\"Mozambique\",\"MM\":\"Myanmar\",\"NA\":\"Namibia\",\"NR\":\"Nauru\",\"NP\":\"Nepal\",\"NL\":\"Netherlands\",\"AN\":\"Netherlands Antilles\",\"NC\":\"New Caledonia\",\"NZ\":\"New Zealand\",\"NI\":\"Nicaragua\",\"NE\":\"Niger\",\"NG\":\"Nigeria\",\"NU\":\"Niue\",\"NF\":\"Norfolk Island\",\"MP\":\"Northern Mariana Islands\",\"NO\":\"Norway\",\"OM\":\"Oman\",\"PK\":\"Pakistan\",\"PW\":\"Palau\",\"PS\":\"Palestinian Territory, Occupied\",\"PA\":\"Panama\",\"PG\":\"Papua New Guinea\",\"PY\":\"Paraguay\",\"PE\":\"Peru\",\"PH\":\"Philippines\",\"PN\":\"Pitcairn\",\"PL\":\"Poland\",\"PT\":\"Portugal\",\"PR\":\"Puerto Rico\",\"QA\":\"Qatar\",\"RE\":\"Reunion\",\"RO\":\"Romania\",\"RU\":\"Russian Federation\",\"RW\":\"Rwanda\",\"BL\":\"Saint Barthelemy\",\"SH\":\"Saint Helena\",\"KN\":\"Saint Kitts And Nevis\",\"LC\":\"Saint Lucia\",\"MF\":\"Saint Martin\",\"PM\":\"Saint Pierre And Miquelon\",\"VC\":\"Saint Vincent And Grenadines\",\"WS\":\"Samoa\",\"SM\":\"San Marino\",\"ST\":\"Sao Tome And Principe\",\"SA\":\"Saudi Arabia\",\"SN\":\"Senegal\",\"RS\":\"Serbia\",\"SC\":\"Seychelles\",\"SL\":\"Sierra Leone\",\"SG\":\"Singapore\",\"SK\":\"Slovakia\",\"SI\":\"Slovenia\",\"SB\":\"Solomon Islands\",\"SO\":\"Somalia\",\"ZA\":\"South Africa\",\"GS\":\"South Georgia And Sandwich Isl.\",\"ES\":\"Spain\",\"LK\":\"Sri Lanka\",\"SD\":\"Sudan\",\"SR\":\"Suriname\",\"SJ\":\"Svalbard And Jan Mayen\",\"SZ\":\"Swaziland\",\"SE\":\"Sweden\",\"CH\":\"Switzerland\",\"SY\":\"Syrian Arab Republic\",\"TW\":\"Taiwan\",\"TJ\":\"Tajikistan\",\"TZ\":\"Tanzania\",\"TH\":\"Thailand\",\"TL\":\"Timor-Leste\",\"TG\":\"Togo\",\"TK\":\"Tokelau\",\"TO\":\"Tonga\",\"TT\":\"Trinidad And Tobago\",\"TN\":\"Tunisia\",\"TR\":\"Turkey\",\"TM\":\"Turkmenistan\",\"TC\":\"Turks And Caicos Islands\",\"TV\":\"Tuvalu\",\"UG\":\"Uganda\",\"UA\":\"Ukraine\",\"AE\":\"United Arab Emirates\",\"GB\":\"United Kingdom\",\"US\":\"United States\",\"UM\":\"United States Outlying Islands\",\"UY\":\"Uruguay\",\"UZ\":\"Uzbekistan\",\"VU\":\"Vanuatu\",\"VE\":\"Venezuela\",\"VN\":\"Viet Nam\",\"VG\":\"Virgin Islands, British\",\"VI\":\"Virgin Islands, U.S.\",\"WF\":\"Wallis And Futuna\",\"EH\":\"Western Sahara\",\"YE\":\"Yemen\",\"ZM\":\"Zambia\",\"ZW\":\"Zimbabwe\"}"
  );

  $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$reservation_id'";
  $rezultat = mysqli_query($konekcija, $sql);
  $reservation = mysqli_fetch_assoc($rezultat);

  $sql = "SELECT name FROM all_properties WHERE lcode = $lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  $property_name = mysqli_fetch_assoc($rezultat);
  $property_name = $property_name["name"];

  $channel_names = [];
  $sql = "SELECT name, id FROM channels_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat))
  {
    $channel_names[$red["id"]] = $red["name"];
  }
  $res_id = $reservation["reservation_code"];
  $date_received = ymdToDmy($reservation["date_received"]);
  $nights = $reservation["nights"];
  $date_arrival = ymdToDmy($reservation["date_arrival"]);
  $date_departure = ymdToDmy($reservation["date_departure"]);
  $men = $reservation["men"];
  $children = $reservation["children"];
  $channel_name = $channel_names[$reservation["id_woodoo"]];
  $channel_id = $reservation["channel_reservation_code"];
  $guest = $reservation["customer_name"] . " " . $reservation["customer_surname"];
  $email = $reservation["customer_mail"];
  $phone = $reservation["customer_phone"];
  $address = $reservation["customer_address"];
  $zip = $reservation["customer_zip"];
  $city = $reservation["customer_city"];
  $note = $reservation["note"];
  $country = $reservation["customer_country"];
  if(isset($iso_countries->$country))
    $country = $iso_countries->$country;
  else
    $country = "";
  $rooms = json_decode($reservation["room_data"]);
  for($i=0;$i<sizeof($rooms);$i++){
    $room_price = $rooms[$i]->price;
    $room_name = $rooms[$i]->name;
    $count = $rooms[$i]->count;
    $rooms_html .= " <div class='row'> <div class='value value4'> $room_name ($count) = $room_price EUR  </div> </div>";
  }
  $addons_html = "";
  $addons = (array)json_decode($reservation["addons_list"]);
  $services = json_decode($reservation["services"]);
  if(sizeof($addons) || sizeof($services)){
    $addons_html .= "<div class='title'> DODATNE USLUGE: </div>";
  }

  for($i=0;$i<sizeof($addons);$i++){
    $name = $addons[$i]->name;
    $price = $addons[$i]->price;
    if($name == "City tax"){
      $total_guests = $men + $children;
      $name .= " ($total_guests)";
      $price *= $total_guests;
    }
    $addons_html .= "<div class='row'> <div class='value value4'> $name = $price EUR </div> </div>";
  }
  for($i=0;$i<sizeof($services);$i++){
    $name = $services[$i]->name;
    $price = $services[$i]->total_price;
    $addons_html .= "<div class='row'> <div class='value value4'> $name = $price EUR </div> </div>";
  }
  $price_with_services = "";
  if(sizeof($services)){
    $total_res_price = $reservation["total_price"] . " EUR";
    $price_with_services = "
    <div class='title'> UKUPNA CENA REZERVACIJE: </div>
    <div class='row'>
      <div class='value value4'> $total_res_price </div>
    </div>";
  }
  $status = $reservation["status"];
  if($status == 1){
    $status_title = "<div class='title confirmed'> NOVA REZERVACIJA</div>";
    $status_text = "<div class='value value2 confirmed'> Potvrđena </div>";
    $subject = "Nova rezervacija $property_name ($date_arrival, $res_id, $guest)";
  }
  else if($status == 5 && $reservation["was_modified"] == 1){
    $status_title = "<div class='title modified'> IZMJENJENA REZERVACIJA</div>";
    $status_text = "<div class='value value2 modified'> Izmenjena </div>";
    $subject = "Izmjenjena rezervacija $property_name ($date_arrival, $res_id, $guest";
  }
  else if($status == 5) {
    $status_title = "<div class='title canceled'> OTKAZANA REZERVACIJA </div>";
    $status_text = "<div class='value value2 canceled'> Otkazana </div>";
    $subject = "Otkazana rezervacija $property_name ($date_arrival, $res_id, $guest)";
  }
  else {
    return; // No status means reservation isn't fetched at all
  }
  $ancillary = $reservation["additional_data"];
  if($reservation["additional_data"] != "{}" && $reservation["additional_data"] != "[]"){
    $ancillary_html = ancillary_recursive($ancillary, 0);
    $ancillary_html = "
    <div class='section'>
      <div class='title'> INFORMACIJE SA KANALA PRODAJE: </div>
      <div class='value5'> $ancillary_html </div>
    </div>";
  }
  else {
    $ancillary_html = "";
  }
  $total_price = $reservation["reservation_price"] . " EUR";
  $message =
  "<!DOCTYPE html>
  <html lang='en' dir='ltr'>
    <head>

      <title></title>
      <style>
        * {
          font-family: Helvetica;
        }
        #container {
          width: 80%;
          max-width: 900px;
          margin: auto;
        }
        .section {
          width: 100%;
          margin-top: 40px;
          box-sizing: border-box;
          border: 1px solid #cecece;
        }
        .title {
          width: 100%;
          padding: 5px;
          text-align: center;
          vertical-align: middle;
          font-weight: bold;
          color: white;
          background-color: #aaaaaa;
          box-sizing: border-box;
        }
        .title.confirmed {
          background-color: #70ad1f;
        }
        .value.confirmed {
          color: #70ad1f;
        }
        .title.modified {
          background-color: #ed7c31;
        }
        .value.modified {
          color: #ed7c31;
        }
        .title.canceled {
          background-color: #cc0000;
        }
        .value.canceled {
          color: #cc0000;
        }
        .row {
          display: flex;
          width:100%;
          box-sizing: border-box;
        }
        .row:nth-child(even) {
          background: #dedede;
        }
        .label, .value {
          padding: 4px;
          width: 25%;
          border-left: 1px solid #cecece;
          border-bottom: 1px solid #cecece;
          box-sizing: border-box;
        }
        .label {
          padding-left: 10px;
        }
        .value {
          font-weight: bold;
        }
        .label:last-child, .value:last-child {
          border-right: 1px solid #cecece;
        }
        .value2 {
          width: 50%;
        }
        .value3 {
          width: 75%;
        }
        .value4 {
          width: 100%;
          text-align: center;
        }
        .value5 {
          width: 100%;
        }
      </style>
    </head>
    <body>
      <div id='container'>

        <div class='section'>
          $status_title
          <div class='row'>
            <div class='label value2'> OBJEKAT: </div>
            <div class='value value2'> $property_name </div>
          </div>
          <div class='row'>
            <div class='label value2'> LINK </div>
            <div class='value value2'> <a href='https://admin.otasync.me/?lcode=$lcode&id=$res_id'> https://admin.otasync.me/?lcode=$lcode&id=$res_id </a> </div>
          </div>
          <div class='row'>
            <div class='label value2'> STATUS </div>
            $status_text
          </div>
        </div>

        <div class='section'>
          <div class='title'> BROJ REZERVACIJE - $res_id</div>
          <div class='row'>
            <div class='label'> Datum rezervacije </div>
            <div class='value'> $date_received </div>
            <div class='label'> Broj noći </div>
            <div class='value'> $nights </div>
          </div>
          <div class='row'>
            <div class='label'> Datum dolaska </div>
            <div class='value'> $date_arrival </div>
            <div class='label'> Datum odlaska </div>
            <div class='value'> $date_departure </div>
          </div>
          <div class='row'>
            <div class='label'> Kanal prodaje </div>
            <div class='value'> $channel_name </div>
            <div class='label'> Odrasli </div>
            <div class='value'> $men </div>
          </div>
          <div class='row'>
            <div class='label'> Kod na kanalu prodaje </div>
            <div class='value'> $channel_id </div>
            <div class='label'> Deca </div>
            <div class='value'> $children </div>
          </div>
        </div>

        <div class='section'>
          <div class='title'> GOST: $guest</div>
          <div class='row'>
            <div class='label'> Email </div>
            <div class='value value2'> $email </div>
            <div class='value'> $country </div>
          </div>
          <div class='row'>
            <div class='label'> Telefon </div>
            <div class='value'> $phone </div>
            <div class='value'> $address $zip </div>
            <div class='value'> $city </div>
          </div>
          <div class='row'>
            <div class='label'> Napomena </div>
            <div class='value value3'> $note </div>
          </div>
        </div>

        <div class='section'>
          <div class='title'> JEDINICE: </div>
            $rooms_html

          <div class='title'> UKUPNA CENA SOBA: </div>
          <div class='row'>
            <div class='value value4'> $total_price </div>
          </div>

          $addons_html

          $price_with_services
        </div>

        $ancillary_html

      </div>
    </body>
  </html>";
  $headers = "MIME-Version: 1.0" . "\r\n";
  $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
  $headers .= "From: noreply@otasync.me" . "\r\n";
  $headers .= "Reply-To: $email" . "\r\n" ;
  $rez = mail($to_email, $subject, $message, $headers);
}
function sendGuestConfirmation($reservation_id, $lcode, $account, $konekcija)
{
  $iso_countries = json_decode("{\"--\":\"Ostale\",\"AF\":\"Afghanistan\",\"AX\":\"Aland Islands\",\"AL\":\"Albania\",\"DZ\":\"Algeria\",\"AS\":\"American Samoa\",\"AD\":\"Andorra\",\"AO\":\"Angola\",\"AI\":\"Anguilla\",\"AQ\":\"Antarctica\",\"AG\":\"Antigua And Barbuda\",\"AR\":\"Argentina\",\"AM\":\"Armenia\",\"AW\":\"Aruba\",\"AU\":\"Australia\",\"AT\":\"Austria\",\"AZ\":\"Azerbaijan\",\"BS\":\"Bahamas\",\"BH\":\"Bahrain\",\"BD\":\"Bangladesh\",\"BB\":\"Barbados\",\"BY\":\"Belarus\",\"BE\":\"Belgium\",\"BZ\":\"Belize\",\"BJ\":\"Benin\",\"BM\":\"Bermuda\",\"BT\":\"Bhutan\",\"BO\":\"Bolivia\",\"BA\":\"Bosnia And Herzegovina\",\"BW\":\"Botswana\",\"BV\":\"Bouvet Island\",\"BR\":\"Brazil\",\"IO\":\"British Indian Ocean Territory\",\"BN\":\"Brunei Darussalam\",\"BG\":\"Bulgaria\",\"BF\":\"Burkina Faso\",\"BI\":\"Burundi\",\"KH\":\"Cambodia\",\"CM\":\"Cameroon\",\"CA\":\"Canada\",\"CV\":\"Cape Verde\",\"KY\":\"Cayman Islands\",\"CF\":\"Central African Republic\",\"TD\":\"Chad\",\"CL\":\"Chile\",\"CN\":\"China\",\"CX\":\"Christmas Island\",\"CC\":\"Cocos (Keeling) Islands\",\"CO\":\"Colombia\",\"KM\":\"Comoros\",\"CG\":\"Congo\",\"CD\":\"Congo, Democratic Republic\",\"CK\":\"Cook Islands\",\"CR\":\"Costa Rica\",\"CI\":\"Cote D'Ivoire\",\"HR\":\"Croatia\",\"CU\":\"Cuba\",\"CY\":\"Cyprus\",\"CZ\":\"Czech Republic\",\"DK\":\"Denmark\",\"DJ\":\"Djibouti\",\"DM\":\"Dominica\",\"DO\":\"Dominican Republic\",\"EC\":\"Ecuador\",\"EG\":\"Egypt\",\"SV\":\"El Salvador\",\"GQ\":\"Equatorial Guinea\",\"ER\":\"Eritrea\",\"EE\":\"Estonia\",\"ET\":\"Ethiopia\",\"FK\":\"Falkland Islands (Malvinas)\",\"FO\":\"Faroe Islands\",\"FJ\":\"Fiji\",\"FI\":\"Finland\",\"FR\":\"France\",\"GF\":\"French Guiana\",\"PF\":\"French Polynesia\",\"TF\":\"French Southern Territories\",\"GA\":\"Gabon\",\"GM\":\"Gambia\",\"GE\":\"Georgia\",\"DE\":\"Germany\",\"GH\":\"Ghana\",\"GI\":\"Gibraltar\",\"GR\":\"Greece\",\"GL\":\"Greenland\",\"GD\":\"Grenada\",\"GP\":\"Guadeloupe\",\"GU\":\"Guam\",\"GT\":\"Guatemala\",\"GG\":\"Guernsey\",\"GN\":\"Guinea\",\"GW\":\"Guinea-Bissau\",\"GY\":\"Guyana\",\"HT\":\"Haiti\",\"HM\":\"Heard Island & Mcdonald Islands\",\"VA\":\"Holy See (Vatican City State)\",\"HN\":\"Honduras\",\"HK\":\"Hong Kong\",\"HU\":\"Hungary\",\"IS\":\"Iceland\",\"IN\":\"India\",\"ID\":\"Indonesia\",\"IR\":\"Iran, Islamic Republic Of\",\"IQ\":\"Iraq\",\"IE\":\"Ireland\",\"IM\":\"Isle Of Man\",\"IL\":\"Israel\",\"IT\":\"Italy\",\"JM\":\"Jamaica\",\"JP\":\"Japan\",\"JE\":\"Jersey\",\"JO\":\"Jordan\",\"KZ\":\"Kazakhstan\",\"KE\":\"Kenya\",\"KI\":\"Kiribati\",\"KR\":\"Korea\",\"KW\":\"Kuwait\",\"KG\":\"Kyrgyzstan\",\"LA\":\"Lao People's Democratic Republic\",\"LV\":\"Latvia\",\"LB\":\"Lebanon\",\"LS\":\"Lesotho\",\"LR\":\"Liberia\",\"LY\":\"Libyan Arab Jamahiriya\",\"LI\":\"Liechtenstein\",\"LT\":\"Lithuania\",\"LU\":\"Luxembourg\",\"MO\":\"Macao\",\"MK\":\"Macedonia\",\"MG\":\"Madagascar\",\"MW\":\"Malawi\",\"MY\":\"Malaysia\",\"MV\":\"Maldives\",\"ML\":\"Mali\",\"MT\":\"Malta\",\"MH\":\"Marshall Islands\",\"MQ\":\"Martinique\",\"MR\":\"Mauritania\",\"MU\":\"Mauritius\",\"YT\":\"Mayotte\",\"MX\":\"Mexico\",\"FM\":\"Micronesia, Federated States Of\",\"MD\":\"Moldova\",\"MC\":\"Monaco\",\"MN\":\"Mongolia\",\"ME\":\"Montenegro\",\"MS\":\"Montserrat\",\"MA\":\"Morocco\",\"MZ\":\"Mozambique\",\"MM\":\"Myanmar\",\"NA\":\"Namibia\",\"NR\":\"Nauru\",\"NP\":\"Nepal\",\"NL\":\"Netherlands\",\"AN\":\"Netherlands Antilles\",\"NC\":\"New Caledonia\",\"NZ\":\"New Zealand\",\"NI\":\"Nicaragua\",\"NE\":\"Niger\",\"NG\":\"Nigeria\",\"NU\":\"Niue\",\"NF\":\"Norfolk Island\",\"MP\":\"Northern Mariana Islands\",\"NO\":\"Norway\",\"OM\":\"Oman\",\"PK\":\"Pakistan\",\"PW\":\"Palau\",\"PS\":\"Palestinian Territory, Occupied\",\"PA\":\"Panama\",\"PG\":\"Papua New Guinea\",\"PY\":\"Paraguay\",\"PE\":\"Peru\",\"PH\":\"Philippines\",\"PN\":\"Pitcairn\",\"PL\":\"Poland\",\"PT\":\"Portugal\",\"PR\":\"Puerto Rico\",\"QA\":\"Qatar\",\"RE\":\"Reunion\",\"RO\":\"Romania\",\"RU\":\"Russian Federation\",\"RW\":\"Rwanda\",\"BL\":\"Saint Barthelemy\",\"SH\":\"Saint Helena\",\"KN\":\"Saint Kitts And Nevis\",\"LC\":\"Saint Lucia\",\"MF\":\"Saint Martin\",\"PM\":\"Saint Pierre And Miquelon\",\"VC\":\"Saint Vincent And Grenadines\",\"WS\":\"Samoa\",\"SM\":\"San Marino\",\"ST\":\"Sao Tome And Principe\",\"SA\":\"Saudi Arabia\",\"SN\":\"Senegal\",\"RS\":\"Serbia\",\"SC\":\"Seychelles\",\"SL\":\"Sierra Leone\",\"SG\":\"Singapore\",\"SK\":\"Slovakia\",\"SI\":\"Slovenia\",\"SB\":\"Solomon Islands\",\"SO\":\"Somalia\",\"ZA\":\"South Africa\",\"GS\":\"South Georgia And Sandwich Isl.\",\"ES\":\"Spain\",\"LK\":\"Sri Lanka\",\"SD\":\"Sudan\",\"SR\":\"Suriname\",\"SJ\":\"Svalbard And Jan Mayen\",\"SZ\":\"Swaziland\",\"SE\":\"Sweden\",\"CH\":\"Switzerland\",\"SY\":\"Syrian Arab Republic\",\"TW\":\"Taiwan\",\"TJ\":\"Tajikistan\",\"TZ\":\"Tanzania\",\"TH\":\"Thailand\",\"TL\":\"Timor-Leste\",\"TG\":\"Togo\",\"TK\":\"Tokelau\",\"TO\":\"Tonga\",\"TT\":\"Trinidad And Tobago\",\"TN\":\"Tunisia\",\"TR\":\"Turkey\",\"TM\":\"Turkmenistan\",\"TC\":\"Turks And Caicos Islands\",\"TV\":\"Tuvalu\",\"UG\":\"Uganda\",\"UA\":\"Ukraine\",\"AE\":\"United Arab Emirates\",\"GB\":\"United Kingdom\",\"US\":\"United States\",\"UM\":\"United States Outlying Islands\",\"UY\":\"Uruguay\",\"UZ\":\"Uzbekistan\",\"VU\":\"Vanuatu\",\"VE\":\"Venezuela\",\"VN\":\"Viet Nam\",\"VG\":\"Virgin Islands, British\",\"VI\":\"Virgin Islands, U.S.\",\"WF\":\"Wallis And Futuna\",\"EH\":\"Western Sahara\",\"YE\":\"Yemen\",\"ZM\":\"Zambia\",\"ZW\":\"Zimbabwe\"}"
  );

  $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$reservation_id'";
  $rezultat = mysqli_query($konekcija, $sql);
  $reservation = mysqli_fetch_assoc($rezultat);


  $sql = "SELECT name FROM all_properties WHERE lcode = $lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  $property_name = mysqli_fetch_assoc($rezultat);
  $property_name = $property_name["name"];

  $channel_names = [];
  $sql = "SELECT name, id FROM channels_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat))
  {
    $channel_names[$red["id"]] = $red["name"];
  }

  $to_email = $reservation["customer_mail"];
  $res_id = $reservation["reservation_code"];
  $date_received = ymdToDmy($reservation["date_received"]);
  $nights = $reservation["nights"];
  $date_arrival = ymdToDmy($reservation["date_arrival"]);
  $date_departure = ymdToDmy($reservation["date_departure"]);
  $men = $reservation["men"];
  $children = $reservation["children"];
  $channel_name = $channel_names[$reservation["id_woodoo"]];
  $channel_id = $reservation["channel_reservation_code"];
  $guest = $reservation["customer_name"] . " " . $reservation["customer_surname"];
  $email = $reservation["customer_mail"];
  $phone = $reservation["customer_phone"];
  $address = $reservation["customer_address"];
  $zip = $reservation["customer_zip"];
  $city = $reservation["customer_city"];
  $note = $reservation["note"];
  $country = $reservation["customer_country"];
  if(isset($iso_countries->$country))
    $country = $iso_countries->$country;
  else
    $country = "";
  $rooms = json_decode($reservation["room_data"]);
  for($i=0;$i<sizeof($rooms);$i++){
    $room_price = $rooms[$i]->price;
    $room_name = $rooms[$i]->name;
    $count = $rooms[$i]->count;
    $rooms_html .= " <div class='row'> <div class='value value4'> $room_name ($count) = $room_price EUR  </div> </div>";
  }
  $addons_html = "";
  $addons = (array)json_decode($reservation["addons_list"]);
  $services = json_decode($reservation["services"]);
  if(sizeof($addons) || sizeof($services)){
    $addons_html .= "<div class='title'> DODATNE USLUGE: </div>";
  }

  for($i=0;$i<sizeof($addons);$i++){
    $name = $addons[$i]->name;
    $price = $addons[$i]->price;
    if($name == "City tax"){
      $total_guests = $men + $children;
      $name .= " ($total_guests)";
      $price *= $total_guests;
    }
    $addons_html .= "<div class='row'> <div class='value value4'> $name = $price EUR </div> </div>";
  }
  for($i=0;$i<sizeof($services);$i++){
    $name = $services[$i]->name;
    $price = $services[$i]->total_price;
    $addons_html .= "<div class='row'> <div class='value value4'> $name = $price EUR </div> </div>";
  }
  $price_with_services = "";
  if(sizeof($services)){
    $total_res_price = $reservation["total_price"] . " EUR";
    $price_with_services = "
    <div class='title'> UKUPNA CENA REZERVACIJE: </div>
    <div class='row'>
      <div class='value value4'> $total_res_price </div>
    </div>";
  }
  $status = $reservation["status"];
  if($status == 1){
    $status_title = "<div class='title confirmed'> NOVA REZERVACIJA</div>";
    $status_text = "<div class='value value2 confirmed'> Potvrđena </div>";
    $subject = "Nova rezervacija $property_name ($date_arrival, $res_id, $guest)";
  }
  else if($status == 5 && $reservation["was_modified"] == 1){
    $status_title = "<div class='title modified'> IZMJENJENA REZERVACIJA</div>";
    $status_text = "<div class='value value2 modified'> Izmenjena </div>";
    $subject = "Izmjenjena rezervacija $property_name ($date_arrival, $res_id, $guest";
  }
  else {
    $status_title = "<div class='title canceled'> OTKAZANA REZERVACIJA </div>";
    $status_text = "<div class='value value2 canceled'> Otkazana </div>";
    $subject = "Otkazana rezervacija $property_name ($date_arrival, $res_id, $guest)";
  }
  $ancillary = $reservation["additional_data"];
  if($reservation["additional_data"] != "{}" && $reservation["additional_data"] != "[]"){
    $ancillary_html = ancillary_recursive($ancillary, 0);
    $ancillary_html = "
    <div class='section'>
      <div class='title'> INFORMACIJE SA KANALA PRODAJE: </div>
      <div class='value5'> $ancillary_html </div>
    </div>";
  }
  else {
    $ancillary_html = "";
  }
  $total_price = $reservation["reservation_price"] . " EUR";
  $message =
  "<!DOCTYPE html>
  <html lang='en' dir='ltr'>
    <head>

      <title></title>
      <style>
        * {
          font-family: Helvetica;
        }
        #container {
          width: 80%;
          max-width: 900px;
          margin: auto;
        }
        .section {
          width: 100%;
          margin-top: 40px;
          box-sizing: border-box;
          border: 1px solid #cecece;
        }
        .title {
          width: 100%;
          padding: 5px;
          text-align: center;
          vertical-align: middle;
          font-weight: bold;
          color: white;
          background-color: #aaaaaa;
          box-sizing: border-box;
        }
        .title.confirmed {
          background-color: #70ad1f;
        }
        .value.confirmed {
          color: #70ad1f;
        }
        .title.modified {
          background-color: #ed7c31;
        }
        .value.modified {
          color: #ed7c31;
        }
        .title.canceled {
          background-color: #cc0000;
        }
        .value.canceled {
          color: #cc0000;
        }
        .row {
          display: flex;
          width:100%;
          box-sizing: border-box;
        }
        .row:nth-child(even) {
          background: #dedede;
        }
        .label, .value {
          padding: 4px;
          width: 25%;
          border-left: 1px solid #cecece;
          border-bottom: 1px solid #cecece;
          box-sizing: border-box;
        }
        .label {
          padding-left: 10px;
        }
        .value {
          font-weight: bold;
        }
        .label:last-child, .value:last-child {
          border-right: 1px solid #cecece;
        }
        .value2 {
          width: 50%;
        }
        .value3 {
          width: 75%;
        }
        .value4 {
          width: 100%;
          text-align: center;
        }
        .value5 {
          width: 100%;
        }
      </style>
    </head>
    <body>
      <div id='container'>

        <div class='section'>
          $status_title
          <div class='row'>
            <div class='label value2'> OBJEKAT: </div>
            <div class='value value2'> $property_name </div>
          </div>
        </div>

        <div class='section'>
          <div class='title'> BROJ REZERVACIJE - $res_id</div>
          <div class='row'>
            <div class='label'> Datum rezervacije </div>
            <div class='value'> $date_received </div>
            <div class='label'> Broj noći </div>
            <div class='value'> $nights </div>
          </div>
          <div class='row'>
            <div class='label'> Datum dolaska </div>
            <div class='value'> $date_arrival </div>
            <div class='label'> Datum odlaska </div>
            <div class='value'> $date_departure </div>
          </div>
          <div class='row'>
            <div class='label'> Odrasli </div>
            <div class='value'> $men </div>
            <div class='label'> Deca </div>
            <div class='value'> $children </div>
          </div>
        </div>

        <div class='section'>
          <div class='title'> GOST: $guest</div>
          <div class='row'>
            <div class='label'> Email </div>
            <div class='value value2'> $email </div>
            <div class='value'> $country </div>
          </div>
          <div class='row'>
            <div class='label'> Telefon </div>
            <div class='value'> $phone </div>
            <div class='value'> $address $zip </div>
            <div class='value'> $city </div>
          </div>
          <div class='row'>
            <div class='label'> Napomena </div>
            <div class='value value3'> $note </div>
          </div>
        </div>

        <div class='section'>
          <div class='title'> JEDINICE: </div>
            $rooms_html
          <div class='title'> UKUPNA CENA SOBA: </div>
          <div class='row'>
            <div class='value value4'> $total_price </div>
          </div>
          $addons_html
          $price_with_services
        </div>
      </div>
    </body>
  </html>";
  $headers = "MIME-Version: 1.0" . "\r\n";
  $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
  $headers .= "From: noreply@otasync.me" . "\r\n";
  $headers .= "Reply-To: $email" . "\r\n" ;
  $rez = mail($to_email, $subject, $message, $headers);
}
function sendGuestEmail($reservation_id, $lcode, $account, $konekcija)
{
  $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$reservation_id'";
  $rezultat = mysqli_query($konekcija, $sql);
  $reservation = mysqli_fetch_assoc($rezultat);
  $sql = "SELECT * FROM all_guest_emails WHERE account = '$account'";
  $rezultat = mysqli_query($konekcija, $sql);
  $email_info = mysqli_fetch_assoc($rezultat);

  $res_type = 1;
  if($reservation["id_woodoo"] != "-1" && $reservation["id_woodoo"] != "-2" && $reservation["id_woodoo"] != "-")
  {
      $res_type = 2;
  }

  if($email_info["received_active"] == 0)
    return;
  if($email_info["res_type"] == 1 && $res_type == 2)
    return;
  if($email_info["res_type"] == 2 && $res_type == 1)
    return;


  $subject = $email_info["received_subject"];
  $message = $email_info["received_text"];
  $message = str_replace( "_NAME_" , $reservation["customer_name"] , $message);
  $message = str_replace( "_SURNAME_" , $reservation["customer_surname"] , $message);
  $message = str_replace( "_DFROM_" , ymdToDmy($reservation["date_arrival"]) , $message);
  $message = str_replace( "_DTO_" , ymdToDmy($reservation["date_departure"]) , $message);
  $message = str_replace( "_AMOUNT_" , $reservation["total_price"] . " EUR" , $message);
  $to_email = $reservation["customer_mail"];
  $headers = "MIME-Version: 1.0" . "\r\n";
  $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
  $headers .= 'From: noreply@otasync.me';
  $rez = mail($to_email, $subject, $message, $headers);
}


?>
