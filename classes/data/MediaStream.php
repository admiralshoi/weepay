<?php
namespace classes\data;

class MediaStream {
    private int $requestingUsersId = 0;
    public string $tmpDir = "public/media/dynamic/tmp/";
    public string $metricUploads = "public/media/dynamic/metrics/";


    function __construct() {
        if(isset($_SESSION["uid"])) $this->requestingUsersId = $_SESSION["uid"];
    }

    public function tmpFilePath(string $filename): array {
        return ["path" => $this->tmpDir . $filename];
    }

    public function setTmpUpload($FILES, string|int $prefix = "", string $subDir = ""): ?array {
        debugLog($FILES, "settmpupload");
        if(empty($FILES)) return null;
        if(!is_dir(ROOT . $this->tmpDir)) mkdir(ROOT . $this->tmpDir);


        $collector = [];
        foreach ($FILES as $type => $objects) {
            foreach (array("tmp_name", "name", "size", "type") as $key) if(!array_key_exists($key, $objects)) return null;

            if(is_array($objects["name"])) {
                $fileItems = [];
                foreach ($objects["name"] as $i => $name) {
                    $fileItems[] = [
                        "name" => $name,
                        "tmp_name" => $objects["tmp_name"][$i],
                        "size" => $objects["size"][$i],
                        "type" => $objects["type"][$i],
                    ];
                }
            }
            else $fileItems = [$objects];

            foreach ($fileItems as $object) {
                $originalFn = $prefix . (is_array($object["name"]) ? $object["name"][0] : $object["name"]);
                $tmpFile = is_array($object["tmp_name"]) ? $object["tmp_name"][0] : $object["tmp_name"];
                $destination = $this->tmpDir . $subDir;
                if(!is_dir(ROOT . $destination)) mkdir(ROOT . $destination);
                $destination .= (!str_ends_with($destination, "/") ? "/" : "") . $originalFn;

                if(!file_exists(ROOT . $destination)) move_uploaded_file($tmpFile,$destination);
                $collector[] = $originalFn;
            }
        }

        return $collector;
    }

    public function moveTmpUpload(array $files, string $targetDirectory, ?string $type = null): array {
        $tmpFileDir = "objects/tmp/" . $this->requestingUsersId . "/";
        $filesToMove = $result = [];

        foreach ($files as $filename) {
            $filename = str_replace('"', "", $filename);
            $tmpPath = $tmpFileDir . $filename;
            if(!file_exists(ROOT . $tmpPath)) return array("status" => "error", "error" => "Tmp file doesnt exist: $filename", "filepath" => $tmpPath);

            $fileInfo = $this->crud->filenameInfo($tmpPath);
            $ext = strtolower($fileInfo["ext"]);
            $maxSize = 15000000; //15MB | 15.000.000 BYTES'
            $mimeType = mime_content_type($tmpPath);
            $filepath = $targetDirectory . $filename;

            if (
                ($type === "image" && !in_array($ext, $this->mediaTypeDetails($type)["valid_extensions"]))
                ||
                (is_null($type) && !$this->disallowedFiletypes($ext))

            ) return array("status" => "error", "error" => "Filetype '.$ext' is not allowed");
            try {
                $fileSize = filesize(ROOT . $tmpPath);
            }
            catch (\Exception $exception) {
                return array("status" => "error", "error" => "The size of the file exceeds the maximum of " . ($maxSize / pow(10, 6)) . "MB");
            }
            if($fileSize > $maxSize) return array("status" => "error", "error" => "The size of the file exceeds the maximum of " . ($maxSize / pow(10, 6)) . "MB");

            $filesToMove[] = array("tmp" => $tmpPath, "dest" => $filepath, "size" => $fileSize, "mime_type" => $mimeType, "ext" => $ext);
        }

        if(empty($filesToMove)) return array("status" => "error", "error" => "An unexpected error happened");
        foreach ($filesToMove as $fileItem) {
            rename(ROOT . $fileItem["tmp"], ROOT . $fileItem["dest"]);
            $result[] = array(
                "filename" => $fileItem["dest"],
                "size" => $fileItem["size"],
                "mime_type" => $fileItem["mime_type"],
                "ext" => $fileItem["ext"],
            );
        }

        return array("status" => "success", "files" => $result);
    }



