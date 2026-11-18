<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Orders\CreatePrintOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePrintOrderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;

class PrintOrderController extends Controller
{
    /**
     * Create a public 3D print order and return its QRIS payment page.
     */
    public function __invoke(StorePrintOrderRequest $request, CreatePrintOrder $createOrder): JsonResponse
    {
        $invoice = $createOrder->handle($request->validated());

        return response()
            ->json([
                'invoice_number' => $invoice->number,
                'payment_url' => URL::signedRoute('payments.show', [
                    'invoice' => $invoice->number,
                ]),
                'total' => (float) $invoice->total_amount,
                'currency' => 'IDR',
            ], 201)
            ->header('Cache-Control', 'no-store');
    }
}
