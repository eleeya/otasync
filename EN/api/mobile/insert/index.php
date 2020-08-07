<?php

require '../../main.php';

if($_SERVER['REQUEST_METHOD'] == "OPTIONS"){
    http_response_code(200);
    die();
}
else if ($_SERVER['REQUEST_METHOD'] != "POST"){
  fatal_error("Invalid method", 405);
}

$key = checkPost("key");
$lcode = checkPost("lcode");
$account = checkPost("account");

$konekcija = connectToDB();
$action = getAction();

$user = getSession($key, $account, $konekcija);
$user_id = $user["id"];

// Check access here

$old_data = [];
$new_data = [];
$ret_val = [];
$ret_val["status"] = "ok";

if($action == "avail")
{
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $values = checkPost("values");
  if(is_string($values))
    $values = json_decode($values);
  $values = to_array($values);
  $rooms = [];
  foreach($values as $key => $value){
    array_push($rooms, $key);
  }
  $variation_type = checkPost("variation_type");
  $old_values = plansAvailValues($lcode, $dfrom, $dto, $konekcija);
  $old_data["dfrom"] = $dfrom;
  $old_data["dto"] = $dto;
  $old_data["values"] = $old_values;
  plansAvailUpdate($lcode, $dfrom, $dto, $values, $rooms, $variation_type, $konekcija);
  wubookAvailUpdate($lcode, $account, $dfrom, $dto, $rooms, $konekcija);
  $new_values = plansAvailValues($lcode, $dfrom, $dto, $konekcija);
  $new_data["dfrom"] = $dfrom;
  $new_data["dto"] = $dto;
  $new_data["values"] = $new_values;
}

if($action == "price")
{
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $id = checkPost("pid");
  $values = checkPost("values");
  if(is_string($values))
    $values = json_decode($values);
  $values = to_array($values);
  $rooms = [];
  foreach($values as $key => $value){
    array_push($rooms, $key);
  }
  $variation_type = checkPost("variation_type");
  $old_values = plansPriceValues($lcode, $dfrom, $dto, $id, $konekcija);
  $old_data["dfrom"] = $dfrom;
  $old_data["dto"] = $dto;
  $old_data["pid"] = $id;
  $old_data["values"] = $old_values;
  plansPriceUpdate($lcode, $dfrom, $dto, $id, $values, $rooms, $variation_type, $konekcija);
  wubookPriceUpdate($lcode, $account, $dfrom, $dto, $id, $rooms, $konekcija);
  $new_values = plansPriceValues($lcode, $dfrom, $dto, $id, $konekcija);
  $new_data["dfrom"] = $dfrom;
  $new_data["dto"] = $dto;
  $new_data["pid"] = $id;
  $new_data["values"] = $new_values;
}

if($action == "restriction")
{
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $id = checkPost("pid");
  $values = checkPost("values");
  if(is_string($values))
    $values = json_decode($values);
  $values = to_array($values);
  $rooms = [];
  foreach($values as $key => $value){
    array_push($rooms, $key);
  }
  $old_values = plansRestrictionValues($lcode, $dfrom, $dto, $id, $konekcija);
  $old_data["dfrom"] = $dfrom;
  $old_data["dto"] = $dto;
  $old_data["pid"] = $id;
  $old_data["values"] = $old_values;
  plansRestrictionUpdate($lcode, $dfrom, $dto, $id, $values, $rooms, $konekcija);
  wubookRestrictionUpdate($lcode, $account, $dfrom, $dto, $id, $rooms, $konekcija);
  $new_values = plansRestrictionValues($lcode, $dfrom, $dto, $id, $konekcija);
  $new_data["dfrom"] = $dfrom;
  $new_data["dto"] = $dto;
  $new_data["pid"] = $id;
  $new_data["values"] = $new_values;
}

