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

if($action == "reservation")
{
  // Post data
  $date_arrival = checkPost("date_arrival");
  $date_departure = checkPost("date_departure");
  $rooms = json_decode(checkPost("rooms"));
  $men = checkPost("adults");
  $children = checkPost("children");
  $id_woodoo = checkPost("id_woodoo");
  $guests = json_decode(checkPost("guests"));
  $discount = checkPost("discount");
  $payment_gateway_fee = checkPost("avans");
  $services = json_decode(checkPost("services"));
  $total_price = checkPost("total_price");
  $customer_name =  $guests[0]->name;
  $customer_surname = $guests[0]->surname;
  $customer_email = $guests[0]->email;
  $customer_phone = $guests[0]->phone;
  $customer_notes = checkPost("note");
  $nights = dateDiff($date_arrival, $date_departure);

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
    $id = $room->id;
    for($j=0;$j<$room->count;$j++){
      array_push($rooms_list, $id);
      array_push($real_rooms_list, $rooms_map[$id]["parent_id"]);
      if(isset($room->room_numbers[$j]))
        array_push($room_numbers, $room->room_numbers[$j]);
      else
        array_push($room_numbers, "x");
    }
    $rooms[$i]->name = $rooms_map[$id]["name"];
    $rooms[$i]->shortname = $rooms_map[$id]["shortname"];
    $rooms[$i]->parent_id = $rooms_map[$id]["parent_id"];
    $rooms[$i]->room_numbers = []; // Room numbers in room_data will be set later
  }

  // Calculating total services price and adding total price of each services
  $services_price = 0;
  for($i=0;$i<sizeof($services);$i++){
    $service = $services[$i];
    $service_price = $service->amount * $service->price * (100 + $service->tax) / 100;
    $services[$i]->total_price = $service_price;
    $services_price += $service_price;
  }
  $reservation_price = $total_price - $services_price; // This should be actually calculated and checked
  $services = json_encode($services);

  // Wubook insert
  $rooms_obj;
  for($i=0;$i<sizeof($rooms);$i++){
    $room_id = $rooms[$i]->id;
    $rooms_obj->$room_id = array($rooms[$i]->count, 'nb');
  }
  $customer_obj;
  $customer_obj->fname = $customer_name;
  $customer_obj->lname = $customer_surname;
  $customer_obj->email = $customer_email;
  if($customer_obj->email == "")
    $customer_obj->email = "no@email.com";
  $dfrom = ymdToDmy($date_arrival);
  $dto = ymdToDmy($date_departure);
  $rooms_fixed = (object)$rooms_obj;
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
          array_push($rooms[$i]->room_numbers, $room_numbers[$j]);
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
      if(checkPost("send_guest_email") == 1)
        sendGuestConfirmation($reservation_code, $lcode, $account, $konekcija);
    }
    else {
     fatal_error("Database error", 500); // Server failed
    }
  }
  else { // Wubook insert failed
    fatal_error($resp[1], 200);
  }
}

if($action == "guest")
{
  $name = checkPost("name");
  $surname = checkPost("surname");
  $email = checkPost("email");
  $phone = checkPost("phone");
  $country_of_residence = checkPost("country_of_residence");
  $date_of_birth = checkPost("date_of_birth");
  $gender =  checkPost("gender");
  $host_again = checkPost("host_again");
  $note = checkPost("note");

  $sql = "INSERT INTO guests_$lcode (name, surname, email, phone, country_of_residence, place_of_residence, address, zip, country_of_birth, date_of_birth, gender, host_again, note, total_arrivals, total_nights, total_paid, registration_data, created_by) VALUES (
    '$name',
    '$surname',
    '$email',
    '$phone',
    '$country_of_residence',
    '',
    '',
    '',
    '',
    '$date_of_birth',
    '$gender',
    $host_again,
    '$note',
    0,
    0,
    0,
    '',
    $user_id)";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat){
    $id = mysqli_insert_id($konekcija);
    $sql = "SELECT * FROM guests_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
  else {
   fatal_error("Database error", 500); // Server failed
  }
}

