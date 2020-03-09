#!/usr/bin/php -q
<?PHP

$table    = "your_table_name";
$username = "your_db_username";
$password = "your_db_password";
$database = "your_db_name";
$email    = "your_email_address";

ini_set("memory_limit","700M");
error_reporting(E_ALL);
ini_set('display_errors', '1');
$expected_runtime = 355;

$executionStartTime = microtime(true);

$output = "";

if ( !isset($argv[1]) ) {
 echo "Usage: $argv[0] <debug>\n";
 exit;
}
$debug           = (isset($argv[1])) ? $argv[1] : 0 ;

$program = "$argv[0] $argv[1]";

$body = "";
$body = "<html><body>An update has been made to System Admin Accounts.<br/>\n<br/>\n<font color=red><strong>%%body_details%%</strong></font><br/>\n<br/>\nPlease check for hacking activity and reply-to-all if this is expected<br/><br/>$program</body></html>\n";
$body_details = "";

$email_warnings = 0;

if ( $debug >= 1 ) {
 $checkFile = "/tmp/access.test.json";
} else {
 $checkFile = "/tmp/access.json";
}

if (file_exists($checkFile)) {
 $firstTime = 0;
 $old_file_contents = file_get_contents($checkFile);
 $old_data_arr = json_decode($old_file_contents, true);
} else {
 $firstTime = 1;
 $old_file_contents = "";
 $old_data_arr = Array();
}

$localDb['server'] = 'localhost';
$localDb['user'] = $username;
$localDb['password'] = $password;
$localDb['db'] = $database;

$sql = "SELECT login, email, status, password_md5 FROM $table ORDER BY login";

# Get the data
$data = get_data($localDb, $sql);

foreach ( $data as $key => $login ) {
 $current_logins_arr[$login['login']] = $login;
}

$logins_json = json_encode($current_logins_arr,JSON_PRETTY_PRINT);

if ( $logins_json != $old_file_contents ) {
 if ( $debug >= 1 ) echo "Files don't match\n";

 foreach ( $current_logins_arr as $login => $record ) {

  if ( isset($old_data_arr[$login]) ) {
   # The login exists, check if it's values have changes
   if ( $record['email'] != $old_data_arr[$login]['email'] ) {
    $body_details .= "Email address changed for an Admin User with login = '$login'.  From " . $old_data_arr[$login]['email'] . " to $record[email]<br/>\n";
    if ( $debug >= 2 ) echo "$body_details";
  }
   if ( $record['password_md5'] != $old_data_arr[$login]['password_md5'] ) {
    $body_details .= "The password for an Admin User with login = '$login' has changed.<br/>\n";
    if ( $debug >= 2 ) echo "$body_details";
   }
   if ( $record['status'] != $old_data_arr[$login]['status'] && $record['status'] != 'inactive' ) {
    $body_details .= "The status for an Admin User with login = '$login' has changed from " . $old_data_arr[$login]['status'] . " to $record[status]<br/>\n";
    if ( $debug >= 2 ) echo "$body_details";
   }
  } else {
   # The login didn't previously exist, send an alert
   $body_details .= "An Admin User was added with login = '$login'. Is it real?<br/>\n";
   if ( $debug >= 2 ) echo "$body_details";
  }

 } # foreach $current_logins_arr

} # file match



if ( $body_details ) {
 $body = str_replace('%%body_details%%', $body_details, $body);
 if ( $debug >= 1 ) echo "$body\n";
 $to = $email
 $from = $email;
 $subject = "Alert: System Admin Accounts Updated";

 if ( !$firstTime ) send_csv_mail ($to, $body, $subject,$from);

}

$myfile = fopen($checkFile, "w") or die("Unable to open file!");
fwrite($myfile, $logins_json);
fclose($myfile);
chmod($checkFile, 0600);

function array_diff_assoc_recursive($array1, $array2) {
    $difference=array();
    foreach($array1 as $key => $value) {
        if( is_array($value) ) {
            if( !isset($array2[$key]) || !is_array($array2[$key]) ) {
                $difference[$key] = $value;
            } else {
                $new_diff = array_diff_assoc_recursive($value, $array2[$key]);
                if( !empty($new_diff) )
                    $difference[$key] = $new_diff;
            }
        } else if( !array_key_exists($key,$array2) || $array2[$key] !== $value ) {
            $difference[$key] = $value;
        }
    }
    return $difference;
}

function send_csv_mail ($to='test@test.com', $body='Uknown Body', $subject='Unknown Subject', $from='test@test.com', $Cc=0, $Bcc=0, $filename=0, $csvData=0) {

/***
 send_csv_mail($to, $body, $subject, $from, $Cc, $Bcc, $filename, $csvData);
***/

 $headers[] = "From: $from";
 $headers[] = "Reply-To: $from";
 $headers[] = 'X-Mailer: PHP/' . phpversion();

 if ( $csvData ) {
  // This will provide plenty adequate entropy
  $multipartSep = '-----'.md5(time()).'-----';
  $headers[] = "Content-Type: multipart/mixed; boundary=\"$multipartSep\"";
  // Make the attachment
  $attachment = chunk_split(base64_encode($csvData));
 } else {
  $headers[] = 'MIME-Version: 1.0';
  $headers[] = 'Content-type: text/html; charset=iso-8859-1';
 }

 if ( $Bcc ) {
  array_push($headers, "Bcc: $Bcc\r\n");
 }

 if ( $Cc ) {
  array_push($headers, "Cc: $Cc\r\n");
 }

 if ( $csvData ) {
 // Make the body of the message
 $body = "--$multipartSep\r\n"
       . "Content-Type: text/html; charset=ISO-8859-1; format=flowed\r\n"
       . "Content-Transfer-Encoding: 7bit\r\n"
       . "\r\n"
       . "$body\r\n"
       . "--$multipartSep\r\n"
       . "Content-Type: text/txt\r\n"
       . "Content-Transfer-Encoding: base64\r\n"
       . "Content-Disposition: attachment; filename=\"$filename\"\r\n"
       . "\r\n"
       . "$attachment\r\n"
       . "--$multipartSep--";
 } else {
  $body = $body;
 }

 // Send the email, return the result
 return @mail($to, $subject, $body, implode("\r\n", $headers));

}

function get_data($db_data, $sql) {

 $returner = "";

 $link = mysqli_connect($db_data['server'], $db_data['user'], $db_data['password'], $db_data['db']);

 /* check connection */
 if (!$link) {
     printf("Connect failed: %s\n", mysqli_connect_error());
     exit();
 }

 $result = mysqli_query($link, $sql) or die(mysqli_error($link)." Q=".$sql);

 if ( $result ) {

  /* fetch associative array */
  while ($row = mysqli_fetch_assoc($result)) {
   $returner[] = $row;
  }

  /* free result set */
  mysqli_free_result($result);
 }


 /* close connection */
 mysqli_close($link);

 return $returner;

}

?>
