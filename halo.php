<?php
	$con = mysql_connect("localhost", "root", "") or die(mysql_error());
	$db = mysql_select_db("lms") or die(mysql_error());
	
	$q = mysql_query("select realisasijadwalid from sim_realisasijadwaldiklat where realisasijadwalid='23'");
	
	while($f = mysql_fetch_array($q)) {
    	break;
    }
    
	require_once('html2pdf/html2pdf.class.php');
    $html2pdf = new HTML2PDF('L','A4','fr');
    $html2pdf->WriteHTML(
    	'<h1>'.$f['realisasijadwalid'].'</h1><br>
    	<img src="logopln.png">'

    	);
    $html2pdf->Output('exemple.pdf');
?>