<html lang="en-us">
<head>

  <meta charset="utf-8"/>

  <?php
  // EDIT THIS FILE TO SET YOUR LEADERBOARD PARAMETERS
  require("config.php");
  ?>

  <title>AoC<?= substr(strval($event_year), 2, 2) ?> Leaderboard #<?= $board_id ?></title>

  <link href='//fonts.googleapis.com/css?family=Source+Code+Pro:300&subset=latin,latin-ext' rel='stylesheet' type='text/css'>

  <style>
    body {
      background-color: #0f0f23;
      color: #cccccc;
      font-family: "Source Code Pro", monospace;
      font-size: 14pt;
    }
    .star {
      font-size: 18pt;
      line-height: 80%;
    }
    .star-big {
      font-size: 20pt;
    }
    .star-gold {
      color: #ffff66;
      text-shadow: 0 0 5px #ffff66;
    }
    .star-silver {
      color: #666699;
    }
    .star-gray {
      color: #333333;
    }
    #main-table {
      border-collapse: collapse;
    }
    #main-table td, th {
      border: 1px solid rgb(51, 51, 51);
      padding-left: 5px;
      padding-right: 5px;
      text-align: center;
      font-size: 14pt;
      height: 35px;
    }
    #main-table td.left-align {
      text-align: left;
    }
    #main-table td.star-table-1 {
      border-right: 0px;
      padding-left: 3px;
      padding-right: 0px;
      text-align: center;
      min-width: 14px;
    }
    #main-table td.star-table-2 {
      border-left: 0px;
      padding-left: 0px;
      padding-right: 3px;
      text-align: center;
      min-width: 14px;
    }
    input {
      border: 1px solid #666666;
      background: #10101a;
      font-family: inherit;
      font-weight: normal;
      color: white;
      font-size: 12pt;
    }
    small {
      font-size: 80%;
      color: #888888;
    }
    .left-align {
      text-align: left;
    }
    .gold {
      color: #ffda59;
      text-shadow: 0 0 5px #ffda59;
      font-weight: bold;
    }
    .silver {
      color: #d3e2ec;
      text-shadow: 0 0 5px #d3e2ec;
      font-weight: bold;
    }
    .bronze {
      color: #e28960;
      text-shadow: 0 0 5px #e28960;
      font-weight: bold;
    }
    a {
      text-decoration: none;
      color: #009900;
    }
    a:hover {
      color: #99ff99;
    }
    a.tooltip {
      text-decoration: none;
      color: #ffff66;
    }
    a.tooltip:hover {
      cursor: crosshair;
      font-size: 20pt;
      position: relative
    }
    a.tooltip > span, a.tooltip2 > span {
      text-align: left;
      color: white;
      display: none;
      font-weight: normal;
      font-size: 12pt;
    }
    a.tooltip:hover > span, a.tooltip2:hover > span {
      border: #aaaaaa 1px solid;
      background-color: black;
      padding: 8px 8px 8px 8px;
      display: block;
      z-index: 100;
      position: absolute;
      left: 5px;
      top: 20px;
      margin: 10px;
      text-decoration: none;
      white-space: nowrap;
    }
    a.tooltip2 {
      text-decoration: none;
      color: white;
      font-size: 10pt;
      padding-left: 5px;
    }
    a.tooltip2:hover {
      position: relative
    }
    </style>
