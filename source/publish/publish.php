<?php
// Publish extension, https://github.com/datenstrom/yellow-extensions/tree/master/source/publish

class YellowPublish {
    const VERSION = "0.8.23";
    public $yellow;         // access to API
    public $extensions;     // number of extensions
    public $errors;         // number of errors

    // Handle initialisation
    public function onLoad($yellow) {
        $this->yellow = $yellow;
    }

    // Handle command
    public function onCommand($command, $text) {
        switch ($command) {
            case "publish": $statusCode = $this->processCommandPublish($command, $text); break;
            default:        $statusCode = 0;
        }
        return $statusCode;
    }

    // Handle command help
    public function onCommandHelp() {
        return "publish [directory]\n";
    }
    
    // Process command to publish extensions
    public function processCommandPublish($command, $text) {
        $statusCode = 0;
        list($path) = $this->yellow->toolbox->getTextArguments($text);
        $pathRepository = rtrim($this->yellow->system->get("updateExtensionDirectory"), "/")."/";
        $pathRepositoryOffical = $pathRepository."yellow-extensions/";
        $path = rtrim(empty($path) ? $pathRepositoryOffical : $pathRepository.$path, "/")."/";
        if (is_dir($pathRepository) && is_dir($pathRepositoryOffical) && is_dir($path)) {
            $this->extensions = $this->errors = 0;
            $statusCode = max($statusCode, $this->updateExtensionDirectory($path, $pathRepositoryOffical));
            $entries = $this->yellow->toolbox->getDirectoryEntriesRecursive($path, "/.*/", true, true);
            foreach ($entries as $entry) {
                echo "\rPublishing extension files ".$this->getProgressPercent($this->extensions, count($entries), 10, 95)."%... ";
                $statusCode = max($statusCode, $this->updateExtensionDirectory("$entry/", $pathRepositoryOffical));
            }
            echo "\rPublishing extension files 100%... done\n";
        } elseif (is_dir($pathRepository)) {
            $statusCode = 500;
            $this->extensions = 0;
            $this->errors = 1;
            $path = !is_dir($pathRepositoryOffical) ? $pathRepositoryOffical : $path;
            echo "ERROR updating files: Can't find directory '$path'!\n";
        } else {
            $statusCode = 500;
            $this->extensions = 0;
            $this->errors = 1;
            $fileName = $this->yellow->system->get("coreSettingDirectory").$this->yellow->system->get("coreSystemFile");
            echo "ERROR updating files: Please configure UpdateExtensionDirectory in file '$fileName'!\n";
        }
        echo "Yellow $command: $this->extensions extension".($this->extensions!=1 ? "s" : "");
        echo ", $this->errors error".($this->errors!=1 ? "s" : "")."\n";
        return $statusCode;
    }
    
    // Update extension directory
    public function updateExtensionDirectory($path, $pathRepositoryOffical) {
        $statusCode = 200;
        $fileNameExtension = $path.$this->yellow->system->get("updateExtensionFile");
        if (is_file($fileNameExtension)) {
            $statusCode = max($statusCode, $this->updateExtensionInformation($path));
            $statusCode = max($statusCode, $this->updateExtensionDocumentation($path));
            $statusCode = max($statusCode, $this->updateExtensionArchive($path, $pathRepositoryOffical));
            $statusCode = max($statusCode, $this->updateExtensionVersion($path, $pathRepositoryOffical));
            $statusCode = max($statusCode, $this->updateExtensionWaffle($path, $pathRepositoryOffical));
            if (defined("DEBUG") && DEBUG>=1) echo "YellowPublish::updateExtensionDirectory ".ucfirst($extension)." $version<br/>\n";
            ++$this->extensions;
            if ($statusCode!=200) ++$this->errors;
        }
        return $statusCode;
    }
    
