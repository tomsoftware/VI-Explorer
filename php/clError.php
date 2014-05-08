<?PHP

class clError
{
  private $m_error;
  private $m_srcClass;


  // -------------------------------------- //
  function __construct($srcClass)
  {
    $this->m_error = array();
    $this->m_srcClass = $srcClass;

    $this->reset();
  }


  // -------------------------------------- //
  Public Function getErrorString($showWarnings=True)
  {
  
    $out = '';

    foreach($this->m_error as $err)
    {
      If (($showWarnings) || ($err['isWarning'] == False))
      {
        $out .= $this->formErrorString($err) ."\n";
      }
    }

    return $out;
  }


  // -------------------------------------- //
  Public Function CopyErrorsTo($destinationErrorClass, $copyWarnings=false)
  {

    foreach($this->m_error as $err)
    {
      $srcClass = $err['srcClass'];
      If ($srcClass == '') $srcClass = $this->m_srcClass;

      if (!$err['isWarning'] || $copyWarnings) $destinationErrorClass->AddError($err['errStr'], $err['isWarning'], $srcClass);
    }
  }


  // -------------------------------------- //
  public function getXML()
  {
    $out = "  <ERRORS>\n";

    foreach($this->m_error as $err)
    {
      $srcClass = $err['srcClass'];
      If ($srcClass == '') $srcClass = $this->m_srcClass;

      $out .= '    <Message class="'. $srcClass .'" type="'. ($err['isWarning']?'warning':'error') .'">'. htmlentities($err['errStr']) ."</Message>\n";
    }

    $out .= "  </ERRORS>\n";

    return $out;
  }


  // -------------------------------------- //
  Private Function formErrorString($ErrorInfo)
  {

    $srcClass = $ErrorInfo['srcClass'];
    If ($srcClass == '') $srcClass = $this->m_srcClass;
    
    return $srcClass .' : '. $ErrorInfo['errStr'];
  }


  // -------------------------------------- //
  Public Function AddError($ErrorStr /* String */, $isWarning = False , $srcClass= '', $doPrint=true)
  {
    $ErrorInfo=array();

    $ErrorInfo['errStr'] = $ErrorStr;
    $ErrorInfo['srcClass'] = $srcClass;
    $ErrorInfo['isWarning'] = $isWarning;

    $this->m_error[] = $ErrorInfo;

    //- Output
    if ($doPrint)
    {
      if ($isWarning)
      {
	echo 'Warning: '. htmlentities($this->formErrorString($ErrorInfo)) .'<br />';
      }
      else
      {
	echo  'Error: '. htmlentities($this->formErrorString($ErrorInfo)) .'<br />';
      }
    }
  }


  // -------------------------------------- //
  Public Function shiftError()
  {

    return formErrorString(array_shift($thi->m_error));
 
  }

  // -------------------------------------- //
  Public Function getErrorCount()
  {
    return count($thi->m_error);
  }


  // -------------------------------------- //
  Public Function reset()
  {
    $this->m_error = array();
  }


}

?>