if($action == "calendar")
{
  $price_id = checkPost("price_id");
  $restriction_id = checkPost("restriction_id");
  $values = checkPost("values");
  if(is_string($values))
    $values = json_decode($values);
  for($i=0;$i<sizeof($values);$i++){
    $update_vals = $values[$i];
    $room_id = $update_vals->id;
    $rooms = array($room_id);
    $dfrom = $update_vals->date;
    $dto = $dfrom;
    // Avail
    if(isset($update_vals->avail)){
      $avail_values = [];
      $avail_values[$room_id] = $update_vals->avail;

      plansAvailUpdate($lcode, $dfrom, $dto, $avail_values, $rooms, 0, $konekcija);
      wubookAvailUpdate($lcode, $account, $dfrom, $dto, $rooms, $konekcija);
    }
    // Price
    if(isset($update_vals->price)){
      $price_values = [];
      $price_values[$room_id] = $update_vals->price;
      plansPriceUpdate($lcode, $dfrom, $dto, $price_id, $price_values, $rooms, 0, $konekcija);
      wubookPriceUpdate($lcode, $account, $dfrom, $dto, $price_id, $rooms, $konekcija);
    }
    // Restriction
    $rest_set = 0;
    $rest_values = [];
    foreach($update_vals as $rest => $val){
      if($rest != "closed" && $rest != "closed_arrival" && $rest != "closed_departure" && $rest != "min_stay" && $rest != "min_stay_arrival" && $rest != "max_stay" && $rest != "no_ota")
        continue;
      $rest_set = 1;
      $value = $val;
      if($val === true)
          $value = 1;
      if($val === false)
          $value = 0;
      $rest_values[$rest] = $value;
    }
    if($rest_set){
      $real_rest_values = [];
      $real_rest_values[$room_id] = $rest_values;
      plansRestrictionUpdate($lcode, $dfrom, $dto, $restriction_id, $real_rest_values, $rooms, $konekcija);
      wubookRestrictionUpdate($lcode, $account, $dfrom, $dto, $restriction_id, $rooms, $konekcija);
    }
  }
}

if($action == "restrictionCompact")
{
  $id = checkPost("pid");
  $values = json_decode(checkPost("values"));
  $sql = "SELECT rules FROM restrictions_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $old_data["pid"] = $id;
  $old_data["values"] = json_decode($red["rules"]);
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  makeRequest("rplan_update_rplan_rules", array($userToken, $lcode, $id, $values));
  makeReleaseRequest("release_token", array($userToken));
  $new_values = json_encode($values);
  $sql = "UPDATE restrictions_$lcode SET rules = '$new_values' WHERE id = '$id'";
  mysqli_query($konekcija, $sql);
  $new_data["pid"] = $id;
  $new_data["values"] = $values;
}

if($action == "priceVirtual")
{
  $id = checkPost("pid");
  $variation = checkPost("variation");
  $variation_type = checkPost("variation_type");
  $sql = "SELECT variation, variation_type FROM prices_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $old_data["pid"] = $id;
  $old_data["variation"] = $red["variation"];
  $old_data["variation_type"] = $red["variation_type"];
  $wubook_values = [];
  $wubook_values["pid"] = $id;
  $wubook_values["variation"] = $variation;
  $wubook_values["variation_type"] = $variation_type;
  $wubook_values = json_decode(json_encode($wubook_values));
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  makeRequest("mod_vplans", array($userToken, $lcode, array($wubook_values)));
  makeReleaseRequest("release_token", array($userToken));
  $sql = "UPDATE prices_$lcode SET variation = $variation, variation_type = $variation_type WHERE id = '$id'";
  mysqli_query($konekcija, $sql);
  $new_data["pid"] = $id;
  $new_data["variation"] = $variation;
  $new_data["variation_type"] = $variation_type;
}