    // Update extension information file
    public function updateExtensionInformation($path) {
        $statusCode = 200;
        list($extension, $version, $fileNameSource) = $this->getExtensionInformationFromSource($path);
        $fileNameExtension = $path.$this->yellow->system->get("updateExtensionFile");
        if (is_file($fileNameExtension) && !empty($extension) && !empty($version)) {
            $fileData = $this->yellow->toolbox->readFile($fileNameExtension);
            $fileDataNew = "";
            foreach ($this->yellow->toolbox->getTextLines($fileData) as $line) {
                if (preg_match("/^\s*(.*?)\s*:\s*(.*?)\s*$/", $line, $matches)) {
                    if (!empty($matches[1]) && !empty($matches[2]) && strposu($matches[1], "/")) {
                        list($dummy1, $entry, $dummy2) = $this->yellow->toolbox->getTextList($matches[2], ",", 3);
                        if (is_file($path.$entry)) {
                            $published = filemtime($path.$entry);
                            break;
                        }
                    }
                }
            }
            foreach ($this->yellow->toolbox->getTextLines($fileData) as $line) {
                if (preg_match("/^\s*(.*?)\s*:\s*(.*?)\s*$/", $line, $matches)) {
                    if (lcfirst($matches[1])=="extension") $line = "Extension: ".ucfirst($extension)."\n";
                    if (lcfirst($matches[1])=="version") $line = "Version: $version\n";
                    if (lcfirst($matches[1])=="published") $line = "Published: ".date("Y-m-d H:i:s", $published)."\n";
                    if (lcfirst($matches[1])=="status" && $matches[2]=="unpublished") $line = "";
                    if (!empty($matches[1]) && !empty($matches[2]) && strposu($matches[1], "/")) {
                        list($extensionResponsible, $entry, $flags) = $this->yellow->toolbox->getTextList($matches[2], ",", 3);
                        if (ucfirst($extensionResponsible)!=ucfirst($extension)) {
                            $extensionResponsible = ucfirst($extension);
                            $line = "$matches[1]: $extensionResponsible,$entry,$flags\n";
                        }
                        $fileNameDestination = $matches[1];
                        if (!$this->yellow->lookup->isValidFile($this->yellow->toolbox->normaliseTokens($fileNameDestination))) {
                            $statusCode = 500;
                            echo "ERROR checking files: File '$fileNameDestination' is not possible!\n";
                        }
                    }
                }
                $fileDataNew .= $line;
            }
            if (!empty($fileNameSource)) {
                $fileNameClass = basename($fileNameSource);
                if ($extension!=$this->yellow->lookup->normaliseName($fileNameClass, true, true)) {
                    $statusCode = 500;
                    $class = "Yellow".ucfirst($extension);
                    echo "ERROR updating files: Class '$class' and file '$fileNameClass' is not possible!\n";
                }
            }
            if ($fileData!=$fileDataNew) {
                if (!$this->yellow->toolbox->createFile($fileNameExtension, $fileDataNew)) {
                    $statusCode = 500;
                    echo "ERROR updating files: Can't write file '$fileNameExtension'!\n";
                }
            }
            if (defined("DEBUG") && DEBUG>=2) echo "YellowPublish::updateExtensionInformation file:$fileNameExtension<br/>\n";
        }
        return $statusCode;
    }

    // Update extension documentation file
    public function updateExtensionDocumentation($path) {
        $statusCode = 200;
        list($extension, $version) = $this->getExtensionInformation($path);
        $regex = "/^.*\\".$this->yellow->system->get("coreContentExtension")."$/";
        foreach ($this->yellow->toolbox->getDirectoryEntries($path, $regex, true, false) as $entry) {
            $fileData = $fileDataNew = $this->yellow->toolbox->readFile($entry);
            if (preg_match("/^(\xEF\xBB\xBF)?([\w ]+[0-9\.]{5,}[\r\n]+)(\=+[\r\n]+)(.*)$/s", $fileData, $parts)) {
                $parts[2] = ucfirst($extension)." ".$version."\n";
                $parts[3] = str_repeat("=", strlenu($parts[2])-1)."\n";
                $fileDataNew = $parts[1].$parts[2].$parts[3].$parts[4];
            }
            if ($fileData!=$fileDataNew) {
                if (!$this->yellow->toolbox->createFile($entry, $fileDataNew)) {
                    $statusCode = 500;
                    echo "ERROR updating files: Can't write file '$entry'!\n";
                }
            }
            if (defined("DEBUG") && DEBUG>=2) echo "YellowPublish::updateExtensionDocumentation file:$entry<br/>\n";
        }
        return $statusCode;
    }
    
