<?PHP

// -------------------------------
// -- Version: 0.0.5  - 10. Sep. 2012
// --
// -- This program demonstrates the basic structure of National Instruments(c) LabView(c) fileformat
// --   * how the password protection is set (see clBDPW)
// --   * the Block-diagram (stored as Object Tree - see clObjectFile) is stored (see clBDHx)
// --   * the Frontpannel (stored as Object Tree - see clObjectFile) is stored (see clFPHx)
// --   * the Versions information of the VI-File (see clVers)
// --   * the Icon (see clIcon) - only 256 Color
// -- There is absolute no warranty for the work! This is just a "Proof of concept"
// -- Feel free to use/modify this code for any purpose. 
// -- I would be glad if You would contact me because of any questions, comments or problems about this code
// --
// --                                               see:  www.hmilch.net
// --                                               mail: Tomsoftware <at> gmx <dot> de
// -------------------------------

include_once('clFile.php');

class clLabView
{

  private $error=array();
  private $FileReader;

  //- Information about the data-containers / data-blocks
  private $BlockInfoCount;
  private $BlockInfo=array();

  //- Information about the Header of the VI File
  private $m_FileHead = array();


  //- Information about the file version
  private $m_version=array();

  //- LV-objects
  private $lvObj;



  // -------------------------------------- //
  /**
   * @param clFReader object Filereade-Objcet of the VI File
   */
  function __construct($FReader) {

    $this->lvObj=array();
    $this->FileReader = $FReader;
  }


