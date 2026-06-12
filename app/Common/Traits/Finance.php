<?php

namespace App\Common\Traits;

use App\Enums\MovementType;
use App\Models\FinancialStatement;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait Finance
{
    public function financialStatements(): HasMany
    {
        return $this->hasMany(FinancialStatement::class, 'receiver_id');
    }

    public function balance(): int
    {
        return (int) $this->financialStatements()
            ->selectRaw('COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE -amount END), 0) as balance', [
                MovementType::Positive->value,
            ])
            ->value('balance');
    }
}
