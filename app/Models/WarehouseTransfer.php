<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class WarehouseTransfer extends Model
{
    use SoftDeletes, HasFactory;

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transfer_number',
        'source_warehouse_id',
        'destination_warehouse_id',
        // 'item_id',
        // 'quantity',
        'status',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'completed_at'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        // 'quantity' => 'integer',
    ];

    /**
     * Estados disponibles para las transferencias
     */
    const STATUS_PENDING = 'pending';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        // Generar número de transferencia automáticamente
        static::creating(function ($transfer) {
            if (empty($transfer->transfer_number)) {
                $transfer->transfer_number = self::generateTransferNumber();
            }
        });
    }

    /**
     * Genera un número único de transferencia
     */
    public static function generateTransferNumber(): string
    {
        $prefix = 'TRF';
        $year = date('Y');
        $month = date('m');
        
        // Obtener el último número de transferencia del mes
        $lastTransfer = self::where('transfer_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('transfer_number', 'desc')
            ->first();

        if ($lastTransfer) {
            $lastNumber = (int) substr($lastTransfer->transfer_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relación con el almacén origen
     */
    public function sourceWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    /**
     * Relación con el almacén destino
     */
    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    /**
     * Relación con el producto
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouseTransferDetail()
    {
        return $this->hasMany(WarehouseTransferDetail::class);
    }

    public function user(){
        return $this->belongsTo(User::class,'created_by');
    }

    /**
     * Relación con el usuario que creó la transferencia
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con el usuario que aprobó la transferencia
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope para transferencias pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope para transferencias en tránsito
     */
    public function scopeInTransit($query)
    {
        return $query->where('status', self::STATUS_IN_TRANSIT);
    }

    /**
     * Scope para transferencias completadas
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope para transferencias canceladas
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Verifica si la transferencia está pendiente
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Verifica si la transferencia está en tránsito
     */
    public function isInTransit(): bool
    {
        return $this->status === self::STATUS_IN_TRANSIT;
    }

    /**
     * Verifica si la transferencia está completada
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Verifica si la transferencia está cancelada
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Marca la transferencia como aprobada
     */
    public function approve(User $user): bool
    {
        if ($this->isPending()) {
            $this->update([
                'status' => self::STATUS_IN_TRANSIT,
                'approved_by' => $user->id,
                'approved_at' => now()
            ]);
            return true;
        }
        return false;
    }

    /**
     * Marca la transferencia como completada
     */
    public function complete(): bool
    {
        if ($this->isInTransit()) {
            // Verificar stock disponible en el almacén origen
            $sourceStock = WarehouseItem::where('warehouse_id', $this->source_warehouse_id)
                ->where('item_id', $this->item_id)
                ->first();

            if (!$sourceStock || $sourceStock->stock < $this->quantity) {
                return false;
            }

            // Actualizar stock en almacén origen
            $sourceStock->decrement('stock', $this->quantity);

            // Actualizar o crear stock en almacén destino
            $destinationStock = WarehouseItem::where('warehouse_id', $this->destination_warehouse_id)
                ->where('item_id', $this->item_id)
                ->first();

            if ($destinationStock) {
                $destinationStock->increment('stock', $this->quantity);
            } else {
                WarehouseItem::create([
                    'warehouse_id' => $this->destination_warehouse_id,
                    'item_id' => $this->item_id,
                    'stock' => $this->quantity,
                    'is_available' => true
                ]);
            }

            $this->update([
                'status' => self::STATUS_COMPLETED,
                'completed_at' => now()
            ]);

            return true;
        }
        return false;
    }

    /**
     * Cancela la transferencia
     */
    public function cancel(): bool
    {
        if ($this->isPending() || $this->isInTransit()) {
            $this->update([
                'status' => self::STATUS_CANCELLED
            ]);
            return true;
        }
        return false;
    }

    /**
     * Obtiene el estado formateado
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_IN_TRANSIT => 'En Tránsito',
            self::STATUS_COMPLETED => 'Completada',
            self::STATUS_CANCELLED => 'Cancelada',
            default => 'Desconocido'
        };
    }

    /**
     * Obtiene el color del estado para la interfaz
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_IN_TRANSIT => 'info',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'gray'
        };
    }
}