if($action == "invoice")
{
  $invoice_user = checkPost("user");
  $type = checkPost("type");
  $mark = checkPost("mark");
  $status = checkPost("status");
  $issued = checkPost("issued");
  $delivery = checkPost("delivery");
  $payment_type = checkPost("payment_type");
  $name = checkPost("name");
  $pib = checkPost("pib");
  $mb = checkPost("mb");
  $address = checkPost("address");
  $email = checkPost("email");
  $phone = checkPost("phone");
  $reservation_name = checkPost("reservation_name");
  $services = checkPost("services");
  $price = checkPost("price");
  $note = checkPost("note");
  $reservation_code = checkPost("reservation_code");
  // Get number/year
  $invoice_year = date("Y");
  $sql = "SELECT MAX(invoice_number) AS max_num FROM invoices_$lcode WHERE invoice_year = $invoice_year";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $invoice_number = $red["max_num"];
  $invoice_number += 1;
  $created_date = date("Y-m-d");
  $created_time = date("H:i:s");
  if($mark == "")
    $mark = $invoice_number . " - " . $invoice_year;
  $room_id = "";
  if(checkPostExists("room_id"))
    $room_id = checkPost("room_id");

  $sql = "INSERT INTO invoices_$lcode (invoice_number, invoice_year, created_date, created_time, user, type, mark, room_id, status, issued, delivery, payment_type, name, pib, mb, address, email, phone, reservation_name, services, price, note, reservation_code, created_by) VALUES (
    '$invoice_number',
    '$invoice_year',
    '$created_date',
    '$created_time',
    '$invoice_user',
    '$type',
    '$mark',
    '$room_id',
    '$status',
    '$issued',
    '$delivery',
    '$payment_type',
    '$name',
    '$pib',
    '$mb',
    '$address',
    '$email',
    '$phone',
    '$reservation_name',
    '$services',
    '$price',
    '$note',
    '$reservation_code',
    '$user_id')
    ";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat){
    $id = mysqli_insert_id($konekcija);
    $sql = "SELECT * FROM invoices_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
  else {
   fatal_error("Database error", 500); // Server failed
  }
}

if($action == "promocode")
{
  $name = checkPost("name");
  $code = checkPost("code");
  $target = checkPost("target");
  $value = checkPost("value");
  $type = checkPost("type");
  $description = checkPost("description");

  // DB insert
  $sql = "INSERT INTO promocodes_$lcode (name, code, target, value, type, description, created_by) VALUES (
    '$name',
    '$code',
    '$target',
    '$value',
    '$type',
    '$description',
    '$user_id')";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat){
    $id = mysqli_insert_id($konekcija);
    $sql = "SELECT * FROM promocodes_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
  else {
   fatal_error("Database error", 500); // Server failed
  }
}

if($action == "policy")
{
  $name = checkPost("name");
  $type = checkPost("type");
  $value = checkPost("value");
  $enableFreeDays = checkPost("enableFreeDays");
  $freeDays = checkPost("freeDays");
  $description = checkPost("description");

  // DB insert
  $sql = "INSERT INTO policies_$lcode (name, type, value, enableFreeDays, freeDays, description, created_by) VALUES (
    '$name',
    '$type',
    '$value',
    '$enableFreeDays',
    '$freeDays',
    '$description',
    '$user_id')";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat){
    $id = mysqli_insert_id($konekcija);
    $sql = "SELECT * FROM policies_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
  else {
   fatal_error("Database error", 500); // Server failed
  }
}

