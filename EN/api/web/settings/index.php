<?php

require '../../main.php';


function initProperty($account, $lcode, $konekcija){


  $sql = "CREATE TABLE rooms_$lcode
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

  $sql = "CREATE TABLE channels_$lcode
  (
    id VARCHAR(63) NOT NULL,
    ctype INT,
    name VARCHAR(255),
    tag VARCHAR(255),
    logo VARCHAR(255),
    commission FLOAT,
    hotel_id VARCHAR(255),
    created_by VARCHAR(255),
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE prices_$lcode
  (
    id VARCHAR(63) NOT NULL,
    name VARCHAR(255),
    type VARCHAR(255),
    variation VARCHAR(63),
    variation_type VARCHAR(63),
    vpid VARCHAR(63),
    description TEXT,
    policy INT,
    booking_engine INT,
    board VARCHAR(63),
    restriction_plan VARCHAR(63),
    created_by VARCHAR(255),
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE restrictions_$lcode
  (
    id VARCHAR(63) NOT NULL,
    name VARCHAR(255),
    type VARCHAR(255),
    rules TEXT,
    created_by VARCHAR(255),
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE changelog_$lcode
  (
    id INT NOT NULL AUTO_INCREMENT,
    data_type VARCHAR(63),
    action VARCHAR(63),
    old_data TEXT,
    new_data TEXT,
    undone INT,
    created_by VARCHAR(255),
    created_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE reservations_$lcode
  (
      reservation_code VARCHAR(63) NOT NULL,
      status INT,
      was_modified INT,
      modified_reservation VARCHAR(63),
      new_reservation_code VARCHAR(63),
      date_received DATE,
      time_received TIME,
      date_arrival DATE,
      date_departure DATE,
      nights INT,
      rooms TEXT,
      room_data TEXT,
      real_rooms TEXT,
      room_numbers TEXT,
      men INT,
      children INT,
      guest_ids TEXT,
      customer_name VARCHAR(255),
      customer_surname VARCHAR(255),
      customer_mail VARCHAR(255),
      customer_phone VARCHAR(255),
      customer_country VARCHAR(255),
      customer_address VARCHAR(255),
      customer_zip VARCHAR(255),
      note TEXT,
      payment_gateway_fee TEXT,
      reservation_price FLOAT,
      services TEXT,
      services_price FLOAT,
      total_price FLOAT,
      pricing_plan VARCHAR(63),
      discount TEXT,
      invoices TEXT,
      cc_info INT,
      guest_status VARCHAR(63),
      date_canceled DATE,
      deleted_advance INT,
      addons_list VARCHAR(255),
      id_woodoo VARCHAR(255),
      channel_reservation_code VARCHAR(255),
      additional_data TEXT,
      created_by VARCHAR(255),
      PRIMARY KEY (reservation_code)
    )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE guests_$lcode
  (
      id INT NOT NULL AUTO_INCREMENT,
      name VARCHAR(255),
      surname VARCHAR(255),
      email VARCHAR(255),
      phone VARCHAR(255),
      country_of_residence VARCHAR(7),
      place_of_residence VARCHAR(255),
      address TEXT,
      zip VARCHAR(255),
      country_of_birth VARCHAR(7),
      date_of_birth DATE,
      gender VARCHAR(15),
      host_again INT,
      note TEXT,
      total_arrivals INT,
      total_nights INT,
      total_paid INT,
      registration_data TEXT,
      created_by VARCHAR(255),
      PRIMARY KEY (id)
    )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE invoices_$lcode
  (
      id INT NOT NULL AUTO_INCREMENT,
      invoice_number INT,
      invoice_year INT,
      created_date DATE,
      created_time TIME,
      user VARCHAR(255),
      type VARCHAR(255),
      mark VARCHAR(255),
      room_id VARCHAR(63) NOT NULL DEFAULT '',
      status VARCHAR(255),
      issued DATE,
      delivery DATE,
      payment_type INT,
      name VARCHAR(255),
      pib VARCHAR(255),
      mb VARCHAR(255),
      address VARCHAR(255),
      email VARCHAR(255),
      phone VARCHAR(255),
      reservation_name VARCHAR(255),
      services TEXT,
      price FLOAT,
      note TEXT,
      reservation_code VARCHAR(63),
      created_by VARCHAR(255),
      PRIMARY KEY (id)
    )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE extras_$lcode
  (
      id INT NOT NULL AUTO_INCREMENT,
      name VARCHAR(255),
      description TEXT,
      type VARCHAR(63),
      price FLOAT,
      pricing INT,
      daily INT,
      dfrom DATE,
      dto DATE,
      restriction_plan VARCHAR(63),
      rooms TEXT,
      specific_rooms TEXT,
      image TEXT,
      tax FLOAT,
      created_by VARCHAR(255),
      PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE policies_$lcode
  (
    id INT NOT NULL AUTO_INCREMENT,
    name varchar(100),
    type varchar(100),
    value int(11),
    freeDays int(11),
    enableFreeDays tinyint(1),
    description text,
    created_by VARCHAR(255),
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);
  // Insert initial policy
  $sql = "INSERT INTO policies_$lcode (name, type, value, enableFreeDays, freeDays, description, created_by) VALUES
  (
    'Osnovna politika',
    'firstNight',
    '0',
    '0',
    '0',
    'U slučaju otkazivanja, zadržava se pravo naplate prve noći rezervacije',
    '1'
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE promocodes_$lcode
  (
    id INT NOT NULL AUTO_INCREMENT,
    code varchar(30),
    name varchar(30),
    target varchar(30),
    value varchar(30),
    description varchar(150),
    type varchar(20),
    created_by VARCHAR(255),
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  // Emails
  $sql = "SELECT email FROM all_users WHERE account = '$account' AND status = 1";
  $rezultat = mysqli_query($konekcija, $sql);
  $email = mysqli_fetch_assoc($rezultat);
  $email = $email["email"];

  $sql = "INSERT INTO all_client_emails VALUES (
    '$lcode',
    '$account',
    0,
    '$email',
    0,
    0,
    0,
    0,
    0
  )";
  mysqli_query($konekcija, $sql);

  $sql = "INSERT INTO all_guest_emails VALUES (
    '$lcode',
    '$account',
    0,
    '',
    '',
    0,
    '',
    '',
    0,
    '',
    '',
    1
  )";
  mysqli_query($konekcija, $sql);


  // Inserting wubook data
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));


  // Rooms
  $rooms = makeRequest("fetch_rooms", array($userToken, $lcode, 0));
  $colors = ["4286f4","0049bf","13b536","157c26","673eef","2e00c9","e59900","ddc133","ef2f2f","8e0c0c"];
  $cal_real_rooms = [];
  $cal_single_rooms = [];
  for($i=0;$i<sizeof($rooms);$i++){
    $id = $rooms[$i]["id"];
    $name = $rooms[$i]["name"];
    $shortname = $rooms[$i]["shortname"];
    $occupancy = $rooms[$i]["occupancy"];
    $price = $rooms[$i]["price"];
    $availability = $rooms[$i]["availability"];
    $color = $colors[$i%10];
    $parent_room = $rooms[$i]["subroom"];
    $room_numbers = [];
    $room_status = [];
    array_push($cal_real_rooms, $id);
    for($j=1;$j<=$availability;$j++){
      array_push($room_numbers, $j);
      array_push($room_status, "clean");
      array_push($cal_single_rooms, $id . "_" . ($j - 1));
    }
    $room_numbers = implode(",", $room_numbers);
    $room_status = implode(",", $room_status);
    $booking_engine = 1;
    if($parent_room != "0"){
      $booking_engine = 0;
    }

    $sql = "INSERT INTO rooms_$lcode VALUES (
      '$id',
      '$name',
      '$shortname',
      'apartment',
      $price,
      $availability,
      $occupancy,
      '',
      '[]',
      0,
      0,
      '[]',
      '[]',
      $booking_engine,
      '$room_numbers',
      '{\"active\":0, \"avail\":0, \"price\":0, \"restrictions\":0, \"variation\": 0, \"variation_type\": \"fixed\"}',
      '$parent_room',
      '{\"active\": 0, \"room\": -1, \"variation\": 0, \"variation_type\": \"fixed\"}',
      '$room_status',
      'Wubook'
    )";
    mysqli_query($konekcija, $sql);
  }

  // Custom calendar
  $custom_calendar = [];
  $custom_calendar["type"] = "room_types";
  $custom_calendar["avail"] = "0";
  $custom_calendar["price"] = "1";
  $custom_calendar["min"] = "0";
  $custom_calendar["room_name"] = "1";
  $custom_calendar["room_type"] = "1";
  $custom_calendar["room_status"] = "1";
  $custom_calendar["room_types"] = $cal_real_rooms;
  $custom_calendar["single_rooms"] = $cal_single_rooms;
  $custom_calendar["days"] = "21";
  $custom_calendar = json_encode($custom_calendar);
  $sql = "UPDATE all_properties SET custom_calendar = '$custom_calendar' WHERE lcode = '$lcode'";
  mysqli_query($konekcija, $sql);

  // Channels
  $channels = makeRequest("get_otas", array($userToken, $lcode));
  for($i=0;$i<sizeof($channels);$i++){
    $id = $channels[$i]["id"];
    $ctype = $channels[$i]["ctype"];
    $hotel_id = $channels[$i]["channel_hid"];
    $sql = "SELECT name, logo, commission
            FROM all_channels
            WHERE ctype = $ctype
            LIMIT 1";
    $rezultat = mysqli_query($konekcija, $sql); // Default channel data
    $red = mysqli_fetch_assoc($rezultat);
    $name = $red["name"];
    $tag = $channels[$i]["tag"];
    if($tag != "")
      $name = $name . " (" . $tag . ")";
    $logo = $red["logo"];
    $commission = $red["commission"];
    $sql = "INSERT INTO channels_$lcode VALUES (
      '$id',
      $ctype,
      '$name',
      '$tag',
      '$logo',
      $commission,
      '$hotel_id',
      'Wubook'
    )";
    mysqli_query($konekcija, $sql);
  }

  // Pricing plans
  $pricing_plans = makeRequest("get_pricing_plans", array($userToken, $lcode));
  $default_price = "";
  for($i=0;$i<sizeof($pricing_plans);$i++){
    $plan_id = $pricing_plans[$i]["id"];
    $plan_name = $pricing_plans[$i]["name"];
    $plan_type = "daily";
    $plan_variation = "";
    $plan_variation_type = "";
    $plan_vpid = "";
    if(isset($pricing_plans[$i]["variation"])){
      $plan_type = "virtual";
      $plan_variation = $pricing_plans[$i]["variation"];
      $plan_variation_type = $pricing_plans[$i]["variation_type"];
      $plan_vpid = $pricing_plans[$i]["vpid"];
    }
    if($plan_type == "daily" && $default_price == "")
      $default_price = $plan_id;
    $sql = "INSERT INTO prices_$lcode VALUES(
      '$plan_id',
      '$plan_name',
      '$plan_type',
      '$plan_variation',
      '$plan_variation_type',
      '$plan_vpid',
      '',
      1,
      0,
      '',
      '',
      'Wubook'
    )";
    mysqli_query($konekcija, $sql);
  }
  // Set default plan
  $sql = "UPDATE all_properties SET default_price = '$default_price' WHERE lcode = '$lcode'";
  mysqli_query($konekcija, $sql);

  // Restriction plans
  $restriction_plans = makeRequest("rplan_rplans", array($userToken, $lcode));
  $sql = "INSERT INTO restrictions_$lcode VALUES(
    '1',
    'Osnovne restrikcije',
    'daily',
    '{}',
    'Wubook'
  )";
  mysqli_query($konekcija, $sql);
  for($i=0;$i<sizeof($restriction_plans);$i++){
    $plan_id = $restriction_plans[$i]["id"];
    $plan_name = $restriction_plans[$i]["name"];
    $plan_type = "daily";
    $plan_rules = "{}";
    if(isset($restriction_plans[$i]["rules"])){
      $plan_type = "compact";
      $plan_rules = json_encode($restriction_plans[$i]["rules"]);
    }
    $sql = "INSERT INTO restrictions_$lcode VALUES(
      '$plan_id',
      '$plan_name',
      '$plan_type',
      '$plan_rules',
      'Wubook'
    )";
    mysqli_query($konekcija, $sql);
  }

  // Fetching all reservations



  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));

  makeUncheckedRequest("push_activation", array($userToken, $lcode, "https://admin.otasync.me/api/notifications/$account"));
  makeUncheckedRequest("unmark_reservations", array($userToken, $lcode, "01/01/2019"));
  $actions = 5; // Already did 5 actions with token
  $reservations = makeRequest("fetch_new_bookings", array($userToken, $lcode, 1, 1));
  $modified_sqls = [];
  while(sizeof($reservations) > 0){
    for($i=0;$i<sizeof($reservations);$i++){ // Insert all
      // Res Data
      $reservation = $reservations[$i];
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
      $rezultat = mysqli_query($konekcija, $sql);
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
      $rezultat = mysqli_query($konekcija, $sql);
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

      $guest_ids = insertWubookGuest($lcode, $reservation, $konekcija);
      $customer_name = mysqli_real_escape_string($konekcija, $reservation["customer_name"]);
      $customer_surname = mysqli_real_escape_string($konekcija, $reservation["customer_surname"]);
      $customer_mail = mysqli_real_escape_string($konekcija, $reservation["customer_mail"]);
      $customer_phone = mysqli_real_escape_string($konekcija, $reservation["customer_phone"]);
      $customer_country = mysqli_real_escape_string($konekcija, $reservation["customer_country"]);
      $customer_address = mysqli_real_escape_string($konekcija, $reservation["customer_address"]);
      $customer_zip = mysqli_real_escape_string($konekcija, $reservation["customer_zip"]);
      $note = mysqli_real_escape_string($konekcija, $reservation["customer_notes"]);

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
        $additional_data = mysqli_real_escape_string($konekcija, json_encode($reservation["ancillary"]));
      $created_by = "Wubook";

      // Update old reservation
      if($modified_reservations != "" && $modified_reservations != $reservation_code){
        array_push($modified_sqls, "UPDATE reservations_$lcode SET new_reservation_code = '$reservation_code' WHERE reservation_code = '$modified_reservations'");
      }

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
      mysqli_query($konekcija, $sql);
    }
    $reservations = makeRequest("fetch_new_bookings", array($userToken, $lcode, 1, 1)); // Fetch next
    $actions += 1;
    if($actions > 50){ // Reset token
      makeReleaseRequest("release_token", array($userToken));
      $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
      $actions = 0;
    }
  }
  for($i=0;$i<sizeof($modified_sqls);$i++){
    mysqli_query($konekcija, $modified_sqls[$i]);
  }
  makeRequest("release_token", array($userToken));



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


  // Articles

  $sql = "CREATE TABLE categories_$lcode
  (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(63) NOT NULL,
    parent_id INT,
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE articles_$lcode
  (
    id INT NOT NULL AUTO_INCREMENT,
    category_id INT NOT NULL,
    barcode INT NOT NULL DEFAULT -1,
    code INT NOT NULL,
    tax_rate TINYINT NOT NULL DEFAULT 0,
    description VARCHAR(32),
    class TINYINT NOT NULL DEFAULT 0,
    price FLOAT NOT NULL,
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "INSERT INTO categories_$lcode (name, parent_id) VALUES ('Artikli', 0)";
  mysqli_query($konekcija, $sql);

  // Yield

  $sql = "CREATE TABLE yield_variations_$lcode
  (
    id INT NOT NULL AUTO_INCREMENT,
    variation_type INT,
    variation_value FLOAT,
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  // Engine

  // Get user defaults
  $sql = "SELECT email, phone, address, name, latitude, longitude, logo FROM all_properties WHERE lcode = '$lcode'";
  $rezultat = mysqli_query($konekcija, $sql);
  $property = mysqli_fetch_assoc($rezultat);
  $sql = "INSERT INTO engine_confirmation VALUES (
    '$lcode',
    0,
    1,
    1,
    1,
    1,
    1,
    1,
    '12:00-16:00'
  )";
  mysqli_query($konekcija, $sql);

  $address = $property["address"];
  $phone = $property["phone"];
  $email = $property["email"];
  $longitude = $property["longitude"];
  $latitude = $property["latitude"];
  $sql = "INSERT INTO engine_contact VALUES (
    '$lcode',
    '$address',
    '$phone',
    '$email',
    '',
    '',
    '',
    '',
    '$longitude',
    '$latitude'
  )";
  mysqli_query($konekcija, $sql);

  $sql = "INSERT INTO engine_footer VALUES (
    '$lcode',
    ''
  )";
  mysqli_query($konekcija, $sql);

  $name = $property["name"];
  $sql = "INSERT INTO engine_header VALUES (
    '$lcode',
    '$name',
    ''
  )";
  mysqli_query($konekcija, $sql);

  $sql = "INSERT INTO engine_messages VALUES (
    '$lcode',
    '',
    '',
    '',
    ''
  )";
  mysqli_query($konekcija, $sql);

  $logo = $property["logo"];
  $sql = "INSERT INTO engine_styles VALUES (
    '$lcode',
    '#2c3e50',
    5,
    '$logo',
    ''
  )";
  mysqli_query($konekcija, $sql);

  $sql = "INSERT INTO engine_selectdates VALUES (
    '$lcode',
    1,
    '12:00',
    0,
    0
  )";
  mysqli_query($konekcija, $sql);


}


