<?PHP

class clFile
{
  private $content;
  private $error;
  private $filename;


  // -------------------------------------- //
  public function __construct() {
    $this->error=array();
  }

  // -------------------------------------- //
  public function readFromFile($fileName)
  {
    $this->content = file_get_contents($fileName);
    $this->filename = $fileName;
  }

  // -------------------------------------- //
  public function readFromString($content)
  {
    $this->content = $content;
    $this->filename = '';
  }


  // -------------------------------------- //
  public function getFileReader() {
    return new clFileReader($this);
  }


  // -------------------------------------- //
  public function contentLen() {
    return strlen($this->content);
  }

  // -------------------------------------- //
  public function readStr($offset, $lenght=-1) {
    if ($lenght<0) return substr($this->content, $offset);

    return substr($this->content, $offset, $lenght);
  }

  // -------------------------------------- //
  public function writeStr($offset, $content) {

    $len = strlen($content);
    
    for($i=0; $i<$len ; $i++)
    {
      $this->content[$i + $offset] = $content[$i];
    }

    return $len;
  }


  // -------------------------------------- //
  public function getError() {
    return $this->error;
  }



  // -------------------------------------- //
  private function setError($errStr) {
    $this->error[]=$errStr;
    return false;
  }

  // -------------------------------------- //
  public function getFilename() {
    return $this->filename;
  }


  // -------------------------------------- //
  public function store($filename) {
    return file_put_contents($filename, $this->content);
  }


};



// ------------------------------------------------------------ //
class clFileReader
{
  private $clF;
  private $offset=0;
  private $hiddenoffset=0;
  private $blocklen=-1;
  private $error;
  private $m_eof=true;

  // -------------------------------------- //
  public function __construct($clFile, $offset=0, $hiddenoffset=0, $blocklen=-1) {
    $this->clF = $clFile;
    $this->offset=$offset;
    $this->hiddenoffset=$hiddenoffset;
    $this->error=array();

    if ($blocklen>$clFile->contentLen()) $blocklen=$clFile->contentLen();

    if ($clFile->contentLen()>0)
    {
      $this->m_eof=false;

      if ($blocklen>=0)
      {
        $this->blocklen=$blocklen;
      }
      else
      {
        $this->blocklen=$clFile->contentLen();
      }
    }
    else
    {
      $this->m_eof=true;
      $this->blocklen=0;
    }
  }


  // -------------------------------------- //
  public function writeStr($content, $offset=-1)
  {
    if ($offset>-1) $this->offset=$offset;

    $ofs = $this->hiddenoffset + $this->offset;

    $ret = $this->clF->writeStr($ofs, $content);

    $this->offset += $ret;

    return true;
  }

  // -------------------------------------- //
  public function readStr($size=-1, $offset=-1)
  {
    $out ='';

    if ($size == -1) $size = $this->blocklen;
    if ($size < 0) $size = 0;
    if ($offset>-1) $this->offset=$offset;

    $ofs = $this->hiddenoffset + $this->offset;

    if ($this->offset<$this->blocklen) 
    {
      if ($this->offset+$size>$this->blocklen)
      {
	$this->m_eof=true;
	$size=$this->blocklen-$this->offset;
      }

      $out = $this->clF->readStr($ofs, $size);
    }
    else
    {
      $this->setError('EOF!');
      $this->m_eof=true;
      $size=0;
      $out='';
      
    }
  
    $this->offset += $size;

    return $out;
  }

  // -------------------------------------- //
  public function store($filename) {
    return file_put_contents($filename, $this->readStr($this->blocklen, 0));
  }



  // -------------------------------------- //
  // - returns signed (e.g. negativ) intager values
  public function readInt($size=4, $offset=-1)
  {
    $tmp = $this->readStr($size, $offset);

    $ret =0;
    $len = strlen($tmp);


    for ($i=0; $i<$len; $i++)
    {
      $ret = ($ret<<8) + ord($tmp[$i]);
    }  

    return $ret;
  }


  // -------------------------------------- //
  /**
   * @abstract returns a part of this File as new FileReader 
   * @return clFileReader object Instanz of class clFileReader
   */  
  public function getNewFileReader($offset=0, $hiddenoffset=0, $blocklen=-1) {
    return new clFileReader($this->clF, $offset, $hiddenoffset, $blocklen);
  }


  // -------------------------------------- //
  public function setOffset($newOffset) {
    $this->m_eof=false;
    $this->offset=$newOffset;
  }

  // -------------------------------------- //
  public function getOffset() {
    if ($this->m_eof) return -1;
    return $this->offset;
  }


  // -------------------------------------- //
  public function eof() {
    return $this->m_eof;
  }

  // -------------------------------------- //
  public function len() {
    return $this->blocklen;
  }



  // -------------------------------------- //
  public function getError() {
    return $this->error;
  }


  // -------------------------------------- //
  public function getFileName() {
    return $this->clF->getFileName();
  }


  // -------------------------------------- //
  private function setError($errStr) {
    $this->error[]=$errStr;
    return false;
  }

  // -------------------------------------- //
  public function debug() {

    $nl = "\r\n" ;

    $out  = '--- File ---'. $nl;
    $out .= 'Error        : '. print_r($this->clF->getError(),true) . $nl;
    $out .= 'content-Name :'. $this->clF->getFilename() . $nl;
    $out .= 'content-size :'. $this->clF->contentLen() . $nl;
    $out .= '--- Reader ---'. $nl;
    $out .= 'Error        : '. print_r($this->error,true) . $nl;
    $out .= 'offset       :'. $this->offset . $nl;
    $out .= 'hidden-offset:'. $this->hiddenoffset . $nl;
    $out .= 'blocklen     :'. $this->blocklen . $nl;
    $out .= 'is eof       :'. ($this->m_eof?'true':'false') . $nl;

    return $out;
  }



}


?>