if($action == "category")
{
  $name = checkPost("name");
  $parent_id = checkPost("parent_id");
  $sql = "INSERT INTO categories_$lcode (name, parent_id)
  VALUES
  (
  '$name',
  '$parent_id'
  )
  ";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
  {
    $new_data_id = mysqli_insert_id($konekcija);
    $sql = "SELECT * FROM categories_$lcode WHERE id = $new_data_id";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
  else {
    fatal_error("Database error", 500); // Server failed
  }
}

if($action == "article")
{
  $category_id = checkPost("category_id");
  $barcode = checkPost("barcode");
  $code = checkPost("code");
  $tax_rate = checkPost("tax_rate");
  $description = checkPost("description");
  $class = checkPost("class");
  $price = checkPost("price");
  $sql = "INSERT INTO articles_$lcode (category_id, barcode, code,
  tax_rate, description, class, price)
  VALUES
  (
  '$category_id',
  '$barcode',
  '$code',
  '$tax_rate',
  '$description',
  '$class',
  '$price'
  )
  ";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
  {
    $new_data_id = mysqli_insert_id($konekcija);
    $sql = "SELECT * FROM articles_$lcode WHERE id = $new_data_id";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
  else {
    fatal_error("Database error", 500); // Server failed
  }
}

if($action == "pricingPlan")
{
  $name = checkPost("name");
  $type = checkPost("type");
  $variation = checkPost("variation");
  $variation_type = checkPost("variation_type");
  $vpid = checkPost("vpid");
  $description = checkPost("description");
  $policy = checkPost("policy");
  $booking_engine = checkPost("booking_engine");
  $board = checkPost("board");
  $restriction_plan = checkPost("restriction_plan");

  // Wubook insert
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  if($type == "daily"){
    $pid = makeRequest("add_pricing_plan", array($userToken, $lcode, $name));
    $variation = "";
    $variation_type = "";
    $vpid = "";
  }
  else {
    $pid = makeRequest("add_vplan", array($userToken, $lcode, $name, $vpid, $variation_type, $variation));
  }
  makeReleaseRequest("release_token", array($userToken));

  // DB insert
  $sql = "INSERT INTO prices_$lcode VALUES (
    '$pid',
    '$name',
    '$type',
    '$variation',
    '$variation_type',
    '$vpid',
    '$description',
    '$policy',
    '$booking_engine',
    '$board',
    '$restriction_plan',
    '$user_id')";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat){
    $sql = "SELECT * FROM prices_$lcode WHERE id = '$pid'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
    if($type == "daily"){ // Getting values from wubook
      $dfrom = date("Y-m-d");
      $time = strtotime($dfrom);
      $dto = date("Y-m-d", $time+364*24*60*60);
      plansInsertWubookPrice($lcode, $account, $dfrom, $dto, $pid, $konekcija);
      $dfrom = date("Y-m-d", $time+365*24*60*60);
      $dto = date("Y-m-d", $time+729*24*60*60);
      plansInsertWubookPrice($lcode, $account, $dfrom, $dto, $pid, $konekcija);
    }
  }
  else {
   fatal_error("Database error", 500); // Server failed
  }
}

if($action == "restrictionPlan")
{
  $name = checkPost("name");
  $type = checkPost("type");

  // Wubook insert
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  if($type == "daily"){
    $pid = makeRequest("rplan_add_rplan", array($userToken, $lcode, $name, 0));
    $rules = [];
  }
  else {
    $pid = makeRequest("rplan_add_rplan", array($userToken, $lcode, $name, 1));
    $rules = [];
    $rules['closed_arrival'] = 0;
    $rules['closed'] = 0;
    $rules['min_stay'] = 0;
    $rules['closed_departure'] = 0;
    $rules['max_stay'] = 0;
    $rules['min_stay_arrival'] = 0;
    $rules = (object)$rules;
    makeRequest("rplan_update_rplan_rules", array($userToken, $lcode, $pid, $rules));
    $rules = json_encode($rules);
  }
  makeReleaseRequest("release_token", array($userToken));

  // DB insert
  $sql = "INSERT INTO restrictions_$lcode VALUES (
    '$pid',
    '$name',
    '$type',
    '$rules',
    '$user_id')";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat){
    $sql = "SELECT * FROM restrictions_$lcode WHERE id = '$pid'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
    if($type == "daily"){ // Getting values from wubook
      $dfrom = date("Y-m-d");
      $time = strtotime($dfrom);
      $dto = date("Y-m-d", $time+364*24*60*60);
      plansInsertWubookRestriction($lcode, $account, $dfrom, $dto, $pid, $konekcija);
      $dfrom = date("Y-m-d", $time+365*24*60*60);
      $dto = date("Y-m-d", $time+729*24*60*60);
      plansInsertWubookRestriction($lcode, $account, $dfrom, $dto, $pid, $konekcija);
    }
  }
  else {
   fatal_error("Database error", 500); // Server failed
  }
}

