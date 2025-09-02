<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Population;
use App\Services\GeneticAlgorithmService;
use Illuminate\Support\Facades\Log;
use Throwable; // لالتقاط كل الأخطاء

class GenerateTimetableJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // خصائص لتخزين الإعدادات ومعلومات التشغيل
    protected array $settings;
    // protected Population $populationRun;
    protected int $populationRunId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $settings, int $populationRunId)
    // public function __construct(array $settings, Population $populationRun)
    {
        $this->settings = $settings;
        // $this->populationRun = $populationRun;
         $this->populationRunId = $populationRunId;
    }

    /**
     * Execute the job.
     * هذه الدالة هي التي سيقوم الـ Queue Worker بتنفيذها في الخلفية
     */
    public function handle(): void
    {
        // Log::info("Job starting for Population Run ID: {$this->populationRun->population_id}");
        Log::info("Job starting for Population Run ID: {$this->populationRunId}");
        $populationRun = Population::find($this->populationRunId);
        if (!$populationRun) {
            Log::error("Population Run with ID: {$this->populationRunId} not found in the database.");
            return; // نوقف المهمة إذا لم نجد السجل
        }
        try {
            // $gaService = new GeneticAlgorithmService($this->settings, $this->populationRun);
            $gaService = new GeneticAlgorithmService($this->settings, $populationRun);
            // إنشاء وتشغيل الـ Service
            $gaService->run(); // بدء التنفيذ الفعلي للخوارزمية

        } catch (Throwable $e) {
            // إذا حدث أي خطأ فادح أثناء عمل الـ Job
            Log::error("GenerateTimetableJob failed for Population Run ID: {$this->populationRunId}. Error: " . $e->getMessage());
            // تحديث حالة التشغيل إلى "failed"
            $populationRun->update(['status' => 'failed']);
            // يمكنك إرسال إشعار للمستخدم بأن العملية فشلت
             // $this->fail($e); // هذا يضع المهمة في جدول failed_jobs
        }
    }
}
