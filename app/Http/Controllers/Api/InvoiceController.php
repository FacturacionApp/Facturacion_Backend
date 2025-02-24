<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Tax;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $invoices = Invoice::whereHas('company', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->with(['company', 'client', 'project',])->latest()->paginate(10);
        } else {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return InvoiceResource::collection($invoices);
    }

    public function show(Invoice $invoice)
    {
        $user = Auth::user();

        if ($invoice->company->user_id === $user->id) {
            $invoice->load([
                'company',
                'client',
                'project',
                'products.tax',
                'products.category',
            ]);

            return new InvoiceResource($invoice);
        } else {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
    }

    public function store(StoreInvoiceRequest $request)
    {
        $user = Auth::user();
        $validatedData = $request->validated();

        if ($user->cannot('create', [Invoice::class, $validatedData['company_id']])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $subtotal = 0;
        $tax_amount = 0;

        foreach ($validatedData['products'] as $productData) {
            $subtotal += $productData['price'] * $productData['quantity'];
            $tax = Tax::find($productData['tax_id']);
            $tax_amount += ($productData['price'] * $productData['quantity']) * ($tax->rate / 100);
        }

        $total = $subtotal + $tax_amount;
        $validatedData['subtotal'] = $subtotal;
        $validatedData['tax_amount'] = $tax_amount;
        $validatedData['total'] = $total;

        $invoice = Invoice::create($validatedData);

        foreach ($validatedData['products'] as $productData) {
            $productData['invoice_id'] = $invoice->id;
            Product::create($productData);
        }

        $invoice->load(['company', 'client', 'project', 'products.category', 'products.tax']);
        return new InvoiceResource($invoice);
    }


    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $user = Auth::user();
        if ($user->cannot('update', $invoice)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validatedData = $request->validated();
        $subtotal = 0;
        $tax_amount = 0;

        foreach ($validatedData['products'] as $productData) {
            $subtotal += $productData['price'] * $productData['quantity'];
            $tax = Tax::find($productData['tax_id']);
            $tax_amount += ($productData['price'] * $productData['quantity']) * ($tax->rate / 100);
        }

        $total = $subtotal + $tax_amount;
        $validatedData['subtotal'] = $subtotal;
        $validatedData['tax_amount'] = $tax_amount;
        $validatedData['total'] = $total;
        $validatedData['status'] = (string) $validatedData['status'];

        $invoice->update($validatedData);
        $invoice->products()->delete();

        foreach ($validatedData['products'] as $productData) {
            $productData['invoice_id'] = $invoice->id;
            Product::create($productData);
        }

        $invoice->load(['company', 'client', 'project', 'products.category', 'products.tax']);
        return new InvoiceResource($invoice);
    }

    public function destroy(Invoice $invoice)
    {
        $user = Auth::user();
        if ($user->cannot('delete', $invoice)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $invoice->delete();
        return response()->json(null, 204);
    }

    public function generatePdf(Invoice $invoice)
    {
        $user = Auth::user();
        $invoice->load(['company', 'client', 'project', 'products.category', 'products.tax']);

        if ($invoice->company->user_id != $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('invoices.pdf', compact('invoice'));
        return $pdf->download('factura_' . $invoice->id . '.pdf');
    }
}
