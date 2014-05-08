<?PHP

include_once('clError.php');


class clBDPW {
	
  private $m_lv;
  private $m_FileHasPassword; //- Boolean
  private $m_error; //- clError

  private $m_VCTP;
  private $m_VERS;

  private $m_reader; //- clFileReader

  private $m_set_md5_psw; //- String

  //- Information about the file password
  private $m_file_psw=array();

  //- Information about the new password
  private $m_password_set=array();



  // -------------------------------------- //
  function __construct($lv)
  {
    $this->m_lv = $lv;
    $this->m_FileHasPassword = false;
    $this->m_error = new clError('clBDPW');


    If (!$lv->BlockNameExists('BDPW'))
    {
      $this->m_error->AddError('File has no password information!');
      return;
    }
  
    $this->m_FileHasPassword = True;
    $reader = $lv->getBlockContent('BDPW', 0, False);
    $this->m_reader = $reader;

    //- requested for Hash-Salt
    $this->m_VCTP = $lv->getVCTP();
    $this->m_VCTP->getError()->CopyErrorsTo($this->m_error);

    $this->m_VERS = $lv->getVERS();
    $this->m_VERS->getError()->CopyErrorsTo($this->m_error);
  

    $this->m_file_psw = $this->readBDPW();


    //- calc current Salt (sometimes we are not able to read the salt from file: in this case the salt is "brute-force")
    $hash = $this->getHash($this->m_file_psw['password_md5'], True);
    $this->m_file_psw['salt'] = $hash->salt;

    if (!$hash->isOK) $this->m_error->AddError('Unable to detect the salt!');

  }

  // -------------------------------------- //
  Public Function getError()
  {
    return $this->m_error;
  }


  // -------------------------------------- //
  Private Function getHash($md5password, $checkSalt = False)
  {
    $out = new stdClass();
    $out->salt ='';
    $out->hash1 ='';
    $out->hash2 ='';
    $out->isOK =false;

    $data ='';

    $lv = $this->m_lv;

    $BDH_name ='BDHc';
    if ($this->m_lv->BlockNameExists('BDHb')) $BDH_name ='BDHb';


    $LVSR_content = $lv->getBlockContent('LVSR', 0, false);
    $LIBN_content = $lv->getBlockContent('LIBN', 0, false); 
    $BDH__content = $lv->getBlockContent($BDH_name);


    $LIBN_count = $LIBN_content->readInt(4);
    $LIBN_len = $LIBN_content->readInt(1);



    If ($lv->BlockNameExists('LIBN'))
    {
      $LIBN_content = $lv->getBlockContent('LIBN', 0, False);
    
      $LIBN_count = $LIBN_content->readInt(4);
      $LIBN_len = $LIBN_content->readInt(1);
    
      $data .= $LIBN_content->readStr($LIBN_len);
    
      For ($i = 1; $i<$LIBN_count; $i++)
      {
	$LIBN_len2 = $LIBN_content->readInt(1);
	$data .= ':'. $LIBN_content->readStr($LIBN_len2);
      }
    }



    $data .= $LVSR_content->readStr();

    //- I'm not sure how to figure out if there are Terminals (=> generateSaltString) and waht is right... so we have to test :-(
    if ($checkSalt)
    {
      $salt = '';

      if ($this->m_VERS->getMaior() >= 12)
      {
	$findOK = false;
	$interfaceCount = $this->m_VCTP->getObjectIndexForInterfaceCount();

	for($i=0; $i<$interfaceCount; $i++)
	{
	  //- generate Salt
	  $interface = $this->m_VCTP->getObjectIndexForInterface($i);

	  $count = $this->countTerminals($interface);

	  $salt = $this->getSaltString($count->numCount, $count->strCount, $count->pathCount);

	  //- test Salt
	  if (md5($md5password . $data . $salt, true) == $this->m_file_psw['hash_1'])
	  {
	    $findOK=true;
	    break;
	  }
	}

	//- OK test if it is just {0 0 0}
	if (!$findOK)
	{
	  $salt = $this->getSaltString(0, 0, 0);

	  if (md5($md5password . $data . $salt, true) != $this->m_file_psw['hash_1']) return $out; //- Fail!
	}
      }
      else
      {
	//- empty $salt
        if (md5($md5password . $data . $salt, true) != $this->m_file_psw['hash_1']) return $out; //- Fail!
      }

      $out->salt = $salt;
    }
    else
    {
      $out->salt=$this->m_file_psw['salt'];
    }



    $out->hash1 = md5($md5password . $data . $out->salt, true);



    $BDH__len = $BDH__content->readInt(4);
    $BDH__hash = md5($BDH__content->readStr($BDH__len), true);

    //-- Hash2:  Hash1 + BDHc
    $out->hash2 = md5($out->hash1 . $BDH__hash, true);

    $out->isOK = true;

    return $out;
  }



