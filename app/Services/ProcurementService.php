<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\StockReceipt;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcurementService
{
    public function approve(PurchaseOrder $order, int $userId): PurchaseOrder
    {
        if ($order->status !== 'draft') {
            throw ValidationException::withMessages(['order' => 'Only draft purchase orders can be approved.']);
        }

        $order->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        return $order->refresh();
    }

    public function recordSupplierPayment(array $data): SupplierPayment
    {
        return DB::transaction(function () use ($data) {
            $receipt = null;

            if (! empty($data['stock_receipt_id'])) {
                $receipt = StockReceipt::query()->lockForUpdate()->findOrFail($data['stock_receipt_id']);

                if ((float) $data['amount'] > (float) $receipt->balance) {
                    throw ValidationException::withMessages([
                        'paymentAmount' => 'Payment cannot exceed the outstanding receipt balance.',
                    ]);
                }
            }

            $payment = SupplierPayment::create([
                'business_id' => $data['business_id'],
                'supplier_id' => $data['supplier_id'],
                'stock_receipt_id' => $data['stock_receipt_id'] ?: null,
                'recorded_by' => $data['recorded_by'],
                'payment_number' => $this->nextPaymentNumber(),
                'payment_date' => $data['payment_date'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?: null,
                'notes' => $data['notes'] ?: null,
            ]);

            if ($receipt) {
                $paid = min((float) $receipt->subtotal, (float) $receipt->amount_paid + (float) $data['amount']);
                $balance = max(0, (float) $receipt->subtotal - $paid);

                $receipt->update([
                    'amount_paid' => $paid,
                    'balance' => $balance,
                    'payment_status' => $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                ]);
            }

            return $payment;
        });
    }

    private function nextPaymentNumber(): string
    {
        $prefix = 'SP-'.now()->format('ymd').'-';
        $last = SupplierPayment::withoutGlobalScopes()
            ->where('payment_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('payment_number');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
