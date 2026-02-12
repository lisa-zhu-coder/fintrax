<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PayrollController extends Controller
{
    public function view(Payroll $payroll)
    {
        $base64 = $payroll->base64_content;
        $pdfContent = base64_decode($base64);
        
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $payroll->file_name . '"');
    }
}
