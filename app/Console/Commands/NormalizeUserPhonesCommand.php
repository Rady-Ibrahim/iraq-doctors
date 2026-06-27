<?php

namespace App\Console\Commands;

use App\Support\PhoneNormalizer;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Modules\Auth\Models\User;

class NormalizeUserPhonesCommand extends Command
{
    protected $signature = 'phones:normalize {--dry-run : Preview changes without saving}';

    protected $description = 'Convert stored user phone numbers to E.164 (+964...)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $skipped = 0;

        User::query()->orderBy('id')->chunkById(100, function ($users) use ($dryRun, &$updated, &$skipped) {
            foreach ($users as $user) {
                try {
                    $e164 = PhoneNormalizer::toE164($user->phone);
                } catch (InvalidArgumentException) {
                    $this->warn("Skip user #{$user->id}: invalid phone [{$user->phone}]");
                    $skipped++;
                    continue;
                }

                if ($user->phone === $e164) {
                    continue;
                }

                $this->line("User #{$user->id}: {$user->phone} → {$e164}");

                if (!$dryRun) {
                    $user->update(['phone' => $e164]);
                }

                $updated++;
            }
        });

        $this->info($dryRun
            ? "Dry run: {$updated} would be updated, {$skipped} skipped."
            : "Done: {$updated} updated, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
