<?PHP

include_once('clError.php');

class clIcon {

  private $m_lv;
  private $m_error; //- clError
  private $m_img;

  // -------------------------------------- //
  Public Function getError()
  {
    return $this->m_error;
  }

  // -------------------------------------- //
  function __construct($lv)
  {
    $this->m_lv = $lv;
    $this->m_error = new clError('clICON');
    $this->m_img = array();

    if ($this->m_lv->BlockNameExists('icl8'))
    {
      $tmp = new stdClass;
      $tmp->blocName='icl8';
      $tmp->width = 32;
      $tmp->height = 32;
      $tmp->bitPerPixel = 8;
      $tmp->usePalette = true;

      $this->m_img[] = $tmp;
    }

    if ($this->m_lv->BlockNameExists('icl4'))
    {
      $tmp = new stdClass;
      $tmp->blocName='icl4';
      $tmp->width = 32;
      $tmp->height = 32;
      $tmp->bitPerPixel = 4;
      $tmp->usePalette = true;

      $this->m_img[] = $tmp;
    }



    if ($this->m_lv->BlockNameExists('ICON'))
    {
      $tmp = new stdClass;
      $tmp->blocName='ICON';
      $tmp->width = 32;
      $tmp->height = 32;
      $tmp->bitPerPixel = 1;
      $tmp->usePalette = false;

      $this->m_img[] = $tmp;
    }

  }

  // -------------------------------------- //
  public function getIconCount()
  {
    return count($this->m_img);
  }

  // -------------------------------------- //
  public function getIconIndex($maxBitPerPixel=32, $fallback=true)
  {
    $c = count($this->m_img);

    $best_index = -1;
    $best_bpp = 0;

    foreach($this->m_img as $index=>$icon)
    {
      if (($icon->bitPerPixel<$maxBitPerPixel) && ($icon->bitPerPixel>$best_bpp))
      {
	if (($fallback) || ($icon->bitPerPixel==$maxBitPerPixel))
	{
	  $best_bpp=$icon->bitPerPixel;
	  $best_index=$index;
	}
      }
    }

    return $best_index;
  }


  // -------------------------------------- //
  private function getImagePalette(& $im, & $colorArray)
  {
    $color=array();

    for ($i=0;$i<count($colorArray);$i++)
    {
      $c = $colorArray[$i];

      $r =  ($c & 0xFF0000)>>16;
      $g =  ($c & 0xFF00)>>8;
      $b =  ($c & 0xFF);

      $color[$i] = imagecolorallocate($im, $r, $g, $b);
    }
    return $color;
  }

  // -------------------------------------- //
  public function getIcon($IconIndex=0)
  {
    if (!isset($this->m_img[$IconIndex])) return $this->m_error->AddError('getIcon('. $IconIndex .') Icon-Index out of range!');

    if (!function_exists('ImageCreate'))  return $this->m_error->AddError('function_exists ImageCreate fail!');

    $icon = $this->m_img[$IconIndex];
    $im = imagecreatetruecolor($icon->width, $icon->height);




    $content = $this->m_lv->getBlockContent($icon->blocName, false);

    if (($icon->bitPerPixel==8) && ($icon->usePalette))
    {
      //-- Set color palette
      $color = $this->getImagePalette($im, self::$s_LABVIEW_COLOR_PALETTE_256);

      //- read Image
      $y=0;
      $x=0;
      while ($y<$icon->height)
      {
	$index = $content->ReadInt(1);

	imagesetpixel($im, $x, $y, $color[$index] );

	$x++;
	if ($x>=$icon->width)
	{
	  $x=0;
	  $y++;
	}
      }
    }


    if (($icon->bitPerPixel==4) && ($icon->usePalette))
    {
      //-- Set color palette
      $color = $this->getImagePalette($im, self::$s_LABVIEW_COLOR_PALETTE_16);

      //- read Image
      $y=0;
      $x=0;
      $index=0;
      $useBuffer=false;

      while ($y<$icon->height)
      {
	if (!$useBuffer)
	{
	  $index = $content->ReadInt(1);
	}
	else
	{
	  $index = $index << 4;
	}

	$useBuffer=!$useBuffer;

	imagesetpixel($im, $x, $y, $color[($index>>4) & 0x0F] );

	$x++;
	if ($x>=$icon->width)
	{
	  $x=0;
	  $y++;
	}
      }
    }




    if ($icon->bitPerPixel==1)
    {
      $c_0 = imagecolorallocate($im, 0, 0, 0);
      $c_1 = imagecolorallocate($im, 255, 255, 255);

      //- read Image
      $y=0;
      $x=0;
      while ($y<$icon->height)
      {
	$byte = $content->ReadInt(1);

	for ($i=0; $i<8; $i++)
	{
	  imagesetpixel($im, $x, $y, (($byte & 128)?$c_0:$c_1)  );
	  $byte=$byte<<1;

	  $x++;
	  if ($x>=$icon->width)
	  {
	    $x=0;
	    $y++;
	    break; //- end of line => ignore following bits
	  }
	}
      }
    }

    
    return $im;
   
  }


  // -------------------------------------- //
  public function getIconHTML($IconIndex=0)
  {
    $image=$this->getIcon($IconIndex);

    if ($image===false) return '';

    ob_start();
    imagepng($image);
    $imageStr = ob_get_contents();
    ob_end_clean();

    imagedestroy($image);

    return 'data:image/png;base64,'. base64_encode($imageStr);
  }





  // -------------------------------------- //

