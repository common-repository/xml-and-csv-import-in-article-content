<?php
if(isset($_GET['lang']))
    $lang=$_GET['lang'];
elseif(isset($_POST['lang']))
    $lang=$_POST['lang'];
else
    $lang="en_GB";
require_once("importCSV-".$lang.".php");
include_once("xmlToArray.class.php");
function do_offset($level){
    $offset = "";             // offset for subarry 
    for ($i=1; $i<$level;$i++){
    $offset = $offset . "<td></td>";
    }
    return $offset;
}
function autoMakeLink($text){
  $text = ereg_replace("((www.)([a-zA-Z0-9@:%_.~#-\?&]+[a-zA-Z0-9@:%_~#\?&/]))","http://\\1", $text);
  $text = ereg_replace("((ftp://|http://|https://){2})([a-zA-Z0-9@:%_.~#-\?&]+[a-zA-Z0-9@:%_~#\?&/])", "http://\\3", $text);
  $text = ereg_replace("(((ftp://|http://|https://){1})[a-zA-Z0-9@:%_.~#-\?&]+[a-zA-Z0-9@:%_~#\?&/])", "<A HREF=\"\\1\" TARGET=\"_blank\">\\1</A>", $text);
  $text = ereg_replace("([_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,3})","<A HREF=\"mailto:\\1\">\\1</A>", $text);
  return $text;
}

function show_array($array, $level, $sub,$code){
    if (is_array($array) == 1){          // check if input is an array
       foreach($array as $key_val => $value) {
           $offset = "";
           if (is_array($value) == 1){   // array is multidimensional
           $code.="<tr>";
           $offset = do_offset($level);
           $code.=$offset;
           $code=show_array($value, $level+1, 1,$code);
           }
           else{                        // (sub)array is not multidim
           if ($sub != 1){          // first entry for subarray
               $code.="<tr nosub>\n";
               $offset = do_offset($level);
           }
           $sub = 0;
           $code.=$offset . "<td main ".$sub." width=\"120\">" . $key_val . 
               "</td><td width=\"120\">" . $value . "</td>\n"; 
           $code.="</tr>\n";
           }
       } //foreach $array
    }  
    return $code;
}