    // Update extension ZIP archive
    public function updateExtensionArchive($pathSource, $pathRepositoryOffical) {
        $statusCode = 200;
        list($extension) = $this->getExtensionInformation($pathSource);
        $fileNameExtension = $pathSource.$this->yellow->system->get("updateExtensionFile");
        if (is_file($fileNameExtension) && !empty($extension)) {
            $zip = new ZipArchive();
            $fileNameZipArchive = $pathRepositoryOffical."zip/".strtoloweru("$extension.zip");
            if (is_file($fileNameZipArchive)) $this->yellow->toolbox->deleteFile($fileNameZipArchive);
            if ($zip->open($fileNameZipArchive, ZIPARCHIVE::CREATE)===true) {
                $modified = 0;
                $fileNamesRequired = $this->getExtensionFileNames($pathSource);
                $fileNamesFound = $this->yellow->toolbox->getDirectoryEntriesRecursive($pathSource, "/.*/", true, false);
                foreach ($fileNamesFound as $fileName) {
                    if (!isset($fileNamesRequired[$fileName])) continue;
                    $fileNameSource = $fileNamesRequired[$fileName];
                    $zip->addFile($fileName, $fileNameSource);
                    $modified = max($modified, $this->yellow->toolbox->getFileModified($fileName));
                    unset($fileNamesRequired[$fileName]);
                }
                if (!empty($fileNamesRequired)) {
                    $statusCode = 500;
                    foreach ($fileNamesRequired as $key=>$value) {
                        echo "ERROR updating files: Can't find file '$key'!\n";
                    }
                }
                if (!$zip->close() || !$this->yellow->toolbox->modifyFile($fileNameZipArchive, $modified)) {
                    $statusCode = 500;
                    echo "ERROR updating files: Can't write file '$fileNameZipArchive'!\n";
                }
            } else {
                $statusCode = 500;
                echo "ERROR updating files: Can't write file '$fileNameZipArchive'!\n";
            }
            if (defined("DEBUG") && DEBUG>=2) echo "YellowPublish::updateExtensionArchive file:$fileNameZipArchive<br/>\n";
        }
        return $statusCode;
    }
    
    // Update extension version file
    public function updateExtensionVersion($pathSource, $pathRepositoryOffical) {
        $statusCode = 200;
        list($extension, $version, $status, $description, $author) = $this->getExtensionInformation($pathSource);
        $fileNameVersion = $pathRepositoryOffical.$this->yellow->system->get("updateVersionFile");
        if (is_file($fileNameVersion) && $status!="unlisted") {
            $found = false;
            $fileData = $this->yellow->toolbox->readFile($fileNameVersion);
            $fileDataNew = "";
            foreach ($this->yellow->toolbox->getTextLines($fileData) as $line) {
                if (preg_match("/^\s*(.*?)\s*:\s*(.*?)\s*$/", $line, $matches)) {
                    if (!empty($matches[1]) && !empty($matches[2]) && strtoloweru($matches[1])==strtoloweru($extension)) {
                        list($dummy1, $url, $dummy2) = $this->yellow->toolbox->getTextList($matches[2], ",", 3);
                        $fileDataNew .= "$matches[1]: $version,$url,$author\n";
                        $found = true;
                        continue;
                    }
                }
                $fileDataNew .= $line;
            }
            if (!$found) {
                $url = "https://github.com/datenstrom/yellow-extensions/raw/master/zip/".strtoloweru("$extension.zip");
                $fileDataNew .= "\n# Datenstrom Yellow version, new extension\n\n";
                $fileDataNew .= ucfirst($extension).": $version,$url,$author\n";
            }
            if ($fileData!=$fileDataNew) {
                if (!$this->yellow->toolbox->createFile($fileNameVersion, $fileDataNew)) {
                    $statusCode = 500;
                    echo "ERROR updating files: Can't write file '$fileNameVersion'!\n";
                }
            }
            if (defined("DEBUG") && DEBUG>=2) echo "YellowPublish::updateExtensionVersion file:$fileNameVersion<br/>\n";
        }
        return $statusCode;
    }

    // Update extension waffle file
    public function updateExtensionWaffle($pathSource, $pathRepositoryOffical) {
        $statusCode = 200;
        list($extension, $version, $status) = $this->getExtensionInformation($pathSource);
        $fileNameWaffle = $pathRepositoryOffical.$this->yellow->system->get("updateWaffleFile");
        if (is_file($fileNameWaffle) && $status!="unlisted") {
            $found = false;
            $waffle = $this->getExtensionWaffle($pathSource);
            $fileData = $this->yellow->toolbox->readFile($fileNameWaffle);
            $fileDataNew = "";
            foreach ($this->yellow->toolbox->getTextLines($fileData) as $line) {
                if (preg_match("/^\s*(.*?)\s*:\s*(.*?)\s*$/", $line, $matches)) {
                    if (!empty($matches[1]) && !empty($matches[2]) && preg_match("/^$extension,/i", $matches[2])) {
                        if (!$found) {
                            $fileDataNew .= $waffle;
                            $found = true;
                        }
                        continue;
                    }
                }
                $fileDataNew .= $line;
            }
            if (!$found) {
                $fileDataNew .= "\n# Datenstrom Yellow waffle, new extension\n\n";
                $fileDataNew .= $waffle;
            }
            if ($fileData!=$fileDataNew) {
                if (!$this->yellow->toolbox->createFile($fileNameWaffle, $fileDataNew)) {
                    $statusCode = 500;
                    echo "ERROR updating files: Can't write file '$fileNameWaffle'!\n";
                }
            }
            if (defined("DEBUG") && DEBUG>=2) echo "YellowPublish::updateExtensionWaffle file:$fileNameWaffle<br/>\n";
        }
        return $statusCode;
    }
    
