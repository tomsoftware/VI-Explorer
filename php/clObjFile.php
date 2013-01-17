<?PHP


class clObjFile {

  private $error;

  const FILEVERSION_B=2;
  const FILEVERSION_C=3;
  private $m_version;
  private $m_container_start;
  private $m_data_len;
  private $m_data_start;
  private $m_tree;
  private $m_objectNames;
  private $m_propNames;

  // -------------------------------------- //
  public function getError() {
    return $this->error;
  }


  // -------------------------------------- //
  private function setError($errStr) {
    $this->error=$errStr;

    echo '<hr />'. $errStr .'<hr />';
    return false;
  }


  // -------------------------------------- //
  private function initNames() {

    $this->m_objectNames=array();
    $this->m_propNames=array();

    $this->m_objectNames[0x51] = 'TextBoxString';
    $this->m_objectNames[0x50] = 'TextBoxNumber';
    $this->m_objectNames[0x5B] = 'TextBoxPath';
    $this->m_objectNames[0x01] = 'Text';
    $this->m_objectNames[0x12] = 'Control';

    $this->m_propNames[0x21]='TextAlignment';
    $this->m_propNames[0xD0]='WindowPosition';
    $this->m_propNames[0x28]='BackgroundColor';
    $this->m_propNames[0x24]='BorderColor';
    $this->m_propNames[0x29]='TextColor';
    $this->m_propNames[0x2D]='BoxPosAndSize';
    $this->m_propNames[0x44]='ConnectorIndex';
    $this->m_propNames[0xCB]='BorderStyle';
    $this->m_propNames[0x22]='Text';

  }

