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
     * @return JsonResponse
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
        $data = $request->json()->all();

        if ($this->isValidPropertyData($data)) {
            $this->storeProperties($data);

            return response()->json(['message' => 'Data stored successfully'], 201);
        } else {
            return response()->json(['error' => 'Invalid data format'], 400);
        }
    }

    private function isValidPropertyData($data)
    {
        if (is_array($data)) {
            foreach ($data as $propertyData) {
                if (!isset($propertyData['type'], $propertyData['registration_no'], $propertyData['license_no'])) {
                    return false;
                }
            }
        } else {
            return isset($data['type'], $data['registration_no'], $data['license_no']);
        }
        return true;
    }

    private function storeProperties($data)
    {
        if (is_array($data)) {
            foreach ($data as $propertyData) {
                $this->storeProperty($propertyData);
            }
        } else {
            $this->storeProperty($data);
        }
    }

    private function storeProperty($propertyData)
    {
        Property::create([
            'type' => $propertyData['type'],
            'registration_no' => $propertyData['registration_no'],
            'license_no' => $propertyData['license_no'],
            'user_id' => Auth::user()->getAuthIdentifier(),
        ]);
    }
    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        // Find property by ID
        $property = Property::find($id);

        // If property does not exist, return error response
        if (is_null($property)) {
            return $this->sendError('Property does not exist.');
        }

        return $this->sendResponse(new PropertyResource($property), 'Property fetched.');
    }

    public function update(Request $request, int $propertyId): JsonResponse
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
     * @return JsonResponse
     */
    public function destroy(Request $request, int $propertyId): JsonResponse
    {
        $property = Property::find($propertyId);

        if (!$property) {
            return $this->sendError('Property not found.');
        }

        $property->delete();

        return $this->sendResponse([], 'Property deleted.');
    }

    public function deletedProperties(Request $request): JsonResponse
    {
        $deletedProperties = Property::onlyTrashed()->paginate($request->get('perPage', 10));

        return $this->sendResponse($deletedProperties, 'Deleted properties retrieved successfully.');
    }

}
