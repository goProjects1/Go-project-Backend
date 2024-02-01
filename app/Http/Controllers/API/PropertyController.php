<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Property as PropertyResource;


class PropertyController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        // Retrieve all properties
        $properties = Property::all();
        return $this->sendResponse(PropertyResource::collection($properties), 'Properties fetched.');
    }

    /**
     * Display a listing of the user's properties.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserProperty(Request $request): JsonResponse
    {
        // Pagination settings
        $perPage = $request->input('per_page', 10);

        // Retrieve user's properties with pagination
        $userProperties = Property::where('user_id', Auth::user()->getAuthIdentifier())->paginate($perPage);

        return response()->json($userProperties);
    }

    public function store(Request $request)
    {
        try {

            // Loop through each item in the array and create properties
            foreach ($request->all() as $propertyData) {
                $this->createProperty($propertyData);
            }

            return $this->sendResponse(null, 'Properties created.');
        } catch (\Exception $e) {
            return $this->sendError('Error creating property.', $e->getMessage(), 500);
        }
    }

    private function createProperty($data)
    {
        // Create a new property and associate it with the authenticated user
        $property = Property::create($data);
        $property->user_id = Auth::user()->getAuthIdentifier();
        $property->save();
    }





    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function show(int $id): Response
    {
        // Find property by ID
        $property = Property::find($id);

        // If property does not exist, return error response
        if (is_null($property)) {
            return $this->sendError('Property does not exist.');
        }

        return $this->sendResponse(new PropertyResource($property), 'Property fetched.');
    }


    public function update(Request $request, int $propertyId)
    {
        try {
            // Find property by ID
            $property = Property::findOrFail($propertyId);

            // Update property fields
            $property->update([
                'type' => $request->input('type', $property->type),
                'registration_no' => $request->input('registration_no', $property->registration_no),
                'license_no' => $request->input('license_no', $property->license_no),
                'user_id' => Auth::user()->getAuthIdentifier(),
            ]);

            return $this->sendResponse(new PropertyResource($property), 'Property updated.');
        } catch (\Exception $e) {
            return $this->sendError('Error updating property.', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Property $property
     * @return Response
     */
    public function destroy(Property $property): Response
    {
        // Delete the property
        $property->delete();

        return $this->sendResponse([], 'Property deleted.');
    }
}
