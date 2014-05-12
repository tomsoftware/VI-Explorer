<?php

include_once('clError.php');

class clVCTP {
  
  private $m_lv;
  private $m_objects;
  private $m_error; //- clError
  private $TypeNameTable;
  private $AttributNameTable;
  private $m_InterfaceCache;


  //- BEGIN [enumObjectFlags] -//
    const ObjectFlagHasLabel = 0x40;
  //- END [enumObjectFlags] -//



  //- BEGIN [enumTerminalMainType] -//
    const MainTypeUnknown = -1;
    const MainTypeNumber = 0x0;   //- INT/DBL/complex/...
    const MainTypeUnit = 0x1;     //- INT+Format: Enum/Units
    const MainTypeBool = 0x2;     //- only Boolean
    const MainTypeBlob = 0x3;     //- String/Path/...
    const MainTypeArray = 0x4;    //- Array
    const MainTypeCluster = 0x5;  //- Struct (hard code [Timestamp] or flexibl)
    const MainTypeRef = 0x7;      //- Pointer
    const MainTypeNumberPointer = 0x8;      //- INT+Format: Enum/Units Pointer
    const MainTypeTerminal = 0xF; //- like Cluser+Flags/Typdef
    //--- not official
    const MainTypeVoid = 0x100;   //- 0 is used for numbers
    const MainTypeValue = -2;     //- Entry for Enum
  //- END [enumTerminalMainType] -//




  //- BEGIN [enumAttributType]
    const AttributTypeUnknown = 0;
    const AttributTypeNumberFlag = 0x1;       //- Unknown: 0x00
    const AttributTypeStringFlag = 0x31;      //- Unknown: FF FF FF FF
    const AttributTypeArrayDimensions = 0x41; //- Count of Dimensions of a Array
    const AttributTypeClusterFormat = 0x51;
    const AttributTypeClusterFormatStr = 0x52;
    const AttributTypeRefType = 0x71;
    const AttributTypeRefTypeName = 0x72;
    const AttributTypeRefControlFlags = 0x73;
    const AttributTypeRefEventRegistFlags = 0x74;
    const AttributTypeRefDataValFlags = 0x75;
    const AttributTypeTerminalPattern = 0xF1; //- Index of pattern type for terminal
    const AttributTypeTerminalFlags = 0xF2;   //- seperarot between Index-List and Flags-List: 0x0300 or 0x0200 ???
    const AttributTypeTypDefFalg1 = 0xF5;
    const AttributTypeTypDefControlNameCount = 0xF6;
    const AttributTypeTypDefControlName1 = 0xF7;
    const AttributTypeTypDefControlName2 = 0xF8;
    const AttributTypeTypDefControlName3 = 0xF9;
  //- END [enumAttributType]



  //- BEGIN [enumTerminalSubType] -//
    const TypeUnknown = -1;
    const TypeVoid = 0;

    const TypeNumberI8 = 0x1;
    const TypeNumberI16 = 0x2;
    const TypeNumberI32 = 0x3;
    const TypeNumberI64 = 0x4;
    const TypeNumberU8 = 0x5;
    const TypeNumberU16 = 0x6;
    const TypeNumberU32 = 0x7;
    const TypeNumberU64 = 0x8;
    const TypeNumberSGL = 0x9;
    const TypeNumberDBL = 0xA;
    const TypeNumberXTP = 0xB;
    const TypeNumberCSG = 0xC;
    const TypeNumberCDB = 0xD;
    const TypeNumberCXT = 0xE;

    const TypeUnitI8 = 0x11;
    const TypeUnitI16 = 0x12;
    const TypeUnitI32 = 0x13;
    const TypeUnitI64 = 0x14;
    const TypeUnitU8 = 0x15;
    const TypeUnitU16 = 0x16;
    const TypeUnitU32 = 0x17;
    const TypeUnitU64 = 0x18;
    const TypeUnitSGL = 0x19;
    const TypeUnitDBL = 0x1A;
    const TypeUnitXTP = 0x1B;
    const TypeUnitCSG = 0x1C;
    const TypeUnitCDB = 0x1D;
    const TypeUnitCXT = 0x1E;

    const TypeBool = 0x21;

    const TypeString = 0x30;
    const TypePath = 0x32;
    const TypePicture = 0x33;
    const TypeCString = 0x34;
    const TypePascalString = 0x35;
    const TypeDAQChannel = 0x37;

    const TypeArray = 0x40;

    const TypeCluster = 0x50;
    const TypeClusterVariant = 0x53;
    const TypeClusterData = 0x54;
    const TypeClusterNumFixPoint = 0x5F;

    const TypeRef = 0x70;

