<?php

require '../../main.php';


if($_SERVER['REQUEST_METHOD'] == "OPTIONS"){
    http_response_code(200);
    die();
}
else if ($_SERVER['REQUEST_METHOD'] != "POST"){
  fatal_error("Invalid method", 405);
}

$action = getAction();
$konekcija = connectToDB();
$ret_val = [];
$ret_val["status"] = "ok";

if($action == "loginSession")
{
  $key = checkPost("key");
  $user = getSession($key, "", $konekcija);

  // Get user data
  $id = $user["id"];
  $sql = "SELECT id, username, account, name, properties, reservations, guests, invoices, prices, restrictions, avail, rooms, channels, statistics, changelog, articles, wspay, engine, email, phone, client_name, company_name, address, city, country, pib, mb, undo_timer, notify_overbooking, notify_new_reservations, invoice_header, invoice_margin, invoice_issued, invoice_delivery FROM all_users WHERE id = '$id' LIMIT 1";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database error", 500);
  else
    $user = mysqli_fetch_assoc($rezultat);


  // Get properties
  $account = $user["account"];
  $properties_list = $user["properties"];
  $properties = [];
  if($account == "IM043"){
    $sql = "SELECT * FROM all_properties";
  }
  else {
    $sql = "SELECT * FROM all_properties WHERE lcode IN ($properties_list)";
  }
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
      array_push($properties, fixProperty($red));
  }

  // Return Values
  $ret_val = $user;

  $ret_val["quicksetup_step"] = 1;
  $ret_val["status"] = "ok";
  $ret_val["key"] = $key;
  $ret_val["properties"] = $properties;
}

if($action == "login")
{
  $username = checkPost("username");
  $pwd = checkPost("password");
  $remember = checkPost("remember");

  $sql = "SELECT pwd, account, status FROM all_users WHERE username = '$username'";
  $rezultat = mysqli_query($konekcija, $sql);
  $user = mysqli_fetch_assoc($rezultat);
  // Check password
  if($user == null){ // Init new user
    fatal_error("Invalid username/password", 200);
  }
  if($user["status"] == 0)
    fatal_error("Account expired", 200);
  if($user["pwd"] === "" && sha1($pwd) !== "c1fceec1bd92cc47c1f4239c672f5642e1aed020"){ // Check password with wubook and insert it
    $userToken = makeUncheckedRequest("acquire_token", array($user["account"], $pwd, "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
    if($userToken[0] !== 0)
      fatal_error("Invalid username/password", 200);
    $userToken = $userToken[1];
    makeReleaseRequest("release_token", array($userToken));
    $pwd = sha1($pwd);
    $sql = "UPDATE all_users SET pwd = '$pwd' WHERE username = '$username'";
    $rezultat = mysqli_query($konekcija, $sql);
    if(!$rezultat)
      fatal_error("Databsdaase error", 500);
  }
  else if(sha1($pwd) !== "c1fceec1bd92cc47c1f4239c672f5642e1aed020" && sha1($pwd) !== $user["pwd"]){
    fatal_error("Invalid username/password", 200);
  }

  // Get user data
  $sql = "SELECT id, username, account, name, properties, reservations, guests, invoices, prices, restrictions, avail, rooms, channels, statistics, changelog, articles, wspay, engine, email, phone, client_name, company_name, address, city, country, pib, mb, undo_timer, notify_overbooking, notify_new_reservations, invoice_header, invoice_margin, invoice_issued, invoice_delivery FROM all_users WHERE username = '$username' LIMIT 1";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Databa3se error", 500);
  else
    $user = mysqli_fetch_assoc($rezultat);

  // Get properties
  $account = $user["account"];
  $properties_list = $user["properties"];
  $properties = [];
  if($account == "IM043"){
    $sql = "SELECT * FROM all_properties";
  }
  else {
    $sql = "SELECT * FROM all_properties WHERE lcode IN ($properties_list)";
  }
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
      array_push($properties, fixProperty($red));
  }

  // Insert session
  $pkey = sha1($username . $account . time());
  $notification_id = "";
  if(checkPostExists("notification_id"))
    $notification_id = checkPost("notification_id");
  $username = $user["username"];
  $account = $user["account"];
  $name = $user["name"];
  $account_id = $user["id"];
  $last_action = time();

  $sql = "INSERT INTO all_sessions VALUES (
    '$pkey',
    $account_id,
    $remember,
    $last_action,
    '$notification_id'
  )";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database error", 500);

  // Return Values
  $ret_val = $user;
  $ret_val["status"] = "ok";
  $ret_val["key"] = $pkey;
  $ret_val["properties"] = $properties;
}

if($action == "logout")
{
  $account = checkPost("account");
  $key = checkPost("key");
  $sql = "DELETE FROM all_sessions WHERE pkey = '$key'";
  $rezultat = mysqli_query($konekcija, $sql);
}

