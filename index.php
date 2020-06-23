<?php
// DO NOT REMOVE!
include("includes/init.php");
// DO NOT REMOVE!
$db = open_sqlite_db("secure/data.sqlite");
$messages = array();
$stages = array("Basic", "Stage 1", "Stage 2", "Legendary");
$types = array("Normal", "Fighting", "Flying", "Poison", "Ground", "Rock", "Bug", "Ghost", "Steel", "Fire", "Water", "Grass", "Electric", "Psychic", "Ice", "Dragon", "Dark", "Fairy");
const SEARCH_CATS = [
  "pname" => "By Name",
  "ptype" => "By Type",
  "pokedex_num" => "By Pokedex Number",
  "evolution_stage" => "By Evolution Stage"
];

if (isset($_POST["add-submit"])) {
  $valid_add = TRUE;

  $user_pname = filter_input(INPUT_POST, "pname", FILTER_SANITIZE_STRING);
  if ($user_pname == '') {
    $valid_add = FALSE;
    $show_pname_error = TRUE;
  }

  $user_ptype = filter_input(INPUT_POST, "ptype", FILTER_SANITIZE_STRING);
  $comma_loc = strpos($user_ptype, ',');
  if ($comma_loc !== FALSE) {
    $type1 = substr($user_ptype, 0, $comma_loc);
    $type2 = substr($user_ptype, $comma_loc+2);
    if (!in_array($type1, $types) || !in_array($type2, $types)) {
      $valid_add = FALSE;
      $show_ptype_error = TRUE;
    }
  }
  else {
    if (!in_array($user_ptype, $types)) {
      $valid_add = FALSE;
      $show_ptype_error = TRUE;
    }
  }

  $user_pdex_num = filter_input(INPUT_POST, "pdex_num",  FILTER_VALIDATE_INT);
  if ($user_pdex_num < 1 || $user_pdex_num > 809) {
    $valid_add = FALSE;
    $show_pdex_num_error = TRUE;
  }

  $user_evo_stage = filter_input(INPUT_POST, "evo_stage", FILTER_SANITIZE_STRING);
  if (!in_array($user_evo_stage, $stages)) {
    $valid_add = FALSE;
    $show_evo_stage_error = TRUE;
  }

  $user_password = filter_input(INPUT_POST, "password",  FILTER_SANITIZE_STRING);
  if (htmlspecialchars($user_password) != "Secretly, I like Digimon better.") {
    $valid_add = FALSE;
    $show_password_error = TRUE;
  }
}

