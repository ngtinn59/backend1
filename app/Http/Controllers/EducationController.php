<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Education;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EducationController extends Controller
{
    public function index()
    {
        return Education::all();
    }

    public function getByCurrentCandidateProfile()
    {
        $candidate_id = Auth::user()->id;
        $res = Education::where([
            ["candidate_id", $candidate_id],
            ["resume_id", null]
        ])->get();
        return response()->json($res);
    }

    public function getByCurCandResumeId($resume_id)
    {
        $candidate_id = Auth::user()->id;
        $res = Education::where([
            ["candidate_id", $candidate_id],
            ["resume_id", $resume_id]
        ])->get();
        return response()->json($res);
    }

    public function create(Request $req)
    {
        // Xác thực dữ liệu đầu vào
        $validator = Validator::make($req->all(), [
            'school' => 'required|string|max:255',
            'major' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'description' => 'nullable|string',
        ], [
            'school.required' => 'Trường là bắt buộc.',
            'school.string' => 'Trường phải là chuỗi văn bản.',
            'school.max' => 'Trường không được vượt quá 255 ký tự.',
            'major.required' => 'Chuyên ngành là bắt buộc.',
            'major.string' => 'Chuyên ngành phải là chuỗi văn bản.',
            'major.max' => 'Chuyên ngành không được vượt quá 255 ký tự.',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'start_date.date' => 'Ngày bắt đầu không hợp lệ.',
            'end_date.required' => 'Ngày kết thúc là bắt buộc.',
            'end_date.date' => 'Ngày kết thúc không hợp lệ.',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
            'description.string' => 'Mô tả phải là chuỗi văn bản.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors(),
                'status_code' => 422, // 422 Unprocessable Entity
            ], 422);
        }

        // Lấy ID của ứng viên
        $candidate_id = Auth::user()->id;

        // Tạo bản ghi Education
        Education::create([
            "candidate_id" => $candidate_id,
            "school" => $req->school,
            "major" => $req->major,
            "start_date" => $req->start_date,
            "end_date" => $req->end_date,
            "description" => $req->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo thành công.',
            'status_code' => 201,
        ]);
    }

    public function destroy($id)
    {
        Education::findOrFail($id)->delete();

        return response()->json("deleted successfully");
    }
    public function update(Request $req)
    {
        $update_fields = $req->all();
        Education::where('id', $req->id)->update($update_fields);
        $msg = 'Update successfully';

        return response()->json($msg);
    }
}
