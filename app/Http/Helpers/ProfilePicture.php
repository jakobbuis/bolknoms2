<?php

namespace App\Http\Helpers;

use App\Http\Helpers\OAuth;
use App\Models\User;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ProfilePicture
{
    private $oauth;

    public function __construct(OAuth $oauth)
    {
        $this->oauth = $oauth;
    }

    /**
     * Updates the local cached profile picture of a user
     * @param  User   $user
     * @return void
     */
    public function updatePictureFor(User $user)
    {
        // Calculate location of image
        $path = $this->picturePathFor($user);

        try {
            $client = new Client;
            $token = $this->oauth->getAccessToken();
            $url = 'https://people.debolk.nl/persons/'.$user->username.'/photo/256/256?access_token='.$token;
            $file = fopen($path, 'w');
            $response = $client->get($url, ['sink' => $file]);
        }
        catch (\Exception $exception) {
            Bugsnag::notifyException($exception);
            // No handling needed, we'll just not have an image available
            return;
        }

        // Check the mimetype of the resulting image
        // to make sure we have a valid image
        if (file_exists($path) && substr(mime_content_type($path), 0, 6) !== "image/") {
            unlink($path);
        }
    }

    /**
     * Return a picture for a specific user
     * @param  User   $user
     * @return string
     */
    public function getPictureFor(User $user)
    {
        $path = $this->picturePathFor($user);

        if (File::exists($path)) {
            return File::get($path);
        }

        // Try downloading a new file once if needed (and possible)
        if ($this->oauth->valid()) {
            $this->updatePictureFor($user);
        }

        // If the file still doesn't exist, return the swedish chef
        if (File::exists($path)) {
            return File::get($path);
        }
        else {
            return File::get(public_path() . '/images/swedishchef.jpg');
        }
    }

    /**
     * Correct mime-type to use for serving a profile picture of a user
     * @param  User   $user
     * @return string
     */
    public function mimetypeFor(User $user)
    {
        return mime_content_type($this->picturePathFor($user));
    }

    /**
     * Determine the path to the a specific profile picture
     * @param  User   $user user to get a picture for
     * @return string       full filesystem path to picture
     */
    private function picturePathFor(User $user)
    {
        return base_path() . '/uploads/profile_pictures/' . $user->id;
    }
}
