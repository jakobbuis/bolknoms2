<?php

namespace App\Http\Controllers;

use App\Http\Helpers\OAuth;
use App\Models\Meal;
use App\Models\Registration;
use App\Services\DeregisterService;
use App\Services\DoubleRegistrationException;
use App\Services\MealDeadlinePassedException;
use App\Services\RegisterService;
use App\Services\UserBlockedException;
use App\Services\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Log;
use Validator;

class Register extends Application
{
    /**
      * Show the index that allows users to quickly register for the upcoming meal
      * @return View
      */
    public function index()
    {
        $data = [];

        // Add more data if we have a current user
        if (OAuth::valid()) {
            $data['meals'] = Meal::available()->get();
            $data['user'] = OAuth::user();
        }
        else {
            $data['meals'] = Meal::available()->take(1)->get();
        }

        return $this->setPageContent(view('register/index', $data));
    }

    public function aanmelden(Request $request)
    {
        $data = $request->all();

        // Populate the data from the session if not passed
        if (! $request->has('name')) {
            $user = OAuth::user();
            if (!$user) {
                return response()->json([
                    'error' => 'user_not_found',
                    'error_details' => 'Je gebruikersaccount kon niet worden gevonden. Probeer opnieuw in te loggen.'
                ], 500);
            }
            $data['user_id'] = $user->id;
            $data['name'] = $user->name;
            $data['email'] = $user->email;
            $data['handicap'] = $user->handicap;
        }

        // Create registration
        try {
            $registration = with(new RegisterService($data, false))->execute();
        }
        catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'meal_not_found',
                'error_details' => 'De maaltijd waarvoor je je probeert aan te melden bestaat niet'
            ], 404);
        }
        catch (ValidationException $e) {
            return response()->json([
                'error' => 'input_invalid',
                'error_details' => 'Naam of e-mailadres niet ingevuld of ongeldig',
            ], 400);
        }
        catch (MealDeadlinePassedException $e) {
            return response()->json([
                'error' => 'meal_deadline_expired',
                'error_details' => 'De aanmeldingsdeadline is verstreken'
            ], 400);
        }
        catch (UserBlockedException $e) {
            return response()->json([
                'error'         => 'user_blocked',
                'error_details' => 'Je bent geblokkeerd op bolknoms. Je kunt je niet aanmelden voor maaltijden.',
            ], 403);
        }
        catch (DoubleRegistrationException $e) {
            return response()->json([
                'error'         => 'double_registration',
                'error_details' => 'Je bent al aangemeld voor deze maaltijd',
            ], 400);
        }

        // Return succesfull registration
        return response(null, 204);
    }

    /**
     * Unsubscribe a user from a meal
     * @return JSON
     */
    public function afmelden(Request $request)
    {
        // Find the meal
        $meal = Meal::find((int) $request->input('meal_id'));
        if (!$meal) {
            return response()->json([
                'error' => 'meal_not_found',
                'error_details' => 'De maaltijd bestaat niet'
            ], 404);
        }

        // Find the registration data
        $user = OAuth::user();
        $registration = $user->registrationFor($meal);
        if (!$registration) {
            return response()->json([
                'error' => 'no_registration',
                'error_details' => 'Je bent niet aangemeld voor deze maaltijd'
            ], 404);
        }

        // Deregister from the meal
        try {
            with(new DeregisterService($registration, false))->execute();
        }
        catch(MealDeadlinePassedException $e) {
            return response()->json([
                'error' => 'meal_deadline_expired',
                'error_details' => 'De aanmeldingsdeadline is verstreken'
            ], 400);
        }
        return response(null, 204);
    }
}