    const TypePointerNumberI8 = 0x81;
    const TypePointerNumberI16 = 0x82;
    const TypePointerNumberI32 = 0x83;
    const TypePointerNumberI64 = 0x84;
    const TypePointerNumberU8 = 0x85;
    const TypePointerNumberU16 = 0x86;
    const TypePointerNumberU32 = 0x87;
    const TypePointerNumberU64 = 0x88;
    const TypePointerNumberSGL = 0x89;
    const TypePointerNumberDBL = 0x8A;
    const TypePointerNumberXTP = 0x8B;
    const TypePointerNumberCSG = 0x8C;
    const TypePointerNumberCDB = 0x8D;
    const TypePointerNumberCXT = 0x8E;

    const TypeTerminal = 0xF0;
    const TypeTypeDef = 0xF1;

    //- Not official
    const TypeValue = -2;

  //- END [enumTerminalSubType] -//



  //- BEGIN [enumDataType] -//
    const DataTypeString=10;
    const DataTypeNumber=20;
    const DataTypeNumberAsHex=21;
  //- END [enumDataType]  -//



  // -------------------------------------- //
  public function getError()
  {
    return $this->m_error;
  }


  // -------------------------------------- //
  function __construct($lv)
  {
    $this->m_lv = $lv;
    $this->m_error = new clError('clVCTP');

    $this->initNameTabels(); //-- fill $this->TypeNameTable

    $this->m_objects=array();
    $this->m_InterfaceCache=array();


    $this->m_VersionMaior = $lv->getVERS()->getMaior();


    if ($lv->BlockNameExists('VCTP'))
    {
      $reader = $lv->getBlockContent('VCTP', true);
      
      $count = $reader->readInt(4);
      
      $pos = $reader->getOffset();
      

      //- create Objects
      for($i=0; $i<$count; $i++)
      {
	$this->getNewObject(0, 0);
      }

      //- fill objects
      for($i=0; $i<$count; $i++)
      {
        

        $reader->setOffset($pos);
        $len = $reader->readInt(2);
        
	if ($len<4) $this->m_error->AddError('internel Error: wrong block size!');


	$ob=& $this->m_objects[$i];

	$ob['pos'] = $pos;
	$ob['size'] = $len;
        $ob['flags']=$reader->readInt(1);


	//-- basic type validation
	$this->setNewObjectType($ob, $reader->readInt(1));

        
        $pos += $len;

  
	//- read specific object propertys -//
	$this->readObjectInfo($reader, $i);
      }
    }
  }

  // -------------------------------------- //
  private Function setNewObjectType(&$ob, $type)
  {
    $ob['fileType']=$type;
    if (isset($this->TypeNameTable[$type]))
    {
      $ob['type']=$type;
      $ob['mainType']=($type >> 4);
      if ($type==0) $ob['mainType'] = self::MainTypeVoid;
      $ob['name']=$this->TypeNameTable[$type];
    }
  }

  // -------------------------------------- //
  private Function getNewObject($filePos, $length)
  {
    $item = array();
    $item ['atrib']=array();
    $item ['clients']=array();
    $item ['pos']=$filePos;
    $item ['size']=$length;
    $item ['flags']=0;
    $item ['label']='';
    $item ['fileType']=0;
    $item ['name']='';
    $item ['type']=self::TypeUnknown;
    $item ['mainType']=self::MainTypeUnknown;

    $Newindex = count($this->m_objects);

    $this->m_objects[$Newindex] = $item;

    return $Newindex;
  }


  // -------------------------------------- //
  // - calls the representing [readObjectInfo____] function for this Termianl-Object and reads the Label
  private function readObjectInfo($reader, $ObjectIndex)
  {

    $deltaLen=0;
    $tinfo=array(); //- tyObjectInfo
    $otyp=0;
    $length=0;


    $ob=& $this->m_objects[$ObjectIndex];


  
    $propReadOK = false;
    $subType = $ob['type'];
  

    switch ($ob['mainType'])
    {

      case self::MainTypeNumber: //-40 01   00
	$propReadOK = $this->readObjectPropertyNumber($reader, $ObjectIndex);
	break;

      case self::MainTypeNumberPointer:
	$propReadOK = $this->readObjectPropertyNumber($reader, $ObjectIndex);  //- not sure!
	break;

      case self::MainTypeBlob:   //- 40 32   FF FF FF FF
	switch($subType)
	{
	  case self::TypePascalString:
	  case self::TypeCString:
	    $propReadOK = true;
	    break;

	  default:
	    $propReadOK = $this->readObjectPropertyBlob($reader, $ObjectIndex);
	    break;
	}

      case self::MainTypeTerminal:

	switch ($subType)
	{
          Case self::TypeTerminal:
	    $propReadOK = $this->readObjectPropertyTerminal($reader, $ObjectIndex);
	    break;

          Case self::TypeTypeDef:
	    $propReadOK = $this->readObjectPropertyTypDef($reader, $ObjectIndex);
	    break;
	}
	break;
       
       
      case self::MainTypeArray:
	$propReadOK = $this->readObjectPropertyArray($reader, $ObjectIndex);
	break;

      case self::MainTypeUnit:
	$propReadOK = $this->readObjectPropertyUnit($reader, $ObjectIndex);
	break;
     
      case self::MainTypeRef:
	$propReadOK = $this->readObjectPropertyRef($reader, $ObjectIndex);
	break;

      case self::MainTypeBool:
      case self::MainTypeVoid:
	//- no propertys
	$propReadOK = True;
	break;

      case self::MainTypeCluster:
	$propReadOK = $this->readObjectPropertyCluster($reader, $ObjectIndex);
	break;

      default:  //- +MainTypeUnknown
	$this->m_error->AddError('unknown object type [index='. $ObjectIndex .']: 0x'. dechex($ob['fileType']) .'  @ '.$ob['pos'], true, '', false);
	$propReadOK = false;
    }



    //- read Label of object
    if ($propReadOK)
    {
  
      if ($ob['flags'] & self::ObjectFlagHasLabel)
      {
      
	$length = $reader->readInt(1);
	$deltaLen = $ob['size'] - ($reader->getOffset() - $ob['pos']);  //- not sure about this!

	if (($deltaLen == $length) || ($deltaLen == $length + 1))
	{
          $ob['label'] = $reader->readStr($length);
	}
	else
	{
          $this->m_error->AddError('Caption/Label size (diff: '. ($deltaLen - $length) .' - len: '. $length .'  -  '. $ob['size'] .') [index='. $ObjectIndex .'] Error @ '. $ob['pos']);
        }
        
      }
    
    }
  }


