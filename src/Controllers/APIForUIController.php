<?php

namespace Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\RouteAttributes\Attributes\Any;
use Spatie\RouteAttributes\Attributes\Where;

class APIForUIController extends PageController
{
    #[Any('/api-for-ui/{path}')]
    #[Where('path', '(.*)')]
    public function __invoke(Request $request, ?string $path = null)
    {
        $tokens = Auth::user()->getApiTokens();

        // Determine actual API path
        $urlPieces = explode('/', $request->fullUrl());
        $apiForUIIndex = array_search('api-for-ui', $urlPieces);

        if ($apiForUIIndex === false) { // This should never happen due to the route definition
            return response()->json(['error' => 'Invalid API path'], 400);
        }

        $pathLength = count($urlPieces) - 1 - (int) $apiForUIIndex;

        // Only remove the api-for-ui part and keep the rest of the path
        $prefix = implode('/', array_slice($urlPieces, 0, $apiForUIIndex));
        array_splice($urlPieces, $apiForUIIndex, 1);
        $url = implode('/', $urlPieces);

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
            // Make the request
            $response = $client->request($request->method(), $url, [
                'headers' => $formattedHeaders,
                'json' => $request->all(),
            ]);

            // Get the body of the response
            $body = $response->getBody();
            if ($pathLength == 1 && str_ends_with($url, "/api")) {
                return str_replace("/vendor/api-platform", str_replace("/public", "", $prefix)."/assets/dist/apiplatform", $body);
            }

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
