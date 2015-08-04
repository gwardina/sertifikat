<?PHP // $Id: version.php,v 3.1.0

require_once('../../config.php');
require_once('lib.php');
include ('../../lib/fpdf/fpdf.php');
include ('../../lib/fpdf/fpdfprotection.php');
include_once('html2pdf.php');
require_once('fpdi/fpdi.php');

class concat_pdf extends FPDI {

    var $files = array();
	
	

    function setFiles($files) {
        $this->files = $files;
    }

    function concat() {
        foreach($this->files AS $file) {
            $pagecount = $this->setSourceFile($file);
            for ($i = 1; $i <= $pagecount; $i++) {
                 $tplidx = $this->ImportPage($i);
                 $s = $this->getTemplatesize($tplidx);
                 $this->AddPage('L', 'pt', 'Letter');
                 $this->useTemplate($tplidx);
            }
        }
    }

}

    $id = required_param('id', PARAM_INT);    // Course Module ID
    $action = optional_param('action', '', PARAM_ALPHA);

    if (! $cm = get_coursemodule_from_id('certificate', $id)) {
        error('Course Module ID was incorrect');
    }
    if (! $course = get_record('course', 'id', $cm->course)) {
        error('course is misconfigured');
    }
    if (! $certificate = get_record('certificate', 'id', $cm->instance)) {
        error('course module is incorrect');
    }

	global $USER,$USER_loop;
	$count_folder = 0;
    require_login($course->id);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('mod/certificate:view', $context);
	

	$query_user = "SELECT u.id AS user_id, u.username as nip, u.firstname,
					c.id AS course_id, c.shortname, c.fullname, cf.id AS cf_id, cf.course
					FROM	sdl_user u
					inner join sdl_role_assignments ra on u.id = ra.userid	
					inner join sdl_context con on ra.contextid = con.id AND con.contextlevel = 50
					inner join sdl_course c on con.instanceid = c.id AND c.id = ".$course->id."
					inner join sdl_role r on ra.roleid = r.id AND r.shortname = 'student'
					inner join sdl_certificate cf on cf.course = c.id AND cf.id = ".$certificate->id;
	
	$result = mysql_query($query_user) or die(mysql_error());
	
	$count = 0;
	$countuser= 0;
