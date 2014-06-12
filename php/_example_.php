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

  //- Password
  $BDPW = $LV->getBDPW();
  $BDPW->setPassword('new Password!'); //- set the new password

  //- Version
  $LVSR = $LV->getLVSR();
  //$LVSR->setVersion(8,6); //- does not work because too many errors when opening VI in Labview



  //-- just debugging ----
  $VCTP = $LV->getVCTP();

  $VERS = $LV->getVERS();

  echo '<pre>'. htmlentities($LV->getXML()) .'</pre>';
  echo '<hr />';
  echo '<pre>'. htmlentities($BDPW->getXML()) .'</pre>';
  echo '<hr />';
  echo '<pre>'. htmlentities($VCTP->getXML()) .'</pre>';
  echo '<hr />';
  echo '<pre>'. htmlentities($VERS->getXML()) .'</pre>';
  echo '<hr />';
  echo '<pre>'. htmlentities($LVSR->getXML()) .'</pre>';
  //-- end debugging ----




  //- save the .VI
  if (!$LV->store('out.vi'))
  {
    echo '<b>Error: </b><pre>'. $LV->getErrorStr() .'</pre>';
  }



}



?>