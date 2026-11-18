<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Sent = 'sent';
    case Queued = 'queued';
    case Printing = 'printing';
    case Packaging = 'packaging';
    case Ready = 'ready';
    case Done = 'done';

    /**
     * Human readable label for the order progress.
     */
    public function label(?string $fulfillment = null): string
    {
        if ($this === self::Ready) {
            return $fulfillment === 'shipment'
                ? __('invoice.order_statuses.ready_shipment')
                : __('invoice.order_statuses.ready_pickup');
        }

        return __('invoice.order_statuses.'.$this->value);
    }

    /**
     * Short explanation shown under the label on the public page.
     */
    public function description(?string $fulfillment = null): string
    {
        if ($this === self::Ready) {
            return $fulfillment === 'shipment'
                ? __('invoice.order_descriptions.ready_shipment')
                : __('invoice.order_descriptions.ready_pickup');
        }

        return __('invoice.order_descriptions.'.$this->value);
    }

    /**
     * The ordered progress steps shown to the customer.
     *
     * @return list<array{key: string, label: string, description: string}>
     */
    public static function steps(?string $fulfillment = null): array
    {
        return array_map(fn (self $status): array => [
            'key' => $status->value,
            'label' => $status->label($fulfillment),
            'description' => $status->description($fulfillment),
        ], self::cases());
    }
}
