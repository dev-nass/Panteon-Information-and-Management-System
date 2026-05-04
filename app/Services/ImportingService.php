<?php

namespace App\Services;
class ImportingService
{

    /**
     * Description: Separate the fullname from excel to f_name and l_name
     * @param string $fullName
     * @return array{first_name: mixed, last_name: mixed, middle_name: null|array{first_name: mixed, last_name: null, middle_name: null}|array{first_name: null, last_name: null, middle_name: null}}
     */
    private function parseFullName($fullName)
    {
        $parts = preg_split('/\s+/', trim($fullName));
        $count = count($parts);

        if ($count === 0) {
            return ['first_name' => null, 'middle_name' => null, 'last_name' => null];
        } elseif ($count === 1) {
            return ['first_name' => $parts[0], 'middle_name' => null, 'last_name' => null];
        } else {
            // First name and last name only, disregard middle name
            $firstName = array_shift($parts);
            $lastName = array_pop($parts);
            return ['first_name' => $firstName, 'middle_name' => null, 'last_name' => $lastName];
        }
    }


    /**
     * Description: Convert excel date to Y-m-d
     * @param $date
     * @return string|null
     */
    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            // Try to parse Excel date format
            if (is_numeric($date)) {
                $unixDate = ($date - 25569) * 86400;
                return date('Y-m-d', $unixDate);
            }

            // Try standard date formats
            $timestamp = strtotime($date);
            if ($timestamp === false) {
                return null;
            }
            return date('Y-m-d', $timestamp);
        } catch (\Exception $e) {
            return null;
        }
    }
}