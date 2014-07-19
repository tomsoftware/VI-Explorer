<?PHP

class clTerminalBounds
{

  private $m_TermBound; //- for compressed coordinates
  private $m_TermBoundValues; //- lookup to uncompress

  //-------------------------------------//
  public function getTerminalBounds($PatternNo, $Permutation=0)
  {
    $out=array();

    //- get rect-data
    if (!isset($this->m_TermBound[$PatternNo])) $PatternNo=0;
    $bs =  $this->m_TermBound[$PatternNo];


    foreach($bs as $b)
    {
      $tmp = array();

      //- split data in x,y,w,h
      $left=(($b>>0) & 0xF);
      $top=(($b>>4) & 0xF);
      $right=(($b>>8) & 0xF);
      $bottom=(($b>>12) & 0xF);

      //- "uncompress" data
      $left= $this->m_TermBoundValues[$left];
      $top=$this->m_TermBoundValues[$top];
      $right=$this->m_TermBoundValues[$right];
      $bottom=$this->m_TermBoundValues[$bottom];

      //- rotate
      $rot = $Permutation & 3;
      for ($i=0;$i<$rot;$i++)
      {
	$tmpVal=$left;
	$left=32-$top;
	$top=$right;
	$right=32-$bottom;
	$bottom=$tmpVal;
      }

      //- Flip Vertikal
      $flip = (($Permutation & 4)>0);
      
      if ($flip>0)
      {
	$left=$left;
	$top=32-$top;
	$right=$right;
	$bottom=32-$bottom;
      }

      //echo $rot .'+'. $flip.' - ';
      //- export
      $tmp['left']= $left;
      $tmp['top']=$top;
      $tmp['right']=$right;
      $tmp['bottom']=$bottom;


      $out[] = $tmp;
    }

    return $out;
  }


  //-------------------------------------//
  //- get GD-Image for terminal
  public function getTerminalBoundsIm($PatternNo, $Permutation=0)
  {
    $out=array();
    $terms = $this->getTerminalBounds($PatternNo, $Permutation);


    $im = imagecreatetruecolor(32,32);

    $white = imagecolorallocate($im,255,255,255);
    $black = imagecolorallocate($im,0,0,0);
    $red = imagecolorallocate($im,255,0,0);

    imagefill($im,0,0,$white);
    

    $count = count($terms);

    for($i=0; $i<$count; $i++)
    {
      $b = $terms[$i];

      if ($i==0) imagefilledrectangle($im, $b['left'], $b['top'], $b['right'], $b['bottom'], $red);
      imagerectangle($im, $b['left'], $b['top'], $b['right'], $b['bottom'], $black);
    }

    //- bug in Labview? coordinates are out of the 32x32 range
    imagerectangle($im, 0, 0, 31, 31, $black);

    return $im;
  }




