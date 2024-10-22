<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location; // Đảm bảo bạn đã tạo model Location
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminLocationController extends Controller
{
    // Hiển thị danh sách các địa điểm
    public function index()
    {
        $locations = Location::all();
        return response()->json([
            'success' => true,
            'message' => "Lấy danh sách địa điểm thành công",
            'data' => $locations,
            'status_code' => 200
        ]);
    }

    // Tạo địa điểm mới
    public function store(Request $request)
    {
        $data = $request->only('name', 'description'); // Lấy dữ liệu từ request

        $validator = Validator::make($data, [
            'name' => 'required|string|min:3|max:100',
            'description' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Trường tên là bắt buộc.',
            'name.min' => 'Tên địa điểm phải có ít nhất :min ký tự.',
            'name.max' => 'Tên địa điểm không được vượt quá :max ký tự.',
            'description.max' => 'Mô tả không được vượt quá :max ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors(),
                'status_code' => 400
            ], 400);
        }

        $location = Location::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => "Tạo địa điểm thành công!",
            'data' => $location,
            'status_code' => 201 // Created
        ]);
    }

    // Hiển thị chi tiết một địa điểm
    public function show(Location $location)
    {
        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin địa điểm thành công',
            'data' => $location,
            'status_code' => 200
        ]);
    }

    // Cập nhật thông tin địa điểm
    public function update(Request $request, Location $location)
    {
        $data = $request->only('name', 'description');

        $validator = Validator::make($data, [
            'name' => 'required|string|min:3|max:100',
            'description' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Trường tên là bắt buộc.',
            'name.min' => 'Tên địa điểm phải có ít nhất :min ký tự.',
            'name.max' => 'Tên địa điểm không được vượt quá :max ký tự.',
            'description.max' => 'Mô tả không được vượt quá :max ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors(),
                'status_code' => 400
            ], 400);
        }

        $location->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật địa điểm thành công',
            'data' => $location,
            'status_code' => 200
        ]);
    }

    // Xóa địa điểm
    public function destroy(Location $location)
    {
        $location->delete();
        return response()->json([
            'success' => true,
            'message' => 'Xóa địa điểm thành công',
            'status_code' => 200
        ]);
    }
}
