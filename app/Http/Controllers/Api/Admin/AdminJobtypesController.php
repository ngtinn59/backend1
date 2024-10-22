<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jtype;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminJobtypesController extends Controller
{
    // Hiển thị danh sách các loại công việc
    public function index()
    {
        $jobTypes = Jtype::all();
        return response()->json([
            'success' => true,
            'message' => "Lấy danh sách loại công việc thành công",
            'data' => $jobTypes,
            'status_code' => 200
        ]);
    }

    // Tạo loại công việc mới
    public function store(Request $request)
    {
        $data = $request->only('name'); // Lấy dữ liệu từ request

        $validator = Validator::make($data, [
            'name' => 'required|string|min:3|max:50',
        ], [
            'name.required' => 'Trường tên là bắt buộc.',
            'name.min' => 'Tên loại công việc phải có ít nhất :min ký tự.',
            'name.max' => 'Tên loại công việc không được vượt quá :max ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors(),
                'status_code' => 400
            ], 400);
        }

        $jobType = Jtype::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => "Tạo loại công việc thành công!",
            'data' => $jobType,
            'status_code' => 201 // Created
        ]);
    }

    // Hiển thị chi tiết một loại công việc
    public function show(Jtype $jobType)
    {
        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin loại công việc thành công',
            'data' => $jobType,
            'status_code' => 200
        ]);
    }

    // Cập nhật thông tin loại công việc
    public function update(Request $request, Jtype $jobType)
    {
        $data = $request->only('name');

        $validator = Validator::make($data, [
            'name' => 'required|string|min:3|max:50',
        ], [
            'name.required' => 'Trường tên là bắt buộc.',
            'name.min' => 'Tên loại công việc phải có ít nhất :min ký tự.',
            'name.max' => 'Tên loại công việc không được vượt quá :max ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors(),
                'status_code' => 400
            ], 400);
        }

        $jobType->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật loại công việc thành công',
            'data' => $jobType,
            'status_code' => 200
        ]);
    }

    // Xóa loại công việc
    public function destroy(Jtype $jobType)
    {
        $jobType->delete();
        return response()->json([
            'success' => true,
            'message' => 'Xóa loại công việc thành công',
            'status_code' => 200
        ]);
    }
}
