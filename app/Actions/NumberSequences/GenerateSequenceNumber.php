<?php

namespace App\Actions\NumberSequences;

use App\Enums\SequenceResetPeriod;
use App\Models\NumberSequence;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GenerateSequenceNumber
{
    /**
     * Reserve and format the next number for the given sequence key.
     */
    public function handle(string $key): string
    {
        return DB::transaction(function () use ($key): string {
            $sequence = NumberSequence::query()
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            if (! $sequence instanceof NumberSequence) {
                throw new InvalidArgumentException(
                    "No number sequence is configured for [{$key}].",
                );
            }

            $now = now();

            $this->resetIfDue($sequence, $now);

            $number = $this->format($sequence, $sequence->next_number, $now);

            $sequence->increment('next_number');

            return $number;
        }, 3);
    }

    /**
     * Restart the counter when the configured period has rolled over.
     */
    private function resetIfDue(NumberSequence $sequence, CarbonInterface $now): void
    {
        if (! $this->isResetDue($sequence, $now)) {
            return;
        }

        $sequence->forceFill([
            'next_number' => 1,
            'last_reset_at' => $now,
        ])->save();
    }

    /**
     * Determine whether the sequence is due for a counter reset.
     */
    private function isResetDue(NumberSequence $sequence, CarbonInterface $now): bool
    {
        $lastReset = $sequence->last_reset_at;

        return match ($sequence->reset_period) {
            SequenceResetPeriod::Never => false,
            SequenceResetPeriod::Daily => $lastReset === null || ! $lastReset->isSameDay($now),
            SequenceResetPeriod::Monthly => $lastReset === null || ! $lastReset->isSameMonth($now),
            SequenceResetPeriod::Yearly => $lastReset === null || $lastReset->year !== $now->year,
        };
    }

    /**
     * Replace the placeholders in the sequence format with their values.
     */
    private function format(NumberSequence $sequence, int $number, CarbonInterface $now): string
    {
        $formatted = (string) preg_replace_callback(
            '/\{SEQ(?::(\d+))?\}/',
            fn (array $matches): string => str_pad(
                (string) $number,
                isset($matches[1]) ? (int) $matches[1] : $sequence->padding,
                '0',
                STR_PAD_LEFT,
            ),
            $sequence->format,
        );

        $formatted = strtr($formatted, [
            '{PREFIX}' => (string) $sequence->prefix,
            '{YYYYMMDD}' => $now->format('Ymd'),
            '{YYYY}' => $now->format('Y'),
            '{YY}' => $now->format('y'),
            '{MM}' => $now->format('m'),
            '{DD}' => $now->format('d'),
        ]);

        $formatted = ltrim($formatted, '-_/');
        $formatted = (string) preg_replace('/[-_\/]{2,}/', '-', $formatted);

        return trim($formatted, '-_/');
    }
}