  // -------------------------------------- //
  function __construct($reader, $version=SELF::FILEVERSION_B) {

    $this->error=array();
    $this->m_version = $version;

    $this->m_tree=array();
    $TreePos = -1;
    $pos=-1;

    $this->initNames();


    //-- Read "Struction-Header" --
    if ($this->m_version == self::FILEVERSION_C) 
    {
      $pos = $reader->readInt(4, 0);
      $this->m_container_start = $reader->getOffset();
  
      $this->m_data_len = $reader->readInt(4, $pos);
  
      $this->m_data_start = $pos - $this->m_data_len;
      $this->m_container_len = $pos - $this->m_data_len - $this->m_container_start;
    
    }
    else if ($this->m_version = self::FILEVERSION_B)
    {
      $this->m_container_len = $reader->readInt(4, 0);
      $this->m_container_start = $reader->getOffset();
    
      $this->m_data_start = $this->m_container_len + $this->m_container_start;
      $this->m_data_len = 0;
    }



    $cRead = $reader->getNewFileReader(0, $this->m_container_start, $this->m_container_len);
    $dRead = $reader->getNewFileReader(0, $this->m_data_start, $this->m_data_len);


    //- read objects


    while(!$cRead->eof())
    {
      $pos=$cRead->getOffset();

      $cmd1 = $cRead->readInt(1);
      $cmd2 = $cRead->readInt(1);
      $doneSomething=false;


      if ($cmd1 & 16)
      {
	//--------------------------------------------------------------------
	//- Object start - [ <object> ] -//
	//--------------------------------------------------------------------

	$autoClose=false;
	$doneSomething=true;

	$id = ($cmd1 & 3) * 512 + $cmd2;

	$ob = array();
	$ob['child'] =array();
	$ob['parent'] = $TreePos;
	$ob['id'] = $id;
	$ob['pos'] = $pos;
	$ob['prop'] = '';

	$oldTreePos = $TreePos;
	$TreePos = count($this->m_tree);

        $this->m_tree[$TreePos]=$ob;

	if ($oldTreePos>=0) $this->m_tree[$oldTreePos]['child'][]= $TreePos;


	//--------------------------------------------------------------------
	//- First Object Attribut - [ attribut="value" ] -//
	//--------------------------------------------------------------------

        if (($cmd1 & 4) > 0) {
	  if (($cmd1 & 232) > 0) $this->setError('Error - Object start '. $pos .' @ '.' - unknown flags?!');
	  $ArgLen = 2;
	  $cmd2=254;
	  $autoClose=true; 
	}
	else {

	  $cmd1 = $cRead->readInt(1);
	  $cmd2 = $cRead->readInt(1);
	  $ArgLen = ($cmd1 & 3) + 1;
	}

        $ArgStr = $cRead->readStr($ArgLen);


	$DataLen = ord(substr($ArgStr, -1,1));

	if (($DataLen & 0xF0) == 0xF0) {
	  $ArgStr = substr($ArgStr, 0, -1);
        
	  if ($DataLen == 0xFD)
	    $DataLen = 2;
	  else if ($DataLen == 0xF8)
	    $DataLen = 0;
	  else if ($DataLen == 0xF4)
	    $DataLen = 8;
	  else if ($DataLen == 0xFA)
	    $DataLen = 8; //- ??
	  else
	    $this->setError('Unknown Object-Data-Length: 0x'. dechex($DataLen & 0x0F) .' @ pos: '. $pos);
	  
	} else {
	  $DataLen = 0;
	}


        $this->m_tree[$TreePos]['_type'] = $cmd2;

	switch ($cmd2)
	{
	  case 251:
	    $this->m_tree[$TreePos]['count'] = hexdec(bin2hex($ArgStr));
	    break;

	  default:
            $this->m_tree[$TreePos]['objtype'] = hexdec(bin2hex($ArgStr));
	}


	//--------------------------------------------------------------------
	//- Second Object Attribut - [ attribut="value" ] -//
	//--------------------------------------------------------------------

	if ($DataLen > 0) 
	{
	  if ($this->m_version == self::FILEVERSION_B) {

	    //- VERSION_B -//
	    $ArgLen = 2;
	    $ArgStr = $cRead->readStr($ArgLen);
          
	    $this->m_tree[$TreePos]['prop'] = $ArgStr;
          } else {

	    //- VERSION_C -//
	    $ArgStr = $dRead->readStr($DataLen);
          
	    $this->m_tree[$TreePos]['prop'] = $ArgStr;
          
          }
        }



	//--------------------------------------------------------------------
	//- Auto Close Object [ </object> ]
	//--------------------------------------------------------------------
	if ($autoClose) {
	  $TreePos = $this->m_tree[$TreePos]['parent'];
	}


      } else if ($cmd1 & 8) {
	

	//--------------------------------------------------------------------
	//- Close Object [ </object> ]
	//--------------------------------------------------------------------

	$id = ($cmd1 & 3) * 512 + $cmd2;
	if ($id!=$this->m_tree[$TreePos]['id']) $this->setError('Error closing Object (opened:'. $this->m_tree[$TreePos]['id'] .' != closing:'. $id .') @ pos: '. $pos);
	$TreePos = $this->m_tree[$TreePos]['parent'];


      } else if ($cmd1 & 4) {


	//--------------------------------------------------------------------
	//- property-Node [ <prop name="123" value="345" /> ]
	//--------------------------------------------------------------------

	$id = ($cmd1 & 3) * 512 + $cmd2;
	$ArgLen = $cmd1 >> 5;

	$ArgStr = '';

	if ($ArgLen > 6)
	{
	  $this->setError('Error - Property lenght - >6  @ '. $pos);
	}
	else if ($ArgLen == 6)
	{
	  $ArgLen = $cRead->readInt(1);
	  if ($ArgLen == 255) $ArgLen = $cRead->readInt(2);
	}
	else if ($ArgLen == 0)
	{

	  if ($id == 0x2D || $id == 0xD6 || $id == 0x4C || $id == 0x5F || $id == 0x20D || $id == 0x229 || $id == 0x4A || $id == 0x8B || $id == 0x87 || $id == 0x7A || $id == 0x1F || $id == 0x23 || $id == 0x76 )
	  {

            $ArgStr = $dRead->readStr(8);
	  }
        
	}
      

	if ($ArgLen > 0) $ArgStr = $cRead->readStr($ArgLen);
      

	$ob = array();
	$ob['_type'] = 1;
	$ob['parent'] = $TreePos;
	$ob['id'] = $id;
	$ob['pos'] = $pos;
	$ob['value'] = $ArgStr;


	$newTreePos=count($this->m_tree);

        $this->m_tree[$newTreePos]=$ob;
	if ($oldTreePos>=0) $this->m_tree[$TreePos]['child'][] = $newTreePos;
      }

    }

    if ($TreePos!=-1) $this->setError('unexpected object stream end @ pos: '. $pos );

  }


