<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Resources\Student as StudentResource;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class StudentController extends BaseController
{
    /**
     * Display a listing of the students.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Retrieve all students
        $students = Student::all();

        // Send a success response with the collection of students
        return $this->sendResponse(StudentResource::collection($students), 'Students fetched.');
    }

    /**
     * Display a listing of the user's schools with pagination.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserSchool(Request $request): JsonResponse
    {
        // Pagination settings
        $perPage = $request->input('per_page', 10);

        // Retrieve user's schools with pagination
        $userSchool = Student::where('user_id', Auth::user()->getAuthIdentifier())->paginate($perPage);

        // Return a JSON response with paginated user schools
        return response()->json($userSchool);
    }


    public function store(Request $request)
    {
        try {
            // Validation rules
            $validator = Validator::make($request->all(), [
                'institution' => 'required',
                'student_no' => 'required',
                'course' => 'required',
            ]);

            // If validation fails, return error response
            if ($validator->fails()) {
                return $this->sendError($validator->errors(), 'Validation Error', 422);
            }

            // Create a new school and associate it with the authenticated user
            $school = Student::create($request->all());
            $school->user_id = Auth::user()->getAuthIdentifier();

            // Return a success response with the created school
            return $this->sendResponse(new StudentResource($school), 'School created.');
        } catch (\Exception $e) {
            // Return an error response in case of an exception
            return $this->sendError('Error creating School.', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified school.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        // Find school by ID
        $school = Student::find($id);

        // If school does not exist, return error response
        if (is_null($school)) {
            return $this->sendError('School does not exist.');
        }

        // Return a success response with the fetched school
        return $this->sendResponse(new StudentResource($school), 'School fetched.');
    }

    public function update(Request $request, $schoolId)
    {
        try {
            // Find school by ID
            $school = Student::findOrFail($schoolId);

            // Update school fields
            $school->update([
                'institution' => $request->input('institution', $school->institution),
                'course' => $request->input('course', $school->course),
                'student_no' => $request->input('student_no', $school->student_no),
            ]);
		$school->user_id = Auth::user()->getAuthIdentifier();
            // Return a success response with the updated school
            return $this->sendResponse(new StudentResource($school), 'School updated.');
        } catch (\Exception $e) {
            // Return an error response in case of an exception
            return $this->sendError('Error updating school.', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified school from storage.
     *
     * @param Student $school
     * @return JsonResponse
     */
    public function destroy(Student $school): JsonResponse
    {
        // Delete the school
        $school->delete();

        // Return a success response
        return $this->sendResponse([], 'School deleted.');
    }
}
