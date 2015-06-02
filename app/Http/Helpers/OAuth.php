<?php

namespace App\Http\Helpers;

use Session;
use App;
use GuzzleHttp\Client;

class OAuth
{
    /**
     * Return whether we have a valid session
     * @access public
     * @static
     * @return boolean
     */
    public static function valid()
    {
        // Must have a token
        if (! Session::has('oauth.token')) {
            return false;
        }

        // Refresh the token if needed
        if (self::tokenIsExpired()) {
            self::refreshToken();
        }

        return self::tokenIsValid();
    }

    /**
     * Returns the current user details or null if none
     * @return App\Models\User
     */
    public static function user()
    {
        if (! OAuth::valid()) {
            return null;
        }

        // Refresh details if needed
        if (Session::get('oauth.user_info', null) === null) {
            self::retrieveDetails();
        }

        $id       = Session::get('oauth.user_info')->id;
        $name     = Session::get('oauth.user_info')->name;
        $photoURL = Session::get('oauth.user_info')->photoURL;
        return new App\Models\User($id, $name, $photoURL);
    }

    /**
     * Get the details of this user
     */
    private static function retrieveDetails()
    {
        $user = new \stdClass();
        $client = new Client();
        $token = Session::get('oauth.token')->access_token;

        // Get the user ID
        $url = env('OAUTH_ENDPOINT').'resource/?access_token='.$token;
        $response = $client->get($url);
        $user->id = json_decode($response->getBody())->user_id;

        // Get full name
        $url = 'https://people.debolk.nl/persons/'.$user->id.'/name?access_token='.$token;
        $response = $client->get($url);
        $user->name = json_decode($response->getBody())->name;

        // Get picture
        $user->photoURL = 'https://people.debolk.nl/persons/'.$user->id.'/photo/128/128?access_token='.$token;

        // Store data
        Session::set('oauth.user_info', $user);
        Session::save();
    }

    /**
     * Returns whether the token is not expired
     * @access private
     * @static
     * @return boolean
     */
    private static function tokenIsExpired()
    {
        $now = new \DateTime();
        $expiry = Session::get('oauth.token')->expires_at;

        // Subtract one minute to allow for clock drift
        $expiry = $expiry->sub(new \DateInterval('PT1M'));

        return ($expiry <= $now);
    }

    /**
     * Refreshes the token
     * THIS FUNCTION MAY APP::ABORT()
     * @access private
     * @static
     * @return void
     */
    private static function refreshToken()
    {
        try {
            $client = new Client();
            $response = $client->post(env('OAUTH_ENDPOINT').'token/', ['json' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => Session::get('oauth.token')->refresh_token,
                'client_id' => env('OAUTH_CLIENT_ID'),
                'client_secret' => env('OAUTH_CLIENT_SECRET'),
            ]]);
        }
        catch (\Exception $e) {
            Session::remove('oauth');
            App::abort(500, 'Fatal error while refreshing OAuth2 token');
        }

        $token = json_decode($response->getBody());

        // Do not proceed if we encounter an error
        if (isset($token->error)) {
            Session::remove('oauth');
            App::abort(500, $token->error_description);
        }

        // Calculate expiration date of token
        $token->created_at = new \DateTime();
        $token->expires_at = new \DateTime("+{$token->expires_in} seconds");

        // Must refresh photoURL too
        self::retrieveDetails();

        // Overwrite the token with the new token
        Session::put('oauth.token', $token);
    }

    /**
     * Redirect the client to the authorisation server to login
     * @return Redirect
     */
    public static function toAuthorisationServer($original_route)
    {
        // Store the URL we attempt to visit
        Session::put('oauth.goal', $original_route);

        // Generate a random six digit number as state to defend against CSRF-attacks
        $state = rand(pow(10, 5), pow(10, 6)-1);
        Session::put('oauth.state', $state);

        // For some reason, an explicit save is needed in middleware
        Session::save();

        // Redirect to the oauth endpoint for authentication
        $query_string = http_build_query([
            'response_type' => 'code',
            'client_id' => env('OAUTH_CLIENT_ID'),
            'client_pass' => env('OAUTH_CLIENT_SECRET'),
            'redirect_uri' => env('OAUTH_CALLBACK'),
            'state'=> $state,
        ]);
        return redirect(env('OAUTH_ENDPOINT').'authenticate/?'.$query_string);
    }

    /**
     * Returns whether the token is valid
     * @access private
     * @static
     * @return boolean
     */
    private static function tokenIsValid()
    {
        try {
            $client = new Client();
            $url = env('OAUTH_ENDPOINT').'resource/?access_token='.Session::get('oauth.token')->access_token;
            $request = $client->get($url);
            return ($request->getStatusCode() === 200);
        }
        catch(\Exception $e) {
            return false;
        }
    }

    /**
     * Check whether the current user has board-level permissions
     * @access public
     * @static
     * @return boolean
     */
    public static function isBoardMember()
    {
        try {
            $client = new Client();
            $url = env('OAUTH_ENDPOINT').'bestuur/?access_token='.Session::get('oauth.token')->access_token;
            $request = $client->get($url);
            return ($request->getStatusCode() === 200);
        }
        catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Forget the currently logged-in user
     * @return void
     */
    public static function logout()
    {
        Session::remove('oauth');
    }

    /**
     * Process the OAuth authorisation callback, storing the session
     * THIS FUNCTION MAY APP::ABORT()
     * @static
     * @access public
     * @param  array $input Input::get() is the only acceptable input here
     * @return string a URL to redirect to
     */
    public static function processCallback($input)
    {
        // Check state to prevent CSRF
        if ((string)$input['state'] !== (string)Session::get('oauth.state')) {
            App::abort(400, 'OAuth state mismatch');
        }

        // Check for errors
        if (isset($input['error'])) {
            // Denying permission is not actually an error, redirect to frontpage
            if ($input['error'] === 'access_denied') {
                return '/';
            }
            else {
                Session::remove('oauth');
                App::abort(500, 'Unknown error while processing OAuth authorisation callback');
            }
        }

        // Retrieve access code
        try {
            $client = new Client();
            $result = $client->post(env('OAUTH_ENDPOINT').'token/', [
                'json' => [
                    'grant_type' => 'authorization_code',
                    'code' => $input['code'],
                    'redirect_uri' => env('OAUTH_CALLBACK'),
                    'client_id' => env('OAUTH_CLIENT_ID'),
                    'client_secret' => env('OAUTH_CLIENT_SECRET'),
                ],
            ]);
        }
        catch (\Exception $e) {
            Session::remove('oauth');
            App::abort(500, 'Fatal error while trading authorisation code for a token');
        }

        $token = json_decode($result->getBody());

        // Do not proceed if we encounter an error
        if (isset($token->error)) {
            Session::remove('oauth');
            App::abort(500, 'Fatal error in authentication token');
        }

        // Determine expiry time
        $token->created_at = new \DateTime();
        $token->expires_at = new \DateTime("+{$token->expires_in} seconds");

        // Store the token
        Session::put('oauth.token', $token);

        // Redirect to the original URL
        return Session::get('oauth.goal');

    }
}
