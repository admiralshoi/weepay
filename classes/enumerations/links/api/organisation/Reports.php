<?php

namespace classes\enumerations\links\api\organisation;

class Reports {
    public string $generateCsv = "api/organisation/reports/generate-csv";
    public string $generatePdf = "api/organisation/reports/generate-pdf";

    public function download(string $filename): string {
        return "api/organisation/reports/download/" . $filename;
    }
}
