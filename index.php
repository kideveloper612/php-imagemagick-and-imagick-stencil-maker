<?php   
  
// header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
// header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");   // Date in the past

$masterStencilMakerPath = "https://billrosener.com/photo-stencil-maker/";
$target_dir = "tmp/";
$maxUploadSize = 9000000;
$maxImageSize = 800;
$threshold = "15";    // Contrast
$kernelRadius = 1;    // Density

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  if (!empty($_POST["relativeUploadedFile"])) {
    $relativeUploadedFile = test_input($_POST["relativeUploadedFile"]);
  } 

  if (!empty($_POST["relativeStencil"])) {
    $relativeStencil = test_input($_POST["relativeStencil"]);
  } 

  if (!empty($_POST["threshold"])) {
    $threshold = test_input($_POST["threshold"]);
  } 

  if (!empty($_POST["kernelRadius"])) {
    $kernelRadius = test_input($_POST["kernelRadius"]);
  } 


}



if (isset($_POST['upLoadFile'])) {

  $targetFileName = basename($_FILES["fileToUpload"]["name"]);
 
  // Remove all spaces and special characters.  Leave a-Z, dashes, and periods for extensions.
  $targetFileName = preg_replace("/[^a-zA-Z.-]+/", "", $targetFileName);
  $targetFileNameNoExt = pathinfo($targetFileName, PATHINFO_FILENAME);
  $myUniqid = uniqid ('', true);

  $relativeUploadedFile = $target_dir . $myUniqid . $targetFileName;            
  $absoluteUploadedFile = getcwd() . "/$relativeUploadedFile";

  $relativeStencil = $target_dir . $myUniqid . $targetFileNameNoExt . "-STENCIL.jpg";    
  $absoluteStencil = getcwd() . "/$relativeStencil";

  // echo "relativeUploadedFile: $relativeUploadedFile <br>";
  // echo "absoluteUploadedFile: $absoluteUploadedFile <br>";
  // echo "relativeStencil: $relativeStencil <br>";
  // echo "absoluteStencil: $absoluteStencil <br>";

  $imageFileType = strtolower(pathinfo($targetFileName, PATHINFO_EXTENSION));
  $uploadOk = 1;


  // Check if image file is a actual image or fake image
  if(isset($_POST["submit"])) {
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if($check !== false) {
        echo "File is an image - " . $check["mime"] . ".";
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }
  }

  // Check if file already exists
  if (file_exists($relativeUploadedFile)) {
    echo "Sorry, file already exists.";
    $uploadOk = 0;
  }

  // Check file size
  if ($_FILES["fileToUpload"]["size"] > $maxUploadSize) {
    echo "Sorry, your file is too large.";
    $uploadOk = 0;
  }

  // Allow certain file formats
  if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && 
      $imageFileType != "gif" && $imageFileType != "svg") {
    echo "Sorry, only JPG, JPEG, PNG, GIF, and SVG files are allowed.";
    $uploadOk = 0;
  }

  // Check if $uploadOk is set to 0 by an error
  if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
  } else {
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $relativeUploadedFile)) {
        // echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
  }

 
  list($imgWidth, $imgHeight) = getimagesize($absoluteUploadedFile);

  // echo "imgWidth: $imgWidth <br>";
  // echo "imgHeight: $imgHeight <br>";

  if (($imgWidth >= $maxImageSize) or ($imgHeight >= $maxImageSize)) {
    // Resize original image making it smaller.
    $sizeStr = '-size '.$maxImageSize.'x'.$maxImageSize.' -resize '.$maxImageSize.'x'.$maxImageSize;
    exec("convert $absoluteUploadedFile $sizeStr $absoluteUploadedFile"); 
  }

}  // End upLoadFile Button.




if ($relativeStencil == "") {
  // Use example image provided.

  $targetFileNameNoExt = "flower";

  $relativeUploadedFile = "examples/flower.jpg";            
  $absoluteUploadedFile = getcwd() . "/$relativeUploadedFile";

  $relativeStencil = $target_dir . time() . $targetFileNameNoExt . "-STENCIL.jpg";    
  $absoluteStencil = getcwd() . "/$relativeStencil";

  // echo "Relative Stencil is blank <br>";
  // echo "relativeUploadedFile: $relativeUploadedFile <br>";
  // echo "absoluteUploadedFile: $absoluteUploadedFile <br>";
  // echo "relativeStencil: $relativeStencil <br>";
  // echo "absoluteStencil: $absoluteStencil <br>";
}