  // -------------------------------------- //
  private function readObjectPropertyArray($Reader, $ObjectIndex)
  {
    $ob=& $this->m_objects[$ObjectIndex];
  

    $dimensions = $Reader->readInt(2); //- dimensions
    

    if ($dimensions > 64)
    {
      $this->m_error->AddError('Array Dimension ('. $dimensions .') is > 64 @ '. $ob['pos']);
      return false;
    }


    $this->AddPropertyNum($ObjectIndex, self::AttributTypeArrayDimensions, $dimensions, false);
      
    $ok = true;
      
    for($i=0;$i<$dimensions;$i++)
    {
      $tmp = $Reader->readInt(4);
      if (($tmp != 0xFFFFFFFF) && ($tmp != -1)) //- depending on platform 32/64 Bit
      {
        $this->m_error->AddError('Array with property (index: '. $i .' - flag: '. $tmp .' ? ) @ '. $ob['pos']);
        $ok=false;
      }
    }

      
    if ($ok)
    {
      $tmp = $Reader->readInt(2);
        
      if ($tmp > $ObjectIndex)
      {
	$this->m_error->AddError('Wrong value for Array-Type-Index '. $tmp .' is > "This-Array-Object"-Index: '. $ObjectIndex .' @ '. $ob['pos']);
	$ok=false;   
      }
      else
      {
	$item = array();
	$item['index'] = $tmp;
	$item['flags'] = 0;

        $ob['clients'][] = $item;
      }

    }

  
    return $ok;
  }

  // -------------------------------------- //
  private function readObjectPropertyNumber($Reader, $ObjectIndex)
  {

    $tmp = $Reader->readInt(1);
  
    if ($tmp == 0) return true;

    $this->m_error->AddError('Number [index='. $ObjectIndex .'] with property (0x'. dechex($tmp) .')??? @ '. $this->m_objects[$ObjectIndex]['pos']);
    return false;
  }


  // -------------------------------------- //
  private function AddPropertyNum($ObjectIndex, $PropertyType, $Value)
  {
    $this->m_objects[$ObjectIndex]['atrib'][] = array('n'=>$PropertyType, 'v'=>$Value, 't'=>self::DataTypeNumber);
  }

  // -------------------------------------- //
  private function AddPropertyStr($ObjectIndex, $PropertyType, $Value)
  {
    $this->m_objects[$ObjectIndex]['atrib'][] = array('n'=>$PropertyType, 'v'=>$Value, 't'=>self::DataTypeString);
  }


  // -------------------------------------- //
  private function readObjectPropertyCluster($Reader, $ObjectIndex)
  {
    $ob=& $this->m_objects[$ObjectIndex];

  
    switch($ob['type'])
    {
      case self::TypeCluster:
	$count = $Reader->readInt(2);
          
	If ($count > 124)
	{
          $this->m_error->AddError('Cluster Item count ('. $count .') is > 124 @ '. $ob['pos']);
          return false;
	}
	else
	{
          for($i=0; $i<$count; $i++)
	  {
	    $item = array();
	    $item['index'] = $Reader->readInt(2);
	    $item['flags'] = 0;

	    $ob['clients'][] = $item;
	  }
	}
	return true;
	//- break;


      case self::TypeClusterData:
         
        $tmp = $Reader->readInt(2);
        
        switch ($tmp)
	{
          case 6:  $tmpStr = 'TimeStamp';	break;
          case 7:  $tmpStr = 'Digitaldata';	break;
          case 9:  $tmpStr = 'Dynamicdata';	break;
          default: $tmpStr = '0x'.dechex($tmp);	break;
	}


        $this->AddPropertyNum($ObjectIndex, self::AttributTypeClusterFormat, $tmp);
        $this->AddPropertyStr($ObjectIndex, self::AttributTypeClusterFormatStr, $tmpStr);
        
	return true;
    }

    return false;
  
  }