$file_name="";
	view_header($course, $certificate, $cm);
	
	while($row = mysql_fetch_array($result))
	{

		// log update
		add_to_log($course->id, 'certificate', 'view', "view.php?id=$cm->id", $certificate->id, $cm->id);
		

		$field = 'id';
		$value = $row['user_id'];
		//echo $certificate->lockgrade.'<br>';
		$USER_loop = get_complete_user_data($field, $value);
		$count++;
		//echo '<b>['.$count.']'.$USER_loop->username.' | '.$USER_loop->firstname.' '.$USER_loop->lastname.'</b><br>';
		$restrict_errors = certificate_grade_condition_loop();

		///*************************
	
		//check to see if requiredcertification and user has not completed.
		if (!empty($certificate->requiredcertification)) {
			//get any certifications this user has.       
			$usercert = get_field('user_info_data', 'data','fieldid', $certificate->requiredcertification, 'userid', $USER_loop->id);    
		    
			//echo 'Test -2B: get any certifications this user has<br>';
			if (empty($usercert)) {
				view_header($course, $certificate, $cm);
			
				//echo 'Test -1: empty($usercert) <br>';
			
				print_simple_box(notify(get_string('requiredcertificationdesc','local')));
				print_continue("$CFG->wwwroot/course/view.php?id=$course->id");
				print_footer();
				//die;
			}
		}
		
		if (!empty($restrict_errors))
		{
			//echo 'the id not allowed<br>';
			}	
		
		if (empty($restrict_errors)) {	
			//echo 'Test D: lolos u diprint.<br>';	
			/// Create certrecord
			certificate_prepare_issue($course, $USER_loop, $certificate);
	
			//echo 'Test E<br>';
			/// Load custom type
			$type = $certificate->certificatetype;
			$certificateid = $certificate->id;
			$certrecord = get_record('certificate_issues', 'certificateid', $certificateid, 'userid', $USER_loop->id);

			/// Load some strings
			$strreviewcertificate = get_string('reviewcertificate', 'certificate');
			$strgetcertificate = get_string('getcertificate', 'certificate');
			$strgrade = get_string('grade', 'certificate');
			$strcoursegrade = get_string('coursegrade', 'certificate');
			$strcredithours = get_string('credithours', 'certificate');
			
			
			$ss = "SELECT shortname FROM ".$CFG->prefix."course WHERE id = '$course->id'";
			$rr =mysql_query($ss) or die(mysql_error());
			$ww =mysql_fetch_array($rr);
			$strQry = mysql_query("SELECT RIGHT(KodeDiklat, 1) as Kode FROM sim_realisasijadwaldiklat WHERE KodeSertifikat = '$ww[shortname]'");	
			$resKode = mysql_fetch_object($strQry);
			
			if($resKode->Kode == 'I')
				require ("$CFG->dirroot/mod/certificate/type/$certificate->certificatetype/certificate_en.php");
			else
				///Load the specific certificatetype
				require ("$CFG->dirroot/mod/certificate/type/$certificate->certificatetype/certificate.php");
    
			if($certrecord->certdate > 0) { ///Review certificate
				//echo 'Test 1: Review certificate<br>';
				if (empty($action)) {
					view_header($course, $certificate, $cm);

					echo '<p align="center">'.get_string('viewed', 'certificate').'<br />'.userdate($certrecord->certdate).'
					</p>';
					echo '<center>';
					$opt = new stdclass();
					$opt->id = $cm->id;
					$opt->action = 'review';					
					print_single_button('', $opt, $strreviewcertificate, 'get', 'certframe');
			
					//echo 'Test 2: empty action Review certificate<br>';
					//TODO: not sure why this iframe is used - in FF it seems to operate the same as the create certificate below. - might be able to be removed?
					
					echo '<iframe name="certframe" id="certframe" frameborder="NO" border="0" style="width:90%;height:500px;border:0px;"></iframe>';
					echo '</center>';

					print_footer(NULL, $course);					
					exit;
				}
			} 
	
			elseif($certrecord->certdate == 0) { ///Create certificate
				//echo 'Test 3: Create certificate<br>';
				if(empty($action)) {
					//echo 'Test 4: empty action Create certificate<br>';
					view_header($course, $certificate, $cm);
					if ($certificate->delivery == 0)    {
					//updated by Rinamay
						//Tgl 29 Desember 2009
						
						$s="select shortname from ".$CFG->prefix."course where id=$course->id";
						$r=mysql_query($s) or die(mysql_error());
						$w=mysql_fetch_array($r);
						$s="select LockSertifikat from sim_realisasijadwaldiklat where KodeSertifikat='$w[shortname]'";
						$r=mysql_query($s) or die(mysql_error());
						$w=mysql_fetch_array($r);
						if($w['LockSertifikat']<>'Y')
							echo '<p align="center">'.get_string('openwindow', 'certificate').'</p>';
					} 
					
					elseif ($certificate->delivery == 1)    {
						echo '<p align="center">'.get_string('opendownload', 'certificate').'</p>';
					} 
					
					elseif ($certificate->delivery == 2)    {
						echo '<p align="center">'.get_string('openemail', 'certificate').'</p>';
					}

					$opt = new stdclass();
					$opt->id = $cm->id;
					$opt->action = 'get';
					echo '<center>';
					
					//updated by Rinamay
					//Tgl 29 Desember 2009
			  
					$s="select shortname from ".$CFG->prefix."course where id=$course->id";
					$r=mysql_query($s) or die(mysql_error());
					$w=mysql_fetch_array($r);
					$s="select LockSertifikat from sim_realisasijadwaldiklat where KodeSertifikat='$w[shortname]'";
					$r=mysql_query($s) or die(mysql_error());
					$w=mysql_fetch_array($r);
			
					if($w['LockSertifikat']<>'Y')
						print_single_button('view_loop.php', $opt, 'Get All Certificate', 'get', '_blank');
            
					echo '</center>';
					add_to_log($course->id, 'certificate', 'received', "view.php?id=$cm->id", $certificate->id, $cm->id);
					print_footer(NULL, $course);
					exit;
				}
			
				//echo $certificate->printgrade;
				certificate_issue($course, $certificate, $certrecord, $cm,$USER_loop); // update certrecord as issued
			}			
  
		}
			$grade_condition = NULL;		
		
	}

	$sql_lulus = "SELECT u.id AS user_id, u.username as nip, u.firstname,
					c.id AS course_id, c.shortname, c.fullname, cf.id AS cf_id, cf.course,ci.reportgrade
					FROM	sdl_user u
					inner join sdl_role_assignments ra on u.id = ra.userid	
					inner join sdl_certificate_issues ci on u.id=ci.userid
					inner join sdl_context con on ra.contextid = con.id AND con.contextlevel = 50
					inner join sdl_course c on con.instanceid = c.id AND c.id = $course->id
					inner join sdl_role r on ra.roleid = r.id AND r.shortname = 'student'
					inner join sdl_certificate cf on cf.course = c.id AND cf.id = $certificate->id AND ci.certificateid = cf.id
					where ci.reportgrade >=70 or ci.reportgrade ='-'
					order by u.firstname";
					
	
				
	$qry_lulus = mysql_query($sql_lulus) or die(mysql_error());
	$rows_lulus = mysql_num_rows($qry_lulus);
