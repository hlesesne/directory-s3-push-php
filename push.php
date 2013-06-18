<?
error_reporting(E_ERROR | E_PARSE);
require "S3.php";
date_default_timezone_set('UTC');

$date = filemtime(".");
$dateFormat = "Y/m/d h:i:s";
$rundate = date($dateFormat, time());
$keyTimeStamp = "mirrorconfig.timestamp";

$accessKey = '{AMAZON AWS KEY}';
$secretKey = '{AMAZON AWS SECRET SECRET}';
$bucketName = '{AMAZON S3 Bucket Name}';

$sourceDir = '{LOCAL source directory without trailing slash eg: /home/files}';
$cacheDuration = 3600 * 24 * 30;
$fileAcl = S3::ACL_PUBLIC_READ;

$s3 = new S3($accessKey, $secretKey);
$http_headers = array('Cache-Control' => 'max-age=' . $cacheDuration, 'Expires' => date('D, j M Y H:i:s \G\M\T', time() + $cacheDuration)); $meta_headers = array();


if (($lastrunObject = $s3::GetObject($bucketName, $keyTimeStamp)) !== false) {
	$lastrun = $lastrunObject->body;
} else {
	if ($result = $s3::PutObject($date, $bucketName, $keyTimeStamp, $fileAcl) !== false) {
		$lastrun = $date;
	}
}

$dir=$sourceDir;  // need this so that we have $souceDir when we recurse.

$comparedate=$lastrun; // set the date to start the comparison from to the last time it was run (or the directories last modified date).
$max_modified=$lastrun;

$filecount = 0; // pass a counter that tracks the total number of files updated.

// recurse the directory tree and upload new files.
directory_tree($dir,$comparedate,$max_modified,$sourceDir,$bucketName, $s3,$fileAcl,$meta_headers,$http_headers,$filecount);

// if there were files uploaded, update the timestamp of the newest file updated, for the next run to start with.
// also log that the run completed.
if ($filecount > 0) {
	$result = $s3::PutObject($max_modified, $bucketName, $keyTimeStamp, $fileAcl);
	echo "Processed: ".$filecount." file(s). Updated Max Modified Time to: ".$max_modified." at ".$rundate." UTC.\n"; }

// ref: http://php.net/manual/en/function.filemtime.php
function directory_tree($address,$comparedate,&$max_modified,$sourceDir,$bucketName, $s3,$fileAcl=S3::ACL_PUBLIC_READ,$meta_headers=null,$http_headers=null,&$filecount=0) { 
	@$dir = opendir($address); 
	if(!$dir){ return 0; } 
	while($entry = readdir($dir)){ 
        	if(is_dir("$address/$entry") && ($entry != ".." && $entry != ".")){                              
                	directory_tree("$address/$entry",$comparedate,$max_modified,$sourceDir,$bucketName, $s3,$fileAcl,$meta_headers,$http_headers,$filecount); 
                }  else   { 
			if($entry != ".." && $entry != ".") { 
				$fulldir=$address.'/'.$entry; 
                    		$last_modified = filemtime($fulldir); 
                       		if($comparedate < $last_modified)  { 
			  		if ($last_modified > $max_modified) {
						$max_modified = $last_modified;
					}
					$file = preg_replace('!'.preg_quote($sourceDir."/").'!','',$fulldir,1);	
					echo "Source directory: ".$sourceDir." Source file: ".$fulldir." Destination: ".$file." Modified: ".$last_modified." Last Run: ".$comparedate."... ";	
                			if ($s3->putObject($s3->inputFile($fulldir), $bucketName, $file, $fileAcl, $meta_headers, $http_headers)) {
                        			echo "OK\n";
						$filecount++;
                			} else {
                        			echo "ERROR\n";
                			}
/* Small helper routine to copy the file to a local directory for additional backup
 * 					try {
						$srcfile=$fulldir;
						$dstfile="/backups".$fulldir;
						$dirname = dirname($dstfile);
						if (!file_exists($dirname)) {
							mkdir($dirname, 0755, true);
						}
						copy($srcfile, $dstfile);
					} catch (Exception $e) {
						echo "Error moving: ".$fulldir." to /backups".$fulldir."\n";
					}
*/
				} 

                 	}
            	} 
      	}
}
?>