  // -------------------------------------- //
  private function readObjectPropertyRef($Reader, $ObjectIndex)
  {

    $ret = False;

    $refType = $Reader->readInt(2);

    $this->AddPropertyNum($ObjectIndex, self::AttributTypeRefType, $refType);
  
    switch ($refType)
    {
      Case 0x1:
	$refTypeName = 'DataLogFile';
	$ret = $this->readObjectPropertyRefQueue($Reader, $ObjectIndex);
	break;

      Case 0x4:
	$refTypeName = 'Occurrence';
	$ret = True; //- empty: no more propertys
	break;
      
    
      Case 0x17:
	$refTypeName = 'EventRegistration';
	$ret = $this->readObjectPropertyRefEventRegist($Reader, $ObjectIndex);
	break;

      Case 0x19:
        $refTypeName = 'UserEvent';
        $ret = $this->readObjectPropertyRefQueue($Reader, $ObjectIndex);
	break;


     Case 0x5:
	$refTypeName = 'TCP connection';
	$ret = True;
	break;

      Case 0x10:
	$refTypeName = 'UDP connection';
	$ret = True;
	break;

      Case 0x13:
	$refTypeName = 'IrDA connection';
	$ret = True;
	break;
      
      Case 0x1F:
	$refTypeName = 'Bluetooth connection';
	$ret = True;
	break;

      Case 0x15:
	$refTypeName = 'Shared variable';
	$ret = false;
	break;


      Case 0xD:
        $refTypeName = 'DataSocket';
	break;

      Case 0x8:
	$refTypeName = 'Control'; //- Control Refnum
	$ret = $this->readObjectPropertyRefControl($Reader, $ObjectIndex);
	break;

      Case 0x12:
	$refTypeName = 'Queue';
	$ret = $this->readObjectPropertyRefQueue($Reader, $ObjectIndex);
	break;

      Case 0x14:
	$refTypeName = 'Channel';
	break;

      Case 0x20:
	$refTypeName = 'data value reference';
	$ret = $this->readObjectPropertyRefDataValue($Reader, $ObjectIndex);
	break;

      Case 0x1E:
	$refTypeName = 'Class';
	break;

      default:
	$refTypeName = '[unknown]';
	$this->m_error->AddError('Unknown refenence Type (index: '. $ObjectIndex .' - 0x'. dechex($refType) .')??? @ '. $this->m_objects[$ObjectIndex]['pos']);
	break;
    }
  
  
    $this->AddPropertyStr($ObjectIndex, self::AttributTypeRefTypeName, $refTypeName);
  
    return $ret;
  }


  // -------------------------------------- //
  private function readObjectPropertyRefDataValue($Reader, $ObjectIndex)
  {

    $ob=& $this->m_objects[$ObjectIndex];


    $count = $Reader->readInt(2); //- item count
    
    if ($count <= 1)
    {
    
      for ($i = 0; $i<1; $i++)
      {
        //- add Item to parent
        $ob['clients'][$i]['index'] = $Reader->readInt(2);
        $ob['clients'][$i]['flags'] = 0;
      }

      
      $value = $Reader->readInt(1); //- dont know value
      $this->AddPropertyNum($ObjectIndex, self::AttributTypeRefDataValFlags, $value);

      return true;

    }
    else
    {

      $this->m_error->AddError('[readObjectPropertyRefDataValue]  - Unknown value/count (0x'. dechex($count) .') ? @ '. $ob['pos']);

      return false;
    }
  }




  // -------------------------------------- //
  private function readObjectPropertyRefControl($Reader, $ObjectIndex)
  {

    $ob=& $this->m_objects[$ObjectIndex];


    $count = $Reader->readInt(2); //- item count
    
    if ($count <= 1)
    {
    
      for ($i = 0; $i<1; $i++)
      {
        //- add Item to parent
        $ob['clients'][$i]['index'] = $Reader->readInt(2);
        $ob['clients'][$i]['flags'] = 0;
      }

      
      $count = $Reader->readInt(2); //- dont know count
      $this->AddPropertyNum($ObjectIndex, self::AttributTypeRefControlFlags, $count);

      return true;

    }
    else
    {

      $this->m_error->AddError('[readObjectPropertyRefControl]  - Unknown value/count (0x'. dechex($count) .') ? @ '. $ob['pos']);

      return false;
    }
  }

