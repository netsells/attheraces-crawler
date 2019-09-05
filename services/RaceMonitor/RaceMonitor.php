<?php
/**
 *
 * Usage: run @method run($email = '') email is optional
 *
 */

namespace Services\RaceMonitor\RaceMonitor;

use App\Exceptions\InvalidRaceEmailException;
use App\Mail\RaceEmail;
use DOMXPath;
use DOMDocument;
use Illuminate\Support\Facades\Mail;

final class RaceMonitor
{

    protected $target;
    protected $email;

    private $message;

    public function __construct()
    {
        $this->email = config('races.email');
        $this->target = config('races.target-url');
    }

    /**
     * Crawls data from URL and emails to the provided email (if ATR Index gets greater thank 2000). Email is optional.
     *
     * @param string $email Optional, destination email
     * @return string
     * @throws InvalidRaceEmailException
     */
    public function run()
    {
        $this->validateEmail();

        $dom = $this->getDOM($this->target);
        $data = $this->getData($dom);
        $this->sendEmail($data);

        return $this->getMessage();
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     * Validates email
     *
     * @return bool
     * @throws InvalidRaceEmailException
     */
    private function validateEmail()
    {
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidRaceEmailException();
        }

        return true;
    }

    /**
     * Gets HTML DOM from URL
     *
     * @param string $url
     * @return DOMDocument
     */
    private function getDOM($url)
    {
        $resource = file_get_contents($url);

        $dom = new DOMDocument();

        libxml_use_internal_errors(true);
        $dom->loadHTML($resource);
        libxml_use_internal_errors(false);

        return $dom;
    }

    /**
     * Extracts data from HTML DOM
     *
     * @param DOMDocument $dom
     * @return array
     */
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

    /**
     * Sends an email if @method getData() returns array with data
     *
     * @param array $data
     */
    private function sendEmail($data)
    {
        if (!empty($data)) {
            Mail::to($this->email)->send(new RaceEmail($data));

            $this->message = 'An email was sent to ' . $this->email;

            return;
        }

        $this->message = 'No ATR Index data greater than 2000 was detected';
    }

    /**
     * Returns message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
