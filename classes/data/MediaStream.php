<?php
namespace classes\data;

class MediaStream {
    private string $requestingUsersId = "";
    public string $tmpDir = "public/media/dynamic/tmp/";
    public string $metricUploads = "public/media/dynamic/metrics/";
    public string $organisations = "public/content/organisations";


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

    /**
     * Generic method to upload organisation media with flexible validation
     *
     * @param array $FILES The uploaded file
     * @param string $organisationId Organisation ID
     * @param array $config Configuration array with keys:
     *   - type: 'hero' or 'logo' (for subdirectory and filename prefix)
     *   - minWidth: minimum width in pixels
     *   - minHeight: minimum height in pixels
     *   - recommendedRatio: recommended aspect ratio (width/height)
     *   - minRatio: minimum acceptable ratio
     *   - maxRatio: maximum acceptable ratio
     *   - ratioDescription: description of ratio for error message
     *   - maxFileSize: max file size in bytes (default 10MB)
     *   - defaultConstant: constant to compare for default check (optional)
     */
    public function uploadOrganisationMedia($FILES, string $organisationId, array $config): array {
        if(empty($FILES) || !isset($FILES["file"])) return array("success" => false, "error" => "Intet billede blev valgt");
        if(array_key_exists("file", $FILES)) $FILES = $FILES["file"];

        // Get image details
        $details = $this->mediaTypeDetails("image");
        if(empty($details)) return array("success" => false, "error" => "Kunne ikke hente billeddetaljer");

        // Set max file size
        $details["max_file_size"] = $config['maxFileSize'] ?? 10000000;

        // First, save to tmp directory
        $tmpDetails = $details;
        $tmpDetails["destination_directory"] = $this->tmpDir;
        if(!is_dir(ROOT . $tmpDetails["destination_directory"])) {
            mkdir(ROOT . $tmpDetails["destination_directory"], 0755, true);
        }

        $res = $this->handle($FILES, $tmpDetails);
        if(array_key_exists("error", $res)) return array("success" => false, "error" => $res["error"]);

        // Move to tmp first
        $tmpFilePath = $res["filepath"];
        if(!move_uploaded_file($res["tmp"], ROOT . $tmpFilePath)) {
            return array("success" => false, "error" => "Kunne ikke gemme midlertidig fil");
        }

        // Validate image dimensions and aspect ratio
        $imageInfo = getimagesize(ROOT . $tmpFilePath);
        if(!$imageInfo) {
            unlink(ROOT . $tmpFilePath);
            return array("success" => false, "error" => "Ugyldig billedfil");
        }

        list($width, $height) = $imageInfo;

        // Check minimum dimensions
        $minWidth = $config['minWidth'] ?? 0;
        $minHeight = $config['minHeight'] ?? 0;

        if($width < $minWidth || $height < $minHeight) {
            unlink(ROOT . $tmpFilePath);
            return array("success" => false, "error" => "Billedet skal være mindst {$minWidth}×{$minHeight}px. Dit billede er {$width}×{$height}px");
        }

        // Check aspect ratio if provided
        if(isset($config['minRatio']) && isset($config['maxRatio'])) {
            $actualRatio = $width / $height;

            if($actualRatio < $config['minRatio'] || $actualRatio > $config['maxRatio']) {
                unlink(ROOT . $tmpFilePath);
                $recommendedWidth = isset($config['recommendedRatio']) ? round($height * $config['recommendedRatio']) : $width;
                $ratioDesc = $config['ratioDescription'] ?? 'korrekt format';
                return array(
                    "success" => false,
                    "error" => "Billedet skal have {$ratioDesc}. Dit billede er {$width}×{$height}px. Prøv {$recommendedWidth}×{$height}px"
                );
            }
        }

        // Prepare final destination
        $type = $config['type'] ?? 'media';
        $mediaDir = $this->organisations . "/" . $organisationId . "/media/{$type}/";
        if(!is_dir(ROOT . $mediaDir)) {
            mkdir(ROOT . $mediaDir, 0755, true);
        }

        $ext = strtolower(pathinfo($FILES['name'], PATHINFO_EXTENSION));
        $filename = $type . "-" . time() . "." . $ext;
        $finalPath = $mediaDir . $filename;

        // Move from tmp to final destination
        if(!rename(ROOT . $tmpFilePath, ROOT . $finalPath)) {
            unlink(ROOT . $tmpFilePath);
            return array("success" => false, "error" => "Kunne ikke flytte fil til endelig destination");
        }

        $result = array(
            "success" => true,
            "path" => $finalPath,
            "width" => $width,
            "height" => $height
        );

        // Check if this is default if constant provided
        if(isset($config['defaultConstant'])) {
            $result['default'] = $finalPath === $config['defaultConstant'];
        }

        return $result;
    }

    /**
     * Upload hero image for organisation location
     */
    public function uploadOrganisationHeroImage($FILES, string $organisationId): array {
        return $this->uploadOrganisationMedia($FILES, $organisationId, [
            'type' => 'hero',
            'minWidth' => 0,
            'minHeight' => 300,
            'recommendedRatio' => 19 / 6,
            'minRatio' => 19 / 12,
            'maxRatio' => 20 / 5,
            'ratioDescription' => 'et billedformat mellem 20:5 og 19:12 (19:6 anbefalet, f.eks. 1920×600px)',
            'maxFileSize' => 10000000,
            'defaultConstant' => DEFAULT_LOCATION_HERO
        ]);
    }

    /**
     * Upload logo/profile picture for organisation location
     */
    public function uploadOrganisationLogo($FILES, string $organisationId): array {
        return $this->uploadOrganisationMedia($FILES, $organisationId, [
            'type' => 'logo',
            'minWidth' => 250,
            'minHeight' => 250,
            'recommendedRatio' => 1.0,
            'minRatio' => 0.8,
            'maxRatio' => 1.2,
            'ratioDescription' => 'et kvadratisk format (1:1 anbefalet, f.eks. 500×500px)',
            'maxFileSize' => 5000000,
            'defaultConstant' => DEFAULT_LOCATION_LOGO
        ]);
    }

    /**
     * Upload offer image for organisation location
     * Accepts ratio between 4:5 (portrait) and 16:9 (landscape)
     */
    public function uploadOrganisationOfferImage($FILES, string $organisationId): array {
        return $this->uploadOrganisationMedia($FILES, $organisationId, [
            'type' => 'offer',
            'minWidth' => 300,
            'minHeight' => 200,
            'recommendedRatio' => 16 / 9,
            'minRatio' => 4 / 5,      // 0.8 - portrait
            'maxRatio' => 16 / 9,     // 1.78 - landscape
            'ratioDescription' => 'et billedformat mellem 4:5 og 16:9 (f.eks. 800×450px eller 400×500px)',
            'maxFileSize' => 5000000
        ]);
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