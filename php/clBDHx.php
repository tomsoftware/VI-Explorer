<?PHP

include_once('clObjFile.php');



class clBDHx extends clObjFile {

  private $m_lv;

  // -------------------------------------- //
  function __construct($lv) {
    $this->m_lv = $lv;

 
    if ($lv->BlockNameExists('BDHb')) {

      $reader = $lv->getBlockContent('BDHb', true);
      parent::__construct($reader, self::FILEVERSION_B);

    } else {

      $reader = $lv->getBlockContent('BDHc', true);
      parent::__construct($reader, self::FILEVERSION_C);
    }


  }


}

?>