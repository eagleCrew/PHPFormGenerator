<?php
  require(fpdf.php);
  session_start();
  $info_arr = $_SESSION['info_arr'];
  $pdf_arr = $_SESSION['pdf_arr'];

  foreach ($pdf_arr as $pdf_name) {
    $pdf = new FPDM($pdf_name);
    $pdf->Load($info_arr, false);
    $pdf->Merge();
    $pdf->Output();
  }
?>
