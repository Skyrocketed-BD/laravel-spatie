<?php

namespace App\Http\Controllers\api\operation;

use App\Classes\PdfClass;
use App\Helpers\ActivityLogHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\Controller;
use App\Models\finance\BankNCash;
use App\Models\main\Kontak;
use App\Models\main\User;
use App\Models\operation\InvoiceFob;
use App\Models\operation\Kontraktor;
use App\Models\operation\ProvisionCoa;
use App\Models\operation\ShippingInstruction;
use Illuminate\Support\Str;

class PrintPDFController extends Controller
{
    public function invoice_provision(Request $request)
    {
        $no_invoice = $request->inv_number;

        $data       = [];
        $amount     = [];
        $total_paid = 0;

        $nowUtc         = Carbon::now();
        $nowGmt8        = $nowUtc->setTimezone('Asia/Singapore');
        $printed_date   = $nowGmt8->toDateTimeString();
        $company        = get_arrangement('company_name');
        $username       = User::find(auth()->id())->name ?? 'invalid user';
        $query          = ProvisionCoa::with(['toProvision.toShippingInstruction.toKontraktor'])->where('no_invoice', $no_invoice)->first();
        // $query          = ProvisionCoa::where('no_invoice', $no_invoice)->();
        // dd($query);
        if ($query) {
            $data = [
                'title'             => 'INVOICE',
                'company'           => $company,
                'invoice_number'    => $query->no_invoice,
                'date'              => $query->date,
                'receipent'         => $query->toProvision->toShippingInstruction->toKontraktor->company,
                'details'           => [
                    'description'   => $query->description,
                    'harga'         => rupiah($query->toProvision->selling_price),
                    'tonage'        => $query->tonage_final, // . ' MT',
                    'total_harga'   => rupiah($query->price),
                    'tagihan'       => rupiah($query->price),
                ],
                'terbilang'         => _terbilang($query->price),
                'rekening'          => '151-00-1337369-8',
                'bank'              => 'Mandiri an PT Sumber Warna Pratama',
                'mining_inspector'  => $query->toProvision->toShippingInstruction->mining_inspector,
                'printed_date'      => $printed_date,
                'printed_by'        => $username,
            ];

            // $pdfOutput = PdfClass::view($data['title'], 'operation.invoice', 'A4', 'potrait', $data);
            $pdfOutput = PdfClass::print($data['title'], 'operation.invoice', 'A4', 'potrait', $data);

            $fileName = $data['invoice_number'] . '-' . now()->format('YmdHis') . '.pdf';

            ActivityLogHelper::log('finance:invoice_provision_print', 1, [
                'finance:invoice_number' => $data['invoice_number'],
            ]);

            return response($pdfOutput, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename=' . $fileName);
        } else {
            return Response::json(['success' => false, 'message' => 'Transaction Number Not Found!'], 400);
        }
    }

    public function shipping_instruction(Request $request)
    {
        $si_number  = $request->si_number;

        $data       = [];
        $amount     = [];

        $nowUtc         = Carbon::now();
        $nowGmt8        = $nowUtc->setTimezone('Asia/Singapore');
        $printed_date   = $nowGmt8->toDateTimeString();
        $company        = get_arrangement('company_name');
        $username       = User::find(auth()->id())->name ?? 'invalid user';
        $query          = ShippingInstruction::with(['toSlot', 'toKontraktor'])->where('number_si', $si_number)->first();

        if ($query) {
            $data = [
                'title'             => 'SHIPPING INSTRUCTION (SI)',
                'company'           => $company,
                'receipent'         => $query->toKontraktor->company,
                'surveyor'          => $query->surveyor,
                'kontraktor'        => $query->toKontraktor->company,
                'si_number'         => $query->number_si,
                'date'              => $query->created_at,
                'additional_note'   => $query->information,
                'details'           => [
                    'shipper'       => $company,
                    'consignee'     => $query->consignee,
                    'notify_party'  => $query->notify_party,
                    'commodity'     => 'Nickel Ore',
                    'transport_mode' => $query->tug_boat . ' - ' . $query->barge,
                    'loading_port'  => $query->loading_port,
                    'discharge_port' => $query->unloading_port,
                    'cargo_quantity' => $query->load_amount . ' MT ' . html_entity_decode("&plusmn;") . "10%",
                    'start_date'    => Carbon::parse($query->load_date_start)->format('d-M-Y'),
                    'end_date'      => Carbon::parse($query->load_date_finish)->format('d-M-Y'),
                ],
                'mining_inspector'  => $query->mining_inspector,
                'printed_date'      => $printed_date,
                'printed_by'        => $username,
            ];

            // $pdfOutput = PdfClass::view($data['title'], 'operation.shipping-instruction', 'A4', 'potrait', $data);
            $pdfOutput = PdfClass::print($data['title'], 'operation.shipping-instruction', 'A4', 'potrait', $data);

            $fileName = $data['si_number'] . '-' . now()->format('YmdHis') . '.pdf';

            ActivityLogHelper::log('operation:shipping_instruction_print', 1, [
                'operation:shipping_instruction_number'     => $data['si_number'],
            ]);

            return response($pdfOutput, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename=' . $fileName);
        } else {
            return Response::json(['success' => false, 'message' => 'SI Number Not Found!'], 404);
        }
    }

    public function invoiceBill(Request $request)
    {
        if (isset($request->lang)) {
            if ($request->lang == 'id') {
                return $this->invoiceBillID($request);
            } else {
                return $this->invoiceBillEN($request);
            }
        } else {
            return $this->invoiceBillID($request);
        }
    }

    public function invoiceBillID(Request $request)
    {
        $inv_number     = $request->inv_number;
        $req_termin     = $request->termin;

        $data           = [];
        $amount         = [];

        $nowUtc         = Carbon::now();
        $nowGmt8        = $nowUtc->setTimezone('Asia/Singapore');
        $printed_date   = $nowGmt8->toDateTimeString();
        $company        = get_arrangement('company_name');
        $title          = 'INVOICE';
        $username       = User::find(auth()->id())->name ?? 'invalid user';
        $query          = InvoiceFob::with(['toTransaction', 'toPlanBarging.toShippingInstruction.toProvision.toProvisionCoa'])->where('transaction_number', $inv_number)->first();

        $buyer_name     = NULL;
        $buyer_address  = NULL;
        $buyer_contract = NULL;

        $banks          = BankNCash::where('show', 'y')->get();
        $transport_vessel = NULL;
        $shipping_instruction = NULL;
        $shipping_status = 0;

        if ($query) {
            $get_buyer = Kontak::with('toKontrak:id_kontrak,no_kontrak')
                ->select('id_kontak', 'id_kontrak', 'name', 'address')
                ->where('id_kontak', $query->id_kontak)
                ->first();;

            if ($get_buyer) {
                $buyer_name     = $get_buyer->name;
                $buyer_address  = $get_buyer->address;
                $buyer_contract = $get_buyer->toKontrak->no_kontrak ?? NULL;
            }

            $get_termins    = $query->toTransaction->toTransactionTerm;

            if (!empty($request->termin)) {
                $req_termin = $request->termin;
            }

            $termin_name    = '';
            $termin_percent = 0;
            $termin_pph     = 0;
            $termin_ppn     = 0;
            $dpp            = 0;
            $dpp_lain       = 0;
            $pph            = 0;
            $ppn_dibebaskan = 0;
            $total_invoice  = $query->toTransaction->value;
            $sisa_tagihan   = 0;
            $total_termin   = 0;

            $pre_payment_type   = null;
            $pre_payment        = 0;

            $total_payment  = $query->price;
            $termins        = [];
            $termin_details = [];
            $receipts       = [];
            $details        = [];

            $query_transport_vessel = $query->toPlanBarging->toShippingInstruction;

            if ($query_transport_vessel) {
                $transport_vessel = $query_transport_vessel->tug_boat . ' - ' . $query_transport_vessel->barge;
                $shipping_instruction = $query_transport_vessel->number_si;
                $shipping_status = $query_transport_vessel->status;
            }

            if ($get_termins) {
                $termin_details = [];
                $total_termin   = 0;
                $total_termins  = count($get_termins);
                $counter        = 1;

                foreach ($get_termins as $termin) {
                    // Jika request mencapai akhir (final termin) dan status = 5, maka jangan tampilkan final termin
                    // if ($req_termin == $total_termins && $shipping_status == 5 && $counter == $total_termins) {
                    //     break;
                    // }

                    $termin_details[] = [
                        'name'          => $termin->nama,
                        'value_percent' => locale_currency($termin->value_percent, 'ID'),
                        'value_int'     => $termin->value_percent,
                    ];
                    $total_termin += $termin->value_percent;

                    // deposit
                    if ($termin->deposit) {
                        if ($termin->deposit == 'advance_payment') {
                            $pre_payment_type = 'Penggunaan Saldo Deposit';
                        } else {
                            $pre_payment_type = 'Penggunaan Saldo Uang Muka';
                        }
                    }

                    if ($termin->value_deposit) {
                        $pre_payment        = $termin->value_deposit;
                    }

                    // Stop ketika sudah mencapai termin yang diminta
                    if ($counter == $req_termin) {
                        break;
                    }

                    $counter++;
                }
            }

            if ($query->toTransaction->toReceipts) {
                foreach ($query->toTransaction->toReceipts->where('status', 'valid') as $key => $receipt) {
                    $index = $key;
                    $termin_name = '';
                    //mencegah anomali pembayaran lebih banyak daripada termin??
                    // if ($key > count($termins)-1) {
                    //     $index = count($termins)-1;
                    // }

                    if ($key > count($termin_details) - 1) {
                        // $index = count($termin_details)-1;
                        $termin_name = 'FINAL INVOICE';
                    } else {
                        $termin_name = $termin_details[$index]['name'];
                    }

                    $receipts[] = [
                        'date'       => $receipt->date,
                        'pnm_number' => $receipt->transaction_number,
                        'value'      => locale_currency($receipt->value, 'ID'),
                        // 'termin'     => $termin_name,
                        'termin'     => $receipt->description,
                    ];
                }
            }

            // dd($receipts);
            // if ($shipping_status == 5 && ( ($req_termin == count($get_termins) || $req_termin > count($get_termins)) )) {
            Carbon::setLocale('id');
            if (($shipping_status == 5 || $shipping_status == 6) && !isset($req_termin)) {
                $title = 'FINAL INVOICE';
                $provision = $query->toPlanBarging->toShippingInstruction->toProvision->toProvisionCoa[0];
                $details = [
                    // 'buyer'         => $buyer_name,
                    'ni'            => $provision->ni_final,
                    'mc'            => $provision->mc_final,
                    'cf'            => ($provision->ni_final * 10) + 1,
                    'cargo'         => locale_number($provision->tonage_final),
                    'hma'           => locale_currency($provision->hma, 'USD', 2),
                    'hma_date'      => Carbon::parse($query->date)->translatedFormat('F Y'),
                    // 'hma_date'      => Carbon::parse($provision->date)->translatedFormat('F Y'),
                    'hpm'           => locale_currency($provision->hpm, 'USD', 2),
                    'kurs'          => locale_currency($provision->kurs, 'ID'),
                    'hpm_idr'       => locale_currency($provision->kurs * $provision->hpm, 'ID'),
                    'price'         => locale_currency($provision->price, 'ID'),
                ];
                //hitung dpp;
                $dpp = $total_invoice - $total_termin;
                $dpp_lain = round($dpp * 11 / 12);
                $ppn_dibebaskan = ($dpp * 11 / 100);
                $pph = floor($dpp * 1.5 / 100);
                $sisa_tagihan = ($dpp - $pph);
                $termins = [
                    'details'       => $termin_details,
                    'dpp'           => locale_currency($dpp, 'IDR', 0),
                    'dpp_lain'      => locale_currency($dpp_lain, 'IDR', 0),
                    'ppn_dibebaskan' => locale_currency($ppn_dibebaskan, 'IDR', 0),
                    'pph'           => locale_currency($pph, 'IDR', 0),
                    'sisa_tagihan'  => locale_currency($sisa_tagihan, 'IDR', 0)
                ];
            } else {

                $count = count($termin_details);
                if ($req_termin > $count) {
                    $req_termin = $count;
                }
                // $title = $this->ordinalSuffix($req_termin) . ' INVOICE';
                $title = '#' . $req_termin . ' INVOICE';
                $details = [
                    // 'buyer'         => $query->buyer_name,
                    'ni'            => $query->ni,
                    'mc'            => $query->mc,
                    // 'mc'            => $query->toPlanBarging->mc,
                    'cf'            => ($query->ni * 10) + 1,
                    'cargo'         => locale_number($query->tonage),
                    // 'cargo'         => locale_number($query->toPlanBarging->tonage),
                    'hma'           => locale_currency($query->hma, 'USD', 2),
                    'hma_date'      => Carbon::parse($query->date)->translatedFormat('F Y'),
                    'hpm'           => locale_currency($query->hpm, 'USD', 2),
                    'kurs'          => locale_currency($query->kurs, 'ID'),
                    'hpm_idr'       => locale_currency($query->kurs * $query->hpm, 'ID'),
                    'price'         => locale_currency($query->price, 'ID'),
                ];
                //hitung dpp
                $dpp = $termin_details[$req_termin - 1]['value_int'];
                $dpp_lain = round($dpp * 11 / 12);
                $ppn_dibebaskan = ($dpp * 11 / 100);
                $pph = floor($dpp * 1.5 / 100);
                $sisa_tagihan = ($dpp - $pph);
                $termins = [
                    'details'       => $termin_details,
                    'dpp'           => locale_currency($dpp, 'ID', 0),
                    'dpp_lain'      => locale_currency($dpp_lain, 'ID', 0),
                    'ppn_dibebaskan' => locale_currency($ppn_dibebaskan, 'ID', 0),
                    'pph'           => locale_currency($pph, 'ID', 0),
                    'sisa_tagihan'  => locale_currency($sisa_tagihan, 'ID', 0)
                ];
            }

            $data = [
                'title'             => $title,
                'company'           => $company,
                'receipent'         => $buyer_name,
                'receipent_address' => $buyer_address,
                'receipent_contract' => $buyer_contract,
                'date'              => Carbon::parse($query->date)->translatedFormat('d F Y'),
                'description'       => $query->description,
                'invoice_number'    => $query->transaction_number,
                'transport_vessel'  => $transport_vessel,
                'shipping_instruction' => $shipping_instruction,
                'details'           => $details,
                'receipts'          => $receipts,
                'termins'           => $termins,
                // 'total_payment'     => locale_currency($sisa_tagihan),
                'banks'             => $banks,
                'terbilang'         => _numberToWords('id', $sisa_tagihan) . ' Rupiah',

                'pre_payment_type'  => $pre_payment_type,
                'pre_payment'       => locale_currency($pre_payment, 'ID'),

                'printed_date'      => $printed_date,
                'printed_by'        => $username,
            ];
            // $pdfOutput = PdfClass::view($data['title'], 'operation.invoice-bill-id', 'A4', 'potrait', $data);
            $pdfOutput = PdfClass::print($data['title'], 'operation.invoice-bill-id', 'A4', 'potrait', $data);

            $fileName = $data['invoice_number'] . '-' . now()->format('YmdHis') . '.pdf';

            ActivityLogHelper::log('finance:invoice_bill_print', 1, [
                'finance:invoice_number' => $data['invoice_number'],
            ]);

            return response($pdfOutput, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename=' . $fileName);
        } else {
            return Response::json(['success' => false, 'message' => 'Invoice Number Not Found!'], 404);
        }
    }

    public function invoiceBillEN(Request $request)
    {
        $inv_number     = $request->inv_number;
        $req_termin     = $request->termin;

        $data           = [];
        $amount         = [];

        $nowUtc         = Carbon::now();
        $nowGmt8        = $nowUtc->setTimezone('Asia/Singapore');
        $printed_date   = $nowGmt8->toDateTimeString();
        $company        = get_arrangement('company_name');
        $title          = 'INVOICE';
        $username       = User::find(auth()->id())->name ?? 'invalid user';
        $query          = InvoiceFob::with(['toTransaction', 'toPlanBarging.toShippingInstruction.toProvision.toProvisionCoa'])->where('transaction_number', $inv_number)->first();

        $buyer_name     = NULL;
        $buyer_address  = NULL;
        $buyer_contract = NULL;

        $banks          = BankNCash::where('show', 'y')->get();
        $transport_vessel = NULL;
        $shipping_instruction = NULL;
        $shipping_status = 0;

        if ($query) {
            $get_buyer = Kontak::with('toKontrak:id_kontrak,no_kontrak')
                ->select('id_kontak', 'id_kontrak', 'name', 'address')
                ->where('id_kontak', $query->id_kontak)
                ->first();;

            if ($get_buyer) {
                $buyer_name     = $get_buyer->name;
                $buyer_address  = $get_buyer->address;
                $buyer_contract = $get_buyer->toKontrak->no_kontrak ?? NULL;
            }

            $get_termins    = $query->toTransaction->toTransactionTerm;

            if (!empty($request->termin)) {
                $req_termin = $request->termin;
            }

            $termin_name    = '';
            $termin_percent = 0;
            $termin_pph     = 0;
            $termin_ppn     = 0;
            $dpp            = 0;
            $dpp_lain       = 0;
            $pph            = 0;
            $ppn_dibebaskan = 0;
            $total_invoice  = $query->toTransaction->value;
            $sisa_tagihan   = 0;
            $total_termin   = 0;

            $pre_payment_type   = null;
            $pre_payment        = 0;

            $total_payment  = $query->price;
            $termins        = [];
            $termin_details = [];
            $receipts       = [];
            $details        = [];

            $query_transport_vessel = $query->toPlanBarging->toShippingInstruction;

            if ($query_transport_vessel) {
                $transport_vessel = $query_transport_vessel->tug_boat . ' - ' . $query_transport_vessel->barge;
                $shipping_instruction = $query_transport_vessel->number_si;
                $shipping_status = $query_transport_vessel->status;
            }

            if ($get_termins) {
                $termin_details = [];
                $total_termin   = 0;
                $total_termins  = count($get_termins);
                $counter        = 1;

                foreach ($get_termins as $termin) {
                    $termin_details[] = [
                        'name'          => $termin->nama,
                        'value_percent' => locale_currency($termin->value_percent, 'IDR'),
                        'value_int'     => $termin->value_percent,
                    ];
                    $total_termin += $termin->value_percent;

                    // deposit
                    if ($termin->deposit) {
                        if ($termin->deposit == 'advance_payment') {
                            $pre_payment_type = 'Deposit Saldo';
                        } else {
                            $pre_payment_type = 'Down Payment Saldo';
                        }
                    }

                    if ($termin->value_deposit) {
                        $pre_payment        = $termin->value_deposit;
                    }

                    if ($counter == $req_termin) {
                        break;
                    }

                    $counter++;
                }
            }

            if ($query->toTransaction->toReceipts) {
                foreach ($query->toTransaction->toReceipts->where('status', 'valid') as $key => $receipt) {
                    $index = $key;
                    $termin_name = '';

                    if ($key > count($termin_details) - 1) {
                        $termin_name = 'FINAL INVOICE';
                    } else {
                        $termin_name = $termin_details[$index]['name'];
                    }

                    $receipts[] = [
                        'date'       => $receipt->date,
                        'pnm_number' => $receipt->transaction_number,
                        'value'      => locale_currency($receipt->value, 'IDR'),
                        'termin'     => $receipt->description,
                    ];
                }
            }

            // dd($receipts);
            if (($shipping_status == 5 || $shipping_status == 6) && !isset($req_termin)) {
                $title = 'FINAL INVOICE';
                $provision = $query->toPlanBarging->toShippingInstruction->toProvision->toProvisionCoa[0];
                $details = [
                    // 'buyer'         => $buyer_name,
                    'ni'            => $provision->ni_final,
                    'mc'            => $provision->mc_final,
                    'cf'            => ($provision->ni_final * 10) + 1,
                    'cargo'         => locale_number($provision->tonage_final),
                    'hma'           => locale_currency($provision->hma, 'USD', 2),
                    'hma_date'      => Carbon::parse($query->date)->translatedFormat('F Y'),
                    // 'hma_date'      => Carbon::parse($provision->date)->translatedFormat('F Y'),
                    'hpm'           => locale_currency($provision->hpm, 'USD', 2),
                    'kurs'          => locale_currency($provision->kurs, 'IDR'),
                    'hpm_idr'       => locale_currency($provision->kurs * $provision->hpm, 'IDR'),
                    'price'         => locale_currency($provision->price, 'IDR'),
                ];
                //hitung dpp;
                $dpp = $total_invoice - $total_termin;
                $dpp_lain = round($dpp * 11 / 12);
                $ppn_dibebaskan = ($dpp * 11 / 100);
                $pph = floor($dpp * 1.5 / 100);
                $sisa_tagihan = ($dpp - $pph);
                $termins = [
                    'details'       => $termin_details,
                    'dpp'           => locale_currency($dpp, 'IDR', 0),
                    'dpp_lain'      => locale_currency($dpp_lain, 'IDR', 0),
                    'ppn_dibebaskan' => locale_currency($ppn_dibebaskan, 'IDR', 0),
                    'pph'           => locale_currency($pph, 'IDR', 0),
                    'sisa_tagihan'  => locale_currency($sisa_tagihan, 'IDR', 0)
                ];
            } else {

                $count = count($termin_details);
                if ($req_termin > $count) {
                    $req_termin = $count;
                }
                // $title = $this->ordinalSuffix($req_termin) . ' INVOICE';
                $title = '#' . $req_termin . ' INVOICE';
                $details = [
                    // 'buyer'         => $query->buyer_name,
                    'ni'            => $query->ni,
                    'mc'            => $query->mc,
                    // 'mc'            => $query->toPlanBarging->mc,
                    'cf'            => ($query->ni * 10) + 1,
                    'cargo'         => locale_number($query->tonage),
                    // 'cargo'         => locale_number($query->toPlanBarging->tonage),
                    'hma'           => locale_currency($query->hma, 'USD', 2),
                    'hma_date'      => Carbon::parse($query->date)->translatedFormat('F Y'),
                    'hpm'           => locale_currency($query->hpm, 'USD', 2),
                    'kurs'          => locale_currency($query->kurs, 'IDR'),
                    'hpm_idr'       => locale_currency($query->kurs * $query->hpm, 'IDR'),
                    'price'         => locale_currency($query->price, 'IDR'),
                ];
                //hitung dpp
                $dpp = $termin_details[$req_termin - 1]['value_int'];
                $dpp_lain = round($dpp * 11 / 12);
                $ppn_dibebaskan = ($dpp * 11 / 100);
                $pph = floor($dpp * 1.5 / 100);
                $sisa_tagihan = ($dpp - $pph);
                $termins = [
                    'details'       => $termin_details,
                    'dpp'           => locale_currency($dpp, 'IDR', 0),
                    'dpp_lain'      => locale_currency($dpp_lain, 'IDR', 0),
                    'ppn_dibebaskan' => locale_currency($ppn_dibebaskan, 'IDR', 0),
                    'pph'           => locale_currency($pph, 'IDR', 0),
                    'sisa_tagihan'  => locale_currency($sisa_tagihan, 'IDR', 0)
                ];
            }

            $data = [
                'title'             => $title,
                'company'           => $company,
                'receipent'         => $buyer_name,
                'receipent_address' => $buyer_address,
                'receipent_contract' => $buyer_contract,
                'date'              => Carbon::parse($query->date)->translatedFormat('d F Y'),
                'description'       => $query->description,
                'invoice_number'    => $query->transaction_number,
                'transport_vessel'  => $transport_vessel,
                'shipping_instruction' => $shipping_instruction,
                'details'           => $details,
                'receipts'          => $receipts,
                'termins'           => $termins,
                // 'total_payment'     => locale_currency($sisa_tagihan),
                'banks'             => $banks,
                'terbilang'         => _numberToWords('id', $sisa_tagihan) . ' Rupiah',

                'pre_payment_type'  => $pre_payment_type,
                'pre_payment'       => locale_currency($pre_payment, 'IDR'),

                'printed_date'      => $printed_date,
                'printed_by'        => $username,
            ];
            // $pdfOutput = PdfClass::view($data['title'], 'operation.invoice-bill-en', 'A4', 'potrait', $data);
            $pdfOutput = PdfClass::print($data['title'], 'operation.invoice-bill-en', 'A4', 'potrait', $data);

            $fileName = $data['invoice_number'] . '-' . now()->format('YmdHis') . '.pdf';

            ActivityLogHelper::log('finance:invoice_bill_print', 1, [
                'finance:invoice_number' => $data['invoice_number'],
            ]);

            return response($pdfOutput, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename=' . $fileName);
        } else {
            return Response::json(['success' => false, 'message' => 'Invoice Number Not Found!'], 404);
        }
    }

    function ordinalSuffix($number): string
    {
        if (in_array($number % 100, [11, 12, 13])) {
            return $number . 'th';
        }

        return match ($number % 10) {
            1 => $number . 'st',
            2 => $number . 'nd',
            3 => $number . 'rd',
            default => $number . 'th',
        };
    }

    function formatBulanTahunIndonesia($tanggal)
    {
        Carbon::setLocale('id');
        return Carbon::parse($tanggal)->translatedFormat('F Y');
    }
}
