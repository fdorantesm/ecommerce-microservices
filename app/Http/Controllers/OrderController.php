<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mpdf\Mpdf;
use GuzzleHttp\Client as Axios;
use App\Libraries\BarCode;
use Carbon\Carbon;
use Log;

class OrderController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Makes PDF receipt
     */
    public function getReceipt(Request $request, String $order)
    {
        $axios = new Axios();

        $params = [
            'with' => 'summary.product.files,payments,deliveries,customer'
        ];

        $queryString = http_build_query($params);

        
        $options['headers']['authorization'] = $request->access_token;
        
        try {
            $response = $axios->request('GET', env('API_HOST').'/orders/'.$order.'?'.$queryString, $options);
            $body = json_decode($response->getBody());
            $data = [
                "order" => $body->data
            ];
            if ($body->data->payments[0]->method === 'oxxo' || $body->data->payments[0]->method === 'spei') {
                $expirationDate = Carbon::parse($body->data->payments[0]->referenceExpiration)->setTimezone(env('APP_TIMEZONE'))->locale(env('APP_LOCALE'));
                $createdAt = Carbon::parse($body->data->createdAt)->setTimezone(env('APP_TIMEZONE'))->locale(env('APP_LOCALE'));
                $data["createdAt"] = ucfirst($createdAt->monthName)." {$createdAt->day}, {$createdAt->year} {$createdAt->format('g:i A')}";
                $data["expirationDate"] = ucfirst($expirationDate->monthName)." {$expirationDate->day}, {$expirationDate->year} {$expirationDate->format('g:i A')}";
                if ($body->data->payments[0]->method === 'spei') {
                    $response["spei"] = [
                        "clabe" => $body->data->payments[0]->clabe,
                        "bank" => $body->data->payments[0]->bank,
                        "receiving_account_number" => $body->data->payments[0]->receivingAccountNumber,
                        "receiving_account_bank" => $body->data->payments[0]->receivingAccountBank,
                        "expires_at" => Carbon::createFromTimestamp($body->data->payments[0]->referenceExpiration)->toDateTimeString()
                    ];
                } else {
                    $barcodeParams = ["text" => $body->data->payments[0]->reference];
                    $barcode = new BarCode($barcodeParams);
                    $data["barcode"] = $barcode->getImageUrl();
                }
            }
            
            $html = view('pdf.receipt', $data)->render();

            if ($request->has('html')) {
                return $html;
            } else {
                $mpdf = new \Mpdf\Mpdf();
                $mpdf->SetDisplayMode('fullpage');
                $mpdf->WriteHTML($html);
                if ($request->has('render')) {
                    $mpdf->Output();
                } else {
                    $mpdf->Output("order-{$body->data->_id}.pdf",'D');
                }
            }

        } catch (\Exception $e) {
            return response($e->getMessage());
        }

    }
}
