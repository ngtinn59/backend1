<?php

namespace App\Http\Controllers\Api\Candidates;

use App\Http\Controllers\Controller;
use App\Models\Objective; // Import the Objective model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ObjectivesController extends Controller
{
    public function index()
    {
        $candidate_id = Auth::user()->id;
        $objectives = Objective::where('candidate_id', $candidate_id)->get();
        return response()->json($objectives);
    }

    public function store(Request $req)
    {

        // Validate input data
        $validator = Validator::make($req->all(), [

        ], [

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors(),
                'status_code' => 422, // 422 Unprocessable Entity
            ], 422);
        }

        // Get candidate ID
        $candidate_id = Auth::user()->id;

        // Create new Objective record
        Objective::create([
            'candidate_id' => $candidate_id,
            'jtype_id' => $req->jtype_id,
            'jlevel_id' => $req->jlevel_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo mục tiêu thành công.',
            'status_code' => 201,
        ]);
    }

    public function update(Request $req, $id)
    {
        // Validate input data
        $validator = Validator::make($req->all(), [
            'objective' => 'required|string|max:255',
            'desired_location' => 'nullable|string|max:255',
            'desired_salary' => 'nullable|integer',
            'preferred_gender' => 'nullable|integer',
            'experience_years' => 'nullable|integer',
            'jtype_id' => 'required|exists:job_types,id',
            'jlevel_id' => 'required|exists:job_levels,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors(),
                'status_code' => 422, // 422 Unprocessable Entity
            ], 422);
        }

        // Update the Objective record
        $objective = Objective::findOrFail($id);
        $objective->update($req->all());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật mục tiêu thành công.',
        ]);
    }

    public function destroy($id)
    {
        // Find and delete the Objective record
        Objective::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa mục tiêu thành công.',
        ]);
    }
}
