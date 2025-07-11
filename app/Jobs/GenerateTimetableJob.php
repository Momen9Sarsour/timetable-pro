<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Population; // استيراد الموديل
use App\Services\GeneticAlgorithmService; // استيراد الـ Service
use Illuminate\Support\Facades\Log;
use Throwable; // لالتقاط كل الأخطاء

class GenerateTimetableJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // خصائص لتخزين الإعدادات ومعلومات التشغيل
    protected array $settings;
    protected Population $populationRun;

    /**
     * Create a new job instance.
     */
    public function __construct(array $settings, Population $populationRun)
    {
        $this->settings = $settings;
        $this->populationRun = $populationRun;
    }

    /**
     * Execute the job.
     * هذه الدالة هي التي سيقوم الـ Queue Worker بتنفيذها في الخلفية
     */
    public function handle(): void
    {
        Log::info("Job starting for Population Run ID: {$this->populationRun->population_id}");
        try {
            // إنشاء وتشغيل الـ Service
            $gaService = new GeneticAlgorithmService($this->settings, $this->populationRun);
            $gaService->run(); // بدء التنفيذ الفعلي للخوارزمية

        } catch (Throwable $e) {
            // إذا حدث أي خطأ فادح أثناء عمل الـ Job
            Log::error("GenerateTimetableJob failed for Population Run ID: {$this->populationRun->population_id}. Error: " . $e->getMessage());
            // تحديث حالة التشغيل إلى "failed"
            $this->populationRun->update(['status' => 'failed']);
            // يمكنك إرسال إشعار للمستخدم بأن العملية فشلت
             // $this->fail($e); // هذا يضع المهمة في جدول failed_jobs
        }
    }
}