  // -------------------------------------- //
  private function readObjectPropertyRefEventRegist($Reader, $ObjectIndex)
  {

    $ob=& $this->m_objects[$ObjectIndex];

     //- dont know this data!
    $tmp1 = $Reader->readInt(2);
    $tmp2 = $Reader->readInt(2);
    $tmp3 = $Reader->readInt(2);
    $tmp4 = $Reader->readInt(2);
    $tmp5 = $Reader->readInt(2);

    
    if (($tmp1 == 0) && ($tmp2 == 1) && ($tmp3 == 1) && ($tmp4 == 0))
    {
      $this->AddPropertyNum($ObjectIndex, self::AttributTypeRefEventRegistFlags, $tmp5);
    
      $count = 1; //- dont know if $tmp2 or $tmp3??


      for ($i = 0; $i<1; $i++)
      {
        //- add Item to parent
        $ob['clients'][$i]['index'] = $Reader->readInt(2);
        $ob['clients'][$i]['flags'] = 0;
      }


      return true;
    }
    else
    {

      $this->m_error->AddError('[readObjectPropertyRefEventRegist]  - Unknown value {0x'. dechex($tmp1) .', 0x'. dechex($tmp2) .', 0x'. dechex($tmp3) .', 0x'. dechex($tmp4) .', 0x'. dechex($tmp5) .'} ? @ '. $ob['pos']);

      return false;
    }
    

    
  }

  // -------------------------------------- //
  private function readObjectPropertyRefQueue($Reader, $ObjectIndex)
  {

    $ob=& $this->m_objects[$ObjectIndex];


    $count = $Reader->readInt(2); //-item count

    if ($count <= 1)
    {
      
      for ($i = 0; $i<1; $i++)
      {
        //- add Item to parent
        $ob['clients'][$i]['index'] = $Reader->readInt(2);
        $ob['clients'][$i]['flags'] = 0;
      }


      return true;
    }
    else
    {
      
      $this->m_error->AddError('[readObjectPropertyRefQueue]  - Unknown value/count ['. decHex($tmp) .'] ? @ '. $ob['pos']);
      return false;
    }

    
  }


  // -------------------------------------- //
  private function readObjectPropertyUnit($Reader, $ObjectIndex)
  {
    $count = $Reader->readInt(2); //-unit/item count

    $ob=& $this->m_objects[$ObjectIndex];


    $DataSize=0;
    $isTextEnum = False;

    if (($ob['type'] == self::TypeUnitU16) || ($ob['type'] == self::TypeUnitU8) || ($ob['type'] == self::TypeUnitU32)) $isTextEnum = True;
  

    for ($i=0; $i<$count; $i++)
    {
      $tmpClient = array();

      $tmpClient['atrib']=array();
      $tmpClient['clients']=array();
      $tmpClient['mainType'] = self::MainTypeValue;
      $tmpClient['type'] = self::TypeValue;
      $tmpClient['fileType'] = self::TypeValue;
      $tmpClient['pos'] = $Reader->getOffset();
      $tmpClient['flags'] = 0;
      $tmpClient['name'] = 'UnitValue';

      $unitsize=0;
 
      if ($isTextEnum)
      {
	$unitsize = $Reader->readInt(1);

	$tmpClient['label'] = $Reader->readStr($unitsize);
                             
        $DataSize += $unitsize;
      }
      else 
      {
	$tmpClient['label'] = '0x'. decHex($Reader->readInt(4));
      }        
      

      $tmpClient['len'] = $unitsize;


      //- Add client
      $ob['client'][] = $tmpClient;

    }

    
    
    If ($isTextEnum)
    {
      //- pad size to mod 2 - not sure about this!
      $tmp = $Reader->readInt($DataSize % 2);
					      
      //$tmp = $Reader->readInt(($Reader->getOffset() + 1) % 2);
      
      
      if ($tmp != 0)
      {
        $this->m_error->AddError('Number+Uni - padding Error - unknown Data ['. decHex($tmp) .'] ? index='. $ObjectIndex .' @ '. $ob['pos']);
      }
    }
    
    
    //- dont know property -> see readObjectPropertyNumber
    $tmp = $Reader->readInt(1);
    If ($tmp != 0)
    {
      $this->m_error->AddError('Number+Uni - Unknown Data ['. decHex($tmp) .'] Property? index='. $ObjectIndex .' @ '. $ob['pos']);
    }


    return True;
  }


  // -------------------------------------- //
  private function readObjectPropertyTypDef($Reader, $ObjectIndex)
  {

    $ob=& $this->m_objects[$ObjectIndex];

    $tmp = $Reader->readInt(4);
    $this->AddPropertyNum($ObjectIndex, self::AttributTypeTypDefFalg1, $tmp);

    $count = $Reader->readInt(4);
    $this->AddPropertyNum($ObjectIndex, self::AttributTypeTypDefControlNameCount, $count);


    for($i=0; $i<$count; $i++)
    {
      $length = $Reader->readInt(1); //- lenght of label
      $this->AddPropertyStr($ObjectIndex, self::AttributTypeTypDefControlName1 + $i, $Reader->readStr($length));
    }


    //- Create New Object
    $length = $Reader->readInt(2) - 6; //- dont know why?!

    $unitIndex = $this->getNewObject($Reader->getOffset()+2, $length-2);

    $Newob=& $this->m_objects[$unitIndex];

    $Newob['flags'] = $Reader->readInt(1);

    $this->setNewObjectType($Newob, $Reader->readInt(1));

    //- add Item to parent
    $ob['clients'][0]['index'] = $unitIndex;
    $ob['clients'][0]['flags'] = 0;


    //- read specific object propertys -//
    $this->readObjectInfo($Reader, $unitIndex);


    return true;
  }