  /** --------------------------------------
   * @abstract initial read of the VI-File
   * @return boolean true on success
   */
  public function readVI() {

    $FReader = $this->FileReader;

    //----
    //- Find last "Header" in file
    $lastPos = -1;
    $curPos = 0;

    while ($lastPos != $curPos) {

      $lastPos = $curPos;

      $FReader->setOffset($curPos);

      $this->m_FileHead['HeadIdentifier1'] = $FReader->readStr(6);
      $this->m_FileHead['HeadIdentifier2'] = $FReader->readInt(2);
      $this->m_FileHead['HeadIdentifier3'] = $FReader->readStr(4);
      $this->m_FileHead['HeadIdentifier4'] = $FReader->readStr(4);
    
      if ($this->m_FileHead['HeadIdentifier1'] != "RSRC\r\n")    return $this->setError('Wrong File Format: missing HeadIdentifier1: RSRC');
      if ($this->m_FileHead['HeadIdentifier3'] == 'LVAR')        return $this->setError('This program does not support .lvlib / LabView-LIB-files : wrong value for HeadIdentifier3: LVAR');
      if ($this->m_FileHead['HeadIdentifier3'] != 'LVIN')        return $this->setError('Wrong File Format: missing HeadIdentifier3: LVIN');
      if ($this->m_FileHead['HeadIdentifier4'] != 'LBVW')        return $this->setError('Wrong File Format: missing HeadIdentifier4: LBVW');

    
      $this->m_FileHead['RSRCOffset'] = $FReader->readInt(4);
      $this->m_FileHead['RSRCSize'] = $FReader->readInt(4);


      if (($this->m_FileHead['RSRCOffset'] > 0) && ($this->m_FileHead['RSRCSize'] > 0)) {
        $curPos = $this->m_FileHead['RSRCOffset'];
      }
      else {
        return $this->setError('Wrong RSRC-Haeder');
      }

    }
  
  
    //----
    //- Read Block-Infos
    $this->m_FileHead['DataSetOffset'] = $FReader->readInt();
    $this->m_FileHead['DataSetSize'] = $FReader->readInt(); 
  
    $this->m_FileHead['DataSetINT1'] = $FReader->readInt();
    $this->m_FileHead['DataSetINT2'] = $FReader->readInt();
    $this->m_FileHead['DataSetINT3'] = $FReader->readInt();

    $this->m_FileHead['BlockInfoOffset'] = $this->m_FileHead['RSRCOffset'] + $FReader->readInt();
    $this->m_FileHead['BlockInfoSize'] = $FReader->readInt();
  



    //----
    //- Read Block-Names
    $FReader->setOffset( $this->m_FileHead['BlockInfoOffset'] );
    $this->BlockInfoCount= $FReader->readInt() + 1;
  
    if ($this->BlockInfoCount > 1000) return $this->setError('VI.BlockInfoCount to large?!');

  
    $this->BlockInfo = array();

    for($i=0; $i<$this->BlockInfoCount; $i++) {
      $this->BlockInfo[$i]['BlockName'] = $FReader->readStr(4);
      $this->BlockInfo[$i]['BlockCount'] = ($FReader->readInt() + 1); //- thanks to Pete for his hint
                                                                //- not sure about versions before 8.0
      $this->BlockInfo[$i]['BlockInfoOffset'] = $this->m_FileHead['BlockInfoOffset'] + $FReader->readInt();
    }
  


    //----
    //- Read Blocks/container-Infos
    for($i=0; $i<$this->BlockInfoCount; $i++) {

      $FReader->setOffset($this->BlockInfo[$i]['BlockInfoOffset']);

      $this->BlockInfo[$i]['INT1'] = $FReader->readInt();
      $this->BlockInfo[$i]['INT2'] = $FReader->readInt();
      $this->BlockInfo[$i]['INT3'] = $FReader->readInt();
      $this->BlockInfo[$i]['BlockOffset'] = $this->m_FileHead['DataSetOffset'] + $FReader->readInt();
      $this->BlockInfo[$i]['INT4'] = $FReader->readInt();    
    }



    //----
    //-- get max container-size (need for [BlockCount]... version 4.0 of LV does not save [BlockCount] or I don't know how! )
    for($i=0; $i<$this->BlockInfoCount; $i++) {

      $minSize = $this->m_FileHead['DataSetSize'];

      for($j=0; $j<$this->BlockInfoCount; $j++) {

        if ($i!=$j) {
          $deltaSize = $this->BlockInfo[$j]['BlockOffset'] - $this->BlockInfo[$i]['BlockOffset'];

          if (($deltaSize > 0) && ($deltaSize < $minSize)) $minSize = $deltaSize;

        }

      }

      $this->BlockInfo[$i]['BlockFileSize'] = $minSize;
    }


    return true;
  }
  

  /** --------------------------------------
   * @abstract returns the VI Filename if 
   * @return clBDHx object Instanz of class clBDHx
   */
  public function getFileName()
  {
    return $this->FileReader->getFileName();
  }



  /** --------------------------------------
   * @abstract returns the VI BDHb or BDHc container Object that contains the Source Code

   * @return clBDHx object Instanz of class clBDHx
   */
  public function getBDHx()
  {
    if (!isset($this->lvObj['BDHx'])) {
      include_once('clBDHx.php');

      $this->lvObj['BDHx'] = new clBDHx($this);
    }

    return $this->lvObj['BDHx'];
  }


  /** --------------------------------------
   * @abstract returns the VI FPHb or FPHc container Object that contains the Front Panel
   * @return clFPHx object Instanz of class clFPHx
   */
  public function getFPHx()
  {
    if (!isset($this->lvObj['FPHx'])) {
      include_once('clFPHx.php');

      $this->lvObj['FPHx'] = new clFPHx($this);
    }

    return $this->lvObj['FPHx'];
  }

  

  /** --------------------------------------
   * @abstract returns the VI VCTP container Object that contains the Terminal information
   * @return clVCTP object instanz of class clVCTP
   */
  public function getVCTP()
  {
    if (!isset($this->lvObj['VCTP'])) {
      include_once('clVCTP.php');

      $this->lvObj['VCTP'] = new clVCTP($this);
    }

    return $this->lvObj['VCTP'];
  }

