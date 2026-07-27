<?php

namespace App\Http\Controllers;

use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;

class ActivityEnrollmentImportController extends Controller
{
    public function downloadTemplate()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'activity_template') . '.xlsx';

        $writer = new Writer();
        $writer->openToFile($tempFile);

        // Header row
        $headerCells = [
            Cell::fromValue('كود المخدوم'),
        ];
        $writer->addRow(new Row($headerCells));

        // Example rows
        $writer->addRow(new Row([Cell::fromValue('10001')]));
        $writer->addRow(new Row([Cell::fromValue('10002')]));
        $writer->addRow(new Row([Cell::fromValue('10003')]));

        $writer->close();

        return response()->download($tempFile, 'activity_enrollment_template.xlsx')->deleteFileAfterSend(true);
    }
}
