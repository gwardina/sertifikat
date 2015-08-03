<?php
    $con = mysql_connect("localhost", "root", "") or die(mysql_error());
	$db = mysql_select_db("lms") or die(mysql_error());
	
	$q = mysql_query("select nip, nama, wilayahunitinduk from sim_realisasipeserta where nama='MUJIRIN'");
	$q1 = mysql_query("select juduldiklat, kodediklat, udiklat, tanggalmulai, tanggalselesai from sim_realisasijadwaldiklat where realisasijadwalid='23'");
	
	setlocale(LC_TIME, "INDONESIA.utf8");
	setlocale(LC_TIME, "id_ID.utf8");
	
	while($f = mysql_fetch_array($q)) {
    	break;
    }
	
	while($f1 = mysql_fetch_array($q1)) {
		$startdate = strtotime($f1['tanggalmulai']);
		$finishdate = $f1['tanggalselesai'];
		break;
	}
    
	$header = '
<table>
	<tr>
		<td rowspan="3"><img src="logopln.png" width="38" height="46"></td>
		<td style="padding-top: 10px;">PT PLN (PERSERO)</td>
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
<h1 style="font-size: 42pt; color: navy; text-align: center;">SERTIFIKAT</h1>
	';
	
	$content = '
	<div style="text-align: center; font-size: 14pt;">	
		Dengan ini menyatakan bahwa :
	</div><br><br>

	<div style="text-align: center; font-size: 18pt; text-decoration: underline; padding-bottom: 5px;">'
		.$f['nama'].'
	</div>

	<div style="text-align: center; font-size: 14pt; padding-bottom: 10px;">
		NIP : ' .$f['nip'].'
	</div>

	<div style="text-align: center; font-size: 12pt;">'
		.$f['wilayahunitinduk'].'	
	</div><br><br>
	
	<div style="text-align: center; font-size: 14pt; padding-bottom: 10px;">
		telah mengikuti pelatihan
	</div>
	
	<div style="text-align: center; font-size: 14pt; padding-bottom: 8px;">'
		.$f1['juduldiklat'].'
	</div>
	
	<div style="text-align: center; font-size: 12pt; padding-bottom: 10px;">'
		.$f1['kodediklat'].'
	</div><br>
	
	<div style="text-align: center; font-size: 14pt;">
		yang dilaksanakan oleh PT PLN (Persero) Pusdiklat '.$f1['udiklat'].' <br>
		pada tanggal : '.strftime("%d %B %Y", $startdate).' 
	</div>

<page>
	<h3 style="font-weight: bold; font-size: 12pt; text-align: center;">DAFTAR NILAI PELATIHAN</h3>
	<div style="font-weight: bold; font-size: 14pt; text-align: center;">'
		.$f1['juduldiklat'].
	'</div><br><br>
	
	<div style="font-size: 10pt; font-weight: bold;">
		<table align="center" border="1" cellpadding="0" cellspacing="0">
			<tr>
				<td style="height: 5px; width: 15px; padding: 5px; text-align: center;">No</td>
				<td style="height: 5px; width: 600px; padding: 5px; text-align: center;">Materi</td>
				<td style="height: 5px; width: 70px; padding: 5px; text-align: center;">Nilai</td>
			</tr>
		</table>
	</div>
</page>
	';

    require_once(dirname(__FILE__).'/html2pdf/html2pdf.class.php');
    $html2pdf = new HTML2PDF('L',array(279,210),'en', false, 'ISO-8859-15', array(16.5, 10, 10, 20));
    $html2pdf->setDefaultFont('Arial');
	$html2pdf->WriteHTML($header);
    $html2pdf->setDefaultFont('Algerian');
    $html2pdf->WriteHTML($title);
	$html2pdf->setDefaultFont('Arial');
	$html2pdf->WriteHTML($content);
    $html2pdf->Output('exemple.pdf');
?>
