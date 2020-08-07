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

$dfrom = checkPost("dfrom");
$dto = checkPost("dto");
$name = checkPost("name");
$email = checkPost("email");
$message = checkPost("message");

$dfrom = ymdToDmy($dfrom);
$dto = ymdToDmy($dto);

$sql = "SELECT email FROM all_users WHERE lcodes LIKE '%$lcode%' AND status = 1";
$rezultat = mysqli_query($konekcija, $sql);
$red = mysqli_fetch_assoc($rezultat);
$user_email = $red["email"];
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "Reply-to: $email" . "\r\n";
$headers .= 'From: noreply@otasync.me';
$rez = mail($user_email, "Upit za period $dfrom - $dto ($name)", $message, $headers);
if($rez){
  $ret_val["status"] = "ok";
}
else {
  $ret_val["status"] = "Došlo je do greške.";
}
echo json_encode($ret_val);
$konekcija->close();

?>