if($_SERVER['REQUEST_METHOD'] == "OPTIONS"){
    http_response_code(200);
    die();
}
else if ($_SERVER['REQUEST_METHOD'] != "POST"){
  fatal_error("Invalid method", 405);
}

$key = checkPost("key");
$account = checkPost("account");
$konekcija = connectToDB();
$action = getAction();
$ret_val = [];
$ret_val["status"] = "ok";
$user = getSession($key, $account, $konekcija);
// Check access here

if($action == "property")
{
  $lcode = checkPost("lcode");
  $item = checkPost("item");
  $value = checkPost("value");

  $sql = "UPDATE all_properties SET $item = '$value' WHERE lcode = '$lcode'";
  mysqli_query($konekcija, $sql);
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}

if($action == "logo")
{
  $lcode = checkPost("lcode");
  if(checkPostExists("clear")){
    $url = "";
  }
  else {
    $url = saveImage("logo", $lcode . "_" . time(), "/beta/images/");
  }
  $sql = "UPDATE all_properties SET logo = '$url' WHERE lcode = '$lcode'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}

if($action == "account")
{
  $id = $user["id"];
  $item = checkPost("item");
  $value = checkPost("value");

  $sql = "UPDATE all_users SET $item = '$value' WHERE id = $id";
  mysqli_query($konekcija, $sql);
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}

if($action == "guestEmails")
{
  $lcode = checkPost("lcode");
  $field = checkPost("field");
  $active = checkPost("active");
  $subject = mysqli_real_escape_string($konekcija, checkPost("subject"));
  $text = mysqli_real_escape_string($konekcija, checkPost("text"));
  $active_sql = $field . "_active";
  $subject_sql = $field . "_subject";
  $text_sql = $field . "_text";

  $sql = "UPDATE all_guest_emails SET $active_sql = $active, $subject_sql = '$subject', $text_sql = '$text' WHERE lcode = '$lcode'";
  mysqli_query($konekcija, $sql);
}

if($action == "guestEmailsType")
{
  $lcode = checkPost("lcode");
  $value = checkPost("value");

  $sql = "UPDATE all_guest_emails SET res_type = $value WHERE lcode = '$lcode'";
  mysqli_query($konekcija, $sql);
}

if($action == "clientEmails")
{
  $lcode = checkPost("lcode");
  $item = checkPost("item");
  $value = checkPost("value");

  $sql = "UPDATE all_client_emails SET $item = '$value' WHERE lcode = '$lcode'";
  mysqli_query($konekcija, $sql);
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}

if($action == "sync")
{
  $lcode = checkPost("lcode");
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));

  // Reservations

  makeUncheckedRequest("unmark_reservations", array($userToken, $lcode, "01/01/2020"));
  $actions = 1; // Already did 1 actions with token
  $rezervacijeAPI = makeRequest("fetch_new_bookings", array($userToken, $lcode, 1, 1));
  $duzina = sizeof($rezervacijeAPI);
  while($duzina > 0)
  {
    for($i=0;$i<$duzina;$i++)
    {
      $reservation = $rezervacijeAPI[$i];
      insertWubookReservation($lcode, $account, $reservation);
    }
    $rezervacijeAPI = makeRequest("fetch_new_bookings", array($userToken, $lcode, 1, 1));
    $actions += 1;
    if($actions > 50)
    {
      makeReleaseRequest("release_token", array($userToken));
      $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
      $actions = 0;
    }
    $duzina = sizeof($rezervacijeAPI);
  }
  makeReleaseRequest("release_token", array($userToken));

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

  // Rooms

  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  $rooms = makeRequest("fetch_rooms", array($userToken, $lcode, 0));
  $colors = ["4286f4","0049bf","13b536","157c26","673eef","2e00c9","e59900","ddc133","ef2f2f","8e0c0c"];
  $cal_real_rooms = [];
  $cal_single_rooms = [];
  for($i=0;$i<sizeof($rooms);$i++){
    $id = $rooms[$i]["id"];
    $name = $rooms[$i]["name"];
    $shortname = $rooms[$i]["shortname"];
    $occupancy = $rooms[$i]["occupancy"];
    $price = $rooms[$i]["price"];
    $availability = $rooms[$i]["availability"];
    $color = $colors[$i%10];
    $parent_room = $rooms[$i]["subroom"];
    $room_numbers = [];
    $room_status = [];
    array_push($cal_real_rooms, $id);
    for($j=1;$j<=$availability;$j++){
      array_push($room_numbers, $j);
      array_push($room_status, "clean");
      array_push($cal_single_rooms, $id . "_" . ($j - 1));
    }
    $room_numbers = implode(",", $room_numbers);
    $room_status = implode(",", $room_status);
    $booking_engine = 1;
    if($parent_room != "0"){
      $booking_engine = 0;
    }

    $sql = "INSERT INTO rooms_$lcode VALUES (
      '$id',
      '$name',
      '$shortname',
      'apartment',
      $price,
      $availability,
      $occupancy,
      '',
      '[]',
      0,
      0,
      '[]',
      '[]',
      $booking_engine,
      '$room_numbers',
      '{\"active\":0, \"avail\":0, \"price\":0, \"restrictions\":0, \"variation\": 0, \"variation_type\": \"fixed\"}',
      '$parent_room',
      '{\"active\": 0, \"room\": -1, \"variation\": 0, \"variation_type\": \"fixed\"}',
      '$room_status',
      'Wubook'
    )";
    mysqli_query($konekcija, $sql);
  }

  // Channels
  $channels = makeRequest("get_otas", array($userToken, $lcode));
  for($i=0;$i<sizeof($channels);$i++){
    $id = $channels[$i]["id"];
    $ctype = $channels[$i]["ctype"];
    $hotel_id = $channels[$i]["channel_hid"];
    $sql = "SELECT name, logo, commission
            FROM all_channels
            WHERE ctype = $ctype
            LIMIT 1";
    $rezultat = mysqli_query($konekcija, $sql); // Default channel data
    $red = mysqli_fetch_assoc($rezultat);
    $name = $red["name"];
    $tag = $channels[$i]["tag"];
    if($tag != "")
      $name = $name . " (" . $tag . ")";
    $logo = $red["logo"];
    $commission = $red["commission"];
    $sql = "INSERT INTO channels_$lcode VALUES (
      '$id',
      $ctype,
      '$name',
      '$tag',
      '$logo',
      $commission,
      '$hotel_id',
      'Wubook'
    )";
    mysqli_query($konekcija, $sql);
  }

  // Pricing plans
  $pricing_plans = makeRequest("get_pricing_plans", array($userToken, $lcode));
  $default_price = "";
  for($i=0;$i<sizeof($pricing_plans);$i++){
    $plan_id = $pricing_plans[$i]["id"];
    $plan_name = $pricing_plans[$i]["name"];
    $plan_type = "daily";
    $plan_variation = "";
    $plan_variation_type = "";
    $plan_vpid = "";
    if(isset($pricing_plans[$i]["variation"])){
      $plan_type = "virtual";
      $plan_variation = $pricing_plans[$i]["variation"];
      $plan_variation_type = $pricing_plans[$i]["variation_type"];
      $plan_vpid = $pricing_plans[$i]["vpid"];
    }
    if($plan_type == "daily" && $default_price == "")
      $default_price = $plan_id;
    $sql = "INSERT INTO prices_$lcode VALUES(
      '$plan_id',
      '$plan_name',
      '$plan_type',
      '$plan_variation',
      '$plan_variation_type',
      '$plan_vpid',
      '',
      1,
      0,
      'Wubook'
    )";
    mysqli_query($konekcija, $sql);
  }

  // Restriction plans
  $restriction_plans = makeRequest("rplan_rplans", array($userToken, $lcode));
  for($i=0;$i<sizeof($restriction_plans);$i++){
    $plan_id = $restriction_plans[$i]["id"];
    $plan_name = $restriction_plans[$i]["name"];
    $plan_type = "daily";
    $plan_rules = "{}";
    if(isset($restriction_plans[$i]["rules"])){
      $plan_type = "compact";
      $plan_rules = json_encode($restriction_plans[$i]["rules"]);
    }
    $sql = "INSERT INTO restrictions_$lcode VALUES(
      '$plan_id',
      '$plan_name',
      '$plan_type',
      '$plan_rules',
      'Wubook'
    )";
    mysqli_query($konekcija, $sql);
  }

  // Inserting new properties
  $properties = makeRequest("fetch_properties", array($userToken));
  // Insert all properties
  $all_properties = [];
  $inserted_properties = [];
  foreach ($properties as $key => $value){
    array_push($all_properties, $key);

    $sql = "SELECT COUNT(*) AS cnt FROM all_properties WHERE lcode = '$key'";
    $rezultat = mysqli_query($konekcija,$sql);
    $red = mysqli_fetch_assoc($rezultat);
    $cnt = $red["cnt"];
    if($cnt == 0){
      array_push($inserted_properties, $key);
      $name = mysqli_real_escape_string($konekcija, $value['name']);
      $address = mysqli_real_escape_string($konekcija, $value['address']);
      $zip = $value['zip'];
      $city = mysqli_real_escape_string($konekcija, $value['city']);
      $country = mysqli_real_escape_string($konekcija, $value['country']);
      $email = $value['email'];
      $phone = $value['phone'];
      $latitude = $value['latitude'];
      $longitude = $value['longitude'];
      $custom_calendar = [];
      $sql = "INSERT IGNORE INTO all_properties VALUES
      (
        '$key',
        '$account',
        '$name',
        '',
        '$address',
        '$zip',
        '$city',
        '$country',
        '$email',
        '$phone',
        '$latitude',
        '$longitude',
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        '[0,0,0,0,0,0,0,0,0,0,0,0]',
        '',
        'EUR',
        1,
        '',
        0,
        1,
        0,
        1,
        0
       )";
     $rezultat = mysqli_query($konekcija, $sql);
     if(!$rezultat)
       fatal_error("Failed to insert property.", 500); // Server failed
    }
  }
  $all_properties = implode(",", $all_properties);
  $sql = "UPDATE all_users SET properties = '$all_properties' WHERE account = '$account' AND status = 1";
  mysqli_query($konekcija, $sql);
  for($i=0;$i<sizeof($inserted_properties);$i++){
    initProperty($account, $inserted_properties[$i], $konekcija);
  }


  // Add new reservation code on edited reservations
  $edited_reservations = [];
  $sql = "SELECT reservation_code, modified_reservation FROM reservations_$lcode WHERE modified_reservation != ''";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    if($red["reservation_code"] != $red["modified_reservation"])
      array_push($edited_reservations, $red);
  }
  for($i=0;$i<sizeof($edited_reservations);$i++){
    $old = $edited_reservations[$i]["modified_reservation"];
    $new = $edited_reservations[$i]["reservation_code"];
    $sql = "UPDATE reservations_$lcode SET new_reservation_code = '$new' WHERE reservation_code = '$old'";
    mysqli_query($konekcija, $sql);
  }

  // Fix guest names
  $guests = [];
  $sql = "SELECT * FROM guests_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($guests, $red);
  }
  for($i=0;$i<sizeof($guests);$i++){
    $id = $guests[$i]["id"];
    $sql = "SELECT customer_name, customer_surname FROM reservations_$lcode WHERE guest_ids = '$id' OR guest_ids LIKE '%,$id,%' OR guest_ids LIKE '%,$id' OR guest_ids LIKE '$id,%' LIMIT 1";
    $rezultat = mysqli_query($konekcija, $sql);
    $red = mysqli_fetch_assoc($rezultat);
    $name = $red["customer_name"];
    $surname = $red["customer_surname"];
    if($name != "" && $surname != ""){
      $sql = "UPDATE guests_$lcode SET name = '$name' AND surname = '$surname' WHERE id = '$id'";
      mysqli_query($konekcija, $sql);
    }
  }

}