function html_show_array($array){
	if(!empty($_POST['taille']))
				$code="<table width=\"".$_POST['taille']."\" border='0'>";
			else
				$code="<table border='0'>";  
	$code.=show_array($array, 1, 0,"");
    $code.="</table>\n";
	return $code;
}
function affCSVXML($contenu,$type,$autodetect="n",$params)
{
    global $timportCSV;
    $tab=array();
    $allowedExt = array("csv","xml");
    echo $contenu."<hr />";
    if(in_array($type,array("csv")))
    {
		if($params['EOL_DELIMITER']=='\n' ||$params['EOL_DELIMITER']=="/n")
            $res=explode("\n",$contenu);
        else
            $res=explode($params['EOL_DELIMITER'],$contenu);
		for($i=0;$i<sizeof($res);$i++)
		{
			$res2=explode($params['CELL_DELIMITER'],$res[$i]);
			foreach($res2 as $r)
				$tab[$i][]=str_replace($params['TEXT_DELIMITER'],'',$r);
		}
		if(!empty($_POST['taille']))
			$code="<table width=\"".$_POST['taille']."\">";
		else
			$code="<table>";
        print_r($tab);
        for($i=0;$i<sizeof($tab);$i++)
		{
			if($i==0&&$_POST['entete']=="o")
			{
				$code.="<tr>";
				foreach($tab[$i] as $t)
				{
					// $code.="<th>".str_replace(array('"',"'"),"\'",$t)."</th>";
					// correction proposé par lmconseils
					$code.="<th>".utf8_encode(str_replace(array('"',"'"),"\'",$t))."</th>";
				}	
				$code.="</tr>";
			}
			else
			{
				$code.="<tr>";
				foreach($tab[$i] as $t)
				{
					//$code.="<td>".str_replace(array('"',"'"),"\'",$t)."</td>";
					// correction proposé par lmconseils
					$code.="<td>".utf8_encode(str_replace(array('"',"'"),"\'",$t))."</td>";
				}	
				$code.="</tr>";
			}
		}
		$code.="</table>";
	}
	else
	{
		$xml2a = new XMLToArray();
		$contenuXML=$xml2a->parse($contenu); 
		$code=html_show_array($contenuXML);    
        print_r($contenuXML);

	}
	if($autodetect=="o")
	{
		$code=autoMakeLink($code);
	}
	echo "<br><div id=\"codeCSV1\" style=\"display:none\">$code</div><center></center><button onclick=\"window.parent.tinyMCE.getInstanceById('content').getBody().innerHTML+=document.getElementById('codeCSV1').innerHTML;\">".$timportCSV['INSERT_CONTENT']."</button> <a href=\"\">".$timportCSV['Back']."</a></center>";
}
function readFileContent($filename)
{
	$fp=fopen($filename,"r");
    $content="";
    while(!feof($fp))
    	$content.=fread($fp,1024);
    fclose($fp);
	return $content;
}
function findExt($filename)
{
	return end(explode('.',$filename));
}
if( isset($_POST['submit']) ) // si formulaire soumis
{
    echo "del ==>".$_POST['EOL_DELIMITER']." <==";
    $params=array('EOL_DELIMITER'=>str_replace('\n','/n',$_POST['EOL_DELIMITER']),'CELL_DELIMITER'=>str_replace('\n','/n',$_POST['CELL_DELIMITER']),'TEXT_DELIMITER'=>str_replace('\n','/n',$_POST['TEXT_DELIMITER']));
    if($_POST['type']=="file")
	{
	$content_dir=dirname(__FILE__)."/tmp/";
    $tmp_file = $_FILES['fichier']['tmp_name'];

    if( !is_uploaded_file($tmp_file) )
    {
        exit('ERROR_FILE1');
    }

    // on vérifie maintenant l'extension
    $type_file = $_FILES['fichier']['type'];
    $name_file = $_FILES['fichier']['name'];
    $allowedExt = array("csv","xml");

    if(!in_array(end(explode(".", $name_file)),$allowedExt))
    {
        exit('ERROR_FILE2'." <a href=\"\">".$timportCSV['Back']."</a>");
    }

    // on copie le fichier dans le dossier de destination
    $name_file = $_FILES['fichier']['name'];

    if( !move_uploaded_file($tmp_file,$content_dir . $name_file) )
    {
        exit('ERROR_FILE3'." $content_dir <a href=\"\">".$timportCSV['Back']."</a>");
    }
	echo "<br><br><br>".$timportCSV['FILE_SUCCESS'];
	$filename=$content_dir.$name_file;
	affCSVXML(readFileContent($filename),findExt($filename),$_POST['autodetect'],$params);
	}
	elseif($_POST['type']=="url")
		affCSVXML(str_replace('"','',file_get_contents($_POST['fichier'])),findExt($_POST['fichier']),$_POST['autodetect'],$params);
	else
		affCSVXML($_POST['fichier'],$_POST['typeFile'],$_POST['autodetect'],$params);
    unlink($filename);

}
else
{
	?>
<script language="javascript" type="application/javascript">
function view(ID)
{
	document.getElementById('tab1').style.display='none';
	document.getElementById('tab2').style.display='none';
	document.getElementById('tab3').style.display='none';
	document.getElementById(ID).style.display='block';
}
</script>
<style>
html,body
{
    margin:0;
    padding:0;
}
body,tr,td,input
{
    font-family:"Lucida Grande",Verdana,Arial,"Bitstream Vera Sans",sans-serif;
    font-size:12px;
    color:#333333;
}
.buttonCSV
{
	margin-left:5px;
	margin-right:5px;
	padding:5px;
	text-decoration:none;
}
a .buttonCSV,a
{
    text-decoration:none;
    color:#21759B;
}
h1
{
    color:#5A5A5A;
    font-family:Georgia,"Times New Roman",Times,serif;
    font-size:1.6em;
    font-weight:normal;
}
h4
{
    font-size:11px;
    color:black;
}
</style>
<div style="clear:both">
<ul style="list-style-type:none;clear:both">
<li style="float:left"><a href="#" onclick="view('tab1');return false;"><div class="buttonCSV"><?php echo $timportCSV['UPLOAD_CSV']?></div></a></li>
<li style="float:left"><a href="#" onclick="view('tab2');return false;"><div class="buttonCSV"><?php echo $timportCSV['FROMURL_CSV']?></div></a></li>
<li style="float:left"><a href="#" onclick="view('tab3');return false;"><div class="buttonCSV"><?php echo $timportCSV['PASTE_CSV']?></div></a></li>
</ul>
</div>
<div style="clear:both"></div>
<div id="tab1">
<h1><?php echo $timportCSV['UPLOAD_CSV']?></h1>
<form action="" method="POST" enctype="multipart/form-data">
<input type="hidden" name="lang" value="<?php echo $lang?>" />
<input type="hidden" name="type" value="file" />
  <table width="100%">
    <tr>
      <td><?php echo $timportCSV['CSV_FILE']?> :</td>
      <td><input type="file" name="fichier" /></td>
    </tr>
    <tr>
      <td><?php echo $timportCSV['TABLE_WIDTH']?> :</td>
      <td><input type="text" name="taille" value="100%" /></td>
    </tr>
    <tr>
      <td><?php echo $timportCSV['DISPLAY_COLS']?> :</td>
      <td><label for="enteteOui"><?php echo $timportCSV['YES']?></label>
        <input type="radio" value="o" name="entete" id="enteteOui" />
        <label for="enteteNon"><?php echo $timportCSV['NO']?></label>
        <input type="radio" value="n" name="entete" id="enteteNon" checked="checked" /></td>
    </tr>
    <tr>
      <td><?php echo $timportCSV['AUTODETECT']?> :</td>
      <td><label for="autoOui"><?php echo $timportCSV['YES']?></label>
        <input type="radio" value="o" name="autodetect" id="autoOui" />
        <label for="autoNon"><?php echo $timportCSV['NO']?></label>
        <input type="radio" value="n" name="autodetect" id="autoNon" checked="checked" /></td>
    </tr>
    <tr>
        <td colspan="2">
        <h4><?php echo $timportCSV['DELIMITERS']?></h4>
            <table width="100%">
                <tr>
                    <td><?php echo $timportCSV['CELL_DELIMITER']?></td><td><?php echo $timportCSV['TEXT_DELIMITER']?></td><td><?php echo $timportCSV['EOL_DELIMITER']?></td>
                </tr>
                <tr>
                    <td><input type="text" name="CELL_DELIMITER" value=";" style="width:15px;" /></td>
                    <td><input type="text" name="TEXT_DELIMITER" value="" style="width:15px;" /></td>
                    <td><input type="text" name="EOL_DELIMITER" value="\n" style="width:15px;" /></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
      <td colspan="2" align="center"><input type="submit" name="submit" /></td>
    </tr>
  </table>
  <input type="hidden" name="baseSite" value="<?php echo "/".str_replace('//','/',$baseSite)?>" />
</form>
</div>
<div id="tab2" style="display:none">
<h1><?php echo $timportCSV['FROMURL_CSV']?></h1>
<form action="" method="POST" enctype="multipart/form-data">
<input type="hidden" name="lang" value="<?php echo $lang?>" />
<input type="hidden" name="type" value="url" />
  <table width="100%">
    <tr>
      <td><?php echo $timportCSV['URL']?> :</td>
      <td><input type="text" name="fichier" /></td>
    </tr>
    <tr>
      <td><?php echo $timportCSV['TABLE_WIDTH']?> :</td>
      <td><input type="text" name="taille" value="100%" /></td>
    </tr>
    <tr>
      <td><?php echo $timportCSV['DISPLAY_COLS']?> :</td>
      <td><label for="enteteOui"><?php echo $timportCSV['YES']?></label>
        <input type="radio" value="o" name="entete" id="enteteOui" />
        <label for="enteteNon"><?php echo $timportCSV['NO']?></label>
        <input type="radio" value="n" name="entete" id="enteteNon" checked="checked" /></td>
    </tr>
    <tr>
      <td><?php echo $timportCSV['AUTODETECT']?> :</td>
      <td><label for="autoOui"><?php echo $timportCSV['YES']?></label>
        <input type="radio" value="o" name="autodetect" id="autoOui" />
        <label for="autoNon"><?php echo $timportCSV['NO']?></label>
        <input type="radio" value="n" name="autodetect" id="autoNon" checked="checked" /></td>
    </tr>
    <tr>
        <td colspan="2">
        <h4><?php echo $timportCSV['DELIMITERS']?></h4>
            <table width="100%">
                <tr>
                    <td><?php echo $timportCSV['CELL_DELIMITER']?></td><td><?php echo $timportCSV['TEXT_DELIMITER']?></td><td><?php echo $timportCSV['EOL_DELIMITER']?></td>
                </tr>
                <tr>
                    <td><input type="text" name="CELL_DELIMITER" value=";" style="width:15px;" /></td>
                    <td><input type="text" name="TEXT_DELIMITER" value="" style="width:15px;" /></td>
                    <td><input type="text" name="EOL_DELIMITER" value="\n" style="width:15px;" /></td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
      <td colspan="2" align="center"><input type="submit" name="submit" /></td>
    </tr>
  </table>
  <input type="hidden" name="baseSite" value="<?php echo "/".str_replace('//','/',$baseSite)?>" />
</form>
</div>
<div id="tab3" style="display:none">
<h1><?php echo $timportCSV['PASTE_CSV']?></h1>
<form action="" method="POST" enctype="multipart/form-data">
<input type="hidden" name="lang" value="<?php echo $lang?>" />
<input type="hidden" name="type" value="paste" />
  <table width="100%">
    <tr>
      <td><?php echo $timportCSV['CSV_FILE']?> :</td>
      <td><textarea name="fichier"></textarea></td>
    </tr>
    <tr>
      <td><?php echo $timportCSV['File_Type']?> :</td>
      <td><label for="typeOui"><?php echo $timportCSV['XML']?></label>
        <input type="radio" value="xml" name="typeFile" id="typeOui" />
        <label for="typeNon"><?php echo $timportCSV['CSV']?></label>
        <input type="radio" value="csv" name="typeFile" id="typeNon" checked="checked" /></td>
    </tr>
    <tr>
      <td><?php echo $timportCSV['TABLE_WIDTH']?> :</td>
      <td><input type="text" name="taille" value="100%" /></td>
    </tr>
    <tr>
      <td><?php echo $timportCSV['DISPLAY_COLS']?> :</td>
      <td><label for="enteteOui"><?php echo $timportCSV['YES']?></label>
        <input type="radio" value="o" name="entete" id="enteteOui" />
        <label for="enteteNon"><?php echo $timportCSV['NO']?></label>
        <input type="radio" value="n" name="entete" id="enteteNon" checked="checked" /></td>
    </tr>
    <tr>
      <td><?php echo $timportCSV['AUTODETECT']?> :</td>
      <td><label for="autoOui"><?php echo $timportCSV['YES']?></label>
        <input type="radio" value="o" name="autodetect" id="autoOui" />
        <label for="autoNon"><?php echo $timportCSV['NO']?></label>
        <input type="radio" value="n" name="autodetect" id="autoNon" checked="checked" /></td>
    </tr>
    <tr>
        <td colspan="2">
        <h4><?php echo $timportCSV['DELIMITERS']?></h4>
            <table width="100%">
                <tr>
                    <td><?php echo $timportCSV['CELL_DELIMITER']?></td><td><?php echo $timportCSV['TEXT_DELIMITER']?></td><td><?php echo $timportCSV['EOL_DELIMITER']?></td>
                </tr>
                <tr>
                    <td><input type="text" name="CELL_DELIMITER" value=";" style="width:15px;" /></td>
                    <td><input type="text" name="TEXT_DELIMITER" value="" style="width:15px;" /></td>
                    <td><input type="text" name="EOL_DELIMITER" value="\n" style="width:15px;" /></td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
      <td colspan="2" align="center"><input type="submit" name="submit" /></td>
    </tr>
  </table>
  <input type="hidden" name="baseSite" value="<?php echo "/".str_replace('//','/',$baseSite)?>" />
</form>
</div>
<p align="center" style="font-style:italic;color:#666666;line-height:140%;">More information <a href="http://www.erreurs404.net/labels/importcsv.html?piwik_campaign=ImportCSV_Wordpress">http://www.erreurs404.net/labels/importcsv.html</a></p>
<?php	
}
?>
