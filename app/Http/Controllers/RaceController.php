<?php
/*
 *
 * TARGET = URL of the targeted resource;
 *
 * public index(email) - Returns a page and sends an email with data (if there is any data), email is required. Page will auto-refresh every minute
 * public api(email) - Returns a json string with data and sends an email (if there is any data). Email is optional.
 * private getDOM(url) - Returns HTML DOM from URL
 * private getData(dom) - Extracts the data from HTML DOM and returns empty array or array with formatted data.
 *
 *
 */

namespace App\Http\Controllers;

use App\Http\Resources\RaceResource;
use App\Mail\RaceEmail;
use DOMXPath;
use Illuminate\Support\Facades\Mail;

class RaceController extends Controller
{
    const TARGET = "https://www.attheraces.com/market-movers";

    public function index($email)
    {
        $dom = $this->getDOM(self::TARGET);
        $data = $this->getData($dom);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response('Invalid email', 403);
        }

        if (!empty($data)) {
            Mail::to($email)->send(new RaceEmail($data));
        }

        return view('get-data', ['race' => $data, 'email' => $email]);
    }

    public function api($email = '')
    {
        $dom = $this->getDOM(self::TARGET);
        $data = $this->getData($dom);

        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response('Invalid email', 403);
            }

            if (!empty($data)) {
                Mail::to($email)->send(new RaceEmail($data));
            }
        }

        return new RaceResource($data);
    }

    //------------------------------------------------------------------------------------------------------------------

    //get HTML DOM from URL
    private function getDOM($url)
    {
        $resource = file_get_contents($url);

        $dom = new \DOMDocument();

        libxml_use_internal_errors(true);
        $dom->loadHTML($resource);
        libxml_use_internal_errors(false);

        return $dom;
    }

    //extract data from HTML DOM
    private function getData($dom)
    {
        $xpath = new DOMXPath($dom);

        //extract HTML table data from DOM
        $x = 0;
        $rawData = [];

        foreach ($xpath->query('//tr') as $node) {
            $rowData = [];

            if ($x > 0) {
                foreach ($xpath->query('td', $node) as $cell) {
                    $rowData[] = $cell->nodeValue;
                }

                $rawData[] = $rowData;
            }

            //getting data from the first table only
            if ($x >= 10) {
                break;
            }

            $x++;
        }

        //extract and parse data
        $formattedData = [];

        foreach ($rawData as $parent) {
            //remove spaces, tabs and new lines
            $race = preg_replace("/\s+/", "", $parent[1]);

            //remove spaces, tabs, new lines, parentheses and everything what's between them
            $atrIndex = preg_replace(["/\s+/", "/\([^)]+\)/"], "", $parent[5]);

            //add data only if ATR Index is greater than 2000
            if ($atrIndex > 2000) {
                $formattedData[] = 'Race: ' . $race . '. ATR Index: ' . $atrIndex . '.';
            }
        }

        return $formattedData;
    }
}