function print_record($record) {
?>
  <tr>
    <td><?php echo htmlspecialchars($record["pname"]);?></td>
    <td><?php echo htmlspecialchars($record["ptype"]);?></td>
    <td><?php echo htmlspecialchars($record["pokedex_num"]);?></td>
    <td><?php echo htmlspecialchars($record["evolution_stage"]);?></td>
  </tr>
  <?php
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <title>Home</title>
  <link rel="stylesheet" type="text/css" href="styles/all.css" media="all" />
</head>

<body>
  <?php include("includes/header.php"); ?>
  <div id="page-content">
    <?php
    if (isset($_POST["add-submit"])) {
      if ($valid_add == TRUE) {
        $sql = "INSERT INTO pokemon (pname, ptype, pokedex_num, evolution_stage) VALUES (:pname, :ptype, :pokedex_num, :evolution_stage);";
        $params = array(':pname' => $user_pname, ':ptype' => $user_ptype,':pokedex_num' => $user_pdex_num,':evolution_stage' => $user_evo_stage);
        $insert_result = exec_sql_query($db, $sql, $params);
        if ($insert_result) {
          array_push($messages, "Thank you for adding a Pokemon to the Database!");
        } else {
          array_push($messages, "Failed to add Pokemon.");
        }
      }
      else {
        array_push($messages, "Failed to add Pokemon.");
      }
    }


    if (isset($_GET["search-submit"]) & isset($_GET['search_cat'])) {
      $complete_search = TRUE;

      $user_search_cat = filter_input(INPUT_GET, 'search_cat', FILTER_SANITIZE_STRING);
      if (in_array($user_search_cat, array_keys(SEARCH_CATS))) {
        $user_cat_translated = $user_search_cat;
      }
      else {
        array_push($messages, "Invalid category for search.");
        $complete_search = FALSE;
      }

      $user_search = filter_input(INPUT_GET, "search", FILTER_SANITIZE_STRING);
      $user_search = trim($user_search);
      if ($user_search == "") {
        array_push($messages, "Please enter a search.");
        $complete_search = FALSE;
      }
    }
    else if (isset($_GET["search-submit"])) {
      if (!isset($_GET['search_cat'])) {
        array_push($messages, "Please choose a search category.");
      }
    }

    if ($complete_search == TRUE) {
      $sql = "SELECT * FROM pokemon WHERE $user_cat_translated LIKE '%'||:search||'%';";
      $params = array(':search' => $user_search);
      $result = exec_sql_query($db, $sql, $params);
      array_push($messages, "SEARCH RESULTS:");
    }
    else {
      $sql = "SELECT * FROM pokemon;";
      $params = array();
      $result = exec_sql_query($db, $sql, $params);
    } ?>

    <?php
    foreach ($messages as $message) {
      echo "<h2 class='message'>" . htmlspecialchars($message) . "</h2>\n";
    }
    ?>
    <div id="search-form">
      <form action="index.php" method="GET">
        <label for="search">Enter Your Search Here:  </label>
        <input name="search" id="search" type="text">
        <select name="search_cat" id="category">
          <option selected="selected" disabled="disabled">Search By</option>
          <?php
            foreach(SEARCH_CATS as $field_name => $label) {
          ?>
              <option value="<?php echo $field_name;?>"><?php echo $label;?></option>
          <?php
          }
          ?>
        </select>
        <input id="submit" type="submit" name="search-submit" value="Search" >
      </form>
    </div>

    <div id="table-content">
    <?php
    $records = $result->fetchAll();
    if (count($records) > 0) { ?>
      <table>
        <tr>
          <th>Name</th>
          <th>Type(s)</th>
          <th>Pokedex Number</th>
          <th>Evolution Stage</th>
        </tr>
      <?php
      foreach ($records as $record) {
        print_record($record);
      } ?>
      </table>
    <?php
    }
    else {
      echo "<h3 class='message'>No search results found. Please try another search.</h3>\n";
    }
    ?>
    </div>


    <div id="add-content-form">
      <h2>Enter a New Pokemon Here:</h2>
      <form action="index.php" method="POST">
        <label for="pname">Name:*</label>
        <input name="pname" id="pname" type="text" value="<?php if(isset($user_pname) & $valid_add != TRUE) { echo htmlspecialchars($user_pname); } ?>">
        <p class="form_error <?php if ( !isset($show_pname_error) ) { echo 'hidden'; } ?>">Please provide a name.</p>

        <label for="ptype">Type(s):*</label>
        <input name="ptype" id="ptype" type="text" value="<?php if(isset($user_ptype) & $valid_add != TRUE) { echo htmlspecialchars($user_ptype); } ?>">
        <p class="form_error <?php if ( !isset($show_ptype_error) ) { echo 'hidden'; } ?>">Please provide a valid type or types.</p>

        <label for="pdex_num">Pokedex #:*</label>
        <input name="pdex_num" id="pdex_num" type="number" value="<?php if(isset($user_pdex_num) & $valid_add != TRUE) { echo htmlspecialchars($user_pdex_num); } ?>" min=1 max=809>
        <p class="form_error <?php if ( !isset($show_pdex_num_error) ) { echo 'hidden'; } ?>">Please provide the Pokedex Number.</p>

        <label for="evo_stage">Stage of Evolution:*</label>
        <select name="evo_stage" id="evo_stage">
          <option selected="selected" disabled="disabled">Choose Evolution Stage</option>
          <option <?php if($user_evo_stage=="Basic" & $valid_add != TRUE) echo 'selected="selected"'; ?> value="Basic">Basic</option>
          <option <?php if($user_evo_stage=="Stage 1"  & $valid_add != TRUE) echo 'selected="selected"'; ?> value="Stage 1">Stage 1</option>
          <option <?php if($user_evo_stage=="Stage 2"  & $valid_add != TRUE) echo 'selected="selected"'; ?> value="Stage 2">Stage 2</option>
          <option <?php if($user_evo_stage=="Legendary"  & $valid_add != TRUE) echo 'selected="selected"'; ?> value="Legendary">Legendary</option>
        </select>
        <p class="form_error <?php if ( !isset($show_evo_stage_error) ) { echo 'hidden'; } ?>">Please provide a valid choice.</p>

        <label for="password">Whats the Password?:*</label>
        <input name="password" id="password" type="password" value="<?php if(isset($user_password) & $valid_add != TRUE) { echo htmlspecialchars($user_password); } ?>">
        <p class="form_error <?php if ( !isset($show_password_error) ) { echo 'hidden'; } ?>">Please provide the correct password.</p>

        <input id="add-submit" type="submit" name="add-submit" value="Add" >
      </form>
    </div>
  </div>
  <?php include("includes/footer.php"); ?>
</body>
</html>
