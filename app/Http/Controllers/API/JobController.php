<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Job as JobResource;
use Illuminate\Http\JsonResponse;

class JobController extends BaseController
{
    /**
     * Display a listing of the jobs.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Retrieve all jobs
        $jobs = Job::all();

        // Send a success response with the collection of jobs
        return $this->sendResponse(JobResource::collection($jobs), 'Jobs fetched.');
    }

    /**
     * Display a listing of the user's jobs with pagination.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserJob(Request $request): JsonResponse
    {
        // Pagination settings
        $perPage = $request->input('per_page', 10);

        // Retrieve user's jobs with pagination
        $userJob = Job::where('user_id', Auth::user()->getAuthIdentifier())->paginate($perPage);

        // Return a JSON response with paginated user jobs
        return response()->json($userJob);
    }


    public function store(Request $request)
    {
        try {
            // Validation rules
            $validator = Validator::make($request->all(), [
                'sector' => 'required',
                'job_title' => 'required',
            ]);

            // If validation fails, return error response
            if ($validator->fails()) {
                return $this->sendError($validator->errors(), 'Validation Error', 422);
            }

            // Create a new job and associate it with the authenticated user
            $job = Job::create($request->all());
            $job->user_id = Auth::user()->getAuthIdentifier();

            // Return a success response with the created job
            return $this->sendResponse(new JobResource($job), 'Job created.');
        } catch (\Exception $e) {
            // Return an error response in case of an exception
            return $this->sendError('Error creating job.', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified job.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        // Find job by ID
        $job = Job::find($id);

        // If job does not exist, return error response
        if (is_null($job)) {
            return $this->sendError('Job does not exist.');
        }

        // Return a success response with the fetched job
        return $this->sendResponse(new JobResource($job), 'Job fetched.');
    }


    public function update(Request $request, int $jobId)
    {
        try {
            // Find job by ID
            $job = Job::findOrFail($jobId);

            // Update job fields
            $job->update([
                'sector' => $request->input('sector', $job->sector),
                'job_type' => $request->input('job_type', $job->job_type),
                'license_no' => $request->input('license_no', $job->license_no),

            ]);
		 $job->user_id = Auth::user()->getAuthIdentifier();
            // Return a success response with the updated job
            return $this->sendResponse(new JobResource($job), 'Job updated.');
        } catch (\Exception $e) {
            // Return an error response in case of an exception
            return $this->sendError('Error updating job.', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified job from storage.
     *
     * @param Job $job
     * @return JsonResponse
     */
    public function destroy(Request $request, int $jobId): JsonResponse
    {
        $job= Job::find($jobId);

        if (!$jobId) {
            return $this->sendError('Job not found.');
        }

        $job->delete();

        return $this->sendResponse([], 'Job deleted.');
    }

    public function deletedJobs(Request $request): JsonResponse
    {
        $deletedJobs = Job::onlyTrashed()->paginate($request->get('perPage', 10));

        return $this->sendResponse($deletedJobs, 'Deleted Jobs retrieved successfully.');
    }
}
