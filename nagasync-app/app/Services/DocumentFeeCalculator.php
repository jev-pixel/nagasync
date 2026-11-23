<?php

namespace App\Services;

use App\Models\DocumentRequest;

class DocumentFeeCalculator
{
    public function rules(string $type): array
    {
        return config('nagasync_fees.types')[$type] ?? [];
    }

    /**
     * Purposes the citizen picks from for this type, with their fee.
     *
     * @return array<string, array{label: string, fee: float|int, student?: bool}>
     */
    public function purposes(string $type): array
    {
        $source = $this->rules($type)['by_purpose'] ?? [];

        return is_string($source) ? (array) config($source, []) : $source;
    }

    /** True when staff must enter a declared amount (capital, gross sales, income) to price it. */
    public function needsDeclaredAmount(string $type): bool
    {
        $r = $this->rules($type);

        return isset($r['rate_on_declared']) || isset($r['per_thousand']);
    }

    public function acceptsClearanceFee(string $type): bool
    {
        return (bool) ($this->rules($type)['accepts_clearance_fee'] ?? false);
    }

    /**
     * @param array $inputs ['declared_amount' => ..., 'barangay_clearance_fee' => ...] entered by staff
     * @return array{lines: array, total: float, inputs: array}
     */
    public function compute(DocumentRequest $doc, array $inputs = []): array
    {
        $rules     = $this->rules($doc->document_type);
        $declared  = max(0.0, (float) ($inputs['declared_amount'] ?? 0));
        $clearance = max(0.0, (float) ($inputs['barangay_clearance_fee'] ?? 0));
        $lines     = [];

        foreach ($rules['flat'] ?? [] as $line) {
            $lines[] = ['label' => $line['label'], 'amount' => round($line['amount'], 2)];
        }

        if (isset($rules['by_purpose'])) {
            $p = $this->purposes($doc->document_type)[$doc->purpose_key] ?? null;

            if ($p && (float) $p['fee'] > 0) {
                $lines[] = ['label' => 'Fee: ' . $p['label'], 'amount' => round((float) $p['fee'], 2)];
            }
        }

        if ($clearance > 0 && ($rules['accepts_clearance_fee'] ?? false)) {
            $lines[] = ['label' => 'Barangay business clearance fee', 'amount' => round($clearance, 2)];
        }

        if (isset($rules['rate_on_declared'])) {
            $lines[] = [
                'label'  => $rules['rate_on_declared']['label'],
                'basis'  => $declared,
                'amount' => round($declared * $rules['rate_on_declared']['rate'], 2),
            ];
        }

        if (isset($rules['per_thousand'])) {
            $extra = floor($declared / 1000) * $rules['per_thousand']['amount'];
            $lines[] = [
                'label'  => $rules['per_thousand']['label'],
                'basis'  => $declared,
                'amount' => round(min($extra, $rules['per_thousand']['cap']), 2),
            ];
        }

        return [
            'lines'  => $lines,
            'total'  => round((float) array_sum(array_column($lines, 'amount')), 2),
            'inputs' => ['declared_amount' => $declared, 'barangay_clearance_fee' => $clearance],
        ];
    }
}
