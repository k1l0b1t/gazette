<?php
require_once('/path/to/mailparser.php');
// Options begin
$listaddress = "list@example.be"; // will be from for frowarded msgs
$subscribersfile = "/path/to/subscribers.txt";
$listadmin = "admin@example.be"; // recieves notificications of subs
$allowsubscribe = true;
$bannedfile = "/path/to/banned.txt";
$infofile = "/path/to/info.txt";
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

// check if the from address is in the banned file
$banfile = file($bannedfile, FILE_IGNORE_NEW_LINES);
if(array_search($from_address, $banfile)){
  mail($listadmin,"Info for ".$listaddress,$from_address." tried to send, but is banned on list ".$listaddress.".","From: ".$listaddress."\r\nX-Mailer: Gazette/0.1\r\nPrecedence: list\r\nList-Subscribe: <mailto:".$listaddress."?subject=subscribe>\r\nList-Unsubscribe: <mailto:".$listaddress."?subject=unsubscribe>");
  die(); // the user is banned
}

// check if the subject line starts with subscribe if so, do the subscribe 
// action (add the email to the subscriber file)
if (substr(strtolower($subject), 0, 9) == "subscribe"){
  if (!$allowsubscribe){
    die(); // if subscribing is disabled, stop here
  }
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
  $infocontent = file_get_contents($infofile);
  mail($from_address,"Re: ".$subject,"You are now subscribed to ".$listaddress.".\n\n".$infocontent,"From: ".$listaddress."\r\nX-Mailer: Gazette/0.1\r\nPrecedence: list\r\nList-Subscribe: <mailto:".$listaddress."?subject=subscribe>\r\nList-Unsubscribe: <mailto:".$listaddress."?subject=unsubscribe>");
  mail($listadmin,"New subscriber on ".$listaddress,$from_address." is now subscribed to the list ".$listaddress.".","From: ".$listaddress."\r\nX-Mailer: Gazette/0.1\r\nPrecedence: list\r\nList-Subscribe: <mailto:".$listaddress."?subject=subscribe>\r\nList-Unsubscribe: <mailto:".$listaddress."?subject=unsubscribe>");
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
  mail($from_address,"Re:". $subject,"You are now unsubscribed from ".$listaddress.".","From: ".$listaddress."\r\nX-Mailer: Gazette/0.1\r\nPrecedence: list\r\nList-Subscribe: <mailto:".$listaddress."?subject=subscribe>\r\nList-Unsubscribe: <mailto:".$listaddress."?subject=unsubscribe>");
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
