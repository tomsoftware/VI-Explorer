<?PHP

function getgetVar($varname, $default='')
{
  if (isset($_GET[$varname])) return $_GET[$varname];
  return $default;
}

//------------------------------------//

include_once('clFile.php');
include_once('cllabview.php');

$filename_in = 'myFile.vi';
$filename_out = 'out.vi';
$newPassword = '123456';


$file = new clFile();
$file->readFromFile($filename_in); //- open File

$FReader = $file->getFileReader();


//- create a Labview class to controle the process
$LV = new clLabView($FReader);


//- read .VI File
if ($LV->readVI())
{

  //- Password
  $BDPW = $LV->getBDPW();
  $BDPW->setPassword($newPassword); //- set the new password


  //- Version + Library Password
  $LVSR = $LV->getLVSR();
  $LVSR->setLibraryPassword($newPassword); //- set the new Library Password

  //- does not work because too many errors when opening VI in Labview... but you can giv it a try
  //$LVSR->setVersion(8,6);



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




  //- save the .VI (this will calculate the password hash)
  if (!$LV->store($filename_out))
  {
    echo '<b>Error: </b><pre>'. $LV->getErrorStr() .'</pre>';
  }



}



?>