  // -------------------------------------- //
  private function readObjectPropertyTerminal($Reader, $ObjectIndex)
  {
    $ob=& $this->m_objects[$ObjectIndex];
    

    $count = $Reader->readInt(2);
    
    if ($count > 125)
    {
      $this->m_error->AddError('Terminal count > 124 @ '. $ob['pos']);
      return false;
    }


    for($i=0; $i<$count; $i++)
    {
      $ob['clients'][$i]['index'] = $Reader->readInt(2);
    }
      
    $this->AddPropertyNum($ObjectIndex, self::AttributTypeTerminalFlags, $Reader->readInt(2));
      
    $this->AddPropertyNum($ObjectIndex, self::AttributTypeTerminalPattern, $Reader->readInt(2), false);

  
    If ($this->m_VersionMaior > 8) 
    {
      $Reader->readInt(2); //- don't know/padding

      for($i=0; $i<$count; $i++)
      {
        $ob['clients'][$i]['flags'] = $Reader->readInt(4);
      }
    }
    else
    {
      for($i=0; $i<$count; $i++)
      {
        $ob['clients'][$i]['flags'] = $Reader->readInt(2);
      }
    }

    //- add to Index Tabel for Terminal/Interfaces
    $this->m_InterfaceCache[] = $ObjectIndex;

    return true;
  }

  // -------------------------------------- //
  private function readObjectPropertyBlob($Reader, $ObjectIndex)
  {
    $tmp = $Reader->readInt(4);
  
    if (($tmp == 0xFFFFFFFF) || ($tmp == -1)) return true; //- same as 0xffffffff

    $this->m_error->AddError('Blob with prop ('. dechex($tmp) .')??? @ '. $this->m_objects[$ObjectIndex]['pos']);
    return false;
  }




  // -------------------------------------- //
  public function getClientCount($ObjectIndex)
  {
    if (($ObjectIndex >= 0) && ($ObjectIndex < count($this->m_objects)))
    {
      return count($this->m_objects[$ObjectIndex]['clients']);
    }
    
    return 0;
  }



  // -------------------------------------- //
  public function getClient($ObjectIndex, $ClientIndex)
  {
    if (($ObjectIndex >= 0) && ($ObjectIndex < count($this->m_objects) ))
    {
      if (($ClientIndex >= 0) && ($ClientIndex < count($this->m_objects[$ObjectIndex]['clients']) ))
      {
        return $this->m_objects[$ObjectIndex]['clients'][$ClientIndex]['index'];
      }
    }

    return -1;  
  }


  // -------------------------------------- //
  public function getObjectIndexForInterface($InterfaceIndex)
  {
    if (($InterfaceIndex >= 0) && ($InterfaceIndex < count($this->m_InterfaceCache)))
    {
      return $this->m_InterfaceCache[$InterfaceIndex];
    }

    return -1;
  }

  // -------------------------------------- //
  public function getObjectIndexForInterfaceCount()
  {
    return count($this->m_InterfaceCache);
  }


  // -------------------------------------- //
  public function isNumber($ObjectIndex)
  {
    if (($ObjectIndex >= 0) && ($ObjectIndex < count($this->m_objects)))
    {
      $mainType = $this->m_objects[$ObjectIndex]['mainType'];
      return (($mainType == self::MainTypeNumber) || ($mainType == self::MainTypeUnit));
    }
    return false;
  }


  // -------------------------------------- //
  public function isString($ObjectIndex)
  {
    if (($ObjectIndex >= 0) && ($ObjectIndex < count($this->m_objects)))
    {
      return ($this->m_objects[$ObjectIndex]['type'] == self::TypeString);
    }

    return false;
  }


  // -------------------------------------- //
  public function isPath($ObjectIndex)
  {
    if (($ObjectIndex >= 0) && ($ObjectIndex < count($this->m_objects)))
    {
      return ($this->m_objects[$ObjectIndex]['type'] == self::TypePath);
    }
  
    return false;
  }



