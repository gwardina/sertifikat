<?php
    session_start();
    set_time_limit(600);
    
    $con = mysql_connect("localhost", "root", "") or die(mysql_error());
	$db = mysql_select_db("lms") or die(mysql_error());
	
	$diklat = $_GET['id'];
	$nip = $_SESSION['nipSertifikat'];
	
for($c = 0; $c < count($nip); $c++) {
	$q = mysql_query("select nip, nama, nilai, wilayahunitinduk from sim_realisasipeserta where nip='$nip[$c]'");
	$q1 = mysql_query("select realisasijadwalid, juduldiklat, kodediklat, udiklat, tanggalmulai, tanggalselesai, tanggalterbit, nilaiakhir, udiklatid 
		from sim_realisasijadwaldiklat where kodediklat='$diklat'");
	
	setlocale(LC_TIME, "INDONESIA.utf8");
	setlocale(LC_TIME, "id_ID.utf8");
	
	$comparatorNilai = array();
	$materi = array();
	$nilai = array();
	$lulus = "";
	$counter = 0;
	$grade = "";
	$kriteria = "";
	$nama = "";
	
	$f = mysql_fetch_assoc($q);
	$nama = $f['nama'];
    
	$f1 = mysql_fetch_assoc($q1);
	$startdate = strtotime($f1['tanggalmulai']);
	$finishdate = strtotime($f1['tanggalselesai']);
	$publishdate = strtotime($f1['tanggalterbit']);
	$jadwalID = $f1['realisasijadwalid'];
		
	if($publishdate == null) {
		$publishdate = $finishdate;
	}
	
	$q2 = mysql_query("select materi, a.realisasimateriid, nilaiminimum, pesertaid, nilai from sim_realisasimateridiklat a, sim_realisasinilaipeserta b 
		where a.realisasimateriid = b.realisasimateriid and b.nama='$nama' and b.realisasijadwalid='$jadwalID'");
	$q3 = mysql_query("select kota, jabatanyangmenandatangani, namayangmenandatangani 
		from sim_udiklat a, sim_realisasijadwaldiklat b where a.udiklatid = b.udiklatid and b.realisasijadwalid = '$jadwalID'");
	
	while($f2 = mysql_fetch_array($q2)) {
		if(intval($f2['nilai']) >= intval($f2['nilaiminimum'])) {
			array_push($comparatorNilai, true);
		}
		$counter++;
		array_push($materi, $f2['materi']);
		array_push($nilai, $f2['nilai']);
	}
	
	$f3 = mysql_fetch_assoc($q3);
	
	if(count(array_filter($comparatorNilai)) == $counter) {
		if($f['nilai'] >= $f1['nilaiakhir']) {
			$lulus = "LULUS";
			if($f['nilai'] >= 92.5) {
				$grade = "A";
				$kriteria = "EKSELEN";
			}
			elseif($f['nilai'] >= 85 and $f['nilai'] < 92.5) {
				$grade = "B";
				$kriteria = "SANGAT MEMUASKAN";
			}
			elseif($f['nilai'] >= 77.5 and $f['nilai'] < 85) {
				$grade = "C";
				$kriteria = "MEMUASKAN";
			}
			elseif($f['nilai'] >= 70 and $f['nilai'] < 77.5) {
				$grade = "D";
				$kriteria = "CUKUP";
			}
		}
	}
	
	else {
		$lulus = "TIDAK LULUS";
		$grade = "E";
		$kriteria = "TIDAK LULUS";
	} 
	
    
	$header = '
<table>
	<tr>
		<td rowspan="3"><img src="logopln.png" width="38" height="46"></td>
		<td style="padding-top: 10px;">PT PLN (PERSERO)</td>
		<td style="width: 600px; text-align: right; font-size: 11pt;">'.$diklat.'.'.$nip[$c].'</td>
	</tr>
	<tr>
		<td>PUSAT PENDIDIKAN DAN PELATIHAN</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
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
	
	<div style="text-align: center; font-size: 14pt; line-height: 150%; padding-bottom: 10px;">
		yang dilaksanakan oleh PT PLN (Persero) Pusdiklat '.$f1['udiklat'].' <br>
		pada tanggal : '.strftime("%d %B %Y", $startdate).' s/d '.strftime("%d %B %Y", $finishdate).' <br>
		'.$lulus.' dengan nilai rata-rata '.$f['nilai'].' <br>
		dan dinyatakan masuk dalam kelompok Grade '.$grade.' dengan kriteria '.$kriteria.' <br>
	</div>
	
	<div style="bottom: 0px; right: 100px; position: absolute; font-size: 14pt;">
		<table>
			<tr><td style="text-align: right;">Jakarta, '.strftime("%d %B %Y", $publishdate).'</td></tr>
			<tr><td><img src="Cap_TTD_Okto.jpg" width="300px;"></td></tr>
		</table>
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
				<td style="height: 5px; width: 650px; padding: 5px; text-align: center;">Materi</td>
				<td style="height: 5px; width: 50px; padding: 5px; text-align: center;">Nilai</td>
			</tr>';
		
		$no = 1;
    	$i = 0;
      	foreach ($materi as $key => $value) {
        	$content .= 
        	'<tr>
        		<td style="height: 5px; padding: 5px; text-align: center;">'.$no.'</td>
        		<td style="height: 5px; padding: 5px; vertical-align: middle;">'.$value.'</td>
        		<td style="height: 5px; padding: 5px; text-align: center;">'.$nilai[$i].'</td>
        	</tr>';
        	$no++;$i++;
      	}
      	
      	$content .= '
      		<tr>
      			<td colspan="2" style="height: 5px; padding: 5px; text-align: center;">Nilai Rata-Rata</td>
      			<td style="height: 5px; padding: 5px; text-align: center;">'.$f['nilai'].'</td>
      		</tr>
		</table>
	</div>
	
	<div style="bottom: 30px; right: 100px; position: absolute; font-size: 12pt;">
		<table>
			<tr><td style="text-align: center; padding-bottom: 5px;">'.$f3['kota'].', '.strftime("%d %B %Y", $publishdate).'</td></tr>
			<tr><td style="text-align: center; font-style: italic;">'.$f3['jabatanyangmenandatangani'].',</td></tr>
			<tr><td>&nbsp;</td></tr>
			<tr><td>&nbsp;</td></tr>
			<tr><td>&nbsp;</td></tr>
			<tr><td style="text-align: center;">'.$f3['namayangmenandatangani'].'</td></tr>
		</table>
	</div>
</page>
	';
	
	$filename = dirname(__FILE__)."/pdf/".$diklat."_".$nip[$c].".pdf";
	
    require_once(dirname(__FILE__).'/html2pdf/html2pdf.class.php');
    $html2pdf = new HTML2PDF('L',array(279,210),'en', false, 'ISO-8859-15', array(16, 10, 15, 10));
    $html2pdf->setDefaultFont('Arial');
	$html2pdf->WriteHTML($header);
    $html2pdf->setDefaultFont('Algerian');
    $html2pdf->WriteHTML($title);
	$html2pdf->setDefaultFont('Arial');
	$html2pdf->WriteHTML($content);
    $html2pdf->Output($filename, 'F');
  }	
	//header("Location: redirect.php?id=".$diklat."");
?>