<?php
namespace App\Helpers;

use App\Models\Client_propertiesModel;

class GeneralHelper
{
    public static function getaioResult($page_token, $engine = 'google_ai_overview')
    {
        try {
            $url = "https://www.searchapi.io/api/v1/search";
            $params = [
                "engine"     => $engine,
                "page_token" => $page_token,
                "api_key"    => env('AIO_TOKEN'),
                "location"   => 'India',
                "gl"         => 'in',
            ];

            return self::curlGet($url, $params);
        } catch (\Throwable $ex) {
            return json_encode(['error' => $ex->getMessage()]);
        }
    }
 
    public static function getDomainByClientPropertyId($client_property_id)
    {
        $clientProperty = Client_propertiesModel::find($client_property_id);
        return $clientProperty ? $clientProperty->domain : null;
    }

    /**
     * @param string $keyword   Search query
     * @param string $engine    e.g. 'google'
     * @param string $location  e.g. 'Delhi', 'Gurgaon' — maps to SearchAPI location string
     */
    public static function getSearchResult($keyword, $engine = 'google', $location = 'India')
    {
        try {
            // Map friendly location names → SearchAPI location strings
            $locationMap = [
                'Delhi'     => 'Delhi, India',
                'Gurgaon'   => 'Gurgaon, India',
                'Noida'     => 'Noida, India',
                'Faridabad' => 'Faridabad, India',
                'Ghaziabad' => 'Ghaziabad, India',
                'India'     => 'India',
            ];

            $resolvedLocation = $locationMap[$location] ?? $location;

            $url = "https://www.searchapi.io/api/v1/search";
            $params = [
                "engine"   => $engine,
                "q"        => $keyword,
                "api_key"  => env('AIO_TOKEN'),
                "location" => $resolvedLocation,
                "gl"       => 'in',
                "hl"       => 'en',
            ];

            return self::curlGet($url, $params);
        } catch (\Throwable $ex) {
            return json_encode(['error' => $ex->getMessage()]);
        }
    }

    // ── Shared cURL helper ────────────────────────────────────────────────────
    private static function curlGet(string $url, array $params): string
    {
        $queryString = http_build_query($params);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $url . '?' . $queryString,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CUSTOMREQUEST  => "GET",
            CURLOPT_HTTPHEADER     => ["accept: application/json"],
        ]);

        $response = curl_exec($curl);
        $error    = curl_error($curl);
        curl_close($curl);

        if ($error) {
            return json_encode(['error' => $error]);
        }

        return $response;
    }

    
}