<?php
    session_start();
	
    $con = mysql_connect("localhost", "root", "") or die(mysql_error());
	$db = mysql_select_db("lms") or die(mysql_error());
	
	$id = $_GET['id']; 
	$nip = array();
	
	$q = mysql_query("select nip from sim_realisasipeserta a, sim_realisasijadwaldiklat b 
		where a.realisasijadwalid = b.realisasijadwalid and b.kodediklat = '$id'");
	
	
	while($f = mysql_fetch_array($q, MYSQL_NUM)) {
		array_push($nip, $f[0]);
	}
	
	$_SESSION['nipSertifikat'] = $nip;
	header("Location: new.php?id=".$id."");
?>