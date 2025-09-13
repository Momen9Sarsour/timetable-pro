<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Population;
use App\Services\InitialPopulationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateInitialPopulationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $settings;
    protected int $populationRunId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $settings, int $populationRunId)
    {
        $this->settings = $settings;
        $this->populationRunId = $populationRunId;
    }

    /**
     * Execute the job.
     * ينشئ الجيل الأول فقط ويتوقف
     */
    public function handle(): void
    {
        Log::info("Initial Population Job starting for Population ID: {$this->populationRunId}");

        $populationRun = Population::find($this->populationRunId);
        if (!$populationRun) {
            Log::error("Population with ID: {$this->populationRunId} not found in the database.");
            return;
        }

        try {
            // استخدام Service الجديد للجيل الأول فقط
            $initialService = new InitialPopulationService($this->settings, $populationRun);
            $initialService->generateInitialGeneration();

            Log::info("Initial Population generated successfully for Population ID: {$this->populationRunId}");
            
        } catch (Throwable $e) {
            Log::error("GenerateInitialPopulationJob failed for Population ID: {$this->populationRunId}. Error: " . $e->getMessage());
            
            // تحديث حالة Population إلى failed
            $populationRun->update([
                'status' => 'failed',
                'end_time' => now()
            ]);

            // يمكن إضافة إشعار للمستخدم هنا
            throw $e;
        }
    }
}