  // -------------------------------------- //
  private function countTerminals($ObjectIndex)
  {

    $VCTP = & $this->m_VCTP;

    $out = new stdClass();

    $out->numCount=0;
    $out->strCount=0;
    $out->pathCount=0;

    for ($i=0; $i< $VCTP->getClientCount($ObjectIndex); $i++)
    {
      $cIndex = $VCTP->getClient($ObjectIndex, $i);

      If ($VCTP->isNumber($cIndex)) $out->numCount++;
      If ($VCTP->isPath($cIndex))   $out->pathCount++;
      If ($VCTP->isString($cIndex)) $out->strCount++;
      
      If ($VCTP->getClientCount($cIndex) > 0)
      {
        $res = $this->countTerminals($cIndex);

	$out->numCount+=$res->numCount;
	$out->strCount+=$res->strCount;
	$out->pathCount+=$res->pathCount;
      }

    }

    return $out;

  }





  // -------------------------------------- //
  private function getSaltString($v1=0, $v2=0, $v3=0)
  {
    return pack('V', $v1) . pack('V', $v2) . pack('V', $v3);
  }


  // -------------------------------------- //
  public function calcPassword($newPassword)
  {
    $md5_password = md5($newPassword, true);

    $hash = $this->getHash($md5_password);

    if ($hash->isOK)
    {

      $out = array();

      $out['password']=$newPassword;
      $out['password_md5']=$md5_password;
      $out['hash_1']=$hash->hash1;
      $out['hash_2']=$hash->hash2;
      $out['salt']=$hash->salt;

      $this->m_password_set = $out;

      return true;
    }

    $this->m_error->AddError('Error crating new password hashs!');

    return false;

  }



  // -------------------------------------- //
  public function writePassword() {

    $set_psw = $this->m_password_set;

    if (count($set_psw)>0)
    {
      $BDPW_content = $this->m_lv->getBlockContent('BDPW', false);

      $BDPW_content->writeStr($set_psw['password_md5'], 0);
      $BDPW_content->writeStr($set_psw['hash_1']);
      $BDPW_content->writeStr($set_psw['hash_2']);
    }
    
  }


  // -------------------------------------- //
  private function readBDPW()
  {
    $block = $this->m_lv->getBlockContent('BDPW', 0, false);
     

    $out = array();

    $out['password_md5'] = $block->readStr(16);
    $out['hash_1'] = $block->readStr(16);
    $out['hash_2'] = $block->readStr(16);

    return $out;

  }



  // -------------------------------------- //
  public function getPasswordHash($seperator='')
  {
    if (count($this->m_password)>0) return $this->toHex($this->m_password['password_md5'],$seperator);
    return '';
  }


  // -------------------------------------- //
  public function getXML()
  {
    $out = "<?xml version='1.0'?>\n";
    $out .=  "<!-- Filename='". htmlentities($this->m_lv->getFileName()) ."' -->\n\n";
    $out .=  "<BDPW>\n";
    
    $out .=  "  <hash type='password' value='". bin2hex($this->m_file_psw['password_md5']) ."' /> \n";
    $out .=  "  <hash type='hash1' value='".  bin2hex($this->m_file_psw['hash_1']) ."' /> \n";
    $out .=  "  <hash type='hash2' value='".  bin2hex($this->m_file_psw['hash_2']) ."' /> \n";
  
    If ($this->m_file_psw['salt'] != '')
    {
      $out .=  "  <salt value='". bin2hex($this->m_file_psw['salt']) ."' /> \n";
    }

    $out .=  $this->m_error->getXML();


    $out .=  "</BDPW>\n";

    return $out;
  }
}

?>