/***  Begin Creating Stencil ******/ 

$absoluteUploadedFile = getcwd() . "/$relativeUploadedFile";
$absoluteStencil = getcwd() . "/$relativeStencil";

// echo "relativeUploadedFile: $relativeUploadedFile <br>";
// echo "absoluteUploadedFile: $absoluteUploadedFile <br>";
// echo "relativeStencil: $relativeStencil <br>";
// echo "absoluteStencil: $absoluteStencil <br>";


$negateStr = "-negate";
$basicStr = '-alpha off -gravity center -bordercolor white -border 3';
$morphologyStr = '-morphology edgeout diamond:' . $kernelRadius;
$thresholdStr = "-threshold $threshold" . "%";
$compressStr = '-sampling-factor 4:2:0 -strip -quality 85 -depth 4';

$cmd = "$morphologyStr -shave 5x5 $thresholdStr $basicStr $negateStr $compressStr";
exec("convert $absoluteUploadedFile $cmd $absoluteStencil");

if (isset($_POST['testAction'])) {
  $object->relativeUploadedFile=$relativeUploadedFile;
  $object->relativeStencil=$relativeStencil;
  $object->threshold=$threshold;
  $object->kernelRadius=$kernelRadius;

  echo(json_encode($object));
  exit(); 
}
/***  End  Creating Stencil ******/ 


?>


<title> Photo Stencil Maker </title>


<style>

h1 {
  color: #8b4513;
  font-size:1.5em;
}

h2 {
  color: #8b4513;
  font-size: 1.2em;
  font-weight: normal;
}


.slidecontainer {
  width: 100%;
}
        
.slider {
  -webkit-appearance: none;
  width: 100%;
  height: 10px;
  border-radius: 5px;
  background: #d3d3d3;
  outline: none;
  opacity: 0.7;
  -webkit-transition: .2s;
  transition: opacity .2s;
}
        
.slider:hover {
  opacity: 1;
}
        
.slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 10px;
  height: 20px;
  border-radius: 5%;
  background: #4CAF50;
  cursor: pointer;
}
        
.slider::-moz-range-thumb {
  width: 10px;
  height: 20px;
  border-radius: 5%;
  background: #4CAF50;
  cursor: pointer;
}
        
        
.button {
  width: 100%;
  height: auto;
  font-size: 1.4em;
}
        
form { 
  display:inline;
}


a.donate { text-decoration: none; 
    border-radius: 10px;
    background-color: #008ddf;
    color: white;
    padding-left: 10px;
    padding-right: 10px;
    padding-top: 5px;
    padding-bottom: 5px;
   }

a.donate:hover { 
	background-color: #0870ac;
}


.loader1 {
  text-align: center;
  width: 180px;
}

.loader2 {
  text-align: center;
  width: 180px;
}

</style>


<h1>Photo Stencil Maker</h1>
<p style="margin:0 0 10px 0;">Automatically trace photos and pictures into a stencil, pattern, line drawing, or sketch.

Add more text here - trace photos and pictures into a stencil, pattern, line drawing, or sketch.

</p>



<!-- 
Initially "Upload Image" is disabled.  When a file 
has been selected, this code makes this button enabled. 
-->

<script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
<script>
$(document).ready(
 function(){
   $('input:file').change(
      function(){
        if ($(this).val()) {
          $('input:submit').removeAttr('disabled'); 
        } 
      }
   );
});
</script>



<div style="width:330px; margin:1px 1px 1px 1px; float:left;">


<form name="stencilMaker" id="stencilMaker" method="post" enctype="multipart/form-data" 
   action="<?php echo htmlspecialchars($masterStencilMakerPath);?>">  

