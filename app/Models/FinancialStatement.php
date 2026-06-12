<?php

namespace App\Models;

use App\Common\Traits\HashIdsModel;
use App\Enums\MovementType;
use App\Enums\OperationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $hash
 * @property OperationType $operation_type
 * @property MovementType $type
 * @property bool $reversed
 * @property int $requester_id
 * @property int $receiver_id
 * @property int $amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['operation_type', 'type', 'reversed', 'requester_id', 'receiver_id', 'amount'])]
class FinancialStatement extends Model
{
    use HasFactory, HashIdsModel, SoftDeletes;

    protected function casts(): array
    {
        return [
            'operation_type' => OperationType::class,
            'type' => MovementType::class,
            'reversed' => 'boolean',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
