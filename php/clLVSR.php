<?PHP


class clLVSR {
  //- Information about the LVSR container
  private $m_LVSR=array();




  // -------------------------------------- //
  private function readLVSR() {

    $vers_ID = $this->getBlockIdByName('LVSR');
    $block = $this->getBlockContentById($vers_ID, 0, false);


    $out = array();

    $out['version'] = $this->getVersion($block->readInt(4));

    $out['INT1'] = $block->readInt(2); //- ??
    $out['flags'] = $block->readInt(2);

  
  
    $out['protected'] = (($out['flags'] & 0x2000) > 0);

    //- delete known flag-bits
    $out['flags'] = $out['flags'] & 0xDFFF;

    $this->m_LVSR = $out;
  }


}

?>