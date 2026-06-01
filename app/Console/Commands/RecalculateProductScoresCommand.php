<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Product\Services\Api\ProductRankingService;

class RecalculateProductScoresCommand extends Command
{
    protected $signature = 'catalog:recalculate-product-scores';

    protected $description = 'Recalculate cached product ranking scores';

    public function handle(ProductRankingService $service): int
    {
        $service->recalculateScores();
        $this->info('Product scores recalculated successfully.');

        return self::SUCCESS;
    }
}
