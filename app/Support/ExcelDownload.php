<?php

namespace App\Support;

use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Builds an .xlsx in a temp file and returns a normal download response.
 * (SimpleExcelWriter::toBrowser() calls exit, which skips Laravel's response cycle.)
 */
class ExcelDownload
{
    /**
     * @param  callable(SimpleExcelWriter): void  $fill  adds the rows
     */
    public static function make(string $filename, callable $fill): BinaryFileResponse
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'export-' . Str::uuid() . '.xlsx';

        $writer = SimpleExcelWriter::create($path);
        $fill($writer);
        $writer->close();

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }
}