// All of below should be in edit
/*
if($action == "priceAdd")
{
  $lcode = checkPost("lcode");
  $type = checkPost("type");
  $name = checkPost("name");
  $vpid = checkPost("vpid");
  $variation = checkPost("variation");
  $variation_type = checkPost("variation_type");
  $policy = checkPost("policy");
  $engine = checkPost("engine");
  $description = checkPost("description");
  if($policy == "") // Temp fix
    $policy = 0;

  $sql = "INSERT INTO prices_$lcode (name, type, variation, variation_type, vpid, description, policy, booking_engine) VALUES ('$name', '$type', '$variation', '$variation_type', '$vpid', '$description', '$policy', '$booking_engine')";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}
if($action == "restriction")
{
  $lcode = checkPost("lcode");
  $id = checkPost("id");
  $name = checkPost("name");

  $sql = "UPDATE restrictions_$lcode SET
  name = '$name'
  WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}

if($action == "extra")
{
  $lcode = checkPost("lcode");
  $id = checkPost("id");
  $name = checkPost("name");
  $pricing = checkPost("pricing");
  $price = checkPost("price");
  $type = checkPost("type");
  $daily = checkPost("daily");
  $description = checkPost("description");

  $sql = "UPDATE extras_$lcode SET
  name = '$name',
  pricing = '$pricing',
  price = '$price',
  type = '$type',
  daily = '$daily',
  description = '$description'
  WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}

if($action == "channel")
{
  $lcode = checkPost("lcode");
  $id = checkPost("id");
  $name = checkPost("name");
  $commission = checkPost("commission");

  $sql = "UPDATE channels_$lcode SET
  name = '$name',
  commission = '$commission'
  WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}

if($action == "room")
{
  $lcode = checkPost("lcode");
  $id = checkPost("id");
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
  $description = checkPost("description");
  $amenities = checkPost("amenities");

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
  linked_room = '$linked_room',
  additional_prices = '$additional_prices',
  description = '$description',
  amenities = '$amenities'
  WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}

*/
echo json_encode($ret_val);
$konekcija->close();


?>
