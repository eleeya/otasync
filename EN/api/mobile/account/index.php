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


if(checkPostExists("key") && $action == "login")
  $action = "loginSession";

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
  else if($account == "ME001"){
    $sql = "SELECT * FROM all_properties WHERE agency = 1 OR agency = 3 OR agency = 5";
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
  else if($account == "ME001"){
    $sql = "SELECT * FROM all_properties WHERE agency = 1 OR agency = 3 OR agency = 5";
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

if($action == "forgotPassword")
{
    $email = checkPost("email");
    $username = checkPost("account");
}

echo json_encode($ret_val);

$konekcija->close();


?>
