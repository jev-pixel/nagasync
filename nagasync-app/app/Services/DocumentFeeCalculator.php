<?php

namespace App\Services;

use App\Models\DocumentRequest;

class DocumentFeeCalculator
{
    /**
     * @param array $inputs ['declared_amount' => capital / gross sales / income, entered by staff]
     * @return array{lines: array, total: float, inputs: array}
     */
    public function compute(DocumentRequest $doc, array $inputs = []): array
    {
        $rules    = config('nagasync_fees.types')[$doc->document_type] ?? null;
        $declared = max(0.0, (float) ($inputs['declared_amount'] ?? 0));
        $lines    = [];

        foreach ($rules['flat'] ?? [] as $line) {
            $lines[] = ['label' => $line['label'], 'amount' => round($line['amount'], 2)];
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
            'total'  => round(array_sum(array_column($lines, 'amount')), 2),
            'inputs' => ['declared_amount' => $declared],
        ];
    }
}