if($action == "room")
{
  $name = checkPost("name");
  $shortname = checkPost("shortname");
  $type = checkPost("type");
  $price = checkPost("price");
  $availability = checkPost("availability");
  $booking_engine = checkPost("booking_engine");
  $occupancy = checkPost("occupancy");
  $area = checkPost("area");
  $bathrooms = checkPost("bathrooms");
  $houserooms = checkPost("houserooms");
  $linked_room = checkPost("linked_room");
  $additional_prices = checkPost("additional_prices");
  $room_numbers = checkPost("room_numbers");
  $description = checkPost("description");
  $amenities = checkPost("amenities");
  $image_links = [];
  $files_count = checkPost("files_count");
  for($i=0;$i<$files_count;$i++){
    array_push($image_links, saveImage("file_$i", $lcode . "_" . time() . $i, "/beta/images/"));
  }
  $images = json_encode($image_links);

  // Wubook insert
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  $id = makeRequest("new_room", array($userToken, $lcode, 0, $name, $occupancy, $price, $availability, $shortname, 'nb'));
  makeReleaseRequest("release_token", array($userToken));

  // DB insert
  $sql = "INSERT INTO rooms_$lcode VALUES (
    '$id',
    '$name',
    '$shortname',
    '$type',
    '$price',
    '$availability',
    '$occupancy',
    '$description',
    '$images',
    '$area',
    '$bathrooms',
    '$houserooms',
    '$amenities',
    '$booking_engine',
    '$room_numbers',
    '$linked_room',
    '0',
    '$additional_prices',
    'clean',
    '$user_id')";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat){
    $sql = "SELECT * FROM rooms_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
  else {
   fatal_error("Database error", 500); // Server failed
  }

  // Fix tables

  // Drop old avail/price/restriction tables

  $sql = "DROP TABLE avail_values_$lcode, prices_values_$lcode, restrictions_values_$lcode";
  mysqli_query($konekcija, $sql);

  // Create avail/price/restriction values tables
  $real_rooms = [];
  $sql = "SELECT id FROM rooms_$lcode WHERE parent_room = '0'"; // Only get real rooms for avail
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($real_rooms, $red["id"]);
  }
  $rooms = [];
  $restrictions_rooms = [];
  $sql = "SELECT id FROM rooms_$lcode"; // Get all rooms for prices and restrictions
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($rooms, $red["id"]);
    // Making all fields for restrictions table
    array_push($restrictions_rooms, "min_stay_" . $red["id"]);
    array_push($restrictions_rooms, "min_stay_arrival_" . $red["id"]);
    array_push($restrictions_rooms, "max_stay_" . $red["id"]);
    array_push($restrictions_rooms, "closed_" . $red["id"]);
    array_push($restrictions_rooms, "closed_departure_" . $red["id"]);
    array_push($restrictions_rooms, "closed_arrival_" . $red["id"]);
    array_push($restrictions_rooms, "no_ota_" . $red["id"]);
  }



  // Avail
  $rooms_sql = "room_" . implode(" INT, room_", $real_rooms) . " INT"; // SQL to create a column for each room
  $sql = "CREATE TABLE avail_values_$lcode
  (
    avail_date DATE NOT NULL,
    $rooms_sql,
    PRIMARY KEY (avail_date)
  )";
  $rezultat = mysqli_query($konekcija, $sql);
  // Prices
  $rooms_sql = "room_" . implode(" FLOAT, room_", $rooms) . " FLOAT";
  $sql = "CREATE TABLE prices_values_$lcode
  (
    id VARCHAR(63),
    price_date DATE NOT NULL,
    $rooms_sql,
    PRIMARY KEY (id, price_date)
  )";
  mysqli_query($konekcija, $sql);
  // Restrictions
  $rooms_sql = implode(" INT, ", $restrictions_rooms) . " INT";
  $sql = "CREATE TABLE restrictions_values_$lcode
  (
    id VARCHAR(63),
    restriction_date DATE NOT NULL,
    $rooms_sql,
    PRIMARY KEY (id, restriction_date)
  )";
  mysqli_query($konekcija, $sql);

  // Values of avail/prices/restrictions
  $dfrom = date("Y-m-d");
  $time = strtotime($dfrom);
  $dto = date("Y-m-d", $time+364*24*60*60);
  plansInsertWubook($lcode, $account, $dfrom, $dto, $konekcija);
  $dfrom = date("Y-m-d", $time+365*24*60*60);
  $dto = date("Y-m-d", $time+729*24*60*60);
  plansInsertWubook($lcode, $account, $dfrom, $dto, $konekcija);
  plansInsertWubook($lcode, $account, "2021-03-01", "2021-03-31", $konekcija);


}