  // -------------------------------------- //
  public function getObjectNameById($id){

    if(isset($this->m_objectNames[$id])) {
      return $id .':'. $this->m_objectNames[$id];
    }
    else
    {
      return $id;
    }
  }


  // -------------------------------------- //
  public function getObjectIdByName($name){

    if (isset($this->m_objectNames[$name])) return $name;

    $id = array_search($name, $this->m_objectNames, false);

    if($id!==false) {
      return $id;
    }
    else
    {
      return -1;
    }
  }


  // -------------------------------------- //
  public function getPropertyNameById($id){

    if(isset($this->m_propNames[$id])) {
      return $id .':'. $this->m_propNames[$id];
    }
    else
    {
      return $id;
    }
  }

  // -------------------------------------- //
  public function getPropertyIdByName($name){

    if (isset($this->m_propNames[$name])) return $name;

    $id = array_search($name, $this->m_propNames, false);

    if($id!==false) {
      return $id;
    }
    else
    {
      return -1;
    }
  }



  // -------------------------------------- //
  public function getObjectsByType($nameOrId){

    $out = array();

    $id = $this->getObjectIdByName($nameOrId);


    foreach($this->m_tree as $pos=>$ob) {
      if (($ob['_type']==254) && ($ob['objtype']==$id)) $out[] = $pos;
    }

    return $out;
  }



  // -------------------------------------- //
  public function GetPropertyStr($treeElement, $nameOrId, $default = '') {

    if (isset($this->m_tree[$treeElement]['child'])) {

      $id = $this->getPropertyIdByName($nameOrId);

      foreach($this->m_tree[$treeElement]['child'] as $cpos) {
	$ob = $this->m_tree[$cpos];

	if (($ob['_type']==1) && ($ob['id']==$id)) return $ob['value'];
      }
    }


    return $default;
  }


  // -------------------------------------- //
  public function GetPropertyNum($treeElement, $nameOrId, $default = 0) {

    $v = $this->GetPropertyStr($treeElement, $nameOrId, pack('N*', $default) );

    return hexdec(bin2hex($v));

  }


  // -------------------------------------- //
  public function getXML($pos=-1, $deep=0) {

    $tmp='';
    $space = str_repeat('  ', $deep);
    $nl= "\n";

    if ($pos<0) $pos=0;

    $ob = $this->m_tree[$pos];

    $tag='';

    switch ($ob['_type'])
    {
      case 251:
    	  $tmp .= $space .'[list count="'. $ob['count'] .'" pos="'. $ob['pos'] .'"';
    
    	  if (count($ob['child'])==0)
    	  {
    	    $tmp .= ' /]'. $nl;
    	  }
    	  else
    	  {
    	    $tmp .= ']'. $nl;
    
    	    foreach($ob['child'] as $cpos)
    	    {
    	      $tmp .= $this->getXML($cpos, $deep+1);
    	    }
    
    	    $tmp .= $space .'[/list]'. $nl;
    	  }
    
    	  break;
    
    
    	case 254:
    
    	  $tmp .= $space .'[object type="'. $this->getObjectNameById($ob['objtype']) .'" id="'. $ob['id'] .'" pos="'. $ob['pos'] .'"';
    	  if ($ob['prop']!='') $tmp .= ' prop="#'. bin2hex($ob['prop']) .'"';
    
    
    	  if (count($ob['child'])==0)
    	  {
    	    $tmp .= ' /]'. $nl;
    	  }
    	  else
    	  {
    	    $tmp .= ']'. $nl;
    
    	    foreach($ob['child'] as $cpos)
    	    {
    	      $tmp .= $this->getXML($cpos, $deep+1);
    	    }
    
    	    $tmp .= $space .'[/object]'. $nl;
    	  }
    
    
    	  break;
    
    	case 1:
    	  $tmp .= $space .'[prop type="'. $this->getPropertyNameById($ob['id']) .'" value="#'. bin2hex($ob['value']) .'" pos="'. $ob['pos'] .'" /]'. $nl;
    	  break;
    
    	default:
    	  $tmp .= $space .'[?? type='. $ob['_type'] .' ?? /]'. $nl;
    }


    return $tmp;

  }
}



?>