  // -------------------------------------- //
  public function getXML($index=-1, $deep= 0, $ClientFlags=0) {

  
    $out='';

    //- Main run -//
    if (($index == -1) && ($deep==0) && ($ClientFlags==0))
    {
      //$out  = "<'.'?xml version='1.0'?'.'>\n";
      //$out .= "<!-- Filename='". htmlentities($this->m_lv->getFileName()) ."' -->\n\n";
      $out .= "<VCTP>\n";
    
      foreach ($this->m_objects as $i=>$ob)
      {
        $out .= $this->getXML($i, 1);
      } 

      $out .= $this->m_error->getXML();

      $out .= "</VCTP>\n";
        
      return $out;
    }
  
    //- Sub run --//
    if (!isset($this->m_objects[$index])) return '';
  

    $SpPad = str_repeat(' ', $deep * 2);
  
    $ob = & $this->m_objects[$index];

    switch ($ob['mainType'])
    {
      Case self::MainTypeValue:
	$tagName = 'VALUE';
	brak;

      default:
	$tagName = 'VAR';
	break;
    }


    $out .= $SpPad ."<". $tagName ." index='". $index ."' ";


    if (($ob['type'] < 256) && ($ob['type'] >= 0)) 
    {
      $out .= " ObjectType='0x". decHex($ob['type']) .":". $ob['name'] ."' ";
    }
    else
    {
      if ($ob['name'] == '')
      {
        $out .= " ObjectType='0x". decHex($ob['fileType']) .":unknown' ";
      }
      else
      {
        $out .= " ObjectType='". $ob['name'] ."' ";
      }
    }
    
  
    if ($ob['label'] != '')
    {
       $out .= " Label='". htmlentities($ob['label']) ."' ";
    }
  
  
  
    if ($ClientFlags != 0)
    {
      if ($ClientFlags & 0x100)
      {
        $out .= "Type='input' ";
        $ClientFlags = $ClientFlags - 0x100;
      }
      Else
      {
        $out .= "Type='output' ";
      }
      
      
      if ($ClientFlags > 0) $out .= "TerminalFlag='0x". decHex($ClientFlags) ."' ";
      
    }
  
  
  
  
    foreach ($ob['atrib'] as $attribut)
    {
      $n = $attribut['n'];
      if (!isset($this->AttributNameTable[$n])) $n=self::AttributTypeUnknown;

      $out .= $this->AttributNameTable[$n] ."=";
        
      switch ($attribut['t'])
      {
	Case self::DataTypeString:
	  $out .= "'". htmlentities($attribut['v']) ."' ";
	  break;

	Case self::DataTypeNumber:
	  $out .= "'". $attribut['v'] ."' ";
	  break;

	Case self::DataTypeNumberAsHex:
	  $out .= "'0x". decHex($attribut['v']) ."' ";
	  break;

	default:
	  $out .= "'0x". decHex($attribut['v']) .":". htmlentities($attribut['v']) ."' ";
	  break;
      }
    }
    
  
    if (count($ob['clients']) == 0)
    {
      $out .= "/>\n";
    }
    else
    {
      $out .= ">\n";

      foreach($ob['clients'] as $client)
      {

        $out .=  $this->getXML($client['index'], ($deep + 1), $client['flags']);
      }
      
      $out .= $SpPad ."</". $tagName .">\n";
    }
    

    return $out; // print_r($this->m_objects, true);
  }



