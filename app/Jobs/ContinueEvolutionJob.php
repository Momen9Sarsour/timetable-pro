<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Population;
use App\Services\ContinueEvolutionService;
use Illuminate\Support\Facades\Log;
use Throwable;

class ContinueEvolutionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $settings;
    protected int $newPopulationId;
    protected int $parentPopulationId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $settings, int $newPopulationId, int $parentPopulationId)
    {
        $this->settings = $settings;
        $this->newPopulationId = $newPopulationId;
        $this->parentPopulationId = $parentPopulationId;
    }

    /**
     * Execute the job.
     * يكمل الخوارزمية من population موجود
     */
    public function handle(): void
    {
        Log::info("Continue Evolution Job starting for New Population ID: {$this->newPopulationId}, Parent ID: {$this->parentPopulationId}");

        $newPopulation = Population::find($this->newPopulationId);
        $parentPopulation = Population::find($this->parentPopulationId);

        if (!$newPopulation) {
            Log::error("New Population with ID: {$this->newPopulationId} not found in the database.");
            return;
        }

        if (!$parentPopulation) {
            Log::error("Parent Population with ID: {$this->parentPopulationId} not found in the database.");
            $newPopulation->update(['status' => 'failed', 'end_time' => now()]);
            return;
        }

        try {
            // استخدام Service الجديد لإكمال الخوارزمية
            $continueService = new ContinueEvolutionService($this->settings, $newPopulation, $parentPopulation);
            $continueService->continueFromParent();

            Log::info("Evolution continued successfully for Population ID: {$this->newPopulationId}");

        } catch (Throwable $e) {
            Log::error("ContinueEvolutionJob failed for Population ID: {$this->newPopulationId}. Error: " . $e->getMessage());

            // تحديث حالة Population إلى failed
            $newPopulation->update([
                'status' => 'failed',
                'end_time' => now()
            ]);

            // يمكن إضافة إشعار للمستخدم هنا
            throw $e;
        }
    }
}