  private static $s_LABVIEW_COLOR_PALETTE_256 = array(0xF1F1F1, 0xFFFFCC, 0xFFFF99, 0xFFFF66, 0xFFFF33, 0xFFFF00, 0xFFCCFF, 0xFFCCCC, 0xFFCC99, 0xFFCC66, 0xFFCC33, 0xFFCC00, 0xFF99FF, 0xFF99CC, 0xFF9999, 0xFF9966, 0xFF9933, 0xFF9900, 0xFF66FF, 0xFF66CC, 0xFF6699, 0xFF6666, 0xFF6633, 0xFF6600, 0xFF33FF, 0xFF33CC, 0xFF3399, 0xFF3366,
		0xFF3333, 0xFF3300, 0xFF00FF, 0xFF00CC, 0xFF0099, 0xFF0066, 0xFF0033, 0xFF0000, 0xCCFFFF, 0xCCFFCC, 0xCCFF99, 0xCCFF66, 0xCCFF33, 0xCCFF00, 0xCCCCFF, 0xCCCCCC, 0xCCCC99, 0xCCCC66, 0xCCCC33, 0xCCCC00, 0xCC99FF, 0xCC99CC, 0xCC9999, 0xCC9966, 0xCC9933, 0xCC9900, 0xCC66FF, 0xCC66CC, 0xCC6699, 0xCC6666, 0xCC6633, 0xCC6600,
		0xCC33FF, 0xCC33CC, 0xCC3399, 0xCC3366, 0xCC3333, 0xCC3300, 0xCC00FF, 0xCC00CC, 0xCC0099, 0xCC0066, 0xCC0033, 0xCC0000, 0x99FFFF, 0x99FFCC, 0x99FF99, 0x99FF66, 0x99FF33, 0x99FF00, 0x99CCFF, 0x99CCCC, 0x99CC99, 0x99CC66, 0x99CC33, 0x99CC00, 0x9999FF, 0x9999CC, 0x999999, 0x999966, 0x999933, 0x999900, 0x9966FF, 0x9966CC,
		0x996699, 0x996666, 0x996633, 0x996600, 0x9933FF, 0x9933CC, 0x993399, 0x993366, 0x993333, 0x993300, 0x9900FF, 0x9900CC, 0x990099, 0x990066, 0x990033, 0x990000, 0x66FFFF, 0x66FFCC, 0x66FF99, 0x66FF66, 0x66FF33, 0x66FF00, 0x66CCFF, 0x66CCCC, 0x66CC99, 0x66CC66, 0x66CC33, 0x66CC00, 0x6699FF, 0x6699CC, 0x669999, 0x669966,
		0x669933, 0x669900, 0x6666FF, 0x6666CC, 0x666699, 0x666666, 0x666633, 0x666600, 0x6633FF, 0x6633CC, 0x663399, 0x663366, 0x663333, 0x663300, 0x6600FF, 0x6600CC, 0x660099, 0x660066, 0x660033, 0x660000, 0x33FFFF, 0x33FFCC, 0x33FF99, 0x33FF66, 0x33FF33, 0x33FF00, 0x33CCFF, 0x33CCCC, 0x33CC99, 0x33CC66, 0x33CC33, 0x33CC00,
		0x3399FF, 0x3399CC, 0x339999, 0x339966, 0x339933, 0x339900, 0x3366FF, 0x3366CC, 0x336699, 0x336666, 0x336633, 0x336600, 0x3333FF, 0x3333CC, 0x333399, 0x333366, 0x333333, 0x333300, 0x3300FF, 0x3300CC, 0x330099, 0x330066, 0x330033, 0x330000, 0xFFFF, 0xFFCC, 0xFF99, 0xFF66, 0xFF33, 0xFF00, 0xCCFF, 0xCCCC, 0xCC99, 0xCC66,
		0xCC33, 0xCC00, 0x99FF, 0x99CC, 0x9999, 0x9966, 0x9933, 0x9900, 0x66FF, 0x66CC, 0x6699, 0x6666, 0x6633, 0x6600, 0x33FF, 0x33CC, 0x3399, 0x3366, 0x3333, 0x3300,	0xFF, 0xCC, 0x99, 0x66, 0x33, 0xEE0000, 0xDD0000, 0xBB0000, 0xAA0000, 0x880000, 0x770000, 0x550000, 0x440000, 0x220000, 0x110000, 0xEE00, 0xDD00, 0xBB00, 0xAA00,
		0x8800, 0x7700, 0x5500, 0x4400, 0x2200, 0x1100, 0xEE, 0xDD, 0xBB, 0xAA, 0x88, 0x77, 0x55, 0x44,	0x22, 0x11, 0xEEEEEE, 0xDDDDDD, 0xBBBBBB, 0xAAAAAA, 0x888888, 0x777777, 0x555555, 0x444444, 0x222222, 0x111111, 0x0);


  private static $s_LABVIEW_COLOR_PALETTE_16 = array (0xFFFFFF, 0xFFFF00, 0x000080, 0xFF0000, 0xFF00FF, 0x800080, 0x0000FF, 0x00FFFF, 0x00FF00, 0x008000, 0x800000, 0x808000, 0xC0C0C0, 0x808080,  0x008080, 0x000000);

  // -------------------------------------- //
  public function getXML()
  {
    $out = "<?xml version='1.0'?>\n";
    $out .=  "<!-- Filename='". htmlentities($this->m_lv->getFileName()) ."' -->\n\n";
    $out .=  "<ICON>\n";


    foreach($this->m_img as $index=>$icon)
    {
      $out .=  "  <img index='". $index ."' name='". $icon->blocName ."' width='". $icon->width ."' height='". $icon->height ."' bitsPerPixel='". $icon->bitPerPixel ."' /> \n";
    }


    $out .=  $this->m_error->getXML();

    $out .=  "</ICON>\n";

    return $out;
  }
}

?>