  // -------------------------------------- //
  private function initNameTabels() {

    $this->TypeNameTable[self::TypeVoid]=	'Void';
    $this->TypeNameTable[self::TypeTerminal]=	'Terminal';
    $this->TypeNameTable[self::TypeTypeDef]=	'TypeDef';
      
    $this->TypeNameTable[self::TypeNumberI8]=	'I8';
    $this->TypeNameTable[self::TypeNumberI16]=	'I16';
    $this->TypeNameTable[self::TypeNumberI32]=	'I32';
    $this->TypeNameTable[self::TypeNumberI64]=	'I64';
  
    $this->TypeNameTable[self::TypeNumberU8]=	'U8';
    $this->TypeNameTable[self::TypeNumberU16]=	'U16';
    $this->TypeNameTable[self::TypeNumberU32]=	'U32';
    $this->TypeNameTable[self::TypeNumberU64]=	'U64';
  
    $this->TypeNameTable[self::TypeNumberSGL]=	'Single precision';
    $this->TypeNameTable[self::TypeNumberDBL]=	'Double precision';
    $this->TypeNameTable[self::TypeNumberXTP]=	'Extended precision';
  
    $this->TypeNameTable[self::TypeNumberCSG]=	'Complex Single';
    $this->TypeNameTable[self::TypeNumberCDB]=	'Complex Double';
    $this->TypeNameTable[self::TypeNumberCXT]=	'Complex Extended';


    $this->TypeNameTable[self::TypeUnitI8]=	'I8+Unit';
    $this->TypeNameTable[self::TypeUnitI16]=	'I16+Unit';
    $this->TypeNameTable[self::TypeUnitI32]=	'I32+Unit';
    $this->TypeNameTable[self::TypeUnitI64]=	'I64+Unit';
  
    $this->TypeNameTable[self::TypeUnitU8]=	'U8+Unit';
    $this->TypeNameTable[self::TypeUnitU16]=	'U16+Unit';
    $this->TypeNameTable[self::TypeUnitU32]=	'U32+Unit';
    $this->TypeNameTable[self::TypeUnitU64]=	'U64+Unit';
  
    $this->TypeNameTable[self::TypeUnitSGL]=	'Single precision+Unit';
    $this->TypeNameTable[self::TypeUnitDBL]=	'Double precision+Unit';
    $this->TypeNameTable[self::TypeUnitXTP]=	'Extended precision+Unit';
  
    $this->TypeNameTable[self::TypeUnitCSG]=	'Complex Single+Unit';
    $this->TypeNameTable[self::TypeUnitCDB]=	'Complex Double+Unit';
    $this->TypeNameTable[self::TypeUnitCXT]=	'Complex Extended+Unit';
  
  
    $this->TypeNameTable[self::TypePointerNumberI8]=	'I8 Pointer';
    $this->TypeNameTable[self::TypePointerNumberI16]=	'I16 Pointer';
    $this->TypeNameTable[self::TypePointerNumberI32]=	'I32 Pointer';
    $this->TypeNameTable[self::TypePointerNumberI64]=	'I64 Pointer';
  
    $this->TypeNameTable[self::TypePointerNumberU8]=	'U8 Pointer';
    $this->TypeNameTable[self::TypePointerNumberU16]=	'U16 Pointer';
    $this->TypeNameTable[self::TypePointerNumberU32]=	'U32 Pointer';
    $this->TypeNameTable[self::TypePointerNumberU64]=	'U64';
  
    $this->TypeNameTable[self::TypePointerNumberSGL]=	'Single precision Pointer';
    $this->TypeNameTable[self::TypePointerNumberDBL]=	'Double precision Pointer';
    $this->TypeNameTable[self::TypePointerNumberXTP]=	'Extended precision Pointer';
  
    $this->TypeNameTable[self::TypePointerNumberCSG]=	'Complex Single Pointer';
    $this->TypeNameTable[self::TypePointerNumberCDB]=	'Complex Double Pointer';
    $this->TypeNameTable[self::TypePointerNumberCXT]=	'Complex Extended Pointer';



    $this->TypeNameTable[self::TypeBool]=	'Boolean';
  
    $this->TypeNameTable[self::TypeString]=	'String';
    $this->TypeNameTable[self::TypeCString]=	'C String';
    $this->TypeNameTable[self::TypePascalString]='Pascal String';

    $this->TypeNameTable[self::TypePath]=	'Path';
    $this->TypeNameTable[self::TypePicture]=	'Picture';
    $this->TypeNameTable[self::TypeDAQChannel]='DAQ Channel';
      
    $this->TypeNameTable[self::TypeArray]=	'Array';
  
    $this->TypeNameTable[self::TypeCluster]=	'Cluster';
    $this->TypeNameTable[self::TypeClusterData]='Data';
    $this->TypeNameTable[self::TypeClusterNumFixPoint]='FixPointNumber';
    $this->TypeNameTable[self::TypeClusterVariant]= 'Variant';
      
    $this->TypeNameTable[self::TypeRef]=	'Reference';



    //////

    $this->AttributNameTable[self::AttributTypeTerminalPattern]=	'TerminalPattern';
    $this->AttributNameTable[self::AttributTypeTerminalFlags]=		'TerminalFlags';
    
    $this->AttributNameTable[self::AttributTypeStringFlag]=		'StringFlag';
    
    $this->AttributNameTable[self::AttributTypeArrayDimensions]=	'ArrayDimensions';
    
    $this->AttributNameTable[self::AttributTypeNumberFlag]=		'NumberFlag';
    
    $this->AttributNameTable[self::AttributTypeClusterFormat]=		'ClusterFormat';
    $this->AttributNameTable[self::AttributTypeClusterFormatStr]=	'ClusterFormatStr';
    
    $this->AttributNameTable[self::AttributTypeTypDefFalg1]=		'TypDefFalg1';
    $this->AttributNameTable[self::AttributTypeTypDefControlNameCount]=	'TypDefControlNameCount';
    $this->AttributNameTable[self::AttributTypeTypDefControlName1]=	'TypDefControlName1';
    $this->AttributNameTable[self::AttributTypeTypDefControlName2]=	'TypDefControlName2';
    $this->AttributNameTable[self::AttributTypeTypDefControlName3]=	'TypDefControlName3';
    
    $this->AttributNameTable[self::AttributTypeRefType]=		'RefType';
    $this->AttributNameTable[self::AttributTypeRefTypeName]=		'RefTypeName';
    
    $this->AttributNameTable[self::AttributTypeRefControlFlags]=	'RefControlFlags';
    $this->AttributNameTable[self::AttributTypeRefEventRegistFlags]=	'RefEventRegistFlags';

    $this->AttributNameTable[self::AttributTypeRefDataValFlags]=	'RefDataValeuFlags';
  


    $this->AttributNameTable[self::AttributTypeUnknown]=		'_unknown_';
    
  }
}

?>