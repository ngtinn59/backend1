<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User; // Giả sử bạn sử dụng model User để quản lý tài khoản
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminAccountsController extends Controller
{
    // Hiển thị danh sách tài khoản
    public function index()
    {
        $accounts = User::all();
        $dataAccounts = $accounts->map(function ($account) {
            $roleLabel = '';
            switch ($account->role) {
                case 1:
                    $roleLabel = 'Người tìm việc'; // Job Seeker
                    break;
                case 2:
                    $roleLabel = 'Nhà tuyển dụng'; // Recruiter
                    break;
                case 3:
                    $roleLabel = 'Admin'; // Admin
                    break;
                default:
                    $roleLabel = 'Khách'; // Guest or Unknown Role
            }

            return [
                'id' => $account->id,
                'email' => $account->email,
                'role' => $roleLabel,
                'is_active' => $account->is_active
            ];
        });

        return response()->json([
            'success' => true,
            'message' => "Lấy danh sách tài khoản thành công",
            'data' => $dataAccounts,
            'status_code' => 200
        ]);
    }


    // Tạo tài khoản mới
    public function store(Request $request)
    {
        $data = $request->only('email', 'password'); // Lấy dữ liệu từ request

        $validator = Validator::make($data, [
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:6',
        ], [
            'name.required' => 'Trường tên là bắt buộc.',
            'email.required' => 'Trường email là bắt buộc.',
            'password.required' => 'Trường mật khẩu là bắt buộc.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors(),
                'status_code' => 400
            ], 400);
        }

        // Mã hóa mật khẩu trước khi lưu
        $data['password'] = bcrypt($data['password']);
        $account = User::create($data);

        return response()->json([
            'success' => true,
            'message' => "Tạo tài khoản thành công!",
            'data' => $account,
            'status_code' => 201 // Created
        ]);
    }

    // Hiển thị chi tiết một tài khoản
    public function show(User $account)
    {
        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin tài khoản thành công',
            'data' => $account,
            'status_code' => 200
        ]);
    }

    // Cập nhật thông tin tài khoản
    public function update(Request $request, User $account)
    {
        $data = $request->only('name', 'email', 'password');

        $validator = Validator::make($data, [
            'name' => 'required|string|min:3|max:50',
            'email' => 'required|string|email|max:100|unique:users,email,' . $account->id,
            'password' => 'nullable|string|min:6', // Mật khẩu có thể không cần thiết
        ], [
            'name.required' => 'Trường tên là bắt buộc.',
            'email.required' => 'Trường email là bắt buộc.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xác thực',
                'errors' => $validator->errors(),
                'status_code' => 400
            ], 400);
        }

        // Cập nhật mật khẩu nếu có
        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']); // Xóa mật khẩu nếu không được cung cấp
        }

        $account->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật tài khoản thành công',
            'data' => $account,
            'status_code' => 200
        ]);
    }

    // Xóa tài khoản
    public function destroy(User $account)
    {
        $account->delete();
        return response()->json([
            'success' => true,
            'message' => 'Xóa tài khoản thành công',
            'status_code' => 200
        ]);
    }
}
