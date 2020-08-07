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
if(checkPostExists("id")) // For now, because of avail/prices/restrictions
  $id = checkPost("id");

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

  // Remember data
  $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

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
    $rid = $room->id;
    for($j=0;$j<$room->count;$j++){
      array_push($rooms_list, $rid);
      array_push($real_rooms_list, $rooms_map[$rid]["parent_id"]);
      if(isset($room->room_numbers[$j]))
        array_push($room_numbers, $room->room_numbers[$j]);
      else
        array_push($room_numbers, "x");
    }
    $rooms[$i]->name = $rooms_map[$rid]["name"];
    $rooms[$i]->shortname = $rooms_map[$rid]["shortname"];
    $rooms[$i]->parent_id = $rooms_map[$rid]["parent_id"];
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

  // Inserting guests or getting correct ids of existing guests (Arrivals/Nights/Paid data will be fixed later)
  $guest_ids = [];
  for($i=0;$i<sizeof($guests);$i++){
    $gid = $guests[$i]->id;
    if($gid == ""){ // New guest
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
        WHERE id = $gid";
      $rezultat = mysqli_query($konekcija, $sql);
    }
    array_push($guest_ids, $guests[$i]->id);
  }

  // Getting currently occupied rooms
  $occupied_rooms = [];
  $sql = "SELECT rooms, room_numbers
          FROM reservations_$lcode
          WHERE date_departure > '$date_arrival' AND date_arrival < '$date_departure' AND status=1 AND reservation_code != '$id'
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

  $rooms_list = implode(",", $rooms_list);
  $real_rooms = implode(",", $real_rooms_list);
  $room_numbers = implode(",", $room_numbers);
  $guest_ids = implode(",", $guest_ids);
  $room_data = json_encode($rooms);

  $sql = "UPDATE reservations_$lcode SET
    date_arrival = '$date_arrival',
    date_departure = '$date_departure',
    nights = $nights,
    rooms = '$rooms_list',
    room_data = '$room_data',
    real_rooms = '$real_rooms',
    room_numbers = '$room_numbers',
    men = $men,
    children = $children,
    guest_ids = '$guest_ids',
    customer_name = '$customer_name',
    customer_surname = '$customer_surname',
    customer_mail = '$customer_email',
    customer_phone = '$customer_phone',
    note = '$customer_notes',
    payment_gateway_fee  = '$payment_gateway_fee',
    reservation_price = $reservation_price,
    services = '$services',
    services_price = $services_price,
    total_price = $total_price,
    discount = '$discount',
    id_woodoo = '$id_woodoo'
    WHERE reservation_code = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
  else {
    // New data
    $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);

    if($old_data["real_rooms"] != $new_data["real_rooms"] || $old_data["date_arrival"] != $new_data["date_arrival"] || $old_data["date_departure"] != $new_data["date_departure"])
    {
      // Updating avail in database and on WB (old data)
      $real_rooms_list = explode(",", $old_data["real_rooms"]);
      $date_arrival = $old_data["date_arrival"];
      $date_departure = $old_data["date_departure"];
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
      plansAvailUpdate($lcode, $date_arrival, $avail_date_departure, $avail_values, $avail_rooms, 1, $konekcija);
      wubookAvailUpdate($lcode, $account, $date_arrival, $avail_date_departure, $avail_rooms, $konekcija);

      // Updating avail in database and on WB (new data)
      $real_rooms_list = explode(",", $new_data["real_rooms"]);
      $date_arrival = $new_data["date_arrival"];
      $date_departure = $new_data["date_departure"];
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
      wubookAvailUpdate($lcode, $account, $date_arrival, $avail_date_departure, $avail_rooms, $konekcija);
    }
  }
}

