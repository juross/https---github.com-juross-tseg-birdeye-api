<?php
    /*
     *  Plugin Name: TSEG BirdEye API
     *  Description: Gets reviews from BirdEye. More specifically, this plugin defines helper functions which can be used in PHP templates to make a API calls to BirdEye and retrieve review data.
     *  Version: 2017.05.15
     *  Author: The Search Engine Guys
     *  Author URI: http://www.thesearchengineguys.com/
     */

    require 'plugin_settings.php';

    /**
     * Gets reviews from BirdEYE 2.
     *
     * View the birdeye API documentation at http://docs.birdeye.apiary.io/
     *
     * @param array $settings
     * @author Travis Hohl <thohl@cloud816.com>
     * @return array|string Returns an array of reviews on success, or an error message on failure.
     */
    function tseg_get_reviews($user_settings = [])
    {

        $default_settings = [
            'api_endpoint' => [
                'protocol' => 'https:',
                'hostname' => 'api.birdeye.com',
                'port' => '',
                'pathname' => '/resources/v1/review/businessid/' . get_option('birdeye_api_business_id'),
            ],
            'api_key' => get_option('birdeye_api_key'),
            'count' => 10,
            'minimum_rating' => 5,
            'sindex' => 0,
            'with_comments' => true
        ];

        $error_network_problem = "The review module was unable to load the most recent reviews.";
        $error_not_enough_reviews = "The review module was unable to load the most recent reviews.";

        if(empty($user_settings))
        {
            // Use default settings if none are provided.
            $settings = $default_settings;
        }
        else
        {
            // Replace default settings with settings provided by user.
            $settings = array_replace_recursive($default_settings, $user_settings);
        }

        // Begin setting up the CURL request to BirdEye.
        $ch = curl_init();

        curl_setopt(
            $ch,
            CURLOPT_URL,
            $settings['api_endpoint']['protocol'] . '//' .
                $settings['api_endpoint']['hostname'] .
                $settings['api_endpoint']['pathname'] .
                '?api_key=' . $settings['api_key'] .
                '&sindex=' . $settings['sindex'] .
                '&count=' . $settings['count']
        );

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
        curl_setopt($ch, CURLOPT_POST, FALSE);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Accept: application/json"
        ]);

        /*
         *  If successful, the $response should be an array of review objects. Returns
         *  false on failure.
         */
        $json_response = curl_exec($ch);
        curl_close($ch);

        /*
         *  Check to see if our API call was successful.
         */
        if($json_response)
        {
            // Turn the JSON into something PHP can work with.
            $response = json_decode($json_response);

            /*
             *  If applicable, remove reviews that don't meet the minimum rating or don't have comments.
             */
            $filtered_reviews = array_filter($response, function($value) use($settings) {
                return intval($value->rating) >= $settings['minimum_rating'] &&
                       isset($value->comments) == $settings['with_comments'];
            });

            return count($filtered_reviews) > 0 ? $filtered_reviews : $error_not_enough_reviews;
        }
        else
        {
            return $error_network_problem;
        }
    }


    // Trims long reviews to 300 characters and creates a "Read More" link.
    function tseg_truncate_comments($string, $read_more_url = '#')
    {
        $string = strip_tags($string);

        if (strlen($string) > 300) {

            // truncate string
            $stringCut = substr($string, 0, 300);

            // make sure it ends in a word so assassinate doesn't become ass...
            $string = substr($stringCut, 0, strrpos($stringCut, ' ')) . '... <a href="' . $read_more_url . '" target="_blank">Read More</a>'; 
        }

        return $string;
    }

?>
