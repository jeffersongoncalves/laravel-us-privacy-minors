<?php

namespace JeffersonGoncalves\UsPrivacyMinors\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\UsPrivacy\UsPrivacy;
use JeffersonGoncalves\UsPrivacyMinors\MinorsManager;

class MinorsCommand extends Command
{
    public $signature = 'us-privacy:minors
        {--state= : Two-letter state code (default: the strictest rules, minors under 18)}
        {--json : Print the report as JSON}';

    public $description = 'List users known to be minors without consent: no targeted advertising or sale of their data';

    public function handle(MinorsManager $minors, UsPrivacy $privacy): int
    {
        $state = $this->option('state');
        $rules = $privacy->rulesFor(is_string($state) ? $state : null);

        $rows = $minors->withoutOptIn($rules)
            ->map(fn (Model $subject): array => [
                'type' => $subject->getMorphClass(),
                'id' => $subject->getKey(),
                'age' => $minors->age($subject),
                'consent_by' => $rules?->minorRule === 'prohibited' ? 'none (prohibited)' : ($minors->needsParent($subject) ? 'parent' : 'minor'),
            ])
            ->all();

        if ($this->option('json')) {
            $this->line((string) json_encode($rows, JSON_PRETTY_PRINT));
        } else {
            $this->components->info(count($rows).' minor(s) without consent under '.($rules->name ?? 'no state law').'.');
            $this->table(['Type', 'ID', 'Age', 'Consent by'], $rows);
        }

        return self::SUCCESS;
    }
}
