<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jlevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminJobLevelsController extends Controller
{
    // Hiển thị danh sách các cấp bậc công việc
    public function index()
    {
        $jobLevels = Jlevel::all();
        return response()->json([
            'success' => true,
            'message' => "Lấy danh sách cấp bậc công việc thành công",
            'data' => $jobLevels,
            'status_code' => 200
        ]);
    }

    // Tạo cấp bậc công việc mới
    public function store(Request $request)
    {
        $data = $request->only('name'); // Lấy dữ liệu từ request

        $validator = Validator::make($data, [
            'name' => 'required|string|min:3|max:50',
        ], [
            'name.required' => 'Trường tên là bắt buộc.',
            'name.min' => 'Tên cấp bậc công việc phải có ít nhất :min ký tự.',
            'name.max' => 'Tên cấp bậc công việc không được vượt quá :max ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors(),
                'status_code' => 400
            ], 400);
        }

        $jobLevel = Jlevel::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => "Tạo cấp bậc công việc thành công!",
            'data' => $jobLevel,
            'status_code' => 201 // Created
        ]);
    }

    // Hiển thị chi tiết một cấp bậc công việc
    public function show(Jlevel $jobLevel)
    {
        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin cấp bậc công việc thành công',
            'data' => $jobLevel,
            'status_code' => 200
        ]);
    }

    // Cập nhật thông tin cấp bậc công việc
    public function update(Request $request, Jlevel $jobLevel)
    {
        $data = $request->only('name');

        $validator = Validator::make($data, [
            'name' => 'required|string|min:3|max:50',
        ], [
            'name.required' => 'Trường tên là bắt buộc.',
            'name.min' => 'Tên cấp bậc công việc phải có ít nhất :min ký tự.',
            'name.max' => 'Tên cấp bậc công việc không được vượt quá :max ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors(),
                'status_code' => 400
            ], 400);
        }

        $jobLevel->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật cấp bậc công việc thành công',
            'data' => $jobLevel,
            'status_code' => 200
        ]);
    }

    // Xóa cấp bậc công việc
    public function destroy(Jlevel $jobLevel)
    {
        $jobLevel->delete();
        return response()->json([
            'success' => true,
            'message' => 'Xóa cấp bậc công việc thành công',
            'status_code' => 200
        ]);
    }
}
