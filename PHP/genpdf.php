<?php
  error_reporting(E_ALL);
  ini_set('display_startup_errors', 1);
  ini_set('display_errors', 1);
  require('lib/fpdm.php');
  
  // Initialize arrays for each location.
  $salmonarm_location = array(
    'lname' => 'Eagle Homes Sales (Salmon Arm) Ltd',
    'laddress' => '#1-120 Harbourfront Drive NE, Salmon Arm BC V1E 2T3',
    'lphone' => 'Phone: 250-803-0500',
    'lwebsite' => 'www.eaglehomes.ca',
    'lgst' => 'GST: 80929 6478 RT0001'
  );

  $kamloops_location = array(
    'lname' => 'Eagle Homes Sales (Kamloops) Ltd',
    'laddress' => '7510 Dallas Drive, Kamloops BC V2C 6X2',
    'lphone' => 'Phone: 250-573-2278',
    'lwebsite' => 'www.eaglehomes.ca',
    'lgst' => 'GST: 80930 3076 RT0001'
  );

  $castlegar_location = array(
    'lname' => 'Eagle Homes Sales Ltd',
    'laddress' => '4430 Minto Road, Castlegar BC V1N 4C1',
    'lphone' => 'Phone: 250-365-2121',
    'lwebsite' => 'www.eaglehomes.ca',
    'lgst' => 'GST: 81575 8461 RT0001'
  );

  $cranbrook_location = array(
    'lname' => 'Eagle Homes Sales (Cranbrook) Ltd',
    'laddress' => '2232 Cranbrook Street N, Cranbrook BC V1C 3T2',
    'lphone' => 'Phone: 250-489-1230',
    'lwebsite' => 'www.eaglehomes.ca',
    'lgst' => 'GST: 80929 6270 RT0001'
  );

  // Getting results from pdf checkboxes in prior form.
  $pdf_arr = array(
    'sales_contract' => isset($_POST['sales_contract']),
    'gst_new_housing' => isset($_POST['gst_new_housing']),
    'gst190_calc' => isset($_POST['gst190_calc']),
    'bill_of_sale' => isset($_POST['bill_of_sale']),
    'payment_cash' => isset($_POST['payment_cash']),
    'payment_finance' => isset($_POST['payment_finance']),
    'individual_id_record' => isset($_POST['individual_id_record']),
    'residential_set_up' => isset($_POST['residential_set_up']),
    'home_set_up_sheet' => isset($_POST['home_set_up_sheet']),
    'site_check_req' => isset($_POST['site_check_req']),
    'appliance_delivery_waiver' => isset($_POST['appliance_delivery_waiver']),
    'linoleum_waiver' => isset($_POST['linoleum_waiver']),
    'addendum_2' => isset($_POST['addendum_2'])
  );

  // Getting pdf field variables through POST.
  $location = filter_input(INPUT_POST, 'ealist');
  $currdate = filter_input(INPUT_POST, 'currdate');
  $firstname = filter_input(INPUT_POST, 'firstname');
  $lastname = filter_input(INPUT_POST, 'lastname');
  $initials = substr($firstname, 0, 1).'.'.substr($lastname, 0, 1).'.';
  $email = filter_input(INPUT_POST, 'email');
  $firstname2 = filter_input(INPUT_POST, 'firstname2');
  $lastname2 = filter_input(INPUT_POST, 'lastname2');
  $initials2 = substr($firstname2, 0, 1).'.'.substr($lastname2, 0, 1).'.';
  $phone = filter_input(INPUT_POST, 'phone');
  $altphone = filter_input(INPUT_POST, 'altphone');
  $tenants = filter_input(INPUT_POST, 'tenants');
  $sale = filter_input(INPUT_POST, 'sale');
  $addr = filter_input(INPUT_POST, 'addr');
  $newaddr = filter_input(INPUT_POST, 'newaddr');
  $nladdr = filter_input(INPUT_POST, 'nladdr');
  $Home_Price = filter_input(INPUT_POST, 'Home_Price');
  $gstrebate = filter_input(INPUT_POST, 'gstrebate');
  $fridgeprice = filter_input(INPUT_POST, 'fridgeprice');
  $rngprice = filter_input(INPUT_POST, 'rngprice');
  $deposit = filter_input(INPUT_POST, 'deposit');
  $depositdue = filter_input(INPUT_POST, 'depositdue');
  $tradeIn = filter_input(INPUT_POST, 'tradein');
  $tryear = filter_input(INPUT_POST, 'tryear');
  $trmake = filter_input(INPUT_POST, 'trmake');
  $trmodel = filter_input(INPUT_POST, 'trmodel');
  $trsize = filter_input(INPUT_POST, 'trsize');
  $trcsa = filter_input(INPUT_POST, 'trcsa');
  $trserial = filter_input(INPUT_POST, 'trserial');
  $trmhr = filter_input(INPUT_POST, 'trmhr');
  $year = filter_input(INPUT_POST, 'year');
  $make = filter_input(INPUT_POST, 'make');
  $model = filter_input(INPUT_POST, 'model');
  $size = filter_input(INPUT_POST, 'size');
  $csa = filter_input(INPUT_POST, 'csa');
  $serial = filter_input(INPUT_POST, 'serial');
  $mhr = filter_input(INPUT_POST, 'mhr');
  $sections = filter_input(INPUT_POST, 'sections');
  $sitechk = filter_input(INPUT_POST, 'sitechk');
  $drywall = filter_input(INPUT_POST, 'drywall');
  $fiber = filter_input(INPUT_POST, 'fiber');
  $skirting = filter_input(INPUT_POST, 'skirting');
  $stairs = filter_input(INPUT_POST, 'stairs');
  $regular = filter_input(INPUT_POST, 'regular');
  $lrg = filter_input(INPUT_POST, 'lrg');
  $roofonsite = filter_input(INPUT_POST, 'roofonsite');
  $dormer = filter_input(INPUT_POST, 'dormer');
  $solar = filter_input(INPUT_POST, 'solar');
  $skylight = filter_input(INPUT_POST, 'skylight');
  $five = '';
  $six = '';
  $tenantscommon = '';
  $tenantsjoint = '';
  
  if($lrg == '5/12'){
    $five = 'x';
  }else if($lrg == '6/12'){
    $six = 'x';
  }

  if($tenants == 'incommon'){
    $tenantscommon = 'x';
  }else if($tenants == 'joint'){
    $tenantsjoint = 'x';
  }
  
  // Assigning fields variables to arrays for each pdf.
  // If they are in the pdf they should be in the array.
  $addendum_2 = array(
    'firstname' => $firstname,
    'lastname' => $lastname,
    'currdate' => $currdate,
    'model' => $model,
    'size' => $size,
    'newaddr' => $newaddr,
    'csa' => $csa
  );

  $appliance_delivery_waiver = array(
    'currdate' => $currdate,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'newaddr' => $newaddr
  );

  $bill_of_sale = array(
    'currdate' => $currdate,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'year' => $year,
    'model' => $model,
    'serial' => $serial,
    'mhr' => $mhr,
    'Home_Price' => $Home_Price
  );

  $gst190_calc = array();
  
  $gst_new_housing = array(
    'fullname' => $firstname.', '.$lastname.', '.$initials,
    'fullname2' => $firstname2.', '.$lastname2.', '.$initials2,
    'phone' => $phone,
    'altphone' => $altphone,
    'model' => $model,
    'serial' => $serial
  );
  
  $home_set_up_sheet = array(
    'firstname' => $firstname,
    'lastname' => $lastname,
    'email' => $email,
    'currdate' => $currdate,
    'phone' => $phone,
    'stairs' => $stairs,
    'altphone' => $altphone,
    'model' => $model,
    'size' => $size,
    'skylight' => $skylight,
    'dormer' => $dormer,
    'tradein' => $tradeIn,
    'serial' => $serial,
    'solar' => $solar,
    'skirting' => $skirting,
    'drywall' => $drywall
  );

  $individual_id_record = array(
    'currdate' => $currdate,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'addr' => $addr
  );

  $linoleum_waiver = array(
    'currdate' => $currdate,
    'date' => $currdate,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'addr' => $addr
  );

  $payment_finance = array();

  $payment_method = array();

  $residential_set_up = array(
    'currdate' => $currdate,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'email' => $email,
    'phone' => $phone,
    'altphone' => $altphone,
    'model' => $model,
    'size' => $size,
    'dormer' => $dormer,
    'tradein' => $tradeIn,
    'serial' => $serial,
    'drywall' => $drywall,
    '5/12' => $five,
    '6/12' => $six
  );

  $sales_contract = array(
    'currdate' => $currdate,
    'Date1'=> $currdate,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'firstname2' => $firstname2,
    'lastname2' => $lastname2,
    'firstname1' => $firstname,
    'lastname1' => $lastname,
    'firstname2' => $firstname2,
    'lastname2' => $lastname2,
    'firstname3' => $firstname2,
    'lastname3' => $lastname2,	
    'addr' => $addr,
    'phone' => $phone,
    'altphone' => $altphone,
    'email' => $email,
    'year' => $year,
    'model' => $model,
    'size' => $size,
    'csa' => $csa,
    'serial' => $serial,
    'mhr' => $mhr,
    'Home_Price' => $Home_Price,
    'Apply_Rebate' => $gstrebate,
    'tangibles' => $fridgeprice + $rngprice,
    'deposit' => $deposit,
    'depositdue' => $depositdue,
    'incommon' => $tenantscommon,
    'joint' => $tenantsjoint
  );
  
  $site_check_req = array(
    'currdate' => $currdate,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'newaddr' => $newaddr,
    'phone' => $phone
  );

  // Merging the location info array to the arrays that need them.
  // Arrays that get merged with the location array:
  // site_check
  // sales_contract
  switch ($location) {
    case "salmon arm":
      $site_check_req = $salmonarm_location + $site_check_req;
      $sales_contract = $salmonarm_location + $sales_contract;
      break;
    case "kamloops":
      $site_check_req = $kamloops_location + $site_check_req;
      $sales_contract = $kamloops_location + $sales_contract;
      break;
    case "castlegar":
      $site_check_req = $castlegar_location + $site_check_req;
      $sales_contract = $castlegar_location + $sales_contract;
      break;
    case "cranbrook":
      $site_check_req = $cranbrook_location + $site_check_req;
      $sales_contract = $cranbrook_location + $sales_contract;
      break;
  }

  // Array holding all the pdf arrays
  $arr_of_arr = array(
    'addendum_2' => $addendum_2,
    'appliance_delivery_waiver' => $appliance_delivery_waiver,
    'bill_of_sale' => $bill_of_sale,
    'gst190_calc' => $gst190_calc,
    'gst_new_housing' => $gst_new_housing,
    'home_set_up_sheet' => $home_set_up_sheet,
    'individual_id_record' => $individual_id_record,
    'linoleum_waiver' => $linoleum_waiver,
    'payment_finance' => $payment_finance,
    'payment_method' => $payment_method,
    'residential_set_up' => $residential_set_up,
    'sales_contract' => $sales_contract,
    'site_check_req' => $site_check_req
  );
				    
  $ln_fn = strtolower($lastname).'_'.strtolower($firstname);
  $folder_name = $ln_fn.'_'.date('Y-m-d');
  $out_folder = $_SERVER['DOCUMENT_ROOT'].'/PHPFormGenerator/OutputPDFs/'.$ln_fn.'/'.$folder_name;
  $root_path = realpath($out_folder);
    
  // Creates customer folder, if it doesn't exist
  if (!file_exists($root_path)) {
    mkdir($out_folder, 0777, true);
  }
  
  // Loops through the array of pdfs and array of pdf arrays.
  // Calls fillpdf if the pdf value is true, and the names of the pdf and array are equal.
  foreach($pdf_arr as $pdf_name => $pdf){
    if($pdf == true){
      foreach($arr_of_arr as $arr_name => $arr) {
        if($pdf_name == $arr_name) {
          fillpdf($pdf_name, $arr, $ln_fn, $out_folder);
        }
      }
    }
  }
  
  // Function to load pdf fields, and create/append to backup file. Call for each pdf form.
  function fillpdf($pdf_name, $arr, $ln_fn, $out_folder) {
      $info_file = fopen($out_folder.'/'.$ln_fn.'_info.txt', "a");

      // Writing plaintext info to backup text file
      $info_pdf = '\r\nInfo loaded into: '.$pdf_name."\r\n";
      fwrite($info_file, $info_pdf);
      foreach ($arr as $info_name => $info) {
        fwrite($info_file, $info_name.': '.$info."\r\n");
      }

      // Close backup text file
      fclose($info_file);

      // Generating and saving the pdf files
      $pdf = new FPDM($_SERVER['DOCUMENT_ROOT'].'/PHPFormGenerator/InputPDFs/'.$pdf_name.'.pdf');
      $pdf->Load($arr, false);
      $pdf->Merge();
      $pdf->Output($out_folder.'/'.$pdf_name.'_'.$ln_fn.'.pdf', false);

      return;
  }
  
  // Initialize archive object
  $root_path = realpath($out_folder);
  $zip_name = $folder_name.'.zip';
  $zip = new ZipArchive();
  $zip->open($root_path.'/'.$zip_name, ZipArchive::CREATE | ZipArchive::OVERWRITE);
  // Create recursive directory iterator
  $file = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root_path),
    RecursiveIteratorIterator::LEAVES_ONLY
  );
  
  foreach ($file as $name => $file) {
    // Skip directories
    if (!$file->isDir()) {
     // Get real and relative path for current file
     $file_path = $file->getRealPath();
     $relative_path = substr($file_path, strlen($root_path) + 1);
     // Add current file to archive
     if($file->getFilename() == $folder_name.'.zip'){
       unlink($file);
     }
     
     $zip->addFile($file_path, $relative_path);
    }
  }
  
  // Download ZipArchive
  ob_start();
  ob_end_clean();
  $zip->close();
  $file_size = filesize($root_path.'/'.$zip_name);
  $file_name = basename($zip_name);
  header("Pragma: public");
  header("Expires: 0");
  header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
  header("Cache-Control: public");
  header("Content-Description: File Transfer");
  header("Content-Type: application/zip");
  header('Content-Disposition: attachment; filename=documents.zip');
  header("Content-Tranfer-Encoding: binary");
  header("Content-Length: ".$file_size."");
  //ob_end_clean();
  readfile($root_path.'/'.$zip_name);
?>