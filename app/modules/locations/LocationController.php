<?php
require_once __DIR__.'/../../core/BaseController.php';

class LocationController extends BaseController {
    
    public function getCountries() {
        try {
            // Get distinct countries from societies table
            $stmt = $this->db->query("
                SELECT DISTINCT country as name
                FROM societies 
                WHERE country IS NOT NULL AND country != ''
                ORDER BY country
            ");
            $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Transform to match expected format
            $result = [];
            foreach ($countries as $index => $country) {
                $result[] = [
                    'id' => $index + 1,
                    'name' => $country['name']
                ];
            }
            
            Response::success("Countries retrieved successfully", $result);
            
        } catch(Exception $e) {
            error_log("Get countries error: " . $e->getMessage());
            Response::error("Failed to retrieve countries: " . $e->getMessage(), 500);
        }
    }
    
    public function getCities() {
        try {
            // Get distinct cities from societies table
            $stmt = $this->db->query("
                SELECT DISTINCT city as name, country
                FROM societies 
                WHERE city IS NOT NULL AND city != ''
                ORDER BY country, city
            ");
            $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Transform to match expected format
            $result = [];
            foreach ($cities as $index => $city) {
                $result[] = [
                    'id' => $index + 1,
                    'name' => $city['name'],
                    'country' => $city['country']
                ];
            }
            
            Response::success("Cities retrieved successfully", $result);
            
        } catch(Exception $e) {
            error_log("Get cities error: " . $e->getMessage());
            Response::error("Failed to retrieve cities: " . $e->getMessage(), 500);
        }
    }
    
    public function getCitiesByCountry($countryName) {
        try {
            // Validate country name
            if (empty($countryName)) {
                Response::error("Country name is required", 400);
            }
            
            // Decode URL encoded country name
            $countryName = urldecode($countryName);
            
            // Get cities for the specified country from societies table
            $stmt = $this->db->prepare("
                SELECT DISTINCT city as name
                FROM societies 
                WHERE country = ? AND city IS NOT NULL AND city != ''
                ORDER BY city
            ");
            $stmt->execute([$countryName]);
            $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Transform to match expected format
            $result = [];
            foreach ($cities as $index => $city) {
                $result[] = [
                    'id' => $index + 1,
                    'name' => $city['name']
                ];
            }
            
            Response::success("Cities retrieved successfully", $result);
            
        } catch(Exception $e) {
            error_log("Get cities by country error: " . $e->getMessage());
            Response::error("Failed to retrieve cities: " . $e->getMessage(), 500);
        }
    }
}