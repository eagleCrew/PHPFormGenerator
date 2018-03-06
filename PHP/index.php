<!DOCTYPE html >
<?php
session_start();
include('sql/connectsqli.php');
//include sql/connectsqli.php;
EHConnect();
//$mysqli = mysqli_connect("localhost", "eh_read_contacts", "Hkj95*k5W2n4bi@9", "eaglehomes");

$firstname = filter_input(INPUT_POST, 'firstname');
$lastname = filter_input(INPUT_POST, 'lastname');
$email = filter_input(INPUT_POST, 'email');



$display_block = "
    <form method='post' action='index.php'>
    <label for='firstname'>First Name: </label>
    <input type='text' id='firstname' name ='firstname'>
    <label for='lastname'>Last Name: </label>
    <input type='text' id='lastname' name ='lastname'><br><br>
    <label for='email'>Email: </label>
    <input type='text' id='email' name ='email'><br><br>
    <button type='submit' value='Continue' id='continue'>create</button>
    </form
    ";

$sql = "SELECT cell_phone, country_id, city_id, province_id, postal_code FROM contacts WHERE email_address = '" . $email . "'";
$result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));
if (mysqli_num_rows($result) == 1) {

    //if authorized, get the values of f_name l_name
    while ($info = mysqli_fetch_array($result)) {
        $cell_phone = stripslashes($info['cell_phone']);
        $country_id = stripslashes($info['country_id']);
        $province_id = stripslashes($info['province_id']);
        $city_id = stripslashes($info['city_id']);
        $phone = stripslashes($info['cell_phone']);
        $postal_code = stripslashes($info['postal_code']);
    }
    
    

    if ($country_id == 39) {
        $country = 'Canada';
    }if($province_id == 52){
        $province = 'British Columbia';
    }
    if($city_id == 21689){
        $city = 'Salmon Arm';
    }

    $info_arr = array(
        'first_name' => $firstname,
        'last_name' => $lastname,
        'email' => $email,
        'phone' => $cell_phone,
        'country' => $country,
        'province' => $province,
        'city' => $city,
        'postal_code' => $postal_code         
    );
    
    $_SESSION['info_arr'] = $info_arr;


    $display_block = "
    <form method='post' action='genpdf.php'>
    <label for='firstname'>First Name: </label>
    <input type='text' id='firstname' name ='firstname' value='" . $firstname . "'>
    <label for='lastname'>Last Name: </label>
    <input type='text' id='lastname' name ='lastname' value='" . $lastname . "'><br><br>
    <label for='email'>Email: </label>
    <input type='text' id='email' name ='email' value='" . $email . "'><br><br>
    <label for='phone'>Phone: </label>
    <input type='text' id='phone' name ='phone' value='" . $phone . "'><br><br>
    <label for='city'>City: </label>
    <input type='text' id='city' name ='city' value='" . $city . "'>
    <label for='province'>Province: </label>
    <input type='text' id='province' name ='province' value='" . $province . "'><br><br>
    <label for='country'>Country: </label>
    <input type='text' id='country' name ='country' value='" . $country . "'><br><br>
    <label for='postal_code'>Postal Code: </label>
    <input type='text' id='postal_code' name ='postal_code' value='" . $postal_code . "'><br><br>
    <button type='submit' value='Generate' id='generate'>Generate</button>
    
    </form
    ";
}



if (filter_input(INPUT_POST, 'firstname') == 'Jackson') {
    $phone = "(250)575-0997";
    $city = "Kelowna";
    $province = "British Columbia";
    $display_block = "
    <form method='post' action='index.php'>
    <label for='firstname'>First Name: </label>
    <input type='text' id='firstname' name ='firstname' value='" . $firstname . "'></p>
    <label for='lastname'>Last Name: </label>
    <input type='text' id='lastname' name ='lastname' value='" . $lastname . "'></p>
    <label for='email'>Email: </label>
    <input type='text' id='email' name ='email' value='" . $email . "'></p>
    <label for='phone'>Phone: </label>
    <input type='text' id='phone' name ='phone' value='" . $phone . "'></p>
    <label for='city'>City: </label>
    <input type='text' id='city' name ='city' value='" . $city . "'></p>
    <label for='province'>Province: </label>
    <input type='text' id='province' name ='province' value='" . $province . "'></p><br>
    <button type='submit' value='Continue' id='continue'>create</button>
    </form
    ";
}
echo $display_block;

/*
  $mysqli = mysqli_connect("localhost", "cs213user", "letmein", "testDB");
  $email = strtolower(filter_input(INPUT_POST, 'email'));
  $targetpasswd = filter_input(INPUT_POST, 'password');
  $f_name = filter_input(INPUT_POST, 'firstname');
  $l_name = filter_input(INPUT_POST, 'lastname');
  $age = filter_input(INPUT_POST, 'age');
  $gender = filter_input(INPUT_POST, 'gender');


  if (filter_input(INPUT_POST, 'create') != 'Create Account') {
  echo $displayBody;
  } else {

  $sql = "SELECT firstname, lastname FROM members WHERE email = '" . $email . "'";

  $result = mysqli_query($mysqli, $sql) or die(mysqli_error($mysqli));

  //get the number of rows in the result set; should be 1 if a match
  if (mysqli_num_rows($result) == 1) {
  echo '<strong>The email address "' . $email . '" is already in use, try again!</strong>';
  }
  if (mysqli_num_rows($result) == 0) {
  $insert = "INSERT INTO members VALUES('" .
  $f_name . "','" .
  $l_name . "','" .
  $email .
  "',password('" . $targetpasswd . "'),'" .
  $age . "','" .
  $gender . "'," .
  "CURDATE())";
  $insertsuccess = mysqli_query($mysqli, $insert) or die(mysqli_error($mysqli));
  if (insertsuccess) {
  mkdir("/var/www/html/uploaddir/".$email, 0733);
  echo 'Your account "' . $email . '" has been created. Thank you for joining us!';
  echo '<br><a href="userlogin.html">Login Page</a>';
  } else {
  echo "Your account was not created successfully";
  }
  }
  }
  echo "hello";

 */
?>
