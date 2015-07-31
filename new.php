<?php
    $con = mysql_connect("localhost", "root", "") or die(mysql_error());
	$db = mysql_select_db("lms") or die(mysql_error());
	
	$q = mysql_query("select nip, nama, wilayahunitinduk from sim_realisasipeserta where nama='MUJIRIN'");
	$q1 = mysql_query("select juduldiklat from sim_realisasijadwaldiklat where realisasijadwalid='23'");
	
	while($f = mysql_fetch_array($q)) {
    	break;
    }
	
	while($f1 = mysql_fetch_array($q1)) {
		break;
	}
    
	$header = '
<table>
	<tr>
		<td rowspan="3"><img src="logopln.png" width="40" height="50"></td>
		<td>PT PLN (PERSERO)</td>
	</tr>
	<tr>
		<td>PUSAT PENDIDIKAN DAN PELATIHAN</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
</table>
   ';
   
	$title = '
<h1 style="font-size: 40pt; color: darkblue; text-align: center;">SERTIFIKAT</h1>
	';
	
	$content = '
	<div style="text-align: center; font-size: 14pt;">	
		Dengan ini menyatakan bahwa :
	</div><br><br>

	<div style="text-align: center; font-size: 18pt; text-decoration: underline; padding-bottom: 5px;">'
		.$f['nama'].
	'</div>

	<div style="text-align: center; font-size: 15pt; padding-bottom: 15px;">
		NIP : ' .$f['nip'].
	'</div>

	<div style="text-align: center; font-size: 13pt; padding-bottom: 5px;">'
		.$f['wilayahunitinduk'].	
	'</div><br><br>
	
	<div style="text-align: center; font-size: 14pt; padding-bottom: 10px;">
		telah mengikuti pelatihan
	</div>
	
	<div style="text-align: center; font-size: 14pt;">'
		.$f1['juduldiklat'].	
	'</div>
<page>
	HALO1
</page>
	';

    require_once(dirname(__FILE__).'/html2pdf/html2pdf.class.php');
    $html2pdf = new HTML2PDF('L','A4','en', false, 'ISO-8859-15', array(15, 10, 10, 20));
    $html2pdf->setDefaultFont('Arial');
	$html2pdf->WriteHTML($header);
    $html2pdf->setDefaultFont('Algerian');
    $html2pdf->WriteHTML($title);
	$html2pdf->setDefaultFont('Arial');
	$html2pdf->WriteHTML($content);
    $html2pdf->Output('exemple.pdf');
?>