/*	if($rows_lulus < 1){ 
				//adib
				$sql_del_ci="delete from ".$cfg->prefix."certificate_issues where certificateid=$certificate->id and reportgrade < 70";
				$qry_del_ci= mysql_query($sql_del_ci);
				//--
				echo "<br>&nbsp";
				echo "&nbsp Error Generate Certificate.";
				echo "<br>&nbsp Tidak ada peserta yang lulus. Mohon cek kembali nilai peserta dan syarat kelulusan ya";
				echo "<br>&nbsp";
				exit();				
			}*/
	
	
	// edited by rafi 31-7-12 passing nilai SIM
	if($rows_lulus <= 0){ 
				$sql_sync_sim = "select RealisasiJadwalID from sim_realisasijadwaldiklat where KodeSertifikat = '$course->shortname'";
				$query_sync_sim = mysql_query($sql_sync_sim) or die(mysql_error());
				$result_sync_sim = mysql_fetch_array($query_sync_sim);
				  
				$id_realisasi = $result_sync_sim['RealisasiJadwalID'];
				
				$qry_peserta = mysql_query($query_user) or die(mysql_error());
	while($rs_peserta = mysql_fetch_array($qry_peserta)){
		
		$sql_nilai_akhir = "SELECT finalgrade FROM sdl_grade_grades gg
									inner join sdl_grade_items gi on gg.itemid = gi.id and 
									gi.courseid = $course->id and 
									gi.itemtype = 'course' and 
									userid = ".$rs_peserta['user_id'];
			$qry_nilai_akhir = mysql_query($sql_nilai_akhir) or die('salah nij');
			$rs_nilai_akhir = mysql_fetch_array($qry_nilai_akhir);
			$rows_nilai_akhir = mysql_num_rows($qry_nilai_akhir);	
			
	
			$nilai = $rs_nilai_akhir ['finalgrade'];
			//echo $nilai;//$sql_nilai_akhir;
	

			//	passing nilai SIM
				$sql_sync_sim = "select RealisasiJadwalID from sim_realisasijadwaldiklat where KodeSertifikat = '$course->shortname'";
				$query_sync_sim = mysql_query($sql_sync_sim) or die(mysql_error());
				$result_sync_sim = mysql_fetch_array($query_sync_sim);
				  
				$id_realisasi = $result_sync_sim['RealisasiJadwalID'];
				$sql_update_sim = "update sim_realisasipeserta set 
				Nilai = '$nilai',
				Lulus = 'N', 
				Rangking = 0 ,
				NoSertifikat=null,
				 KelompokPrestasi='',
				 Grade=''
				where 
				RealisasiJadwalID = '$id_realisasi' 
				and NIP = '".$rs_peserta['nip']."'";
				$query_sync_sim = mysql_query($sql_update_sim) or die(mysql_error());
	
		
				$sql_update_sim = "update sim_realisasipeserta
				 set Nilai = '$nilai', 
				 Lulus = 'N', 
				 Rangking = 0,
				 NoSertifikat=null,
				 KelompokPrestasi='',
				 Grade=''
				 where RealisasiJadwalID = '$id_realisasi' 
				 and NIP = '".$rs_peserta['nip']."'";
				$query_sync_sim = mysql_query($sql_update_sim) or die("salah");
			    
				//if(!$query_sync_sim) echo " gagal update";
				//else echo $sql_update_sim."berhasil update";
				
				
				
	}
	//exit();	
	echo "<br>&nbsp Tidak ada peserta yang lulus.";
				echo "<br>&nbsp";	
	}
	//--			