  /** --------------------------------------
   * @abstract returns the VI VERS container Object that contains version information
   * @return clVERS object instanz of class clVERS
   */
  public function getVERS()
  {
    if (!isset($this->lvObj['Vers'])) {
      include_once('clVers.php');

      $this->lvObj['Vers'] = new clVers($this);
    }

    return $this->lvObj['Vers'];
  }


  /** --------------------------------------
   * @abstract returns the VI BDPW container Object that contains the password infromation

   * @return clBDPW object Instanz of class clBDPW
   */
  public function getBDPW()
  {
    if (!isset($this->lvObj['BDPW'])) {
      include_once('clBDPW.php');

      $this->lvObj['BDPW'] = new clBDPW($this);
    }

    return $this->lvObj['BDPW'];
  }


  /** --------------------------------------
   * @abstract returns the VI Icon container Object that contains the Symbol
   * @return clIcon object Instanz of class clIcon
   */
  public function getICON()
  {
    if (!isset($this->lvObj['ICON'])) {
      include_once('clICON.php');

      $this->lvObj['ICON'] = new clICON($this);
    }

    return $this->lvObj['ICON'];
  }

  


  // -------------------------------------- //
  /**
   * @abstract returns a FileReader-Object of the container of a given BlockID or BlockName 
   * @return clFileReader object Instanz of class clFileReader
   */
  public function getBlockContent($BlockIdOrName, $useCompression = True, $BlockNr=0)
  {
    $FReader = $this->FileReader;
    $BlockID=-1;

    //- check if a ID or a Name is given
	  if (is_numeric($BlockIdOrName))
    {
      $BlockID=($BlockIdOrName +0);
    }
    else
	  {
	    //- find ID for Name
      foreach($this->BlockInfo as $i => $block)
	    {
	      if ($block['BlockName'] == $BlockIdOrName) 
	      {
	        $BlockID = $i;
          break;
        }
	    }
    }

    if ($BlockNr < 0) $BlockNr = 0;
    
    //- nothing found or wrong ID: return empty File
    if ($BlockID < 0)
    {
      $file = new clFile();
      return $file->getFileReader();
    }

    //- "jump" to Block-Number
    $offset = $this->BlockInfo[$BlockID]['BlockOffset'];
    $size = 0;
    $sumSize = 0;

    for($i=0; $i<=$BlockNr; $i++) {

      if (($size % 4) > 0) $size = $size + (4 - ($size % 4)); //- every block has a (to 4 Byte) up rounded size

      $sumSize += $size;
      $size = $FReader->readInt(4, $offset + $sumSize); //- read block size
      $sumSize += 4; //- 4 Byte for size

      if ($sumSize + $size > $this->BlockInfo[$BlockID]['BlockFileSize']) {
        $this->setError('getBlockContentById() - out of Block/container data');
        $file = new clFile();
        return $file->getFileReader();
      }
    }


    //- read the content of the block
    $BlockData = $FReader->getNewFileReader(0, $offset + $sumSize, $size);

    //- uncompress?
    if ($useCompression)
    {
      $BlockDataUnC = $this->BlockUncompress($BlockData, $BlockID);
      if (!$BlockDataUnC->eof()) return $BlockDataUnC;
      $BlockData->setOffset(0);
    }

    
    return $BlockData;

  }


  // -------------------------------------- //
  public function BlockNameExists($BlockName)
  {
    foreach($this->BlockInfo as $i => $block)
    {
      if ($block['BlockName'] == $BlockName) return true;
    }

    return false;
  }