</head>
<body>

  <div id="content">

  <?php

  /****************************************************************************/

  // Retrieve and decode the board's JSON file
  // Implements caching of the file so as to not retrieve it again from AoC's
  // server if it's been retrieved less than $cache_seconds seconds ago.
  function get_data($board_id, $event_year, $session_id) {

    // Path to the JSON file on the local server
    $JSON_path = "./board_" . $board_id . ".json";

    // Number of seconds to cache the file
    $cache_seconds = 900;

    // The URL of the JSON file on the AoC server
    $remote_url = "https://adventofcode.com/" . $event_year . "/leaderboard/private/view/" . $board_id . ".json";

    // Determine if file needs to be retrieved from the AoC server
    if (!file_exists($JSON_path)) {
      $retrieve = true;
    } else if (file_exists($JSON_path)) {
      $retrieve = ((time() - filemtime($JSON_path)) > $cache_seconds);
    }

    // Retrieve file from the AoC server if nonexistent or cache expired
    if ($retrieve) {

      // Try and retrieve JSON file using cURL
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $remote_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: session=" . $session_id));
      $result = curl_exec($ch);
      $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      // Save to disk if retrieval was successful
      if ($status == "200") {
        $file = fopen($JSON_path, 'w');
        if ($file) {
          fwrite($file, $result);
          fclose($file);
        }
      }

    }

    // Read file from disk
    if (file_exists($JSON_path)) {
      $content = file_get_contents($JSON_path);
      $mtime = filemtime($JSON_path);
    } else {
      return null;
    }

    // Decode JSON
    $data = json_decode($content, true);
    $data["last_update"] = $mtime;

    return $data;

  }

  /****************************************************************************/

  // Format the solve time as a nice string
  function nice_solve_time($puzzle_open, $ts) {
    $open_ts = $puzzle_open->getTimestamp();
    $secs = $ts - $open_ts;
    $result = "";
    $days = 0;
    $hours = 0;
    $minutes = 0;
    $bits = [];
    if ($secs > 86400) {
      $days = intval($secs/86400);
      array_push($bits, $days . "d");
      $secs -= $days * 86400;
    }
    if ($secs > 3600 || count($bits) > 0) {
      $hours = intval($secs/3600);
      array_push($bits, $hours . "h");
      $secs -= $hours * 3600;
    }
    if ($secs > 60 || count($bits) > 0) {
      $minutes = intval($secs/60);
      array_push($bits, $minutes . "m");
      $secs -= $minutes * 60;
    }
    array_push($bits, $secs . "s");
    return implode(" ", $bits);
  }

  // Formats a rank as a nice string
  function nice_rank($rank) {
    if ($rank == 1) return "1st";
    else if ($rank == 2) return "2nd";
    else if ($rank == 3) return "3rd";
    else if ($rank > 3) return $rank . "th";
  }

  /****************************************************************************/

  if (isset($_GET["sort_field"])) {
    $sort_field = $_GET["sort_field"];
  } else {
    $sort_field = "local_score";
  }

  if (!empty($board_id)) {
    $json_fname = $board_id . ".json";
  }

  // Get data -- either from the server's cache or from AoC.com
  if (!empty($board_id) && !empty($session_id)) {
    $data = get_data($board_id, $event_year, $session_id);
  } else {
    $data = null;
  }

  /****************************************************************************/
  // OUTPUT PAGE

  if (!empty($data)) { ?>

    <h4>Advent of Code <?= $event_year ?> &mdash; Private leaderboard #<?= $board_id ?></h4>

    <?php

    # Initialize players
    $players = [];
    foreach ($data["members"] as $player) {
      $player["gold"] = 0;
      $player["silver"] = 0;
      $player["bronze"] = 0;
      $player["num_medals"] = 0;
      $player["num_stars"] = $player["stars"];
      $player["stars"] = array();
      for ($day = 1; $day <= 25; $day++) {
        for ($star_num = 1; $star_num <= 2; $star_num++) {
          $player["stars"][$day][$star_num]["rank"] = null;
          if (isset($player["completion_day_level"][$day][$star_num]["get_star_ts"])) {
            $player["stars"][$day][$star_num]["ts"] = $player["completion_day_level"][$day][$star_num]["get_star_ts"];
          } else {
            $player["stars"][$day][$star_num]["ts"] = null;
          }
        }
      }
      $players[$player["id"]] = $player;
    }

    // Determine ranks and count medals for each star
    for ($day = 1; $day <= 25; $day++) {
      for ($star_num = 1; $star_num <= 2; $star_num++) {

        // Gather solve times
        $times = [];
        foreach ($players as $player) {
          $s = $player["stars"][$day][$star_num];
          if (!is_null($s["ts"])) {
            $times[] = [$player["id"], $s["ts"]];
          }
        }

        if (count($times) > 0) {

          // Sort by solve time
          usort($times, function($a, $b) {
            if ($a[1] < $b[1]) return -1;
            else if ($a[1] > $b[1]) return +1;
            else return 0;
          });

          // Save rank and medals
          for ($i = 0; $i < count($times); $i++) {
            $rank = $i + 1;
            $pid = $times[$i][0];
            $ts = $times[$i][1];
            // echo $rank . " " . $pid . " " . $ts . "\n";
            $players[$pid]["stars"][$day][$star_num]["rank"] = $rank;
            if ($rank == 1) {
              $players[$pid]["gold"] += 1;
              $players[$pid]["num_medals"] += 1;
            } else if ($rank == 2) {
              $players[$pid]["silver"] += 1;
              $players[$pid]["num_medals"] += 1;
            } else if ($rank == 3) {
              $players[$pid]["bronze"] += 1;
              $players[$pid]["num_medals"] += 1;
            }
          }

        }

      }
    }

    // Compute the points awarded to each rank
    $points_rank = array();
    for ($rank = 1; $rank <= count($players); $rank++) {
      $points_rank[$rank] = count($players) - $rank + 1;
    }

    // Sort players by selected sort field
    usort($players, function ($a, $b) {
      global $sort_field;
      if ($a[$sort_field] < $b[$sort_field]) {
        return +1;
      } else if ($a[$sort_field] > $b[$sort_field]) {
        return -1;
      } else {
        return 0;
      }
    });

    $medals_tot_img = '<img src="medals.png"/>';
    $medal_gold_img = '<img src="medal_gold.png"/>';
    $medal_silver_img = '<img src="medal_silver.png"/>';
    $medal_bronze_img = '<img src="medal_bronze.png"/>';

    ?>

    <table id="main-table">

      <tr>
        <th><strong>#</strong></th>
        <th class="left-align"><strong>Name</strong></th>
        <th><strong>Score</strong></th>
        <th><span class="star-big star-gold">*</span></th>
        <th><?= $medal_gold_img ?></th>
        <th><?= $medal_silver_img ?></th>
        <th><?= $medal_bronze_img ?></th>
        <th><?= $medals_tot_img ?></th>
        <!--<th><strong>GScore</strong></th>-->
        <?php for ($day = 1; $day <= 25; $day++) { ?>
          <th colspan="2"><?= $day ?></th>
        <?php } ?>
      </tr>

      <?php for ($i = 0; $i < count($players); $i++) { ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td class="left-align"><?= $players[$i]["name"] ?></td>
          <td><?= $players[$i]["local_score"] ?></td>
          <td><?= $players[$i]["num_stars"] ?></td>
          <td><?= $players[$i]["gold"] ?></td>
          <td><?= $players[$i]["silver"] ?></td>
          <td><?= $players[$i]["bronze"] ?></td>
          <td><?= $players[$i]["num_medals"] ?></td>
          <!--<td><?= $players[$i]["global_score"] ?></td>-->
          <?php
          for ($day = 1; $day <= 25; $day++) {

            $puzzle_open = new DateTime($event_year . '-12-' . $day . "T" . "05:00:00Z");

            for ($star_num = 1; $star_num <= 2; $star_num++) { ?>

              <td class="<?= 'star-table-' . $star_num ?>">

              <?php $cell = "";

              if (!is_null($players[$i]["stars"][$day][$star_num]["ts"])) {

                $s = $players[$i]["stars"][$day][$star_num];

                $solve_ts = $s["ts"];
                $solve_datetime = new DateTime('@' . $solve_ts);
                $solve_datetime->setTimezone(new DateTimeZone($show_timezone));

                $tooltip = '<span>Day ' . $day . ' Star '. $star_num;
                $tooltip .= '<br>Obtained ' . $solve_datetime->format('Y-m-d H:i:s T');
                $tooltip .= "<br>Solve time: " . nice_solve_time($puzzle_open, $solve_ts);
                $tooltip .= "<br>" . $points_rank[$s["rank"]]. " points obtained (" .  nice_rank($s["rank"]) . " place)";
                if ($s["rank"] == 1) {
                  $tooltip .= '<br><span class="gold">Gold</span> medal awarded!';
                } else if ($s["rank"] == 2) {
                  $tooltip .= '<br><span class="silver">Silver</span> medal awarded!';
                } else if ($s["rank"] == 3) {
                  $tooltip .= '<br><span class="bronze">Bronze</span> medal awarded!';
                }
                $tooltip .= '</span>';
                $cell = '<span class="star star-gold"><a href="#" class="tooltip">*' . $tooltip . '</a></span>';

                if ($s["rank"] == 1) {
                  $cell .= '<br><a href="#" class="tooltip">' . $medal_gold_img . $tooltip . '</a>';
                } else if ($s["rank"] == 2) {
                  $cell .= '<br><a href="#" class="tooltip">' . $medal_silver_img . $tooltip . '</a>';
                } else if ($s["rank"] == 3) {
                  $cell .= '<br><a href="#" class="tooltip">' . $medal_bronze_img . $tooltip . '</a>';
                }

              }
              echo $cell; ?>
              </td>
              <?php
            }
          }
          ?>

        </tr>
      <?php } ?>

    </table>

    <p>Sort by: <?php
    $score_tooltip = '"For N users, the first user to get each star gets<br>N points, the second gets N-1, and the last gets 1."';
    $bits = array();
    $entries = array(
      "local_score"=>'score<span><a href="#" class="tooltip2">?<span>' . $score_tooltip . '</span></a></span>',
      "stars"=>"stars",
      "medals_tot"=>"total medals",
      "gold"=>"gold medals",
      "silver"=>"silver medals",
      "bronze"=>"bronze medals"
    );
    foreach ($entries as $field => $text) {
      if ($field == $sort_field) {
        array_push($bits, '<strong>' . $text . '</strong>');
      } else {
        $url = '<a href="' . parse_url($_SERVER["REQUEST_URI"],
        PHP_URL_PATH) . '?sort_field=' . $field;
        $url .= '">' . $text . '</a>';
        array_push($bits, $url);
      }
    }
    echo implode(" &ndash; ", $bits);
    ?>
    </p>

    <p>Medals are awarded to the top three fastest solvers for each star.</p>

    <?php
      $last_update_datetime = new DateTime('@' . $data["last_update"]);
      $last_update_datetime->setTimezone(new DateTimeZone($show_timezone));
    ?>

    <p>
      <small>JSON last updated on <?= $last_update_datetime->format('Y-m-d H:i:s T') ?> &mdash; data might be up to 15 minutes old.</small><br>
    </p>

    <p><a href="https://adventofcode.com/<?= $event_year ?>" target="_blank">Advent of Code <?= $event_year ?></a> is a programming challenge created by <a href="http://was.tl/" target="_blank">Eric Wastl</a>.</p>

  <?php }

  /****************************************************************************/
  // OUTPUT ERROR PAGE WITH INSTRUCTIONS

  if (empty($data) || empty($board_id) ||empty($session_id)) { ?>

    <h4>Advent of Code <?= $event_year ?> Private leaderboard viewer</h4>

    This is a simple PHP script that displays an Advent of Code's private leaderboard, including more stats and <strong>medals</strong> for the top three fastest solvers for each of the 50 stars. It was inspired by u/jeroenheijmans's <a href="https://www.reddit.com/r/adventofcode/comments/a4mdtp/chromefirefox_extension_with_charts_for_private/" target="_blank">Chrome/Firefox extension</a>.

    <?php if (empty($data) && (!empty($board_id) || !empty($session_id))) { ?>

      <h4>Oops!</h4>

      <p>There was a problem retrieving the JSON file for private leaderboard: <?php echo $board_id ?>.</p>

      <p>The most likely reason is an incomplete (make sure to get all 64 hex digits!), invalid (you must be a member of the board to view it) or expired (log-in again) session ID.</p>

    <?php } ?>

    <h4>Instructions</h4>

    <p>To make this work you'll need to know the <strong>private leaderboard ID</strong> and your <strong>adventofcode.com session ID</strong>.</p>

    <p>Having those modify this PHP file and edit the values of the <strong>$board_id</strong> and <strong>$session_id</strong> variables at the top of the file, and host the file yourself.</p>

    <p><strong>NOTE: Never share or make your AoC cookie session ID public in any way, as you'll grant strangers access to your AoC account</strong></p>

    <p>Here's how to obtain them:</p>

    <ol>

      <li><p><strong>private leaderboard ID</strong></p>
        <p>Go to the official private leaderboard you want to view and obtain the ID at the end of the URL:</p>
        <img src="guide_leaderboard_id.png" />
        <p>It should be a numeric value around 5 digits long.
      </li>


      <li><p><strong>adventofcode.com session ID</strong></p>

        <p>You'll need to access your adventofcode.com cookie and retrieve your session ID from it. It should be 96 hex digits long, something like this:<br> ff26cf24aa0d4057d7de2454f41c409642b9047b4d0465aeb76ca39783a60b31b0f1a946f24f01e575c05789754df92d</p>

        <p>Navigate to <a href="https://adventofcode.com/<?= $event_year ?>" target="_blank">adventofcode.com</a> and <strong>log in</strong>. Then:</p>

        <p>On <strong>Chrome</strong>:</p>
        <ol>
          <li>Click on the padlock to the left of the URL.</li>
          <li>Click on Cookies. A window will open.</li>
          <li>Expand the entry adventofcode.com to find the session cookie.</li>
          <li>The Content field contains the session ID. Double-click to copy it (make sure to get all 96 hex characters).</li>
        </ol>
        <p><img src="guide_session_Chrome1.png" />&nbsp;<img src="guide_session_Chrome2.png" /></p>

        <p>On <strong>Firefox</strong>:</p>
        <ol>
          <li>Open the Firefox Developer Tools by hitting CTRL+SHIFT+I (or Cmd+Option+I on Mac).</li>
          <li>Open the "Storage" tab.</li>
          <li>Expand "Cookies" and select adventofcode.com</li>
          <li>On the list, click on the "session" entry.</li>
          <li>The "value" column will hold the session ID. Double-click to copy it.</li>
        </ol>
        <p><img src="guide_session_Firefox.png" /></p>
      </li>


  <?php } ?>

  </div>

</body>
</html>