else{	

		
	while($rs_lulus = mysql_fetch_array($qry_lulus))
	{
		//$field = 'id';
		//$value = $row_1['user_id'];
		//echo $certificate->lockgrade.'<br>';
		//$USER_loop = get_complete_user_data($field, $value);
	//echo "<br> apa <br>";
	//////***********Hardi**********///////	

		$id_lulus = $rs_lulus['user_id'];		
	$nip=strtoupper($rs_lulus['nip']);
			$test_sql1 = "select userid, cast(replace(reportgrade,',','.') as decimal(6,2)) as nilai_akhir from sdl_certificate_issues 
						where certificateid = $certificate->id order by nilai_akhir desc";
			$result2 = mysql_query($test_sql1) or die(mysql_error());

			$rank = 0;
			$grade = 10001;

			do {
				$row1 = mysql_fetch_array($result2);
				//$count1 = $count1+20;
				if($row1['nilai_akhir'] < $grade)
				{
				$rank = $rank+1;
				}
				$grade = $row1['nilai_akhir'];
	
			}while ($row1['userid'] != $id_lulus && !empty($row1));

			$sql_tipe_kode = "select certificatetype from sdl_certificate where id = $certificate->id";
			$result_tipe_kode = mysql_query($sql_tipe_kode) or die(mysql_error());
			$row_tipe_kode = mysql_fetch_array($result_tipe_kode);

			$sql_urut = "SELECT u.id AS user_id, u.username as nip, u.firstname,
					c.id AS course_id, c.shortname, c.fullname, cf.id AS cf_id, cf.course,ci.reportgrade
					FROM	sdl_user u
					inner join sdl_role_assignments ra on u.id = ra.userid	
					inner join sdl_certificate_issues ci on u.id=ci.userid
					inner join sdl_context con on ra.contextid = con.id AND con.contextlevel = 50
					inner join sdl_course c on con.instanceid = c.id AND c.id = $course->id
					inner join sdl_role r on ra.roleid = r.id AND r.shortname = 'student'
					inner join sdl_certificate cf on cf.course = c.id AND cf.id = $certificate->id AND ci.certificateid = cf.id
					where ci.reportgrade >=70 or ci.reportgrade ='-'
					order by u.firstname";
	
			$result_urut = mysql_query($sql_urut) or die(mysql_error());
			$rows_urut = mysql_num_rows($result_urut);
			
			
			
			if($rows_urut < 1){ 
				//adib
				$sql_del_ci="delete from ".$cfg->prefix."certificate_issues where certificateid=$certificate->id and reportgrade < 70";
				$qry_del_ci= mysql_query($sql_del_ci);
				//--
				echo "<br>&nbsp";
				echo "&nbsp Error Generate Certificate.";
				echo "<br>&nbsp Tidak ada peserta yang lulus. Mohon cek kembali nilai peserta";
				echo "<br>&nbsp";
				exit();				
			}
			
			$no_urut = 0;
			$urut = "";

			do {
				$row_urut = mysql_fetch_array($result_urut);
				$no_urut = $no_urut + 1;
			}while ($row_urut['user_id'] != $id_lulus && !empty($row_urut));

			//adib n t_cat
			
			$gradess=$row1['nilai_akhir'];
			if($resKode->Kode == 'I') {
								if($gradess>=92.5){
				$pres='1';
				$gred="Grade A";
				$krit="EXCELLENT";
				}
				elseif($gradess>=85 and $gradess<92.5){
				$pres='2';
				$gred="Grade B";
				$krit="VERY GOOD";
				}
				elseif($gradess>=77.5 and $gradess<85){
				$pres='3';
				$gred="Grade C";
				$krit="SATISFACTORY";
				}
				elseif($gradess>=70 and $gradess<77.5){
				$pres='4';
				$gred="Grade D";
				$krit="SUFFICIENT";
				}
				elseif($gradess<70){
				$pres='5';
				$gred="Grade E";
				$krit="NOT GRADUATE";
				}
			}
			else {
				if($gradess>=92.5){
				$pres='1';
				$gred="Grade A";
				$krit="EKSELEN";
				}
				elseif($gradess>=85 and $gradess<92.5){
				$pres='2';
				$gred="Grade B";
				$krit="SANGAT MEMUASKAN";
				}
				elseif($gradess>=77.5 and $gradess<85){
				$pres='3';
				$gred="Grade C";
				$krit="MEMUASKAN";
				}
				elseif($gradess>=70 and $gradess<77.5){
				$pres='4';
				$gred="Grade D";
				$krit="CUKUP";
				}
				elseif($gradess<70){
				$pres='5';
				$gred="Grade E";
				$krit="TIDAK LULUS";
				}
			}
           //--
			if($row_tipe_kode['certificatetype'] == "diklat") {
				$cek_kode = substr($course->idnumber, 1, 1);
				if($cek_kode == '.')
				{
					$var_temp1 = explode('.',$course->idnumber);
					for ($n=1; $n<strlen(implode("",$var_temp1)); $n++) {
						$var_temp2[$n] = $var_temp1[$n];
					}
					$var1 = (int) implode("",$var_temp2);
					$var2 = (int) $no_urut;
					$var3 = $var1 * 2.5 * $var2 + 1001;
				}
	
				elseif($cek_kode!='.')
				{
					$var1 = (int) substr($course->idnumber, 1, 8);
					$var2 = (int) $no_urut;
					$var3 = $var1 * 2.5 * $var2 + 1001;	
				}
		
				if($no_urut>=1 and $no_urut<=9) $urut = "00".$no_urut;
				elseif($no_urut>=10 and $no_urut<=99) $urut = "0".$no_urut;
				elseif($no_urut>99) $urut = $no_urut;
				//$code1 = $course->shortname.".".$urut;
				$code1 = $course->shortname.".".$nip;
				//$test_sql3 = "update sdl_certificate_issues set code = '$code1', ranking = $rank, verification = $var3 where certificateid = $certificate->id and userid = $id_lulus";
				
				$test_sql3 = "update sdl_certificate_issues set code = '$code1', ranking = $rank , KelompokPrestasi = '$pres', Grade='$gred', verification = $var3 where certificateid = $certificate->id and userid = $id_lulus and reportgrade >69 ";
				$result3 = mysql_query($test_sql3) or die(mysql_error());
				
				//passing nilai SIM
				$sql_sync_sim = "select RealisasiJadwalID from sim_realisasijadwaldiklat where KodeSertifikat = '$course->shortname'";
				$query_sync_sim = mysql_query($sql_sync_sim) or die(mysql_error());
				$result_sync_sim = mysql_fetch_array($query_sync_sim);
				  
				$id_realisasi = $result_sync_sim['RealisasiJadwalID'];
				//$sql_update_sim = "update sim_realisasipeserta set NoSertifikat = '$code1', Nilai = '".$row1['nilai_akhir']."', Rangking = '$rank' , Lulus = 'Y' 
							//where RealisasiJadwalID = '$id_realisasi' and NIP = '".$row_urut['nip']."'";
							
				$sql_update_sim = "update sim_realisasipeserta set NoSertifikat = '$code1', 
									Nilai = '".$row1['nilai_akhir']."',
									Rangking = '$rank' ,
									Lulus = 'Y',
									KelompokPrestasi='$pres', 
									Grade='$gred' 
								where 
									RealisasiJadwalID = '$id_realisasi' and NIP = '".$row_urut['nip']."'";
							
				$query_sync_sim = mysql_query($sql_update_sim) or die(mysql_error());
				$sql_del_ci="delete from ".$cfg->prefix."certificate_issues where certificateid=$certificate->id and reportgrade < 70";
			$qry_del_ci= mysql_query($sql_del_ci);
					
					//echo "diklat";
			}

			elseif($row_tipe_kode['certificatetype'] == "workshop"){
				$cek_kode = substr($course->idnumber, 1, 1);
				if($cek_kode == '.')
				{
					$var_temp1 = explode('.',$course->idnumber);
					for ($n=1; $n<strlen(implode("",$var_temp1)); $n++) {
						$var_temp2[$n] = $var_temp1[$n];
					}
					$var1 = (int) implode("",$var_temp2);
					$var2 = (int) $no_urut;
					$var3 = $var1 * 2.5 * $var2 + 1001;		
				}
	
				elseif($cek_kode!='.')
				{
					$var1 = (int) substr($course->idnumber, 1, 8);
					$var2 = (int) $no_urut;
					$var3 = $var1 * 2.5 * $var2 + 1001;	
				}
	
				if($no_urut>=1 and $no_urut<=9) $urut = "00".$no_urut;
				elseif($no_urut>=10 and $no_urut<=99) $urut = "0".$no_urut;
				elseif($no_urut>99) $urut = $no_urut;
				//$code1 = $course->shortname.".".$urut;
				$code1 = $course->shortname.".".$nip;
				$test_sql3 = "update sdl_certificate_issues set code = '$code1', ranking = $urut, verification = $var3 where certificateid = $certificate->id and userid = $id_lulus";
				$result3 = mysql_query($test_sql3) or die(mysql_error());

				//passing nilai SIM
				  $sql_sync_sim = "select RealisasiJadwalID from sim_realisasijadwaldiklat where KodeSertifikat = '$course->shortname'";
				  $query_sync_sim = mysql_query($sql_sync_sim) or die(mysql_error());
				  $result_sync_sim = mysql_fetch_array($query_sync_sim);
				  
				  $id_realisasi = $result_sync_sim['RealisasiJadwalID'];
				  $sql_update_sim = "update sim_realisasipeserta set NoSertifikat = '$code1', Lulus = 'Y' where RealisasiJadwalID = '$id_realisasi' and NIP = '".$row_urut['nip']."'";
				  $query_sync_sim = mysql_query($sql_update_sim) or die(mysql_error());
					//echo "workshop";
			}

			elseif($row_tipe_kode['certificatetype'] == "kompetensi"){
				$cek_kode = substr($course->idnumber, 1, 1);
				if($cek_kode == '.')
				{
					$var_temp1 = explode('.',$course->idnumber);
					for ($n=1; $n<strlen(implode("",$var_temp1)); $n++) {
						$var_temp2[$n] = $var_temp1[$n];
					}
					$var1 = (int) implode("",$var_temp2);
					$var2 = (int) $no_urut;
					$var3 = $var1 * 2.5 * $var2 + 1001;		
				}
	
				elseif($cek_kode!='.')
				{
					$var1 = (int) substr($course->idnumber, 1, 8);
					$var2 = (int) $no_urut;
					$var3 = $var1 * 2.5 * $var2 + 1001;	
				}
		
				if($no_urut>=1 and $no_urut<=9) $urut = "00".$no_urut;
				elseif($no_urut>=10 and $no_urut<=99) $urut = "0".$no_urut;
				elseif($no_urut>99) $urut = $no_urut;
				//$code1 = $course->shortname.".".$urut;
				$code1 = $course->shortname.".".$nip;
				$test_sql3 = "update sdl_certificate_issues set code = '$code1', ranking = $urut, verification = $var3 where certificateid = $certificate->id and userid = $id_lulus";
				$result3 = mysql_query($test_sql3) or die(mysql_error());
	
				//passing nilai SIM
				$sql_sync_sim = "select RealisasiJadwalID from sim_realisasijadwaldiklat where KodeSertifikat = '$course->shortname'";
				$query_sync_sim = mysql_query($sql_sync_sim) or die(mysql_error());
				$result_sync_sim = mysql_fetch_array($query_sync_sim);
				  
				$id_realisasi = $result_sync_sim['RealisasiJadwalID'];
				$sql_update_sim = "update sim_realisasipeserta set NoSertifikat = '$code1', Nilai = '".$row1['nilai_akhir']."', Rangking = '$rank' , Lulus = 'Y' 
								where RealisasiJadwalID = '$id_realisasi' and NIP = '".$row_urut['nip']."'";
				$query_sync_sim = mysql_query($sql_update_sim) or die(mysql_error());				
					//echo "kompetensi";
			}			

			elseif($row_tipe_kode['certificatetype'] == "magang"){
				$cek_kode = substr($course->idnumber, 1, 1);
				if($cek_kode == '.')
				{
					$var_temp1 = explode('.',$course->idnumber);
					for ($n=1; $n<strlen(implode("",$var_temp1)); $n++) {
						$var_temp2[$n] = $var_temp1[$n];
					}
					$var1 = (int) implode("",$var_temp2);
					$var2 = (int) $no_urut;
					$var3 = $var1 * 2.5 * $var2 + 1001;
				}
				
				elseif($cek_kode!='.')
				{
					$var1 = (int) substr($course->idnumber, 1, 8);
					$var2 = (int) $no_urut;
					$var3 = $var1 * 2.5 * $var2 + 1001;	
				}

				if($no_urut>=1 and $no_urut<=9) $urut = "00".$no_urut;
				elseif($no_urut>=10 and $no_urut<=99) $urut = "0".$no_urut;
				elseif($no_urut>99) $urut = $no_urut;
				//$code1 = $course->shortname.".".$urut;
				$code1 = $course->shortname.".".$nip;
				$test_sql3 = "update sdl_certificate_issues set code = '$code1', ranking = $urut, verification = $var3 where certificateid = $certificate->id and userid = $id_lulus";
				$result3 = mysql_query($test_sql3) or die(mysql_error());
	
				//passing nilai SIM
				$sql_sync_sim = "select RealisasiJadwalID from sim_realisasijadwaldiklat where KodeSertifikat = '$course->shortname'";
				$query_sync_sim = mysql_query($sql_sync_sim) or die(mysql_error());
				$result_sync_sim = mysql_fetch_array($query_sync_sim);
				  
				$id_realisasi = $result_sync_sim['RealisasiJadwalID'];
				$sql_update_sim = "update sim_realisasipeserta set NoSertifikat = '$code1', Lulus = 'Y' where RealisasiJadwalID = '$id_realisasi' and NIP = '".$row_urut['nip']."'";
				$query_sync_sim = mysql_query($sql_update_sim) or die(mysql_error());
						
				//echo "magang";
			}			
			
		if($resKode->Kode == 'I')
			require ("$CFG->dirroot/mod/certificate/type/$certificate->certificatetype/certificate_en.php");	
		else
		require ("$CFG->dirroot/mod/certificate/type/$certificate->certificatetype/certificate.php");	
		$file_name = $certificate->name."_".$course->shortname."_".$rs_lulus['nip'];			

		// Output to pdf
		certificate_file_area($id_lulus);
		$filesafe = clean_filename($file_name.'.pdf');
		$file = $CFG->dataroot.'/'.$course->id.'/moddata/certificate/'.$certificate->id.'/'.$id_lulus.'/'.$filesafe;
			
		//echo 'Near if</br>';
	
		if ($certificate->savecert == 1){
			$pdf->Output($file, 'F');//save as file
			//echo 'Test 5: Save as File<br>'; 	
		}			
	}	
	
	$qry_peserta = mysql_query($query_user) or die(mysql_error());
	while($rs_peserta = mysql_fetch_array($qry_peserta)){
		$sql_lulus1 = "SELECT u.id AS user_id, u.username as nip, u.firstname,
					c.id AS course_id, c.shortname, c.fullname, cf.id AS cf_id, cf.course
					FROM	sdl_user u
					inner join sdl_role_assignments ra on u.id = ra.userid	
					inner join sdl_certificate_issues ci on u.id=ci.userid and ci.userid = ".$rs_peserta['user_id']."
					inner join sdl_context con on ra.contextid = con.id AND con.contextlevel = 50
					inner join sdl_course c on con.instanceid = c.id AND c.id = $course->id
					inner join sdl_role r on ra.roleid = r.id AND r.shortname = 'student'
					inner join sdl_certificate cf on cf.course = c.id AND cf.id = $certificate->id AND ci.certificateid = cf.id
					order by u.firstname";
		$qry_lulus1 = mysql_query($sql_lulus1) or die(mysql_error());
		$row_lulus1 = mysql_num_rows($qry_lulus1);
				
		if($row_lulus1 < 1){
			$sql_nilai_akhir = "SELECT finalgrade FROM sdl_grade_grades gg
									inner join sdl_grade_items gi on gg.itemid = gi.id and 
									gi.courseid = $course->id and 
									gi.itemtype = 'course' and 
									userid = ".$rs_peserta['user_id'];
			$qry_nilai_akhir = mysql_query($sql_nilai_akhir) or die(mysql_error());
			$rs_nilai_akhir = mysql_fetch_array($qry_nilai_akhir);
			$rows_nilai_akhir = mysql_num_rows($qry_nilai_akhir);	
			
			//adib
			$nilai = $rs_nilai_akhir ['finalgrade'];
			if($row_nilai_akhir <1);
			//--
		//	$nilai = $rs_nilai_akhir['finalgrade'];
		//	if($rows_nilai_akhir < 1) $nilai = 0.00;

				//passing nilai SIM
				$sql_sync_sim = "select RealisasiJadwalID from sim_realisasijadwaldiklat where KodeSertifikat = '$course->shortname'";
				$query_sync_sim = mysql_query($sql_sync_sim) or die(mysql_error());
				$result_sync_sim = mysql_fetch_array($query_sync_sim);
				  
				$id_realisasi = $result_sync_sim['RealisasiJadwalID'];
				$sql_update_sim = "update sim_realisasipeserta set Nilai = '$nilai', Lulus = 'N', Rangking = 0 ,
					NoSertifikat=null,
				 KelompokPrestasi='',
				 Grade=''
							where RealisasiJadwalID = '$id_realisasi' and NIP = '".$rs_peserta['nip']."'";
				$query_sync_sim = mysql_query($sql_update_sim) or die(mysql_error());
		}
	}
	

	$countuser = 0;	

	$query_Rec1 = "SELECT ci.userid AS user_id
					FROM	sdl_user u
					inner join sdl_role_assignments ra on u.id = ra.userid	
					inner join sdl_certificate_issues ci on u.id=ci.userid
					inner join sdl_context con on ra.contextid = con.id AND con.contextlevel = 50
					inner join sdl_course c on con.instanceid = c.id AND c.id = $course->id
					inner join sdl_role r on ra.roleid = r.id AND r.shortname = 'student'
					inner join sdl_certificate cf on cf.course = c.id AND cf.id = $certificate->id AND ci.certificateid = cf.id
					order by u.firstname";
	$Rec1 = mysql_query($query_Rec1) or die(mysql_error());
	$row_Rec1 = mysql_fetch_assoc($Rec1);


	
	
	do {
		/*$query_Rec2 = sprintf('SELECT * FROM '.$CFG->prefix.'user_info_data  WHERE userid = '.$row_Rec1['user_id'].' and  fieldid=4');
		$Rec2 = mysql_query($query_Rec2) or die("KESALAHAN GENERATE SERTIFIKAT. Sertifikat Diklat tetapi nilai belum diisi. Harap lengkapi nilai terlebih dahulu.");
		$row_Rec2 = mysql_fetch_assoc($Rec2);*/
		
		$sql_nip = "select username from ".$CFG->prefix."user where id = ".$row_Rec1['user_id'];
		$qry_nip = mysql_query($sql_nip) or die(mysql_error());
		$rs_nip = mysql_fetch_array($qry_nip);
		
		$sel_real = "select RealisasiJadwalID from sim_realisasijadwaldiklat where KodeSertifikat = '$course->shortname'";
		$qry_real = mysql_query($sel_real) or die(mysql_error());
		$rs_real = mysql_fetch_array($qry_real);
		
		$sql_eval = "select SudahIsiEvaluasi from sim_realisasipeserta rp inner join 
                    sim_peserta p on rp.PesertaID=p.PesertaID 
                    where rp.NIP = '$rs_nip[username]' and RealisasiJadwalID = $rs_real[RealisasiJadwalID]";
		$qry_eval = mysql_query($sql_eval) or die(mysql_error());
		$rs_eval = mysql_fetch_array($qry_eval);
		
		if($rs_eval['SudahIsiEvaluasi']=='Y') {	
			$file_name2 = $certificate->name."_".$course->shortname."_".$rs_nip['username'];
			$filesafe2 = clean_filename($file_name2.'.pdf');
			$file2 = $CFG->dataroot.'/'.$course->id.'/moddata/certificate/'.$certificate->id.'/'.$row_Rec1['user_id'].'/'.$filesafe2;
			$folder2[$countuser] = $file2;
			$countuser++;
		}
	}while ($row_Rec1 = mysql_fetch_assoc($Rec1));


	$file_gabungan = $certificate->name."_".$course->shortname;
	$filegabungsafe = clean_filename($file_gabungan.'.pdf');

	$pdf1 =& new concat_pdf();
	$pdf1->setFiles($folder2);
	$pdf1->concat();
	$pdf1->Output($CFG->dataroot.'/'.$course->id.'/moddata/certificate/'.$certificate->id.'/'.$filegabungsafe, 'F');

} // tutup else kalo tidak lulus semua	


	// Updated by Rinamay
	// Tgl 23 Desember 2009
	$s="select shortname from sdl_course where id='$course->id'";
	$r=mysql_query($s) or die(mysql_error());
	$w=mysql_fetch_array($r);
	$now=date('Y-m-d');
	$nows=date('Y-m-d h:i:s');
	
	/*// barkah
	// select databse nilai = 0
	$sql_nilai2= "select rp.nilai, rp.Nosertifikat, rp.nama from sim_realisasidiklat rjd inner join sim_realisasipeserta rp inner join sim_peserta p where rjd.realisasijadwalid= rp.realisasijadwalid and rp.pesertaid=p.pesertaid and kodesertifikat='$w[shortname]' and nosertifikat !=''";
   
   // validasi nilai o
   $nilainol=false;
   while ($rs_nilai2=mysql_fetch_array($qry_nilai2))
   {
	   if ($rs_nilai2['Nilai'] == '0.00'){
		   $nilainol=true;
		   break;
		   }
   }*/
