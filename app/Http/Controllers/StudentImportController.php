<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;

class StudentImportController extends Controller
{
    public function downloadTemplate()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'template') . '.xlsx';
        
        $writer = new Writer();
        $writer->openToFile($tempFile);

        // Header cells
        $headerCells = [
            Cell::fromValue('اسم المخدوم'),
            Cell::fromValue('الجنس'),
            Cell::fromValue('تاريخ الميلاد'),
            Cell::fromValue('ملاحظات'),
            Cell::fromValue('اسم ولي الأمر'),
            Cell::fromValue('رقم موبايل ولي الأمر'),
        ];
        $writer->addRow(new Row($headerCells));

        // Example row 1
        $exampleRow1 = [
            Cell::fromValue('جرجس سمير فايز'),
            Cell::fromValue('ذكر'),
            Cell::fromValue('2015-05-15'),
            Cell::fromValue('موهوب في الألحان'),
            Cell::fromValue('سمير فايز'),
            Cell::fromValue('01234567890'),
        ];
        $writer->addRow(new Row($exampleRow1));

        // Example row 2
        $exampleRow2 = [
            Cell::fromValue('مريم ميخائيل شفيق'),
            Cell::fromValue('أنثى'),
            Cell::fromValue('2016-08-20'),
            Cell::fromValue(''),
            Cell::fromValue('ميخائيل شفيق'),
            Cell::fromValue('01288226619'),
        ];
        $writer->addRow(new Row($exampleRow2));

        $writer->close();

        return response()->download($tempFile, 'students_import_template.xlsx')->deleteFileAfterSend(true);
    }
}
