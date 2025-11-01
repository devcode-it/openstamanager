<?php

namespace Controllers;

use Spatie\RouteAttributes\Attributes\Any;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\JsonResponse;
use Spatie\RouteAttributes\Attributes\Where;

class APIForUIController extends PageController
{
    #[Any('/api-for-ui/{path}')]
    #[Where('path', '(.*)')]
    public function __invoke(Request $request, string $path = null)
    {
        $tokens = Auth::user()->getApiTokens();
        
        // Determine actual API path
        $url = str_replace("api-for-ui/", "",$request->fullUrl());
        
        // Create a new Guzzle client
        $client = new Client();

        // Get headers from the incoming request
        $incomingHeaders = $request->headers->all();

        // Format headers for Guzzle
        $formattedHeaders = [];
        foreach ($incomingHeaders as $key => $value) {
            $formattedHeaders[$key] = implode(', ', $value); // Convert array to string
        }
        
        // Attach API token for auth
        $formattedHeaders['X-API-Key'] = $tokens[0]['token'];

        try {
            // Make the GET request
            $response = $client->request($request->method(), $url, [
                'headers' => $formattedHeaders,
                'json' => $request->all(),
            ]);
            
            // Get the body of the response
            $body = $response->getBody();
            $data = json_decode($body, true); // Decode JSON to array

            // Return the response data
            return response()->json($data);
        } catch (RequestException $e) {
            // Handle different types of errors
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 500;
            return response()->json([
                'error' => $e->getMessage(),
                'status_code' => $statusCode,
            ], $statusCode);
        }
    }
}