if($action == "initialRooms")
{
    $sql = "CREATE TABLE IF NOT EXISTS rooms_$lcode
  (
    id VARCHAR(63) NOT NULL,
    name VARCHAR(255),
    shortname VARCHAR(255),
    type VARCHAR(255),
    price FLOAT,
    availability INT,
    occupancy INT,
    description TEXT,
    images TEXT,
    area FLOAT,
    bathrooms FLOAT,
    houserooms TEXT,
    amenities TEXT,
    booking_engine INT,
    room_numbers TEXT,
    linked_room TEXT,
    parent_room VARCHAR(63),
    additional_prices TEXT,
    status VARCHAR(255),
    created_by VARCHAR(255),
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  $new_data = [];
  $rooms = checkPost("rooms");
  if(is_string($rooms))
    $rooms = json_decode($rooms);
  for($i=0;$i<sizeof($rooms);$i++){
    $id = time();
    $room = (object)$rooms[$i];


    $name = $room->name;
    $shortname = $room->shortname;
    $type = $room->type;
    $price = $room->price;
    $availability = $room->availability;
    $booking_engine = 1;
    $occupancy = $room->occupancy;
    $area = $room->area;
    $bathrooms = $room->bathrooms;
    $houserooms = json_encode($room->houserooms);
    $linked_room = "{\"active\":0, \"avail\":0, \"price\":0, \"restrictions\":0, \"variation\": 0, \"variation_type\": \"fixed\"}";
    $additional_prices = json_encode($room->additional_prices);
    $room_numbers = implode(",", $room->room_numbers);
    $description = $room->description;
    $amenities = json_encode($room->amenities);
    $image_links = [];
    $files_count = 0;
    for($j=0;$j<$files_count;$j++){
      array_push($image_links, saveImage("file_$j", $lcode . "_" . time() . $j, "/beta/images/"));
    }
    $images = json_encode($image_links);
    // DB insert
    $sql = "INSERT INTO rooms_$lcode VALUES (
      '$id',
      '$name',
      '$shortname',
      '$type',
      '$price',
      '$availability',
      '$occupancy',
      '$description',
      '$images',
      '$area',
      '$bathrooms',
      '$houserooms',
      '$amenities',
      '$booking_engine',
      '$room_numbers',
      '$linked_room',
      '0',
      '$additional_prices',
      'clean',
      '$user_id')";
    $rezultat = mysqli_query($konekcija, $sql);
    if($rezultat){
      $sql = "SELECT * FROM rooms_$lcode WHERE id = '$id'";
      $rezultat = mysqli_query($konekcija, $sql);
      array_push($new_data, mysqli_fetch_assoc($rezultat));
    }
    else {
     fatal_error("Database error", 500); // Server failed
    }
  }
}

if($action == "extra")
{
  $name = checkPost("name");
  $description = checkPost("description");
  $type = checkPost("type");
  $price = checkPost("price");
  $pricing = checkPost("pricing");
  $daily = checkPost("daily");
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $restriction_plan = checkPost("restriction");
  $rooms = checkPost("rooms");
  $specific_rooms = checkPost("specific_rooms");
  $tax = checkPost("tax");
  $image = "";
  /*
  if(checkPostExists("img_link")){
    $image = checkPost("image");
  }
  else {
    $image = saveImage("image", $lcode . "_" . time(), "/beta/images/");
  }
  */

  // DB insert
  $sql = "INSERT INTO extras_$lcode (name, description, type, price, pricing, daily, dfrom, dto, restriction_plan, rooms, specific_rooms, image, tax, created_by) VALUES (
    '$name',
    '$description',
    '$type',
    '$price',
    '$pricing',
    '$daily',
    '$dfrom',
    '$dto',
    '$restriction_plan',
    '$rooms',
    '$specific_rooms',
    '$image',
    '$tax',
    '$user_id')";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat){
    $id = mysqli_insert_id($konekcija);
    $sql = "SELECT * FROM extras_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
  else {
   fatal_error("Database error", 500); // Server failed
  }
}

