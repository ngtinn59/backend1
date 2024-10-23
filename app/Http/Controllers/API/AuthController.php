<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Candidate;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Employer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

//use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt', [
            'except' => ['login', 'register', 'employerRegister'],
        ]);
    }

    public function login(Request $request)
    {
        // Xác thực dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không đúng định dạng.',
            'password.required' => 'Mật khẩu là bắt buộc.',
        ]);

        // Nếu validation thất bại, trả về lỗi
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors(),
                'status_code' => 422, // 422 Unprocessable Entity
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Kiểm tra email có tồn tại không
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email chưa được đăng ký.',
                    'status_code' => 404, // 404 Not Found
                ], 404);
            }

            // Kiểm tra trạng thái tài khoản (is_active)
            if ($user->is_active == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tài khoản đã bị chặn, vui lòng liên hệ quản trị viên.',
                    'status_code' => 403, // 403 Forbidden
                ], 403);
            }

            // Kiểm tra mật khẩu có khớp không
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mật khẩu không chính xác.',
                    'status_code' => 401, // 401 Unauthorized
                ], 401);
            }

            // Kiểm tra vai trò của người dùng
            if ($user->role !== $request->role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vai trò của người dùng không phù hợp.',
                    'status_code' => 403, // 403 Forbidden
                ], 403);
            }

            // Nếu thông tin hợp lệ, tiến hành đăng nhập
            $token = Auth::attempt($request->only('email', 'password'));

            // Kiểm tra lại token, đảm bảo login thành công
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đăng nhập thất bại.',
                    'status_code' => 401,
                ], 401);
            }

            // Nếu role là 1 (ứng viên), lấy thêm thông tin họ và tên
            if ($request->role == 1) {
                $name = User::join('candidates', 'users.id', '=', 'user_id')
                    ->where('users.id', $user->id)
                    ->select('firstname', 'lastname')
                    ->first();
                $user['name'] = $name;
            }

            // Nếu role là 2 (nhà tuyển dụng), lấy thêm thông tin từ model employer
            if ($request->role == 2) {
                $employerInfo = User::join('employers', 'users.id', '=', 'user_id')
                    ->where('users.id', $user->id)
                    ->select('name', 'website') // Chọn các trường cần thiết từ bảng employers
                    ->first();
                $user['employer_info'] = $employerInfo;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'user' => $user,
                'authorization' => [
                    'token' => $token,
                    'type' => 'bearer',
                ],
                'status_code' => 200,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            // Log lỗi nếu có vấn đề
            Log::error('Đăng nhập thất bại: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Đăng nhập thất bại.',
                'status_code' => 500,
            ], 500);
        }
    }



    public function register(Request $request)
    {
        // Xác thực dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:100',
            'lastname' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|', // 'confirmed' yêu cầu trường password_confirmation
        ], [
            'firstname.required' => 'Tên là bắt buộc.',
            'lastname.required' => 'Họ là bắt buộc.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email đã tồn tại.',
            'password.required' => 'Mật khẩu là bắt buộc.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'erro' => $validator->errors(),
                'status_code' => 422, // 422 Unprocessable Entity
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Tạo user mới với dữ liệu từ request
            $user = User::create([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'email' => $request->email,
                'role' => 1, // Gán vai trò cho người dùng
                'is_active' => 1, // Kích hoạt tài khoản
                'password' => Hash::make($request->password), // Mã hóa mật khẩu
            ]);

            // Tạo candidate cho user
            Candidate::create([
                'id' => $user->id, // Sử dụng ID của người dùng
                'user_id' => $user->id, // Liên kết với người dùng
                'firstname' => $request->firstname, // Họ
                'lastname' => $request->lastname, // Tên
                'email' => $request->email // Email
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký thành công.',
                'user' => $user, // Thông tin người dùng
                'status_code' => 201,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            // Log lỗi nếu có vấn đề
            Log::error('Đăng ký thất bại: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Đăng ký thất bại.',
                'status_code' => 500,
            ], 500);
        }
    }


    public function me()
    {
        $user = Auth::user();
        if ($user->role == 2) {
            $user = User::with('employer')->find($user->id);
        }
        if ($user->role == 1) {
            $name = User::join('candidates', 'users.id', '=', 'user_id')
                ->where('users.id', $user->id)
                ->select('firstname', 'lastname')
                ->first();
            $user['name'] = $name;
        }
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json($user);
    }

    public function logout()
    {
        Auth::logout();
        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'user' => Auth::user(),
            'authorization' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ]
        ]);
    }

    public function employerRegister(Request $request)
    {


        // Xác thực dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
        ], [
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'erro' => $validator->errors(),
                'status_code' => 422, // 422 Unprocessable Entity
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Tạo user mới với dữ liệu từ request
            $user = User::create([
                'email' => $request->email,
                'role' => 2,
                'is_active' => 1,
                'password' => Hash::make($request->password),
            ]);

            // Tạo candidate cho user
            Employer::create([
                'id' => $user->id,
                'user_id' => $user->id,
                'name' => $request->name,
                'phone' => $request->phone,
            ]);


            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký thành công.',
                'user' => $user, // Thông tin người dùng
                'status_code' => 201,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            // Log lỗi nếu có vấn đề
            Log::error('Đăng ký thất bại: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Đăng ký thất bại.',
                'status_code' => 500,
            ], 500);
        }
    }
}
