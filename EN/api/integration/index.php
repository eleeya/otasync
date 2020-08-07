<?php

require '../main.php';


if($_SERVER['REQUEST_METHOD'] == "OPTIONS"){
    http_response_code(200);
    die();
}
else if ($_SERVER['REQUEST_METHOD'] != "POST"){
  fatal_error("Invalid method", 405);
}

$lcode = checkPost("lcode");
$account = checkPost("account");
$konekcija = connectToDB();
$action = getAction();
$ret_val = [];
$ret_val["status"] = "ok";

// Checking if property exists
$sql = "SELECT 1 FROM all_properties WHERE lcode = '$lcode' LIMIT 1";
$rezultat = mysqli_query($konekcija, $sql);
if($rezultat->num_rows == 0){
  fatal_error("Invalid lcode", 400);
}

if($action == "rooms")
{
  $rooms = [];

  $sql = "SELECT id, name, shortname, type, availability, price, occupancy FROM rooms_$lcode WHERE parent_room = '0' ORDER BY name ASC";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($rooms, $red);
  }
  $ret_val["rooms"] = $rooms;
}

if($action == "pricingPlans")
{
  $pricing_plans = [];

  $sql = "SELECT id, name FROM prices_$lcode ORDER BY name ASC";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($pricing_plans, $red);
  }

  $ret_val["pricing_plans"] = $pricing_plans;
}

if($action == "restrictionPlans")
{
  $restriction_plans = [];

  $sql = "SELECT id, name FROM restrictions_$lcode ORDER BY name ASC";
  $rezultat = mysqli_query ($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($restriction_plans, $red);
  }

  $ret_val["restriction_plans"] = $restriction_plans;
}

if($action == "avail")
{
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $avail = plansAvailValues($lcode, $dfrom, $dto, $konekcija);
  $ret_val["avail"] = $avail;
}

if($action == "prices")
{
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $pid  = checkPost("pid");
  $prices = plansPriceValues($lcode, $dfrom, $dto, $pid, $konekcija);
  $ret_val["prices"] = $prices;
}

if($action == "restrictions")
{
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $pid  = checkPost("pid");
  $restrictions = plansRestrictionValues($lcode, $dfrom, $dto, $pid, $konekcija);
  $ret_val["restrictions"] = $restrictions;
}

if($action == "insertReservation")
{
  $date_arrival = checkPost("date_arrival");
  $date_departure = checkPost("date_departure");
  $men = checkPost("adults");
  $children = checkPost("children");
  $rooms = json_decode(checkPost("rooms"));
  $total_price = checkPost("total_price");
  $customer_name =  checkPost("customer_name");
  $customer_surname = checkPost("customer_surname");
  $customer_email = checkPost("customer_email");
  $customer_phone = checkPost("customer_phone");
  $customer_notes = checkPost("note");
  $nights = dateDiff($date_arrival, $date_departure);

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
  $resp = makeUncheckedRequest("new_reservation", array($userToken, $lcode, $dfrom, $dto, $rooms_fixed, $customer_obj, $wb_price, 0,0,0,1,1));
  if($resp[0] == 0)
  {
    $reservation_code = $resp[1];
    // Guest id
    $sql = "INSERT INTO guests_$lcode (name, surname, email, phone, country_of_residence, place_of_residence, address, zip, country_of_birth, date_of_birth, gender, host_again, note, total_arrivals, total_nights, total_paid, registration_data, created_by) VALUES (
      '$customer_name',
      '$customer_surname',
      '$customer_email',
      '$customer_phone',
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
      'Wubook')";
    $rezultat = mysqli_query($konekcija, $sql);
    if($rezultat)
      $customer_id = mysqli_insert_id($konekcija);

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
    for($j=0;$j<sizeof($rooms_list);$j++)
    {
      $one_room_id = $rooms_list[$j];
      $room_count = $room_avails[$one_room_id];
      $x=0;
      while(1)
      {
        if($occupied_rooms[$one_room_id][$x] !== 1)
        {
          $room_numbers = $room_numbers . $x . ",";
          $occupied_rooms[$one_room_id][$x] = 1;
          break;
        }
        $x += 1;
      }
    }
    $room_numbers = rtrim($room_numbers,",");
    // Room ids done

    $date_received = date("Y-m-d");
    $time_received = date("H:i:s");
    $rooms_list = [];
    $real_rooms = [];
    for($i=0;$i<sizeof($rooms);$i++){
      $id = $rooms[$i]->id;
      $sql = "SELECT name, shortname, id, parent_room FROM rooms_$lcode WHERE id = '$id'";
      $rezultat = mysqli_query($konekcija, $sql);
      $red = mysqli_fetch_assoc($rezultat);
      if($red["parent_room"] == '0')
        array_push($real_rooms, $id);
      else
        array_push($real_rooms, $red["parent_room"]);
      array_push($rooms_list, $id);
      $rooms[$i]->name = $red["name"];
      $rooms[$i]->shortname = $red["shortname"];
    }
    $rooms_list = implode(",", $rooms_list);
    $real_rooms = implode(",", $real_rooms);
    $room_data = json_encode($rooms);
    $payment_gateway_fee = [];
$payment_gateway_fee["type"] = "fixed";
$payment_gateway_fee["value"] = 0;
$payment_gateway_fee = json_encode($payment_gateway_fee);

$discount = [];
$discount["type"] = "fixed";
$discount["value"] = 0;
$discount = json_encode($discount);


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
     '$customer_id',
     '$customer_name',
     '$customer_surname',
     '$customer_email',
     '$customer_phone',
     '--',
     '',
     '',
     '$customer_notes',
     '$payment_gateway_fee',
     $total_price,
     '[]',
     0,
     $total_price,
     '',
     '$discount',
     '[]',
     0,
     'waiting_arrival',
     '0001-01-01',
     0,
     '[]',
     '',
     '',
     '',
     'Wubook'
   )";
    $rezultat = mysqli_query($konekcija, $sql);
  }
  else { // Wubook insert failed
    fatal_error($resp[1], 200);
  }
}


// Return
echo json_encode($ret_val);
$konekcija->close();


?>