if($action == "channel")
{
  $name = checkPost("name");
  $commission = checkPost("commission");

  // Get ID
  $sql = "SELECT MAX(id) as next_id FROM channels_$lcode WHERE ctype = '-1'";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $id = $red["next_id"] + 1;

  // DB insert
  $sql = "INSERT INTO channels_$lcode (id, ctype, name, commission, logo, created_by) VALUES (
    '$id',
    '-1',
    '$name',
    '$commission',
    '',
    '$user_id')";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat){
    $sql = "SELECT * FROM channels_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
  else {
   fatal_error("Database error", 500); // Server failed
  }
}

if($action == "user")
{
  $user_email = checkPost("email");
  $user_name = checkPost("name");
  $user_reservations = checkPost("reservations");
  $user_guests = checkPost("guests");
  $user_invoices = checkPost("invoices");
  $user_prices = checkPost("prices");
  $user_restrictions = checkPost("restrictions");
  $user_avail = checkPost("avail");
  $user_rooms = checkPost("rooms");
  $user_channels = checkPost("channels");
  $user_statistics = checkPost("statistics");
  $user_changelog = 3;
  $user_properties = checkPost("properties");
  $user_key = sha1($user_email . $account . time());


  $sql = "INSERT INTO all_users (username, pwd, account, status, properties, reservations, guests, invoices, prices, restrictions, avail, rooms, channels, statistics, changelog, articles, wspay, engine, name, email, phone, client_name, company_name, address, city, country, pib, mb, wspay_key, wspay_shop, undo_timer, notify_overbooking, notify_new_reservations, invoice_header,
  invoice_margin, invoice_issued, invoice_delivery, room_count, ctypes, booking, booking_percentage, expedia, airbnb, private, agency, split)
  VALUES
  (
    '$user_email',
    '$user_key',
    '$account',
    3,
    '$user_properties',
    $user_reservations,
    $user_guests,
    $user_invoices,
    $user_prices,
    $user_restrictions,
    $user_avail,
    $user_rooms,
    $user_channels,
    $user_statistics,
    $user_changelog,
    0,
    0,
    3,
    '$user_name',
    '$user_email',
    '',
    '$user_name',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    60,
    0,
    0,
    0,
    10,
    'today',
    'today',
    0,
    '',
    0,
    15,
    0,
    0,
    1,
    0,
    0
  )";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat){
    $id = mysqli_insert_id($konekcija);
    $sql = "SELECT * FROM all_users WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
    // Send email
    $link = "https://admin.otasync.me/confirm?key=$user_key&id=$id";
    $to_email = $user_email;
    $subject = 'Registracija';
    $message = "Za registraciju na admin.otasync.me za nalog $account kliknite na link: $link";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: noreply@otasync.me';
    $rez = mail($to_email, $subject, $message, $headers);
    if(!$rez)
      fatal_error("Email not sent", 500);
  }
  else {
   fatal_error("Database error", 500); // Server failed
  }
}

if($action == "yieldVariations")
{
  $variation_type = checkPost("variation_type");
  $variation_value = checkPost("variation_value");
  $sql = "INSERT INTO yield_variations_$lcode (variation_type, variation_value)
  VALUES
  (
  '$variation_type',
  '$variation_value'
  )";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat){
    $id = mysqli_insert_id($konekcija);
    $sql = "SELECT * FROM yield_variations_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
  else {
   fatal_error("Database error", 500); // Server failed
  }
}

// Changelog

$data_type = $action;
$ch_action = "insert";
$user_name = $user["client_name"];
if($user["status"] == "1")
  $user_name = "Master";

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
  $ret_val["data"] = [];
  $ret_val["data"]["id"] = -1;
}

echo json_encode($ret_val);
$konekcija->close();

?>
