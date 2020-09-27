<?php

require_once('/path/to/mailparser.php');

// Options begin

$listaddress = "listname@example.com"; // will be from for frowarded msgs
$subscribersfile = "/path/to/subscribers.txt";

// options end =================================================================

// get data from STDIN
$data = stream_get_contents(STDIN);

// create new mailparser object
$emailParser = new PlancakeEmailParser($data);

// get the subject from the message
$subject = $emailParser->getHeader('Subject');

// get the from: from the message
$from = $emailParser->getHeader('From');
$from_address = substr(explode("<", $from)[1], 0, -1);

// get the message body
$body = $emailParser->getPlainBody();

// check if the subject line starts with subscribe if so, do the subscribe 
// action (add the email to the subscriber file)
if (substr(strtolower($subject), 0, 9) == "subscribe"){

  $subscribers = array();
  $readfile = file($subscribersfile, FILE_IGNORE_NEW_LINES);
  foreach ($readfile as $subaddress)  {
    if ($subaddress == $from_address){
      die("");
    }
  }
  $addfile = fopen($subscribersfile, "a") or die("Unable to open file!");
  fwrite($addfile, $from_address."\n");
  fclose($addfile);
  mail($from_address,"Re: ".$subject,"You are now subscribed to ".$listaddress.".","From: ".$listaddress."\r\nX-Mailer: Gazette/0.1\r\nPrecedence: list\r\nList-Subscribe: <mailto:".$listaddress."?subject=subscribe>\r\nList-Unsubscribe: <mailto:".$listaddress."?subject=unsubscribe>");
  die(); // don't do anything else
}

// After this point, the sender should be subscribed. if he is not subscribed, 
// the scrippt will end
$issub = false ;
$readfile = file($subscribersfile, FILE_IGNORE_NEW_LINES);
foreach ($readfile as $subaddress)  {
  if ($subaddress == $from_address){
    $issub = true;
  }
}
if ($issub == false){
  die(); // stop here, as the sender is not a subscriber
}


// check if the subject line starts with unsubscribe, if so, do the unsubscribe
// action (remove the email from the subscriber file)
if (substr(strtolower($subject), 0, 11) == "unsubscribe"){
  // we allready know that the sender is subscribed, so we don't have to check
  // that again here
  $newfile = fopen($subscribersfile, "w") or die("Unable to open file!");
  foreach ($readfile as $subaddress)  {
    
    if ($subaddress != $from_address){
      
      fwrite($newfile, $subaddress."\n");
      
    }
    
  }
  fclose($newfile);
  mail($subaddress,"Re:". $subject,"You are now unsubscribed from ".$listaddress.".","From: ".$listaddress."\r\nX-Mailer: Gazette/0.1\r\nPrecedence: list\r\nList-Subscribe: <mailto:".$listaddress."?subject=subscribe>\r\nList-Unsubscribe: <mailto:".$listaddress."?subject=unsubscribe>");
  die(); // stop here
}

// if the subject is nothing special, treat is as a mail that should be
// forwarded to the subscribers. 

foreach ($readfile as $subaddress)  {
  mail($subaddress,$subject,"Sender: ".$from."\n\n".$body,"From: ".$listaddress."\r\nX-Mailer: Gazette/0.1\r\nPrecedence: list\r\nList-Subscribe: <mailto:".$listaddress."?subject=subscribe>\r\nList-Unsubscribe: <mailto:".$listaddress."?subject=unsubscribe>");
}

// exit
exit;
?>
