<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Setting;
use App\Support\BusinessProfile;
use App\Support\QrisPayload;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PaymentController extends Controller
{
    /**
     * Show the public QRIS payment page for a signed invoice link.
     */
    public function __invoke(Invoice $invoice): Response
    {
        $invoice->loadMissing('customer');

        $paid = $invoice->status === InvoiceStatus::Paid;
        $payable = $invoice->status === InvoiceStatus::Sent;
        $qrisDataUri = null;
        $qrisError = null;

        if (! $paid && ! $payable) {
            $qrisError = 'This invoice is not ready for payment yet.';
        } elseif (! $paid) {
            $payload = (string) Setting::query()
                ->where('key', 'qris_payload')
                ->value('value');

            if (blank($payload)) {
                $qrisError = 'QRIS is not configured yet. Please contact us to complete payment.';
            } else {
                $dynamicPayload = QrisPayload::forAmount(
                    $payload,
                    (string) $invoice->total_amount,
                );

                if ($dynamicPayload === null) {
                    $qrisError = 'The QRIS code could not be generated. Please contact us to complete payment.';
                } else {
                    $qrisDataUri = $this->qrDataUri($dynamicPayload);

                    if ($qrisDataUri === null) {
                        $qrisError = 'The QRIS code could not be generated. Please contact us to complete payment.';
                    }
                }
            }
        }

        return Inertia::render('payments/Show', [
            'business' => BusinessProfile::toArray(),
            'invoice' => [
                'number' => $invoice->number,
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
                'issue_date' => $invoice->issue_date?->toDateString(),
                'total' => (float) $invoice->total_amount,
                'customer_name' => $invoice->customer?->name,
            ],
            'paid' => $paid,
            'qris_data_uri' => $qrisDataUri,
            'qris_error' => $qrisError,
        ]);
    }

    private function qrDataUri(string $payload): ?string
    {
        try {
            $renderer = new ImageRenderer(
                new RendererStyle(720, 2),
                new SvgImageBackEnd,
            );

            $svg = (new Writer($renderer))->writeString(
                $payload,
                ecLevel: ErrorCorrectionLevel::M(),
            );

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        } catch (Throwable) {
            return null;
        }
    }
}
