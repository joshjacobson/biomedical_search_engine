<?php
	// Get a connection for the database
	require_once('mysqlconnect_sandbox.php');
	if (($name = strtolower(post('query')))) {
		// Create a query for the database
	}
	else{
		$name = ''; // Leave empty if nothing entered
	}
?>

<html>
	<head>
		<title> Medical Search Engine</title>
	</head>

	<body>
	
		<h1> Biomedical Search Engine </h1>
		<form action="lookup.php" method="post">
			<fieldset>
				<legend> Please enter a biomedical terminology.</legend>
					
				<label for="query"> Terminology: </label>
				<input type="text" name="query" id="query" size="20" value="<?=$name?>" />
				<br />
				<br />
					
				<input type="submit" name="subform" value="SEARCH" />
			</fieldset>
		</form>

	<?php
		if($name){ // If name of query is given
	?>

	<hr />

	<?php
		
		//Pre-processing of words to normalize
		
		// WORD COUNT ---------------------------------------------
		$word_count = 0;
		$input_length = strlen($name); //find length of input string
		
		// Determine word count of the input query
		for ($i = 0; $i < $input_length; $i++) { 
			if($name[$i] == ' ' and $i != 0 and $name[$i-1] != ' '){
				$word_count++;
    		}
		}
		$word_count++; // to account for last word or only 1 word
		
		// PLACE WORDS INTO SEPERATE VARIABLES & CLEAN THEM ---------
		$temp_word = ' '; // will contain temporary word
		$a = 0; // character index for temp_word
		$w = 0; // word number
		for ($i = 0; $i < $input_length; $i++) {
			
			if($name[$i] != ' '){ //if not empty, place char in temp_word
    			$temp_word[$a] = $name[$i];
    			$a++;
    		}
    		
    		//once you arrive at a space or the last character, place temp into word array
			if($name[$i] == ' ' and $i != 0 and $name[$i-1] != ' ' or ($i == $input_length - 1) ){
    			$a = 0;
    			
    			// Clean up the temp_word
    			$b = 0; //character index for $clean_word
    			$clean_word = ' ';
    			$temp_length = strlen($temp_word);
    			
    			//Remove all non-alphabets
    			for ($j = 0; $j < $temp_length; $j++){
    				if( preg_match("/^[a-z]$/i", $temp_word[$j]) ){
    					$clean_word[$b] = $temp_word[$j];
    					$b++;
    				}
    			}
    			
    			//Remove basic plurality 's'
    			//if( strlen($clean_word) > 1 and $clean_word[($b-1)] == 's' and $clean_word[($b-2)] != 's'){
    			//	$clean_word = chop($clean_word, 's'); //then delete the plural 's'
    			//	}
    			
    			//Uninflect words by removing 'ing' from verbs
    			
    			if( $clean_word != ' ' ){
    				$word[$w] = $clean_word;
    				$w++;
    			}
    			else{
    				$word_count--;
    			}
    			
    			$temp_word = ' ';
    		}
		}
		
		// REMOVE WORDS LIKE "the","is","a","and" ---------
		$unset_count = 0;
		for($w = 0; $w < $word_count; $w++){
			if( $word[$w]=='the' or $word[$w]=='is' or $word[$w]=='a' or $word[$w]=='and' ){
				unset($word[$w]);
				$unset_count++;
			}
		}
		$word_count = $word_count - $unset_count;
		$word = array_values($word); //set order to account for words deleted in between
		
		// CREATE WORD QUERIES -----------------------------
		$word_limit = $word_count;
		
		// combine all words into a clean entry
		$searchstring = $word[0]; //set it to the first word
		for($j = 1; $j < $word_limit; $j++){ //accumulate all inputs
			$searchstring = $searchstring . ' ' . $word[$j];
		}
		
		// NORMALIZED STR GENERATION -----------------------
		$norm_word = $word;
		sort($norm_word);
		
		$norm_str = $norm_word[0]; //set it to the first word
		for($j = 1; $j < $word_limit; $j++){ //accumulate all inputs
			$norm_str = $norm_str . ' ' . $norm_word[$j];
		}

		// SEND NORMALIZED QUERY TO CLASSIFIER ----------------
		// $TUI contains the semantic type of interest
		$TUI = 'T047';
		$TUI_string = shell_exec('java -jar umls_bayes_classifier.jar ' . $norm_str); // second element has normalized string
		$TUI_string_length = strlen($TUI_string);
		$TUI[0] = $TUI_string[$TUI_string_length - 5];
		$TUI[1] = $TUI_string[$TUI_string_length - 4];
		$TUI[2] = $TUI_string[$TUI_string_length - 3];
		$TUI[3] = $TUI_string[$TUI_string_length - 2];
		
		// CREATE MYSQL SEARCH ---------------------------------
		// Create the MYSQL search by appending words
		if($word_count == 1){
			$CUIrootsearch = "SELECT distinct cs.CUI, c.STR FROM sandbox.semantic_concept_nstring cs INNER JOIN sandbox.concept c ON cs.CUI=c.CUI WHERE cs.TUI='$TUI' AND c.STR LIKE '$searchstring%'";
			$CUIresult = @mysqli_query($dbc, $CUIrootsearch);
			$CUIarray = mysqli_fetch_array($CUIresult);
			if(empty($CUIarray)){
				$CUIrootsearch = "SELECT distinct cs.CUI, c.STR FROM sandbox.semantic_concept_nstring cs INNER JOIN sandbox.concept c ON cs.CUI=c.CUI WHERE cs.TUI='$TUI' AND c.STR LIKE '%$searchstring%'";
			}
		}
		
		else{
			$done_looping = 0;
			$CUIrootsearch = "SELECT distinct cs.CUI, c.STR FROM sandbox.semantic_concept_nstring cs INNER JOIN sandbox.concept c ON cs.CUI=c.CUI WHERE cs.TUI='$TUI' AND c.STR LIKE '%$searchstring%'";
			while($word_limit != 1 and $done_looping == 0){
				$CUIrootsearch = $CUIrootsearch . " OR cs.TUI='$TUI'";
				for($j = 0; $j < $word_limit; $j++){ //accumulate all inputs
					$CUIrootsearch = $CUIrootsearch . " AND c.STR LIKE " . "'%$word[$j]%'";
				}
				$CUIresult = @mysqli_query($dbc, $CUIrootsearch);
				$CUIarray = mysqli_fetch_array($CUIresult);
				if(!empty($CUIarray)){
					$done_looping = 1;
				}
				else{
					$word_limit--;
				}
			}
			$CUIresult = @mysqli_query($dbc, $CUIrootsearch);
			$CUIarray = mysqli_fetch_array($CUIresult);
			if(empty($CUIarray)){
				$CUIrootsearch = "SELECT distinct cs.CUI, c.STR FROM sandbox.semantic_concept_nstring cs INNER JOIN sandbox.concept c ON cs.CUI=c.CUI WHERE cs.TUI='$TUI' AND c.STR like '%$word[0]%'";
			}
		}
		
		// BEGIN SEARCH ----------------------------------
		$CUIlookup = $CUIrootsearch;
		$response = @mysqli_query($dbc, $CUIlookup);

		if($response){ // If MySQL connection is good
		
			$CUIresult = @mysqli_query($dbc, $CUIlookup);
			
			$pos = 0;
			while($CUIarray = mysqli_fetch_array($CUIresult)){
				$CUI_container[$pos] = $CUIarray['CUI'];
				$CUI_STR[$pos] = $CUIarray['STR'];
				$pos++;
			}
			
			if( !empty($CUI_container) ){
				echo " Please select from the matches below";
				?>
				
				<form action="project.php" method="post">
  					<select name="choice">
  					
  					<?php
  					$no_CUIs = count($CUI_container);
  					for($j = 0; $j < $no_CUIs; $j++){
  					?>
    					<option value=<?=$CUI_container[$j]?>><?=$CUI_container[$j]?> <?=$CUI_STR[$j]?></option>
    				<?php
  					}
  					?>
  					
  					</select>
					</p>
					<input type="hidden" name="TUI" value="<?=$TUI?>"/>
					<input type="submit" name="SELECT" value="SELECT"/>
					
				</form>
				<?php
			}
			
			else{
				echo '<tr><td align="left"> No matches could be found. Please try again. ';
			}

		}

		else {
			echo "Couldn't perform query '<br />";
			echo mysqli_error($dbc);
		}

	}

// Close connection to the database
//mysqli_close($dbc);
?>

	</body>
</html>