if($action == "reservation")
{
  if(!(checkPostExists("id"))) // New
  {
      // Real start


      // Post data

      $date_arrival = checkPost("date_arrival");
      $date_departure = checkPost("date_departure");

      $rooms = checkPost("rooms");
      $dayprices = checkPost("dayprices");
      if(is_array($dayprices) || is_object($dayprices))
          $dayprices = json_encode($dayprices);
      $dayprices = json_decode($dayprices);
      $rooms = explode(",", $rooms);
      $r_map = [];
      for($i=0;$i<sizeof($rooms);$i++){
        $room = $rooms[$i];
        if(isset($r_map[$room]))
          $r_map[$room]["count"] += 1;
        else {
          $r_map[$room] = [];
          $r_map[$room]["id"] = $room;
          $r_map[$room]["count"] = 1;
          $r_map[$room]["price"] = $dayprices->$room;
          $r_map[$room]["price"] = $r_map[$room]["price"][0];
          $r_map[$room]["room_numbers"] = [];
        }
      }
      $rooms = [];
      foreach($r_map as $key => $value){
        array_push($rooms, $value);
      }




      $men = checkPost("men");
      $men = $men == "" ? 0 : $men;
      $children = checkPost("children");
      $children = $children == "" ? 0 : $children;
      $id_woodoo = checkPost("id_woodoo");
      $id_woodoo = $id_woodoo == "" ? "-1" : $id_woodoo;

      $guest = [];
      $guest["id"] = "";
      $guest["name"] = checkPost("customer_name");
      $guest["surname"] = checkPost("customer_surname");
      $guest["email"] = checkPost("customer_mail");
      $guest["phone"] = checkPost("customer_phone");
      $guests = array($guest);
      $guests = json_encode($guests);
      $guests = json_decode($guests);

      $discount = checkPost("discount");
      if(is_array($discount) || is_object($discount))
          $discount = json_encode($discount);
      $discount = json_decode($discount);
      if($discount->discount_type == "1")
        $discount->type = "fixed";
      else
        $discount->type = "percent";
      $discount->value = $discount->discount_value;
      $discount = json_encode($discount);


      $payment_gateway_fee_post = checkPost("payment_gateway_fee");
      if(is_array($payment_gateway_fee_post) || is_object($payment_gateway_fee_post))
          $payment_gateway_fee_post = json_encode($payment_gateway_fee_post);
      $payment_gateway_fee_post = json_decode($payment_gateway_fee_post);
      $payment_gateway_fee = [];
      $payment_gateway_fee["value"] = $payment_gateway_fee_post->payment_gateway_fee_value;
      $payment_gateway_fee["type"] = $payment_gateway_fee_post->payment_gateway_fee_type == "1" ? "" : "percent";
    $payment_gateway_fee = json_encode($payment_gateway_fee);

      $services = array();
      $total_price = checkPost("amount");
      $customer_name =  $guests[0]->name;
      $customer_surname = $guests[0]->surname;
      $customer_email = $guests[0]->email;
      $customer_phone = $guests[0]->phone;
      $customer_notes = checkPost("customer_notes");

      $nights = dateDiff($date_arrival, $date_departure);
      /* Fixed */


      // Make map with basic room info for all rooms
      $rooms_map = [];
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


      // Create list of rooms, parent rooms and room numbers (if room number isn't set value is x)
      $rooms_list = [];
      $real_rooms_list = [];
      $room_numbers = [];
      for($i=0;$i<sizeof($rooms);$i++){
        $room = $rooms[$i];
        $id = $room["id"];
        for($j=0;$j<$room["count"];$j++){
          array_push($rooms_list, $id);
          array_push($real_rooms_list, $rooms_map[$id]["parent_id"]);
          array_push($room_numbers, "x");
        }
        $rooms[$i]["name"] = $rooms_map[$id]["name"];
        $rooms[$i]["shortname"] = $rooms_map[$id]["shortname"];
        $rooms[$i]["parent_id"] = $rooms_map[$id]["parent_id"];

        $rooms[$i]["room_numbers"] = []; // Room numbers in room_data will be set later
      }

      // Calculating total services price and adding total price of each services
      $services_price = 0;
      $reservation_price = $total_price - $services_price; // This should be actually calculated and checked
      $services = json_encode($services);

      // Wubook insert
      $rooms_obj;
      $rooms_obj = [];
      for($i=0;$i<sizeof($rooms);$i++){
        $room_id = $rooms[$i]["id"];
        $rooms_obj[$room_id] = array($rooms[$i]["count"], 'nb');

      }
      $customer_obj;
      $customer_obj->fname = $customer_name;
      $customer_obj->lname = $customer_surname;
      $customer_obj->email = $customer_email;
      if($customer_obj->email == "")
        $customer_obj->email = "no@email.com";
      $dfrom = ymdToDmy($date_arrival);
      $dto = ymdToDmy($date_departure);
      $rooms_fixed = json_decode(json_encode($rooms_obj));
      $wb_price = $total_price;
      if($wb_price == 0)
        $wb_price = 1;
      $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
      $resp = makeUncheckedRequest("new_reservation", array($userToken, $lcode, $dfrom, $dto, $rooms_fixed, $customer_obj, $wb_price, 0,0,0,1,1)); // Error should still return 200 with error message so unchecked request is used
      if($resp[0] == 0){
        $reservation_code = $resp[1];

        // Updating avail in database to match WB
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

        plansAvailUpdate($lcode, $date_arrival, $avail_date_departure, $avail_values, $avail_rooms, -1, $konekcija);

        // Inserting guests or getting correct ids of existing guests (Arrivals/Nights/Paid data will be fixed later)
        $guest_ids = [];

        for($i=0;$i<sizeof($guests);$i++){
          $id = $guests[$i]->id;
          if($id == ""){ // New guest
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
          else { // Existing guest
            $name = $guests[$i]->name;
            $surname = $guests[$i]->surname;
            $email = $guests[$i]->email;
            $phone = $guests[$i]->phone;
            $sql = "UPDATE guests_$lcode SET
              name = '$name',
              surname = '$surname',
              email = '$email',
              phone = '$phone'
              WHERE id = $id";
            $rezultat = mysqli_query($konekcija, $sql);
          }
          array_push($guest_ids, $guests[$i]->id);
        }

        // Getting currently occupied rooms
        $occupied_rooms = [];
        $sql = "SELECT rooms, room_numbers
                FROM reservations_$lcode
                WHERE date_departure > '$date_arrival' AND date_arrival < '$date_departure' AND status=1
                ";
        $rezultat = mysqli_query($konekcija, $sql);
        while($red = mysqli_fetch_assoc($rezultat)){
          $cur_room_ids = explode(",", $red["room_numbers"]);
          $cur_rooms = explode(",", $red["rooms"]);
          for($x=0;$x<sizeof($cur_room_ids);$x++){
            $occupied_rooms[$cur_rooms[$x]][$cur_room_ids[$x]] = 1;
          }
         }

         // Setting rooms as occupied for already selected room numbers
         for($j=0;$j<sizeof($real_rooms_list);$j++){
           $room_id = $real_rooms_list[$j];
           if($room_numbers[$j] != "x"){
             if($occupied_rooms[$room_id][$room_numbers[$j]] == 1) // If somehow the selected room is already occupied
               $room_numbers[$j] = "x";
             else
               $occupied_rooms[$room_id][$room_numbers[$j]] = 1;
           }
         }

         // Setting room numbers
        for($j=0;$j<sizeof($real_rooms_list);$j++){
          $room_id = $real_rooms_list[$j];
          if($room_numbers[$j] == "x"){ // If number wasn't previously set
            $x=0;
            while(1){
              if($occupied_rooms[$room_id][$x] !== 1){ // If that room number is free
                $room_numbers[$j] = $x;
                $occupied_rooms[$room_id][$x] = 1;
                break;
              }
              $x += 1;
            }
          }
        }

        // Really ugly way of adding room numbers to room array
        for($i=0;$i<sizeof($rooms);$i++){
          for($j=0;$j<sizeof($real_rooms_list);$j++){
            if($rooms[$i]->id == $real_rooms_list[$j]){
              array_push($rooms[$i]["room_numbers"], $room_numbers[$j]);
            }
          }
        }

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
          0,
          'waiting_arrival',
          '0001-01-01',
          0,
          '{}',
          '$id_woodoo',
          '',
          '',
          $user_id)";
        $rezultat = mysqli_query($konekcija, $sql);
        if($rezultat){
          $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$reservation_code'";
          $rezultat = mysqli_query($konekcija, $sql);
          $new_data = mysqli_fetch_assoc($rezultat);

          // Fixing guests
          $guests_list = explode(",", $new_data["guest_ids"]);
          for($i=0;$i<sizeof($guests_list);$i++){
            repairGuestData($guests_list[$i], $lcode, $konekcija);
          }
          // Emails
          sendReservationConfirmation($reservation_code, $lcode, $account, $konekcija);
          sendGuestEmail($reservation_code, $lcode, $account, $konekcija);
          if(checkPostExists("send_email")){
            sendGuestConfirmation($reservation_code, $lcode, $account, $konekcija);
          }
        }
        else {
         fatal_error("Database error", 500); // Server failed
        }
      }
      else { // Wubook insert failed
        fatal_error($resp[1], 200);
      }
  }
  else { // Edit
    $change_id = checkPost("id");
    $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$change_id' LIMIT 1";
    $rezultat = mysqli_query($konekcija, $sql);
    if(!$rezultat)
      fatal_error("Invalid ID", 200);
    $customer_notes = checkPost("customer_notes");
    $sql = "UPDATE reservations_$lcode SET note = '$customer_notes' WHERE reservation_code = '$change_id'";
    $rezultat = mysqli_query($konekcija, $sql);
    if(!$rezultat) // Updating guest data
      fatal_error("Database error", 500); // Server failed
    // Temp solution
    $ret_val["data"] = [];
    $ret_val["data"]["new_data"] = [];
    $ret_val["data"]["new_data"]["customer_notes"] = $customer_notes;
  }
}

echo json_encode($ret_val);
$konekcija->close();

?>
