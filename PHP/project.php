<?php

	if (!isset($_POST['SELECT'])){
        header("Location:lookup.php");
        }
	// Get a connection for the database
	require_once('mysqlconnect_sandbox.php');
	if (isset($_POST['choice'])){
		$CUI = $_POST['choice'];
		#$QRY = "SELECT v.ID FROM sandbox.vertex v WHERE v.UI='$CUI';";
		#$ID_RESULT = @mysqli_query($dbc, $QRY);
		#$ID_ARRAY = mysqli_fetch_array($ID_RESULT);
		#$NEW_CUI = $ID_ARRAY['ID'];
		$BASH = "/var/www/scritps/cui_graph.bash " . $CUI;
		$VISUALIZER = shelL_exec($BASH);
		$URL = shell_exec('python visualizer.py ' . $CUI);
	}
	if (isset($_POST['TUI'])) {
		 $TUI=$_POST['TUI'];
	}
?>

<html>
	<head>
		<title> Medical Search Engine Results</title>
	</head>

	<body>
	
		<h1> Biomedical Search Engine Results</h1>


	<?php
		if($CUI){ // If name of query is given
	?>

	<hr />

	<?php
	
	$Namelookup = "SELECT c.STR,c.DEF FROM sandbox.concept c WHERE c.CUI = '$CUI'";
	$Nameresult = @mysqli_query($dbc, $Namelookup);
	$Namearray = mysqli_fetch_array($Nameresult);
	$Name = $Namearray['STR']; //store semantic type in $Class
	$DEF = $Namearray['DEF'];			

	$Classlookup = "SELECT st.STY FROM sandbox.concept_semantic cs INNER JOIN sandbox.semantic_type st ON cs.TUI=st.TUI WHERE cs.CUI = '$CUI' AND st.TUI = '$TUI'";
	$Classresult = @mysqli_query($dbc, $Classlookup);
	$Classarray = mysqli_fetch_array($Classresult);
	$Class = $Classarray['STY']; //store semantic type in $Class
		
#	$DEFlookup = "SELECT DEF FROM MRDEF WHERE CUI = '$CUI' AND SAB LIKE '%Medline%'";
#	$DEFresult = @mysqli_query($dbc, $DEFlookup);
#	$DEFarray = mysqli_fetch_array($DEFresult);
#	$DEF = $DEFarray['DEF']; //store definition in $DEF

	//Print the information found
	echo '<br><b>Selection:</b><br>' . $Name .
	'<br><br><b>Classification:</b><br>' . $Class . 
	'<br><br><b>Definition:</b><br>' . $DEF . 
	'<br><br><b>Egonet:</b><br><br><iframe src="' .$URL .'" height=80% width=80%></iframe>' .
	'<br><br><b>Relationships:</b><br>';
			
	//Search for CUI's connections with other CUI's
	//Implement classification for better results
	$Relationslookup = "SELECT c.STR FROM sandbox.concept_relationship cr INNER JOIN sandbox.concept c ON cr.CUI1=c.CUI WHERE cr.CUI2='$CUI'";
	$Relationslookup .= " UNION ";
	$Relationslookup .= "SELECT c.STR FROM sandbox.concept_relationship cr INNER JOIN sandbox.concept c ON cr.CUI2=c.CUI WHERE cr.CUI1='$C1965619';";
	$Relationsresult = @mysqli_query($dbc, $Relationslookup);
				
	while($Relationsarray = mysqli_fetch_array($Relationsresult)){
		echo $Relationsarray['STR'] . '<br>';
	}
	
		}
	?>

	</body>
</html>
