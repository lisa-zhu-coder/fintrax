<?php

namespace App\Jobs;

use App\Services\PayrollPdfProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ProcessPayrollPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(
        public string $token,
        public string $storagePath,
        public string $originalFileName,
        public int $companyId,
    ) {}

    public function handle(PayrollPdfProcessor $processor): void
    {
        $result = $processor->processMultiPage(
            $this->storagePath,
            $this->originalFileName,
            $this->companyId,
        );

        $cache = Cache::store('file');
        if (isset($result['error'])) {
            $cache->put('payroll_error_' . $this->token, ['message' => $result['error']], 600);
        } else {
            $cache->put('payroll_result_' . $this->token, [
                'pending' => $result['pending'],
                'upload_id' => $result['upload_id'],
                'message' => $result['message'],
            ], 600);
        }

        if (Storage::disk('local')->exists($this->storagePath)) {
            Storage::disk('local')->delete($this->storagePath);
        }
    }
}