if($action == "tetris")
{
  $action = "reservation"; //Simplifying changelog
  $updates = [];
  if(checkPostExists("date_arrival")){
    $dfrom = checkPost("date_arrival");
    array_push($updates, "date_arrival = '$dfrom'");
  }
  if(checkPostExists("date_departure")){
    $dto = checkPost("date_departure");
    array_push($updates, "date_departure = '$dto'");
  }
  if(checkPostExists("nights")){
    $nights = checkPost("nights");
    array_push($updates, "nights = '$nights'");
    // update price
    $sql = "SELECT total_price, nights FROM reservations_$lcode WHERE reservation_code = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $red = mysqli_fetch_assoc($rezultat);
    $og_nights = $red["nights"];
    $og_price = $red["total_price"];
    $new_price = $og_price * $nights  / $og_nights;
    $new_price = round($new_price, 2);
    array_push($updates, "total_price = '$new_price'");
    array_push($updates, "reservation_price = '$new_price'");
  }
  if(checkPostExists("rooms")){
    $rooms = checkPost("rooms");
    array_push($updates, "rooms = '$rooms'");
  }
  if(checkPostExists("room_numbers")){
    $room_numbers = checkPost("room_numbers");
    array_push($updates, "room_numbers = '$room_numbers'");
  }
  $updates = implode(",", $updates);

  // Remember data
  $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);



  $sql = "UPDATE reservations_$lcode SET
  $updates
  WHERE reservation_code = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);

  if(!$rezultat)
    fatal_error("Database failed", 500);
  else {

    // New data
    $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);

    // Fixing real rooms and room data
    $old_room_data = json_decode($old_data["room_data"]);
    $new_rooms = $new_data["rooms"];
    $new_rooms_map = []; // Init map of used rooms
    $sql = "SELECT name, shortname, room_numbers, id, parent_room FROM rooms_$lcode WHERE id IN ($new_rooms)";
    $rezultat = mysqli_query($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat)){
      $new_rooms_map[$red["id"]] = [];
      $new_rooms_map[$red["id"]]["id"] = $red["id"];
      $new_rooms_map[$red["id"]]["name"] = $red["name"];
      $new_rooms_map[$red["id"]]["shortname"] = $red["shortname"];
      $new_rooms_map[$red["id"]]["count"] = 0;
      $new_rooms_map[$red["id"]]["parent_id"] = $red["id"];
      if($red["parent_room"] != '0'){
        $new_rooms_map[$red["id"]]["parent_id"] = $red["parent_room"];
      }
      $new_rooms_map[$red["id"]]["room_numbers"] = [];
    }
    // Pushing to real rooms array and adding count to map
    $new_real_rooms = [];
    $new_rooms = explode(",", $new_rooms);
    for($j=0;$j<sizeof($new_rooms);$j++){
      $room = $new_rooms[$j];
      array_push($new_real_rooms, $new_rooms_map[$room]["parent_id"]);
      $new_rooms_map[$room]["count"] += 1;
    }
    // Adding old price to new map
    for($j=0;$j<sizeof($old_room_data);$j++){
      $room = $old_room_data[$j]->id;
      if(isset($new_rooms_map[$room]))
        $new_rooms_map[$room]["price"] = $old_room_data[$j]->price;
    }
    // If a room still doesn't have a price, add the price of the coresponding old room
    $old_rooms = explode(",", $old_data["rooms"]);
    for($j=0;$j<sizeof($new_rooms);$j++){
      if(!(isset($new_rooms_map[$new_rooms[$j]]["price"]))){
        $old_room = $old_rooms[$j];
        for($x=0;$x<sizeof($old_room_data);$x++){ // Ugly way of finding old room price
          if($old_room_data[$x]->id == $old_room){
            $new_rooms_map[$new_rooms[$j]]["price"] = $old_room_data[$x]->price;
            break;
          }
        }
      }
    }

    // Making room data array
    $new_room_data = [];
    foreach($new_rooms_map as $key => $values){
      array_push($new_room_data, $values);
    }

    // Really ugly way of adding room numbers to room array
    $new_room_numbers = explode(",", $new_data["room_numbers"]);

    for($i=0;$i<sizeof($new_room_data);$i++){
      for($j=0;$j<sizeof($new_real_rooms);$j++){
        if($new_room_data[$i]["parent_id"] == $new_real_rooms[$j]){
          array_push($new_room_data[$i]["room_numbers"], $new_room_numbers[$j]);
        }
      }
    }

    $real_rooms = implode(",", $new_real_rooms);
    $room_data = json_encode($new_room_data);

    $sql = "UPDATE reservations_$lcode SET
      room_data = '$room_data',
      real_rooms = '$real_rooms'
      WHERE reservation_code = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);

    // New new data
    $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);

    if($old_data["real_rooms"] != $new_data["real_rooms"] || $old_data["date_arrival"] != $new_data["date_arrival"] || $old_data["date_departure"] != $new_data["date_departure"])
    {
      // Updating avail in database and on WB (old data)
      $real_rooms_list = explode(",", $old_data["real_rooms"]);
      $date_arrival = $old_data["date_arrival"];
      $date_departure = $old_data["date_departure"];
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
      plansAvailUpdate($lcode, $date_arrival, $avail_date_departure, $avail_values, $avail_rooms, 1, $konekcija);
      wubookAvailUpdate($lcode, $account, $date_arrival, $avail_date_departure, $avail_rooms, $konekcija);

      // Updating avail in database and on WB (new data)
      $real_rooms_list = explode(",", $new_data["real_rooms"]);
      $date_arrival = $new_data["date_arrival"];
      $date_departure = $new_data["date_departure"];
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
      wubookAvailUpdate($lcode, $account, $date_arrival, $avail_date_departure, $avail_rooms, $konekcija);
    }
  }
}

