<?PHP

function getgetVar($varname, $default='')
{
  if (isset($_GET[$varname])) return $_GET[$varname];
  return $default;
}

//------------------------------------//

include_once('clFile.php');
include_once('cllabview.php');

$filename = 'myFile.vi';


$file = new clFile();
$file->readFromFile($filename); //- open File

$FReader = $file->getFileReader();

$LV = new clLabView($FReader);


if ($LV->readVI()) { //- read .VI File

  $BDPW = $LV->getBDPW();


  $BDPW->setPassword('new Password!'); //- set the new password



  //-- just debugging ----
  $VCTP = $LV->getVCTP();

  $VERS = $LV->getVERS();

  echo '<pre>'. htmlentities($BDPW->getXML()) .'</pre>';
  echo '<hr />';
  echo '<pre>'. htmlentities($VCTP->getXML()) .'</pre>';
  echo '<hr />';
  echo '<pre>'. htmlentities($VERS->getXML()) .'</pre>';
  //-- end debugging ----




  //- save the .VI
  if (!$LV->store('out.vi'))
  {
    echo '<b>Error: </b><pre>'. print_r($LV->getErrorStr(),true) .'</pre>';
  }




//  print_r($LV->getError());

}



?>