<?PHP

$t='';
if (isset($_GET['type'])) $t = $_GET['type'];

$x=5;
if (isset($_GET['x'])) $x = ($_GET['x']+0);
if ($x<0) $x=0;

$p=0;
if (isset($_GET['p'])) $p = ($_GET['p']+0);


include('clTerminalBounds.php');


$TB = new clTerminalBounds();


$im = $TB->getTerminalBoundsIm($x,$p);

if ($t=='t')
{
  echo 'Terminal: '. $x .' ';
  echo '<a href="_TerminalBoundsTest.php?type=t&x='. ($x-1) .'">prev</a> ';
  echo '<a href="_TerminalBoundsTest.php?type=t&x='. ($x+1) .'">next</a><br />';

  echo 'Permutation: '. $p .' ';
  echo '<a href="_TerminalBoundsTest.php?p='. ($p+1). '&type=t&x='. ($x) .'">++</a> ';
  echo '<a href="_TerminalBoundsTest.php?p='. ($p-1). '&type=t&x='. ($x) .'">--</a><br />';



  echo '<img src="_TerminalBoundsTest.php?p='. $p .'&x='. $x .'" />';

  echo '<pre>';
  print_r($TB->getTerminalBounds($x,$p));
  echo '</pre>';


}
else
{
  header('Content-type: image/png');
  imagepng($im);
  imagedestroy($im);
}



?>