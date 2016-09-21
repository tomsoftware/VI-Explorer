<?PHP
include_once('clVers.php');

class clLVSR {

  //- Information about the LVSR container
  private $m_LVSR=array();

  private $m_lv;
  private $m_error; //- clError


  // -------------------------------------- //
  public function getError()
  {
    return $this->m_error;
  }


  // -------------------------------------- //
  function __construct($lv)
  {

    $this->m_lv = $lv;
    $this->m_error = new clError('clLVSR');


    if (!$lv->BlockNameExists('LVSR'))  return $this->m_error->AddError('Block "LVSR" not found?');


    $reader = $lv->getBlockContent('LVSR', false);


    $out = array();

    $out['version'] = clVers::getVersionFromCode($reader->readInt(4));

    $out['INT1'] = $reader->readInt(2); //- ??
    $out['flags'] = $reader->readInt(2);

  
  
    $out['protected'] = (($out['flags'] & 0x2000) > 0);

    //- delete known flag-bits
    $out['flags'] = $out['flags'] & 0xDFFF;


    //- Library Password
    $out['libpassword'] = $reader->readStr(16, 96);


    $this->m_LVSR = $out;
  }


  // -------------------------------------- //
  public function getLibraryPasswordHash($seperator='')
  {
    if (!isset($this->m_LVSR['libpassword'])) return '';

    return $this->m_lv->toHex($this->m_LVSR['libpassword'], $seperator);
  }


  //--------------------------------------//
  public function setLibraryPassword($newPassword)
  {
    if (!isset($this->m_LVSR['libpassword'])) return;

    //- change Library Password hash if set
    if (strlen($this->m_LVSR['libpassword'])==16)
    {
      $LVSR_content = $this->m_lv->getBlockContent('LVSR', false);
      $LVSR_content->writeStr(md5($newPassword, true), 96);
    }

  }



  //--------------------------------------//
  //- warning: this does not work properly! -//
  public function setVersion($maior, $minor)
  {
    //- change Version in file
    $LVSR_content = $this->m_lv->getBlockContent('LVSR', false);
    $LVSR_content->writeStr(chr($maior) . chr($minor), 0);

    //---
    //- also change value in vers-block
    $vers_content = $this->m_lv->getBlockContent('vers', false);
    $vers_content->writeStr(chr($maior) . chr($minor), 0);
  }

  // -------------------------------------- //
  public function getXML()
  {
    //$out  = "<'.'?xml version='1.0'?'.'>\n";
    //$out .= "<!-- Filename='" . htmlentities($this->m_lv->getFileName()) . "' -->\n\n";
    $out = "<LVSR> \n";
    
    if (isset($this->m_LVSR['version']))
    {
      $version = $this->m_LVSR['version'];

      $out .= "  <version value='" . $version['maior'] .".". $version['minor'] ."' >\n";
      $out .= "    <bugfix value='" . $version['bugfix'] ."' />\n";
      $out .= "    <stage value='" . $version['stage'] ."' />\n";
      $out .= "    <stageText value='" . $version['stage_text'] ."' />\n";
      $out .= "    <build value='" . $version['build'] ."' />\n";
      $out .= "    <flags value='" . $version['flags'] ."' />\n";
      $out .= "  </version>\n";

      $out .= "  <library>\n";
      $out .= "    <password value='" . bin2hex($this->m_LVSR['libpassword']) ."' />\n";
      $out .= "  </library>\n";

      $out .= "  <protected value='" . (($this->m_LVSR['protected']>0)?'yes':'no') ."' />\n";
      $out .= "  <flags value='" . $this->m_LVSR['flags'] ."' />\n";
    }

    $out .= $this->m_error->getXML();

    $out .= "</LVSR>\n";

    return $out;
  }

}

?>