//--
	$sql_terbit = "select TanggalTerbit from sim_realisasijadwaldiklat where KodeSertifikat = '$w[shortname]'";
	$qry_terbit = mysql_query($sql_terbit) or die(mysql_error());
	$rs_terbit = mysql_fetch_array($qry_terbit);
	
	if($rs_terbit['TanggalTerbit'] == NULL || $rs_terbit['TanggalTerbit'] == 0 || $rs_terbit['TanggalTerbit'] == '')
	{ 
		$sql_akhir = "select TanggalSelesai,TanggalGenerate,TanggalMulai from sim_realisasijadwaldiklat where KodeSertifikat = '$w[shortname]'";
		$qry_akhir = mysql_query($sql_akhir) or die(mysql_error());
		$rs_akhir = mysql_fetch_array($qry_akhir);
//tcat
		//$nowdate = strtotime(date("Y-m-d"));
		
		$end=$rs_akhir['TanggalSelesai'];
		$stat=$rs_akhir['TanggalMulai'];
		
		$nw=date('Y-m-d');
		$md= strtotime('+3 day',strtotime ( $end ) );
        $maxdate= date ( 'Y-m-d' , $md );
		
		//if($nw<=$maxdate or ($end>='2012-06-11' and $end <='2012-06-18') ){
			if($nw<=$maxdate or ($nw<=$end and $nm>=$stat)){
		$sql_upd_terbit = "update sim_realisasijadwaldiklat set TanggalTerbit = '$rs_akhir[TanggalSelesai]',  TanggalGenerate='$now' where KodeSertifikat = '$w[shortname]'";
		$sql_upd_terbit2 = "update sim_realisasijadwaldiklat set TanggalTerbit = '$rs_akhir[TanggalSelesai]',  TanggalRevisi='$now' where KodeSertifikat = '$w[shortname]'";
		}
		else{
		$sql_upd_terbit = "update sim_realisasijadwaldiklat set TanggalTerbit = '$now',  TanggalGenerate='$now' where KodeSertifikat = '$w[shortname]'";
		$sql_upd_terbit2 = "update sim_realisasijadwaldiklat set TanggalTerbit = '$now',  TanggalRevisi='$now' where KodeSertifikat = '$w[shortname]'";
		}
		
		if($rs_akhir['TanggalGenerate']==NULL)
		$qry_upd_terbit = mysql_query($sql_upd_terbit) or die(mysql_error());	
		else 
		$qry_upd_terbit = mysql_query($sql_upd_terbit2) or die(mysql_error());	
	}