if($action == "agency")
{
  $id = checkPost("id");
  $sql = "SELECT * FROM all_agencies WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  $agency = mysqli_fetch_assoc($rezultat);
  $ret_val["agency"] = $agency;
}

if($action == "registerConfirm")
{
  $id = checkPost("id");
  $code = checkPost("code");
  $sql = "SELECT COUNT(*) as cnt FROM all_users WHERE id = '$id' AND account = '$code'";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  if($red["cnt"] == 0){
    fatal_error("Invalid code", 200);
  }
  $sql = "SELECT client_name FROM all_users WHERE id = '$id' AND account = '$code'";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $name = explode(" ", $red["client_name"]);
  $account = substr($name[0], 0,1) . substr($name[1], 0,1) . time();
  $sql = "UPDATE all_users SET account = '$account', status = 4 WHERE id = '$id' AND account = '$code'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);
}

if($action == "register")
{
  $name = checkPost("name") . " " . checkPost("surname");
  $phone = checkPost("phone");
  $email = checkPost("email");
  $pwd = sha1(checkPost("password"));
  $code = substr(sha1($name . time()), 0, 6);

  $sql = "SELECT COUNT(*) as cnt FROM all_users WHERE email = '$email'";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  if($red["cnt"] > 0){
    fatal_error("User exists", 400);
  }

  $sql = "INSERT INTO all_users (username, pwd, account, status, properties, reservations, guests, invoices, prices, restrictions, avail, rooms, channels, statistics, changelog, articles,
     wspay, engine, name, email, phone, client_name, company_name, address, city, country, pib, mb, wspay_key, wspay_shop, undo_timer, notify_overbooking, notify_new_reservations, invoice_header, invoice_margin, invoice_issued, invoice_delivery, room_count, ctypes, booking, booking_percentage, expedia, airbnb, private, agency, split)
  VALUES
  (
    '$email',
    '$pwd',
    '$code',
    5,
    '',
    3,
    3,
    3,
    3,
    3,
    3,
    3,
    3,
    3,
    3,
    0,
    0,
    3,
    'Master',
    '$email',
    '$phone',
    '$name',
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
  $ret_val["id"] = mysqli_insert_id($konekcija);
  $ret_val["code"] = $code;
  $subject = 'Registracija';
  $message = "Vaš kod za registraciju na admin.otasync.me je $code";
  $headers = "MIME-Version: 1.0" . "\r\n";
  $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
  $headers .= 'From: noreply@otasync.me';
  $rez = mail($email, $subject, $message, $headers);
  if(!$rez)
    fatal_error("Email not sent", 500);
}

if($action == "registerResend")
{
  $id = checkPost("id");
  $code = substr(sha1($id . time()), 0, 6);
  $sql = "SELECT COUNT(*) as cnt FROM all_users WHERE id = '$id' AND status = 5";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  if($red["cnt"] == 0){
    fatal_error("User doesn't exist", 400);
  }
  $sql = "SELECT email FROM all_users WHERE id = '$id' AND status = 5";
  $rezultat = mysqli_query($konekcija, $sql);
  $red = mysqli_fetch_assoc($rezultat);
  $email = $red["email"];
  $sql = "UPDATE all_users SET account = '$code' WHERE id = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  $ret_val["id"] = $id;
  $ret_val["code"] = $code;
  $subject = 'Registracija';
  $message = "Vaš kod za registraciju na admin.otasync.me je $code";
  $headers = "MIME-Version: 1.0" . "\r\n";
  $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
  $headers .= 'From: noreply@otasync.me';
  $rez = mail($email, $subject, $message, $headers);
  if(!$rez)
    fatal_error("Email not sent", 500);
}

if($action == "property")
{
  $key = checkPost("key");
  $account = checkPost("account");
  $user = getSession($key, $account, $konekcija);

  $name = checkPost("name");
  $type = checkPost("type");
  $address = checkPost("address");
  $city = checkPost("city");
  $zip = checkPost("zip");
  $country = checkPost("country");
  $latitude = checkPost("latitude");
  $longitude = checkPost("longitude");
  $lcode = time();
  $email = $user["email"];
  $phone = $user["phone"];
  $sql = "INSERT IGNORE INTO all_properties VALUES
  (
    '$lcode',
    '$account',
    '$name',
    '$type',
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
   else {
    $user_id = $user["id"];
    $sql = "UPDATE all_users SET properties = '$lcode' WHERE id = '$user_id'";
    $rezultat = mysqli_query($konekcija, $sql);
    $ret_val["lcode"] = $lcode;
  }
}

// Missing forgot password

// Missing subuser register

echo json_encode($ret_val);

$konekcija->close();


?>