  // -------------------------------------- //
  /**
   * @abstract returns a FileReader-Object of the uncompressed FileReader-Data-Object  
   * @return clFileReader object Instanz of class clFileReader
   */
  private function BlockUncompress($data, $info_blockID='')
  {
    $data->setOffset(0);
    $size=$data->len()-4;
    $file = new clFile();

    if ($size<2) {
      $this->setError('unable to decompress section [#'. $info_blockID .']: block-size-error - size: '. $size);
      return $file->getFileReader(); //- empty File
    }

    $usize=$data->readInt(4);
    
    if (($usize < $size) || ($usize > ($size * 10))) {
      $this->setError('unable to decompress section [#'. $info_blockID .']: uncompress-size-error - size: '. $size .' - uncompress-size:'. $usize);
      return $file->getFileReader(); //- empty File
    }
    

    $ucdata = @gzuncompress($data->readStr($size));

    if (strlen($ucdata)<1) {

      $this->setError('unable to decompress section [#'. $info_blockID .']: gzuncompress-error');
      return $file->getFileReader(); //- empty File
    }


    $file->readFromString($ucdata);

    return $file->getFileReader();

  }


  // -------------------------------------- //
  public function debug() {

    $nl = "\r\n" ;
    $out= '';


    if (count($this->error)>0) {
      $out .= '-- Errors --' .$nl;
      $out .= 'Errors : '. print_r($this->error, true) .$nl;
    }


    $out .= '-- Header --' .$nl;
    $out .= 'Magische 1      : '. $this->toHex($this->m_FileHead['HeadIdentifier1']) .$nl;
    $out .= 'Magische 2      : '. $this->toHex($this->m_FileHead['HeadIdentifier2']) .$nl;
    $out .= 'Magische 3      : '. $this->toHex($this->m_FileHead['HeadIdentifier3']) .$nl;
    $out .= 'Magische 4      : '. $this->toHex($this->m_FileHead['HeadIdentifier4']) .$nl;
    $out .= 'RSRCOffset      : '. $this->m_FileHead['RSRCOffset'] .$nl;
    $out .= 'RSRCSize        : '. $this->m_FileHead['RSRCSize'] .$nl .$nl;

    $out .= '-- DataSet --' .$nl;
    $out .= 'DataSetOffset   : '. $this->m_FileHead['DataSetOffset'] .$nl;
    $out .= 'DataSetSize     : '. $this->m_FileHead['DataSetSize'] .$nl;
    $out .= 'DataSetINT1     : '. $this->toHex($this->m_FileHead['DataSetINT1']) .$nl;
    $out .= 'DataSetINT2     : '. $this->toHex($this->m_FileHead['DataSetINT2']) .$nl;
    $out .= 'DataSetINT3     : '. $this->toHex($this->m_FileHead['DataSetINT3']) .$nl .$nl;


    $out .= '-- Block info --' .$nl;
    $out .= 'BlockInfoOffset : '. $this->m_FileHead['BlockInfoOffset'] .$nl;
    $out .= 'BlockInfoSize   : '. $this->m_FileHead['BlockInfoSize'] .$nl;
    $out .= 'BlockInfoCount  : '. $this->BlockInfoCount .$nl .$nl;


    $out .= '-- Block data --' .$nl;
    $out .= print_r($this->BlockInfo,true) .$nl;



    return $out;
  }



  // -------------------------------------- //
  /**
   * @abstract returns a Array with all Errors and reset the Error Handler
   * @return array Array with all Errors 
   */
  public function getError() {
    $ret = $this->error;
    $this->error=array();
    return $ret;
  }



  // -------------------------------------- //
  private function setError($errStr) {
    $this->error[]=$errStr;
    return false;
  }

  // -------------------------------------- //
  public static function toHex($value, $padding=' ') {

    if (is_int($value))
    {
      return '0x'. dechex($value);
    }
    else
    {
      $l=strlen($value);
      $out ='';

      for ($i=0; $i<$l; $i++)
      {
        $out .= str_pad(strtoupper(dechex(ord($value[$i]))), 2,'0', STR_PAD_LEFT) . $padding;
      }
    }


    return $out;
  }

  // -------------------------------------- //


  
}


?>