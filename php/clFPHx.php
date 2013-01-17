<?PHP

include_once('clObjFile.php');



class clFPHx extends clObjFile {

  private $m_lv;

  // -------------------------------------- //
  function __construct($lv) {
    $this->m_lv = $lv;

 
    if ($lv->BlockNameExists('FPHb')) {

      $reader = $lv->getBlockContent('FPHb', true);
      parent::__construct($reader, self::FILEVERSION_B);

    } else {

      $reader = $lv->getBlockContent('FPHc', true);
      parent::__construct($reader, self::FILEVERSION_C);
    }


  }


}

?>