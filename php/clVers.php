<?PHP

class clVers {
  private $m_lv;
  private $m_error; //- clError

  private $m_version;
  private $m_vers_text;
  private $m_vers_info;


  // -------------------------------------- //
  public function getError()
  {
    return $this->m_error;
  }



  // -------------------------------------- //
  function __construct($lv)
  {
    $this->m_lv = $lv;
    $this->m_error = new clError('clVers');



    if (!$lv->BlockNameExists('vers')) return $this->m_error->AddError('Block "vers" not found?');


    $reader = $lv->getBlockContent('vers', false);

    $this->m_version = self::getVersionFromCode($reader->readInt(4));

    
    //- don't know flag??
    $v = $reader->readInt(2);
  
    if ($v != 0)
    {
      $this->m_error->AddError('Version - wrong data format? (value: '. $v .' should be 0)!'); //- maybe the compiled OS-Version?
      return false;
    }
  
    //- read version text
    $length = $reader->readInt(1);
    $this->m_vers_text = $reader->readStr($length);
  
  
    $length = $reader->readInt(1);
    $this->m_vers_info = $reader->readStr($length);
  }



  /** --------------------------------------
   * @abstract returns a Verions-Info-Array of a (4-Byte) Version-Code-Value
   * @return array Array with versions elements {maior, minor, bugfix, stage, flags, build, stage_text}
   */
  public static function getVersionFromCode($versonsCode) {

    static $s_LABVIEW_VERSION_STAGE = array(0=>'unknown', 1=>'development', 2=>'alpha', 3=>'beta', 4=>'release');
 

    $ret = array();

    //- Thanks to guestbook-"user" / VI.lib->utility->libraryn.llb->I32 To App Version.vi
    $ret['maior'] =(($versonsCode >> 28) & 0x0F) * 10 + (($versonsCode >> 24) & 0x0F);
    $ret['minor'] =(($versonsCode >> 20) & 0x0F);
    $ret['bugfix']=(($versonsCode >> 16) & 0x0F);
    $ret['stage'] =(($versonsCode >> 13) & 0x07);
    $ret['flags'] =(($versonsCode >>  8) & 0x1F); //- 5 Bit - dont know
    $ret['build'] =(($versonsCode >>  4) & 0x0F) * 10 + (($versonsCode >>  0) & 0x0F);


    $ret['stage_text'] = $s_LABVIEW_VERSION_STAGE[0];
    if (isset($s_LABVIEW_VERSION_STAGE[$ret['stage']]))  $ret['stage_text'] = $s_LABVIEW_VERSION_STAGE[$ret['stage']];


    return $ret;
  }


  // -------------------------------------- //
  public function getMaior()
  {
    return $this->m_version['maior'];
  }

  // -------------------------------------- //
  public function getMinor()
  {
    return $this->m_version['minor'];
  }



  // -------------------------------------- //
  public function getXML()
  {
    //$out  = "<'.'?xml version='1.0'?'.'>\n";
    //$out .= "<!-- Filename='" . htmlentities($this->m_lv->getFileName()) . "' -->\n\n";
    $out = "<VERS> \n";
    
    
    $out .= "  <version value='" . $this->m_version['maior'] .".". $this->m_version['minor'] ."' />\n";
    $out .= "  <bugfix value='" . $this->m_version['bugfix'] ."' />\n";
    $out .= "  <stage value='" . $this->m_version['stage'] ."' />\n";
    $out .= "  <stageText value='" . $this->m_version['stage_text'] ."' />\n";
    $out .= "  <build value='" . $this->m_version['build'] ."' />\n";
    $out .= "  <flags value='" . $this->m_version['flags'] ."' />\n";
  
    $out .= "  <VersionsText value='" . $this->m_vers_text ."' />\n";
    $out .= "  <VersionsInfo value='" . $this->m_vers_info ."' />\n";
  
    $out .= $this->m_error->getXML();

    $out .= "</VERS>\n";

    return $out;
  }


}

?>