    // Return progress in percent
    public function getProgressPercent($now, $total, $increments, $max)
    {
        $percent = intval(($max / $total) * $now);
        if ($increments>1) $percent = intval($percent / $increments) * $increments;
        return min($max, $percent);
    }
    
    // Return extension information from source code
    public function getExtensionInformationFromSource($path) {
        $extension = $version = $fileNameSource = "";
        foreach ($this->yellow->toolbox->getDirectoryEntries($path, "/^.*\.php$/", false, false) as $entry) {
            $fileData = $this->yellow->toolbox->readFile($entry, 4096);
            foreach ($this->yellow->toolbox->getTextLines($fileData) as $line) {
                if (preg_match("/^\s*(\S+)\s+(\S+)/", $line, $matches)) {
                    if ($matches[1]=="class" && substru($matches[2], 0, 6)=="Yellow") $extension = lcfirst(substru($matches[2], 6));
                    if ($matches[1]=="const" && $matches[2]=="VERSION" && preg_match("/\"([0-9\.]+)\"/", $line, $tokens)) $version = $tokens[1];
                    if ($matches[1]=="function" || $matches[2]=="function") break;
                }
            }
            if (!empty($extension) && !empty($version)) {
                $fileNameSource = $entry;
                break;
            }
        }
        if (empty($extension) && empty($version)) {
            list($extension, $version) = $this->getExtensionInformation($path);
        }
        return array($extension, $version, $fileNameSource);
    }

    // Return extension information
    public function getExtensionInformation($path) {
        $extension = $version = $status = $description = $author = "";
        $fileNameExtension = $path.$this->yellow->system->get("updateExtensionFile");
        $fileData = $this->yellow->toolbox->readFile($fileNameExtension);
        foreach ($this->yellow->toolbox->getTextLines($fileData) as $line) {
            if (preg_match("/^\s*(.*?)\s*:\s*(.*?)\s*$/", $line, $matches)) {
                if (lcfirst($matches[1])=="extension") $extension = lcfirst($matches[2]);
                if (lcfirst($matches[1])=="version") $version = $matches[2];
                if (lcfirst($matches[1])=="status") $status = $matches[2];
                if (lcfirst($matches[1])=="description") $description = $matches[2];
                if (lcfirst($matches[1])=="developer") $author = "$description Developed by $matches[2].";
                if (lcfirst($matches[1])=="translator") $author = "$description Translated by $matches[2].";
                if (lcfirst($matches[1])=="designer") $author = "$description Designed by $matches[2].";
            }
        }
        return array($extension, $version, $status, $description, $author);
    }
    
    // Return extension file names
    public function getExtensionFileNames($path) {
        $data = array();
        $extension = "";
        $language = $this->yellow->system->get("language");
        $fileNameExtension = $path.$this->yellow->system->get("updateExtensionFile");
        $fileData = $this->yellow->toolbox->readFile($fileNameExtension);
        foreach ($this->yellow->toolbox->getTextLines($fileData) as $line) {
            if (preg_match("/^\s*(.*?)\s*:\s*(.*?)\s*$/", $line, $matches)) {
                if (lcfirst($matches[1])=="extension") $extension = lcfirst($matches[2]);
                if (lcfirst($matches[1])=="language") $language = $matches[2];
                if (!empty($matches[1]) && !empty($matches[2]) && strposu($matches[1], "/")) {
                    list($dummy, $entry, $flags) = $this->yellow->toolbox->getTextList($matches[2], ",", 3);
                    if (preg_match("/delete/i", $flags)) continue;
                    if (preg_match("/multi-language/i", $flags)) {
                        foreach (preg_split("/\s*,\s*/", $language) as $token) {
                            $pathLanguage = $token."/";
                            $data["$path$pathLanguage$entry"] = $extension."/".$pathLanguage.basename($entry);
                        }
                    } else {
                        $data["$path$entry"] = $extension."/".basename($entry);
                    }
                }
            }
        }
        $data[$fileNameExtension] = $extension."/".basename($fileNameExtension);
        return $data;
    }
    
    // Return extension waffle
    public function getExtensionWaffle($path) {
        $waffle = "";
        $fileNameExtension = $path.$this->yellow->system->get("updateExtensionFile");
        $fileData = $this->yellow->toolbox->readFile($fileNameExtension);
        foreach ($this->yellow->toolbox->getTextLines($fileData) as $line) {
            if (preg_match("/^\s*(.*?)\s*:\s*(.*?)\s*$/", $line, $matches)) {
                if (!empty($matches[1]) && !empty($matches[2]) && strposu($matches[1], "/")) {
                    $waffle .= "$matches[1]: $matches[2]\n";
                }
            }
        }
        return $waffle;
    }
}