<h2> Step 1:  Upload Image. </h2>
 <br>

  <div style="background-color:lightskyblue; display: inline-block; padding: 5px; margin:1px 1px 1px 1px;">
  <input type="file" name="fileToUpload" id="fileToUpload" style="width:220px;"> 
  <input type="submit" value="Upload" name="upLoadFile" disabled onclick="displayLoader()">
  </div>

  <input type="hidden" name="relativeUploadedFile" value="<?php echo $relativeUploadedFile;?>">
  <input type="hidden" name="relativeStencil" value="<?php echo $relativeStencil;?>">
  
  <p style="" class="loader1" id="loader1">
  </p>

  </div>




  <div style="width:200px; margin: 1px 15px 1px 1px; float:left;">
  <?php
  echo "<img src=\"$relativeUploadedFile\" id=\"relativeUploadedFile\" alt=\"uploaded\" style=\"max-height:130px; max-width:180px;\"> ";
  ?>
  </div>


  <div style="margin:1px 1px 1px 1px; float:left;">
<h2> Step 2: Format Stencil </h2><br>

<!-- Consider adding another option here.  Negate should always be on.
<font style="display: inline-block; padding: 0px 5px 0px 5px; margin:1px 1px 1px 1px;"> Invert Colors:</font>
  <input type="radio" name="negate" class="negate" value="yes" onclick="reloadForm()" 
  <?php if (isset($negate) && $negate=="yes") echo "checked";?> value="yes"> Yes
  &nbsp;
  <input type="radio" name="negate" class="negate" value="no" onclick="reloadForm()"
  <?php if (isset($negate) && $negate=="no") echo "checked";?> value="no"> No
<br />
-->
 
 <font style="display:inline-block; padding: 0px 0px 0px 5px; margin:0px 1px 1px 1px;">Contrast:</font>
 <input style="width:150px;" type="range" class="slider" onchange="reloadForm()"
    class="threshold" name="threshold" min="1" max="50" value="<?php echo $threshold;?>">


<br />

  <font style="display: inline-block; padding: 0px 5px 0px 5px; margin:5px 1px 1px 1px;">Density:</font>
    <input style="width:150px;" type="range" class="slider" onchange="reloadForm()"
    class="kernelRadius" name="kernelRadius" min="1" max="10" value="<?php echo $kernelRadius;?>">

<br />

  <p class="loader2" id="loader2">
  </p>

</div>

<div style="clear:both;"></div>



<div style="width:350px; padding-bottom:20px; float:left;">
<br>
<?php
  // header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
  // header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
  echo "<img src=\"$relativeStencil\" alt=\"uploaded\" id=\"relativeStencil\" style=\"max-height:350px; max-width:350px;\" > ";
?>

</div>



<br>

<div style="clear:both">&nbsp;</div>


<br>


<div id="primary">

<h2>Step 3: Download Stencil</h2>

<p style="margin:15px;">

<?php
echo "<a class=\"donate\" href=\"$relativeStencil\" download=\"download-stencil\">Download</a>";
?>

</p>

<br>
<br>


</form>




<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script>

  function reloadForm () {
    var obj = {};
    document.getElementById('loader2').style.backgroundColor = "lightskyblue"; 
    document.getElementById("loader2").innerHTML = "Working ...";
    obj['testAction'] = 1;
    obj['relativeUploadedFile'] = $('input[name=relativeUploadedFile]').val();
    obj['relativeStencil'] = $('input[name=relativeStencil]').val();
    obj['threshold'] = $('input[name=threshold]').val();
    obj['kernelRadius'] = $('input[name=kernelRadius]').val();

    $.ajax({
      url:<?php echo('"'.$masterStencilMakerPath.'"');?>,
      method: "post",
      data: obj,
      success: function(res){
        var json = JSON.parse(res);
        let timestamp = Date.now();
        $("#relativeUploadedFile").attr("src", json.relativeUploadedFile);
        $("#relativeStencil").attr("src", json.relativeStencil + '?update=' + timestamp);
        $('#loader2').css("background", "");
        $('#loader2').html("");
      },
      error: function(err){
        alert("Failed!", err);
        window.location.reload();
      }
    })
  }

  function displayLoader () {
    document.getElementById('loader1').style.backgroundColor = "#4CAF50"; 
    document.getElementById("loader1").innerHTML = "Working ...";
  }

</script>






</div>








</body>

</html>