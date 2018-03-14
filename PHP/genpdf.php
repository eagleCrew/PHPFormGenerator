<?php
  error_reporting(E_ALL);
  ini_set('display_startup_errors', 1);
  ini_set('display_errors', 1);
  require(fpdf.php);
  session_start();
  $info_arr = $_SESSION['info_arr'];
  $pdf_arr = $_SESSION['pdf_arr'];
  $fn_ln = strtolower($info_arr['first_name']).'_'
        .strtolower($info_arr['last_name']);
  $out_folder = 'OutputPDFs/'.$fn_ln;
  $root_path = realpath($out_folder);

  // Creates customer folder, if it doesn't exist
  if (!file_exists($root_path)) {
    mkdir($root_path, 0777, true);
  }

  $info_file = fopen('../OutputPDFs'.$fn_ln.'_info.txt', "w");
  // Writing plaintext info to backup text file
  foreach ($info_pdf as $info) {
      fwrite($info_file, $info);
  }
  // Close backup text file
  fclose($info_file);

  // Generating and saving the pdf files
  foreach ($pdf_arr as $pdf_name) {
    // Checks if pdf needs to be filled
    if ($pdf_arr[$pdf_name] == true) {
      $pdf = new FPDM('InputPDFs/'.$pdf_name.'.pdf');
      $pdf->Load($info_arr, false);
      $pdf->Merge();
      $pdf->Output(F, $out_folder.'/'.$fn_ln.'_'.$pdf_name.'.pdf', false);
    }
  }

  // Initialize archive object
  $zip = new ZipArchive();
  $zip->open($out_folder, ZipArchive::CREATE | ZipArchive::OVERWRITE);
  // Create recursive directory iterator
  $file = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($out_folder),
    RecursiveIteratorIterator::LEAVES_ONLY
  );

  foreach ($file as $name => $file) {
    // Skip directories
    if (!$file->isDir()) {
      // Get real and relative path for current file
      $file_path = $file->getRealPath();
      $relative_path = substr($file_path, strlen($root_path) + 1);

      // Add current file to archive
      $zip->addFile($file_path, $relative_path);
    }
  }

  $zip->close();

  // Download ZipArchive
  readfile($out_folder);
?>