//--tcat---	
	//barkah 
	/*else if($nilainol)
	{
		$s="update sim_realisasijadwaldiklat
		  set TanggalRevisi='$now',
		  CounterRevisi=CounterRevisi+1,
		  TanggalEdit='$nows'
		  where KodeSertifikat='$w[shortname]' and StatusRevisi<1";
		$r=mysql_query($s) or die(mysql_error());
		echo $s."<br>nilai=".$rs_nilai2['Nilai']."<br>";
	}
	*/
	else {
		
		$s="update sim_realisasijadwaldiklat
		  set 
		  CounterRevisi=CounterRevisi+1,
		  TanggalEdit='$nows',
		  TanggalRevisi='$now'
		  where KodeSertifikat='$w[shortname]' and StatusRevisi=2";
		$r=mysql_query($s) or die(mysql_error());
		
		$s="update sim_realisasijadwaldiklat
		  set TanggalRevisi='$now',
		  StatusRevisi=StatusRevisi+1,
		  CounterRevisi=CounterRevisi+1,
		  TanggalEdit='$nows'
		  where KodeSertifikat='$w[shortname]' and CounterRevisi<2";
		$r=mysql_query($s) or die(mysql_error());

  }
  
	  $sql_sync_sim = "select RealisasiJadwalID from sim_realisasijadwaldiklat where KodeSertifikat = '$course->shortname'";
	  $query_sync_sim = mysql_query($sql_sync_sim) or die(mysql_error());
	  $result_sync_sim = mysql_fetch_array($query_sync_sim);
				  
	  $id_real = $result_sync_sim['RealisasiJadwalID'];

	$nowr=date('Y-m-d');
	$s="update sim_realisasijadwaldiklat
		set LockSertifikat='Y',
		TanggalEdit='$nows',
		TanggalRevisi='$nowr'
		where RealisasiJadwalID = $id_real and TanggalRevisi>0 and 
		((unix_timestamp(curdate()) - unix_timestamp(TanggalRevisi) >= 172800) or StatusRevisi>0)";
	
	/*$s="update sim_realisasijadwaldiklat
		set LockSertifikat='Y',
		TanggalEdit='$nowr',
		TanggalRevisi='$nowr'
		where RealisasiJadwalID = $id_real and TanggalRevisi>0 and StatusRevisi>0";*/
	$r=mysql_query($s) or die(mysql_error());

	echo 'Finish Generate All Certificate';
	print_footer();	

?>