if($action == "reservationGuestStatus")
{
  $action = "reservation"; //Simplifying changelog

  $guest_status = checkPost("guest_status");

  // Remember data
  $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);


  $sql = "UPDATE reservations_$lcode SET
    guest_status = '$guest_status'
    WHERE reservation_code = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
  else {
    // New data
    $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
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

  // Remember data
  $sql = "SELECT * FROM guests_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  $sql = "UPDATE guests_$lcode SET
    name = '$name',
    surname = '$surname',
    email = '$email',
    phone = '$phone',
    country_of_residence = '$country_of_residence',
    date_of_birth = '$date_of_birth',
    gender = '$gender',
    host_again = $host_again,
    note = '$note'
    WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
  else {
    // New data
    $sql = "SELECT * FROM guests_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
}

if($action == "guestStatus")
{
  $action = "guest"; //Simplifying changelog

  $host_again = checkPost("status");

  // Remember data
  $sql = "SELECT * FROM guests_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  $sql = "UPDATE guests_$lcode SET
    host_again = $host_again
    WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
  else {
    // New data
    $sql = "SELECT * FROM guests_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
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

  // Remember data
  $sql = "SELECT * FROM invoices_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  $sql = "UPDATE invoices_$lcode SET
    user = '$invoice_user',
    type = '$type',
    mark = '$mark',
    status = '$status',
    issued = '$issued',
    delivery = '$delivery',
    payment_type = '$payment_type',
    name = '$name',
    pib = '$pib',
    mb = '$mb',
    address = '$address',
    email = '$email',
    phone = '$phone',
    reservation_name = '$reservation_name',
    services = '$services',
    price = '$price',
    note = '$note'
    WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
  else {
    // New data
    $sql = "SELECT * FROM invoices_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
}

if($action == "invoiceStatus")
{
  $action = "invoice"; //Simplifying changelog

  $status = checkPost("status");

  // Remember data
  $sql = "SELECT * FROM invoices_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  $sql = "UPDATE invoices_$lcode SET
    status = '$status'
    WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
  else {
    // New data
    $sql = "SELECT * FROM invoices_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
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

  // Remember data
  $sql = "SELECT * FROM promocodes_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  $sql = "UPDATE promocodes_$lcode SET
  name = '$name',
  code = '$code',
  target = '$target',
  value = '$value',
  type = '$type',
  description = '$description'
  WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
  else {
    // New data
    $sql = "SELECT * FROM promocodes_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
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

  // Remember data
  $sql = "SELECT * FROM policies_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  $sql = "UPDATE policies_$lcode SET
  name = '$name',
  type = '$type',
  value = '$value',
  enableFreeDays = '$enableFreeDays',
  freeDays = '$freeDays',
  description = '$description'
  WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
  else {
    // New data
    $sql = "SELECT * FROM policies_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
}

if($action == "category")
{
  $sql = "SELECT *
  FROM categories_$lcode
  WHERE id = $id
  LIMIT 1
  ";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  $name = checkPost("name");
  $parent_id = checkPost("parent_id");
  $sql = "UPDATE categories_$lcode SET
  `name` = '$name',
  `parent_id` = '$parent_id'
  WHERE id = $id";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
  {
    $sql = "SELECT * FROM categories_$lcode WHERE id = $id";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
  else {
    fatal_error("Database error", 500); // Server failed
  }
}

if($action == "article")
{
  $sql = "SELECT *
  FROM articles_$lcode
  WHERE id = $id
  LIMIT 1
  ";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);
  //provera ko je napravio je bila ovde
  $category_id = checkPost("category_id");
  $barcode = checkPost("barcode");
  $code = checkPost("code");
  $tax_rate = checkPost("tax_rate");
  $description = checkPost("description");
  $class = checkPost("class");
  $price = checkPost("price");
  $sql = "UPDATE articles_$lcode SET
  `category_id` = '$category_id',
  `barcode` = '$barcode',
  `code` = '$code',
  `tax_rate` = '$tax_rate',
  `description` = '$description',
  `class` = '$class',
  `price` = '$price'
  WHERE id = $id";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
  {
    $sql = "SELECT * FROM articles_$lcode WHERE id = $id";
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
  $variation = checkPost("variation");
  $variation_type = checkPost("variation_type");
  $policy = checkPost("policy");
  $engine = checkPost("booking_engine");
  $board = checkPost("board");
  $restriction_plan = checkPost("restriction_plan");
  $description = checkPost("description");

  // Remember data
  $sql = "SELECT * FROM prices_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  $sql = "UPDATE prices_$lcode SET
  name = '$name',
  variation = '$variation',
  variation_type = '$variation_type',
  policy = '$policy',
  booking_engine = '$engine',
  board = '$board',
  restriction_plan = '$restriction_plan',
  description = '$description'
  WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
  else {
    // New data
    $sql = "SELECT * FROM prices_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
}

if($action == "restrictionPlan")
{
  $name = checkPost("name");

  // Remember data
  $sql = "SELECT * FROM restrictions_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  $sql = "UPDATE restrictions_$lcode SET
  name = '$name'
  WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
  else {
    // New data
    $sql = "SELECT * FROM restrictions_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
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
  $image_links = json_decode(checkPost("image_links"));
  $files_count = checkPost("files_count");
  for($i=0;$i<$files_count;$i++){
    array_push($image_links, saveImage("file_$i", $lcode . "_" . time() . $i, "/beta/images/"));
  }
  $images = json_encode($image_links);

  // Remember data
  $sql = "SELECT * FROM rooms_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  $sql = "UPDATE rooms_$lcode SET
  name = '$name',
  shortname = '$shortname',
  type = '$type',
  price = '$price',
  availability = '$availability',
  booking_engine = '$booking_engine',
  occupancy = '$occupancy',
  area = '$area',
  bathrooms = '$bathrooms',
  houserooms = '$houserooms',
  room_numbers = '$room_numbers',
  linked_room = '$linked_room',
  additional_prices = '$additional_prices',
  description = '$description',
  images = '$images',
  amenities = '$amenities'
  WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
  else {
    // New data
    $sql = "SELECT * FROM rooms_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
}

if($action == "amenities")
{
  $action = "";
  $amenities = json_decode(checkPost("amenities"));
  $sql = "SELECT id, amenities FROM rooms_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $id = $red["id"];
    $room_amenities = json_decode($red["amenities"]);
    $room_amenities = array_merge($room_amenities, $amenities);
    $room_amenities = array_unique($room_amenities);
    $room_amenities = json_encode($room_amenities);
    $sql = "UPDATE rooms_$lcode SET amenities = '$room_amenities' WHERE id = '$id'";
    mysqli_query($konekcija, $sql);
  }
}

if($action == "roomStatus")
{
  $action = "room"; //Simplifying changelog

  $status = checkPost("status");
  $room_number = checkPost("room_number");

  // Remember data
  $sql = "SELECT * FROM rooms_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  $room_status = explode(",", $old_data["status"]);
  $room_status[$room_number] = $status;
  $room_status = implode(",", $room_status);
  $sql = "UPDATE rooms_$lcode SET
  status = '$room_status'
  WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
  else {
    // New data
    $sql = "SELECT * FROM rooms_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
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
  if(checkPostExists("img_link")){
    $image = checkPost("image");
  }
  else {
    $image = saveImage("image", $lcode . "_" . time(), "/beta/images/");
  }

  // Remember data
  $sql = "SELECT * FROM extras_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  $sql = "UPDATE extras_$lcode SET
  name = '$name',
  pricing = '$pricing',
  price = '$price',
  type = '$type',
  daily = '$daily',
  description = '$description',
  restriction_plan = '$restriction_plan',
  dfrom = '$dfrom',
  dto = '$dto',
  rooms = '$rooms',
  specific_rooms = '$specific_rooms',
  tax = '$tax',
  image = '$image'
  WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
  else {
    // New data
    $sql = "SELECT * FROM extras_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
}

if($action == "channel")
{
  $name = checkPost("name");
  $commission = checkPost("commission");
  // Remember data
  $sql = "SELECT * FROM channels_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  $sql = "UPDATE channels_$lcode SET
  name = '$name',
  commission = '$commission'
  WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
  else {
    // New data
    $sql = "SELECT * FROM channels_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
}

if($action == "user")
{
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

  // Remember data
  $sql = "SELECT * FROM all_users WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  $sql = "UPDATE all_users SET
    properties = '$user_properties',
    reservations = $user_reservations,
    guests = $user_guests,
    invoices = $user_invoices,
    prices = $user_prices,
    restrictions = $user_restrictions,
    avail = $user_avail,
    rooms = $user_rooms,
    channels = $user_channels,
    statistics = $user_statistics,
    changelog = $user_changelog,
    client_name = '$user_name'
    WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
  else {
    // New data
    $sql = "SELECT * FROM all_users WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
}

if($action == "avail")
{
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $rooms = json_decode(checkPost("rooms"));
  $values = to_array(json_decode(checkPost("values")));
  $variation_type = checkPost("variation_type");

  // Remember data
  $old_data = [];
  $sql = "SELECT * FROM avail_values_$lcode WHERE avail_date >= '$dfrom' AND avail_date <= '$dto'";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($old_data, $red);
  }

  plansAvailUpdate($lcode, $dfrom, $dto, $values, $rooms, $variation_type, $konekcija);
  wubookAvailUpdate($lcode, $account, $dfrom, $dto, $rooms, $konekcija);

  // Remember new data
  $new_data = [];
  $sql = "SELECT * FROM avail_values_$lcode WHERE avail_date >= '$dfrom' AND avail_date <= '$dto'";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($new_data, $red);
  }
}

if($action == "price")
{
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $id = checkPost("pid");
  $rooms = json_decode(checkPost("rooms"));
  $values = to_array(json_decode(checkPost("values")));
  $variation_type = checkPost("variation_type");

  // Remember data
  $old_data = [];
  $sql = "SELECT * FROM prices_values_$lcode WHERE price_date >= '$dfrom' AND price_date <= '$dto' AND id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($old_data, $red);
  }

  plansPriceUpdate($lcode, $dfrom, $dto, $id, $values, $rooms, $variation_type, $konekcija);
  wubookPriceUpdate($lcode, $account, $dfrom, $dto, $id, $rooms, $konekcija);

  // Remember new data
  $new_data = [];
  $sql = "SELECT * FROM prices_values_$lcode WHERE price_date >= '$dfrom' AND price_date <= '$dto' AND id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($new_data, $red);
  }
}

if($action == "priceVirtual")
{
  $action = "pricingPlan"; //Simplifying changelog

  $id = checkPost("pid");
  $variation = checkPost("variation");
  $variation_type = checkPost("variation_type");

  // Remember data
  $sql = "SELECT * FROM prices_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  // Wubook update
  $wubook_values = [];
  $wubook_values["pid"] = $id;
  $wubook_values["variation"] = $variation;
  $wubook_values["variation_type"] = $variation_type;
  $wubook_values = json_decode(json_encode($wubook_values));
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  makeRequest("mod_vplans", array($userToken, $lcode, array($wubook_values)));
  makeReleaseRequest("release_token", array($userToken));

  $sql = "UPDATE prices_$lcode SET variation = $variation, variation_type = $variation_type WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
  else {
    // New data
    $sql = "SELECT * FROM prices_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
}

if($action == "yieldPrices")
{
  $action = "price"; //Simplifying changelog

  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $id = checkPost("plan_id");
  $room = checkPost("room_id");
  // Formating some valuess
  $rooms = array($room);
  $value = checkPost("value");
  $values = [];
  $values[$room] = $value;
  $variation_type = checkPost("variation_type");

  // Remember data
  $old_data = [];
  $sql = "SELECT * FROM prices_values_$lcode WHERE price_date >= '$dfrom' AND price_date <= '$dto' AND id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($old_data, $red);
  }

  plansPriceUpdate($lcode, $dfrom, $dto, $id, $values, $rooms, $variation_type, $konekcija);
  wubookPriceUpdate($lcode, $account, $dfrom, $dto, $id, $rooms, $konekcija);

  // Remember new data
  $new_data = [];
  $sql = "SELECT * FROM prices_values_$lcode WHERE price_date >= '$dfrom' AND price_date <= '$dto' AND id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($new_data, $red);
  }
}

if($action == "restriction")
{
  $dfrom = checkPost("dfrom");
  $dto = checkPost("dto");
  $id = checkPost("pid");
  $rooms = json_decode(checkPost("rooms"));
  $values = to_array(json_decode(checkPost("values")));

  // Remember data
  $old_data = [];
  $sql = "SELECT * FROM restrictions_values_$lcode WHERE restriction_date >= '$dfrom' AND restriction_date <= '$dto' AND id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($old_data, $red);
  }

  plansRestrictionUpdate($lcode, $dfrom, $dto, $id, $values, $rooms, $konekcija);
  wubookRestrictionUpdate($lcode, $account, $dfrom, $dto, $id, $rooms, $konekcija);

  // Remember new data
  $new_data = [];
  $sql = "SELECT * FROM restrictions_values_$lcode WHERE restriction_date >= '$dfrom' AND restriction_date <= '$dto' AND id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($new_data, $red);
  }
}

if($action == "restrictionCompact")
{
  $action = "restrictionPlan"; //Simplifying changelog

  $id = checkPost("pid");
  $values = json_decode(checkPost("values"));

  // Remember data
  $sql = "SELECT * FROM restrictions_$lcode WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);

  // Wubook update
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  makeRequest("rplan_update_rplan_rules", array($userToken, $lcode, $id, $values));
  makeReleaseRequest("release_token", array($userToken));

  $new_values = json_encode($values);
  $sql = "UPDATE restrictions_$lcode SET rules = '$new_values' WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
  else {
    // New data
    $sql = "SELECT * FROM restrictions_$lcode WHERE id = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $new_data = mysqli_fetch_assoc($rezultat);
  }
}

// Changelog
if($action != ""){
  $data_type = $action;
  $ch_action = "edit";
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
    $ret_val["data"] = [];
    $ret_val["data"]["id"] = -1;
    //fatal_error("Changelog error", 500); // Server failed
  }
}


echo json_encode($ret_val);
$konekcija->close();

?>
