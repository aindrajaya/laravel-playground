<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

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

 

    public function fetchHtml(Request $request)
    {
        $url = $request->input('url');

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            error_log('Invalid URL provided: ' . $url);
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid URL',
            ]);
        }

        $lowerUrl = strtolower($url);
        $regex = strpos($lowerUrl, 'wikipedia.org') !== false ?
                '/(\.css|only=styles).*?$/i' :  
                '/\.css(\?(?!AUIClients).*)?$/';

        $htmlContent = null;
        $usedGuzzle = false; 

        try {
            $client = new Client();
            $response = $client->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                ],
            ]);

            $htmlContent = $response->getBody()->getContents();
            Log::info('HTML CONTENT: ' . $htmlContent); 
            $usedGuzzle = true; 
            Log::info('Guzzle used successfully for URL: ' . $url); 
        } catch (\Exception $e) {
            $usedGuzzle = false;
            Log::error('Guzzle failed: ' . $e->getMessage() . ' for URL: ' . $url); 
        }

        if ($htmlContent === null) {
            error_log('Falling back to file_get_contents for URL: ' . $url); 
            $contextOptions = [
                "http" => [
                    "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
                ]
            ];

            $context = stream_context_create($contextOptions);
            $htmlContent = @file_get_contents($url, false, $context);
        }

        if ($htmlContent !== false) {
            // $htmlContent = $this->removeScriptTags($htmlContent);
            $dom = new \DOMDocument();
            @$dom->loadHTML($htmlContent);

            $links = $dom->getElementsByTagName('link');
            foreach ($links as $link) {
                // if ($link->getAttribute('rel') == 'stylesheet' && preg_match('/\.css$/', $link->getAttribute('href'))) {
                if ($link->getAttribute('rel') == 'stylesheet' && preg_match($regex, $link->getAttribute('href'))) {
                    $usedGuzzle = false;
                    Log::error('Guzzle failed: ' . 'because css link not found ' . $usedGuzzle . ' for URL: ' . $url);
                    $cssUrl = $link->getAttribute('href');

                    // Resolve relative URLs
                    if (!parse_url($cssUrl, PHP_URL_SCHEME)) {
                        $cssUrl = $this->resolveRelativeUrl($url, $cssUrl);
                        Log::info('CSS Url Detected: ' . $cssUrl . ' for this link: ' . $url);
                    }

                    if (!$usedGuzzle) {
                        $cssContent = @file_get_contents($cssUrl, false, $context);
                        if ($cssContent !== false) {
                            $styleElement = $dom->createElement('style', htmlspecialchars($cssContent));
                            $styleElement->setAttribute('type', 'text/css');

                            $head = $dom->getElementsByTagName('head')->item(0);
                            if ($head) {
                                $head->appendChild($styleElement);
                            }

                            $link->parentNode->removeChild($link);
                        } else {
                            error_log('Failed to retrieve CSS file: ' . $cssUrl);
                            return response()->json([
                                'status' => 'error',
                                'message' => "Failed to retrieve CSS file: $cssUrl",
                            ]);
                        }
                    }
                }
            }

            if (!$usedGuzzle) {
                error_log('Returning modified HTML content for URL: ' . $url);
                return response()->json([
                    'status' => 'success',
                    'html' => $dom->saveHTML(),
                ]);
            } else {
                error_log('Returning original HTML content retrieved by Guzzle for URL: ' . $url);
                return response()->json([
                    'status' => 'success',
                    'html' => $htmlContent,
                ]);
            }
        } else {
            error_log('Failed to retrieve any content for URL: ' . $url);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve content using both Guzzle and file_get_contents.',
            ]);
        }
    }

    public function removeScriptTags($htmlContent) {
        $dom = new \DOMDocument();
        @$dom->loadHTML($htmlContent, LIBXML_NOWARNING | LIBXML_NOERROR);
    
        $scriptTags = $dom->getElementsByTagName('script');
    
        while ($scriptTags->length > 0) {
            $script = $scriptTags->item(0);
            $script->parentNode->removeChild($script);
        }
    
        return $dom->saveHTML();
    }

    private function resolveRelativeUrl($baseUrl, $relativeUrl)
    {
        $baseParts = parse_url($baseUrl);

        if (strpos($relativeUrl, '/') === 0) {
            return $baseParts['scheme'] . '://' . $baseParts['host'] . $relativeUrl;
        }

        $path = $baseParts['path'];
        $path = preg_replace('/\/[^\/]*$/', '', $path);

        $combinedPath = $this->normalizePath($path . '/' . $relativeUrl);

        return $baseParts['scheme'] . '://' . $baseParts['host'] . $combinedPath;
    }

    private function normalizePath($path)
    {
        $parts = array_filter(explode('/', $path), function($part) {
            return $part !== '' && $part !== '.';
        });
        $absolutes = [];
        foreach ($parts as $part) {
            if ($part === '..') {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return '/' . implode('/', $absolutes);
    }


    public function fetchHtmlWithGuzzle(Request $request)
    {
        $url = $request->input('url');

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $client = new Client();
            try {
                $response = $client->request('GET', $url, [
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    ],
                ]);

                $htmlContent = $response->getBody()->getContents();

                return response()->json([
                    'status' => 'success',
                    'html' => $htmlContent,
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to retrieve content: ' . $e->getMessage(),
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid URL',
            ]);
        }
    }
    public function fetchHtmlWithFileGetContents(Request $request)
    {
        $url = $request->input('url');
    
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $contextOptions = [
                "http" => [
                    "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
                ]
            ];
    
            $context = stream_context_create($contextOptions);
            $htmlContent = @file_get_contents($url, false, $context);
    
            if ($htmlContent !== false) {
                $dom = new \DOMDocument();
                @$dom->loadHTML($htmlContent); 
                    
                $links = $dom->getElementsByTagName('link');
                foreach ($links as $link) {
                    if ($link->getAttribute('rel') == 'stylesheet') {
                        $cssUrl = $link->getAttribute('href');
    
                        if (!parse_url($cssUrl, PHP_URL_SCHEME)) {
                            $cssUrl = rtrim($url, '/') . '/' . ltrim($cssUrl, '/');
                        }
    
                        $cssContent = @file_get_contents($cssUrl, false, $context);
                        if ($cssContent !== false) {
                            $styleElement = $dom->createElement('style', $cssContent);
                            $styleElement->setAttribute('type', 'text/css');
                                
                            $head = $dom->getElementsByTagName('head')->item(0);
                            $head->appendChild($styleElement);
    
                            $link->parentNode->removeChild($link);
                        } else {
                            return response()->json([
                                'status' => 'error',
                                'message' => "Failed to retrieve CSS file: $cssUrl",
                            ]);
                        }
                    }
                }
    
                return response()->json([
                    'status' => 'success',
                    'html' => $dom->saveHTML(),
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to retrieve content using file_get_contents.',
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid URL',
            ]);
        }
    }
}