    public function uploadUserMedia($FILES, string $reference):array {
        if(empty($_FILES) || (is_array($_FILES) && !isset($_FILES["file"]))) return array("success" => false, "error" => "No image was chosen");
        if(array_key_exists("file", $FILES)) $FILES = $FILES["file"];

        $details = $this->mediaTypeDetails(str_contains($FILES["type"], "video") ? "video" : "image");
        if(empty($details)) return array("success" => false, "error" => "Could not get details");

        $userHandler = $this->crud->user($this->requestingUsersId);
        switch ($reference) {
            default: return array("success" => false, "error" => "No such reference $reference");
            case "cover_image": $details["destination_directory"] = $userHandler->getSubDir($this->requestingUsersId,  $userHandler::COVER_IMAGES_DIR); break;
            case "profile_picture":
                $details["destination_directory"] = $userHandler->getSubDir($this->requestingUsersId,  $userHandler::IMAGE_DIR);
                $details["fn"] = "";
                break;
        }

        $res = $this->handle($FILES, $details);
        if(array_key_exists("error", $res)) return array("success" => false, "error" => $res["error"]);
        if(!move_uploaded_file($res["tmp"],$res["filepath"])) return array("success" => false,"error" => "Failed to store file");

        if($reference === "profile_picture") {
            $userBaseInfo = $userHandler->getBaseFileContent();
            $userBaseInfo["picture"] = $details["destination_directory"] . basename($res["filepath"]);
            $userBaseInfo["has_profile_picture"] = true;
            $userHandler->setBaseFileContent($userBaseInfo, $this->requestingUsersId);
        }
        return array("success" => true,"content" => $res["filepath"]);

    }


    public function mediaTypeDetails(string $type): array {
        $details = array(
            "video" => array(
                "end_dir" => "",
                "valid_extensions" => array('mp4', 'avi', 'mov', 'wmv'),
                "valid_types" => array("video/mp4"=>"mp4","video/avi"=>"mp4","video/mov"=>"mov","video/wmv"=>"wmv"),
                "max_file_size" => 10000000,
                "type" => "video",
                "fn" => ""
            ),
            "image" => array(
                "end_dir" => "",
                "valid_extensions" => array('jpeg', 'jpg', 'png', 'gif', "heic"),
                "valid_types" => array("image/jpeg"=>"jpeg","image/jpg"=>"jpg","image/png"=>"png","image/gif"=>"gif","image/heif"=>"heic"),
                "max_file_size" => 5000000,
                "type" => "image",
                "fn" => ""
            )
        );

        return !array_key_exists($type, $details) ? array() : $details[$type];
    }




    /**
     * @param string $compareType
     * @return bool|array
     *
     * if compareType is not empty, method will return TRUE if allowed, FALSE if disallowed
     */
    private function disallowedFiletypes(string $compareType = ""): bool|array {
        $disallowedTypes = ["exe", "dat", "dll", "sys", "jar"];
        return !empty($compareType) ? !in_array($compareType, $disallowedTypes) : $disallowedTypes;
    }


    private function handle($FILES, array $details):array {
        if(!is_dir(ROOT . $details["destination_directory"])) mkdir(ROOT . $details["destination_directory"]);


        $mediaName = $FILES['name'];
        $type = $FILES['type'];

        if($mediaName === "blob") {
            if(array_key_exists($type,$details["valid_types"])) $mediaName = time() . "." . $details["valid_types"][$type];
            else return array("success" => false,"error" => "This filetype is not allowed. Please only use filetypes of " . implode(", ", $details["valid_extensions"]));
        }

        $fileSize = (int)$FILES['size'];
        if($fileSize > $details["max_file_size"]) return array(
            "error" => "The file is too large. Max: " . ($details["max_file_size"] / (pow(10,6))) ."MB. Your file is: " .
                ($fileSize / (pow(10,6))) . "MB"
        );

        $tmp = $FILES['tmp_name'];
        $ext = strtolower(pathinfo($mediaName, PATHINFO_EXTENSION));

        $final_image = empty($details["fn"]) ? "IMG-" .  time() . "." . $ext : $details["fn"];
        $filepath = $details["destination_directory"] . $final_image;

        return in_array($ext, $details["valid_extensions"]) ? array("tmp" => $tmp, "filepath" => $filepath) : array("error" => "Bad file type (102)");
    }



}