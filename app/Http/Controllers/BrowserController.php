<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BrowserController extends Controller
{
    public function index()
    {
        return view('browser.simulator');
    }

    public function list()
    {
        return view('browser.list-simulator');
    }

    public function simulateBrowser(Request $request)
    {
        // $url = $request->input('url');

        // // Call the Lambda function via API Gateway
        // $response = Http::post('https://your-api-gateway-endpoint.amazonaws.com/default/your-lambda-function', [
        //     'url' => $url
        // ]);

        // if ($response->successful()) {
        //     $data = $response->json();
        //     return view('browser.simulator', ['content' => $data['content']]);
        // }

        return view('browser.simulator');

        // return back()->withErrors(['error' => 'Failed to simulate browser']);
    }
}
