<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Induction; // Ensure you have the Induction model
use App\Models\Industry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InductionsController extends Controller
{
    // Display a list of Inductions
    public function index()
    {
        $inductions = Industry::all();
        return response()->json([
            'success' => true,
            'message' => "Lấy danh sách ngành nghề thành công",
            'data' => $inductions,
            'status_code' => 200
        ]);
    }

    // Create a new Induction
    public function store(Request $request)
    {
        $data = $request->only('name'); // Retrieve data from the request

        $validator = Validator::make($data, [
            'name' => 'required|string|min:3|max:50',
        ], [
            'name.required' => 'Trường tên là bắt buộc.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors(),
                'status_code' => 400
            ], 400);
        }

        $induction = Industry::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => "Tạo induction thành công!",
            'data' => $induction,
            'status_code' => 200
        ]);
    }

    // Display details of a specific Induction
    public function show(Industry $induction)
    {
        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin ngành nghề thành công',
            'data' => $induction,
            'status_code' => 200
        ]);
    }

    // Update Induction information
    public function update(Request $request, Industry $induction)
    {
        $data = $request->only('name',);

        $validator = Validator::make($data, [
            'name' => 'required|string|min:2|max:50',
        ], [
            'name.required' => 'Trường tên là bắt buộc.',
            'name.min' => 'Tên induction phải có ít nhất :min ký tự.',
            'name.max' => 'Tên induction không được vượt quá :max ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors(),
                'status_code' => 400
            ], 400);
        }

        $induction->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật ngành nghề thành công',
            'data' => $induction,
            'status_code' => 200
        ]);
    }

    // Delete an Induction
    public function destroy(Industry $induction)
    {
        $induction->delete();
        return response()->json([
            'success' => true,
            'message' => 'Xóa ngành nghề thành công',
            'status_code' => 200
        ]);
    }
}
