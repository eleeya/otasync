<?php

require '../../../main.php';

if($_SERVER['REQUEST_METHOD'] == "OPTIONS"){
    http_response_code(200);
    die();
}
else if ($_SERVER['REQUEST_METHOD'] != "POST"){
  fatal_error("Invalid method", 405);
}

$lcode = checkPost("lcode");
$konekcija = connectToDB();
$action = getAction();
$ret_val = [];
$ret_val["status"] = "ok";

$old_data = [];
$new_data = [];

if($action == "reservation")
{
  $date_arrival = checkPost("date_arrival");
  $date_departure = checkPost("date_departure");
  $rooms = checkPost("rooms");
  if(!(is_array($rooms)))
    $rooms = json_decode($rooms);
  $men = checkPost("adults");
  $children = checkPost("children");
  $id_woodoo = "-2";
  $guests = checkPost("guests");
  if(!(is_array($guests)))
    $guests = json_decode($guests);
  $discount = '{"type": "fixed", "value": 0}';
  $payment_gateway_fee = '{"type": "fixed", "value": 0}';
  $services = checkPost("services");
  if(!(is_array($services)))
    $services = json_decode($services);
  $total_price = checkPost("total_price");

  $promocode = checkPost("promocode");
  if($promocode != ""){
    if(!(is_object($promocode)))
    $promocode = json_decode($promocode);
    if($promocode->target == "all" || $promocode->target == "room"){
      for($i=0;$i<sizeof($rooms);$i++){
        if($promocode->type == "percentage")
          $rooms[$i]->price = $rooms[$i]->price - ($rooms[$i]->price * $promocode->value / 100);
        else
          $rooms[$i]->price = $rooms[$i]->price - $promocode->value;
        if($rooms[$i]->price < 0)
          $rooms[$i]->price = 0;
      }
    }
    if($promocode->target == "all" || $promocode->target == "extras"){
      for($i=0;$i<sizeof($services);$i++){
        if($promocode->type == "percentage")
          $services[$i]->price = $services[$i]->price - ($services[$i]->price * $promocode->value / 100);
        else
          $services[$i]->price = $services[$i]->price - $promocode->value;
        if($services[$i]->price < 0)
          $services[$i]->price = 0;
      }
    }
    $total_price = checkPost("discounted_price");
  }




  // Get account
  $sql = "SELECT account FROM all_properties WHERE lcode = '$lcode'";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $account = $red["account"];


  $customer_name =  $guests[0]->name;
  $customer_surname = $guests[0]->surname;
  $customer_email = $guests[0]->email;
  $customer_phone = $guests[0]->phone;
  $customer_notes = "";
  $nights = dateDiff($date_arrival, $date_departure);


  $rooms_map = []; // Init map rooms
  $sql = "SELECT name, shortname, id, parent_room FROM rooms_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $rooms_map[$red["id"]] = [];
    $rooms_map[$red["id"]]["id"] = $red["id"];
    $rooms_map[$red["id"]]["name"] = $red["name"];
    $rooms_map[$red["id"]]["shortname"] = $red["shortname"];
    $rooms_map[$red["id"]]["parent_id"] = $red["id"];
    if($red["parent_room"] != '0'){
      $rooms_map[$red["id"]]["parent_id"] = $red["parent_room"];
    }
  }
  // Rooms formating
  $rooms_list = [];
  $real_rooms_list = [];
  $room_numbers = [];
  for($i=0;$i<sizeof($rooms);$i++)
  {
    $room = $rooms[$i];
    $id = $room->id;
    for($j=0;$j<$room->count;$j++){
      array_push($rooms_list, $id);
      array_push($real_rooms_list, $rooms_map[$id]["parent_id"]);
      if(isset($room->room_numbers[$j])){
        array_push($room_numbers, $room->room_numbers[$j]);
      }
      else {
        array_push($room_numbers, "x");
      }
    }
    $rooms[$i]->name = $rooms_map[$id]["name"];
    $rooms[$i]->shortname = $rooms_map[$id]["shortname"];
    $rooms[$i]->room_numbers = [];
  }

  $services_price = 0;
  for($i=0;$i<sizeof($services);$i++){
    $services[$i]->tax = 0;
    $service = $services[$i];
    $service_price = $service->amount * $service->price * (100 + $service->tax) / 100;
    $services[$i]->total_price = $service_price;
    $services_price += $service_price;
  }
  $reservation_price = $total_price - $services_price;
  $services = json_encode($services);
  // Wubook insert
  $rooms_obj;

  for($i=0;$i<sizeof($rooms);$i++)
  {
    $room_id = $rooms[$i]->id;
    $rooms_obj->$room_id = array($rooms[$i]->count, 'nb');
  }

  $customer_obj;
  $customer_obj->fname = $customer_name;
  $customer_obj->lname = $customer_surname;
  $customer_obj->email = $customer_email;
  $dfrom = ymdToDmy($date_arrival);
  $dto = ymdToDmy($date_departure);
  $rooms_fixed = (object)$rooms_obj;
  $wb_price = $total_price;
  if($wb_price == 0)
    $wb_price = 1;

  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));

  $cc_info = 0;
  if(checkPostExists("cc_number")){
    $cc_info = 1;
    $cc->cc_number = checkPost("cc_number");
    $cc->cc_owner = checkPost("cc_owner");
    $cc->cc_exp_month = checkPost("cc_exp_month");
    $cc->cc_exp_year = checkPost("cc_exp_year");
    $cc->cc_cvv = checkPost("cc_cvv");
    $cc->cc_type = checkPost("cc_type");
    $resp = makeUncheckedRequest("new_reservation", array($userToken, $lcode, $dfrom, $dto, $rooms_fixed, $customer_obj, $wb_price, 'xml',$cc,0,0,1,1));
  }
  else {
    $resp = makeUncheckedRequest("new_reservation", array($userToken, $lcode, $dfrom, $dto, $rooms_fixed, $customer_obj, $wb_price, 0,0,0,1,1));
  }
  if($resp[0] == 0)
  {


    $reservation_code = $resp[1];
    // Guest ids
    $guest_ids = [];
    for($i=0;$i<sizeof($guests);$i++){
      $id = $guests[$i]->id;
      if($id == "" || (!(isset($guests[$i]->id)))){
        $name = $guests[$i]->name;
        $surname = $guests[$i]->surname;
        $email = $guests[$i]->email;
        $phone = $guests[$i]->phone;
        $sql = "INSERT INTO guests_$lcode (name, surname, email, phone, country_of_residence, place_of_residence, address, zip, country_of_birth, date_of_birth, gender, host_again, note, total_arrivals, total_nights, total_paid, registration_data, created_by) VALUES (
          '$name',
          '$surname',
          '$email',
          '$phone',
          '--',
          '',
          '',
          '',
          '--',
          '0001-01-01',
          'M',
          1,
          '',
          1,
          $nights,
          $total_price,
          '',
          $user_id)";
        $rezultat = mysqli_query($konekcija, $sql);
        if($rezultat)
          $guests[$i]->id = mysqli_insert_id($konekcija);
      }
      else {
        $sql = "UPDATE guests_$lcode SET
          name = '$name',
          surname = '$surname',
          email = '$email',
          phone = '$phone'
          WHERE id = $id";
        $rezultat = mysqli_query($konekcija, $sql);
        if($rezultat)
          $guests[$i]->id = mysqli_insert_id($konekcija);
      }
      array_push($guest_ids, $guests[$i]->id);
    }


    $occupied_rooms = [];
    $sql = "SELECT rooms, room_numbers
            FROM reservations_$lcode
            WHERE date_departure > '$date_arrival' AND date_arrival < '$date_departure' AND status=1
            ";
    $rezultat = mysqli_query($konekcija, $sql);
    $reservations_in_period = [];
    while($red = mysqli_fetch_assoc($rezultat)){
      array_push($reservations_in_period, $red);
     }
    for($j=0;$j<sizeof($reservations_in_period);$j++)
    {
      $rip_room_ids = explode(",",$reservations_in_period[$j]["room_numbers"]); // rip = reservation in period
      $rip_rooms = explode(",", $reservations_in_period[$j]["rooms"]);
      for($x=0;$x<sizeof($rip_room_ids);$x++){
        $occupied_rooms[$rip_rooms[$x]][$rip_room_ids[$x]] = 1;
      }
    }
    // occupied rooms done

    for($j=0;$j<sizeof($real_rooms_list);$j++)
    {
      $room_id = $real_rooms_list[$j];
      if($room_numbers[$j] == "x"){
        $x=0;
        while(1)
        {
          if($occupied_rooms[$room_id][$x] !== 1)
          {
            $room_numbers[$j] = $x;
            $occupied_rooms[$room_id][$x] = 1;
            break;
          }
          $x += 1;
        }
      }
      else {
        $occupied_rooms[$room_id][$room_numbers[$j]] = 1;
      }
    }

    // Really ugly way of adding room numbers to room array
    for($i=0;$i<sizeof($rooms);$i++){
      for($j=0;$j<sizeof($real_rooms_list);$j++){
        if($rooms[$i]->id == $real_rooms_list[$j]){
          array_push($rooms[$i]->room_numbers, $room_numbers[$j]);
        }
      }
    }

    // Room ids done

    $date_received = date("Y-m-d");
    $time_received = date("H:i:s");
    $rooms_list = implode(",", $rooms_list);
    $real_rooms = implode(",", $real_rooms_list);
    $room_numbers = implode(",", $room_numbers);
    $guest_ids = implode(",", $guest_ids);
    $room_data = json_encode($rooms);
    $sql = "INSERT INTO reservations_$lcode VALUES (
      '$reservation_code',
      1,
      0,
      '',
      '',
      '$date_received',
      '$time_received',
      '$date_arrival',
      '$date_departure',
      $nights,
      '$rooms_list',
      '$room_data',
      '$real_rooms',
      '$room_numbers',
      $men,
      $children,
      '$guest_ids',
      '$customer_name',
      '$customer_surname',
      '$customer_email',
      '$customer_phone',
      '--',
      '',
      '',
      '$customer_notes',
      '$payment_gateway_fee',
      $reservation_price,
      '$services',
      $services_price,
      $total_price,
      '',
      '$discount',
      '',
      $cc_info,
      'waiting_arrival',
      '0001-01-01',
      0,
      '{}',
      '$id_woodoo',
      '',
      '',
      'Engine')";
    $rezultat = mysqli_query($konekcija, $sql);
    if($rezultat){
      $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$reservation_code'";
      $rezultat = mysqli_query($konekcija, $sql);
      $new_data = mysqli_fetch_assoc($rezultat);

      // Avail update for reservation rooms
      $avail_rooms = [];
      $avail_values = [];
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
      plansAvailUpdate($lcode, $date_arrival, $avail_date_departure, $avail_values, $avail_rooms, -1, $konekcija);

      // Emails
      sendReservationConfirmation($reservation_code, $lcode, $account, $konekcija);
      sendGuestEmail($reservation_code, $lcode, $account, $konekcija);
    }
    else {
     fatal_error("Database error", 500); // Server failed
    }
  }
  else { // Wubook insert failed
    fatal_error($resp[1], 200);
  }
}



// Changelog
$data_type = $action;
$ch_action = "insert";
$user_name = $user["name"];

$old_data = mysqli_real_escape_string($konekcija, json_encode($old_data));
$new_data = mysqli_real_escape_string($konekcija, json_encode($new_data));

$sql = "INSERT INTO changelog_$lcode (data_type, action, old_data, new_data, undone, created_by) VALUES (
  '$data_type',
  '$ch_action',
  '$old_data',
  '$new_data',
  0,
  '$user_name')";
$rezultat = mysqli_query($konekcija, $sql);
if($rezultat){
  $ch_id = mysqli_insert_id($konekcija);
  $sql = "SELECT * FROM changelog_$lcode WHERE id = $ch_id";
  $rezultat = mysqli_query($konekcija, $sql);
  $ch_data = mysqli_fetch_assoc($rezultat);
  $ch_data = fixChange($ch_data);
  $ret_val["data"] = $ch_data;
}
else {
  fatal_error("Changelog error", 500); // Server failed
}

echo json_encode($ret_val);
$konekcija->close();

?>