  //-------------------------------------//
  function __construct()
  {
    $this->m_TermBoundValues=array(0,6,7,8,11,13,16,19,21,24,25,26,32);

    $this->m_TermBound=array();

    $this->m_TermBound[] = array(0xCC00);
    $this->m_TermBound[] = array(0xCC06,0xC600);
    $this->m_TermBound[] = array(0xCC06,0xC660,0x6600);
    $this->m_TermBound[] = array(0xCC80,0x8C40,0x4C00);
    $this->m_TermBound[] = array(0xCC06,0xC680,0x8640,0x4600);
    $this->m_TermBound[] = array(0xCC66,0x6C06,0xC660,0x6600);
    $this->m_TermBound[] = array(0xCC90,0x9C60,0x6C30,0x3C00);
    $this->m_TermBound[] = array(0xCC66,0x6C06,0xC680,0x8640,0x4600);
    $this->m_TermBound[] = array(0xCC06,0xC690,0x9660,0x6630,0x3600);
    $this->m_TermBound[] = array(0xCC66,0x6C06,0xC690,0x9660,0x6630,0x3600);
    $this->m_TermBound[] = array(0xCC86,0x8C46,0x4C06,0xC680,0x8640,0x4600);
    $this->m_TermBound[] = array(0xCC86,0x8C46,0x4C06,0xC690,0x9660,0x6630,0x3600);
    $this->m_TermBound[] = array(0xCC96,0x9C66,0x6C36,0x3C06,0xC690,0x9660,0x6630,0x3600);
    $this->m_TermBound[] = array(0xCC96,0x9C66,0x6C36,0x3C06,0xC603,0xC390,0x9360,0x6330,0x3300);
    $this->m_TermBound[] = array(0xCC96,0x9C66,0x6C36,0x3C06,0xC663,0x6603,0xC390,0x9360,0x6330,0x3300);
    $this->m_TermBound[] = array(0xCC99,0x9C69,0x6C39,0x3C09,0xC966,0x6906,0xC663,0x6603,0xC390,0x9360,0x6330,0x3300);
    $this->m_TermBound[] = array(0x4300,0x4603,0x8640,0xC380,0xC683,0xCC06);
    $this->m_TermBound[] = array(0x4300,0x4603,0x8640,0xC380,0xC683,0x6C06,0xCC66);
    $this->m_TermBound[] = array(0x4300,0x4603,0x8640,0xC380,0xC683,0x4C06,0x8C46,0xCC86);
    $this->m_TermBound[] = array(0x4300,0x4603,0x8640,0xC380,0xC683,0x3C06,0x6C36,0x9C66,0xCC96);
    $this->m_TermBound[] = array(0x4300,0x4603,0x8640,0xC380,0xC683,0x4906,0x4C09,0x8C46,0xC986,0xCC89);
    $this->m_TermBound[] = array(0x4300,0x4603,0x8640,0xC380,0xC683,0x6906,0x3C09,0x6C39,0xC966,0x9C69,0xCC99);
    $this->m_TermBound[] = array(0x3300,0x6603,0x6330,0x9360,0xC663,0xC390,0x4C06,0x8C46,0xCC86);
    $this->m_TermBound[] = array(0x3300,0x6330,0x9360,0xC390,0xC603,0xCC06);
    $this->m_TermBound[] = array(0x3300,0x6330,0x9360,0xC390,0xC603,0x6C06,0xCC66);
    $this->m_TermBound[] = array(0x3300,0x6330,0x9360,0xC390,0xC603,0x4C06,0x8C46,0xCC86);
    $this->m_TermBound[] = array(0xCC99,0x9C69,0x6C39,0x3C09,0xC906,0xC603,0xC390,0x9360,0x6330,0x3300);
    $this->m_TermBound[] = array(0xCC06,0xC663,0x6603,0xC390,0x9360,0x6330,0x3300);
    $this->m_TermBound[] = array(0xCC66,0x6C06,0xC663,0x6603,0xC390,0x9360,0x6330,0x3300);
    $this->m_TermBound[] = array(0xCC99,0x9C69,0x6C39,0x3C09,0xC906,0xC663,0x6603,0xC390,0x9360,0x6330,0x3300);
    $this->m_TermBound[] = array(0xCC08,0xC804,0xC460,0x6400);
    $this->m_TermBound[] = array(0xCC08,0xC804,0xC480,0x8440,0x4400);
    $this->m_TermBound[] = array(0xCC68,0x6C08,0xC804,0xC480,0x8440,0x4400);
    $this->m_TermBound[] = array(0x2200,0x6502,0x6705,0x6A07,0x2C0A,0x5220,0x5C2A,0x7250,0x7C5A,0xA270,0xAC7A,0xC2A0,0xC562,0xC765,0xCA67,0xCCAA);
    $this->m_TermBound[] = array(0x1100,0x4110,0x6140,0x8160,0xB180,0xC1B0,0x6401,0xC461,0x6604,0xC664,0x6806,0xC866,0x6B08,0xCB68,0x1C0B,0x4C1B,0x6C4B,0x8C6B,0xBC8B,0xCCBB);
  }

}


?>